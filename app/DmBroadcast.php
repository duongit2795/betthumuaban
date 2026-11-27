<?php
defined('APP') or exit;

/**
 * Nhắn riêng cho thành viên nhóm qua bot. Telegram chỉ cho bot nhắn người đã bấm Start (từng nhắn riêng cho bot),
 * nên bot ghi nhận ai đã Start (bot_starts) và chỉ gửi cho những người đó.
 * Gửi chạy nền theo hàng đợi, song song ~25 tin / giây / bot (gần giới hạn 30 tin/giây của Telegram),
 * tự chờ khi bị giới hạn (429), tự đánh dấu người đã chặn bot.
 */
class DmBroadcast
{
    const PER_SECOND = 25;
    const VARS = ['{name}' => 'Tên', '{first_name}' => 'Tên riêng', '{username}' => '@username', '{group}' => 'Tên nhóm'];
    const STATUS = ['queued' => 'Đang chờ', 'sending' => 'Đang gửi', 'done' => 'Đã gửi xong', 'stopped' => 'Đã dừng'];

    // ================= Ghi nhận người đã Start bot =================
    public static function seen(int $botId, $tgUserId): void
    {
        if (!$botId || !$tgUserId) return;
        if (!DB::run('UPDATE bot_starts SET blocked = 0, updated_at = ? WHERE bot_id = ? AND tg_user_id = ?', [now(), $botId, $tgUserId])) {
            try { DB::insert('bot_starts', ['bot_id' => $botId, 'tg_user_id' => $tgUserId, 'started_at' => now(), 'updated_at' => now()]); } catch (PDOException $e) {}
        }
    }

    /** Người dùng chặn / bỏ chặn bot (my_chat_member trong chat riêng) */
    public static function privateStatus(int $botId, $tgUserId, string $status): void
    {
        if ($status === 'kicked') DB::run('UPDATE bot_starts SET blocked = 1, updated_at = ? WHERE bot_id = ? AND tg_user_id = ?', [now(), $botId, $tgUserId]);
        elseif ($status === 'member') self::seen($botId, $tgUserId);
    }

    // ================= Người nhận =================
    private static function recipientSql(array $groupIds, bool $includeLeft): string
    {
        $st = $includeLeft ? "('member','muted','left')" : "('member','muted')";
        return 'FROM tg_members m JOIN bot_starts s ON s.tg_user_id = m.tg_user_id AND s.bot_id = ? AND s.blocked = 0
            WHERE m.group_id IN (' . (implode(',', array_map('intval', $groupIds)) ?: '0') . ") AND m.is_bot = 0 AND m.is_admin IN (0, 1) AND m.status IN $st";
    }

    /** Số thành viên / số người nhận được của 1 nhóm */
    public static function groupStats(array $g): array
    {
        $members = (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND status IN ('member','muted')", [$g['id']]);
        $reach = $g['bot_id'] ? (int)DB::val('SELECT COUNT(DISTINCT m.tg_user_id) ' . self::recipientSql([(int)$g['id']], false), [$g['bot_id']]) : 0;
        return ['members' => $members, 'reachable' => $reach];
    }

    /** Nhóm theo bot: [bot_id => [group_id…]] */
    private static function byBot(array $groupIds): array
    {
        $out = [];
        foreach ($groupIds as $gid) {
            $g = Repo::group((int)$gid);
            if ($g && $g['active'] && $g['bot_id']) $out[(int)$g['bot_id']][] = (int)$g['id'];
        }
        return $out;
    }

    public static function count(array $groupIds, bool $includeLeft): int
    {
        $n = 0;
        foreach (self::byBot($groupIds) as $botId => $gids) $n += (int)DB::val('SELECT COUNT(DISTINCT m.tg_user_id) ' . self::recipientSql($gids, $includeLeft), [$botId]);
        return $n;
    }

    /** Đọc nút "Chữ | https://link" mỗi dòng */
    public static function parseButtons(string $raw): array
    {
        $rows = [];
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $p = array_map('trim', explode('|', $line, 2));
            if (count($p) < 2 || $p[0] === '' || !preg_match('~^(https?://|tg://)\S+$~i', $p[1])) throw new HttpError('Nút không hợp lệ: "' . mb_substr($line, 0, 40) . '". Mỗi dòng: Chữ trên nút | https://link');
            $rows[] = [['text' => mb_substr($p[0], 0, 64), 'url' => $p[1]]];
            if (count($rows) >= 8) break;
        }
        return $rows;
    }

    /** Tạo đợt gửi (mỗi bot 1 đợt). Trả về danh sách id đợt gửi. */
    public static function create(array $groupIds, string $text, ?string $image, string $buttons, bool $silent, bool $includeLeft, string $by): array
    {
        $text = trim($text);
        if ($text === '' && !$image) throw new HttpError('Nhập nội dung tin nhắn');
        if (mb_strlen(strip_tags($text)) > ($image ? 1024 : 4096)) throw new HttpError($image ? 'Kèm ảnh thì nội dung tối đa 1024 ký tự' : 'Nội dung tối đa 4096 ký tự');
        $btn = self::parseButtons($buttons);
        $ids = [];
        foreach (self::byBot($groupIds) as $botId => $gids) {
            $rows = DB::all('SELECT m.tg_user_id, MIN(m.group_id) AS gid ' . self::recipientSql($gids, $includeLeft) . ' GROUP BY m.tg_user_id', [$botId]);
            if (!$rows) continue;
            $cid = DB::insert('dm_campaigns', ['bot_id' => $botId, 'group_ids' => json_encode($gids), 'text' => $text, 'image' => $image, 'buttons' => $btn ? json_encode($btn, JSON_UNESCAPED_UNICODE) : null,
                'silent' => $silent ? 1 : 0, 'status' => 'queued', 'total' => count($rows), 'created_by' => mb_substr($by, 0, 190), 'created_at' => now()]);
            DB::tx(function () use ($rows, $cid) {
                foreach ($rows as $r) DB::insert('dm_queue', ['campaign_id' => $cid, 'tg_user_id' => $r['tg_user_id'], 'group_id' => (int)$r['gid'], 'status' => 'q']);
            });
            foreach ($gids as $gid) Repo::log($gid, $by, 'dm_send', null, 'Nhắn riêng ' . count($rows) . ' thành viên qua bot (đợt #' . $cid . ')', ['source' => 'web']);
            $ids[] = $cid;
        }
        if (!$ids) throw new HttpError('Chưa có thành viên nào nhận được tin (chưa ai Start bot). Bấm "Mời thành viên Start bot" để tăng số người nhận.');
        return $ids;
    }

    // ================= Gửi =================
    private static function api(int $botId): ?Telegram
    {
        if (Repo::$testApi) return Repo::$testApi;
        $tok = DB::val('SELECT token FROM tg_bots WHERE id = ? AND enabled = 1', [$botId]);
        return $tok ? new Telegram($tok) : null;
    }

    /** Nội dung riêng cho 1 người */
    private static function personal(string $text, array $m, array $groups): string
    {
        $first = (string)($m['first_name'] ?? '');
        return strtr($text, ['{name}' => tg_esc(trim($first . ' ' . ($m['last_name'] ?? '')) ?: 'bạn'), '{first_name}' => tg_esc($first ?: 'bạn'),
            '{username}' => !empty($m['username']) ? '@' . tg_esc($m['username']) : tg_esc($first ?: 'bạn'), '{group}' => tg_esc($groups[(int)($m['group_id'] ?? 0)] ?? '')]);
    }

    private static function params(array $c, string $text, $chatId): array
    {
        $p = ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'disable_notification' => (bool)$c['silent']];
        if ($c['buttons']) $p['reply_markup'] = ['inline_keyboard' => json_decode($c['buttons'], true)];
        if ($c['image']) { $p['caption'] = $text; if ($c['file_id']) $p['photo'] = $c['file_id']; }
        else { $p['text'] = $text; $p['link_preview_options'] = ['is_disabled' => true]; }
        return $p;
    }

    /** Gửi thử cho 1 người (VD chính admin) */
    public static function test(int $groupId, $tgUserId, string $text, ?string $image, string $buttons): void
    {
        $g = Repo::group($groupId) ?: throw new HttpError('Chọn nhóm');
        $api = self::api((int)$g['bot_id']) ?: throw new HttpError('Nhóm chưa có bot hoạt động');
        $c = ['silent' => 0, 'image' => $image, 'file_id' => null, 'buttons' => ($b = self::parseButtons($buttons)) ? json_encode($b, JSON_UNESCAPED_UNICODE) : null];
        $m = Repo::member($groupId, $tgUserId) ?: ['first_name' => 'bạn', 'group_id' => $groupId];
        $p = self::params($c, self::personal($text, $m + ['group_id' => $groupId], [$groupId => $g['title']]), $tgUserId);
        try {
            if ($image) { unset($p['photo']); $api->callMultipart('sendPhoto', $p, ['photo' => Ads::imagePath($image)]); }
            else $api->call('sendMessage', $p);
        } catch (TelegramException $e) {
            throw new HttpError(stripos($e->getMessage(), 'chat not found') !== false || stripos($e->getMessage(), 'blocked') !== false
                ? 'Bot chưa nhắn được cho bạn: hãy mở @' . DB::val('SELECT username FROM tg_bots WHERE id = ?', [$g['bot_id']]) . ' và bấm Start trước.' : 'Gửi thử lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Xử lý hàng đợi trong tối đa $seconds giây (gọi sau khi tạo đợt gửi và từ cron mỗi phút).
     * Mỗi lượt gửi song song tối đa PER_SECOND tin rồi chờ cho đủ 1 giây.
     */
    public static function run(int $seconds = 55): int
    {
        $lock = @fopen(DATA_DIR . '/dm.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return 0; // tiến trình khác đang gửi
        $end = microtime(true) + $seconds;
        $sentTotal = 0;
        try {
            while (microtime(true) < $end) {
                $c = DB::get("SELECT * FROM dm_campaigns WHERE status IN ('sending','queued') ORDER BY id LIMIT 1");
                if (!$c) break;
                if ($c['status'] === 'queued') { DB::run("UPDATE dm_campaigns SET status = 'sending', started_at = ? WHERE id = ?", [now(), $c['id']]); $c['status'] = 'sending'; }
                $api = self::api((int)$c['bot_id']);
                if (!$api) { DB::run("UPDATE dm_campaigns SET status = 'stopped', last_error = ?, finished_at = ? WHERE id = ?", ['Bot đã tắt hoặc bị xoá', now(), $c['id']]); continue; }
                $t0 = microtime(true);
                $n = self::batch($api, $c);
                $sentTotal += $n;
                if ($n === 0) continue;
                $wait = 1.0 - (microtime(true) - $t0);
                if ($wait > 0) usleep((int)($wait * 1e6));
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        return $sentTotal;
    }

    /** 1 lượt gửi song song. Trả về số tin đã xử lý. */
    private static function batch(Telegram $api, array $c): int
    {
        $cid = (int)$c['id'];
        $groups = [];
        foreach (json_decode((string)$c['group_ids'], true) ?: [] as $gid) $groups[(int)$gid] = (string)DB::val('SELECT title FROM tg_groups WHERE id = ?', [$gid]);
        $rows = DB::all("SELECT q.id, q.tg_user_id, q.group_id, m.first_name, m.last_name, m.username FROM dm_queue q LEFT JOIN tg_members m ON m.group_id = q.group_id AND m.tg_user_id = q.tg_user_id
            WHERE q.campaign_id = ? AND q.status = 'q' ORDER BY q.id LIMIT " . self::PER_SECOND, [$cid]);
        if (!$rows) { self::finish($cid); return 0; }

        // Có ảnh: gửi tin đầu tiên kèm tệp để lấy file_id, các tin sau dùng lại file_id (gửi song song được)
        if ($c['image'] && !$c['file_id']) {
            $r = array_shift($rows);
            $p = self::params($c, self::personal((string)$c['text'], $r, $groups), $r['tg_user_id']);
            unset($p['photo']);
            $path = Ads::imagePath($c['image']);
            try {
                if (!$path) throw new TelegramException('Ảnh đã bị xoá');
                $res = $api->callMultipart('sendPhoto', $p, ['photo' => $path]);
                $fid = end($res['photo'])['file_id'] ?? null;
                DB::run('UPDATE dm_campaigns SET file_id = ? WHERE id = ?', [$fid, $cid]);
                $c['file_id'] = $fid;
                self::mark($cid, $r, ['ok' => true], (int)$c['bot_id']);
            } catch (TelegramException $e) {
                self::mark($cid, $r, ['ok' => false, 'code' => $e->tgCode, 'error' => $e->getMessage()], (int)$c['bot_id']);
                if (!$path) { DB::run("UPDATE dm_campaigns SET status = 'stopped', last_error = ?, finished_at = ? WHERE id = ?", ['Ảnh đã bị xoá', now(), $cid]); return 1; }
            }
            if (!$c['file_id']) return 1; // chưa lấy được file_id: thử với người tiếp theo ở lượt sau
        }
        if (!$rows) return 1;
        $params = array_map(fn($r) => self::params($c, self::personal((string)$c['text'], $r, $groups), $r['tg_user_id']), $rows);
        $res = $api->multi($c['image'] ? 'sendPhoto' : 'sendMessage', $params);
        $retry = 0;
        foreach ($rows as $i => $r) {
            $x = $res[$i] ?? ['ok' => false, 'error' => 'Không có phản hồi'];
            if (!$x['ok'] && (int)($x['code'] ?? 0) === 429) { $retry = max($retry, (int)($x['retry_after'] ?? 1)); continue; } // để lại hàng đợi, gửi lại sau
            self::mark($cid, $r, $x, (int)$c['bot_id']);
        }
        if ($retry) { DB::run('UPDATE dm_campaigns SET last_error = ? WHERE id = ?', ["Telegram yêu cầu chậm lại {$retry}s", $cid]); sleep(min(30, $retry)); }
        // Đợt bị dừng giữa chừng thì không gửi tiếp
        if (DB::val('SELECT status FROM dm_campaigns WHERE id = ?', [$cid]) === 'stopped') return count($rows);
        if (!DB::val("SELECT 1 FROM dm_queue WHERE campaign_id = ? AND status = 'q' LIMIT 1", [$cid])) self::finish($cid);
        return count($rows);
    }

    private static function mark(int $cid, array $r, array $x, int $botId): void
    {
        if ($x['ok']) {
            DB::run("UPDATE dm_queue SET status = 'ok' WHERE id = ?", [$r['id']]);
            DB::run('UPDATE dm_campaigns SET sent = sent + 1 WHERE id = ?', [$cid]);
            return;
        }
        $err = (string)($x['error'] ?? '');
        $gone = (int)($x['code'] ?? 0) === 403 || stripos($err, 'chat not found') !== false || stripos($err, 'blocked') !== false || stripos($err, 'deactivated') !== false;
        DB::run("UPDATE dm_queue SET status = 'err', error = ? WHERE id = ?", [mb_substr($err, 0, 190), $r['id']]);
        DB::run('UPDATE dm_campaigns SET failed = failed + 1' . ($gone ? ', blocked = blocked + 1' : '') . ' WHERE id = ?', [$cid]);
        if ($gone) DB::run('UPDATE bot_starts SET blocked = 1, updated_at = ? WHERE bot_id = ? AND tg_user_id = ?', [now(), $botId, $r['tg_user_id']]); // không gửi lại lần sau
    }

    private static function finish(int $cid): void
    {
        DB::run("UPDATE dm_campaigns SET status = 'done', finished_at = ? WHERE id = ? AND status = 'sending'", [now(), $cid]);
    }

    public static function stop(int $cid): void
    {
        DB::run("UPDATE dm_campaigns SET status = 'stopped', finished_at = ? WHERE id = ? AND status IN ('queued','sending')", [now(), $cid]);
    }

    public static function row(array $c): array
    {
        $gids = json_decode((string)$c['group_ids'], true) ?: [];
        return ['id' => (int)$c['id'], 'status' => $c['status'], 'total' => (int)$c['total'], 'sent' => (int)$c['sent'], 'failed' => (int)$c['failed'], 'blocked' => (int)$c['blocked'],
            'text' => mb_substr(strip_tags((string)$c['text']), 0, 140), 'image' => (bool)$c['image'], 'created_by' => $c['created_by'], 'last_error' => $c['last_error'],
            'groups' => array_map(fn($id) => (string)DB::val('SELECT title FROM tg_groups WHERE id = ?', [$id]), $gids),
            'created_at' => (int)$c['created_at'], 'started_at' => $c['started_at'] ? (int)$c['started_at'] : null, 'finished_at' => $c['finished_at'] ? (int)$c['finished_at'] : null];
    }

    /** Đăng lời mời Start bot vào nhóm (nút mở chat riêng với bot) */
    public static function invite(array $g, string $text, string $button): void
    {
        $api = Repo::apiForGroup($g) ?: throw new HttpError('Nhóm chưa có bot hoạt động');
        $bot = DB::val('SELECT username FROM tg_bots WHERE id = ?', [$g['bot_id']]);
        if (!$bot) throw new HttpError('Bot chưa có username');
        $api->call('sendMessage', ['chat_id' => $g['chat_id'], 'text' => trim($text) ?: '📩 Bấm nút bên dưới để <b>nhận thông báo riêng</b> từ nhóm qua bot (chỉ cần bấm Start 1 lần).', 'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [[['text' => trim($button) ?: '📩 Nhận thông báo riêng', 'url' => "https://t.me/$bot?start=dm"]]]]]);
    }
}

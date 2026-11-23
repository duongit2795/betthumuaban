<?php
defined('APP') or exit;

/**
 * Yêu cầu mở band: thành viên bị cấm (hoặc người giúp) gửi yêu cầu trên web — chọn nhóm, ID, @username, lý do bị band.
 * Admin nhóm nhận tin riêng qua bot (có nút Duyệt / Từ chối) và xem trên web; trong nhóm có thông báo kèm link gửi yêu cầu.
 * Duyệt → bot gỡ band, gửi link vào lại, gắn tag "Từng Bị Band" khi người đó vào lại nhóm.
 * Cài đặt theo nhóm (GroupFeature 'unban_request').
 */
class UnbanRequest
{
    const KEY = 'unban_request';
    const STATUS = ['pending' => 'Chờ duyệt', 'approved' => 'Đã mở band', 'rejected' => 'Từ chối'];
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],
        'notify_admins' => true,      // nhắn riêng cho admin nhóm (kèm nút duyệt)
        'notify_group' => true,       // thông báo vào nhóm khi có yêu cầu mới
        'group_text' => "📨 Có yêu cầu <b>mở band</b> mới cho {who}.\nAi bị band trong nhóm có thể gửi yêu cầu tại: {link}",
        'notify_done' => true,        // thông báo vào nhóm khi được duyệt
        'done_text' => "✅ {who} đã được admin <b>mở band</b>.",
        'autodel' => 600,             // tự xoá thông báo trong nhóm (giây, 0 = không)
        'tag' => 'Từng Bị Band',      // gắn khi vào lại nhóm (để trống = không gắn)
        'send_invite' => true,        // gửi link vào lại nhóm (dùng 1 lần)
        'need_banned' => true,        // chỉ nhận yêu cầu khi ID đang bị band thật (hỏi Telegram)
    ];

    public static function config(?array $group = null): array
    {
        return GroupFeature::resolve(self::KEY, self::DEFAULTS, $group);
    }

    public static function save(array $in, ?array $group = null, string $by = ''): array
    {
        $c = self::config($group);
        unset($c['_custom']);
        foreach (['enabled', 'notify_admins', 'notify_group', 'notify_done', 'send_invite', 'need_banned'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['autodel'])) $c['autodel'] = max(0, min(86400, (int)$in['autodel']));
        if (isset($in['tag'])) $c['tag'] = MemberTag::clean((string)$in['tag']);
        foreach (['group_text', 'done_text'] as $k) {
            if (isset($in[$k])) { $t = trim(mb_substr((string)$in[$k], 0, 1000)); $c[$k] = $t !== '' ? $t : self::DEFAULTS[$k]; }
        }
        GroupFeature::store(self::KEY, $c, $group, $by);
        return self::config($group);
    }

    public static function applies(array $group): bool
    {
        $c = self::config($group);
        return $c['enabled'] && $group['active'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    public static function link(array $group): string
    {
        return site_url() . '/#/unban/' . (int)$group['id'];
    }

    public static function row(array $r): array
    {
        return ['id' => (int)$r['id'], 'group_id' => (int)$r['group_id'], 'group' => $r['group_title'] ?? null, 'tg_user_id' => (string)$r['tg_user_id'],
            'username' => $r['username'], 'name' => $r['name'], 'reason' => $r['reason'], 'requester' => $r['requester_name'],
            'status' => $r['status'], 'admin_note' => $r['admin_note'], 'decided_by' => $r['decided_by'], 'decided_at' => $r['decided_at'] ? (int)$r['decided_at'] : null,
            'invite_link' => $r['invite_link'], 'created_at' => (int)$r['created_at']];
    }

    private static function who(array $r): string
    {
        $t = $r['username'] ? '@' . tg_esc($r['username']) : ($r['name'] ? tg_esc($r['name']) : '');
        return trim($t . ' (ID <code>' . $r['tg_user_id'] . '</code>)');
    }

    /** Gửi yêu cầu */
    public static function submit(array $u, int $groupId, string $tgId, string $username, string $reason): array
    {
        $group = Repo::group($groupId);
        if (!$group || !self::applies($group)) throw new HttpError('Nhóm này chưa nhận yêu cầu mở band');
        $tgId = trim($tgId);
        if (!preg_match('/^\d{4,15}$/', $tgId)) throw new HttpError('ID Telegram phải là dãy số (xem hướng dẫn lấy ID)');
        $username = ltrim(trim($username), '@');
        if ($username !== '' && !preg_match('/^[A-Za-z0-9_]{4,32}$/', $username)) throw new HttpError('@username không hợp lệ');
        $reason = trim(mb_substr($reason, 0, 1000));
        if (mb_strlen($reason) < 5) throw new HttpError('Nhập lý do bị band (ít nhất 5 ký tự)');
        $gid = (int)$group['id'];
        if (DB::val("SELECT id FROM unban_requests WHERE group_id = ? AND tg_user_id = ? AND status = 'pending'", [$gid, $tgId])) {
            throw new HttpError('ID này đã có yêu cầu đang chờ duyệt ở nhóm này');
        }
        if ((int)DB::val('SELECT COUNT(*) FROM unban_requests WHERE requester_id = ? AND created_at > ?', [$u['id'], now() - 86400]) >= 10) {
            throw new HttpError('Bạn đã gửi quá nhiều yêu cầu hôm nay, thử lại sau', 429);
        }
        $c = self::config($group);
        $api = Repo::apiForGroup($group);
        $name = null;
        if ($api) {
            $cm = $api->tryCall('getChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $tgId]);
            if (is_array($cm)) {
                $name = trim(($cm['user']['first_name'] ?? '') . ' ' . ($cm['user']['last_name'] ?? '')) ?: null;
                if ($username === '' && !empty($cm['user']['username'])) $username = $cm['user']['username'];
                if ($c['need_banned'] && ($cm['status'] ?? '') !== 'kicked') {
                    throw new HttpError('ID ' . $tgId . ' hiện không bị band trong nhóm ' . $group['title'] . '. Kiểm tra lại ID (xem hướng dẫn lấy ID).');
                }
            }
        }
        if (!$name && ($m = Repo::member($gid, $tgId))) $name = Repo::name($m);
        $id = DB::insert('unban_requests', ['group_id' => $gid, 'tg_user_id' => $tgId, 'username' => $username ?: null, 'name' => $name ? mb_substr($name, 0, 190) : null,
            'reason' => $reason, 'requester_id' => (int)$u['id'], 'requester_name' => mb_substr($u['display_name'] ?: $u['username'], 0, 190), 'status' => 'pending', 'created_at' => now()]);
        $r = DB::get('SELECT * FROM unban_requests WHERE id = ?', [$id]);
        Repo::log($gid, $u['display_name'] ?: $u['username'], 'unban_req', null, 'Yêu cầu mở band cho ' . ($username ? '@' . $username . ' · ' : '') . $tgId . ' · Lý do: ' . mb_substr($reason, 0, 150), ['source' => 'web']);

        if ($api) {
            $who = self::who($r);
            // 1. Nhắn riêng admin nhóm (người đã mở bot mới nhận được)
            if ($c['notify_admins']) {
                $text = "🔓 <b>Yêu cầu mở band</b> · " . tg_esc($group['title']) . "\n👤 Người bị band: $who" . ($name ? ' · ' . tg_esc($name) : '')
                    . "\n📝 Lý do bị band (theo người gửi): " . tg_esc($reason) . "\n🙋 Người gửi: " . tg_esc($r['requester_name']) . "\n🕒 " . date('H:i d/m/Y');
                $kb = ['inline_keyboard' => [[['text' => '✅ Duyệt mở band', 'callback_data' => "ub:$id:1"], ['text' => '❌ Từ chối', 'callback_data' => "ub:$id:0"]]]];
                foreach (self::admins($group, $api) as $aid) $api->trySend($aid, $text, ['reply_markup' => $kb]);
            }
            // 2. Thông báo trong nhóm: để mọi người biết chỗ gửi yêu cầu
            if ($c['notify_group']) {
                $sent = $api->trySend($group['chat_id'], strtr($c['group_text'], ['{who}' => $who, '{name}' => tg_esc($name ?? ''), '{link}' => tg_esc(self::link($group)), '{group}' => tg_esc($group['title'])]),
                    ['link_preview_options' => ['is_disabled' => true]]);
                if (!empty($sent['message_id']) && $c['autodel'] > 0 && $group['bot_id']) DelQueue::add($api, (int)$group['bot_id'], $group['chat_id'], [$sent['message_id']], $c['autodel']);
            }
        }
        return self::row($r + ['group_title' => $group['title']]);
    }

    /** Admin thật của nhóm (bỏ bot) + chủ bot khách thuê */
    private static function admins(array $group, Telegram $api): array
    {
        $ids = array_map('strval', Repo::tgAdmins($group, $api));
        $bots = array_map('strval', array_column(DB::all('SELECT tg_user_id FROM tg_members WHERE group_id = ? AND is_bot = 1', [$group['id']]), 'tg_user_id'));
        $botIds = array_map(fn($b) => explode(':', $b['token'])[0], DB::all('SELECT token FROM tg_bots'));
        $ids = array_values(array_diff($ids, $bots, $botIds));
        if ($owner = Tenant::groupOwner($group)) { $t = Tenant::user($owner); if (!empty($t['tg_user_id'])) $ids[] = (string)$t['tg_user_id']; }
        return array_values(array_unique($ids));
    }

    /**
     * Duyệt / từ chối. Duyệt: gỡ band trên Telegram, gửi link vào lại, gắn tag khi vào lại nhóm.
     */
    public static function decide(int $id, bool $approve, string $by, string $note = ''): array
    {
        $r = DB::get("SELECT * FROM unban_requests WHERE id = ?", [$id]) ?: throw new HttpError('Không tìm thấy yêu cầu', 404);
        if ($r['status'] !== 'pending') throw new HttpError('Yêu cầu này đã được xử lý (' . (self::STATUS[$r['status']] ?? $r['status']) . ' bởi ' . $r['decided_by'] . ')');
        $group = Repo::group((int)$r['group_id']) ?: throw new HttpError('Không tìm thấy nhóm', 404);
        $c = self::config($group);
        $api = Repo::apiForGroup($group);
        $note = trim(mb_substr($note, 0, 190));
        // Giữ yêu cầu trước (2 admin bấm cùng lúc)
        if (!DB::run("UPDATE unban_requests SET status = 'processing' WHERE id = ? AND status = 'pending'", [$id])) throw new HttpError('Yêu cầu này đang được admin khác xử lý');
        $invite = null;
        if ($approve) {
            try {
                Repo::upsertMember((int)$group['id'], ['id' => $r['tg_user_id'], 'first_name' => $r['name'] ?: (string)$r['tg_user_id'], 'username' => $r['username']]);
                Moderation::perform('unban', $group, ['tgUserId' => $r['tg_user_id'], 'actor' => $by, 'source' => 'web', 'notify' => false,
                    'reason' => 'Duyệt yêu cầu mở band #' . $id . ($note !== '' ? ' · ' . $note : ''), 'meta' => ['unban_request' => $id]]);
            } catch (Throwable $e) {
                DB::run("UPDATE unban_requests SET status = 'pending' WHERE id = ?", [$id]);
                throw new HttpError('Không gỡ band được trên Telegram: ' . $e->getMessage());
            }
            if ($api && $c['send_invite']) {
                $l = $api->tryCall('createChatInviteLink', ['chat_id' => $group['chat_id'], 'name' => 'Mở band #' . $id, 'member_limit' => 1, 'expire_date' => now() + 7 * 86400]);
                $invite = $l['invite_link'] ?? ($group['username'] ? 'https://t.me/' . $group['username'] : null);
            }
        }
        DB::run('UPDATE unban_requests SET status = ?, admin_note = ?, decided_by = ?, decided_at = ?, tag_pending = ?, invite_link = ? WHERE id = ?',
            [$approve ? 'approved' : 'rejected', $note ?: null, mb_substr($by, 0, 190), now(), $approve && $c['tag'] !== '' ? 1 : 0, $invite, $id]);
        $r = DB::get('SELECT * FROM unban_requests WHERE id = ?', [$id]);
        $who = self::who($r);
        Repo::log((int)$group['id'], $by, 'unban_req', Repo::member((int)$group['id'], $r['tg_user_id']), ($approve ? 'Duyệt' : 'Từ chối') . " yêu cầu mở band #$id" . ($note !== '' ? " · $note" : ''), ['source' => 'web']);
        if ($api) {
            // Báo cho người bị band (nếu đã mở bot)
            $dm = $approve
                ? "✅ Yêu cầu <b>mở band</b> của bạn ở nhóm <b>" . tg_esc($group['title']) . "</b> đã được duyệt." . ($invite ? "\n🔗 Link vào lại (dùng 1 lần, hạn 7 ngày):\n$invite" : '')
                : "❌ Yêu cầu mở band ở nhóm <b>" . tg_esc($group['title']) . "</b> chưa được duyệt." . ($note !== '' ? "\n📝 " . tg_esc($note) : '');
            $api->trySend($r['tg_user_id'], $dm);
            if ($approve && $c['notify_done']) {
                $sent = $api->trySend($group['chat_id'], strtr($c['done_text'], ['{who}' => $who, '{name}' => tg_esc($r['name'] ?? ''), '{group}' => tg_esc($group['title'])]));
                if (!empty($sent['message_id']) && $c['autodel'] > 0 && $group['bot_id']) DelQueue::add($api, (int)$group['bot_id'], $group['chat_id'], [$sent['message_id']], $c['autodel']);
            }
        }
        return self::row($r + ['group_title' => $group['title']]);
    }

    /** Nút Duyệt / Từ chối trong tin nhắn riêng gửi admin. Trả về true nếu là nút của tính năng này. */
    public static function onCallback(Telegram $api, array $cb): bool
    {
        if (!preg_match('/^ub:(\d+):([01])$/', (string)($cb['data'] ?? ''), $mm)) return false;
        $answer = fn(string $t, bool $alert = false) => $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $t, 'show_alert' => $alert]);
        $r = DB::get('SELECT * FROM unban_requests WHERE id = ?', [(int)$mm[1]]);
        $group = $r ? Repo::group((int)$r['group_id']) : null;
        if (!$r || !$group) { $answer('Không tìm thấy yêu cầu'); return true; }
        $from = (string)$cb['from']['id'];
        $isAdmin = in_array($from, array_map('strval', Repo::tgAdmins($group, $api, true)), true)
            || (($o = Tenant::groupOwner($group)) && (string)(Tenant::user($o)['tg_user_id'] ?? '') === $from);
        if (!$isAdmin) { $answer('Chỉ admin của nhóm mới duyệt được', true); return true; }
        $name = trim(($cb['from']['first_name'] ?? '') . ' ' . ($cb['from']['last_name'] ?? '')) ?: $from;
        try {
            $res = self::decide((int)$r['id'], $mm[2] === '1', $name . ' (Telegram)');
            $answer($res['status'] === 'approved' ? 'Đã mở band ✅' : 'Đã từ chối');
            $line = $res['status'] === 'approved' ? "\n\n✅ <b>Đã duyệt</b> bởi " . tg_esc($name) . ' · ' . date('H:i d/m') : "\n\n❌ <b>Đã từ chối</b> bởi " . tg_esc($name);
        } catch (HttpError $e) {
            $answer($e->getMessage(), true);
            $line = "\n\nℹ️ " . tg_esc($e->getMessage());
        }
        if (!empty($cb['message']['message_id'])) {
            $api->tryCall('editMessageText', ['chat_id' => $cb['message']['chat']['id'], 'message_id' => $cb['message']['message_id'],
                'text' => tg_esc((string)($cb['message']['text'] ?? '')) . $line, 'parse_mode' => 'HTML']);
        }
        return true;
    }

    /** Người được mở band vào lại nhóm → gắn tag "Từng Bị Band" */
    public static function onJoin(array $group, array $user): void
    {
        $r = DB::get("SELECT * FROM unban_requests WHERE group_id = ? AND tg_user_id = ? AND status = 'approved' AND tag_pending = 1 ORDER BY id DESC", [$group['id'], $user['id']]);
        if (!$r) return;
        $tag = self::config($group)['tag'];
        DB::run('UPDATE unban_requests SET tag_pending = 0 WHERE id = ?', [$r['id']]);
        if ($tag === '') return;
        try { MemberTag::set($group, $user['id'], $tag, 'Bot (mở band)', 'manual', false, 'bot'); } catch (Throwable $e) { /* thiếu quyền tag */ }
    }
}

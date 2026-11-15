<?php
defined('APP') or exit;

/**
 * Quảng cáo xoay vòng: 1 tin (ảnh + nội dung + nút) được ghim trong nhóm,
 * bot tự sửa tin đó sang bài kế tiếp mỗi N giây; mỗi ngày đăng + ghim bài mới.
 * Chạy bởi cron.php (mỗi phút, mỗi lần làm việc ~55 giây).
 */
class Ads
{
    const IMG_DIR = DATA_DIR . '/ads';
    const INTERVALS = [10, 15, 20, 30, 60, 120, 300, 600, 900, 1800, 3600];
    const CAPTION_MAX = 1024;   // chú thích ảnh
    const TEXT_MAX = 4096;      // tin chỉ có chữ (chiến dịch gửi theo lịch)
    const EVERY = [1, 2, 3, 5, 10, 15, 20, 30, 45, 60, 90, 120, 180, 240, 360, 480, 720, 1440];
    const DAY_LABELS = ['1' => 'T2', '2' => 'T3', '3' => 'T4', '4' => 'T5', '5' => 'T6', '6' => 'T7', '7' => 'CN'];
    /** Màu nút Telegram (trường style): rỗng = màu mặc định của ứng dụng */
    const STYLES = ['' => 'Mặc định', 'primary' => 'Xanh dương', 'success' => 'Xanh lá', 'danger' => 'Đỏ'];

    // ================= Dữ liệu =================
    public static function campaignRow(array $c): array
    {
        return [
            'id' => (int)$c['id'],
            'name' => $c['name'],
            'enabled' => (int)$c['enabled'],
            'group_ids' => array_map('intval', json_decode((string)$c['group_ids'], true) ?: []),
            'bot_mode' => $c['bot_mode'],
            'bot_username' => $c['bot_username'],
            'has_token' => (bool)$c['bot_token'],
            'interval_sec' => (int)$c['interval_sec'],
            'rotate_order' => $c['rotate_order'],
            'daily_new' => (int)$c['daily_new'],
            'daily_time' => $c['daily_time'],
            'pin' => (int)$c['pin'],
            'pin_silent' => (int)$c['pin_silent'],
            'delete_old' => (int)$c['delete_old'],
            'last_error' => $c['last_error'],
            'type' => $c['type'] ?? 'rotate',
            'sched_mode' => $c['sched_mode'] ?? 'interval',
            'every_min' => (int)($c['every_min'] ?? 60),
            'sched_times' => json_decode((string)($c['sched_times'] ?? ''), true) ?: [],
            'sched_days' => (string)($c['sched_days'] ?? '1234567'),
            'win_from' => (string)($c['win_from'] ?? ''),
            'win_to' => (string)($c['win_to'] ?? ''),
            'silent' => (int)($c['silent'] ?? 1),
            'auto_delete_min' => (int)($c['auto_delete_min'] ?? 0),
        ];
    }

    public static function adRow(array $a): array
    {
        return [
            'id' => (int)$a['id'],
            'campaign_id' => (int)$a['campaign_id'],
            'title' => $a['title'],
            'has_image' => (bool)$a['image'],
            'caption' => (string)$a['caption'],
            'buttons' => json_decode((string)$a['buttons'], true) ?: [],
            'enabled' => (int)$a['enabled'],
            'sort' => (int)$a['sort'],
            'shows' => (int)$a['shows'],
            'updated_at' => (int)$a['updated_at'],
        ];
    }

    /** Chuẩn hoá danh sách nút: [{text, url, row}] — row = số hàng (nút cùng hàng nằm cạnh nhau) */
    public static function cleanButtons($in): array
    {
        $out = [];
        foreach (array_slice((array)$in, 0, 12) as $b) {
            $text = trim(mb_substr((string)($b['text'] ?? ''), 0, 64));
            $url = trim((string)($b['url'] ?? ''));
            if ($text === '' && $url === '') continue;
            if ($text === '') throw new HttpError('Nút bấm cần có chữ hiển thị');
            if (str_starts_with($url, '@')) $url = 'https://t.me/' . substr($url, 1);
            if (!preg_match('~^(https?://|tg://)\S+$~i', $url)) throw new HttpError("Link của nút \"$text\" không hợp lệ (cần https://… hoặc @username)");
            $style = (string)($b['style'] ?? '');
            $emoji = preg_replace('/\D/', '', (string)($b['emoji'] ?? ''));
            $out[] = ['text' => $text, 'url' => $url, 'row' => max(0, (int)($b['row'] ?? count($out)))]
                + (isset(self::STYLES[$style]) && $style !== '' ? ['style' => $style] : [])
                + ($emoji !== '' ? ['emoji' => $emoji] : []);
        }
        return $out;
    }

    public static function keyboard(array $buttons): ?array
    {
        if (!$buttons) return null;
        $rows = [];
        foreach ($buttons as $b) {
            $btn = ['text' => $b['text'], 'url' => $b['url']];
            if (!empty($b['style'])) $btn['style'] = $b['style'];                     // đỏ / xanh lá / xanh dương
            if (!empty($b['emoji'])) $btn['icon_custom_emoji_id'] = $b['emoji'];    // emoji động (cần Premium)
            $rows[(int)$b['row']][] = $btn;
        }
        ksort($rows);
        return ['inline_keyboard' => array_values(array_map(fn($r) => array_slice($r, 0, 4), $rows))];
    }

    /** Lưu ảnh tải lên (data URL base64) → đường dẫn tệp trong data/ads */
    public static function saveImage(string $dataUrl, ?string $old = null): string
    {
        if (!preg_match('~^data:image/(jpeg|jpg|png|webp);base64,(.+)$~s', $dataUrl, $m)) throw new HttpError('Ảnh không hợp lệ (chỉ JPG, PNG, WEBP)');
        $bin = base64_decode($m[2], true);
        if ($bin === false || strlen($bin) < 100) throw new HttpError('Ảnh bị lỗi');
        if (strlen($bin) > 9 * 1024 * 1024) throw new HttpError('Ảnh quá lớn (tối đa 9 MB)');
        if (!@getimagesizefromstring($bin)) throw new HttpError('Tệp không phải ảnh');
        if (!is_dir(self::IMG_DIR) && !@mkdir(self::IMG_DIR, 0755, true)) throw new HttpError('Không tạo được thư mục data/ads');
        @file_put_contents(self::IMG_DIR . '/index.html', '');
        $name = 'ad_' . bin2hex(random_bytes(8)) . '.' . ($m[1] === 'png' ? 'png' : ($m[1] === 'webp' ? 'webp' : 'jpg'));
        if (file_put_contents(self::IMG_DIR . '/' . $name, $bin) === false) throw new HttpError('Không lưu được ảnh');
        if ($old) @unlink(self::IMG_DIR . '/' . basename($old));
        return $name;
    }

    public static function imagePath(?string $name): ?string
    {
        if (!$name) return null;
        $p = self::IMG_DIR . '/' . basename($name);
        return is_file($p) ? $p : null;
    }

    // ================= Bot dùng để đăng =================
    /** @return array{0: ?Telegram, 1: string} [api, khoá bot để nhớ file_id] */
    public static function botFor(array $campaign, array $group): array
    {
        if (Repo::$testApi) return [Repo::$testApi, 'test'];
        if ($campaign['bot_mode'] === 'custom' && $campaign['bot_token']) return [new Telegram($campaign['bot_token']), 'c' . $campaign['id']];
        $botId = $group['bot_id'] ?: DB::val('SELECT id FROM tg_bots WHERE enabled = 1 ORDER BY id LIMIT 1');
        $tok = $botId ? DB::val('SELECT token FROM tg_bots WHERE id = ?', [$botId]) : null;
        return [$tok ? new Telegram($tok) : null, 'b' . $botId];
    }

    // ================= Gửi / sửa bài =================
    private static function fileId(array $ad, string $botKey): ?string
    {
        return (json_decode((string)$ad['file_ids'], true) ?: [])[$botKey] ?? null;
    }

    private static function rememberFile(array $ad, string $botKey, $result): void
    {
        $photos = $result['photo'] ?? null;
        if (!$photos) return;
        $ids = json_decode((string)$ad['file_ids'], true) ?: [];
        $ids[$botKey] = end($photos)['file_id'];
        DB::run('UPDATE tg_ads SET file_ids = ? WHERE id = ?', [json_encode($ids), $ad['id']]);
    }

    /** Đăng bài mới (ảnh + nội dung + nút) */
    private static function sendRaw(Telegram $api, string $botKey, $chatId, array $ad, array $extra = []): array
    {
        $kb = self::keyboard(json_decode((string)$ad['buttons'], true) ?: []);
        if (!$ad['image']) { // bài chỉ có chữ
            $p = ['chat_id' => $chatId, 'text' => mb_substr((string)$ad['caption'], 0, self::TEXT_MAX), 'parse_mode' => 'HTML', 'link_preview_options' => ['is_disabled' => true]] + $extra;
            if ($kb) $p['reply_markup'] = $kb;
            return $api->call('sendMessage', $p);
        }
        $params = ['chat_id' => $chatId, 'caption' => mb_substr((string)$ad['caption'], 0, self::CAPTION_MAX), 'parse_mode' => 'HTML'] + $extra;
        if ($kb) $params['reply_markup'] = $kb;
        $fid = self::fileId($ad, $botKey);
        if ($fid) {
            try { $r = $api->call('sendPhoto', $params + ['photo' => $fid]); }
            catch (TelegramException $e) { $fid = null; } // file_id hỏng → tải ảnh lại
        }
        if (!$fid) {
            $path = self::imagePath($ad['image']);
            if (!$path) throw new HttpError('Bài "' . ($ad['title'] ?: '#' . $ad['id']) . '" chưa có ảnh');
            $r = $api->callMultipart('sendPhoto', $params, ['photo' => $path]);
        }
        self::rememberFile($ad, $botKey, $r);
        return $r;
    }

    /** Sửa tin đang ghim sang bài khác */
    private static function editRaw(Telegram $api, string $botKey, $chatId, int $messageId, array $ad): void
    {
        $kb = self::keyboard(json_decode((string)$ad['buttons'], true) ?: []);
        $media = ['type' => 'photo', 'caption' => mb_substr((string)$ad['caption'], 0, self::CAPTION_MAX), 'parse_mode' => 'HTML'];
        $params = ['chat_id' => $chatId, 'message_id' => $messageId, 'reply_markup' => $kb ?: ['inline_keyboard' => []]];
        $fid = self::fileId($ad, $botKey);
        try {
            if ($fid) {
                $r = $api->call('editMessageMedia', $params + ['media' => $media + ['media' => $fid]]);
            } else {
                $path = self::imagePath($ad['image']);
                if (!$path) throw new HttpError('Bài chưa có ảnh');
                $r = $api->callMultipart('editMessageMedia', $params + ['media' => $media + ['media' => 'attach://photo']], ['photo' => $path]);
            }
            if (is_array($r)) self::rememberFile($ad, $botKey, $r);
        } catch (TelegramException $e) {
            if (stripos($e->getMessage(), 'not modified') !== false) return; // nội dung giống hệt
            throw $e;
        }
    }

    /** Bỏ emoji động khỏi nút (khi bot không có quyền dùng custom emoji) */
    private static function withoutIcons(array $ad): ?array
    {
        $btns = json_decode((string)$ad['buttons'], true) ?: [];
        if (!array_filter($btns, fn($b) => !empty($b['emoji']))) return null;
        $ad['buttons'] = json_encode(array_map(function ($b) { unset($b['emoji']); return $b; }, $btns), JSON_UNESCAPED_UNICODE);
        return $ad;
    }

    public static function send(Telegram $api, string $botKey, $chatId, array $ad, array $extra = []): array
    {
        try {
            return self::sendRaw($api, $botKey, $chatId, $ad, $extra);
        } catch (TelegramException $e) {
            $plain = preg_match('/emoji|icon/i', $e->getMessage()) ? self::withoutIcons($ad) : null;
            if (!$plain) throw $e;
            return self::sendRaw($api, $botKey, $chatId, $plain, $extra); // gửi lại không kèm emoji động
        }
    }

    public static function edit(Telegram $api, string $botKey, $chatId, int $messageId, array $ad): void
    {
        try {
            self::editRaw($api, $botKey, $chatId, $messageId, $ad);
        } catch (TelegramException $e) {
            $plain = preg_match('/emoji|icon/i', $e->getMessage()) ? self::withoutIcons($ad) : null;
            if (!$plain) throw $e;
            self::editRaw($api, $botKey, $chatId, $messageId, $plain);
        }
    }

    // ================= Xoay vòng =================
    /** Bài đang bật của chiến dịch (xoay vòng cần ảnh; gửi theo lịch cho phép bài chỉ có chữ) */
    public static function adsFor(array $c): array
    {
        if (self::isSchedule($c)) {
            return DB::all("SELECT * FROM tg_ads WHERE campaign_id = ? AND enabled = 1 AND (image IS NOT NULL OR (caption IS NOT NULL AND caption <> '')) ORDER BY sort, id", [$c['id']]);
        }
        return DB::all('SELECT * FROM tg_ads WHERE campaign_id = ? AND enabled = 1 AND image IS NOT NULL ORDER BY sort, id', [$c['id']]);
    }

    public static function isSchedule(array $c): bool
    {
        return ($c['type'] ?? 'rotate') === 'schedule';
    }

    // ================= Gửi theo lịch =================
    private static function hm(string $s): ?int
    {
        return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $s, $m) ? (int)$m[1] * 3600 + (int)$m[2] * 60 : null;
    }

    /**
     * Thời điểm gửi kế tiếp (timestamp) cho 1 nhóm, $last = lần gửi trước (0 = chưa gửi).
     * <= now() nghĩa là tới hạn. null = không có lịch (VD không chọn ngày nào).
     */
    public static function nextAt(array $c, int $last): ?int
    {
        $now = now();
        $days = (string)($c['sched_days'] ?? '1234567');
        $today = strtotime('today', $now);
        if (($c['sched_mode'] ?? 'interval') === 'times') {
            $times = array_filter(array_map([self::class, 'hm'], json_decode((string)$c['sched_times'], true) ?: []), fn($x) => $x !== null);
            sort($times);
            for ($i = -1; $i <= 7; $i++) {
                $day = strtotime(($i >= 0 ? "+$i" : $i) . ' day', $today);
                if (!str_contains($days, date('N', $day))) continue;
                foreach ($times as $t) {
                    $slot = $day + $t;
                    if ($slot <= $last) continue;
                    if ($slot >= $now - 600) return $slot; // trễ tối đa 10 phút vẫn gửi (cron chậm)
                }
            }
            return null;
        }
        $cand = max($now, $last ? $last + max(1, (int)$c['every_min']) * 60 : $now);
        $from = self::hm((string)($c['win_from'] ?? ''));
        $to = self::hm((string)($c['win_to'] ?? ''));
        for ($i = -1; $i <= 8; $i++) {
            $day = strtotime(($i >= 0 ? "+$i" : $i) . ' day', $today);
            if (!str_contains($days, date('N', $day))) continue;
            $ws = $day + ($from ?? 0);
            $we = $day + ($to ?? 86400);
            if ($from !== null && $to !== null && $to <= $from) $we += 86400; // khung giờ qua đêm, VD 20:00–02:00
            $t = max($cand, $ws);
            if ($t < $we) return $t;
        }
        return null;
    }

    private static array $failAt = [];

    /** Một lượt cho chiến dịch gửi theo lịch. Trả về số giây tới việc kế tiếp. */
    private static function tickSchedule(array $c, array $ads): int
    {
        $wait = 60;
        $errors = [];
        foreach (json_decode((string)$c['group_ids'], true) ?: [] as $gid) {
            $group = DB::get('SELECT * FROM tg_groups WHERE id = ? AND active = 1', [$gid]);
            if (!$group) continue;
            $fk = $c['id'] . ':' . $gid;
            if (isset(self::$failAt[$fk]) && now() - self::$failAt[$fk] < 120) continue; // lỗi gần đây → thử lại sau 2 phút
            try {
            $post = DB::get('SELECT * FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ? AND active = 1', [$c['id'], $gid]);
            // Tự xoá bài sau N phút
            if ($post && (int)$c['auto_delete_min'] > 0) {
                $delAt = (int)$post['created_at'] + (int)$c['auto_delete_min'] * 60;
                if (now() >= $delAt) {
                    [$api] = self::botFor($c, $group);
                    if ($api) $api->tryCall('deleteMessage', ['chat_id' => $post['chat_id'], 'message_id' => $post['message_id']]);
                    DB::run('UPDATE tg_ad_posts SET active = 0 WHERE id = ?', [$post['id']]);
                    $post = null;
                } else {
                    $wait = min($wait, $delAt - now());
                }
            }
            $last = (int)DB::val('SELECT MAX(created_at) FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ?', [$c['id'], $gid]);
            $lastAd = $last ? (int)DB::val('SELECT ad_id FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ? ORDER BY id DESC LIMIT 1', [$c['id'], $gid]) : null;
            $at = self::nextAt($c, $last);
            if ($at === null) continue;
            if ($at <= now()) {
                self::post($c, $group, $post, $ads, $lastAd);
                $at = self::nextAt($c, now());
                if ($at === null) continue;
            }
            $wait = min($wait, max(1, $at - now()));
            unset(self::$failAt[$fk]);
            } catch (Throwable $e) {
                self::$failAt[$fk] = now();
                $errors[] = $group['title'] . ': ' . $e->getMessage();
            }
        }
        if ($errors) throw new RuntimeException(implode(' | ', $errors));
        return $wait;
    }

    private static function nextAd(array $campaign, array $ads, ?int $currentId): array
    {
        if ($campaign['rotate_order'] === 'random' && count($ads) > 1) {
            $others = array_values(array_filter($ads, fn($a) => (int)$a['id'] !== $currentId));
            return $others[random_int(0, count($others) - 1)];
        }
        foreach ($ads as $i => $a) if ((int)$a['id'] === $currentId) return $ads[($i + 1) % count($ads)];
        return $ads[0];
    }

    /** Mốc đăng bài mới hôm nay (theo giờ cài đặt) */
    private static function dailyMark(array $c): int
    {
        [$h, $mi] = array_map('intval', explode(':', $c['daily_time'] ?: '00:00') + [0, 0]);
        $t = mktime($h, $mi, 0);
        return $t > now() ? $t - 86400 : $t;
    }

    /** Đăng bài mới, ghim, gỡ/xoá bài cũ */
    public static function post(array $c, array $group, ?array $old, array $ads, ?int $afterAdId = null): ?array
    {
        [$api, $botKey] = self::botFor($c, $group);
        if (!$api) throw new HttpError('Chưa có bot để đăng quảng cáo');
        $ad = self::nextAd($c, $ads, $afterAdId);
        $sched = self::isSchedule($c);
        $r = self::send($api, $botKey, $group['chat_id'], $ad, $sched && !empty($c['silent']) ? ['disable_notification' => true] : []);
        if ($c['pin']) {
            try { $api->call('pinChatMessage', ['chat_id' => $group['chat_id'], 'message_id' => $r['message_id'], 'disable_notification' => (bool)$c['pin_silent']]); }
            catch (TelegramException $e) { self::error($c, 'Không ghim được (bot cần quyền ghim tin): ' . $e->getMessage()); }
        }
        if ($old) {
            DB::run('UPDATE tg_ad_posts SET active = 0 WHERE id = ?', [$old['id']]);
            if ($c['delete_old']) $api->tryCall('deleteMessage', ['chat_id' => $old['chat_id'], 'message_id' => $old['message_id']]);
            elseif ($c['pin']) $api->tryCall('unpinChatMessage', ['chat_id' => $old['chat_id'], 'message_id' => $old['message_id']]);
        }
        DB::run('UPDATE tg_ad_posts SET active = 0 WHERE campaign_id = ? AND group_id = ? AND active = 1', [$c['id'], $group['id']]);
        DB::insert('tg_ad_posts', [
            'campaign_id' => $c['id'], 'group_id' => $group['id'], 'chat_id' => $group['chat_id'], 'message_id' => $r['message_id'],
            'bot_key' => $botKey, 'ad_id' => $ad['id'], 'active' => 1, 'last_rotate_at' => now(), 'created_at' => now(),
        ]);
        DB::run('UPDATE tg_ads SET shows = shows + 1 WHERE id = ?', [$ad['id']]);
        if (Settings::get('ads_log') === '1') Repo::log((int)$group['id'], 'Quảng cáo', 'ad_post', null, $c['name'] . ($sched ? ' · gửi theo lịch bài "' : ($c['pin'] ? ' · đăng & ghim bài "' : ' · đăng bài "')) . ($ad['title'] ?: '#' . $ad['id']) . '"', ['source' => 'bot']);
        return DB::get('SELECT * FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ? AND active = 1', [$c['id'], $group['id']]);
    }

    private static function error(array $c, ?string $msg): void
    {
        DB::run('UPDATE tg_ad_campaigns SET last_error = ? WHERE id = ?', [$msg ? mb_substr($msg, 0, 500) : null, $c['id']]);
    }

    /**
     * Một lượt xử lý: đăng bài mới khi cần, đổi bài khi tới hạn.
     * @return int số giây tới lần đổi bài kế tiếp sớm nhất
     */
    public static function tick(): int
    {
        $next = 60;
        foreach (DB::all('SELECT * FROM tg_ad_campaigns WHERE enabled = 1') as $c) {
            $ads = self::adsFor($c);
            if (!$ads) continue;
            if (self::isSchedule($c)) {
                try {
                    $next = min($next, self::tickSchedule($c, $ads));
                    if ($c['last_error']) self::error($c, null);
                } catch (Throwable $e) {
                    self::error($c, $e->getMessage());
                }
                continue;
            }
            $ok = true;
            foreach (json_decode((string)$c['group_ids'], true) ?: [] as $gid) {
                $group = DB::get('SELECT * FROM tg_groups WHERE id = ? AND active = 1', [$gid]);
                if (!$group) continue;
                try {
                    $post = DB::get('SELECT * FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ? AND active = 1', [$c['id'], $gid]);
                    $needNew = !$post || ($c['daily_new'] && (int)$post['created_at'] < self::dailyMark($c));
                    if ($needNew) {
                        self::post($c, $group, $post, $ads, $post ? (int)$post['ad_id'] : null);
                        $next = min($next, (int)$c['interval_sec']);
                        continue;
                    }
                    $due = (int)$post['last_rotate_at'] + (int)$c['interval_sec'];
                    if (count($ads) > 1 && now() >= $due) {
                        $ad = self::nextAd($c, $ads, (int)$post['ad_id']);
                        [$api, $botKey] = self::botFor($c, $group);
                        try {
                            self::edit($api, $botKey, $post['chat_id'], (int)$post['message_id'], $ad);
                        } catch (TelegramException $e) {
                            // Tin đã bị xoá / không sửa được → đăng bài mới
                            if (preg_match('/not found|can\'t be edited|message to edit/i', $e->getMessage())) { self::post($c, $group, $post, $ads, (int)$post['ad_id']); continue; }
                            throw $e;
                        }
                        DB::run('UPDATE tg_ad_posts SET ad_id = ?, last_rotate_at = ? WHERE id = ?', [$ad['id'], now(), $post['id']]);
                        DB::run('UPDATE tg_ads SET shows = shows + 1 WHERE id = ?', [$ad['id']]);
                        $due = now() + (int)$c['interval_sec'];
                    }
                    if (count($ads) > 1) $next = min($next, max(1, $due - now()));
                } catch (Throwable $e) {
                    $ok = false;
                    self::error($c, ($group['title'] ?? '') . ': ' . $e->getMessage());
                }
            }
            if ($ok && $c['last_error']) self::error($c, null);
        }
        return $next;
    }

    /** Chạy liên tục trong $budget giây (gọi từ cron mỗi phút). Có khoá để không chạy chồng. */
    public static function run(int $budget = 55): void
    {
        if (!DB::val('SELECT COUNT(*) FROM tg_ad_campaigns WHERE enabled = 1')) return;
        $lock = @fopen(DATA_DIR . '/ads.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return;
        @set_time_limit($budget + 30);
        ignore_user_abort(true);
        $end = time() + $budget;
        do {
            Settings::set('ads_last_tick', (string)now());
            $wait = self::tick();
            $left = $end - time();
            if ($left <= 1) break;
            sleep(max(1, min($wait, $left - 1)));
        } while (time() < $end);
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /** Gỡ ghim và ngừng bài đang chạy của chiến dịch */
    public static function stop(array $c): void
    {
        foreach (DB::all('SELECT * FROM tg_ad_posts WHERE campaign_id = ? AND active = 1', [$c['id']]) as $p) {
            $group = DB::get('SELECT * FROM tg_groups WHERE id = ?', [$p['group_id']]);
            if ($group) {
                [$api] = self::botFor($c, $group);
                if ($api) $api->tryCall('unpinChatMessage', ['chat_id' => $p['chat_id'], 'message_id' => $p['message_id']]);
            }
            DB::run('UPDATE tg_ad_posts SET active = 0 WHERE id = ?', [$p['id']]);
        }
    }
}

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
    const CAPTION_MAX = 1024;

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
            $out[] = ['text' => $text, 'url' => $url, 'row' => max(0, (int)($b['row'] ?? count($out)))];
        }
        return $out;
    }

    public static function keyboard(array $buttons): ?array
    {
        if (!$buttons) return null;
        $rows = [];
        foreach ($buttons as $b) $rows[(int)$b['row']][] = ['text' => $b['text'], 'url' => $b['url']];
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
    public static function send(Telegram $api, string $botKey, $chatId, array $ad): array
    {
        $kb = self::keyboard(json_decode((string)$ad['buttons'], true) ?: []);
        $params = ['chat_id' => $chatId, 'caption' => mb_substr((string)$ad['caption'], 0, self::CAPTION_MAX), 'parse_mode' => 'HTML'];
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
    public static function edit(Telegram $api, string $botKey, $chatId, int $messageId, array $ad): void
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

    // ================= Xoay vòng =================
    private static function ads(int $campaignId): array
    {
        return DB::all('SELECT * FROM tg_ads WHERE campaign_id = ? AND enabled = 1 AND image IS NOT NULL ORDER BY sort, id', [$campaignId]);
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
        $r = self::send($api, $botKey, $group['chat_id'], $ad);
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
        Repo::log((int)$group['id'], 'Quảng cáo', 'ad_post', null, $c['name'] . ' · đăng & ghim bài "' . ($ad['title'] ?: '#' . $ad['id']) . '"', ['source' => 'bot']);
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
            $ads = self::ads((int)$c['id']);
            if (!$ads) continue;
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

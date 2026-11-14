<?php
defined('APP') or exit;

/**
 * Thông báo bản cập nhật mới qua Telegram cho admin web, xác nhận cập nhật bằng nút bấm.
 */
class UpdateNotifier
{
    public static function enabled(): bool
    {
        return Settings::get('update_notify') !== '0';
    }

    public static function hours(): int
    {
        return max(1, min(168, (int)(Settings::get('update_check_hours') ?: 6)));
    }

    /** Admin web nhận thông báo (đã liên kết Telegram, không bị khoá) */
    public static function recipients(): array
    {
        return DB::all("SELECT * FROM app_users WHERE role = 'admin' AND disabled = 0 AND tg_user_id IS NOT NULL ORDER BY id");
    }

    private static function api(): ?Telegram
    {
        if (Repo::$testApi) return Repo::$testApi;
        $b = DB::get('SELECT token FROM tg_bots WHERE enabled = 1 ORDER BY id LIMIT 1');
        return $b ? new Telegram($b['token']) : null;
    }

    /** Gọi từ cron / webhook: kiểm tra GitHub nếu đã tới hạn, có bản mới thì thông báo */
    public static function maybeCheck(bool $force = false): ?array
    {
        if (!$force && (!self::enabled() || !self::recipients())) return null;
        $last = (int)Settings::get('update_last_check');
        if (!$force && now() - $last < self::hours() * 3600) return null;
        Settings::set('update_last_check', (string)now());
        try {
            $c = Updater::check();
        } catch (Throwable $e) {
            Settings::set('update_last_error', mb_substr($e->getMessage(), 0, 300));
            return null;
        }
        Settings::set('update_last_error', '');
        $sha = $c['latest']['sha'];
        if (!$c['has_update']) return $c;
        if ($sha === Settings::get('update_notified_sha') || $sha === Settings::get('update_skipped_sha')) return $c;
        self::notify($c);
        Settings::set('update_notified_sha', $sha);
        return $c;
    }

    /** Gửi thông báo cho mọi admin. $test = gửi thử dù không có bản mới. */
    public static function notify(array $c, bool $test = false): array
    {
        $api = self::api();
        if (!$api) return [['name' => '—', 'ok' => false, 'error' => 'Chưa có bot nào đang bật']];
        $cur = $c['current'];
        $new = $c['latest'];
        $site = Settings::get('site_name');
        $lines = [];
        foreach (array_slice($c['commits'] ?: [['short' => $new['short'], 'message' => strtok((string)$new['message'], "\n")]], 0, 10) as $cm) {
            $lines[] = '• <code>' . tg_esc($cm['short']) . '</code> ' . tg_esc(mb_substr((string)$cm['message'], 0, 90));
        }
        $more = count($c['commits'] ?? []) > 10 ? "\n… và " . (count($c['commits']) - 10) . ' thay đổi khác' : '';
        $text = ($test ? "🧪 <b>THỬ THÔNG BÁO CẬP NHẬT</b>\n" : '') . "🆕 <b>Có bản cập nhật mới cho " . tg_esc($site) . "</b>\n\n"
            . '📦 Hiện tại: <code>' . tg_esc($cur['sha'] ? substr($cur['sha'], 0, 7) : 'v' . $cur['version']) . "</code>\n"
            . '🚀 Bản mới: <code>' . tg_esc($new['short']) . '</code>' . ($new['date'] ? ' · ' . date('H:i d/m/Y', (int)$new['date']) : '') . "\n"
            . '🌿 Nguồn: ' . tg_esc($c['repo']) . '@' . tg_esc($c['branch']) . "\n\n"
            . "📝 <b>Thay đổi:</b>\n" . implode("\n", $lines) . $more . "\n\n"
            . ($test && !$c['has_update'] ? "ℹ️ Đây là tin thử: web đang ở bản mới nhất, nút cập nhật sẽ không làm gì.\n" : "Bấm <b>Cập nhật ngay</b> để web tự tải và cài bản mới. Dữ liệu được giữ nguyên.");
        $short = substr($new['sha'], 0, 12);
        $kb = [[
            ['text' => '✅ Cập nhật ngay', 'callback_data' => 'upd_ok:' . $short, 'style' => 'success'],
            ['text' => '⏭ Bỏ qua bản này', 'callback_data' => 'upd_skip:' . $short, 'style' => 'danger'],
        ]];
        if (!empty($new['url'])) $kb[] = [['text' => '🔗 Xem trên GitHub', 'url' => $new['url'], 'style' => 'primary']];

        $results = [];
        $sent = [];
        foreach (self::recipients() as $u) {
            try {
                $r = $api->call('sendMessage', ['chat_id' => $u['tg_user_id'], 'text' => $text, 'parse_mode' => 'HTML',
                    'link_preview_options' => ['is_disabled' => true], 'reply_markup' => ['inline_keyboard' => $kb]]);
                $sent[] = ['chat_id' => $u['tg_user_id'], 'message_id' => $r['message_id']];
                $results[] = ['name' => $u['display_name'] ?: $u['username'], 'ok' => true];
            } catch (Throwable $e) {
                $err = $e->getMessage();
                if (stripos($err, 'chat not found') !== false || stripos($err, 'blocked') !== false || stripos($err, "can't initiate") !== false) {
                    $err = 'Admin chưa nhắn /start cho bot (bot không thể nhắn trước)';
                }
                $results[] = ['name' => $u['display_name'] ?: $u['username'], 'ok' => false, 'error' => $err];
            }
        }
        if ($sent) Settings::set('update_notice_msgs', json_encode($sent));
        return $results;
    }

    /** Sửa tin thông báo của mọi admin thành kết quả cuối */
    private static function closeAll(Telegram $api, string $text): void
    {
        foreach (json_decode((string)Settings::get('update_notice_msgs'), true) ?: [] as $m) {
            $api->tryCall('editMessageText', ['chat_id' => $m['chat_id'], 'message_id' => $m['message_id'], 'text' => $text, 'parse_mode' => 'HTML']);
        }
        Settings::set('update_notice_msgs', '[]');
    }

    /** Nút bấm trong tin thông báo. Trả về true nếu đã xử lý. */
    public static function onCallback(Telegram $api, array $cb): bool
    {
        if (!preg_match('/^upd_(ok|skip):([0-9a-f]{7,40})$/', (string)($cb['data'] ?? ''), $m)) return false;
        $answer = fn(string $t, bool $alert = false) => $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $t, 'show_alert' => $alert]);
        $admin = DB::get("SELECT * FROM app_users WHERE tg_user_id = ? AND role = 'admin' AND disabled = 0", [$cb['from']['id']]);
        if (!$admin) { $answer('⛔ Chỉ admin web mới được cập nhật hệ thống', true); return true; }
        $who = $admin['display_name'] ?: $admin['username'];

        if ($m[1] === 'skip') {
            try { $c = Updater::check(); Settings::set('update_skipped_sha', $c['latest']['sha']); } catch (Throwable $e) {}
            $answer('Đã bỏ qua bản này');
            self::closeAll($api, "⏭ <b>Đã bỏ qua bản cập nhật</b> <code>" . tg_esc(substr($m[2], 0, 7)) . "</code>\nBởi " . tg_esc($who) . ' lúc ' . date('H:i d/m/Y') . "\nVẫn có thể cập nhật trên web: Hệ thống → Cập nhật.");
            Repo::log(null, $who . ' (Telegram)', 'update', null, 'Bỏ qua bản ' . substr($m[2], 0, 7));
            return true;
        }

        // Cập nhật: kiểm tra lại bản mới nhất trên GitHub trước khi cài
        $answer('⏳ Đang cập nhật…');
        $edit = fn(string $t) => $api->tryCall('editMessageText', ['chat_id' => $cb['message']['chat']['id'], 'message_id' => $cb['message']['message_id'], 'text' => $t, 'parse_mode' => 'HTML']);
        $edit("⏳ <b>Đang cập nhật…</b>\nĐang tải mã nguồn từ GitHub, vui lòng đợi vài giây.");
        try {
            $c = Updater::check();
            if (!$c['has_update']) {
                self::closeAll($api, "✅ Web đã ở bản mới nhất <code>" . tg_esc($c['latest']['short']) . '</code>.');
                $edit("✅ Web đã ở bản mới nhất <code>" . tg_esc($c['latest']['short']) . '</code>, không cần cập nhật.');
                return true;
            }
            @set_time_limit(300);
            $r = Updater::update();
            Repo::log(null, $who . ' (Telegram)', 'update', null, 'Cập nhật lên ' . substr($r['sha'], 0, 7) . ' (xác nhận qua Telegram)');
            Settings::set('update_notified_sha', $r['sha']);
            // Gọi một request mới để nâng cấp database bằng mã nguồn vừa cài
            self::warmUp();
            self::closeAll($api, "✅ <b>Đã cập nhật lên</b> <code>" . tg_esc(substr($r['sha'], 0, 7)) . "</code>\n"
                . '👮 Bởi: ' . tg_esc($who) . ' (Telegram)' . "\n🕒 Lúc: " . date('H:i d/m/Y') . "\n\n" . tg_esc(implode("\n", $r['log'])));
            $edit("✅ <b>Cập nhật thành công!</b>\n🚀 Bản mới: <code>" . tg_esc(substr($r['sha'], 0, 7)) . "</code>\n🕒 " . date('H:i d/m/Y') . "\n\n" . tg_esc(implode("\n", $r['log'])));
        } catch (Throwable $e) {
            Repo::log(null, $who . ' (Telegram)', 'update', null, 'Cập nhật thất bại: ' . mb_substr($e->getMessage(), 0, 200));
            $edit("❌ <b>Cập nhật thất bại</b>\n" . tg_esc($e->getMessage()) . "\n\nHãy thử lại trên web: Hệ thống → Cập nhật.");
        }
        return true;
    }

    /** Gửi 1 request tới cron.php để chạy migrate bằng mã mới */
    public static function warmUp(): void
    {
        $key = Settings::get('cron_key');
        if (!$key || !function_exists('curl_init')) return;
        $ch = curl_init(site_url() . '/cron.php?key=' . urlencode($key));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 5]);
        @curl_exec($ch);
        curl_close($ch);
    }
}

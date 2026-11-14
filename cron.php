<?php
/**
 * Tác vụ định kỳ (tuỳ chọn). Thêm vào aaPanel > Cron > "Truy cập URL" mỗi 1 phút (để tự động xoá tin đúng giờ):
 *   https://domain/cron.php?key=CRON_KEY
 * hoặc "Shell script":  php /www/wwwroot/domain/cron.php
 */
require __DIR__ . '/app/bootstrap.php';
if (!installed()) exit("not installed\n");
DB::connect();
if ((int)(Settings::get('schema_version') ?? 0) < DB::SCHEMA_VERSION) DB::migrate();
if (PHP_SAPI !== 'cli' && !hash_equals((string)Settings::get('cron_key'), (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
Repo::sweepMutes();
// Xoá tin nhắn hẹn giờ
DelQueue::process(500);
// Kiểm tra bản cập nhật mới, báo admin qua Telegram
UpdateNotifier::maybeCheck();
// Giữ lịch sử chat 30 ngày
ChatLog::cleanup();
// Xoá nhật ký cũ hơn 180 ngày
DB::run('DELETE FROM app_logs WHERE created_at < ?', [now() - 180 * 86400]);
// Dọn file throttle cũ
foreach (glob(DATA_DIR . '/throttle_*.json') ?: [] as $f) if (filemtime($f) < now() - 3600) @unlink($f);
echo "ok\n";

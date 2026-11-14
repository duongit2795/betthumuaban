<?php
/**
 * Tác vụ định kỳ (tuỳ chọn). Thêm vào aaPanel > Cron > "Truy cập URL" mỗi 5 phút:
 *   https://domain/cron.php?key=CRON_KEY
 * hoặc "Shell script":  php /www/wwwroot/domain/cron.php
 */
require __DIR__ . '/app/bootstrap.php';
if (!installed()) exit("not installed\n");
DB::connect();
if (PHP_SAPI !== 'cli' && !hash_equals((string)Settings::get('cron_key'), (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
Repo::sweepMutes();
// Xoá nhật ký cũ hơn 180 ngày
DB::run('DELETE FROM app_logs WHERE created_at < ?', [now() - 180 * 86400]);
// Dọn file throttle cũ
foreach (glob(DATA_DIR . '/throttle_*.json') ?: [] as $f) if (filemtime($f) < now() - 3600) @unlink($f);
echo "ok\n";

<?php
/**
 * Tác vụ định kỳ. aaPanel > Cron > Shell Script, mỗi 1 phút:
 *   /www/server/php/83/bin/php /www/wwwroot/domain/cron.php   (dùng PHP cùng phiên bản với web, xem trang Hệ thống)
 * hoặc Access URL: https://domain/cron.php?key=CRON_KEY
 */
// Bản PHP dòng lệnh thiếu extension cần thiết → báo rõ cách sửa
$missing = array_values(array_filter(['curl', 'pdo', 'json'], fn($e) => !extension_loaded($e)));
if (!extension_loaded('pdo_sqlite') && !extension_loaded('pdo_mysql')) $missing[] = 'pdo_sqlite / pdo_mysql';
if ($missing) {
    $ver = PHP_MAJOR_VERSION . PHP_MINOR_VERSION;
    exit("LỖI: PHP đang chạy cron (" . PHP_BINARY . ", PHP " . PHP_VERSION . ") thiếu extension: " . implode(', ', $missing) . "\n"
        . "Cách sửa: trong aaPanel > Cron, đổi lệnh thành đường dẫn PHP của website, ví dụ:\n"
        . "  /www/server/php/$ver/bin/php " . __FILE__ . "\n"
        . "(thay $ver bằng phiên bản PHP của site; đường dẫn chính xác có ở web: Hệ thống > Tác vụ định kỳ)\n");
}
require __DIR__ . '/app/bootstrap.php';
if (!installed()) exit("not installed\n");
DB::connect();
if ((int)(Settings::get('schema_version') ?? 0) < DB::SCHEMA_VERSION) DB::migrate();
if (PHP_SAPI !== 'cli' && !hash_equals((string)Settings::get('cron_key'), (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
Settings::set('cron_last_run', (string)now());
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
// Dọn dữ liệu chống lặp lệnh cảm xúc cũ hơn 30 ngày
DB::run('DELETE FROM tg_reaction_hits WHERE created_at < ?', [now() - 30 * 86400]);
// Chống raid: hết thời gian khoá → mở lại
try { AntiRaid::sweep(); } catch (Throwable $e) {}
// Captcha: người hết giờ xác minh
try { Captcha::sweep(50); } catch (Throwable $e) {}
// VIP hết hạn: gỡ tag VIP, nhắn báo
try { Vip::expire(); } catch (Throwable $e) {}
try { Tenant::remind(); } catch (Throwable $e) {}
// Gỡ tag đã mua hết hạn
try { MemberTag::expire(); } catch (Throwable $e) {}
// Nạp tiền tự động: đọc lịch sử giao dịch ngân hàng, cộng tiền vào ví
try { Wallet::sync(); } catch (Throwable $e) { Settings::set('pay_last_error', mb_substr($e->getMessage(), 0, 300)); }
// Quảng cáo xoay vòng: chạy liên tục ~55 giây (cron gọi lại mỗi phút)
Ads::run(55);
echo "ok\n";

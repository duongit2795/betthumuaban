<?php
/**
 * Điểm nhận webhook Telegram: https://domain/webhook.php?bot=ID
 * Telegram gửi kèm header X-Telegram-Bot-Api-Secret-Token để xác thực.
 */
require __DIR__ . '/app/bootstrap.php';

if (!installed()) { http_response_code(503); exit; }
$botId = (int)($_GET['bot'] ?? 0);
$secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if (!$botId || !hash_equals(Api::webhookSecret($botId), $secret)) { http_response_code(403); exit('forbidden'); }

$update = json_decode((string)file_get_contents('php://input'), true);
// Trả lời Telegram ngay để tránh gửi lại, rồi mới xử lý
http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
ignore_user_abort(true);
@set_time_limit(60);

if (!is_array($update)) exit;
try {
    DB::connect();
    if ((int)(Settings::get('schema_version') ?? 0) < DB::SCHEMA_VERSION) DB::migrate();
    $bot = DB::get('SELECT * FROM tg_bots WHERE id = ? AND enabled = 1', [$botId]);
    if (!$bot) exit;
    (new BotHandler($bot))->handle($update);
} catch (Throwable $e) {
    error_log('[tele-manager webhook] ' . $e);
    try { DB::run('UPDATE tg_bots SET last_error = ? WHERE id = ?', ['Xử lý: ' . mb_substr($e->getMessage(), 0, 300), $botId]); } catch (Throwable $x) {}
}

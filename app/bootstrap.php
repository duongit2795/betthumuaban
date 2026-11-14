<?php
declare(strict_types=1);

define('APP', true);
define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('CONFIG_FILE', DATA_DIR . '/config.php');
define('APP_VERSION', trim((string)@file_get_contents(ROOT . '/VERSION')) ?: '1.0.0');

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Ho_Chi_Minh');
require __DIR__ . '/polyfill.php'; // thay thế mbstring nếu PHP thiếu (thường gặp ở PHP dòng lệnh của cron)
mb_internal_encoding('UTF-8');

spl_autoload_register(function (string $cls) {
    $f = __DIR__ . '/' . $cls . '.php';
    if (is_file($f)) require $f;
});

function cfg(?string $key = null, $default = null)
{
    static $c = null;
    if ($c === null) $c = is_file(CONFIG_FILE) ? (require CONFIG_FILE) : [];
    if ($key === null) return $c;
    return $c[$key] ?? $default;
}

function installed(): bool
{
    return is_file(CONFIG_FILE);
}

function now(): int
{
    return time();
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape cho parse_mode HTML của Telegram */
function tg_esc($s): string
{
    return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string)$s);
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

class HttpError extends Exception
{
    public int $status;
    public function __construct(string $msg, int $status = 400)
    {
        parent::__construct($msg);
        $this->status = $status;
    }
}

function fail(string $msg, int $status = 400): void
{
    throw new HttpError($msg, $status);
}

function site_url(): string
{
    $u = Settings::get('site_url');
    if ($u) return rtrim($u, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

/** Loại tệp (MIME) không cần extension fileinfo: dựa vào getimagesize, rồi đuôi tệp */
function file_mime(string $path): string
{
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($path);
        if ($m) return $m;
    }
    $info = @getimagesize($path);
    if (!empty($info['mime'])) return $info['mime'];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return ['png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'application/octet-stream';
}

/** Đường dẫn PHP dòng lệnh cùng phiên bản với website (dùng cho lệnh cron) */
function php_cli_path(): string
{
    $bin = PHP_BINARY ?: '';
    // PHP-FPM của aaPanel: /www/server/php/83/sbin/php-fpm → /www/server/php/83/bin/php
    if ($bin && preg_match('~^(.*)/sbin/php-fpm[0-9.]*$~', $bin, $m) && is_file($m[1] . '/bin/php')) return $m[1] . '/bin/php';
    if ($bin && preg_match('~/php[0-9.]*$~', $bin) && is_file($bin)) return $bin;
    if (defined('PHP_BINDIR') && is_file(PHP_BINDIR . '/php')) return PHP_BINDIR . '/php';
    $ver = PHP_MAJOR_VERSION . PHP_MINOR_VERSION;
    if (is_file("/www/server/php/$ver/bin/php")) return "/www/server/php/$ver/bin/php";
    return 'php';
}

function cron_command(): string
{
    return php_cli_path() . ' ' . ROOT . '/cron.php';
}

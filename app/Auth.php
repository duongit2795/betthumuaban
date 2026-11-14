<?php
defined('APP') or exit;

class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_name('tgm_sess');
        $dir = DATA_DIR . '/sessions';
        if (is_dir($dir) || @mkdir($dir, 0700, true)) session_save_path($dir);
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
        session_start();
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    public static function csrf(): string
    {
        return $_SESSION['csrf'] ?? '';
    }

    public static function checkCsrf(): void
    {
        $h = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$h || !hash_equals(self::csrf(), $h)) fail('Phiên làm việc không hợp lệ, hãy tải lại trang', 419);
    }

    public static function user(): ?array
    {
        if (self::$loaded) return self::$user;
        self::$loaded = true;
        $id = $_SESSION['uid'] ?? null;
        if (!$id) return null;
        $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$id]);
        if (!$u || $u['disabled']) {
            unset($_SESSION['uid']);
            return null;
        }
        return self::$user = $u;
    }

    public static function login(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        self::$user = $u;
        self::$loaded = true;
        DB::run('UPDATE app_users SET last_login = ? WHERE id = ?', [now(), $u['id']]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    public static function requireUser(): array
    {
        $u = self::user();
        if (!$u) fail('Vui lòng đăng nhập', 401);
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::requireUser();
        if ($u['role'] !== 'admin') fail('Chỉ admin web mới vào được mục này', 403);
        return $u;
    }

    public static function publicUser(?array $u): ?array
    {
        if (!$u) return null;
        return [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'display_name' => $u['display_name'] ?: $u['username'],
            'role' => $u['role'],
            'tg_user_id' => $u['tg_user_id'] ? (string)$u['tg_user_id'] : null,
            'has_password' => ($u['password_hash'] ?? '') !== '',
            'disabled' => (int)$u['disabled'],
            'created_at' => (int)$u['created_at'],
            'last_login' => $u['last_login'] ? (int)$u['last_login'] : null,
        ];
    }

    /** Giới hạn số lần đăng nhập sai theo IP */
    public static function throttle(string $key, int $max = 8, int $window = 600): void
    {
        $f = DATA_DIR . '/throttle_' . md5($key . ($_SERVER['REMOTE_ADDR'] ?? '')) . '.json';
        $d = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
        $d = array_values(array_filter($d, fn($t) => $t > now() - $window));
        if (count($d) >= $max) fail('Thử quá nhiều lần, vui lòng đợi vài phút', 429);
        $d[] = now();
        @file_put_contents($f, json_encode($d));
    }
}

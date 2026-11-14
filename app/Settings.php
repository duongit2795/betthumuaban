<?php
defined('APP') or exit;

class Settings
{
    const DEFAULTS = [
        'site_name' => 'Tele Manager',
        'site_url' => '',
        'allow_register' => '1',
        'update_repo' => 'duongit2795/betthumuaban',
        'update_branch' => 'main',
        'update_token' => '',
        'cron_key' => '',
        'menu_admin_only' => '0',
    ];
    private static array $cache = [];

    public static function get(string $k): ?string
    {
        if (array_key_exists($k, self::$cache)) return self::$cache[$k];
        $v = null;
        try {
            $v = DB::val('SELECT v FROM app_settings WHERE k = ?', [$k]);
        } catch (Throwable $e) {}
        if ($v === null) $v = self::DEFAULTS[$k] ?? null;
        return self::$cache[$k] = $v;
    }

    public static function set(string $k, string $v): void
    {
        if (DB::get('SELECT k FROM app_settings WHERE k = ?', [$k])) DB::run('UPDATE app_settings SET v = ? WHERE k = ?', [$v, $k]);
        else DB::insert('app_settings', ['k' => $k, 'v' => $v]);
        self::$cache[$k] = $v;
    }

    public static function publicAll(): array
    {
        $out = [];
        foreach (array_keys(self::DEFAULTS) as $k) $out[$k] = self::get($k);
        $out['update_token'] = $out['update_token'] ? '••••••' : '';
        return $out;
    }
}

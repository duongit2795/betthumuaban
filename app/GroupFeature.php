<?php
defined('APP') or exit;

/**
 * Cài đặt tính năng riêng cho từng nhóm.
 * Mỗi tính năng có 1 cài đặt chung (Settings[KEY], kèm danh sách nhóm áp dụng);
 * nhóm nào có cài đặt riêng (bảng group_features) thì dùng cài đặt riêng, bỏ qua cài đặt chung.
 */
class GroupFeature
{
    /** Các tính năng có thể cài riêng theo nhóm: key => [lớp, tên, biểu tượng, trang web] */
    const FEATURES = [
        'captcha' => ['Captcha', 'Captcha khi vào nhóm', '🤖', '/captcha'],
        'antiraid' => ['AntiRaid', 'Chống raid & duyệt thành viên', '🛡', '/antiraid'],
        'name_guard' => ['NameGuard', 'Cấm tên chứa từ cấm', '🚫', '/nameguard'],
        'require_username' => ['UsernameGuard', 'Yêu cầu đặt Username', '🔖', '/username'],
        'watermark' => ['Watermark', 'Đóng dấu ảnh chống copy', '🖼', '/watermark'],
        'learn' => ['Learn', 'Học tập bắt buộc', '📚', '/learn'],
        'checkin' => ['Checkin', 'Điểm danh nhận điểm', '📅', '/checkin'],
        'unban_request' => ['UnbanRequest', 'Yêu cầu mở band', '🔓', '/unbanreq'],
    ];

    private static array $cache = [];

    public static function get(string $feature, int $groupId): ?array
    {
        $k = $feature . ':' . $groupId;
        if (!array_key_exists($k, self::$cache)) {
            $raw = DB::val('SELECT config FROM group_features WHERE group_id = ? AND feature = ?', [$groupId, $feature]);
            self::$cache[$k] = $raw !== null && $raw !== false ? (json_decode((string)$raw, true) ?: []) : null;
        }
        return self::$cache[$k];
    }

    /** Cài đặt hiệu lực cho 1 nhóm (hoặc cài đặt chung khi $group = null) */
    public static function resolve(string $key, array $defaults, ?array $group): array
    {
        if ($group && !array_key_exists('bot_id', $group)) $group = Repo::group((int)$group['id']) ?: $group;
        $o = $group ? self::get($key, (int)$group['id']) : null;
        // Nhóm của khách thuê: không theo cài đặt chung của chủ web; tính năng ngoài gói luôn tắt
        if ($group && Tenant::groupOwner($group)) {
            $c = $o !== null ? array_merge($defaults, array_intersect_key($o, $defaults)) : $defaults;
            if (array_key_exists('groups', $defaults)) $c['groups'] = [];
            if ($o !== null) $c['_custom'] = true;
            if (!Tenant::allows($group, $key)) $c['enabled'] = false;
            return $c;
        }
        if ($o !== null) {
            $c = array_merge($defaults, array_intersect_key($o, $defaults));
            if (array_key_exists('groups', $defaults)) $c['groups'] = []; // cài đặt riêng: luôn áp dụng cho chính nhóm này
            $c['_custom'] = true;
            return $c;
        }
        $c = json_decode((string)Settings::get($key), true) ?: [];
        return array_merge($defaults, array_intersect_key($c, $defaults));
    }

    /** Lưu cài đặt chung hoặc cài đặt riêng của nhóm */
    public static function store(string $key, array $c, ?array $group, string $by = ''): void
    {
        unset($c['_custom']);
        if (!$group) { Settings::set($key, json_encode($c, JSON_UNESCAPED_UNICODE)); return; }
        unset($c['groups']);
        $gid = (int)$group['id'];
        DB::run('DELETE FROM group_features WHERE group_id = ? AND feature = ?', [$gid, $key]);
        DB::insert('group_features', ['group_id' => $gid, 'feature' => $key, 'config' => json_encode($c, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'updated_by' => $by ?: null]);
        unset(self::$cache[$key . ':' . $gid]);
    }

    /** Bỏ cài đặt riêng → nhóm dùng lại cài đặt chung */
    public static function reset(string $key, int $groupId): void
    {
        DB::run('DELETE FROM group_features WHERE group_id = ? AND feature = ?', [$groupId, $key]);
        unset(self::$cache[$key . ':' . $groupId]);
    }

    /** Tóm tắt trạng thái mọi tính năng ở 1 nhóm */
    public static function summary(array $group): array
    {
        $out = [];
        foreach (self::FEATURES as $key => [$cls, $name, $ico, $page]) {
            $c = $cls::config($group);
            $out[] = ['key' => $key, 'name' => $name, 'icon' => $ico, 'page' => $page, 'custom' => !empty($c['_custom']), 'allowed' => Tenant::allows($group, $key),
                'enabled' => !empty($c['enabled']) && (empty($c['groups']) || in_array((int)$group['id'], array_map('intval', $c['groups']), true))];
        }
        return $out;
    }

    /** Có nhóm nào đang bật tính năng này không (cài đặt chung hoặc riêng) */
    public static function anyEnabled(string $key): bool
    {
        $cls = self::FEATURES[$key][0];
        if ($cls::config()['enabled']) return true;
        foreach (DB::all('SELECT config FROM group_features WHERE feature = ?', [$key]) as $r) {
            if (!empty((json_decode((string)$r['config'], true) ?: [])['enabled'])) return true;
        }
        return false;
    }

    public static function clearCache(): void { self::$cache = []; }
}

<?php
defined('APP') or exit;

/**
 * Phân quyền web:
 *  - Admin web (role = admin): toàn quyền.
 *  - Quản trị viên nhóm: tài khoản web đã liên kết Telegram và là admin của nhóm trên Telegram.
 *    Chỉ quản lý được các nhóm của mình, với các quyền admin web cho phép.
 *  - Thành viên: chỉ xem điểm / xếp hạng của mình.
 */
class Access
{
    /** Quyền có thể cấp cho quản trị viên nhóm: [mặc định, tên, mô tả] */
    const PERMS = [
        'chat' => [true, 'Xem chat nhóm', 'Đọc tin nhắn, xoá / ghim tin trong tab Chat'],
        'moderate' => [true, 'Xử lý thành viên', 'Cảnh cáo, cấm chat, đuổi, cấm, gỡ cấm, ghi chú'],
        'points' => [true, 'Chỉnh điểm', 'Cộng / trừ / đặt điểm thành viên'],
        'settings' => [true, 'Cài đặt nhóm', 'Lời chào, nội quy, bộ lọc, khoá nội dung, cảnh cáo, điểm'],
        'rules' => [true, 'Lệnh riêng của nhóm', 'Tạo / sửa / xoá lệnh tự tạo chỉ áp dụng cho nhóm của họ'],
        'send' => [true, 'Gửi tin vào nhóm', 'Gửi thông báo / trả lời với tư cách bot'],
        'logs' => [true, 'Xem nhật ký', 'Lịch sử xử lý của nhóm'],
        'danger' => [false, 'Thao tác nguy hiểm', 'Đặt lại điểm toàn nhóm, cho bot rời nhóm, xoá dữ liệu nhóm'],
    ];

    private static array $cache = [];

    public static function isWebAdmin(?array $u): bool
    {
        return $u && $u['role'] === 'admin' && !$u['disabled'];
    }

    public static function enabled(): bool
    {
        return Settings::get('group_admin_access') !== '0';
    }

    public static function perms(): array
    {
        $saved = json_decode((string)Settings::get('group_admin_perms'), true) ?: [];
        $out = [];
        foreach (self::PERMS as $k => [$def]) $out[$k] = array_key_exists($k, $saved) ? (bool)$saved[$k] : $def;
        return $out;
    }

    /** Danh sách id nhóm (web) mà người dùng quản lý; null = tất cả (admin web) */
    public static function groupIds(?array $u): ?array
    {
        if (!$u) return [];
        if (self::isWebAdmin($u)) return null;
        if (!self::enabled() || !$u['tg_user_id']) return [];
        $key = 'g' . $u['id'];
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $ids = [];
        $tg = (string)$u['tg_user_id'];
        foreach (DB::all('SELECT id, admins_json FROM tg_groups WHERE active = 1') as $g) {
            $admins = array_map('strval', json_decode((string)$g['admins_json'], true) ?: []);
            if (in_array($tg, $admins, true)) $ids[] = (int)$g['id'];
        }
        // Dự phòng: cờ is_admin trong bảng thành viên (cập nhật từ sự kiện Telegram)
        foreach (DB::all("SELECT m.group_id FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND m.is_admin = 1 AND m.status <> 'banned'", [$u['tg_user_id']]) as $r) {
            $ids[] = (int)$r['group_id'];
        }
        return self::$cache[$key] = array_values(array_unique($ids));
    }

    public static function isManager(?array $u): bool
    {
        return self::isWebAdmin($u) || (bool)self::groupIds($u);
    }

    public static function can(?array $u, string $perm, ?int $groupId = null): bool
    {
        if (self::isWebAdmin($u)) return true;
        if ($perm !== 'view' && empty(self::perms()[$perm])) return false;
        $ids = self::groupIds($u);
        if ($groupId === null) return (bool)$ids;
        return in_array($groupId, $ids, true);
    }

    /** Ném lỗi 403 nếu không có quyền */
    public static function require(?array $u, string $perm, ?int $groupId = null): void
    {
        if (!$u) fail('Vui lòng đăng nhập', 401);
        if (self::can($u, $perm, $groupId)) return;
        $ids = self::groupIds($u) ?? [];
        if (!$ids) fail(self::enabled() ? 'Bạn chưa quản lý nhóm nào. Hãy liên kết Telegram và là quản trị viên của nhóm có bot.' : 'Admin web đang tắt quyền vào web của quản trị viên nhóm', 403);
        if ($groupId !== null && !in_array($groupId, $ids, true)) fail('Bạn không phải quản trị viên của nhóm này', 403);
        $name = self::PERMS[$perm][1] ?? $perm;
        fail("Admin web chưa cấp quyền \"$name\" cho quản trị viên nhóm", 403);
    }

    /** Điều kiện SQL lọc theo nhóm được quản lý */
    public static function sqlIn(?array $ids, string $col): string
    {
        if ($ids === null) return '1=1';
        return $ids ? "$col IN (" . implode(',', array_map('intval', $ids)) . ')' : '1=0';
    }

    /** Gửi cho giao diện */
    public static function summary(?array $u): array
    {
        $web = self::isWebAdmin($u);
        $ids = self::groupIds($u);
        return [
            'web_admin' => $web,
            'manager' => $web || (bool)$ids,
            'groups' => $ids,
            'perms' => $web ? array_map(fn() => true, self::PERMS) : (self::enabled() ? self::perms() : array_map(fn() => false, self::PERMS)),
        ];
    }
}

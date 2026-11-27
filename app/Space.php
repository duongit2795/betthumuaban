<?php
defined('APP') or exit;

/**
 * "Không gian" tiền / điểm: 0 = web của chủ web, N = khách thuê có id N.
 * Mỗi không gian có ví thành viên, tài khoản ngân hàng nhận tiền, ân xá, VIP, đổi điểm, mã quà riêng.
 * Nhóm thuộc không gian của chủ bot quản lý nhóm. Cài đặt lưu ở Settings với hậu tố "@N".
 */
class Space
{
    /** Tính năng tiền của khách thuê (bật theo gói) */
    const FEATURES = [
        'wallet' => 'Ví & nạp tiền cho thành viên',
        'amnesty' => 'Ân xá thành viên',
        'vip' => 'Gói VIP cho thành viên',
        'exchange' => 'Đổi điểm ↔ tiền',
        'giftcode' => 'Mã quà tặng',
    ];

    public static function key(string $k, int $sp): string
    {
        return $sp ? "$k@$sp" : $k;
    }

    public static function get(string $k, int $sp, $def = null)
    {
        return Settings::get(self::key($k, $sp)) ?? $def;
    }

    public static function set(string $k, int $sp, $v): void
    {
        Settings::set(self::key($k, $sp), $v);
    }

    public static function ofGroup(?array $g): int
    {
        if ($g && !array_key_exists('bot_id', $g)) $g = Repo::group((int)$g['id']);
        return Tenant::groupOwner($g) ?? 0;
    }

    public static function ofGroupId(int $gid): int
    {
        return $gid ? self::ofGroup(Repo::group($gid)) : 0;
    }

    /** Không gian hợp lệ (0 hoặc tài khoản khách thuê) */
    public static function valid(int $sp): bool
    {
        return $sp === 0 || (bool)DB::val('SELECT tenant FROM app_users WHERE id = ?', [$sp]);
    }

    /** Khách thuê có được dùng tính năng tiền này không (theo gói còn hạn) */
    public static function allows(int $sp, string $feature): bool
    {
        if ($sp === 0) return true;
        $p = Tenant::activePlan(Tenant::user($sp));
        return $p && in_array($feature, $p['features'], true);
    }

    public static function title(int $sp): string
    {
        if ($sp === 0) return (string)Settings::get('site_name');
        $t = trim((string)self::get('shop_name', $sp, ''));
        if ($t !== '') return $t;
        $u = Tenant::user($sp);
        return $u ? ($u['display_name'] ?: $u['username']) : '?';
    }

    /** Bot dùng để nhắn riêng cho thành viên của không gian này */
    public static function api(int $sp): ?Telegram
    {
        if (Repo::$testApi) return Repo::$testApi;
        $b = DB::get('SELECT token FROM tg_bots WHERE enabled = 1 AND ' . ($sp ? 'owner_id = ' . (int)$sp : 'owner_id IS NULL' . Escrow::notBotSql()) . ' ORDER BY id LIMIT 1');
        return $b ? new Telegram($b['token']) : null;
    }

    /** Điều kiện SQL: nhóm thuộc không gian */
    public static function groupsSql(int $sp, string $alias = 'g'): string
    {
        return $sp ? "$alias.bot_id IN (SELECT id FROM tg_bots WHERE owner_id = " . (int)$sp . ')' : Tenant::ownerGroupsSql($alias);
    }

    /**
     * Các không gian liên quan tới tài khoản: web chính, các khách thuê có nhóm người này đang ở / từng có số dư,
     * và không gian của chính họ (nếu là khách thuê).
     */
    public static function forUser(array $u): array
    {
        $ids = [0 => true];
        if (!empty($u['tg_user_id'])) {
            foreach (DB::all('SELECT DISTINCT b.owner_id FROM tg_members m JOIN tg_groups g ON g.id = m.group_id JOIN tg_bots b ON b.id = g.bot_id WHERE m.tg_user_id = ? AND g.active = 1 AND b.owner_id IS NOT NULL', [$u['tg_user_id']]) as $r) $ids[(int)$r['owner_id']] = true;
        }
        foreach (DB::all('SELECT space_id FROM space_wallets WHERE user_id = ?', [$u['id']]) as $r) $ids[(int)$r['space_id']] = true;
        if (!empty($u['tenant'])) $ids[(int)$u['id']] = true;
        $out = [];
        foreach (array_keys($ids) as $sp) {
            if ($sp && !self::valid($sp)) continue;
            $out[] = ['id' => $sp, 'title' => self::title($sp), 'balance' => Wallet::balance((int)$u['id'], $sp), 'own' => $sp && $sp === (int)$u['id']];
        }
        return $out;
    }
}

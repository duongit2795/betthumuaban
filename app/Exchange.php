<?php
defined('APP') or exit;

/**
 * Đổi điểm ↔ tiền ví.
 *  - Điểm khoá = tổng điểm đã kiếm (tg_members.points). Không giảm khi đổi, dùng cho xếp hạng / tag.
 *  - Điểm mở = còn lại sau khi đổi = điểm khoá − điểm đã đổi (tg_members.points_spent). Chỉ điểm mở đổi được ra tiền.
 * Kiếm thêm điểm thì cả 2 cùng tăng; đổi ra tiền chỉ trừ điểm mở. Cấu hình lưu ở Settings 'exchange'.
 */
class Exchange
{
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],             // rỗng = mọi nhóm
        'rate_points' => 100,       // 100 điểm …
        'rate_money' => 1000,       // … = 1.000đ
        'min_points' => 1000,       // đổi tối thiểu
        'daily_max' => 0,           // tối đa tiền đổi / người / ngày (0 = không giới hạn)
        'buy' => false,             // cho mua điểm bằng tiền
        'buy_money' => 1000,        // 1.000đ …
        'buy_points' => 50,         // … = 50 điểm
    ];

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('exchange'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['enabled', 'buy'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        $num = ['rate_points' => [1, 100000000], 'rate_money' => [1, 100000000], 'min_points' => [1, 100000000], 'daily_max' => [0, 1000000000],
            'buy_money' => [1000, 100000000], 'buy_points' => [1, 100000000]];
        foreach ($num as $k => [$lo, $hi]) if (isset($in[$k])) $c[$k] = max($lo, min($hi, (int)$in[$k]));
        Settings::set('exchange', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    public static function applies(array $c, int $groupId): bool
    {
        return !$c['groups'] || in_array($groupId, $c['groups'], true);
    }

    /** Điểm mở = tổng đã kiếm − đã đổi */
    public static function open(array $m): int
    {
        return max(0, (int)$m['points'] - (int)($m['points_spent'] ?? 0));
    }

    /** Số tiền nhận được khi đổi $points điểm */
    public static function money(array $c, int $points): int
    {
        return intdiv($points, max(1, (int)$c['rate_points'])) * (int)$c['rate_money'];
    }

    private static function member(array $u, int $groupId): array
    {
        if (!$u['tg_user_id']) throw new HttpError('Hãy liên kết Telegram ở trang Tài khoản trước');
        $g = Repo::group($groupId);
        if (!$g || !$g['active']) throw new HttpError('Không tìm thấy nhóm', 404);
        $m = Repo::member($groupId, $u['tg_user_id']);
        if (!$m || !in_array($m['status'], ['member', 'muted'], true)) throw new HttpError('Bạn cần đang ở trong nhóm này');
        return [$g, $m];
    }

    /** Đổi điểm mở → tiền ví (tổng điểm đã kiếm giữ nguyên) */
    public static function toMoney(array $u, int $groupId, int $points): array
    {
        $c = self::config();
        if (!$c['enabled']) throw new HttpError('Tính năng đổi điểm đang tắt');
        if (!self::applies($c, $groupId)) throw new HttpError('Nhóm này không áp dụng đổi điểm');
        [$g, $m] = self::member($u, $groupId);
        $points = intdiv(max(0, $points), $c['rate_points']) * $c['rate_points']; // làm tròn theo tỉ giá
        if ($points < $c['min_points']) throw new HttpError('Đổi tối thiểu ' . number_format($c['min_points'], 0, ',', '.') . ' điểm (làm tròn theo bội số ' . $c['rate_points'] . ')');
        $open = self::open($m);
        if ($points > $open) throw new HttpError('Bạn chỉ có ' . number_format($open, 0, ',', '.') . ' điểm mở');
        $money = self::money($c, $points);
        if ($c['daily_max'] > 0) {
            $today = (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND type = 'exchange' AND created_at >= ?", [$u['id'], strtotime('today')]);
            if ($today + $money > $c['daily_max']) throw new HttpError('Vượt giới hạn đổi mỗi ngày (' . Wallet::money($c['daily_max']) . '). Hôm nay bạn đã đổi ' . Wallet::money($today) . '.');
        }
        // Trừ điểm mở nguyên tử: chỉ khi vẫn còn đủ
        if (!DB::run('UPDATE tg_members SET points_spent = points_spent + ? WHERE id = ? AND points >= points_spent + ?', [$points, $m['id'], $points])) {
            throw new HttpError('Điểm vừa thay đổi, hãy thử lại');
        }
        $bal = Wallet::credit((int)$u['id'], $money, 'exchange', 'Đổi ' . number_format($points, 0, ',', '.') . " điểm — {$g['title']}", null, ['group_id' => $groupId, 'points' => $points]);
        $after = DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
        Repo::log($groupId, Repo::name($m) . ' (web)', 'exchange', $m, 'Đổi ' . number_format($points, 0, ',', '.') . ' điểm mở → +' . Wallet::money($money),
            ['source' => 'web', 'points' => $points, 'money' => $money, 'open_before' => $open, 'open_after' => self::open($after)]);
        return ['points' => $points, 'money' => $money, 'balance' => $bal, 'total' => (int)$after['points'], 'open' => self::open($after)];
    }

    /** Mua điểm bằng tiền ví (cộng vào tổng điểm, nên cũng tăng điểm mở) */
    public static function buyPoints(array $u, int $groupId, int $money): array
    {
        $c = self::config();
        if (!$c['enabled'] || !$c['buy']) throw new HttpError('Tính năng mua điểm đang tắt');
        if (!self::applies($c, $groupId)) throw new HttpError('Nhóm này không áp dụng');
        [$g, $m] = self::member($u, $groupId);
        $money = intdiv(max(0, $money), $c['buy_money']) * $c['buy_money'];
        if ($money < $c['buy_money']) throw new HttpError('Mua tối thiểu ' . Wallet::money($c['buy_money']));
        $points = intdiv($money, $c['buy_money']) * $c['buy_points'];
        $bal = Wallet::debit((int)$u['id'], $money, 'buy_points', 'Mua ' . number_format($points, 0, ',', '.') . " điểm — {$g['title']}", ['group_id' => $groupId, 'points' => $points]);
        DB::run('UPDATE tg_members SET points = points + ? WHERE id = ?', [$points, $m['id']]);
        $after = DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
        Repo::log($groupId, Repo::name($m) . ' (web)', 'points', $m, '+' . number_format($points, 0, ',', '.') . ' điểm (mua ' . Wallet::money($money) . ')',
            ['source' => 'web', 'amount' => $points, 'before' => (int)$m['points'], 'after' => (int)$after['points']]);
        MemberTag::auto($g, $m['tg_user_id']);
        return ['points' => $points, 'money' => $money, 'balance' => $bal, 'total' => (int)$after['points'], 'open' => self::open($after)];
    }
}

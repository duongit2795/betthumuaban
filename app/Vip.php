<?php
defined('APP') or exit;

/**
 * Gói VIP theo thời hạn (theo tài khoản Telegram, áp dụng mọi nhóm được chọn).
 * Quyền lợi (bật / tắt từng cái): tag VIP, miễn điều kiện nhắn tin & học tập, được gửi link, nhân điểm.
 */
class Vip
{
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],              // rỗng = mọi nhóm
        'plans' => [['days' => 30, 'price' => 100000], ['days' => 90, 'price' => 250000]],
        'tag' => 'VIP',              // tag gắn khi là VIP (để trống = không gắn)
        'perk_gates' => true,        // miễn điều kiện nhắn tin
        'perk_learn' => true,        // miễn học tập bắt buộc
        'perk_links' => true,        // được gửi link (bỏ qua chặn link)
        'multiplier' => 2,           // nhân điểm tin nhắn & điểm danh (1 = không)
        'notify' => true,
        'notify_text' => '👑 {mention} vừa trở thành <b>VIP</b> đến {until}!',
    ];

    public static function config(int $sp = 0): array
    {
        $c = json_decode((string)Space::get('vip', $sp, ''), true) ?: [];
        $c = array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
        $c['enabled_raw'] = $c['enabled'];
        if (!Space::allows($sp, 'vip') || ($sp && !Wallet::config($sp)['enabled'])) $c['enabled'] = false;
        return $c;
    }

    public static function save(array $in, int $sp = 0): array
    {
        $c = self::config($sp);
        $c['enabled'] = $c['enabled_raw'];
        unset($c['enabled_raw']);
        foreach (['enabled', 'perk_gates', 'perk_learn', 'perk_links', 'notify'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['multiplier'])) $c['multiplier'] = max(1, min(10, (int)$in['multiplier']));
        if (isset($in['tag'])) $c['tag'] = MemberTag::clean((string)$in['tag']);
        if (isset($in['notify_text'])) { $t = trim(mb_substr((string)$in['notify_text'], 0, 1000)); $c['notify_text'] = $t !== '' ? $t : self::DEFAULTS['notify_text']; }
        if (isset($in['plans'])) {
            $plans = [];
            foreach (array_slice((array)$in['plans'], 0, 8) as $p) {
                $days = (int)($p['days'] ?? 0);
                $price = (int)($p['price'] ?? 0);
                if (!$days && !$price) continue;
                if ($days < 1 || $days > 3650) throw new HttpError('Số ngày gói VIP: 1–3650');
                if ($price < 1000) throw new HttpError('Giá gói VIP tối thiểu 1.000đ');
                $plans[] = ['days' => $days, 'price' => $price];
            }
            usort($plans, fn($a, $b) => $a['days'] <=> $b['days']);
            $c['plans'] = $plans;
        }
        Space::set('vip', $sp, json_encode($c, JSON_UNESCAPED_UNICODE));
        return self::config($sp);
    }

    public static function until($tgUserId, int $sp = 0): ?int
    {
        if (!$tgUserId) return null;
        $u = $sp ? DB::val('SELECT until FROM space_vip WHERE space_id = ? AND tg_user_id = ?', [$sp, $tgUserId]) : DB::val('SELECT until FROM vip_members WHERE tg_user_id = ?', [$tgUserId]);
        return $u ? (int)$u : null;
    }

    /** Đang là VIP và áp dụng cho nhóm này? (VIP theo không gian của nhóm) */
    public static function active($tgUserId, ?int $groupId = null, ?array $c = null): bool
    {
        $sp = $groupId ? Space::ofGroupId($groupId) : 0;
        $c ??= self::config($sp);
        if (!$c['enabled'] || !$tgUserId) return false;
        if ($groupId && $c['groups'] && !in_array($groupId, $c['groups'], true)) return false;
        return (self::until($tgUserId, $sp) ?? 0) > now();
    }

    public static function perk(string $perk, $tgUserId, int $groupId): bool
    {
        $c = self::config(Space::ofGroupId($groupId));
        return !empty($c[$perk]) && self::active($tgUserId, $groupId, $c);
    }

    /** Hệ số nhân điểm cho người này ở nhóm này */
    public static function multiplier($tgUserId, int $groupId): int
    {
        $c = self::config(Space::ofGroupId($groupId));
        return $c['multiplier'] > 1 && self::active($tgUserId, $groupId, $c) ? (int)$c['multiplier'] : 1;
    }

    /** Cộng thêm ngày VIP (nối tiếp hạn cũ nếu còn). Trả về hạn mới. */
    public static function extend($tgUserId, int $days, string $why, int $sp = 0): int
    {
        $cur = self::until($tgUserId, $sp);
        $base = $cur && $cur > now() ? $cur : now();
        $until = $base + $days * 86400;
        if ($sp) {
            if ($cur !== null) DB::run('UPDATE space_vip SET until = ?, notified = 0, updated_at = ? WHERE space_id = ? AND tg_user_id = ?', [$until, now(), $sp, $tgUserId]);
            else DB::insert('space_vip', ['space_id' => $sp, 'tg_user_id' => $tgUserId, 'until' => $until, 'created_at' => now(), 'updated_at' => now()]);
        } else {
            if ($cur !== null) DB::run('UPDATE vip_members SET until = ?, notified = 0, updated_at = ? WHERE tg_user_id = ?', [$until, now(), $tgUserId]);
            else DB::insert('vip_members', ['tg_user_id' => $tgUserId, 'until' => $until, 'created_at' => now(), 'updated_at' => now()]);
        }
        self::applyTags($tgUserId, true, $sp);
        $c = self::config($sp);
        if ($c['notify'] && $c['enabled']) {
            foreach (self::groupsOf($tgUserId, $c, $sp) as [$g, $m]) {
                if (!($api = Repo::apiForGroup($g))) continue;
                $api->trySend($g['chat_id'], strtr($c['notify_text'], ['{mention}' => Repo::mention($m), '{name}' => tg_esc(Repo::name($m)), '{until}' => date('d/m/Y', $until), '{days}' => (string)$days]));
            }
        }
        Repo::log(null, 'VIP', 'vip', null, "VIP +$days ngày cho $tgUserId ($why), đến " . date('d/m/Y', $until) . ($sp ? ' · ' . Space::title($sp) : ''));
        return $until;
    }

    /** Các nhóm (thuộc không gian) người này đang ở mà VIP áp dụng → [[group, member], …] */
    private static function groupsOf($tgUserId, array $c, int $sp = 0): array
    {
        $out = [];
        foreach (DB::all("SELECT m.*, g.id AS gid FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND m.status IN ('member','muted') AND " . Space::groupsSql($sp, 'g'), [$tgUserId]) as $m) {
            if ($c['groups'] && !in_array((int)$m['gid'], $c['groups'], true)) continue;
            $out[] = [Repo::group((int)$m['gid']), $m];
        }
        return $out;
    }

    /** Gắn / gỡ tag VIP ở các nhóm (không đụng tag admin đặt tay hay tag đã mua) */
    public static function applyTags($tgUserId, bool $on, int $sp = 0): void
    {
        $c = self::config($sp);
        if ($c['tag'] === '') return;
        foreach (self::groupsOf($tgUserId, $c, $sp) as [$g, $m]) {
            if ($m['is_admin']) continue;
            try {
                if ($on) {
                    if (in_array($m['tag_source'] ?? '', ['manual', 'shop'], true) && (string)$m['tag'] !== '') continue;
                    if ((string)$m['tag'] === $c['tag']) { DB::run("UPDATE tg_members SET tag_source = 'vip' WHERE id = ?", [$m['id']]); continue; }
                    MemberTag::set($g, $tgUserId, $c['tag'], 'VIP', 'vip', false, 'bot');
                } elseif (($m['tag_source'] ?? '') === 'vip') {
                    $mt = MemberTag::config();
                    $next = !$sp && $mt['auto'] && (!$mt['auto_groups'] || in_array((int)$g['id'], $mt['auto_groups'], true)) ? MemberTag::levelTag($mt, (int)$m['points']) : '';
                    MemberTag::set($g, $tgUserId, $next, 'VIP hết hạn', $next !== '' ? 'auto' : 'manual', false, 'bot');
                }
            } catch (Throwable $e) {
                // thiếu quyền tag: bỏ qua, quyền lợi khác vẫn có hiệu lực
            }
        }
    }

    /** Mua gói VIP bằng số dư ví của không gian */
    public static function buy(array $u, int $planIndex, int $sp = 0): array
    {
        $c = self::config($sp);
        if (!$c['enabled']) throw new HttpError('Gói VIP đang tắt');
        if (!$u['tg_user_id']) throw new HttpError('Hãy liên kết Telegram ở trang Tài khoản trước khi mua VIP');
        $plan = $c['plans'][$planIndex] ?? null;
        if (!$plan) throw new HttpError('Gói không tồn tại');
        $bal = Wallet::debit((int)$u['id'], (int)$plan['price'], 'vip', "Mua VIP {$plan['days']} ngày", ['days' => $plan['days']], $sp);
        $until = self::extend($u['tg_user_id'], (int)$plan['days'], 'mua ' . Wallet::money((int)$plan['price']), $sp);
        return ['until' => $until, 'balance' => $bal, 'days' => (int)$plan['days'], 'price' => (int)$plan['price']];
    }

    /** VIP hết hạn: gỡ tag, nhắn riêng báo (cron) */
    public static function expire(): int
    {
        $n = 0;
        $rows = array_merge(
            array_map(fn($r) => $r + ['space_id' => 0], DB::all('SELECT * FROM vip_members WHERE until < ? AND notified = 0 LIMIT 30', [now()])),
            DB::all('SELECT * FROM space_vip WHERE until < ? AND notified = 0 LIMIT 30', [now()])
        );
        foreach ($rows as $v) {
            $sp = (int)$v['space_id'];
            if ($sp) DB::run('UPDATE space_vip SET notified = 1 WHERE space_id = ? AND tg_user_id = ?', [$sp, $v['tg_user_id']]);
            else DB::run('UPDATE vip_members SET notified = 1 WHERE tg_user_id = ?', [$v['tg_user_id']]);
            self::applyTags($v['tg_user_id'], false, $sp);
            if ($api = Space::api($sp)) $api->trySend($v['tg_user_id'], '👑 Gói <b>VIP</b> của bạn' . ($sp ? ' (' . tg_esc(Space::title($sp)) . ')' : '') . ' đã hết hạn. Gia hạn trên web để tiếp tục nhận quyền lợi nhé!');
            $n++;
        }
        return $n;
    }
}

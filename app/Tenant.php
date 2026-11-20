<?php
defined('APP') or exit;

/**
 * Cho thuê: khách thuê (tài khoản web) mua gói bằng số dư ví, thêm bot riêng và tự quản lý nhóm của bot đó.
 *  - Bot của chủ web: tg_bots.owner_id = NULL. Bot của khách: owner_id = id tài khoản khách.
 *  - Nhóm thuộc về chủ của bot đang quản lý nhóm.
 *  - Gói giới hạn số bot, số nhóm, tính năng và thời hạn. Hết hạn → bot của khách ngừng xử lý nhóm.
 * Cấu hình: Settings 'tenant' (bật cho thuê, ...).
 */
class Tenant
{
    /** Tính năng có thể bật / tắt theo gói */
    const FEATURES = [
        'rules' => 'Lệnh tự tạo',
        'chat' => 'Xem & quản lý chat nhóm',
        'send' => 'Gửi tin vào nhóm từ web',
        'captcha' => 'Captcha khi vào nhóm',
        'antiraid' => 'Chống raid & duyệt thành viên',
        'name_guard' => 'Cấm tên chứa từ cấm',
        'require_username' => 'Yêu cầu đặt Username',
        'watermark' => 'Đóng dấu ảnh',
        'checkin' => 'Điểm danh nhận điểm',
    ];
    const DEFAULTS = [
        'enabled' => false,        // bật cho thuê (hiện trang Gói thuê cho mọi tài khoản)
        'remind_days' => 3,        // nhắc gia hạn qua Telegram trước N ngày
        'intro' => "Tự quản lý nhóm Telegram bằng bot của riêng bạn: chống spam, captcha, cấm tên giả mạo, lệnh tự động, điểm thành viên…",
    ];

    private static array $botOwner = [];
    private static array $users = [];
    private static array $plans = [];

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('tenant'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function saveConfig(array $in): array
    {
        $c = self::config();
        if (array_key_exists('enabled', $in)) $c['enabled'] = !empty($in['enabled']);
        if (isset($in['remind_days'])) $c['remind_days'] = max(0, min(30, (int)$in['remind_days']));
        if (isset($in['intro'])) $c['intro'] = trim(mb_substr((string)$in['intro'], 0, 1000));
        Settings::set('tenant', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    // ================= Gói =================
    public static function planRow(array $p): array
    {
        $f = json_decode((string)$p['features'], true);
        return ['id' => (int)$p['id'], 'name' => $p['name'], 'description' => (string)$p['description'], 'price' => (int)$p['price'], 'days' => (int)$p['days'],
            'max_groups' => (int)$p['max_groups'], 'max_bots' => (int)$p['max_bots'], 'features' => is_array($f) ? array_values(array_intersect($f, array_keys(self::FEATURES))) : array_keys(self::FEATURES),
            'enabled' => (int)$p['enabled'], 'sort' => (int)$p['sort']];
    }

    public static function plans(bool $onlyEnabled = true): array
    {
        return array_map([self::class, 'planRow'], DB::all('SELECT * FROM plans' . ($onlyEnabled ? ' WHERE enabled = 1' : '') . ' ORDER BY sort, price, id'));
    }

    public static function plan(?int $id): ?array
    {
        if (!$id) return null;
        if (!array_key_exists($id, self::$plans)) {
            $p = DB::get('SELECT * FROM plans WHERE id = ?', [$id]);
            self::$plans[$id] = $p ? self::planRow($p) : null;
        }
        return self::$plans[$id];
    }

    public static function savePlan(array $in): array
    {
        $name = trim(mb_substr((string)($in['name'] ?? ''), 0, 60));
        if ($name === '') throw new HttpError('Nhập tên gói');
        $feats = array_values(array_intersect((array)($in['features'] ?? []), array_keys(self::FEATURES)));
        $row = ['name' => $name, 'description' => trim(mb_substr((string)($in['description'] ?? ''), 0, 500)),
            'price' => max(0, min(1000000000, (int)($in['price'] ?? 0))), 'days' => max(1, min(3650, (int)($in['days'] ?? 30))),
            'max_groups' => max(1, min(1000, (int)($in['max_groups'] ?? 1))), 'max_bots' => max(1, min(100, (int)($in['max_bots'] ?? 1))),
            'features' => json_encode($feats), 'enabled' => !array_key_exists('enabled', $in) || !empty($in['enabled']) ? 1 : 0, 'sort' => (int)($in['sort'] ?? 0)];
        $id = (int)($in['id'] ?? 0);
        if ($id) DB::update('plans', $row, 'id = ?', [$id]);
        else { $row['created_at'] = now(); $id = DB::insert('plans', $row); }
        unset(self::$plans[$id]);
        return self::plan($id);
    }

    // ================= Khách thuê =================
    public static function user(int $id): ?array
    {
        if (!array_key_exists($id, self::$users)) self::$users[$id] = DB::get('SELECT * FROM app_users WHERE id = ?', [$id]);
        return self::$users[$id];
    }

    /** Gói đang hiệu lực của khách (null = chưa thuê / hết hạn / bị khoá) */
    public static function activePlan(?array $u): ?array
    {
        if (!$u || !empty($u['disabled']) || (int)($u['plan_until'] ?? 0) <= now()) return null;
        return self::plan((int)$u['plan_id']);
    }

    public static function botOwner(?int $botId): ?int
    {
        if (!$botId) return null;
        if (!array_key_exists($botId, self::$botOwner)) {
            $o = DB::val('SELECT owner_id FROM tg_bots WHERE id = ?', [$botId]);
            self::$botOwner[$botId] = $o ? (int)$o : null;
        }
        return self::$botOwner[$botId];
    }

    /** Chủ của nhóm (id khách thuê) hoặc null = nhóm của chủ web */
    public static function groupOwner(?array $group): ?int
    {
        return $group ? self::botOwner(isset($group['bot_id']) ? (int)$group['bot_id'] : null) : null;
    }

    /** Nhóm có được dùng tính năng này không (nhóm chủ web: luôn được) */
    public static function allows(?array $group, string $feature): bool
    {
        $owner = self::groupOwner($group);
        if (!$owner) return true;
        $p = self::activePlan(self::user($owner));
        return $p && in_array($feature, $p['features'], true);
    }

    /**
     * Bot của khách có được xử lý nhóm này không. Trả về null nếu được, ngược lại lý do.
     * Nhóm vượt số lượng của gói (tính theo thứ tự thêm vào) bị bỏ qua.
     */
    public static function blockReason(int $botId, ?int $groupId): ?string
    {
        $owner = self::botOwner($botId);
        if (!$owner) return null;
        $u = self::user($owner);
        if (!$u || !empty($u['disabled'])) return 'Tài khoản chủ bot đã bị khoá';
        $p = self::activePlan($u);
        if (!$p) return 'Gói thuê của chủ bot đã hết hạn';
        if ($groupId) {
            $ids = array_map('intval', array_column(DB::all('SELECT g.id FROM tg_groups g JOIN tg_bots b ON b.id = g.bot_id WHERE b.owner_id = ? AND g.active = 1 ORDER BY g.id LIMIT ' . (int)$p['max_groups'], [$owner]), 'id'));
            if (!in_array($groupId, $ids, true)) return 'Gói thuê chỉ cho phép ' . $p['max_groups'] . ' nhóm';
        }
        return null;
    }

    public static function usage(int $userId): array
    {
        return [
            'bots' => (int)DB::val('SELECT COUNT(*) FROM tg_bots WHERE owner_id = ?', [$userId]),
            'groups' => (int)DB::val('SELECT COUNT(*) FROM tg_groups g JOIN tg_bots b ON b.id = g.bot_id WHERE b.owner_id = ? AND g.active = 1', [$userId]),
        ];
    }

    /** Id các nhóm của khách (theo bot của khách) */
    public static function groupIds(int $userId): array
    {
        return array_map('intval', array_column(DB::all('SELECT g.id FROM tg_groups g JOIN tg_bots b ON b.id = g.bot_id WHERE b.owner_id = ? AND g.active = 1', [$userId]), 'id'));
    }

    /** Điều kiện SQL: nhóm của chủ web (để các tính năng tiền / điểm của chủ web không áp dụng sang nhóm khách thuê) */
    public static function ownerGroupsSql(string $alias = 'g'): string
    {
        return "($alias.bot_id IS NULL OR $alias.bot_id IN (SELECT id FROM tg_bots WHERE owner_id IS NULL))";
    }

    /** Thông tin gói cho giao diện */
    public static function summary(array $u): array
    {
        $p = self::plan((int)$u['plan_id']);
        return ['is_tenant' => (bool)$u['tenant'], 'plan' => $p, 'until' => $u['plan_until'] ? (int)$u['plan_until'] : null,
            'active' => (bool)self::activePlan($u), 'usage' => self::usage((int)$u['id'])];
    }

    // ================= Mua / gia hạn =================
    /** Khách tự mua / gia hạn bằng số dư ví. Cùng gói → cộng dồn ngày; đổi gói → tính từ bây giờ. */
    public static function buy(array $u, int $planId): array
    {
        $p = self::plan($planId);
        if (!$p || !$p['enabled']) throw new HttpError('Gói không tồn tại hoặc đã ngừng bán');
        if (!self::config()['enabled']) throw new HttpError('Hiện chưa mở cho thuê');
        if ($p['price'] === 0 && DB::val("SELECT COUNT(*) FROM tenant_orders WHERE user_id = ? AND plan_id = ? AND kind IN ('buy', 'renew')", [$u['id'], $p['id']])) {
            throw new HttpError('Gói miễn phí / dùng thử chỉ được dùng 1 lần');
        }
        $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$u['id']]);
        $same = (int)$u['plan_id'] === $p['id'] && (int)$u['plan_until'] > now();
        if (!$same && self::activePlan($u)) {
            $usage = self::usage((int)$u['id']);
            if ($usage['bots'] > $p['max_bots']) throw new HttpError("Gói {$p['name']} chỉ cho {$p['max_bots']} bot, bạn đang có {$usage['bots']} bot. Xoá bớt bot trước khi đổi gói.");
        }
        $note = ($same ? 'Gia hạn gói ' : 'Thuê gói ') . $p['name'] . ' · ' . $p['days'] . ' ngày';
        if ($p['price'] > 0) Wallet::debit((int)$u['id'], $p['price'], 'plan', $note, ['plan_id' => $p['id']]);
        $until = self::apply((int)$u['id'], $p, $p['days'], $same);
        DB::insert('tenant_orders', ['user_id' => $u['id'], 'plan_id' => $p['id'], 'plan_name' => $p['name'], 'days' => $p['days'], 'amount' => $p['price'],
            'kind' => $same ? 'renew' : 'buy', 'until_after' => $until, 'by_user' => $u['username'], 'note' => null, 'created_at' => now()]);
        Repo::log(null, $u['username'], 'plan', null, $note . ($p['price'] ? ' · ' . Wallet::money($p['price']) : ''));
        return ['until' => $until, 'balance' => Wallet::balance((int)$u['id'])];
    }

    /** Admin gia hạn / tặng / đổi gói (không trừ tiền). days âm = bớt ngày. */
    public static function grant(int $userId, int $planId, int $days, string $by, string $note = ''): int
    {
        $p = self::plan($planId) ?: throw new HttpError('Không tìm thấy gói');
        $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$userId]) ?: throw new HttpError('Không tìm thấy tài khoản');
        $same = (int)$u['plan_id'] === $p['id'] && (int)$u['plan_until'] > now();
        $until = self::apply($userId, $p, $days, $same);
        DB::insert('tenant_orders', ['user_id' => $userId, 'plan_id' => $p['id'], 'plan_name' => $p['name'], 'days' => $days, 'amount' => 0,
            'kind' => 'admin', 'until_after' => $until, 'by_user' => $by, 'note' => mb_substr($note, 0, 190) ?: null, 'created_at' => now()]);
        Repo::log(null, $by, 'plan', null, "Admin cấp gói {$p['name']} " . ($days >= 0 ? '+' : '') . "$days ngày cho {$u['username']}");
        return $until;
    }

    private static function apply(int $userId, array $p, int $days, bool $same): int
    {
        $u = DB::get('SELECT plan_until FROM app_users WHERE id = ?', [$userId]);
        $from = $same ? max(now(), (int)$u['plan_until']) : now();
        $until = max(now(), $from + $days * 86400);
        DB::run('UPDATE app_users SET plan_id = ?, plan_until = ?, tenant = 1 WHERE id = ?', [$p['id'], $until, $userId]);
        unset(self::$users[$userId]);
        return $until;
    }

    // ================= Nhắc gia hạn (cron) =================
    public static function remind(): int
    {
        $c = self::config();
        if (!$c['enabled'] || $c['remind_days'] <= 0) return 0;
        $bot = DB::get('SELECT * FROM tg_bots WHERE owner_id IS NULL AND enabled = 1 ORDER BY id LIMIT 1');
        if (!$bot) return 0;
        $api = new Telegram($bot['token']);
        $n = 0;
        $rows = DB::all('SELECT * FROM app_users WHERE tenant = 1 AND disabled = 0 AND tg_user_id IS NOT NULL AND plan_until IS NOT NULL AND plan_until BETWEEN ? AND ?',
            [now() - 86400, now() + $c['remind_days'] * 86400]);
        foreach ($rows as $u) {
            $expired = (int)$u['plan_until'] <= now();
            $key = 'tn_remind_' . $u['id'];
            $tag = ($expired ? 'x' : 'r') . date('Ymd', (int)$u['plan_until']);
            if (Settings::get($key) === $tag) continue; // mỗi mốc nhắc 1 lần
            Settings::set($key, $tag);
            $p = self::plan((int)$u['plan_id']);
            $left = max(0, (int)ceil(((int)$u['plan_until'] - now()) / 86400));
            $text = $expired
                ? "⛔ Gói thuê <b>" . tg_esc($p['name'] ?? '') . "</b> của bạn đã <b>hết hạn</b>. Bot của bạn đã ngừng xử lý nhóm.\nGia hạn trên web để bot hoạt động lại."
                : "⏰ Gói thuê <b>" . tg_esc($p['name'] ?? '') . "</b> của bạn còn <b>$left ngày</b> (hết hạn " . date('d/m/Y H:i', (int)$u['plan_until']) . ").\nGia hạn trên web để bot không bị gián đoạn.";
            $url = site_url();
            $api->trySend($u['tg_user_id'], $text, str_starts_with($url, 'https://') ? ['reply_markup' => ['inline_keyboard' => [[['text' => '🔁 Gia hạn ngay', 'url' => $url . '/#/plan']]]]] : []);
            $n++;
        }
        return $n;
    }

    public static function clearCache(): void { self::$botOwner = []; self::$users = []; self::$plans = []; }
}

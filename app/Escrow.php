<?php
defined('APP') or exit;

/**
 * Giao dịch trung gian (GDTG) với "kho phòng":
 *  - Chủ web tạo sẵn các nhóm (phòng), thêm bot làm quản trị + các trung gian (ID đã cài).
 *  - Mỗi giao dịch được cấp 1 phòng trống: bot đổi tên phòng, tạo link xin vào (chỉ duyệt người mua / người bán),
 *    ghim thẻ giao dịch có nút bấm theo từng bước, nhận tiền tự động qua API ngân hàng (nội dung GDTG + mã).
 *  - Trung gian (chỉ các ID đã cài) quyết định giải ngân / hoàn tiền / huỷ. Hạn giờ chỉ nhắc, không tự chuyển tiền.
 *  - Xong giao dịch: lưu toàn bộ tin nhắn làm bằng chứng, mời 2 bên ra, trả phòng về trạng thái trống.
 */
class Escrow
{
    const DEFAULTS = [
        'enabled' => false,
        'middlemen' => [],          // [['id' => '123', 'name' => 'Admin A'], …] — chỉ các ID này được làm trung gian
        'fee_percent' => 1.0,
        'fee_min' => 10000,
        'fee_max' => 0,             // 0 = không giới hạn
        'fee_payer' => 'buyer',     // buyer | seller | half
        'min_amount' => 10000,
        'max_amount' => 50000000,
        'pay_minutes' => 60,        // người mua phải chuyển tiền trong N phút (quá hạn → huỷ, chưa có tiền)
        'deliver_hours' => 24,      // nhắc người bán giao hàng
        'confirm_hours' => 24,      // nhắc người mua xác nhận
        'payout' => 'manual',       // manual: trung gian chuyển khoản tay · wallet: cộng vào ví web của người bán
        'mm_approve' => true,
        'bot_pay_info' => false,    // bot tự gửi QR / số tài khoản sau khi duyệt (tắt: trung gian tự gửi)
        'tags' => true,             // đổi tag người mua / người bán trong phòng       // trung gian duyệt điều khoản (thông tin hàng + yêu cầu) rồi mới cho chuyển tiền
        'prefix' => 'GDTG',
        'room_title' => 'Phòng GDTG',
        'bot_id' => 0,              // bot riêng phụ trách GDTG (0 = dùng bot quản lý nhóm)
        'rules' => "• Chỉ giao dịch trong phòng này, không nhắn riêng ngoài.\n• Người mua chỉ chuyển tiền theo thông tin nhận tiền được gửi trong phòng này, đúng nội dung.\n• Trung gian không bao giờ nhắn riêng xin tiền.\n• Có vấn đề hãy bấm Khiếu nại, trung gian sẽ xử lý.",
    ];
    const STATUS = [
        'queue' => 'Chờ phòng trống', 'agree' => 'Chốt điều khoản (thông tin hàng, yêu cầu, đồng ý)', 'pay' => 'Chờ người mua chuyển tiền', 'paid' => 'Đã nhận tiền · chờ giao hàng',
        'delivered' => 'Đã giao · chờ người mua xác nhận', 'confirmed' => 'Người mua đã nhận · chờ giải ngân', 'disputed' => 'Đang khiếu nại',
        'completed' => 'Hoàn tất (đã giải ngân)', 'refunded' => 'Đã hoàn tiền người mua', 'cancelled' => 'Đã huỷ',
    ];
    const OPEN = ['queue', 'agree', 'pay', 'paid', 'delivered', 'confirmed', 'disputed'];
    const FEE_PAYER = ['buyer' => 'Người mua trả', 'seller' => 'Người bán trả', 'half' => 'Mỗi bên một nửa'];

    // ================= Cài đặt =================
    public static function config(): array
    {
        $c = json_decode((string)Settings::get('escrow'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        if (array_key_exists('enabled', $in)) $c['enabled'] = !empty($in['enabled']);
        if (array_key_exists('mm_approve', $in)) $c['mm_approve'] = !empty($in['mm_approve']);
        if (array_key_exists('bot_pay_info', $in)) $c['bot_pay_info'] = !empty($in['bot_pay_info']);
        if (array_key_exists('tags', $in)) $c['tags'] = !empty($in['tags']);
        if (isset($in['middlemen'])) {
            $list = [];
            foreach (is_array($in['middlemen']) ? $in['middlemen'] : preg_split('/\r?\n/', (string)$in['middlemen']) as $row) {
                if (is_array($row)) { $id = (string)($row['id'] ?? ''); $name = (string)($row['name'] ?? ''); }
                else { [$id, $name] = array_pad(array_map('trim', preg_split('/[\s|,]+/', trim($row), 2)), 2, ''); }
                if (!preg_match('/^\d{1,15}$/', $id)) { if (trim((string)$id) !== '') throw new HttpError("ID trung gian không hợp lệ: $id"); continue; }
                $list[$id] = ['id' => $id, 'name' => mb_substr(trim($name), 0, 60)];
            }
            $c['middlemen'] = array_values($list);
        }
        if (isset($in['fee_percent'])) $c['fee_percent'] = max(0, min(50, round((float)str_replace(',', '.', (string)$in['fee_percent']), 2)));
        foreach (['fee_min' => [0, 100000000], 'fee_max' => [0, 1000000000], 'min_amount' => [1000, 1000000000], 'max_amount' => [1000, 10000000000],
            'pay_minutes' => [5, 1440], 'deliver_hours' => [1, 720], 'confirm_hours' => [1, 720]] as $k => [$lo, $hi]) {
            if (isset($in[$k])) $c[$k] = max($lo, min($hi, (int)$in[$k]));
        }
        if (isset($in['fee_payer']) && isset(self::FEE_PAYER[$in['fee_payer']])) $c['fee_payer'] = $in['fee_payer'];
        if (isset($in['payout']) && in_array($in['payout'], ['manual', 'wallet'], true)) $c['payout'] = $in['payout'];
        if (isset($in['prefix'])) {
            $p = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)$in['prefix']));
            if (strlen($p) < 2 || strlen($p) > 10) throw new HttpError('Tiền tố nội dung chuyển khoản: 2–10 chữ cái');
            if ($p === Wallet::config()['prefix']) throw new HttpError('Tiền tố GDTG phải khác tiền tố nạp tiền ví');
            $c['prefix'] = $p;
        }
        if (isset($in['bot_id'])) {
            $bid = (int)$in['bot_id'];
            if ($bid && !DB::val('SELECT id FROM tg_bots WHERE id = ? AND owner_id IS NULL', [$bid])) throw new HttpError('Bot không hợp lệ');
            $c['bot_id'] = $bid;
        }
        if (isset($in['room_title'])) $c['room_title'] = trim(mb_substr((string)$in['room_title'], 0, 40)) ?: self::DEFAULTS['room_title'];
        if (isset($in['rules'])) $c['rules'] = trim(mb_substr((string)$in['rules'], 0, 2000));
        if ($c['max_amount'] < $c['min_amount']) throw new HttpError('Số tiền tối đa phải lớn hơn tối thiểu');
        if ($c['enabled'] && !$c['middlemen']) throw new HttpError('Nhập ít nhất 1 ID trung gian');
        Settings::set('escrow', json_encode($c, JSON_UNESCAPED_UNICODE));
        self::clearCache();
        return $c;
    }

    // ================= Bot phụ trách GDTG =================
    /** Id bot riêng phụ trách GDTG (0 = không có, dùng bot của nhóm) */
    private static ?int $botIdCache = null;

    public static function botId(): int
    {
        if (self::$botIdCache === null) {
            $bid = (int)(self::config()['bot_id'] ?? 0);
            self::$botIdCache = $bid && DB::val('SELECT id FROM tg_bots WHERE id = ? AND enabled = 1', [$bid]) ? $bid : 0;
        }
        return self::$botIdCache;
    }

    /** Điều kiện SQL bỏ qua bot GDTG riêng (bot GDTG chỉ làm GDTG) */
    public static function notBotSql(string $col = 'id'): string
    {
        return self::botId() ? " AND $col <> " . self::botId() : '';
    }

    public static function isEscrowBot(int $botId): bool
    {
        return $botId && $botId === self::botId();
    }

    /** Bot dùng để nhắn GDTG (bot riêng nếu có, không thì bot đầu tiên của chủ web) */
    public static function botApi(): ?Telegram
    {
        if (Repo::$testApi) return Repo::$testApi;
        $b = self::botId() ? DB::get('SELECT token FROM tg_bots WHERE id = ?', [self::botId()]) : DB::get('SELECT token FROM tg_bots WHERE enabled = 1 AND owner_id IS NULL ORDER BY id LIMIT 1');
        return $b ? new Telegram($b['token']) : null;
    }

    public static function botUsername(): ?string
    {
        return self::botId() ? (DB::val('SELECT username FROM tg_bots WHERE id = ?', [self::botId()]) ?: null)
            : (DB::val('SELECT username FROM tg_bots WHERE enabled = 1 AND owner_id IS NULL ORDER BY id LIMIT 1') ?: null);
    }

    public static function isMiddleman($tgId, ?array $c = null): bool
    {
        $c ??= self::config();
        return in_array((string)$tgId, array_column($c['middlemen'], 'id'), true);
    }

    /** Phí, số người mua phải chuyển, số người bán nhận */
    public static function money(int $amount, ?array $c = null): array
    {
        $c ??= self::config();
        $fee = max((int)$c['fee_min'], (int)round($amount * $c['fee_percent'] / 100));
        if ($c['fee_max'] > 0) $fee = min($fee, (int)$c['fee_max']);
        $buyerPart = ['buyer' => $fee, 'seller' => 0, 'half' => (int)ceil($fee / 2)][$c['fee_payer']];
        return ['fee' => $fee, 'pay_total' => $amount + $buyerPart, 'seller_gets' => $amount - ($fee - $buyerPart)];
    }

    /** "500k", "1tr", "1.5tr", "500.000", "500000đ" → số */
    public static function parseAmount(string $s): int
    {
        $s = mb_strtolower(trim($s));
        if (!preg_match('/^(\d+(?:[.,]\d+)*)\s*(k|nghìn|ngàn|tr|triệu|m|đ|d|vnd)?$/u', $s, $m)) return 0;
        $mul = ['k' => 1000, 'nghìn' => 1000, 'ngàn' => 1000, 'tr' => 1000000, 'triệu' => 1000000, 'm' => 1000000][$m[2] ?? ''] ?? 1;
        $num = $m[1];
        if ($mul > 1) $num = str_replace(',', '.', $num); // 1,5tr = 1.5tr
        else $num = str_replace(['.', ','], '', $num);     // 500.000 = 500000
        return (int)round((float)$num * $mul);
    }

    public static function code(array $d): string
    {
        return self::config()['prefix'] . $d['id'];
    }

    private static function vnd(int $v): string { return Wallet::money($v); }

    // ================= Phòng =================
    private static array $roomCache = [];

    public static function roomOfGroup(int $groupId): ?array
    {
        if (!array_key_exists($groupId, self::$roomCache)) self::$roomCache[$groupId] = DB::get("SELECT * FROM escrow_rooms WHERE group_id = ? AND status <> 'off'", [$groupId]);
        return self::$roomCache[$groupId];
    }

    public static function clearCache(): void
    {
        self::$roomCache = [];
        self::$botIdCache = null;
    }

    public static function isRoom(int $groupId): bool
    {
        return (bool)self::roomOfGroup($groupId);
    }

    private static function api(array $room): ?Telegram
    {
        if (Repo::$testApi) return Repo::$testApi;
        if (self::botId()) return self::botApi(); // phòng do bot GDTG riêng quản lý
        $g = Repo::group((int)$room['group_id']);
        return $g ? Repo::apiForGroup($g) : null;
    }

    /** Kiểm tra phòng đủ điều kiện: siêu nhóm, ẩn lịch sử với người mới, bot đủ quyền, trung gian có trong phòng */
    /** Nhóm thường được nâng lên siêu nhóm → Telegram đổi ID: cập nhật nhóm + phòng */
    public static function migrate($oldChatId, $newChatId): void
    {
        if (!$newChatId || (string)$oldChatId === (string)$newChatId) return;
        if (DB::val('SELECT id FROM tg_groups WHERE chat_id = ?', [$newChatId])) { // đã có bản ghi nhóm mới (bot nhận tin từ nhóm mới trước) → bỏ bản cũ
            $newId = (int)DB::val('SELECT id FROM tg_groups WHERE chat_id = ?', [$newChatId]);
            $oldId = (int)DB::val('SELECT id FROM tg_groups WHERE chat_id = ?', [$oldChatId]);
            if ($oldId) { DB::run('UPDATE escrow_rooms SET group_id = ? WHERE group_id = ?', [$newId, $oldId]); DB::run('UPDATE tg_groups SET active = 0 WHERE id = ?', [$oldId]); }
        } else {
            DB::run("UPDATE tg_groups SET chat_id = ?, type = 'supergroup' WHERE chat_id = ?", [$newChatId, $oldChatId]);
        }
        DB::run('UPDATE escrow_rooms SET chat_id = ? WHERE chat_id = ?', [$newChatId, $oldChatId]);
        self::clearCache();
    }

    public static function check(array $room): array
    {
        $issues = [];
        $api = self::api($room);
        if (!$api) return ['ok' => false, 'issues' => ['Nhóm chưa có bot hoạt động']];
        // ID nhóm có thể đã đổi (nâng lên siêu nhóm): lấy theo bảng nhóm, hoặc theo Telegram báo
        $gChat = DB::val('SELECT chat_id FROM tg_groups WHERE id = ?', [$room['group_id']]);
        if ($gChat && (string)$gChat !== (string)$room['chat_id']) { DB::run('UPDATE escrow_rooms SET chat_id = ? WHERE id = ?', [$gChat, $room['id']]); $room['chat_id'] = $gChat; }
        $chat = null;
        try { $chat = $api->call('getChat', ['chat_id' => $room['chat_id']]); }
        catch (TelegramException $e) {
            if ($e->migrateTo) {
                self::migrate($room['chat_id'], $e->migrateTo);
                $room['chat_id'] = $e->migrateTo;
                $chat = $api->tryCall('getChat', ['chat_id' => $room['chat_id']]);
            }
        }
        if (!is_array($chat)) $issues[] = 'Bot không truy cập được nhóm (bot đã bị xoá khỏi nhóm?)';
        else {
            if (($chat['type'] ?? '') !== 'supergroup') $issues[] = 'Nhóm đang là nhóm thường, cần nâng lên siêu nhóm: mở nhóm → bấm tên nhóm → Sửa → “Lịch sử chat cho thành viên mới” → chọn Ẩn → Lưu (Telegram tự nâng cấp), rồi bấm Kiểm tra lại';
            if (!empty($chat['has_visible_history'])) $issues[] = 'Chưa ẩn lịch sử chat: Cài đặt nhóm → Lịch sử chat cho thành viên mới → Ẩn';
        }
        $botId = explode(':', $api->token)[0];
        $me = $api->tryCall('getChatMember', ['chat_id' => $room['chat_id'], 'user_id' => $botId]);
        if (!is_array($me) || ($me['status'] ?? '') !== 'administrator') $issues[] = 'Bot chưa là quản trị viên';
        else {
            foreach (['can_invite_users' => 'Mời thành viên qua link', 'can_restrict_members' => 'Cấm thành viên', 'can_pin_messages' => 'Ghim tin', 'can_delete_messages' => 'Xoá tin', 'can_change_info' => 'Đổi thông tin nhóm']
                + (self::config()['tags'] ? ['can_manage_tags' => 'Đổi tag thành viên'] : []) as $k => $l) {
                if (empty($me[$k])) $issues[] = "Bot thiếu quyền: $l";
            }
        }
        foreach (self::config()['middlemen'] as $mm) {
            $m = $api->tryCall('getChatMember', ['chat_id' => $room['chat_id'], 'user_id' => $mm['id']]);
            if (!is_array($m) || !in_array($m['status'] ?? '', ['creator', 'administrator', 'member', 'restricted'], true)) $issues[] = 'Trung gian ' . ($mm['name'] ?: $mm['id']) . ' chưa có trong phòng';
        }
        DB::run('UPDATE escrow_rooms SET check_note = ?, checked_at = ? WHERE id = ?', [$issues ? implode("\n", $issues) : null, now(), $room['id']]);
        return ['ok' => !$issues, 'issues' => $issues];
    }

    public static function addRoom(int $groupId): array
    {
        $g = Repo::group($groupId) ?: throw new HttpError('Không tìm thấy nhóm', 404);
        if (DB::val('SELECT id FROM escrow_rooms WHERE group_id = ?', [$groupId])) throw new HttpError('Nhóm này đã là phòng GDTG');
        $no = (int)DB::val('SELECT COALESCE(MAX(no), 0) FROM escrow_rooms') + 1;
        $id = DB::insert('escrow_rooms', ['group_id' => $groupId, 'chat_id' => $g['chat_id'], 'no' => $no, 'status' => 'free', 'created_at' => now()]);
        self::clearCache();
        $room = DB::get('SELECT * FROM escrow_rooms WHERE id = ?', [$id]);
        self::setTitle($room, null);
        return $room + ['check' => self::check($room)];
    }

    private static function setTitle(array $room, ?array $d): void
    {
        $api = self::api($room);
        if (!$api) return;
        $base = self::config()['room_title'] . ' ' . str_pad((string)$room['no'], 2, '0', STR_PAD_LEFT);
        $title = $d ? mb_substr('GDTG #' . $d['id'] . ' · ' . self::vnd((int)$d['amount']) . ' · ' . $d['description'], 0, 120) : $base . ' · Trống';
        $api->tryCall('setChatTitle', ['chat_id' => $room['chat_id'], 'title' => $title]);
    }

    // ================= Tạo giao dịch =================
    /**
     * $role: 'buy' (người tạo là người mua) | 'sell'. $other: @username hoặc ID của đối tác.
     * @return array [deal, invite_link|null]
     */
    public static function create(array $creator, string $role, string $other, int $amount, string $desc, string $source = 'bot'): array
    {
        $c = self::config();
        if (!$c['enabled']) throw new HttpError('Giao dịch trung gian đang tắt');
        if (!in_array($role, ['buy', 'sell'], true)) throw new HttpError('Chọn bạn là người mua hay người bán');
        if ($amount < $c['min_amount'] || $amount > $c['max_amount']) throw new HttpError('Số tiền từ ' . self::vnd($c['min_amount']) . ' đến ' . self::vnd($c['max_amount']));
        $desc = trim(preg_replace('/\s+/u', ' ', $desc));
        if (mb_strlen($desc) < 3) throw new HttpError('Nhập nội dung giao dịch (mua bán gì)');
        $desc = mb_substr($desc, 0, 300);
        $other = trim($other);
        $oId = null; $oName = null; $oUser = null;
        if (preg_match('/^\d{4,15}$/', $other)) {
            $oId = $other;
            $m = DB::get('SELECT * FROM tg_members WHERE tg_user_id = ? ORDER BY last_active DESC', [$oId]);
            if ($m) { $oName = Repo::name($m); $oUser = $m['username']; }
        } elseif (preg_match('/^@?([A-Za-z0-9_]{4,32})$/', $other, $mm)) {
            $oUser = $mm[1];
            $m = DB::get('SELECT * FROM tg_members WHERE LOWER(username) = LOWER(?) ORDER BY last_active DESC', [$oUser]);
            if ($m) { $oId = (string)$m['tg_user_id']; $oName = Repo::name($m); }
        } else throw new HttpError('Nhập @username hoặc ID Telegram của đối tác');
        if ($oId && (string)$oId === (string)$creator['id']) throw new HttpError('Không thể giao dịch với chính mình');
        if (self::isMiddleman($creator['id'], $c) || ($oId && self::isMiddleman($oId, $c))) throw new HttpError('Trung gian không tham gia giao dịch với vai trò mua / bán');
        if ((int)DB::val('SELECT COUNT(*) FROM escrow_deals WHERE creator_tg = ? AND status IN (\'' . implode("','", self::OPEN) . '\')', [$creator['id']]) >= 3) {
            throw new HttpError('Bạn đang có 3 giao dịch chưa xong, hoàn tất bớt trước khi tạo mới');
        }
        $cName = trim(($creator['first_name'] ?? '') . ' ' . ($creator['last_name'] ?? '')) ?: (string)$creator['id'];
        $me = ['tg' => $creator['id'], 'name' => $cName, 'username' => $creator['username'] ?? null];
        $ot = ['tg' => $oId, 'name' => $oName, 'username' => $oUser];
        [$buyer, $seller] = $role === 'buy' ? [$me, $ot] : [$ot, $me];
        $mny = self::money($amount, $c);
        $id = DB::insert('escrow_deals', [
            'status' => 'queue', 'creator_tg' => $creator['id'], 'source' => $source,
            'buyer_tg' => $buyer['tg'], 'buyer_name' => $buyer['name'] ? mb_substr($buyer['name'], 0, 190) : null, 'buyer_username' => $buyer['username'],
            'seller_tg' => $seller['tg'], 'seller_name' => $seller['name'] ? mb_substr($seller['name'], 0, 190) : null, 'seller_username' => $seller['username'],
            'amount' => $amount, 'fee' => $mny['fee'], 'fee_payer' => $c['fee_payer'], 'pay_total' => $mny['pay_total'], 'seller_gets' => $mny['seller_gets'],
            'description' => $desc, 'created_at' => now(),
        ]);
        Repo::log(null, $cName, 'escrow', null, "Tạo GDTG #$id · " . self::vnd($amount) . " · $desc", ['source' => $source]);
        self::assignRoom($id);
        $d = self::deal($id);
        self::notifyMiddlemen($d, '🆕 Giao dịch mới #' . $id . ($d['status'] === 'queue' ? ' (đang chờ phòng trống)' : ''));
        // Mời đối tác qua tin nhắn riêng (nếu đối tác đã Start bot)
        $link = self::invite($d);
        if ($link && $ot['tg']) self::dm($d, $ot['tg'], '🤝 Bạn được mời vào <b>giao dịch trung gian #' . $id . '</b> với ' . tg_esc($cName) . "\n💰 " . self::vnd($amount) . ' · ' . tg_esc($desc) . "\n👉 Vào phòng giao dịch: $link");
        return [$d, $link];
    }

    public static function deal(int $id): ?array
    {
        return DB::get('SELECT * FROM escrow_deals WHERE id = ?', [$id]);
    }

    /** Link vào phòng của giao dịch */
    public static function invite(array $d): ?string
    {
        if (!$d['room_id']) return null;
        return DB::val('SELECT invite_link FROM escrow_rooms WHERE id = ?', [$d['room_id']]) ?: null;
    }

    /** Cấp phòng trống cho giao dịch đang chờ */
    public static function assignRoom(int $dealId): bool
    {
        $d = self::deal($dealId);
        if (!$d || $d['status'] !== 'queue') return false;
        foreach (DB::all("SELECT * FROM escrow_rooms WHERE status = 'free' ORDER BY no") as $room) {
            if (!DB::run("UPDATE escrow_rooms SET status = 'busy', deal_id = ? WHERE id = ? AND status = 'free'", [$dealId, $room['id']])) continue;
            $api = self::api($room);
            $link = $api ? $api->tryCall('createChatInviteLink', ['chat_id' => $room['chat_id'], 'name' => 'GDTG #' . $dealId, 'creates_join_request' => true]) : null;
            if (empty($link['invite_link'])) { // phòng lỗi (bot mất quyền?) → bỏ qua phòng này
                DB::run("UPDATE escrow_rooms SET status = 'off', deal_id = NULL, check_note = ? WHERE id = ?", ['Không tạo được link mời (bot thiếu quyền?). Kiểm tra lại phòng.', $room['id']]);
                continue;
            }
            DB::run('UPDATE escrow_rooms SET invite_link = ? WHERE id = ?', [$link['invite_link'], $room['id']]);
            DB::run("UPDATE escrow_deals SET room_id = ?, status = 'agree', room_at = ? WHERE id = ?", [$room['id'], now(), $dealId]);
            $d = self::deal($dealId);
            self::setTitle($room, $d);
            // Thẻ giao dịch + nội quy chỉ gửi khi CẢ 2 BÊN đã vào phòng (phòng ẩn lịch sử với người mới)
            $api->tryCall('sendMessage', ['chat_id' => $room['chat_id'], 'text' => "🆕 <b>GDTG #$dealId</b> · " . self::vnd((int)$d['amount']) . ' · ' . tg_esc((string)$d['description']) . "\n⏳ Đang chờ người mua và người bán vào phòng.", 'parse_mode' => 'HTML']);
            return true;
        }
        return false;
    }

    // ================= Thẻ giao dịch =================
    private static function who(array $d, string $role): string
    {
        $u = $d[$role . '_username'];
        $n = $d[$role . '_name'];
        if ($d[$role . '_tg']) return '<a href="tg://user?id=' . (int)$d[$role . '_tg'] . '">' . tg_esc($u ? '@' . $u : ($n ?: (string)$d[$role . '_tg'])) . '</a>';
        return $u ? '@' . tg_esc($u) : '<i>chưa rõ</i>';
    }

    public static function cardText(array $d): string
    {
        $c = self::config();
        $mm = implode(', ', array_map(function ($m) {
            $label = $m['name'];
            if ($label === '') { // chưa đặt tên → lấy tên / @username bot đã biết
                $r = DB::get('SELECT first_name, last_name, username FROM tg_members WHERE tg_user_id = ? ORDER BY last_active DESC', [$m['id']]);
                $label = $r ? ($r['username'] ? '@' . $r['username'] : trim($r['first_name'] . ' ' . $r['last_name'])) : '';
            }
            return '<a href="tg://user?id=' . (int)$m['id'] . '">' . tg_esc($label ?: 'Trung gian') . '</a>';
        }, $c['middlemen']));
        $t = "🤝 <b>GIAO DỊCH TRUNG GIAN #{$d['id']}</b>\n"
            . "🛒 Người mua: " . self::who($d, 'buyer') . ($d['buyer_ok'] ? ' ✅' : '') . "\n"
            . "🏪 Người bán: " . self::who($d, 'seller') . ($d['seller_ok'] ? ' ✅' : '') . "\n"
            . "🛡 Trung gian: $mm\n"
            . "📦 Nội dung: " . tg_esc((string)$d['description']) . "\n"
            . "💰 Số tiền: <b>" . self::vnd((int)$d['amount']) . "</b> · Phí: " . self::vnd((int)$d['fee']) . ' (' . mb_strtolower(self::FEE_PAYER[$d['fee_payer']] ?? '') . ")\n"
            . "💸 Người mua chuyển: <b>" . self::vnd((int)$d['pay_total']) . "</b> · Người bán nhận: <b>" . self::vnd((int)$d['seller_gets']) . "</b>\n"
            . "📍 Trạng thái: <b>" . (self::STATUS[$d['status']] ?? $d['status']) . '</b>';
        if ($d['paid_amount'] > 0 && $d['status'] === 'pay') $t .= "\n🏦 Ngân hàng báo đã nhận " . self::vnd((int)$d['paid_amount']) . ((int)$d['paid_amount'] < (int)$d['pay_total'] ? ', còn thiếu ' . self::vnd((int)$d['pay_total'] - (int)$d['paid_amount']) : ' · chờ trung gian xác nhận');
        $t .= "\n\n📝 <b>Thông tin hàng (người bán):</b> " . ($d['seller_info'] ? "\n" . tg_esc((string)$d['seller_info']) : '<i>chưa gửi</i>')
            . "\n📋 <b>Yêu cầu giao dịch (người mua):</b> " . ($d['buyer_req'] ? "\n" . tg_esc((string)$d['buyer_req']) : '<i>chưa gửi</i>');
        if ($d['status'] === 'agree') {
            $ck = fn($ok, $l) => ($ok ? '✅' : '⬜') . ' ' . $l;
            $t .= "\n\n🧾 <b>Chốt điều khoản trước khi chuyển tiền</b>\n" . implode("\n", array_filter([
                $ck((bool)$d['seller_info'], 'Người bán gửi thông tin hàng: <code>/thongtin nội dung</code>'),
                $ck((bool)$d['buyer_req'], 'Người mua gửi yêu cầu: <code>/yeucau nội dung</code>'),
                $ck((bool)$d['buyer_ok'], 'Người mua bấm Tôi đồng ý'),
                $ck((bool)$d['seller_ok'], 'Người bán bấm Tôi đồng ý'),
                $c['mm_approve'] ? $ck((bool)$d['mm_ok'], 'Trung gian duyệt điều khoản') : null,
            ])) . "\n<i>Sửa thông tin / yêu cầu thì 2 bên phải đồng ý lại.</i>";
        }
        if ($d['status'] === 'pay') $t .= "\n\n👉 Trung gian xác nhận thủ công khi nhận được tiền. Người mua chuyển đúng <b>" . self::vnd((int)$d['pay_total'] - (int)$d['paid_amount']) . "</b>, nội dung <code>" . self::code($d) . "</code> (theo thông tin trung gian gửi trong phòng). Hạn: " . date('H:i d/m', (int)$d['agreed_at'] + $c['pay_minutes'] * 60) . '.';
        if ($d['status'] === 'paid') $t .= "\n\n👉 Tiền đang được trung gian giữ. Người bán giao hàng rồi bấm <b>Đã giao hàng</b>.";
        if ($d['status'] === 'delivered') $t .= "\n\n👉 Người mua kiểm tra hàng rồi bấm <b>Đã nhận hàng OK</b>, có vấn đề bấm <b>Khiếu nại</b>.";
        if (in_array($d['status'], ['confirmed', 'disputed'], true)) $t .= "\n\n👉 Trung gian đang xử lý." . ($d['status'] === 'disputed' && $d['dispute_reason'] ? "\n⚠️ Khiếu nại: " . tg_esc((string)$d['dispute_reason']) : '');
        if ($d['seller_bank']) $t .= "\n🏦 STK người bán: " . tg_esc((string)$d['seller_bank']);
        if ($d['buyer_bank']) $t .= "\n🏦 STK người mua (hoàn tiền): " . tg_esc((string)$d['buyer_bank']);
        if ($d['middleman']) $t .= "\n👮 Xử lý: " . tg_esc((string)$d['middleman']);
        $t .= "\n\nℹ️ Gửi STK nhận tiền: <code>/stk Ngân hàng Số TK Tên chủ TK</code>";
        return $t;
    }

    public static function buttons(array $d): array
    {
        $b = fn($text, $act) => ['text' => $text, 'callback_data' => "esc:{$d['id']}:$act"];
        $mmRow = [$b('💰 Đã chuyển cho người bán (giải ngân)', 'release')];
        $rows = match ($d['status']) {
            'agree' => array_values(array_filter([
                (!$d['seller_info'] || !$d['buyer_req']) ? [$b('📝 Cách gửi thông tin / yêu cầu', 'howinfo')] : null,
                !($d['buyer_ok'] && $d['seller_ok']) ? [$b('✅ Tôi đồng ý điều khoản', 'agree')] : null,
                self::config()['mm_approve'] && $d['buyer_ok'] && $d['seller_ok'] ? [$b('🛡 TG duyệt điều khoản & bắt đầu', 'mmok')] : null,
                [$b('❌ Huỷ giao dịch', 'cancel')],
            ])),
            'pay' => [[$b('💸 Tôi đã chuyển tiền', 'claim')], [$b('🛡 TG xác nhận đã nhận tiền', 'mmpaid')], [$b('❌ Huỷ giao dịch', 'cancel')]],
            'paid' => [[$b('📦 Đã giao hàng', 'delivered')], [$b('⚠️ Khiếu nại', 'dispute')], $mmRow, [$b('↩️ Đã hoàn tiền người mua', 'refund')]],
            'delivered' => [[$b('👍 Đã nhận hàng OK', 'received')], [$b('⚠️ Khiếu nại', 'dispute')], $mmRow, [$b('↩️ Đã hoàn tiền người mua', 'refund')]],
            'confirmed', 'disputed' => [$mmRow, [$b('↩️ Đã hoàn tiền người mua', 'refund')]],
            default => [],
        };
        if (self::config()['payout'] === 'wallet') $rows = array_map(fn($r) => array_map(fn($x) => $x['callback_data'] === "esc:{$d['id']}:release" ? ['text' => '💰 Giải ngân vào ví người bán', 'callback_data' => $x['callback_data']] : $x, $r), $rows);
        return ['inline_keyboard' => $rows];
    }

    /** Cập nhật thẻ đã ghim */
    public static function refresh(int $id): void
    {
        $d = self::deal($id);
        if (!$d || !$d['room_id']) return;
        $room = DB::get('SELECT * FROM escrow_rooms WHERE id = ?', [$d['room_id']]);
        $api = $room ? self::api($room) : null;
        if (!$api) return;
        if ($d['card_msg_id']) {
            $api->tryCall('editMessageText', ['chat_id' => $room['chat_id'], 'message_id' => $d['card_msg_id'], 'text' => self::cardText($d), 'parse_mode' => 'HTML', 'reply_markup' => ['inline_keyboard' => []]]);
        }
    }

    private static function room(array $d): ?array
    {
        return $d['room_id'] ? DB::get('SELECT * FROM escrow_rooms WHERE id = ?', [$d['room_id']]) : null;
    }

    /** Gửi tin vào phòng của giao dịch */
    public static function say(array $d, string $text, array $extra = []): void
    {
        $room = self::room($d);
        if ($room && ($api = self::api($room))) $api->tryCall('sendMessage', ['chat_id' => $room['chat_id'], 'text' => $text, 'parse_mode' => 'HTML'] + $extra);
    }

    private static function dm(array $d, $tgId, string $text, array $extra = []): void
    {
        $room = self::room($d);
        $api = $room ? self::api($room) : self::botApi();
        if ($api && $tgId) $api->tryCall('sendMessage', ['chat_id' => $tgId, 'text' => $text, 'parse_mode' => 'HTML', 'link_preview_options' => ['is_disabled' => true]] + $extra);
    }

    /** Link mở phòng (cho người đã ở trong phòng, VD trung gian): nhóm công khai → t.me/username; nhóm riêng → t.me/c/… tới thẻ giao dịch */
    public static function roomLink(array $d): ?string
    {
        $room = self::room($d);
        if (!$room) return null;
        $un = DB::val('SELECT username FROM tg_groups WHERE id = ?', [$room['group_id']]);
        if ($un) return 'https://t.me/' . $un . ($d['card_msg_id'] ? '/' . $d['card_msg_id'] : '');
        $cid = (string)$room['chat_id'];
        if (!str_starts_with($cid, '-100')) return null;
        return 'https://t.me/c/' . substr($cid, 4) . '/' . ($d['card_msg_id'] ?: 1);
    }

    public static function notifyMiddlemen(array $d, string $title): void
    {
        $d = self::deal((int)$d['id']) ?: $d;
        $room = self::room($d);
        $text = "🛡 <b>$title</b>\n"
            . "📦 " . tg_esc((string)$d['description']) . "\n"
            . "💰 " . self::vnd((int)$d['amount']) . ' · phí ' . self::vnd((int)$d['fee']) . ' · người mua chuyển <b>' . self::vnd((int)$d['pay_total']) . '</b> · người bán nhận ' . self::vnd((int)$d['seller_gets']) . "\n"
            . "🛒 Người mua: " . self::who($d, 'buyer') . ($d['buyer_ok'] ? ' ✅' : '') . "\n"
            . "🏪 Người bán: " . self::who($d, 'seller') . ($d['seller_ok'] ? ' ✅' : '') . "\n"
            . ($d['seller_info'] ? "📝 Thông tin hàng: " . tg_esc(mb_substr((string)$d['seller_info'], 0, 300)) . "\n" : '')
            . ($d['buyer_req'] ? "📋 Yêu cầu: " . tg_esc(mb_substr((string)$d['buyer_req'], 0, 300)) . "\n" : '')
            . ($d['paid_amount'] ? "🏦 Ngân hàng báo: " . self::vnd((int)$d['paid_amount']) . "\n" : '')
            . ($d['seller_bank'] ? "🏦 STK người bán: " . tg_esc((string)$d['seller_bank']) . "\n" : '')
            . "📍 " . (self::STATUS[$d['status']] ?? $d['status']) . ($room ? ' · Phòng ' . $room['no'] : ' · chưa có phòng')
            . "\n🔖 Mã: <code>#{$d['id']}</code> · Nội dung CK: <code>" . self::code($d) . '</code>';
        $link = self::roomLink($d);
        $extra = $link ? ['reply_markup' => ['inline_keyboard' => [[['text' => '🚪 Mở phòng GDTG #' . $d['id'], 'url' => $link]]]]] : [];
        foreach (self::config()['middlemen'] as $mm) self::dm($d, $mm['id'], $text, $extra);
    }

    /** Đổi tag thành viên trong phòng (Bot API setChatMemberTag; tối đa 16 ký tự). '' = gỡ tag. */
    private static function tag(array $d, $tgId, string $tag): void
    {
        if (!self::config()['tags'] || !$tgId) return;
        $room = self::room($d);
        $api = $room ? self::api($room) : null;
        if ($api) $api->tryCall('setChatMemberTag', ['chat_id' => $room['chat_id'], 'user_id' => $tgId, 'tag' => $tag]);
    }

    // ================= Thanh toán =================
    private static function payInfo(array $d): void
    {
        $w = Wallet::config(0);
        $left = (int)$d['pay_total'] - (int)$d['paid_amount'];
        if (!$w['account_no']) { self::say($d, '💳 Trung gian sẽ gửi thông tin chuyển khoản. Người mua chờ trung gian xác nhận nhé.'); return; }
        $text = "💳 <b>CHUYỂN TIỀN GDTG #{$d['id']}</b>\n🏦 " . tg_esc(Wallet::BANKS[$w['bank']] ?? $w['bank']) . "\n🔢 STK: <code>{$w['account_no']}</code>" . ($w['account_name'] ? "\n👤 " . tg_esc($w['account_name']) : '')
            . "\n💰 Số tiền: <b>" . self::vnd($left) . "</b>\n📝 Nội dung: <code>" . self::code($d) . "</code>\n\n⚠️ Chuyển <b>đúng nội dung</b>, chuyển xong bấm <b>💸 Tôi đã chuyển tiền</b>. Trung gian kiểm tra tài khoản và xác nhận. Chỉ chuyển vào tài khoản trong tin này.";
        $qr = 'https://img.vietqr.io/image/' . rawurlencode($w['bank']) . '-' . rawurlencode($w['account_no']) . '-compact2.png?' . http_build_query(array_filter(['amount' => $left, 'addInfo' => self::code($d), 'accountName' => $w['account_name'] ?: null]));
        $room = self::room($d);
        $api = $room ? self::api($room) : null;
        if (!$api) return;
        $r = $api->tryCall('sendPhoto', ['chat_id' => $room['chat_id'], 'photo' => $qr, 'caption' => $text, 'parse_mode' => 'HTML']);
        if (!$r) self::say($d, $text);
    }

    /**
     * Giao dịch ngân hàng mới (gọi từ Wallet::sync trước khi khớp nạp ví). Trả về true nếu là tiền GDTG.
     */
    public static function matchPayment(array $t): bool
    {
        $c = self::config();
        $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$t['description']));
        if (!preg_match('/' . preg_quote($c['prefix'], '/') . '(\d{1,9})(?!\d)/', $norm, $m)) return false;
        $d = self::deal((int)$m[1]);
        if (!$d) return false;
        if (!in_array($d['status'], ['agree', 'pay'], true)) { // tiền về khi giao dịch không còn chờ thanh toán → báo trung gian xử lý tay
            self::notifyMiddlemen($d, '⚠️ Nhận thêm ' . self::vnd((int)$t['amount']) . ' cho #' . $d['id'] . ' khi giao dịch đang "' . (self::STATUS[$d['status']] ?? '') . '" — kiểm tra & hoàn tay nếu cần');
            return true;
        }
        // Tiền do trung gian xác nhận thủ công: bot chỉ ghi nhận số tiền ngân hàng báo về và báo trung gian đối chiếu
        $paid = (int)$d['paid_amount'] + (int)$t['amount'];
        DB::run('UPDATE escrow_deals SET paid_amount = ?, bank_txid = ? WHERE id = ?', [$paid, mb_substr((string)$t['id'], 0, 190), $d['id']]);
        $enough = $paid >= (int)$d['pay_total'];
        self::refresh((int)$d['id']);
        self::notifyMiddlemen(self::deal((int)$d['id']), '🏦 Ngân hàng báo nhận ' . self::vnd((int)$t['amount']) . ' cho #' . $d['id'] . ' (tổng ' . self::vnd($paid) . '/' . self::vnd((int)$d['pay_total']) . ')'
            . ($enough ? ' — kiểm tra & bấm "TG xác nhận đã nhận tiền" trong phòng' : ' — CHƯA ĐỦ'));
        self::say($d, '🏦 Hệ thống ghi nhận ' . self::vnd((int)$t['amount']) . ' cho GDTG #' . $d['id'] . '.' . ($enough ? ' Chờ <b>trung gian xác nhận</b> đã nhận tiền.' : ' Còn thiếu <b>' . self::vnd((int)$d['pay_total'] - $paid) . '</b>, chuyển thêm với cùng nội dung <code>' . self::code($d) . '</code>.'));
        return true;
    }

    private static function markPaid(int $id, string $by): void
    {
        if (!DB::run("UPDATE escrow_deals SET status = 'paid', paid_at = ?, reminded = 0 WHERE id = ? AND status IN ('agree','pay')", [now(), $id])) return;
        $d = self::deal($id);
        self::refresh($id);
        self::say($d, "✅ <b>Trung gian đã nhận đủ tiền</b> (" . self::vnd((int)max($d['paid_amount'], $d['pay_total'])) . ") · $by\n🏪 Người bán " . self::who($d, 'seller') . ' giao hàng cho người mua rồi bấm <b>📦 Đã giao hàng</b>.');
        self::notifyMiddlemen($d, '💰 Đã nhận tiền #' . $id);
        Repo::log(null, $by, 'escrow', null, "GDTG #$id: đã nhận tiền " . self::vnd((int)$d['paid_amount'] ?: (int)$d['pay_total']), ['source' => 'bot']);
    }

    // ================= Nút bấm =================
    /** Vai trò của người bấm: buyer / seller / mm / null. Đối tác chỉ biết @username → nhận ID khi khớp username */
    private static function roleOf(array $d, array $from): ?string
    {
        $id = (string)$from['id'];
        if (self::isMiddleman($id)) return 'mm';
        foreach (['buyer', 'seller'] as $r) {
            if ($d[$r . '_tg'] && (string)$d[$r . '_tg'] === $id) return $r;
            if (!$d[$r . '_tg'] && $d[$r . '_username'] && !empty($from['username']) && strcasecmp($d[$r . '_username'], $from['username']) === 0) {
                DB::run("UPDATE escrow_deals SET {$r}_tg = ?, {$r}_name = ? WHERE id = ?", [$id, mb_substr(trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')), 0, 190), $d['id']]);
                return $r;
            }
        }
        return null;
    }

    public static function onCallback(Telegram $api, array $cb): bool
    {
        if (!preg_match('/^esc:(\d+):(\w+)$/', (string)($cb['data'] ?? ''), $m)) return false;
        $ans = fn(string $t, bool $alert = false) => $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $t, 'show_alert' => $alert]);
        try { $ans(self::act((int)$m[1], $m[2], $cb['from']), $m[2] === 'howinfo'); }
        catch (HttpError $e) { $ans($e->getMessage(), true); }
        return true;
    }

    /** Thực hiện 1 hành động (nút trong phòng hoặc trên web). Trả về lời nhắn ngắn. */
    public static function act(int $id, string $action, array $from, string $extra = '', bool $asMiddleman = false): string
    {
        $r = self::actRun($id, $action, $from, $extra, $asMiddleman);
        if ($action !== 'howinfo') self::step($id); // tin "việc cần làm" mới ở cuối phòng
        return $r;
    }

    private static function actRun(int $id, string $action, array $from, string $extra = '', bool $asMiddleman = false): string
    {
        $d = self::deal($id) ?: throw new HttpError('Không tìm thấy giao dịch');
        $role = $asMiddleman ? 'mm' : self::roleOf($d, $from);
        if (!$role) throw new HttpError('Bạn không thuộc giao dịch này');
        $d = self::deal($id);
        $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: (string)$from['id'];
        $need = function (array $roles, array $states) use ($role, $d) {
            if (!in_array($d['status'], $states, true)) throw new HttpError('Không làm được ở bước này (' . (self::STATUS[$d['status']] ?? $d['status']) . ')');
            if (!in_array($role, $roles, true)) throw new HttpError(['buyer' => 'Chỉ người mua', 'seller' => 'Chỉ người bán', 'mm' => 'Chỉ trung gian'][$roles[0]] . ' bấm được nút này');
        };
        switch ($action) {
            case 'howinfo':
                return "Người bán gõ trong phòng:\n/thongtin hàng gì, chi tiết, bảo hành, cách & thời gian giao\n\nNgười mua gõ:\n/yeucau cần kiểm tra gì, điều kiện nhận hàng (không có thì ghi: không)";
            case 'agree':
                $need(['buyer', 'seller'], ['agree']);
                if (!$d['seller_info'] || !$d['buyer_req']) throw new HttpError('Chờ ' . implode(' và ', array_filter([!$d['seller_info'] ? 'người bán gửi thông tin hàng (/thongtin)' : null, !$d['buyer_req'] ? 'người mua gửi yêu cầu (/yeucau)' : null])) . ' rồi mới đồng ý được');
                DB::run("UPDATE escrow_deals SET {$role}_ok = 1 WHERE id = ?", [$id]);
                $d = self::deal($id);
                if ($d['buyer_ok'] && $d['seller_ok'] && $d['buyer_tg'] && $d['seller_tg']) {
                    if (self::config()['mm_approve']) {
                        self::refresh($id);
                        self::say($d, '🤝 Hai bên đã đồng ý điều khoản. Chờ <b>trung gian duyệt</b> để bắt đầu bước chuyển tiền.');
                        self::notifyMiddlemen($d, '🧾 #' . $id . ': 2 bên đã chốt điều khoản — vào phòng xem & bấm "TG duyệt điều khoản & bắt đầu"');
                        return 'Đã đồng ý, chờ trung gian duyệt';
                    }
                    return self::startPay($id);
                }
                self::refresh($id);
                return 'Đã ghi nhận, chờ bên còn lại đồng ý';
            case 'mmok':
                $need(['mm'], ['agree']);
                if (!$d['buyer_ok'] || !$d['seller_ok']) throw new HttpError('Chờ 2 bên đồng ý điều khoản trước');
                DB::run('UPDATE escrow_deals SET mm_ok = 1, middleman = ? WHERE id = ?', [$name, $id]);
                self::say($d, '🛡 Trung gian <b>' . tg_esc($name) . '</b> đã duyệt điều khoản. Bắt đầu giao dịch!');
                return self::startPay($id);
            case 'claim':
                $need(['buyer'], ['pay']);
                DB::run('UPDATE escrow_deals SET claimed_at = ? WHERE id = ?', [now(), $id]);
                if (Wallet::config(0)['has_api']) { try { Wallet::sync(true, 0); } catch (Throwable $e) {} } // để trung gian thấy ngay số tiền ngân hàng báo
                self::notifyMiddlemen(self::deal($id), '💸 Người mua báo đã chuyển ' . self::vnd((int)$d['pay_total']) . ' cho #' . $id . ' — kiểm tra tài khoản & bấm "TG xác nhận đã nhận tiền"');
                self::say($d, '⏳ Người mua báo đã chuyển tiền. <b>Trung gian đang kiểm tra</b> và sẽ xác nhận.');
                return 'Đã báo trung gian, chờ xác nhận';
            case 'mmpaid':
                $need(['mm'], ['agree', 'pay']);
                DB::run('UPDATE escrow_deals SET middleman = ?, paid_amount = CASE WHEN paid_amount < pay_total THEN pay_total ELSE paid_amount END WHERE id = ?', [$name, $id]);
                self::markPaid($id, "trung gian $name xác nhận");
                return 'Đã xác nhận nhận tiền';
            case 'delivered':
                $need(['seller'], ['paid']);
                DB::run("UPDATE escrow_deals SET status = 'delivered', delivered_at = ?, reminded = 0 WHERE id = ?", [now(), $id]);
                self::refresh($id);
                self::say($d, '📦 Người bán báo <b>đã giao hàng</b>. Người mua ' . self::who($d, 'buyer') . ' kiểm tra rồi bấm <b>👍 Đã nhận hàng OK</b>.');
                self::notifyMiddlemen(self::deal($id), '📦 Người bán đã giao #' . $id);
                return 'Đã báo giao hàng';
            case 'received':
                $need(['buyer'], ['delivered', 'paid']);
                DB::run("UPDATE escrow_deals SET status = 'confirmed', confirmed_at = ? WHERE id = ?", [now(), $id]);
                self::refresh($id);
                $dd = self::deal($id);
                self::say($dd, '👍 Người mua <b>đã nhận hàng OK</b>. Trung gian sẽ giải ngân ' . self::vnd((int)$dd['seller_gets']) . ' cho người bán.' . ($dd['seller_bank'] || self::config()['payout'] === 'wallet' ? '' : "\n🏪 Người bán gửi STK: <code>/stk Ngân hàng Số TK Tên</code>"));
                self::notifyMiddlemen($dd, '✅ Người mua xác nhận #' . $id . ' — giải ngân ' . self::vnd((int)$dd['seller_gets']) . ($dd['seller_bank'] ? ' · STK: ' . tg_esc($dd['seller_bank']) : ''));
                return 'Cảm ơn, trung gian sẽ giải ngân';
            case 'dispute':
                $need(['buyer', 'seller'], ['paid', 'delivered']);
                DB::run("UPDATE escrow_deals SET status = 'disputed', dispute_by = ?, dispute_reason = ? WHERE id = ?", [$role === 'buyer' ? 'Người mua' : 'Người bán', mb_substr($extra ?: 'Bấm khiếu nại trong phòng', 0, 1000), $id]);
                self::refresh($id);
                self::say($d, '⚠️ <b>' . ($role === 'buyer' ? 'Người mua' : 'Người bán') . ' khiếu nại.</b> Tiền vẫn được giữ an toàn. Hai bên trình bày & gửi bằng chứng trong phòng, trung gian sẽ quyết định.');
                self::notifyMiddlemen(self::deal($id), '⚠️ KHIẾU NẠI #' . $id);
                return 'Đã gửi khiếu nại, trung gian sẽ xử lý';
            case 'release':
            case 'refund':
                $need(['mm'], ['paid', 'delivered', 'confirmed', 'disputed']);
                return self::settle($id, $action === 'release', $name);
            case 'cancel':
                if ($role !== 'mm') $need(['buyer', 'seller'], ['agree', 'pay']);
                else $need(['mm'], ['queue', 'agree', 'pay']);
                if ((int)$d['paid_amount'] > 0) throw new HttpError('Đã nhận một phần tiền: trung gian cần hoàn tiền thay vì huỷ');
                self::close($id, 'cancelled', $name, ($role === 'mm' ? 'Trung gian' : ($role === 'buyer' ? 'Người mua' : 'Người bán')) . " $name huỷ");
                return 'Đã huỷ giao dịch';
        }
        throw new HttpError('Hành động không hợp lệ');
    }

    /** Điều khoản đã chốt → chuyển sang bước chuyển tiền */
    private static function startPay(int $id): string
    {
        DB::run("UPDATE escrow_deals SET status = 'pay', agreed_at = ? WHERE id = ? AND status = 'agree'", [now(), $id]);
        $d = self::deal($id);
        self::refresh($id);
        if (self::config()['bot_pay_info']) self::payInfo($d);
        else self::say($d, "💳 <b>Trung gian sẽ gửi thông tin nhận tiền (QR / số tài khoản) ngay trong phòng.</b>\n🛒 Người mua chuyển đúng <b>" . self::vnd((int)$d['pay_total']) . "</b>, nội dung <code>" . self::code($d) . "</code>, rồi bấm <b>💸 Tôi đã chuyển tiền</b>.\n⚠️ Chỉ chuyển theo thông tin trung gian gửi trong phòng này.");
        self::notifyMiddlemen($d, '💳 #' . $id . ': đã duyệt điều khoản — gửi QR / STK nhận tiền cho người mua trong phòng');
        return 'Đã chốt điều khoản, chờ người mua chuyển tiền';
    }

    /** Người bán gửi thông tin hàng / người mua gửi yêu cầu (trong phòng hoặc trên web). Sửa sau khi đồng ý → 2 bên đồng ý lại. */
    public static function setTerms(int $id, array $from, string $kind, string $text, bool $fromWeb = false): string
    {
        $d = self::deal($id) ?: throw new HttpError('Không tìm thấy giao dịch');
        $role = self::roleOf($d, $from);
        $need = $kind === 'info' ? 'seller' : 'buyer';
        if ($role !== $need) throw new HttpError($kind === 'info' ? 'Chỉ người bán gửi thông tin hàng' : 'Chỉ người mua gửi yêu cầu giao dịch');
        if ($d['status'] !== 'agree') throw new HttpError('Đã chốt điều khoản, không sửa được nữa');
        $text = trim(mb_substr($text, 0, 1500));
        if (mb_strlen($text) < 2) throw new HttpError('Nội dung quá ngắn');
        $col = $kind === 'info' ? 'seller_info' : 'buyer_req';
        $changed = $d['buyer_ok'] || $d['seller_ok'];
        DB::run("UPDATE escrow_deals SET $col = ?, buyer_ok = 0, seller_ok = 0, mm_ok = 0 WHERE id = ?", [$text, $id]);
        $d = self::deal($id);
        $label = $kind === 'info' ? '📝 Người bán đã ' . ($changed || $fromWeb ? 'cập nhật' : 'gửi') . ' <b>thông tin hàng</b>' : '📋 Người mua đã ' . ($changed || $fromWeb ? 'cập nhật' : 'gửi') . ' <b>yêu cầu giao dịch</b>';
        $full = $d['seller_info'] && $d['buyer_req'];
        self::say($d, $label . ":\n" . tg_esc($text) . ($changed ? "\n\n⚠️ Điều khoản thay đổi: 2 bên cần bấm <b>Tôi đồng ý</b> lại." : '') . ($full ? "\n\n👉 Đã đủ thông tin. Hai bên đọc kỹ tin đã ghim rồi bấm <b>Tôi đồng ý điều khoản</b> bên dưới." : ''));
        self::refresh($id); // thẻ thông tin đã ghim cập nhật tại chỗ
        if ($full) self::notifyMiddlemen($d, '🧾 #' . $id . ': 2 bên đã gửi đủ thông tin / yêu cầu');
        self::step($id);
        return $kind === 'info' ? 'Đã lưu thông tin hàng' : 'Đã lưu yêu cầu';
    }

    /** Trung gian giải ngân (người bán) hoặc hoàn tiền (người mua) */
    private static function settle(int $id, bool $release, string $by): string
    {
        $d = self::deal($id);
        $c = self::config();
        $note = '';
        if ($release && $c['payout'] === 'wallet') {
            $u = $d['seller_tg'] ? DB::get('SELECT * FROM app_users WHERE tg_user_id = ? AND disabled = 0', [$d['seller_tg']]) : null;
            if (!$u) $u = TgLogin::createUser((int)$d['seller_tg'], (string)($d['seller_name'] ?? ''), (string)($d['seller_username'] ?? ''));
            Wallet::credit((int)$u['id'], (int)$d['seller_gets'], 'escrow', "GDTG #$id: " . mb_substr((string)$d['description'], 0, 80), 'esc' . $id);
            $note = 'đã cộng ' . self::vnd((int)$d['seller_gets']) . ' vào ví web của người bán';
        }
        DB::run('UPDATE escrow_deals SET middleman = ? WHERE id = ?', [$by, $id]);
        self::close($id, $release ? 'completed' : 'refunded', $by, $release ? 'Giải ngân cho người bán' . ($note ? " ($note)" : '') : 'Hoàn tiền cho người mua');
        return $release ? 'Đã giải ngân ✅' : 'Đã ghi nhận hoàn tiền';
    }

    /** Kết thúc giao dịch: lưu bằng chứng, thông báo, hẹn đóng phòng sau 5 phút */
    public static function close(int $id, string $status, string $by, string $why): void
    {
        $d = self::deal($id);
        if (!$d || !in_array($d['status'], self::OPEN, true)) throw new HttpError('Giao dịch đã kết thúc');
        DB::run('UPDATE escrow_deals SET status = ?, closed_at = ?, note = ? WHERE id = ?', [$status, now(), mb_substr($why, 0, 190), $id]);
        $d = self::deal($id);
        $icon = ['completed' => '🎉', 'refunded' => '↩️', 'cancelled' => '❌'][$status];
        $msg = "$icon <b>GDTG #$id: " . self::STATUS[$status] . "</b>\n$why · bởi " . tg_esc($by) . ($d['room_id'] ? "\n\n🔒 Sau 5 phút phòng sẽ được dọn (tin nhắn được lưu làm bằng chứng) và 2 bên được mời ra. Cảm ơn đã dùng dịch vụ!" : '');
        self::refresh($id);
        self::step($id); // giao dịch kết thúc → xoá tin "việc cần làm"
        self::say($d, $msg);
        foreach (['buyer', 'seller'] as $r) if ($d[$r . '_tg']) self::dm($d, $d[$r . '_tg'], $msg);
        Repo::log(null, $by, 'escrow', null, "GDTG #$id: " . self::STATUS[$status] . " · $why", ['source' => 'bot']);
        if ($d['room_id']) DB::run("UPDATE escrow_rooms SET status = 'closing', close_at = ? WHERE id = ?", [now() + 300, $d['room_id']]);
    }

    /** Đóng phòng: lưu tin nhắn làm bằng chứng, mời 2 bên ra, đổi tên, trả phòng trống */
    const FILE_DIR = DATA_DIR . '/escrow';
    const MAX_FILE = 20 * 1024 * 1024;

    /** Vai trò người gửi trong giao dịch: buyer / seller / mm / bot / sys */
    private static function roleOfId(array $d, array $r): string
    {
        if ($r['kind'] === 'service') return 'sys';
        $id = (string)$r['tg_user_id'];
        if ($d['buyer_tg'] && $id === (string)$d['buyer_tg']) return 'buyer';
        if ($d['seller_tg'] && $id === (string)$d['seller_tg']) return 'seller';
        if (self::isMiddleman($id)) return 'mm';
        return !empty($r['is_bot']) ? 'bot' : 'other';
    }

    /**
     * Tin nhắn trong phòng của giao dịch, dạng gọn để lưu / hiển thị.
     * $saveFiles = true: tải ảnh / tệp về data/escrow/<mã GD>/ để xem lại cả khi tin đã bị xoá trên Telegram.
     */
    public static function transcriptRows(array $d, int $groupId, bool $saveFiles = false): array
    {
        $rows = DB::all('SELECT * FROM tg_messages WHERE group_id = ? AND created_at >= ? ORDER BY message_id LIMIT 2000', [$groupId, (int)$d['room_at'] - 5]);
        $group = $saveFiles ? Repo::group($groupId) : null;
        $out = [];
        $saved = 0;
        foreach ($rows as $r) {
            $x = ['mid' => (int)$r['message_id'], 'r' => self::roleOfId($d, $r), 'n' => $r['name'], 'u' => $r['username'], 'id' => (string)$r['tg_user_id'], 'k' => $r['kind'], 'm' => $r['media'],
                't' => $r['text'], 'del' => (int)$r['deleted'], 'ed' => (int)$r['edited'], 'at' => (int)$r['created_at']];
            if ($r['reply_to']) { $x['rn'] = $r['reply_name']; $x['rt'] = $r['reply_text']; }
            if ($r['file_id']) {
                $x['fn'] = $r['file_name']; $x['mime'] = $r['mime']; $x['size'] = (int)$r['file_size'];
                if ($saveFiles && $group && $saved < 60 && (int)$r['file_size'] <= self::MAX_FILE && $r['mime'] !== 'application/x-tgsticker') {
                    try {
                        [$path, $mime] = ChatLog::download($group, $r, false);
                        $dir = self::FILE_DIR . '/' . (int)$d['id'];
                        if (!is_dir($dir)) @mkdir($dir, 0755, true);
                        @file_put_contents(self::FILE_DIR . '/index.html', '');
                        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'video/mp4' => 'mp4', 'application/pdf' => 'pdf'][$mime] ?? 'bin';
                        $name = $r['message_id'] . '.' . $ext;
                        if (@copy($path, "$dir/$name")) { $x['f'] = $name; $x['mime'] = $mime; $saved++; }
                    } catch (Throwable $e) { $x['ferr'] = mb_substr($e->getMessage(), 0, 120); }
                }
            }
            $out[] = $x;
        }
        return $out;
    }

    /** Xoá toàn bộ tin nhắn của giao dịch trong phòng (Telegram chỉ cho bot xoá tin dưới 48 giờ). Trả về số tin đã gửi lệnh xoá. */
    private static function wipeRoom(Telegram $api, array $room, array $d): int
    {
        $ids = array_map('intval', array_column(DB::all('SELECT message_id FROM tg_messages WHERE group_id = ? AND created_at >= ?', [$room['group_id'], (int)$d['room_at'] - 5]), 'message_id'));
        foreach (['card_msg_id', 'action_msg_id'] as $k) if ($d[$k]) $ids[] = (int)$d[$k];
        if (!$ids) return 0;
        // Gồm cả tin bot không ghi lại (VD tin "đã ghim"): xoá theo dải id từ tin đầu tới tin cuối
        $all = range(min($ids), max($ids) + 5);
        if (count($all) > 3000) $all = $ids; // dải quá lớn → chỉ xoá các tin đã biết
        foreach (array_chunk($all, 100) as $chunk) $api->tryCall('deleteMessages', ['chat_id' => $room['chat_id'], 'message_ids' => $chunk]);
        DB::run("UPDATE tg_messages SET deleted = 1, note = 'Dọn phòng GDTG' WHERE group_id = ? AND created_at >= ?", [$room['group_id'], (int)$d['room_at'] - 5]);
        return count($all);
    }

    public static function freeRoom(array $room): void
    {
        $d = $room['deal_id'] ? self::deal((int)$room['deal_id']) : null;
        $api = self::api($room);
        if ($d) {
            // 1. Lưu bằng chứng (kèm ảnh / tệp) TRƯỚC khi dọn phòng
            @set_time_limit(120);
            $rows = self::transcriptRows($d, (int)$room['group_id'], true);
            DB::run('UPDATE escrow_deals SET transcript = ? WHERE id = ?', [json_encode($rows, JSON_UNESCAPED_UNICODE), $d['id']]);
            if ($api) {
                foreach (['buyer', 'seller'] as $r) {
                    if (!$d[$r . '_tg'] || self::isMiddleman($d[$r . '_tg'])) continue;
                    self::tag($d, $d[$r . '_tg'], '');
                    $api->tryCall('banChatMember', ['chat_id' => $room['chat_id'], 'user_id' => $d[$r . '_tg'], 'until_date' => now() + 60]);
                    $api->tryCall('unbanChatMember', ['chat_id' => $room['chat_id'], 'user_id' => $d[$r . '_tg'], 'only_if_banned' => true]);
                }
            }
        }
        if ($api) {
            if ($room['invite_link']) $api->tryCall('revokeChatInviteLink', ['chat_id' => $room['chat_id'], 'invite_link' => $room['invite_link']]);
            $api->tryCall('unpinAllChatMessages', ['chat_id' => $room['chat_id']]);
            if ($d) self::wipeRoom($api, $room, $d); // 2. Dọn phòng: xoá tin nhắn cũ của giao dịch
        }
        DB::run("UPDATE escrow_rooms SET status = 'free', deal_id = NULL, close_at = NULL, invite_link = NULL WHERE id = ?", [$room['id']]);
        self::setTitle($room, null);
        self::assignNext();
    }

    private static function assignNext(): void
    {
        foreach (DB::all("SELECT id FROM escrow_deals WHERE status = 'queue' ORDER BY id LIMIT 10") as $q) {
            if (!self::assignRoom((int)$q['id'])) break;
            $d = self::deal((int)$q['id']);
            $link = self::invite($d);
            foreach (['buyer', 'seller'] as $r) if ($d[$r . '_tg']) self::dm($d, $d[$r . '_tg'], '🚪 Đã có phòng cho <b>GDTG #' . $d['id'] . "</b>.\n👉 Vào phòng: $link");
        }
    }

    // ================= Trong phòng =================
    /** Yêu cầu vào phòng: chỉ duyệt người mua / người bán của giao dịch đang dùng phòng (và trung gian) */
    public static function onJoinRequest(Telegram $api, array $group, array $req): bool
    {
        $room = self::roomOfGroup((int)$group['id']);
        if (!$room) return false;
        $u = $req['from'];
        $d = $room['deal_id'] ? self::deal((int)$room['deal_id']) : null;
        $ok = self::isMiddleman($u['id']) || ($d && in_array($d['status'], self::OPEN, true) && self::roleOf($d, $u));
        $api->tryCall($ok ? 'approveChatJoinRequest' : 'declineChatJoinRequest', ['chat_id' => $group['chat_id'], 'user_id' => $u['id']]);
        if (!$ok) $api->tryCall('sendMessage', ['chat_id' => $u['id'], 'text' => '⛔ Phòng giao dịch này dành riêng cho người mua / người bán của giao dịch. Bạn không thể vào.']);
        return true;
    }

    /**
     * Người mua / người bán / trung gian vừa vào phòng. Phòng ẩn lịch sử với người mới nên họ KHÔNG thấy thẻ đã ghim trước đó:
     * gửi lại thẻ giao dịch (kèm nút) + ghim lại + xoá thẻ cũ; đang chờ chuyển tiền thì gửi lại thông tin chuyển khoản.
     */
    public static function memberJoined(Telegram $api, array $group, array $d, array $u, string $role): void
    {
        // Telegram có thể báo 2 lần (tin "đã vào nhóm" + chat_member) → chỉ xử lý 1 lần / 30 giây
        $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = -8 AND group_id = ? AND tg_user_id = ?', [$group['id'], $u['id']]);
        if (now() - $last < 30) return;
        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = -8 AND group_id = ? AND tg_user_id = ?', [$group['id'], $u['id']]);
        DB::insert('tg_rule_hits', ['rule_id' => -8, 'group_id' => $group['id'], 'tg_user_id' => $u['id'], 'last_at' => now()]);
        $d = self::deal((int)$d['id']);
        if (!in_array($d['status'], self::OPEN, true)) return;
        if ($role !== 'mm') {
            DB::run("UPDATE escrow_deals SET {$role}_in = 1 WHERE id = ?", [$d['id']]);
            self::tag($d, $u['id'], $role === 'buyer' ? 'Người mua' : 'Người bán');
        }
        $d = self::deal((int)$d['id']);
        $label = fn($r) => ($r === 'buyer' ? 'Người mua' : 'Người bán') . ' ' . self::who($d, $r);
        if (!$d['buyer_in'] || !$d['seller_in']) { // mới 1 bên → báo chờ bên còn lại, chưa gửi thẻ
            if ($role !== 'mm') self::say($d, '👋 ' . $label($role) . ' đã vào phòng.' . "\n⏳ Đang chờ " . ($d['buyer_in'] ? 'người bán' : 'người mua') . ' vào phòng, khi đủ 2 bên bot sẽ gửi thẻ giao dịch.');
            return;
        }
        // Đủ 2 bên: lần đầu gửi nội quy + thẻ; người vào lại sau đó → gửi lại thẻ để họ thấy
        $first = !$d['card_msg_id'];
        $rules = trim(self::config()['rules']);
        if ($role !== 'mm') self::say($d, '👋 ' . $label($role) . ' đã vào phòng.' . ($first ? ' <b>Đã đủ 2 bên</b>.' : '') . ($rules !== '' && ($first || $role !== 'mm') ? "\n\n📜 <b>Nội quy phòng GDTG</b>\n" . tg_esc($rules) : ''));
        self::repostCard($d);
        self::step((int)$d['id']);
        if ($d['status'] === 'pay' && $role === 'buyer' && self::config()['bot_pay_info']) self::payInfo($d);
    }

    /** chat_member trong phòng: người mua / bán vào → gửi lại thẻ (phòng có thể đang ẩn tin "đã vào nhóm") */
    public static function onChatMember(Telegram $api, array $group, array $cm): void
    {
        $st = $cm['new_chat_member']['status'] ?? '';
        $old = $cm['old_chat_member']['status'] ?? '';
        if (!in_array($st, ['member', 'restricted', 'administrator'], true) || in_array($old, ['member', 'administrator', 'creator'], true)) return;
        $u = $cm['new_chat_member']['user'];
        if (!empty($u['is_bot'])) return;
        $room = self::roomOfGroup((int)$group['id']);
        $d = $room && $room['deal_id'] ? self::deal((int)$room['deal_id']) : null;
        if (!$d) return;
        $role = self::roleOf($d, $u);
        if ($role) self::memberJoined($api, $group, $d, $u, $role);
    }

    /** Gửi lại thẻ giao dịch xuống cuối phòng, ghim lại, xoá thẻ cũ (để ai vào sau cũng thấy & bấm được) */
    public static function repostCard(array $d): void
    {
        $room = self::room($d);
        $api = $room ? self::api($room) : null;
        if (!$api) return;
        $sent = $api->tryCall('sendMessage', ['chat_id' => $room['chat_id'], 'text' => self::cardText($d), 'parse_mode' => 'HTML']);
        if (empty($sent['message_id'])) return;
        if ($d['card_msg_id']) $api->tryCall('deleteMessage', ['chat_id' => $room['chat_id'], 'message_id' => $d['card_msg_id']]);
        DB::run('UPDATE escrow_deals SET card_msg_id = ? WHERE id = ?', [$sent['message_id'], $d['id']]);
        $api->tryCall('pinChatMessage', ['chat_id' => $room['chat_id'], 'message_id' => $sent['message_id'], 'disable_notification' => true]);
    }

    /** Nội dung ngắn "việc cần làm" theo bước hiện tại */
    private static function stepText(array $d): string
    {
        $c = self::config();
        $h = "📌 <b>GDTG #{$d['id']}</b> · " . (self::STATUS[$d['status']] ?? $d['status']) . "\n";
        $wait = fn(array $who) => implode(', ', array_keys(array_filter($who)));
        switch ($d['status']) {
            case 'agree':
                if (!$d['seller_info'] || !$d['buyer_req']) {
                    return $h . "👉 <b>Việc cần làm:</b>\n" . implode("\n", array_filter([
                        !$d['seller_info'] ? '• Người bán gửi thông tin hàng: <code>/thongtin nội dung</code>' : null,
                        !$d['buyer_req'] ? '• Người mua gửi yêu cầu: <code>/yeucau nội dung</code>' : null,
                    ]));
                }
                if (!$d['buyer_ok'] || !$d['seller_ok']) return $h . "👉 Đọc kỹ thông tin ở <b>tin đã ghim</b> rồi bấm <b>Tôi đồng ý điều khoản</b>.\n⏳ Còn chờ: " . $wait(['người mua' => !$d['buyer_ok'], 'người bán' => !$d['seller_ok']]);
                return $h . '👉 Hai bên đã đồng ý. Chờ <b>trung gian duyệt điều khoản</b>.';
            case 'pay':
                return $h . ($d['claimed_at'] ? '⏳ Người mua đã báo chuyển tiền. Chờ <b>trung gian xác nhận</b> đã nhận.'
                    : '👉 Người mua chuyển <b>' . self::vnd((int)$d['pay_total'] - (int)$d['paid_amount']) . '</b>, nội dung <code>' . self::code($d) . '</code> theo thông tin nhận tiền ' . ($c['bot_pay_info'] ? 'bot đã gửi' : 'trung gian gửi') . ', rồi bấm <b>Tôi đã chuyển tiền</b>.');
            case 'paid':
                return $h . '👉 Người bán giao hàng cho người mua rồi bấm <b>Đã giao hàng</b>.' . ($d['seller_bank'] ? '' : "\n• Người bán gửi STK nhận tiền: <code>/stk Ngân hàng Số TK Tên</code>");
            case 'delivered':
                return $h . '👉 Người mua kiểm tra hàng, bấm <b>Đã nhận hàng OK</b> hoặc <b>Khiếu nại</b> nếu có vấn đề.';
            case 'confirmed':
                return $h . '👉 Trung gian chuyển <b>' . self::vnd((int)$d['seller_gets']) . '</b> cho người bán' . ($d['seller_bank'] ? ' (' . tg_esc((string)$d['seller_bank']) . ')' : ' (người bán gửi <code>/stk</code>)') . ' rồi bấm <b>Giải ngân</b>.';
            case 'disputed':
                return $h . '⚠️ Đang khiếu nại. Hai bên trình bày & gửi bằng chứng trong phòng, <b>trung gian</b> sẽ quyết định.';
        }
        return $h;
    }

    /** Gửi tin "việc cần làm" mới ở cuối phòng (kèm nút của bước này), xoá tin cũ. Giao dịch kết thúc → chỉ xoá. */
    public static function step(int $id): void
    {
        $d = self::deal($id);
        if (!$d) return;
        $room = self::room($d);
        $api = $room ? self::api($room) : null;
        if (!$api) return;
        if ($d['action_msg_id']) $api->tryCall('deleteMessage', ['chat_id' => $room['chat_id'], 'message_id' => $d['action_msg_id']]);
        $mid = null;
        if (in_array($d['status'], self::OPEN, true) && $d['status'] !== 'queue' && $d['buyer_in'] && $d['seller_in']) {
            $sent = $api->tryCall('sendMessage', ['chat_id' => $room['chat_id'], 'text' => self::stepText($d), 'parse_mode' => 'HTML', 'reply_markup' => self::buttons($d)]);
            $mid = $sent['message_id'] ?? null;
        }
        DB::run('UPDATE escrow_deals SET action_msg_id = ? WHERE id = ?', [$mid, $id]);
    }

    /** Tin nhắn trong phòng (đã lưu làm bằng chứng ở ChatLog). Xử lý người lạ, /stk, /gd. */
    public static function onRoomMessage(Telegram $api, array $group, array $msg): void
    {
        $room = self::roomOfGroup((int)$group['id']);
        $d = $room && $room['deal_id'] ? self::deal((int)$room['deal_id']) : null;
        // Người vào phòng
        foreach ($msg['new_chat_members'] ?? [] as $u) {
            if (!empty($u['is_bot'])) continue;
            $role = $d ? self::roleOf($d, $u) : (self::isMiddleman($u['id']) ? 'mm' : null);
            if (!$role) { // người lạ → mời ra
                $api->tryCall('banChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $u['id'], 'until_date' => now() + 60]);
                $api->tryCall('unbanChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $u['id'], 'only_if_banned' => true]);
                continue;
            }
            if ($d) self::memberJoined($api, $group, $d, $u, $role);
        }
        if (!$d) return;
        $text = trim((string)($msg['text'] ?? ''));
        if (preg_match('~^/(thongtin|yeucau)(?:@\w+)?(?:\s+(.+))?$~su', $text, $tm)) {
            if (trim($tm[2] ?? '') === '') { $api->tryCall('sendMessage', ['chat_id' => $group['chat_id'], 'text' => $tm[1] === 'thongtin' ? 'Cú pháp: /thongtin hàng gì, chi tiết, bảo hành, cách & thời gian giao' : 'Cú pháp: /yeucau cần kiểm tra gì, điều kiện nhận hàng (không có thì ghi: không)', 'reply_parameters' => ['message_id' => $msg['message_id']]]); return; }
            try { self::setTerms((int)$d['id'], $msg['from'], $tm[1] === 'thongtin' ? 'info' : 'req', $tm[2]); }
            catch (HttpError $e) { $api->tryCall('sendMessage', ['chat_id' => $group['chat_id'], 'text' => '❌ ' . $e->getMessage(), 'reply_parameters' => ['message_id' => $msg['message_id']]]); }
            return;
        }
        if (preg_match('~^/stk(?:@\w+)?\s+(.{6,})$~su', $text, $m)) {
            $role = self::roleOf($d, $msg['from']);
            if (!in_array($role, ['buyer', 'seller'], true)) { $api->tryCall('sendMessage', ['chat_id' => $group['chat_id'], 'text' => 'Chỉ người mua / người bán gửi STK.', 'reply_parameters' => ['message_id' => $msg['message_id']]]); return; }
            DB::run("UPDATE escrow_deals SET {$role}_bank = ? WHERE id = ?", [mb_substr(trim(preg_replace('/\s+/u', ' ', $m[1])), 0, 190), $d['id']]);
            self::refresh((int)$d['id']);
            self::step((int)$d['id']);
            $api->tryCall('sendMessage', ['chat_id' => $group['chat_id'], 'text' => '✅ Đã lưu STK ' . ($role === 'buyer' ? 'người mua (dùng khi hoàn tiền)' : 'người bán (dùng khi giải ngân)') . '.', 'reply_parameters' => ['message_id' => $msg['message_id']]]);
            if (in_array($d['status'], ['confirmed', 'disputed'], true)) self::notifyMiddlemen(self::deal((int)$d['id']), '🏦 ' . ($role === 'buyer' ? 'Người mua' : 'Người bán') . ' gửi STK #' . $d['id'] . ': ' . tg_esc(trim($m[1])));
        } elseif (preg_match('~^/(gd|gdtg)(?:@\w+)?$~i', $text)) {
            self::repostCard($d);
            self::step((int)$d['id']);
        }
    }

    // ================= Hạn giờ (cron / webhook) =================
    public static function sweep(): void
    {
        $c = self::config();
        foreach (DB::all("SELECT * FROM escrow_rooms WHERE status = 'closing' AND close_at <= ?", [now()]) as $room) { try { self::freeRoom($room); } catch (Throwable $e) {} }
        if (!$c['enabled'] && !DB::val("SELECT 1 FROM escrow_deals WHERE status IN ('" . implode("','", self::OPEN) . "') LIMIT 1")) return;
        // Chưa chuyển tiền quá hạn → huỷ (chưa có tiền nên an toàn)
        foreach (DB::all("SELECT * FROM escrow_deals WHERE status = 'pay' AND paid_amount = 0 AND claimed_at IS NULL AND agreed_at < ?", [now() - $c['pay_minutes'] * 60]) as $d) {
            try { self::close((int)$d['id'], 'cancelled', 'Bot', 'Quá ' . $c['pay_minutes'] . ' phút người mua chưa chuyển tiền'); } catch (Throwable $e) {}
        }
        // Người mua đã báo chuyển / ngân hàng đã báo tiền nhưng trung gian chưa xác nhận → nhắc trung gian (1 lần)
        foreach (DB::all("SELECT * FROM escrow_deals WHERE status = 'pay' AND reminded = 0 AND (claimed_at IS NOT NULL OR paid_amount > 0) AND COALESCE(claimed_at, agreed_at) < ?", [now() - 30 * 60]) as $d) {
            DB::run('UPDATE escrow_deals SET reminded = 1 WHERE id = ?', [$d['id']]);
            self::notifyMiddlemen($d, '⏰ #' . $d['id'] . ' chờ trung gian xác nhận đã nhận tiền hơn 30 phút');
        }
        foreach (DB::all("SELECT * FROM escrow_deals WHERE status = 'agree' AND room_at < ?", [now() - 86400]) as $d) {
            try { self::close((int)$d['id'], 'cancelled', 'Bot', 'Quá 24 giờ chưa đủ 2 bên đồng ý'); } catch (Throwable $e) {}
        }
        // Nhắc giao hàng / xác nhận (chỉ nhắc, không tự chuyển tiền)
        foreach (DB::all("SELECT * FROM escrow_deals WHERE reminded = 0 AND ((status = 'paid' AND paid_at < ?) OR (status = 'delivered' AND delivered_at < ?))", [now() - $c['deliver_hours'] * 3600, now() - $c['confirm_hours'] * 3600]) as $d) {
            DB::run('UPDATE escrow_deals SET reminded = 1 WHERE id = ?', [$d['id']]);
            $late = $d['status'] === 'paid' ? 'người bán chưa giao hàng sau ' . $c['deliver_hours'] . ' giờ' : 'người mua chưa xác nhận sau ' . $c['confirm_hours'] . ' giờ';
            self::say($d, '⏰ Nhắc: ' . $late . '. ' . ($d['status'] === 'paid' ? 'Người bán giao hàng rồi bấm <b>Đã giao hàng</b>.' : 'Người mua bấm <b>Đã nhận hàng OK</b> hoặc <b>Khiếu nại</b>.') . ' Trung gian đã được báo.');
            self::notifyMiddlemen($d, '⏰ Quá hạn #' . $d['id'] . ': ' . $late);
        }
        self::assignNext();
    }

    // ================= Cho giao diện =================
    public static function row(array $d, bool $full = false): array
    {
        $r = ['id' => (int)$d['id'], 'status' => $d['status'], 'status_label' => self::STATUS[$d['status']] ?? $d['status'], 'amount' => (int)$d['amount'], 'fee' => (int)$d['fee'],
            'pay_total' => (int)$d['pay_total'], 'seller_gets' => (int)$d['seller_gets'], 'paid_amount' => (int)$d['paid_amount'], 'description' => $d['description'],
            'buyer' => ['tg' => $d['buyer_tg'] ? (string)$d['buyer_tg'] : null, 'name' => $d['buyer_name'], 'username' => $d['buyer_username'], 'ok' => (bool)$d['buyer_ok'], 'bank' => $d['buyer_bank']],
            'seller' => ['tg' => $d['seller_tg'] ? (string)$d['seller_tg'] : null, 'name' => $d['seller_name'], 'username' => $d['seller_username'], 'ok' => (bool)$d['seller_ok'], 'bank' => $d['seller_bank']],
            'room' => $d['room_id'] ? (int)DB::val('SELECT no FROM escrow_rooms WHERE id = ?', [$d['room_id']]) : null, 'invite' => in_array($d['status'], self::OPEN, true) ? self::invite($d) : null,
            'seller_info' => $d['seller_info'] ?? null, 'buyer_req' => $d['buyer_req'] ?? null, 'mm_ok' => (bool)($d['mm_ok'] ?? 0),
            'middleman' => $d['middleman'], 'dispute_by' => $d['dispute_by'], 'dispute_reason' => $d['dispute_reason'], 'note' => $d['note'], 'code' => self::code($d),
            'created_at' => (int)$d['created_at'], 'paid_at' => $d['paid_at'] ? (int)$d['paid_at'] : null, 'closed_at' => $d['closed_at'] ? (int)$d['closed_at'] : null];
        if ($full) $r['transcript'] = json_decode((string)$d['transcript'], true) ?: null;
        return $r;
    }
}

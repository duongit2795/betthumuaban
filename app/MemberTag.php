<?php
defined('APP') or exit;

/**
 * Tag thành viên (nhãn hiện cạnh tên trong nhóm, Bot API setChatMemberTag).
 * - Thành viên thường: setChatMemberTag (bot cần quyền "Quản lý tag" – can_manage_tags).
 * - Quản trị viên: danh hiệu admin (setChatAdministratorCustomTitle, chỉ với admin do bot bổ nhiệm).
 * - Tag tự động theo mốc điểm. Cấu hình lưu ở Settings 'member_tags'.
 */
class MemberTag
{
    const MAX = 16;
    const DEFAULTS = [
        'notify' => false,
        'notify_text' => '🏷 {mention} được gắn tag <b>{tag}</b>',
        'notify_autodel' => 60,
        'auto' => false,
        'auto_groups' => [],     // rỗng = mọi nhóm
        'auto_levels' => [],     // [{min: 100, tag: 'Tích cực'}, …]
        'auto_notify' => true,   // báo khi lên mốc tag tự động
        'auto_text' => '🎉 {mention} đạt <b>{points}</b> điểm và nhận tag <b>{tag}</b>!',
        'auto_admins' => false,  // tự động đổi cả danh hiệu admin
        // Cửa hàng tag: thành viên mua bằng số dư ví
        'shop' => false,
        'shop_groups' => [],     // rỗng = mọi nhóm
        'shop_items' => [],      // [{tag: 'VIP', price: 50000}, …]
        'shop_custom' => true,   // cho tự đặt chữ
        'shop_custom_price' => 30000,
        'shop_days' => 0,        // hạn dùng (ngày), 0 = vĩnh viễn
        'shop_banned' => 'admin, mod, quản trị, qtv, bot, owner, chủ nhóm, support, hỗ trợ',
        'shop_notify' => true,
        'shop_text' => '🛒 {mention} vừa mua tag <b>{tag}</b>{until}',
    ];

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('member_tags'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['notify', 'auto', 'auto_notify', 'auto_admins', 'shop', 'shop_custom', 'shop_notify'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['shop_groups'])) $c['shop_groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['shop_groups'], 'is_numeric'))));
        if (isset($in['shop_custom_price'])) $c['shop_custom_price'] = max(1000, min(1000000000, (int)$in['shop_custom_price']));
        if (isset($in['shop_days'])) $c['shop_days'] = max(0, min(3650, (int)$in['shop_days']));
        if (isset($in['shop_banned'])) $c['shop_banned'] = mb_substr(trim((string)$in['shop_banned']), 0, 1000);
        if (isset($in['shop_text'])) { $t = trim(mb_substr((string)$in['shop_text'], 0, 1000)); $c['shop_text'] = $t !== '' ? $t : self::DEFAULTS['shop_text']; }
        if (isset($in['shop_items'])) {
            $items = [];
            foreach (array_slice((array)$in['shop_items'], 0, 30) as $it) {
                $tag = trim((string)($it['tag'] ?? ''));
                if ($tag === '') continue;
                $tag = self::clean($tag);
                $price = (int)($it['price'] ?? 0);
                if ($price < 1000) throw new HttpError("Tag \"$tag\": giá tối thiểu 1.000đ");
                foreach ($items as $x) if (mb_strtolower($x['tag']) === mb_strtolower($tag)) throw new HttpError("Tag \"$tag\" bị trùng");
                $items[] = ['tag' => $tag, 'price' => min(1000000000, $price)];
            }
            $c['shop_items'] = $items;
        }
        if (isset($in['auto_groups'])) $c['auto_groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['auto_groups'], 'is_numeric'))));
        if (isset($in['notify_autodel'])) $c['notify_autodel'] = max(0, min(172800, (int)$in['notify_autodel']));
        foreach (['notify_text', 'auto_text'] as $k) {
            if (isset($in[$k])) {
                $t = trim(mb_substr((string)$in[$k], 0, 1000));
                $c[$k] = $t !== '' ? $t : self::DEFAULTS[$k];
            }
        }
        if (isset($in['auto_levels'])) {
            $levels = [];
            foreach (array_slice((array)$in['auto_levels'], 0, 20) as $l) {
                $tag = trim((string)($l['tag'] ?? ''));
                if ($tag === '' && ($l['min'] ?? '') === '') continue;
                $tag = self::clean($tag);
                if ($tag === '') throw new HttpError('Mỗi mốc điểm cần có tag');
                $min = max(0, (int)($l['min'] ?? 0));
                foreach ($levels as $x) if ($x['min'] === $min) throw new HttpError("Có 2 mốc trùng $min điểm");
                $levels[] = ['min' => $min, 'tag' => $tag];
            }
            usort($levels, fn($a, $b) => $a['min'] <=> $b['min']);
            $c['auto_levels'] = $levels;
        }
        Settings::set('member_tags', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    /** Kiểm tra tag: tối đa 16 ký tự, không emoji. Trả về tag đã chuẩn hoá ('' = xoá tag). */
    public static function clean(string $tag): string
    {
        $tag = trim(preg_replace('/\s+/u', ' ', $tag));
        if (preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F1E6}-\x{1F1FF}\x{FE0F}\x{200D}\x{20E3}\x{3030}\x{303D}\x{3297}\x{3299}]/u', $tag)) {
            throw new HttpError('Tag không được chứa emoji (giới hạn của Telegram)');
        }
        if (mb_strlen($tag) > self::MAX) throw new HttpError('Tag tối đa ' . self::MAX . ' ký tự (đang có ' . mb_strlen($tag) . ')');
        return $tag;
    }

    /** Thông báo lỗi Telegram dễ hiểu */
    private static function explain(string $err, bool $admin): string
    {
        if (stripos($err, 'not enough rights') !== false || stripos($err, 'CHAT_ADMIN_REQUIRED') !== false || stripos($err, 'right') !== false) {
            return $admin ? 'Bot chỉ đổi được danh hiệu của admin do chính bot bổ nhiệm (và bot cần quyền thêm admin)'
                : 'Bot cần là quản trị viên có quyền "Quản lý tag" (Manage tags) trong nhóm';
        }
        if (stripos($err, 'method not found') !== false || stripos($err, 'Not Found') !== false) return 'Telegram chưa hỗ trợ đổi tag cho bot này';
        if (stripos($err, 'USER_NOT_PARTICIPANT') !== false || stripos($err, 'user not found') !== false) return 'Người này không còn trong nhóm';
        return $err;
    }

    /**
     * Đặt / xoá tag cho 1 thành viên.
     * @param string $source manual (admin đặt) | auto (theo mốc điểm)
     */
    public static function set(array $group, $tgUserId, string $tag, string $actor, string $source = 'manual', bool $notify = true, string $logSource = 'web', ?int $until = null): array
    {
        $tag = self::clean($tag);
        $api = Repo::apiForGroup($group);
        if (!$api) throw new HttpError('Nhóm chưa có bot hoạt động');
        $m = Repo::member((int)$group['id'], $tgUserId) ?: Repo::upsertMember((int)$group['id'], ['id' => $tgUserId, 'first_name' => (string)$tgUserId]);
        $isAdmin = in_array((string)$tgUserId, array_map('strval', Repo::tgAdmins($group, $api)), true);
        try {
            if ($isAdmin) $api->call('setChatAdministratorCustomTitle', ['chat_id' => $group['chat_id'], 'user_id' => $tgUserId, 'custom_title' => $tag]);
            else $api->call('setChatMemberTag', ['chat_id' => $group['chat_id'], 'user_id' => $tgUserId, 'tag' => $tag]);
        } catch (TelegramException $e) {
            DB::run('UPDATE tg_members SET tag_try_at = ? WHERE id = ?', [now(), $m['id']]);
            Settings::set('member_tags_error', mb_substr(self::explain($e->getMessage(), $isAdmin) . ' · ' . $group['title'], 0, 300));
            throw new HttpError('Không đổi được tag: ' . self::explain($e->getMessage(), $isAdmin));
        }
        $before = (string)($m['tag'] ?? '');
        DB::run('UPDATE tg_members SET tag = ?, tag_source = ?, tag_try_at = NULL, tag_until = ? WHERE id = ?', [$tag !== '' ? $tag : null, $tag !== '' ? $source : null, $tag !== '' ? $until : null, $m['id']]);
        Settings::set('member_tags_error', '');
        Repo::log((int)$group['id'], $actor, 'tag', $m, ($tag !== '' ? "Tag: $tag" : 'Xoá tag') . ($before !== '' ? " (trước: $before)" : '') . ($isAdmin ? ' · danh hiệu admin' : ''),
            ['source' => $source === 'auto' ? 'bot' : $logSource, 'tag' => $tag, 'before' => $before, 'auto' => $source === 'auto']);

        $c = self::config();
        $want = $source === 'auto' ? $c['auto_notify'] : $c['notify'];
        if ($notify && $want && $tag !== '') {
            $m2 = DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
            $sent = $api->trySend($group['chat_id'], strtr($source === 'auto' ? $c['auto_text'] : $c['notify_text'], [
                '{mention}' => Repo::mention($m2), '{name}' => tg_esc(Repo::name($m2)), '{tag}' => tg_esc($tag),
                '{points}' => (string)$m2['points'], '{group}' => tg_esc($group['title']),
            ]));
            if ($sent && !empty($sent['message_id']) && $c['notify_autodel'] > 0 && $group['bot_id']) {
                DelQueue::add($api, (int)$group['bot_id'], $group['chat_id'], [$sent['message_id']], (int)$c['notify_autodel']);
            }
        }
        return DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
    }

    /** Tag theo mốc điểm cho số điểm hiện có ('' nếu chưa đạt mốc nào) */
    public static function levelTag(array $c, int $points): string
    {
        $tag = '';
        foreach ($c['auto_levels'] as $l) if ($points >= (int)$l['min']) $tag = $l['tag'];
        return $tag;
    }

    /**
     * Tự động đổi tag theo điểm (gọi sau khi điểm thay đổi). Không ghi đè tag admin tự đặt.
     * Lỗi (thiếu quyền) thì đợi 1 giờ mới thử lại với người đó.
     */
    public static function auto(array $group, $tgUserId): void
    {
        try {
            $c = self::config();
            if (!$c['auto'] || !$c['auto_levels']) return;
            if ($c['auto_groups'] && !in_array((int)$group['id'], $c['auto_groups'], true)) return;
            $m = Repo::member((int)$group['id'], $tgUserId);
            if (!$m || $m['is_bot'] || !in_array($m['status'], ['member', 'muted'], true)) return;
            if (in_array($m['tag_source'] ?? '', ['manual', 'shop'], true) && (string)$m['tag'] !== '') return; // admin đặt tay / thành viên đã mua
            if (!empty($m['tag_try_at']) && now() - (int)$m['tag_try_at'] < 3600) return;
            if (!$c['auto_admins'] && $m['is_admin']) return;
            $want = self::levelTag($c, (int)$m['points']);
            if ($want === (string)($m['tag'] ?? '') || $want === '') return;
            self::set($group, $tgUserId, $want, 'Bot (tag theo điểm)', 'auto', true, 'bot');
        } catch (Throwable $e) {
            // thiếu quyền / Telegram lỗi: đã ghi tag_try_at, không làm gián đoạn xử lý tin nhắn
        }
    }

    // ================= Cửa hàng tag =================
    public static function shopApplies(array $c, int $groupId): bool
    {
        return $c['shop'] && (!$c['shop_groups'] || in_array($groupId, $c['shop_groups'], true));
    }

    /** Kiểm tra tag thành viên tự đặt: không chứa từ cấm (chống giả mạo admin) */
    public static function checkBanned(array $c, string $tag): void
    {
        $t = mb_strtolower($tag);
        $plain = mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $tag));
        foreach (preg_split('/[,\n]+/u', (string)$c['shop_banned']) as $w) {
            $w = mb_strtolower(trim($w));
            if ($w === '') continue;
            $wp = preg_replace('/[^\p{L}\p{N}]/u', '', $w);
            if (str_contains($t, $w) || ($wp !== '' && str_contains($plain, $wp))) throw new HttpError("Tag không được chứa \"$w\"");
        }
    }

    /**
     * Thành viên mua tag bằng số dư ví. $item: tag có sẵn (khớp danh sách) hoặc chữ tự đặt.
     * Trừ tiền trước, đổi tag trên Telegram, lỗi thì hoàn tiền.
     */
    public static function buy(array $u, int $groupId, string $tag, bool $custom): array
    {
        $c = self::config();
        if (!$c['shop']) throw new HttpError('Cửa hàng tag đang tắt');
        if (!$u['tg_user_id']) throw new HttpError('Hãy liên kết Telegram ở trang Tài khoản trước khi mua tag');
        $group = Repo::group($groupId);
        if (!$group || !$group['active'] || !self::shopApplies($c, $groupId)) throw new HttpError('Nhóm này không bán tag');
        $m = Repo::member($groupId, $u['tg_user_id']);
        if (!$m || !in_array($m['status'], ['member', 'muted'], true)) throw new HttpError('Bạn cần đang ở trong nhóm này');
        if ($m['is_admin']) throw new HttpError('Bạn là quản trị viên nhóm: đổi danh hiệu admin trong Telegram, không cần mua tag');
        $tag = self::clean($tag);
        if ($tag === '') throw new HttpError('Nhập tag muốn mua');
        if ($custom) {
            if (!$c['shop_custom']) throw new HttpError('Admin chưa cho tự đặt tag');
            self::checkBanned($c, $tag);
            $price = (int)$c['shop_custom_price'];
        } else {
            $item = null;
            foreach ($c['shop_items'] as $it) if (mb_strtolower($it['tag']) === mb_strtolower($tag)) $item = $it;
            if (!$item) throw new HttpError('Tag này không có trong cửa hàng');
            $tag = $item['tag'];
            $price = (int)$item['price'];
        }
        if ((string)$m['tag'] === $tag && ($m['tag_source'] ?? '') === 'shop' && empty($m['tag_until'])) throw new HttpError('Bạn đang dùng tag này rồi');
        $until = $c['shop_days'] > 0 ? strtotime('+' . (int)$c['shop_days'] . ' day') : null;
        $who = Repo::name($m);
        $balance = Wallet::debit((int)$u['id'], $price, 'tag', "Mua tag \"$tag\" — {$group['title']}", ['group_id' => $groupId, 'tag' => $tag, 'until' => $until]);
        $txId = (int)DB::val("SELECT MAX(id) FROM wallet_tx WHERE user_id = ? AND type = 'tag'", [$u['id']]);
        try {
            $row = self::set($group, $u['tg_user_id'], $tag, $who . ' (mua tag)', 'shop', false, 'web', $until);
        } catch (Throwable $e) {
            DB::run("UPDATE wallet_tx SET type = 'tag_fail' WHERE id = ?", [$txId]);
            $balance = Wallet::credit((int)$u['id'], $price, 'refund', "Hoàn tiền mua tag lỗi — \"$tag\"", null, ['error' => mb_substr($e->getMessage(), 0, 200)]);
            throw new HttpError($e->getMessage() . ' · Đã hoàn lại ' . Wallet::money($price) . ' vào ví.');
        }
        if ($c['shop_notify'] && ($api = Repo::apiForGroup($group))) {
            $sent = $api->trySend($group['chat_id'], strtr($c['shop_text'], ['{mention}' => Repo::mention($row), '{name}' => tg_esc($who), '{tag}' => tg_esc($tag),
                '{price}' => Wallet::money($price), '{group}' => tg_esc($group['title']), '{until}' => $until ? ' (đến ' . date('d/m/Y', $until) . ')' : '']));
            if ($sent && !empty($sent['message_id']) && $c['notify_autodel'] > 0 && $group['bot_id']) DelQueue::add($api, (int)$group['bot_id'], $group['chat_id'], [$sent['message_id']], (int)$c['notify_autodel']);
        }
        return ['tag' => $tag, 'price' => $price, 'balance' => $balance, 'until' => $until];
    }

    /** Gỡ tag đã mua hết hạn (cron). Bật tag theo điểm thì đổi sang tag theo điểm. */
    public static function expire(int $limit = 30): int
    {
        $n = 0;
        $c = self::config();
        foreach (DB::all("SELECT m.*, g.active FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE m.tag_source = 'shop' AND m.tag_until IS NOT NULL AND m.tag_until < ? LIMIT $limit", [now()]) as $m) {
            $group = Repo::group((int)$m['group_id']);
            try {
                if (!$group || !$group['active']) throw new RuntimeException('nhóm không hoạt động');
                $next = $c['auto'] && (!$c['auto_groups'] || in_array((int)$group['id'], $c['auto_groups'], true)) ? self::levelTag($c, (int)$m['points']) : '';
                self::set($group, $m['tg_user_id'], $next, 'Bot (tag mua hết hạn)', $next !== '' ? 'auto' : 'manual', false, 'bot');
                $n++;
            } catch (Throwable $e) {
                DB::run("UPDATE tg_members SET tag_source = 'manual', tag_until = NULL WHERE id = ?", [$m['id']]); // không thử lại mãi
            }
        }
        return $n;
    }

    /** Tag thay đổi trực tiếp trong Telegram (sender_tag / chat_member) → cập nhật database */
    public static function seen(int $groupId, $tgUserId, ?string $tag, bool $fromMessage = false): void
    {
        if ($fromMessage && ($tag === null || $tag === '')) return; // tin không kèm tag: không suy ra là đã xoá tag
        $m = Repo::member($groupId, $tgUserId);
        if (!$m) return;
        $tag = $tag !== null && $tag !== '' ? mb_substr($tag, 0, 32) : null;
        if ((string)$m['tag'] === (string)$tag) return;
        // Ai đó đổi tag ngoài web → coi như đặt tay, tag tự động không ghi đè
        DB::run('UPDATE tg_members SET tag = ?, tag_source = ?, tag_until = NULL WHERE id = ?', [$tag, $tag ? 'manual' : null, $m['id']]);
    }
}

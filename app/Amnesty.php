<?php
defined('APP') or exit;

/**
 * Ân xá thành viên: trả VND từ ví để gỡ cấm chat / gỡ cấm khỏi nhóm / xoá cảnh cáo.
 * Trừ tiền trước, thực hiện trên Telegram, lỗi thì hoàn tiền tự động.
 */
class Amnesty
{
    /** loại => [hành động Moderation, nhãn, icon] */
    const KINDS = [
        'unmute' => ['unmute', 'Gỡ cấm chat', '🔊'],
        'unban' => ['unban', 'Gỡ cấm khỏi nhóm', '✅'],
        'unwarn' => ['resetwarn', 'Xoá toàn bộ cảnh cáo', '🧹'],
    ];

    /** Gói gỡ toàn bộ hình phạt trong 1 lần */
    const ALL = ['all', 'Ân xá toàn bộ', '🕊'];

    const DEFAULT_MESSAGE = "🕊 <b>ÂN XÁ THÀNH VIÊN</b>\n👤 Thành viên: {member}\n{icon} Đã được: <b>{kind}</b>\n💝 Người ân xá: {payer}\n💰 Phí ân xá: <b>{price}</b>\n🕒 Lúc: {time}";

    public static function config(): array
    {
        $g = fn($k, $d) => Settings::get($k) ?? $d;
        return [
            'enabled' => $g('amnesty_enabled', '0') === '1',
            'prices' => [
                'unmute' => (int)$g('amnesty_price_unmute', '20000'),
                'unban' => (int)$g('amnesty_price_unban', '50000'),
                'unwarn' => (int)$g('amnesty_price_unwarn', '10000'),
            ],
            'allow_others' => $g('amnesty_allow_others', '1') === '1',
            'notify' => $g('amnesty_notify', '1') === '1',
            'invite' => $g('amnesty_invite', '1') === '1',
            'message' => (string)($g('amnesty_message', '') ?: self::DEFAULT_MESSAGE),
            'note' => (string)$g('amnesty_note', ''),
            'combo' => $g('amnesty_combo', '1') === '1',
            'combo_discount' => max(0, min(90, (int)$g('amnesty_combo_discount', '10'))),
        ];
    }

    public static function saveConfig(array $in): array
    {
        if (isset($in['combo_discount'])) Settings::set('amnesty_combo_discount', (string)max(0, min(90, (int)$in['combo_discount'])));
        foreach (['enabled', 'allow_others', 'notify', 'invite', 'combo'] as $k) {
            if (array_key_exists($k, $in)) Settings::set("amnesty_$k", !empty($in[$k]) ? '1' : '0');
        }
        foreach (array_keys(self::KINDS) as $k) {
            if (isset($in['prices'][$k])) Settings::set("amnesty_price_$k", (string)max(0, min(1000000000, (int)$in['prices'][$k])));
        }
        if (isset($in['message'])) {
            $m = trim((string)$in['message']);
            Settings::set('amnesty_message', $m === self::DEFAULT_MESSAGE ? '' : mb_substr($m, 0, 2000));
        }
        if (isset($in['note'])) Settings::set('amnesty_note', mb_substr(trim((string)$in['note']), 0, 500));
        return self::config();
    }

    /** Các hình phạt đang có của 1 thành viên → [kind => mô tả] (chỉ loại đang bật giá > 0) */
    public static function punishments(array $m, ?array $cfg = null): array
    {
        $cfg ??= self::config();
        $out = [];
        $t = now();
        if ($m['status'] === 'muted' && (!$m['muted_until'] || (int)$m['muted_until'] > $t)) {
            $out['unmute'] = $m['muted_until'] ? 'Cấm chat đến ' . date('H:i d/m/Y', (int)$m['muted_until']) : 'Cấm chat vĩnh viễn';
        }
        if ($m['status'] === 'banned') $out['unban'] = 'Bị cấm khỏi nhóm';
        if ((int)$m['warns'] > 0 && $m['status'] !== 'banned') $out['unwarn'] = 'Đang có ' . (int)$m['warns'] . ' cảnh cáo';
        return array_filter($out, fn($k) => ($cfg['prices'][$k] ?? 0) > 0, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Gói "Ân xá toàn bộ": các hình phạt sẽ gỡ + giá (đã giảm). null nếu không áp dụng (dưới 2 hình phạt).
     * Người bị cấm mà còn cảnh cáo: gỡ cấm + xoá cảnh cáo.
     * @return array{parts: array<string,string>, full: int, price: int}|null
     */
    public static function combo(array $m, ?array $cfg = null): ?array
    {
        $cfg ??= self::config();
        if (!$cfg['combo']) return null;
        $parts = self::punishments($m, $cfg);
        if ($m['status'] === 'banned' && (int)$m['warns'] > 0 && $cfg['prices']['unwarn'] > 0) $parts['unwarn'] = 'Đang có ' . (int)$m['warns'] . ' cảnh cáo';
        if (count($parts) < 2) return null;
        // Thứ tự: thao tác Telegram trước (lỗi thì hoàn tiền toàn bộ), xoá cảnh cáo sau cùng
        uksort($parts, fn($a, $b) => array_search($a, ['unban', 'unmute', 'unwarn']) <=> array_search($b, ['unban', 'unmute', 'unwarn']));
        $full = array_sum(array_map(fn($k) => $cfg['prices'][$k], array_keys($parts)));
        $price = $full - intdiv($full * $cfg['combo_discount'], 100);
        if ($price >= 1000) $price = (int)(ceil($price / 1000) * 1000); // làm tròn nghìn
        return ['parts' => $parts, 'full' => $full, 'price' => min($price, $full)];
    }

    /** Nhãn gói toàn bộ, VD "Gỡ cấm chat + Xoá toàn bộ cảnh cáo" */
    public static function comboLabel(array $parts): string
    {
        return implode(' + ', array_map(fn($k) => self::KINDS[$k][1], array_keys($parts)));
    }

    /**
     * Hỏi Telegram trạng thái thật của 1 người trong nhóm (getChatMember) rồi cập nhật database.
     * Dùng cho người bị cấm trước khi có web (chưa có trong danh sách). Trả về dòng tg_members.
     */
    public static function syncTelegram(array $group, $tgUserId): ?array
    {
        $api = Repo::apiForGroup($group);
        $r = $api ? $api->tryCall('getChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $tgUserId]) : null;
        $m = Repo::member((int)$group['id'], $tgUserId);
        if (!is_array($r) || empty($r['status'])) return $m; // không hỏi được → dùng dữ liệu đang có
        $user = $r['user'] ?? ['id' => $tgUserId, 'first_name' => (string)$tgUserId];
        if (!empty($user['is_bot'])) throw new HttpError('Đây là bot, không cần ân xá');
        $until = (int)($r['until_date'] ?? 0);
        switch ($r['status']) {
            case 'kicked': $st = 'banned'; break;
            case 'restricted': $st = empty($r['can_send_messages']) ? 'muted' : (!empty($r['is_member']) ? 'member' : 'left'); break;
            case 'left': $st = 'left'; break;
            default: $st = 'member'; // member / administrator / creator
        }
        if (!$m) {
            if ($st === 'left' && !$until) return null; // chưa từng có trong nhóm, không bị phạt
            $m = Repo::upsertMember((int)$group['id'], $user, ['status' => $st]);
        } else {
            DB::update('tg_members', ['username' => $user['username'] ?? $m['username'], 'first_name' => $user['first_name'] ?? $m['first_name'], 'last_name' => $user['last_name'] ?? $m['last_name']], 'id = ?', [$m['id']]);
        }
        $upd = ['status' => $st, 'muted_until' => $st === 'muted' ? ($until ?: null) : null];
        if (in_array($r['status'], ['administrator', 'creator'], true)) $upd['is_admin'] = 1;
        DB::update('tg_members', $upd, 'id = ?', [$m['id']]);
        return DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
    }

    /** Tìm theo ID Telegram / @username trong 1 nhóm (kể cả người chưa có trong danh sách) */
    public static function lookup(array $u, array $group, string $who): array
    {
        $who = trim($who);
        if (preg_match('/^-?\d{4,15}$/', $who)) {
            $tgId = $who;
        } elseif (preg_match('/^@?([A-Za-z0-9_]{4,32})$/', $who, $mm)) {
            $tgId = DB::val('SELECT tg_user_id FROM tg_members WHERE group_id = ? AND LOWER(username) = LOWER(?)', [$group['id'], $mm[1]])
                ?: DB::val('SELECT tg_user_id FROM tg_members WHERE LOWER(username) = LOWER(?) ORDER BY last_active DESC', [$mm[1]]);
            if (!$tgId) throw new HttpError('Không tìm thấy @' . $mm[1] . '. Người bị cấm từ trước khi có web thì hãy nhập ID Telegram (dãy số).', 404);
        } else {
            throw new HttpError('Nhập ID Telegram (dãy số) hoặc @username');
        }
        $m = self::syncTelegram($group, $tgId);
        if (!$m) throw new HttpError("ID $tgId không bị cấm và chưa từng ở trong nhóm này. Kiểm tra lại ID.", 404);
        $cfg = self::config();
        if (!self::canTarget($u, $m, $cfg, true)) throw new HttpError('Admin chỉ cho phép tự ân xá cho chính mình', 403);
        return $m;
    }

    /** SQL điều kiện thành viên đang bị xử lý */
    public static function punishedSql(string $a = ''): string
    {
        $p = $a ? "$a." : '';
        return "({$p}status = 'banned' OR ({$p}status = 'muted' AND ({$p}muted_until IS NULL OR {$p}muted_until > " . now() . ")) OR {$p}warns > 0)";
    }

    /** Nhóm mà người dùng được phép ân xá người khác: nhóm họ đang tham gia (admin web: tất cả) */
    public static function groupIds(array $u): ?array
    {
        if (Access::isWebAdmin($u)) return null;
        $ids = Access::groupIds($u) ?? [];
        if ($u['tg_user_id']) {
            foreach (DB::all("SELECT group_id FROM tg_members WHERE tg_user_id = ? AND status IN ('member','muted')", [$u['tg_user_id']]) as $r) $ids[] = (int)$r['group_id'];
        }
        return array_values(array_unique($ids));
    }

    /** $byId: nhập ID trực tiếp → được ân xá ở mọi nhóm đang hoạt động (người ngoài nhóm cũng giúp được) */
    public static function canTarget(array $u, array $m, array $cfg, bool $byId = false): bool
    {
        if ($u['tg_user_id'] && (string)$u['tg_user_id'] === (string)$m['tg_user_id']) return true;
        if (!$cfg['allow_others'] && !Access::isWebAdmin($u)) return false;
        if ($byId) return true;
        $ids = self::groupIds($u);
        return $ids === null || in_array((int)$m['group_id'], $ids, true);
    }

    /**
     * Thực hiện ân xá. Trả về [member, balance, price, invite_link?]
     */
    public static function perform(array $u, int $memberId, string $kind, bool $byId = false): array
    {
        $cfg = self::config();
        if (!$cfg['enabled']) throw new HttpError('Tính năng ân xá đang tắt');
        if (!isset(self::KINDS[$kind]) && $kind !== 'all') throw new HttpError('Loại ân xá không hợp lệ');
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$memberId]) ?: throw new HttpError('Không tìm thấy thành viên', 404);
        $group = Repo::group((int)$m['group_id']);
        if (!$group || !$group['active']) throw new HttpError('Nhóm không còn hoạt động');
        if (!self::canTarget($u, $m, $cfg, $byId)) throw new HttpError('Bạn không thể ân xá thành viên này', 403);
        // Kiểm tra lại trạng thái thật trên Telegram trước khi trừ tiền (tránh trả tiền cho hình phạt đã hết)
        if ($kind !== 'unwarn') $m = self::syncTelegram($group, $m['tg_user_id']) ?: $m;

        if ($kind === 'all') {
            $combo = self::combo($m, $cfg);
            if (!$combo) throw new HttpError('Gói ân xá toàn bộ chỉ dùng khi thành viên có từ 2 hình phạt trở lên');
            $parts = $combo['parts'];
            $price = $combo['price'];
            [, , $icon] = self::ALL;
            $label = self::ALL[1];
            $kindText = self::comboLabel($parts);
        } else {
            $list = self::punishments($m, $cfg);
            if (!isset($list[$kind])) throw new HttpError('Thành viên không có hình phạt này (hoặc đã hết hạn)');
            $parts = [$kind => $list[$kind]];
            $price = $cfg['prices'][$kind];
            [, $label, $icon] = self::KINDS[$kind];
            $kindText = $label;
        }
        $self = $u['tg_user_id'] && (string)$u['tg_user_id'] === (string)$m['tg_user_id'];
        $payer = $u['display_name'] ?: $u['username'];
        $who = Repo::name($m);

        // 1. Trừ tiền 1 lần (nguyên tử, không đủ thì báo lỗi)
        $balance = Wallet::debit((int)$u['id'], $price, 'amnesty', "Ân xá: $kindText — $who ({$group['title']})",
            ['group_id' => (int)$group['id'], 'member_id' => (int)$m['id'], 'tg_user_id' => (string)$m['tg_user_id'], 'kind' => $kind, 'parts' => array_keys($parts)]);
        $txId = (int)DB::val("SELECT MAX(id) FROM wallet_tx WHERE user_id = ? AND type = 'amnesty'", [$u['id']]);

        // 2. Thực hiện từng phần; phần đầu (thao tác Telegram) lỗi → hoàn toàn bộ tiền
        $after = $m;
        $done = [];
        foreach ($parts as $k => $desc) {
            try {
                $after = Moderation::perform(self::KINDS[$k][0], $group, [
                    'tgUserId' => $m['tg_user_id'], 'actor' => $payer . ' (ân xá)', 'source' => 'amnesty', 'actorTgId' => $u['tg_user_id'],
                    'reason' => ($kind === 'all' ? 'Ân xá toàn bộ, trả phí ' : 'Ân xá trả phí ') . Wallet::money($price) . ($self ? ' (tự ân xá)' : ''), 'notify' => false,
                    'meta' => ['amnesty' => ['kind' => $kind, 'part' => $k, 'price' => $price, 'payer_id' => (int)$u['id'], 'payer' => $payer, 'self' => $self, 'before' => $desc]],
                ]);
                $done[$k] = $desc;
            } catch (Throwable $e) {
                if (!$done) {
                    DB::run("UPDATE wallet_tx SET type = 'amnesty_fail' WHERE id = ?", [$txId]); // không tính vào doanh thu ân xá
                    $balance = Wallet::credit((int)$u['id'], $price, 'refund', "Hoàn tiền ân xá thất bại — $who", null, ['error' => mb_substr($e->getMessage(), 0, 200)]);
                    throw new HttpError('Không thực hiện được trên Telegram (' . $e->getMessage() . '). Đã hoàn lại ' . Wallet::money($price) . ' vào ví.');
                }
                // Phần sau lỗi (hiếm): hoàn tiền phần chưa làm theo giá lẻ
                $back = min($price, (int)$cfg['prices'][$k]);
                if ($back > 0) $balance = Wallet::credit((int)$u['id'], $back, 'refund', 'Hoàn tiền phần ân xá lỗi: ' . self::KINDS[$k][1] . " — $who");
            }
        }
        if ($kind === 'all') $kindText = self::comboLabel($done);

        $api = Repo::apiForGroup($group);
        // 3. Gỡ cấm → tạo link mời dùng 1 lần cho thành viên
        $invite = null;
        if (isset($done['unban']) && $cfg['invite'] && $api) {
            $r = $api->tryCall('createChatInviteLink', ['chat_id' => $group['chat_id'], 'name' => 'Ân xá ' . mb_substr($who, 0, 20), 'member_limit' => 1, 'expire_date' => now() + 86400 * 3]);
            $invite = $r['invite_link'] ?? ($group['username'] ? 'https://t.me/' . $group['username'] : null);
        }
        // 4. Thông báo
        $lines = implode("\n", array_map(fn($k) => self::KINDS[$k][2] . ' ' . self::KINDS[$k][1], array_keys($done)));
        if ($api) {
            if ($cfg['notify']) {
                $vars = [
                    '{member}' => Repo::mention($m), '{member_name}' => tg_esc($who), '{member_id}' => (string)$m['tg_user_id'],
                    '{kind}' => $kindText, '{icon}' => $icon, '{before}' => tg_esc(implode(', ', $done)),
                    '{payer}' => $self ? tg_esc($payer) . ' (tự ân xá)' : tg_esc($payer), '{price}' => Wallet::money($price),
                    '{group}' => tg_esc($group['title']), '{time}' => date('H:i d/m/Y'),
                ];
                $api->trySend($group['chat_id'], strtr($cfg['message'], $vars));
            }
            // Nhắn riêng cho người được ân xá (nếu họ đã mở bot)
            $dm = "🕊 <b>Bạn đã được ân xá</b> trong nhóm <b>" . tg_esc($group['title']) . "</b>\n$lines" . ($self ? '' : "\n💝 Người ân xá: " . tg_esc($payer));
            if ($invite) $dm .= "\n\n🔗 Link vào lại nhóm (dùng 1 lần, hạn 3 ngày):\n$invite";
            $api->trySend($m['tg_user_id'], $dm);
            if (!$self && $u['tg_user_id']) {
                $api->trySend($u['tg_user_id'], "🕊 Bạn đã ân xá <b>" . tg_esc($who) . "</b> ($kindText) — " . Wallet::money($price) . "\n💳 Số dư còn: <b>" . Wallet::money($balance) . '</b>');
            }
        }
        return ['member' => $after, 'balance' => $balance, 'price' => $price, 'invite_link' => $invite, 'label' => $kindText];
    }
}

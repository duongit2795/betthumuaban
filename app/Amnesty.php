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
        ];
    }

    public static function saveConfig(array $in): array
    {
        foreach (['enabled', 'allow_others', 'notify', 'invite'] as $k) {
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

    public static function canTarget(array $u, array $m, array $cfg): bool
    {
        if ($u['tg_user_id'] && (string)$u['tg_user_id'] === (string)$m['tg_user_id']) return true;
        if (!$cfg['allow_others'] && !Access::isWebAdmin($u)) return false;
        $ids = self::groupIds($u);
        return $ids === null || in_array((int)$m['group_id'], $ids, true);
    }

    /**
     * Thực hiện ân xá. Trả về [member, balance, price, invite_link?]
     */
    public static function perform(array $u, int $memberId, string $kind): array
    {
        $cfg = self::config();
        if (!$cfg['enabled']) throw new HttpError('Tính năng ân xá đang tắt');
        if (!isset(self::KINDS[$kind])) throw new HttpError('Loại ân xá không hợp lệ');
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$memberId]) ?: throw new HttpError('Không tìm thấy thành viên', 404);
        $group = Repo::group((int)$m['group_id']);
        if (!$group || !$group['active']) throw new HttpError('Nhóm không còn hoạt động');
        if (!self::canTarget($u, $m, $cfg)) throw new HttpError('Bạn không thể ân xá thành viên này', 403);
        $list = self::punishments($m, $cfg);
        if (!isset($list[$kind])) throw new HttpError('Thành viên không có hình phạt này (hoặc đã hết hạn)');
        $price = $cfg['prices'][$kind];
        [$action, $label, $icon] = self::KINDS[$kind];
        $self = $u['tg_user_id'] && (string)$u['tg_user_id'] === (string)$m['tg_user_id'];
        $payer = $u['display_name'] ?: $u['username'];
        $who = Repo::name($m);

        // 1. Trừ tiền (nguyên tử, không đủ thì báo lỗi)
        $balance = Wallet::debit((int)$u['id'], $price, 'amnesty', "Ân xá: $label — $who ({$group['title']})",
            ['group_id' => (int)$group['id'], 'member_id' => (int)$m['id'], 'tg_user_id' => (string)$m['tg_user_id'], 'kind' => $kind]);
        $txId = (int)DB::val("SELECT MAX(id) FROM wallet_tx WHERE user_id = ? AND type = 'amnesty'", [$u['id']]);

        // 2. Thực hiện trên Telegram, lỗi → hoàn tiền
        try {
            $after = Moderation::perform($action, $group, [
                'tgUserId' => $m['tg_user_id'], 'actor' => $payer . ' (ân xá)', 'source' => 'amnesty', 'actorTgId' => $u['tg_user_id'],
                'reason' => 'Ân xá trả phí ' . Wallet::money($price) . ($self ? ' (tự ân xá)' : ''), 'notify' => false,
                'meta' => ['amnesty' => ['kind' => $kind, 'price' => $price, 'payer_id' => (int)$u['id'], 'payer' => $payer, 'self' => $self, 'before' => $list[$kind]]],
            ]);
        } catch (Throwable $e) {
            DB::run("UPDATE wallet_tx SET type = 'amnesty_fail' WHERE id = ?", [$txId]); // không tính vào doanh thu ân xá
            $balance = Wallet::credit((int)$u['id'], $price, 'refund', "Hoàn tiền ân xá thất bại — $who", null, ['error' => mb_substr($e->getMessage(), 0, 200)]);
            throw new HttpError('Không thực hiện được trên Telegram (' . $e->getMessage() . '). Đã hoàn lại ' . Wallet::money($price) . ' vào ví.');
        }

        $api = Repo::apiForGroup($group);
        // 3. Gỡ cấm → tạo link mời dùng 1 lần cho thành viên
        $invite = null;
        if ($kind === 'unban' && $cfg['invite'] && $api) {
            $r = $api->tryCall('createChatInviteLink', ['chat_id' => $group['chat_id'], 'name' => 'Ân xá ' . mb_substr($who, 0, 20), 'member_limit' => 1, 'expire_date' => now() + 86400 * 3]);
            $invite = $r['invite_link'] ?? ($group['username'] ? 'https://t.me/' . $group['username'] : null);
        }
        // 4. Thông báo
        if ($api) {
            if ($cfg['notify']) {
                $vars = [
                    '{member}' => Repo::mention($m), '{member_name}' => tg_esc($who), '{member_id}' => (string)$m['tg_user_id'],
                    '{kind}' => $label, '{icon}' => $icon, '{before}' => tg_esc($list[$kind]),
                    '{payer}' => $self ? tg_esc($payer) . ' (tự ân xá)' : tg_esc($payer), '{price}' => Wallet::money($price),
                    '{group}' => tg_esc($group['title']), '{time}' => date('H:i d/m/Y'),
                ];
                $api->trySend($group['chat_id'], strtr($cfg['message'], $vars));
            }
            // Nhắn riêng cho người được ân xá (nếu họ đã mở bot)
            $dm = "🕊 <b>Bạn đã được ân xá</b> trong nhóm <b>" . tg_esc($group['title']) . "</b>\n$icon $label" . ($self ? '' : "\n💝 Người ân xá: " . tg_esc($payer));
            if ($invite) $dm .= "\n\n🔗 Link vào lại nhóm (dùng 1 lần, hạn 3 ngày):\n$invite";
            $api->trySend($m['tg_user_id'], $dm);
            if (!$self && $u['tg_user_id']) {
                $api->trySend($u['tg_user_id'], "🕊 Bạn đã ân xá <b>" . tg_esc($who) . "</b> ($label) — " . Wallet::money($price) . "\n💳 Số dư còn: <b>" . Wallet::money($balance) . '</b>');
            }
        }
        return ['member' => $after, 'balance' => $balance, 'price' => $price, 'invite_link' => $invite, 'label' => $label];
    }
}

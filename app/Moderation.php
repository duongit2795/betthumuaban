<?php
defined('APP') or exit;

/** Hành động quản trị dùng chung cho web, lệnh bot, bộ lọc tự động và lệnh tự tạo. */
class Moderation
{
    const ACTIONS = ['ban', 'unban', 'kick', 'mute', 'unmute', 'warn', 'unwarn', 'resetwarn', 'points', 'setpoints'];

    /** Nguồn xử lý → nhãn hiển thị */
    const SOURCES = [
        'telegram' => 'qua Telegram',
        'web' => 'qua Web',
        'bot' => 'Bot tự động',
        'rule' => 'Lệnh tự tạo',
        'auto' => 'Tự động (đủ cảnh cáo)',
        'amnesty' => 'Ân xá (trả phí)',
    ];

    const TITLES = [
        'ban' => ['⛔', 'CẤM KHỎI NHÓM'],
        'unban' => ['✅', 'GỠ CẤM'],
        'kick' => ['👢', 'ĐUỔI KHỎI NHÓM'],
        'mute' => ['🔇', 'CẤM CHAT'],
        'unmute' => ['🔊', 'BỎ CẤM CHAT'],
        'warn' => ['⚠️', 'CẢNH CÁO'],
        'unwarn' => ['↩️', 'GỠ 1 CẢNH CÁO'],
        'resetwarn' => ['🧹', 'XOÁ TOÀN BỘ CẢNH CÁO'],
        'points' => ['⭐', 'ĐIỂM'],
        'setpoints' => ['⭐', 'ĐẶT ĐIỂM'],
    ];

    private static function api(array $group): Telegram
    {
        $api = Repo::apiForGroup($group);
        if (!$api) throw new HttpError('Nhóm này chưa có bot hoạt động');
        return $api;
    }

    /** Lấy bằng chứng từ một tin nhắn Telegram (tin vi phạm / tin bị reply) */
    public static function evidence(?array $msg): ?array
    {
        if (!$msg || empty($msg['message_id'])) return null;
        [$kind, $text, $label] = ChatLog::describe($msg);
        $from = $msg['from'] ?? null;
        return [
            'message_id' => (int)$msg['message_id'],
            'text' => mb_substr($text, 0, 1500),
            'media' => $label ?: null,
            'kind' => $kind,
            'date' => (int)($msg['date'] ?? now()),
            'from' => $from ? trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) : null,
        ];
    }

    /** Bằng chứng từ tin đã lưu trong tab Chat */
    public static function evidenceFromChat(array $row): array
    {
        return [
            'message_id' => (int)$row['message_id'],
            'text' => mb_substr((string)$row['text'], 0, 1500),
            'media' => $row['media'],
            'kind' => $row['kind'],
            'date' => (int)$row['created_at'],
            'from' => $row['name'],
        ];
    }

    /** Link mở tin nhắn trong Telegram */
    public static function link(array $group, ?int $messageId): ?string
    {
        if (!$messageId) return null;
        if (!empty($group['username'])) return 'https://t.me/' . $group['username'] . '/' . $messageId;
        $id = (string)$group['chat_id'];
        if (str_starts_with($id, '-100')) return 'https://t.me/c/' . substr($id, 4) . '/' . $messageId;
        return null;
    }

    /**
     * @param array $o tgUserId, actor (tên người xử lý), source (telegram|web|bot|rule|auto), actorTgId,
     *                 reason, minutes, amount, notify, evidence (mảng từ evidence()), escalatedFrom
     */
    public static function perform(string $action, array $group, array $o): array
    {
        $tgUserId = $o['tgUserId'];
        $actor = $o['actor'] ?? 'system';
        $source = $o['source'] ?? 'web';
        $reason = trim((string)($o['reason'] ?? ''));
        $notify = $o['notify'] ?? true;
        $evidence = $o['evidence'] ?? null;
        $m = Repo::member((int)$group['id'], $tgUserId) ?: Repo::upsertMember((int)$group['id'], ['id' => $tgUserId, 'first_name' => (string)$tgUserId]);
        $s = Repo::settings($group);
        $t = now();
        $chat = $group['chat_id'];
        $meta = ['source' => $source, 'actor_tg_id' => $o['actorTgId'] ?? null];
        if (!empty($o['escalatedFrom'])) $meta['escalated_from'] = $o['escalatedFrom'];
        if (!empty($o['meta']) && is_array($o['meta'])) $meta += $o['meta'];
        $facts = []; // các dòng chi tiết cho thông báo: [icon, nhãn, giá trị HTML]
        $short = '';  // tóm tắt cho nhật ký

        switch ($action) {
            case 'ban':
                self::api($group)->call('banChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId]);
                DB::run("UPDATE tg_members SET status = 'banned', muted_until = NULL WHERE id = ?", [$m['id']]);
                $facts[] = ['⏱', 'Thời hạn', 'Vĩnh viễn'];
                break;
            case 'unban':
                self::api($group)->call('unbanChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId, 'only_if_banned' => true]);
                DB::run("UPDATE tg_members SET status = 'left', warns = 0 WHERE id = ?", [$m['id']]);
                $facts[] = ['ℹ️', 'Ghi chú', 'Có thể vào lại nhóm bằng link mời, cảnh cáo đã được đặt về 0'];
                break;
            case 'kick':
                $api = self::api($group);
                $api->call('banChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId, 'until_date' => $t + 40]);
                $api->tryCall('unbanChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId, 'only_if_banned' => true]);
                DB::run("UPDATE tg_members SET status = 'left' WHERE id = ?", [$m['id']]);
                $facts[] = ['ℹ️', 'Ghi chú', 'Có thể vào lại nhóm bằng link mời'];
                break;
            case 'mute':
                $mins = max(0, (int)($o['minutes'] ?? $s['mute_minutes']));
                $until = $mins > 0 ? $t + max($mins * 60, 60) : 0;
                self::api($group)->call('restrictChatMember', [
                    'chat_id' => $chat, 'user_id' => $tgUserId, 'permissions' => Telegram::PERM_NONE,
                    'use_independent_chat_permissions' => true, 'until_date' => $until,
                ]);
                DB::run("UPDATE tg_members SET status = 'muted', muted_until = ? WHERE id = ?", [$until ?: null, $m['id']]);
                $dur = $mins > 0 ? self::duration($mins) : 'vĩnh viễn';
                $facts[] = ['⏱', 'Thời hạn', $mins > 0 ? "$dur (đến " . date('H:i d/m/Y', $until) . ')' : 'Vĩnh viễn'];
                $meta += ['minutes' => $mins, 'until' => $until ?: null];
                $short = $dur;
                break;
            case 'unmute':
                $was = $m['muted_until'] ? (int)$m['muted_until'] : null;
                self::api($group)->call('restrictChatMember', [
                    'chat_id' => $chat, 'user_id' => $tgUserId, 'permissions' => Telegram::permAll(),
                    'use_independent_chat_permissions' => true,
                ]);
                DB::run("UPDATE tg_members SET status = 'member', muted_until = NULL WHERE id = ?", [$m['id']]);
                if ($was && $was > $t) $facts[] = ['⏱', 'Hạn cấm cũ', 'đến ' . date('H:i d/m/Y', $was) . ' (gỡ sớm)'];
                $meta['previous_until'] = $was;
                break;
            case 'warn':
                $warns = (int)$m['warns'] + 1;
                $limit = (int)$s['warn_limit'];
                $sub = in_array($s['warn_action'], ['ban', 'kick'], true) ? $s['warn_action'] : 'mute';
                $subLabel = ['ban' => 'cấm khỏi nhóm', 'kick' => 'đuổi khỏi nhóm', 'mute' => 'cấm chat ' . ($s['mute_minutes'] ? self::duration((int)$s['mute_minutes']) : 'vĩnh viễn')][$sub];
                if ($limit > 0 && $warns >= $limit) {
                    // Đủ cảnh cáo → ghi nhận lần cảnh cáo này rồi chuyển sang xử lý tự động (1 thông báo duy nhất)
                    DB::run('UPDATE tg_members SET warns = 0 WHERE id = ?', [$m['id']]);
                    Repo::log((int)$group['id'], $actor, 'warn', $m, "$warns/$limit" . ($reason !== '' ? " · $reason" : ''),
                        $meta + ['warns' => $warns, 'limit' => $limit, 'reason' => $reason, 'escalate_to' => $sub, 'link' => self::link($group, $evidence['message_id'] ?? null)], $evidence);
                    return self::perform($sub, $group, [
                        'tgUserId' => $tgUserId, 'actor' => $actor, 'actorTgId' => $o['actorTgId'] ?? null, 'source' => $source,
                        'reason' => "Đủ $warns/$limit cảnh cáo" . ($reason !== '' ? " — lần cuối: $reason" : ''),
                        'notify' => $notify, 'evidence' => $evidence, 'escalatedFrom' => 'warn',
                        'minutes' => $sub === 'mute' ? (int)$s['mute_minutes'] : null,
                    ]);
                }
                DB::run('UPDATE tg_members SET warns = ? WHERE id = ?', [$warns, $m['id']]);
                $facts[] = ['⚠️', 'Cảnh cáo', "<b>$warns/" . ($limit ?: '∞') . '</b>' . ($limit > 0 ? ' · còn ' . ($limit - $warns) . " lần nữa sẽ bị $subLabel" : '')];
                $meta += ['warns' => $warns, 'limit' => $limit];
                $short = "$warns/$limit";
                break;
            case 'unwarn':
                $warns = max(0, (int)$m['warns'] - 1);
                DB::run('UPDATE tg_members SET warns = ? WHERE id = ?', [$warns, $m['id']]);
                $facts[] = ['⚠️', 'Cảnh cáo còn', "$warns/{$s['warn_limit']}"];
                $meta += ['warns' => $warns, 'limit' => (int)$s['warn_limit']];
                $short = "$warns/{$s['warn_limit']}";
                break;
            case 'resetwarn':
                $meta['previous_warns'] = (int)$m['warns'];
                DB::run('UPDATE tg_members SET warns = 0 WHERE id = ?', [$m['id']]);
                $facts[] = ['⚠️', 'Cảnh cáo', "{$m['warns']} → 0"];
                break;
            case 'points':
                $n = (int)round((float)($o['amount'] ?? 0));
                if ($n === 0) throw new HttpError('Số điểm không hợp lệ');
                $newPts = max(0, (int)$m['points'] + $n);
                DB::run('UPDATE tg_members SET points = ? WHERE id = ?', [$newPts, $m['id']]);
                $facts[] = ['⭐', $n > 0 ? 'Được cộng' : 'Bị trừ', '<b>' . ($n > 0 ? '+' : '') . $n . " điểm</b> ({$m['points']} → $newPts)"];
                $meta += ['amount' => $n, 'before' => (int)$m['points'], 'after' => $newPts];
                $short = ($n > 0 ? '+' : '') . $n;
                break;
            case 'setpoints':
                $n = max(0, (int)($o['amount'] ?? 0));
                DB::run('UPDATE tg_members SET points = ?, points_spent = 0 WHERE id = ?', [$n, $m['id']]);
                $meta += ['before' => (int)$m['points'], 'after' => $n];
                $short = "{$m['points']} → $n";
                $notify = false;
                break;
            default:
                throw new HttpError('Hành động không hợp lệ');
        }

        if (in_array($action, ['points', 'setpoints'], true)) MemberTag::auto($group, $tgUserId);
        if ($reason !== '') $meta['reason'] = $reason;
        $link = self::link($group, $evidence['message_id'] ?? null);
        if ($link) $meta['link'] = $link;
        $detail = trim($short . ($short !== '' && $reason !== '' ? ' · ' : '') . $reason);
        Repo::log((int)$group['id'], $actor, $action, $m, $detail !== '' ? $detail : null, $meta, $evidence);

        // Tự xoá tin vi phạm khi cấm / cấm chat / đuổi (tuỳ chọn của nhóm)
        if ($evidence && !empty($s['mod_delete_evidence']) && in_array($action, ['ban', 'mute', 'kick'], true)) {
            self::api($group)->tryCall('deleteMessage', ['chat_id' => $chat, 'message_id' => $evidence['message_id']]);
        }

        if ($notify && $s['mod_notify']) {
            self::api($group)->trySend($chat, self::notice($action, $group, $s, $m, $facts, $reason, $evidence, $actor, $source));
        }
        return DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
    }

    /** Soạn thông báo gửi vào nhóm (chi tiết hoặc gọn theo cài đặt nhóm) */
    private static function notice(string $action, array $group, array $s, array $m, array $facts, string $reason, ?array $ev, string $actor, string $source): string
    {
        [$ico, $title] = self::TITLES[$action] ?? ['ℹ️', strtoupper($action)];
        $mention = Repo::mention($m);
        $by = tg_esc(preg_replace('/\s*\((Telegram|Web|tự động|lệnh tự tạo|ân xá)\)$/u', '', $actor)) . ' · ' . (self::SOURCES[$source] ?? $source);

        if (empty($s['mod_notify_detail'])) {
            $line = "$ico $mention — " . mb_strtolower($title);
            foreach ($facts as [, $label, $val]) if (in_array($label, ['Thời hạn', 'Cảnh cáo', 'Được cộng', 'Bị trừ'], true)) $line .= " · $val";
            if ($reason !== '') $line .= "\n📝 " . tg_esc($reason);
            if (!empty($s['mod_show_admin'])) $line .= "\n👮 " . $by;
            return $line;
        }

        $out = ["$ico <b>$title</b>", "👤 Thành viên: $mention" . (!empty($m['username']) ? ' (@' . tg_esc($m['username']) . ')' : '') . " · <code>{$m['tg_user_id']}</code>"];
        foreach ($facts as [$i, $label, $val]) $out[] = "$i $label: $val";
        if ($reason !== '') $out[] = '📝 Lý do: ' . tg_esc($reason);
        if ($ev && !empty($s['mod_show_evidence']) && in_array($action, ['warn', 'mute', 'kick', 'ban'], true)) {
            $txt = trim((string)$ev['text']);
            $media = $ev['media'] && $ev['kind'] !== 'text' ? $ev['media'] . ' ' : '';
            $snippet = mb_strlen($txt) > 300 ? mb_substr($txt, 0, 300) . '…' : $txt;
            $out[] = '💬 Nội dung vi phạm' . ($ev['date'] ? ' (' . date('H:i d/m', (int)$ev['date']) . ')' : '') . ":\n<blockquote>" . tg_esc($media . $snippet) . '</blockquote>';
        }
        if (!empty($s['mod_show_admin'])) $out[] = '👮 Xử lý bởi: ' . $by;
        $out[] = '🕒 Lúc: ' . date('H:i d/m/Y');
        return implode("\n", $out);
    }

    public static function duration(int $mins): string
    {
        if ($mins % 1440 === 0) return ($mins / 1440) . ' ngày';
        if ($mins % 60 === 0) return ($mins / 60) . ' giờ';
        return $mins . ' phút';
    }

    /** Phân tích "30", "30m", "2h", "1d" thành phút */
    public static function parseMinutes(?string $s): ?int
    {
        if ($s === null || $s === '') return null;
        if (!preg_match('/^(\d+)\s*([mhd]|p|ph|phút|giờ|g|ngày|n)?$/iu', trim($s), $mm)) return null;
        $n = (int)$mm[1];
        $u = mb_strtolower($mm[2] ?? 'm');
        if (in_array($u, ['h', 'giờ', 'g'], true)) return $n * 60;
        if (in_array($u, ['d', 'ngày', 'n'], true)) return $n * 1440;
        return $n;
    }
}

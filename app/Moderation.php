<?php
defined('APP') or exit;

/** Hành động quản trị dùng chung cho web và lệnh bot. */
class Moderation
{
    const ACTIONS = ['ban', 'unban', 'kick', 'mute', 'unmute', 'warn', 'unwarn', 'resetwarn', 'points', 'setpoints'];

    private static function api(array $group): Telegram
    {
        $api = Repo::apiForGroup($group);
        if (!$api) throw new HttpError('Nhóm này chưa có bot hoạt động');
        return $api;
    }

    /**
     * @param array $o tgUserId, actor, reason, minutes, amount, notify
     */
    public static function perform(string $action, array $group, array $o): array
    {
        $tgUserId = $o['tgUserId'];
        $actor = $o['actor'] ?? 'system';
        $reason = trim((string)($o['reason'] ?? ''));
        $notify = $o['notify'] ?? true;
        $m = Repo::member((int)$group['id'], $tgUserId) ?: Repo::upsertMember((int)$group['id'], ['id' => $tgUserId, 'first_name' => (string)$tgUserId]);
        $s = Repo::settings($group);
        $t = now();
        $why = $reason !== '' ? "\nLý do: " . tg_esc($reason) : '';
        $detail = $reason;
        $msg = '';
        $mention = Repo::mention($m);
        $chat = $group['chat_id'];

        switch ($action) {
            case 'ban':
                self::api($group)->call('banChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId]);
                DB::run("UPDATE tg_members SET status = 'banned', muted_until = NULL WHERE id = ?", [$m['id']]);
                $msg = "⛔ $mention đã bị cấm khỏi nhóm.$why";
                break;
            case 'unban':
                self::api($group)->call('unbanChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId, 'only_if_banned' => true]);
                DB::run("UPDATE tg_members SET status = 'left', warns = 0 WHERE id = ?", [$m['id']]);
                $msg = "✅ $mention đã được gỡ cấm.";
                break;
            case 'kick':
                $api = self::api($group);
                $api->call('banChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId, 'until_date' => $t + 40]);
                $api->tryCall('unbanChatMember', ['chat_id' => $chat, 'user_id' => $tgUserId, 'only_if_banned' => true]);
                DB::run("UPDATE tg_members SET status = 'left' WHERE id = ?", [$m['id']]);
                $msg = "👢 $mention đã bị đuổi khỏi nhóm.$why";
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
                $detail = $dur . ($reason !== '' ? " · $reason" : '');
                $msg = "🔇 $mention bị cấm chat $dur.$why";
                break;
            case 'unmute':
                self::api($group)->call('restrictChatMember', [
                    'chat_id' => $chat, 'user_id' => $tgUserId, 'permissions' => Telegram::permAll(),
                    'use_independent_chat_permissions' => true,
                ]);
                DB::run("UPDATE tg_members SET status = 'member', muted_until = NULL WHERE id = ?", [$m['id']]);
                $msg = "🔊 $mention đã được bỏ cấm chat.";
                break;
            case 'warn':
                $warns = (int)$m['warns'] + 1;
                $limit = (int)$s['warn_limit'];
                if ($limit > 0 && $warns >= $limit) {
                    DB::run('UPDATE tg_members SET warns = 0 WHERE id = ?', [$m['id']]);
                    Repo::log((int)$group['id'], $actor, 'warn', $m, "$warns/$limit" . ($reason !== '' ? " · $reason" : ''));
                    if ($notify) self::api($group)->trySend($chat, "⚠️ $mention đã đạt $warns/$limit cảnh cáo.$why");
                    $sub = in_array($s['warn_action'], ['ban', 'kick'], true) ? $s['warn_action'] : 'mute';
                    return self::perform($sub, $group, [
                        'tgUserId' => $tgUserId, 'actor' => $actor . ' (tự động)', 'reason' => 'Vượt giới hạn cảnh cáo', 'notify' => $notify,
                    ]);
                }
                DB::run('UPDATE tg_members SET warns = ? WHERE id = ?', [$warns, $m['id']]);
                $detail = "$warns/$limit" . ($reason !== '' ? " · $reason" : '');
                $msg = "⚠️ $mention bị cảnh cáo ($warns/$limit).$why";
                break;
            case 'unwarn':
                $warns = max(0, (int)$m['warns'] - 1);
                DB::run('UPDATE tg_members SET warns = ? WHERE id = ?', [$warns, $m['id']]);
                $detail = "$warns/{$s['warn_limit']}";
                $msg = "✅ Đã gỡ 1 cảnh cáo cho $mention ($warns/{$s['warn_limit']}).";
                break;
            case 'resetwarn':
                DB::run('UPDATE tg_members SET warns = 0 WHERE id = ?', [$m['id']]);
                $msg = "✅ Đã xoá toàn bộ cảnh cáo của $mention.";
                break;
            case 'points':
                $n = (int)round((float)($o['amount'] ?? 0));
                if ($n === 0) throw new HttpError('Số điểm không hợp lệ');
                $newPts = max(0, (int)$m['points'] + $n);
                DB::run('UPDATE tg_members SET points = ? WHERE id = ?', [$newPts, $m['id']]);
                $detail = ($n > 0 ? '+' : '') . $n . ($reason !== '' ? " · $reason" : '');
                $msg = ($n > 0 ? '🎁' : '➖') . " $mention " . ($n > 0 ? 'được cộng' : 'bị trừ') . ' ' . abs($n) . " điểm (tổng: $newPts).$why";
                break;
            case 'setpoints':
                $n = max(0, (int)($o['amount'] ?? 0));
                DB::run('UPDATE tg_members SET points = ? WHERE id = ?', [$n, $m['id']]);
                $detail = (string)$n;
                break;
            default:
                throw new HttpError('Hành động không hợp lệ');
        }

        Repo::log((int)$group['id'], $actor, $action, $m, $detail !== '' ? $detail : null);
        if ($notify && $msg !== '') self::api($group)->trySend($chat, $msg);
        return DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
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

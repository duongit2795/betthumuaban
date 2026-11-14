<?php
defined('APP') or exit;

/** Truy cập dữ liệu nhóm, thành viên, nhật ký. */
class Repo
{
    const GROUP_DEFAULTS = [
        'welcome_enabled' => true,
        'welcome_text' => "👋 Chào mừng {mention} đến với <b>{group}</b>!\nGõ /rules để xem nội quy nhóm.",
        'goodbye_enabled' => false,
        'goodbye_text' => '👋 {name} đã rời nhóm.',
        'rules_text' => "1. Tôn trọng mọi người.\n2. Không spam, quảng cáo.\n3. Không gửi nội dung phản cảm.",
        'delete_service_messages' => false,

        'anti_link' => false,
        'link_whitelist' => [],
        'anti_forward' => false,
        'anti_flood' => true,
        'flood_limit' => 6,
        'flood_seconds' => 8,
        'banned_words' => [],
        'lock_photo' => false,
        'lock_video' => false,
        'lock_sticker' => false,
        'lock_gif' => false,
        'lock_document' => false,
        'lock_voice' => false,
        'lock_poll' => false,
        'violation_warn' => true,

        'mod_notify' => true,
        'mod_notify_detail' => true,
        'mod_show_admin' => true,
        'mod_show_evidence' => true,
        'mod_delete_evidence' => false,

        'warn_limit' => 3,
        'warn_action' => 'mute',
        'mute_minutes' => 60,

        'points_enabled' => true,
        'points_per_message' => 1,
        'points_cooldown' => 10,
        'points_min_length' => 3,
        'points_per_join' => 0,

        'commands_enabled' => true,
        'member_commands' => true,
        'admins_exempt' => true,
    ];

    public static function settings(array $group): array
    {
        $s = json_decode($group['settings'] ?? '{}', true) ?: [];
        return array_merge(self::GROUP_DEFAULTS, $s);
    }

    public static function sanitizeSettings(array $in): array
    {
        $out = [];
        foreach (self::GROUP_DEFAULTS as $k => $def) {
            if (!array_key_exists($k, $in)) continue;
            $v = $in[$k];
            if (is_bool($def)) $out[$k] = (bool)$v;
            elseif (is_int($def)) $out[$k] = max(0, min(100000, (int)$v));
            elseif (is_array($def)) {
                $arr = is_array($v) ? $v : preg_split('/[\n,]/', (string)$v);
                $arr = array_values(array_filter(array_map(fn($x) => trim((string)$x), $arr), 'strlen'));
                $out[$k] = array_slice($arr, 0, 500);
            } else $out[$k] = mb_substr((string)$v, 0, 4000);
        }
        if (isset($out['warn_action']) && !in_array($out['warn_action'], ['mute', 'kick', 'ban'], true)) $out['warn_action'] = 'mute';
        return $out;
    }

    public static function saveSettings(array $group, array $patch): array
    {
        $s = array_merge(json_decode($group['settings'] ?? '{}', true) ?: [], self::sanitizeSettings($patch));
        DB::run('UPDATE tg_groups SET settings = ?, updated_at = ? WHERE id = ?', [json_encode($s, JSON_UNESCAPED_UNICODE), now(), $group['id']]);
        return array_merge(self::GROUP_DEFAULTS, $s);
    }

    public static function group(int $id): ?array
    {
        return DB::get('SELECT * FROM tg_groups WHERE id = ?', [$id]);
    }

    public static function upsertGroup(array $chat, ?int $botId): array
    {
        $t = now();
        $g = DB::get('SELECT * FROM tg_groups WHERE chat_id = ?', [$chat['id']]);
        if ($g) {
            DB::run('UPDATE tg_groups SET title = ?, type = ?, username = ?, bot_id = COALESCE(?, bot_id), active = 1, updated_at = ? WHERE id = ?', [
                $chat['title'] ?? $g['title'], $chat['type'] ?? $g['type'], $chat['username'] ?? null, $botId, $t, $g['id'],
            ]);
            return self::group((int)$g['id']);
        }
        $id = DB::insert('tg_groups', [
            'chat_id' => $chat['id'],
            'bot_id' => $botId,
            'title' => $chat['title'] ?? (string)$chat['id'],
            'type' => $chat['type'] ?? 'group',
            'username' => $chat['username'] ?? null,
            'settings' => '{}',
            'created_at' => $t,
            'updated_at' => $t,
        ]);
        return self::group($id);
    }

    public static function member(int $groupId, $tgUserId): ?array
    {
        return DB::get('SELECT * FROM tg_members WHERE group_id = ? AND tg_user_id = ?', [$groupId, $tgUserId]);
    }

    public static function upsertMember(int $groupId, array $user, array $extra = []): array
    {
        $t = now();
        $m = self::member($groupId, $user['id']);
        if ($m) {
            $upd = [
                'username' => $user['username'] ?? null,
                'first_name' => $user['first_name'] ?? $m['first_name'],
                'last_name' => $user['last_name'] ?? null,
            ];
            if (!empty($extra['status']) && $m['status'] !== 'banned') $upd['status'] = $extra['status'];
            if (!empty($extra['joined'])) $upd['joined_at'] = $t;
            DB::update('tg_members', $upd, 'id = ?', [$m['id']]);
            return DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]);
        }
        DB::insert('tg_members', [
            'group_id' => $groupId,
            'tg_user_id' => $user['id'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? null,
            'is_bot' => !empty($user['is_bot']) ? 1 : 0,
            'status' => $extra['status'] ?? 'member',
            'joined_at' => $t,
            'last_active' => null,
        ]);
        return self::member($groupId, $user['id']);
    }

    public static function name(?array $m): string
    {
        if (!$m) return '';
        $n = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
        if ($n !== '') return $n;
        if (!empty($m['username'])) return '@' . $m['username'];
        return (string)($m['tg_user_id'] ?? $m['id'] ?? '');
    }

    public static function mention(array $m): string
    {
        return '<a href="tg://user?id=' . $m['tg_user_id'] . '">' . tg_esc(self::name($m)) . '</a>';
    }

    public static function stat(int $groupId, string $field, int $n = 1): void
    {
        if (!in_array($field, ['messages', 'joins', 'leaves'], true)) return;
        $day = date('Y-m-d');
        if (!DB::run("UPDATE daily_stats SET $field = $field + ? WHERE group_id = ? AND day = ?", [$n, $groupId, $day])) {
            try {
                DB::insert('daily_stats', ['group_id' => $groupId, 'day' => $day, $field => $n]);
            } catch (PDOException $e) {
                DB::run("UPDATE daily_stats SET $field = $field + ? WHERE group_id = ? AND day = ?", [$n, $groupId, $day]);
            }
        }
    }

    /**
     * Ghi nhật ký. $meta: chi tiết (nguồn, thời hạn, cảnh cáo, link…); $evidence: nội dung tin vi phạm.
     */
    public static function log(?int $groupId, string $actor, string $action, ?array $target = null, ?string $detail = null, ?array $meta = null, ?array $evidence = null): void
    {
        $meta = $meta ? array_filter($meta, fn($v) => $v !== null && $v !== '') : null;
        DB::insert('app_logs', [
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'evidence' => $evidence ? json_encode($evidence, JSON_UNESCAPED_UNICODE) : null,
            'group_id' => $groupId,
            'actor' => mb_substr($actor ?: 'system', 0, 190),
            'action' => $action,
            'target_tg_id' => $target['tg_user_id'] ?? null,
            'target_name' => $target ? mb_substr(self::name($target), 0, 190) : null,
            'detail' => $detail !== null ? mb_substr($detail, 0, 1000) : null,
            'created_at' => now(),
        ]);
    }

    /** Lấy danh sách admin Telegram của nhóm (cache 10 phút). */
    public static function tgAdmins(array $group, ?Telegram $api, bool $force = false): array
    {
        if (!$force && $group['admins_json'] && now() - (int)$group['admins_at'] < 600) {
            return json_decode($group['admins_json'], true) ?: [];
        }
        if (!$api) return json_decode($group['admins_json'] ?? '[]', true) ?: [];
        try {
            $list = $api->call('getChatAdministrators', ['chat_id' => $group['chat_id']]);
        } catch (Throwable $e) {
            return json_decode($group['admins_json'] ?? '[]', true) ?: [];
        }
        $ids = [];
        $botIsAdmin = 0;
        foreach ($list as $a) {
            $ids[] = $a['user']['id'];
            if (!empty($a['user']['is_bot']) && $a['user']['id'] == explode(':', $api->token)[0]) $botIsAdmin = 1;
            $m = self::upsertMember((int)$group['id'], $a['user']);
            DB::run('UPDATE tg_members SET is_admin = 1 WHERE id = ?', [$m['id']]);
        }
        if ($ids) DB::run('UPDATE tg_members SET is_admin = 0 WHERE group_id = ? AND tg_user_id NOT IN (' . implode(',', array_map('intval', $ids)) . ')', [$group['id']]);
        DB::run('UPDATE tg_groups SET admins_json = ?, admins_at = ?, bot_is_admin = ? WHERE id = ?', [json_encode($ids), now(), $botIsAdmin, $group['id']]);
        return $ids;
    }

    /** Chỉ dùng khi kiểm thử: thay Telegram API thật bằng bản giả */
    public static ?Telegram $testApi = null;

    public static function apiForGroup(array $group): ?Telegram
    {
        if (self::$testApi) return self::$testApi;
        $bot = null;
        if ($group['bot_id']) $bot = DB::get('SELECT token FROM tg_bots WHERE id = ?', [$group['bot_id']]);
        if (!$bot) $bot = DB::get('SELECT token FROM tg_bots WHERE enabled = 1 ORDER BY id LIMIT 1');
        return $bot ? new Telegram($bot['token']) : null;
    }

    public static function deleteGroup(int $id): void
    {
        DB::run('DELETE FROM tg_members WHERE group_id = ?', [$id]);
        DB::run('DELETE FROM app_logs WHERE group_id = ?', [$id]);
        DB::run('DELETE FROM daily_stats WHERE group_id = ?', [$id]);
        DB::run('DELETE FROM tg_rules WHERE group_id = ?', [$id]);
        DB::run('DELETE FROM tg_rule_hits WHERE group_id = ?', [$id]);
        DB::run('DELETE FROM tg_groups WHERE id = ?', [$id]);
    }

    /** Chuẩn hoá trạng thái mute đã hết hạn. */
    public static function sweepMutes(): void
    {
        DB::run("UPDATE tg_members SET status = 'member', muted_until = NULL WHERE status = 'muted' AND muted_until IS NOT NULL AND muted_until > 0 AND muted_until < ?", [now()]);
    }
}

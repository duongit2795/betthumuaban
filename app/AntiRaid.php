<?php
defined('APP') or exit;

/**
 * Chống raid (đẩy bot / tài khoản ảo vào nhóm hàng loạt).
 * - Bot tự phê duyệt yêu cầu tham gia (nhóm bật "Phê duyệt thành viên mới").
 * - Trong X giây có ≥ N người vào / xin vào → chế độ khoá N phút: ngừng tự duyệt, xử lý người vào thẳng,
 *   tuỳ chọn khoá chat cả nhóm, đổi link mời, báo nhóm & nhắn admin. Hết hạn tự mở lại.
 * Cấu hình: Settings 'antiraid'; trạng thái khoá: Settings 'raid_{groupId}'.
 */
class AntiRaid
{
    const ACTIONS = ['mute' => 'Cấm chat', 'kick' => 'Đuổi khỏi nhóm', 'ban' => 'Cấm vĩnh viễn', 'none' => 'Không làm gì'];
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],
        'auto_approve' => true,      // bot tự duyệt yêu cầu tham gia khi bình thường
        'threshold' => 5,            // số người …
        'window' => 60,              // … trong N giây
        'lock_minutes' => 30,
        'request_action' => 'hold',  // khi đang khoá: hold = giữ chờ admin, decline = từ chối
        'join_action' => 'mute',     // người vào thẳng khi đang khoá
        'punish_trigger' => true,    // xử lý cả những người vừa vào kích hoạt khoá
        'lock_chat' => false,        // khoá chat cả nhóm
        'revoke_link' => true,       // đổi link mời chính
        'notify_group' => true,
        'notify_admins' => true,
        'group_text' => "🛡 <b>Phát hiện tham gia hàng loạt</b> ({count} người trong {window} giây).\nNhóm tạm khoá duyệt thành viên mới trong <b>{minutes} phút</b>.",
    ];

    /** Cài đặt hiệu lực: nhóm có cài đặt riêng thì dùng riêng, không thì dùng cài đặt chung */
    public static function config(?array $group = null): array
    {
        return GroupFeature::resolve('antiraid', self::DEFAULTS, $group);
    }

    public static function save(array $in, ?array $group = null, string $by = ''): array
    {
        $c = self::config($group);
        foreach (['enabled', 'auto_approve', 'punish_trigger', 'lock_chat', 'revoke_link', 'notify_group', 'notify_admins'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['threshold'])) $c['threshold'] = max(2, min(500, (int)$in['threshold']));
        if (isset($in['window'])) $c['window'] = max(10, min(3600, (int)$in['window']));
        if (isset($in['lock_minutes'])) $c['lock_minutes'] = max(1, min(1440, (int)$in['lock_minutes']));
        if (isset($in['request_action']) && in_array($in['request_action'], ['hold', 'decline'], true)) $c['request_action'] = $in['request_action'];
        if (isset($in['join_action']) && isset(self::ACTIONS[$in['join_action']])) $c['join_action'] = $in['join_action'];
        if (isset($in['group_text'])) { $t = trim(mb_substr((string)$in['group_text'], 0, 1500)); $c['group_text'] = $t !== '' ? $t : self::DEFAULTS['group_text']; }
        GroupFeature::store('antiraid', $c, $group, $by);
        return $c;
    }

    public static function applies(array $c, array $group): bool
    {
        return $c['enabled'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    public static function state(int $groupId): ?array
    {
        $s = json_decode((string)Settings::get('raid_' . $groupId), true);
        return is_array($s) && !empty($s['until']) ? $s : null;
    }

    public static function locked(int $groupId): bool
    {
        $s = self::state($groupId);
        return $s && (int)$s['until'] > now();
    }

    /**
     * Ghi nhận 1 lượt vào / xin vào; đạt ngưỡng thì khoá.
     * @return array{0:bool,1:bool} [nhóm đang khoá, đây là lượt mới (không trùng)]
     */
    private static function track(Telegram $api, array $group, $tgUserId): array
    {
        $c = self::config($group);
        $gid = (int)$group['id'];
        if (!self::applies($c, $group)) return [false, false];
        // Không đếm trùng (1 lần vào sinh ra cả tin hệ thống lẫn chat_member; duyệt yêu cầu rồi vào nhóm)
        $new = !DB::val('SELECT id FROM join_events WHERE group_id = ? AND tg_user_id = ? AND created_at > ?', [$gid, $tgUserId, now() - 120]);
        if ($new) DB::insert('join_events', ['group_id' => $gid, 'tg_user_id' => $tgUserId, 'created_at' => now()]);
        if (self::locked($gid)) return [true, $new];
        $count = (int)DB::val('SELECT COUNT(*) FROM join_events WHERE group_id = ? AND created_at > ?', [$gid, now() - $c['window']]);
        if ($new && $count >= $c['threshold']) {
            self::lock($api, $group, $c['lock_minutes'], "$count người trong {$c['window']} giây", $count);
            return [true, $new];
        }
        return [false, $new];
    }

    /** Bật chế độ khoá */
    public static function lock(Telegram $api, array $group, int $minutes, string $why, int $count = 0, string $by = 'Bot'): void
    {
        $c = self::config($group);
        $gid = (int)$group['id'];
        $prev = self::state($gid);
        $st = ['until' => now() + $minutes * 60, 'started_at' => now(), 'why' => $why, 'by' => $by, 'perms' => $prev['perms'] ?? null, 'chat_locked' => $prev['chat_locked'] ?? false];
        if ($c['lock_chat'] && empty($st['chat_locked'])) {
            $chat = $api->tryCall('getChat', ['chat_id' => $group['chat_id']]);
            $st['perms'] = is_array($chat) && !empty($chat['permissions']) ? $chat['permissions'] : null;
            if ($api->tryCall('setChatPermissions', ['chat_id' => $group['chat_id'], 'permissions' => Telegram::PERM_NONE, 'use_independent_chat_permissions' => true])) $st['chat_locked'] = true;
        }
        Settings::set('raid_' . $gid, json_encode($st, JSON_UNESCAPED_UNICODE));
        if ($c['revoke_link'] && empty($prev) ) $api->tryCall('exportChatInviteLink', ['chat_id' => $group['chat_id']]); // link mời chính cũ bị vô hiệu

        // Xử lý cả những người vừa vào trong khung thời gian
        if ($c['punish_trigger'] && $c['join_action'] !== 'none' && $count > 0) {
            foreach (DB::all('SELECT DISTINCT tg_user_id FROM join_events WHERE group_id = ? AND created_at > ?', [$gid, now() - $c['window'] - 5]) as $r) {
                $m = Repo::member($gid, $r['tg_user_id']);
                if (!$m || !in_array($m['status'], ['member', 'muted'], true) || $m['is_admin']) continue; // chỉ người đã vào nhóm (yêu cầu chưa duyệt thì bỏ qua)
                self::punish($api, $group, $r['tg_user_id'], 'Tham gia trong đợt raid');
            }
        }
        $vars = ['{count}' => (string)$count, '{window}' => (string)$c['window'], '{minutes}' => (string)$minutes, '{group}' => tg_esc($group['title']), '{until}' => date('H:i', $st['until'])];
        if ($c['notify_group']) $api->trySend($group['chat_id'], strtr($c['group_text'], $vars) . ($c['lock_chat'] ? "\n🔇 Tạm khoá chat cả nhóm." : ''));
        if ($c['notify_admins']) self::notifyAdmins($api, "🛡 <b>Chống raid</b> · " . tg_esc($group['title']) . "\nĐã khoá {$minutes} phút: " . tg_esc($why) . "\nDuyệt / từ chối yêu cầu tham gia trên web.");
        Repo::log($gid, $by, 'raid', null, "Bật khoá chống raid $minutes phút: $why", ['source' => $by === 'Bot' ? 'bot' : 'web']);
    }

    /** Mở khoá (hết hạn hoặc admin mở tay) */
    public static function unlock(Telegram $api, array $group, string $by = 'Bot'): void
    {
        $gid = (int)$group['id'];
        $st = self::state($gid);
        Settings::set('raid_' . $gid, '');
        if (!$st) return;
        if (!empty($st['chat_locked'])) {
            $api->tryCall('setChatPermissions', ['chat_id' => $group['chat_id'], 'permissions' => $st['perms'] ?: Telegram::permAll(), 'use_independent_chat_permissions' => true]);
        }
        $c = self::config($group);
        $pending = (int)DB::val("SELECT COUNT(*) FROM join_requests WHERE group_id = ? AND status = 'pending'", [$gid]);
        if ($c['notify_group']) $api->trySend($group['chat_id'], '✅ Đã hết chế độ chống raid, nhóm hoạt động bình thường.' . (!empty($st['chat_locked']) ? ' Chat đã được mở lại.' : ''));
        if ($c['notify_admins'] && $pending) self::notifyAdmins($api, '🛡 ' . tg_esc($group['title']) . ": hết khoá chống raid. Còn <b>$pending</b> yêu cầu tham gia chờ duyệt trên web.");
        Repo::log($gid, $by, 'raid_end', null, 'Mở khoá chống raid' . ($pending ? " · còn $pending yêu cầu chờ duyệt" : ''), ['source' => $by === 'Bot' ? 'bot' : 'web']);
    }

    private static function punish(Telegram $api, array $group, $tgUserId, string $why): void
    {
        $c = self::config($group);
        try {
            if ($c['join_action'] === 'mute') {
                $api->call('restrictChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $tgUserId, 'permissions' => Telegram::PERM_NONE, 'use_independent_chat_permissions' => true, 'until_date' => 0]);
                DB::run("UPDATE tg_members SET status = 'muted', muted_until = NULL WHERE group_id = ? AND tg_user_id = ?", [$group['id'], $tgUserId]);
                Repo::log((int)$group['id'], 'Bot (chống raid)', 'mute', Repo::member((int)$group['id'], $tgUserId), $why, ['source' => 'bot', 'reason' => $why]);
            } elseif (in_array($c['join_action'], ['kick', 'ban'], true)) {
                Moderation::perform($c['join_action'], $group, ['tgUserId' => $tgUserId, 'actor' => 'Bot (chống raid)', 'source' => 'bot', 'reason' => $why, 'notify' => false]);
            }
            Captcha::left($group, $tgUserId, $api);
        } catch (Throwable $e) {
            // thiếu quyền: bỏ qua
        }
    }

    private static function notifyAdmins(Telegram $api, string $text): void
    {
        foreach (DB::all("SELECT tg_user_id FROM app_users WHERE role = 'admin' AND disabled = 0 AND tg_user_id IS NOT NULL") as $a) $api->trySend($a['tg_user_id'], $text);
    }

    /** Thành viên vào thẳng nhóm. Trả về true nếu đã bị xử lý (không chạy captcha / lời chào). */
    public static function onJoin(Telegram $api, array $group, array $user, bool $isAdmin, ?int $addedBy, callable $isAdminFn): bool
    {
        if (!empty($user['is_bot']) && $addedBy && $isAdminFn($addedBy)) return false; // admin thêm bot
        if ($isAdmin || ($addedBy && $addedBy != $user['id'] && $isAdminFn($addedBy))) return false;
        $c = self::config($group);
        if (!self::applies($c, $group)) return false;
        $m = Repo::upsertMember((int)$group['id'], $user, ['status' => 'member', 'joined' => true]); // ghi nhận trước để lock() xử lý được cả người này
        if ($m['status'] === 'left') DB::run("UPDATE tg_members SET status = 'member' WHERE id = ?", [$m['id']]);
        // Người vừa được duyệt yêu cầu tham gia (admin / bot) → không xử lý lại
        $approved = DB::val("SELECT id FROM join_requests WHERE group_id = ? AND tg_user_id = ? AND status = 'approved' AND decided_at > ?", [$group['id'], $user['id'], now() - 600]);
        $wasLocked = self::locked((int)$group['id']);
        [$locked, $new] = self::track($api, $group, $user['id']);
        if ($approved) return false;
        if ($wasLocked && $c['join_action'] !== 'none') {
            if ($new) self::punish($api, $group, $user['id'], 'Vào nhóm khi đang khoá chống raid');
            return true;
        }
        return $locked && $c['punish_trigger'] && $c['join_action'] !== 'none'; // người kích hoạt đã bị xử lý trong lock()
    }

    /** Yêu cầu tham gia (chat_join_request) */
    public static function onRequest(Telegram $api, array $group, array $req): void
    {
        $u = $req['from'];
        $gid = (int)$group['id'];
        $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        $row = ['group_id' => $gid, 'tg_user_id' => $u['id'], 'name' => mb_substr($name, 0, 190), 'username' => $u['username'] ?? null,
            'bio' => isset($req['bio']) ? mb_substr($req['bio'], 0, 250) : null, 'status' => 'pending', 'created_at' => now(), 'decided_at' => null, 'decided_by' => null];
        $old = DB::val("SELECT id FROM join_requests WHERE group_id = ? AND tg_user_id = ? AND status = 'pending'", [$gid, $u['id']]);
        if ($old) DB::update('join_requests', $row, 'id = ?', [$old]); else $old = DB::insert('join_requests', $row);
        $c = self::config($group);
        if (!self::applies($c, $group)) return; // không áp dụng: để admin duyệt trong Telegram / web
        [$locked] = self::track($api, $group, $u['id']);
        if ($locked) {
            if ($c['request_action'] === 'decline') self::decideQuiet($api, $group, (int)$old, false, 'Bot (chống raid)');
            return; // giữ chờ admin
        }
        if ($c['auto_approve']) self::decideQuiet($api, $group, (int)$old, true, 'Bot (tự duyệt)');
    }

    /** Bot tự duyệt: lỗi thì để yêu cầu ở trạng thái chờ cho admin xử lý trên web, không báo lỗi bot */
    private static function decideQuiet(Telegram $api, array $group, int $id, bool $approve, string $by): void
    {
        try { self::decide($api, $group, $id, $approve, $by); }
        catch (Throwable $e) { Settings::set('raid_error', mb_substr($e->getMessage(), 0, 200) . ' · ' . date('H:i d/m')); }
    }

    /** Duyệt / từ chối 1 yêu cầu */
    public static function decide(Telegram $api, array $group, int $id, bool $approve, string $by): bool
    {
        $r = DB::get("SELECT * FROM join_requests WHERE id = ? AND group_id = ? AND status = 'pending'", [$id, $group['id']]);
        if (!$r) return false;
        try {
            $api->call($approve ? 'approveChatJoinRequest' : 'declineChatJoinRequest', ['chat_id' => $group['chat_id'], 'user_id' => $r['tg_user_id']]);
            $st = $approve ? 'approved' : 'declined';
        } catch (TelegramException $e) {
            $m = $e->getMessage();
            if (stripos($m, 'USER_ALREADY_PARTICIPANT') !== false) $st = 'approved';  // đã ở trong nhóm (admin khác / link khác đã duyệt, hoặc Telegram gửi lặp yêu cầu)
            elseif (preg_match('/HIDE_REQUESTER_MISSING|not found|USER_DEACTIVATED|USER_CHANNELS_TOO_MUCH|CHANNELS_TOO_MUCH/i', $m)) $st = 'expired';
            else throw new HttpError('Telegram: ' . $m);
        }
        DB::run('UPDATE join_requests SET status = ?, decided_at = ?, decided_by = ? WHERE id = ?', [$st, now(), mb_substr($by, 0, 60), $id]);
        return true;
    }

    /** Hết thời gian khoá → mở lại (cron / webhook) */
    public static function sweep(): void
    {
        foreach (DB::all("SELECT k, v FROM app_settings WHERE k LIKE 'raid%'") as $row) {
            if (!preg_match('/^raid_\d+$/', $row['k']) || (string)$row['v'] === '') continue;
            $st = json_decode((string)$row['v'], true);
            if (!is_array($st) || empty($st['until']) || (int)$st['until'] > now()) continue;
            $group = Repo::group((int)substr($row['k'], 5));
            $api = $group ? Repo::apiForGroup($group) : null;
            if ($group && $api) self::unlock($api, $group); else Settings::set($row['k'], '');
        }
        DB::run('DELETE FROM join_events WHERE created_at < ?', [now() - 86400]);
    }
}

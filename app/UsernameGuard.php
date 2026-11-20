<?php
defined('APP') or exit;

/**
 * Tính năng mặc định: yêu cầu thành viên đặt Username Telegram (@username).
 * Cấu hình chung lưu ở Settings 'require_username'.
 */
class UsernameGuard
{
    const ACTIONS = [
        'remind' => 'Chỉ nhắc nhở',
        'delete' => 'Xoá tin nhắn + nhắc nhở',
        'mute' => 'Cấm chat cho tới khi đặt username',
        'kick' => 'Đuổi khỏi nhóm',
    ];
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],          // rỗng = tất cả nhóm
        'action' => 'mute',
        'on_join' => true,       // kiểm tra ngay khi vào nhóm
        'exempt_admins' => true,
        'cooldown' => 60,        // giây giữa 2 lần nhắc cùng 1 người (chế độ nhắc / xoá)
        'autodel' => 120,        // tự xoá tin nhắc sau N giây (0 = không xoá)
        'text' => "👋 {mention}, nhóm yêu cầu thành viên phải có <b>Username Telegram</b> (dạng @tencuaban).\n\n📱 Cách đặt: <b>Cài đặt → Chỉnh sửa hồ sơ → Tên người dùng</b>.\nĐặt xong bấm nút bên dưới nhé!",
    ];
    const HIT_RULE_ID = -1; // dùng bảng tg_rule_hits để giới hạn tần suất nhắc

    /** Cài đặt hiệu lực: nhóm có cài đặt riêng thì dùng riêng, không thì dùng cài đặt chung */
    public static function config(?array $group = null): array
    {
        return GroupFeature::resolve('require_username', self::DEFAULTS, $group);
    }

    public static function save(array $in, ?array $group = null, string $by = ''): array
    {
        $c = self::config($group);
        if (array_key_exists('enabled', $in)) $c['enabled'] = !empty($in['enabled']);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['action']) && isset(self::ACTIONS[$in['action']])) $c['action'] = $in['action'];
        if (array_key_exists('on_join', $in)) $c['on_join'] = !empty($in['on_join']);
        if (array_key_exists('exempt_admins', $in)) $c['exempt_admins'] = !empty($in['exempt_admins']);
        if (isset($in['cooldown'])) $c['cooldown'] = max(0, min(86400, (int)$in['cooldown']));
        if (isset($in['autodel'])) $c['autodel'] = max(0, min(172800, (int)$in['autodel']));
        if (isset($in['text'])) {
            $t = trim(mb_substr((string)$in['text'], 0, 2000));
            $c['text'] = $t !== '' ? $t : self::DEFAULTS['text'];
        }
        GroupFeature::store('require_username', $c, $group, $by);
        return $c;
    }

    public static function appliesTo(array $group): bool
    {
        $c = self::config($group);
        return $c['enabled'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    /**
     * Gọi khi thành viên nhắn tin hoặc vừa vào nhóm.
     * @return bool true nếu tin nhắn đã bị xoá / người dùng bị xử lý (dừng xử lý tiếp)
     */
    public static function check(Telegram $api, int $botId, array $group, array $user, ?array $msg, bool $isAdmin, bool $joined = false): bool
    {
        if (!empty($user['username']) || !empty($user['is_bot']) || !self::appliesTo($group)) return false;
        $c = self::config($group);
        if ($isAdmin && $c['exempt_admins']) return false;
        if ($joined && !$c['on_join']) return false;
        $gid = (int)$group['id'];
        $chat = $group['chat_id'];
        $m = Repo::member($gid, $user['id']) ?: Repo::upsertMember($gid, $user);
        $action = $c['action'];

        // Xoá tin nhắn (chế độ xoá / cấm chat / đuổi)
        if ($msg && in_array($action, ['delete', 'mute', 'kick'], true)) {
            $api->tryCall('deleteMessage', ['chat_id' => $chat, 'message_id' => $msg['message_id']]);
            ChatLog::markDeleted($chat, $msg['message_id'], 'Chưa đặt username');
        }
        $stop = in_array($action, ['delete', 'mute', 'kick'], true);

        // Chế độ nhắc / xoá: không nhắc lại cùng 1 người trong thời gian chờ
        if (in_array($action, ['remind', 'delete'], true) && $c['cooldown'] > 0) {
            $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [self::HIT_RULE_ID, $gid, $user['id']]);
            if (now() - $last < $c['cooldown']) return $stop;
        }
        // Đang bị cấm chat vì username rồi thì không xử lý lại
        if ($action === 'mute' && $m['status'] === 'muted') return true;

        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [self::HIT_RULE_ID, $gid, $user['id']]);
        DB::insert('tg_rule_hits', ['rule_id' => self::HIT_RULE_ID, 'group_id' => $gid, 'tg_user_id' => $user['id'], 'last_at' => now()]);

        $ev = Moderation::evidence($msg);
        try {
            if ($action === 'mute' || $action === 'kick') {
                Moderation::perform($action, $group, ['tgUserId' => $user['id'], 'actor' => 'Bot', 'source' => 'bot', 'minutes' => 0,
                    'reason' => 'Chưa đặt Username Telegram', 'notify' => false, 'evidence' => $ev]);
            } else {
                Repo::log($gid, 'Bot', 'username', $m, $action === 'delete' ? 'Xoá tin + nhắc đặt username' : 'Nhắc đặt username', ['source' => 'bot', 'reason' => 'Chưa đặt Username Telegram'], $ev);
            }
        } catch (Throwable $e) {
            // Bot thiếu quyền cấm chat / đuổi: vẫn gửi nhắc nhở
            Repo::log($gid, 'Bot', 'username', $m, 'Nhắc đặt username (bot thiếu quyền: ' . mb_substr($e->getMessage(), 0, 80) . ')', ['source' => 'bot'], $ev);
        }

        // Tin nhắc nhở
        $text = strtr($c['text'], [
            '{mention}' => Repo::mention($m),
            '{name}' => tg_esc(Repo::name($m)),
            '{id}' => (string)$user['id'],
            '{group}' => tg_esc($group['title']),
        ]);
        if ($action === 'mute') $text .= "\n\n🔇 Bạn đang bị <b>tạm cấm chat</b> cho tới khi đặt username.";
        if ($action === 'kick') $text .= "\n\n👢 Bạn đã bị mời ra khỏi nhóm. Đặt username rồi vào lại nhé.";
        $extra = [];
        if ($action !== 'kick') {
            $extra['reply_markup'] = ['inline_keyboard' => [[['text' => '✅ Tôi đã đặt username', 'callback_data' => 'uname:' . $user['id'], 'style' => 'success']]]];
        }
        $sent = $api->trySend($chat, $text, $extra);
        if ($sent && !empty($sent['message_id']) && $c['autodel'] > 0) {
            DelQueue::add($api, $botId, $chat, [$sent['message_id']], $c['autodel']);
        }
        return $stop;
    }

    /** Nút "Tôi đã đặt username" */
    public static function onCallback(Telegram $api, array $cb): bool
    {
        if (!preg_match('/^uname:(\d+)$/', (string)($cb['data'] ?? ''), $mm)) return false;
        $answer = fn(string $t, bool $alert = false) => $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $t, 'show_alert' => $alert]);
        $from = $cb['from'];
        if ((string)$from['id'] !== $mm[1]) { $answer('Nút này dành cho người được nhắc', true); return true; }
        if (empty($from['username'])) {
            $answer("Bạn chưa đặt username.\nVào Cài đặt → Chỉnh sửa hồ sơ → Tên người dùng, đặt xong bấm lại nút này.", true);
            return true;
        }
        $chat = $cb['message']['chat']['id'] ?? null;
        $group = $chat ? DB::get('SELECT * FROM tg_groups WHERE chat_id = ?', [$chat]) : null;
        if (!$group) { $answer('Không tìm thấy nhóm'); return true; }
        $m = Repo::upsertMember((int)$group['id'], $from);
        if ($m['status'] === 'muted') {
            try {
                Moderation::perform('unmute', $group, ['tgUserId' => $from['id'], 'actor' => 'Bot', 'source' => 'bot', 'reason' => 'Đã đặt username @' . $from['username'], 'notify' => false]);
            } catch (Throwable $e) {
                $answer('Bot chưa đủ quyền để bỏ cấm chat, hãy nhờ admin', true);
                return true;
            }
        }
        $answer('Cảm ơn bạn! ✅');
        $api->tryCall('editMessageText', ['chat_id' => $chat, 'message_id' => $cb['message']['message_id'], 'parse_mode' => 'HTML',
            'text' => '✅ Cảm ơn ' . Repo::mention($m) . ' (@' . tg_esc($from['username']) . ') đã đặt username. Chúc bạn trò chuyện vui vẻ!']);
        return true;
    }
}

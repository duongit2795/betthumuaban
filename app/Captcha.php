<?php
defined('APP') or exit;

/**
 * Captcha khi vào nhóm: người mới bị tạm cấm chat, phải bấm nút / giải phép tính / chọn emoji trong N giây.
 * Đúng → được chat (khôi phục quyền mặc định của nhóm) + lời chào. Sai / hết giờ → đuổi / cấm / giữ cấm chat.
 * Cấu hình ở Settings 'captcha'; người đang chờ ở bảng captcha_pending.
 */
class Captcha
{
    const MODES = ['button' => 'Bấm nút xác nhận', 'math' => 'Giải phép tính', 'emoji' => 'Chọn đúng emoji'];
    const FAIL = ['kick' => 'Đuổi khỏi nhóm (vào lại được)', 'ban' => 'Cấm vĩnh viễn', 'mute' => 'Giữ cấm chat'];
    const EMOJIS = ['🍎' => 'quả táo', '🐶' => 'con chó', '🚗' => 'ô tô', '⚽' => 'quả bóng', '🌙' => 'mặt trăng', '🐱' => 'con mèo', '🍌' => 'quả chuối', '✈️' => 'máy bay', '🌸' => 'bông hoa', '🔑' => 'chìa khoá', '🎁' => 'hộp quà', '⭐' => 'ngôi sao'];

    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],
        'mode' => 'math',
        'timeout' => 120,          // giây
        'attempts' => 2,           // số lần chọn sai tối đa (math / emoji)
        'fail_action' => 'kick',
        'skip_admin_added' => true, // người do admin thêm vào
        'skip_verified' => true,    // đã xác minh trước đây ở nhóm này
        'skip_vip' => true,
        'delete_join' => true,      // xoá tin "x đã tham gia nhóm"
        'notify_fail' => false,     // báo khi bị đuổi
        'text' => "👋 Chào {mention}! Để chống spam, hãy xác minh bạn là người thật trong <b>{seconds} giây</b>.\n{question}",
        'button' => '✅ Tôi không phải robot',
    ];

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('captcha'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['enabled', 'skip_admin_added', 'skip_verified', 'skip_vip', 'delete_join', 'notify_fail'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['mode']) && isset(self::MODES[$in['mode']])) $c['mode'] = $in['mode'];
        if (isset($in['fail_action']) && isset(self::FAIL[$in['fail_action']])) $c['fail_action'] = $in['fail_action'];
        if (isset($in['timeout'])) $c['timeout'] = max(30, min(3600, (int)$in['timeout']));
        if (isset($in['attempts'])) $c['attempts'] = max(1, min(5, (int)$in['attempts']));
        if (isset($in['text'])) { $t = trim(mb_substr((string)$in['text'], 0, 1500)); $c['text'] = $t !== '' ? $t : self::DEFAULTS['text']; }
        if (isset($in['button'])) { $t = trim(mb_substr((string)$in['button'], 0, 40)); $c['button'] = $t !== '' ? $t : self::DEFAULTS['button']; }
        Settings::set('captcha', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    public static function applies(array $c, array $group): bool
    {
        return $c['enabled'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    /** Tạo câu hỏi: [nội dung, đáp án đúng, danh sách lựa chọn] */
    private static function challenge(string $mode): array
    {
        if ($mode === 'math') {
            $a = random_int(2, 12); $b = random_int(2, 12);
            [$q, $ans] = random_int(0, 1) ? ["$a + $b", $a + $b] : [max($a, $b) . ' − ' . min($a, $b), max($a, $b) - min($a, $b)];
            $opts = [$ans];
            while (count($opts) < 4) { $x = $ans + random_int(-6, 6); if ($x >= 0 && !in_array($x, $opts, true)) $opts[] = $x; }
            shuffle($opts);
            return ["🧮 Kết quả của <b>$q</b> là bao nhiêu?", (string)$ans, array_map('strval', $opts)];
        }
        if ($mode === 'emoji') {
            $keys = array_keys(self::EMOJIS);
            shuffle($keys);
            $opts = array_slice($keys, 0, 6);
            $ans = $opts[array_rand($opts)];
            return ['👉 Hãy bấm vào <b>' . self::EMOJIS[$ans] . '</b>', $ans, $opts];
        }
        return ['', 'ok', ['ok']];
    }

    /**
     * Thành viên vừa vào nhóm. Trả về true nếu bắt đầu captcha (lời chào nên đợi tới khi xác minh).
     * @param ?int $addedBy ID người thêm (nếu được thêm bởi người khác)
     */
    public static function start(Telegram $api, int $botId, array $group, array $user, ?int $addedBy, callable $isAdmin): bool
    {
        $c = self::config();
        if (!empty($user['is_bot']) || !self::applies($c, $group)) return false;
        $gid = (int)$group['id'];
        if ($isAdmin((int)$user['id'])) return false;
        if ($c['skip_admin_added'] && $addedBy && $addedBy != $user['id'] && $isAdmin($addedBy)) return false;
        $m = Repo::member($gid, $user['id']) ?: Repo::upsertMember($gid, $user);
        if ($c['skip_verified'] && !empty($m['captcha_ok'])) return false;
        if ($c['skip_vip'] && Vip::active($user['id'], $gid)) return false;
        if (DB::val('SELECT id FROM captcha_pending WHERE group_id = ? AND tg_user_id = ?', [$gid, $user['id']])) return true; // đang chờ rồi

        // Tạm cấm chat cho tới khi xác minh (giới hạn theo thời gian chờ + 5 phút phòng khi bot lỗi)
        try {
            $api->call('restrictChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $user['id'], 'permissions' => Telegram::PERM_NONE,
                'use_independent_chat_permissions' => true, 'until_date' => now() + $c['timeout'] + 300]);
        } catch (TelegramException $e) {
            Repo::log($gid, 'Bot', 'captcha', $m, 'Không bật được captcha (bot cần quyền cấm thành viên): ' . mb_substr($e->getMessage(), 0, 100), ['source' => 'bot']);
            return false;
        }
        [$q, $ans, $opts] = self::challenge($c['mode']);
        $id = DB::insert('captcha_pending', ['group_id' => $gid, 'tg_user_id' => $user['id'], 'answer' => $ans, 'attempts' => 0, 'message_id' => null,
            'expires_at' => now() + $c['timeout'], 'created_at' => now()]);
        if ($c['mode'] === 'button') {
            $kb = [[['text' => $c['button'], 'callback_data' => "cap:$id:ok", 'style' => 'success']]];
        } else {
            $btns = array_map(fn($o) => ['text' => $o, 'callback_data' => "cap:$id:" . (string)$o], $opts);
            $kb = array_chunk($btns, $c['mode'] === 'emoji' ? 3 : 4);
        }
        $text = strtr($c['text'], ['{mention}' => Repo::mention($m), '{name}' => tg_esc(Repo::name($m)), '{group}' => tg_esc($group['title']),
            '{seconds}' => (string)$c['timeout'], '{question}' => $q]);
        $sent = $api->trySend($group['chat_id'], trim($text), ['reply_markup' => ['inline_keyboard' => $kb]]);
        if (!empty($sent['message_id'])) DB::run('UPDATE captcha_pending SET message_id = ? WHERE id = ?', [$sent['message_id'], $id]);
        Repo::log($gid, 'Bot', 'captcha', $m, 'Yêu cầu xác minh (' . self::MODES[$c['mode']] . ', ' . $c['timeout'] . ' giây)', ['source' => 'bot']);
        return true;
    }

    /**
     * Nút captcha. Trả về null nếu không phải nút captcha; ngược lại [kết quả, group, member]:
     * 'pass' (đúng), 'fail' (sai hết lượt), 'retry' (sai còn lượt), 'other' (người khác bấm / hết hạn)
     */
    public static function onCallback(Telegram $api, array $cb): ?array
    {
        if (!preg_match('/^cap:(\d+):(.+)$/u', (string)($cb['data'] ?? ''), $mm)) return null;
        $answer = fn(string $t, bool $alert = false) => $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $t, 'show_alert' => $alert]);
        $p = DB::get('SELECT * FROM captcha_pending WHERE id = ?', [(int)$mm[1]]);
        if (!$p) { $answer('Yêu cầu xác minh này đã hết hạn'); return ['other', null, null]; }
        if ((string)$cb['from']['id'] !== (string)$p['tg_user_id']) { $answer('Nút này dành cho thành viên mới vào nhóm', true); return ['other', null, null]; }
        $group = Repo::group((int)$p['group_id']);
        $c = self::config();
        if ($mm[2] === $p['answer']) {
            self::pass($api, $group, $p);
            $answer('Xác minh thành công! Chào mừng bạn 🎉');
            return ['pass', $group, Repo::member((int)$p['group_id'], $p['tg_user_id'])];
        }
        $att = (int)$p['attempts'] + 1;
        if ($att >= (int)$c['attempts']) {
            $answer('Sai rồi. Bạn đã hết lượt thử.', true);
            self::fail($api, $group, $p, 'Chọn sai ' . $att . ' lần');
            return ['fail', $group, null];
        }
        DB::run('UPDATE captcha_pending SET attempts = ? WHERE id = ?', [$att, $p['id']]);
        $answer('Sai rồi, thử lại nhé! Còn ' . ($c['attempts'] - $att) . ' lần.', true);
        return ['retry', $group, null];
    }

    /** Khôi phục quyền chat mặc định của nhóm */
    private static function unrestrict(Telegram $api, array $group, $tgUserId): void
    {
        $chat = $api->tryCall('getChat', ['chat_id' => $group['chat_id']]);
        $perms = is_array($chat) && !empty($chat['permissions']) ? $chat['permissions'] : Telegram::permAll();
        $api->tryCall('restrictChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $tgUserId, 'permissions' => $perms, 'use_independent_chat_permissions' => true]);
    }

    private static function pass(Telegram $api, array $group, array $p): void
    {
        DB::run('DELETE FROM captcha_pending WHERE id = ?', [$p['id']]);
        self::unrestrict($api, $group, $p['tg_user_id']);
        DB::run("UPDATE tg_members SET captcha_ok = 1, status = CASE WHEN status = 'muted' THEN 'member' ELSE status END, muted_until = NULL WHERE group_id = ? AND tg_user_id = ?", [$group['id'], $p['tg_user_id']]);
        if ($p['message_id']) $api->tryCall('deleteMessage', ['chat_id' => $group['chat_id'], 'message_id' => $p['message_id']]);
        Repo::log((int)$group['id'], 'Bot', 'captcha_ok', Repo::member((int)$group['id'], $p['tg_user_id']), 'Xác minh thành công sau ' . (now() - (int)$p['created_at']) . ' giây', ['source' => 'bot']);
    }

    private static function fail(Telegram $api, array $group, array $p, string $why): void
    {
        DB::run('DELETE FROM captcha_pending WHERE id = ?', [$p['id']]);
        if ($p['message_id']) $api->tryCall('deleteMessage', ['chat_id' => $group['chat_id'], 'message_id' => $p['message_id']]);
        $c = self::config();
        $m = Repo::member((int)$group['id'], $p['tg_user_id']);
        $label = ['kick' => 'đuổi khỏi nhóm', 'ban' => 'cấm vĩnh viễn', 'mute' => 'giữ cấm chat'][$c['fail_action']];
        try {
            if ($c['fail_action'] === 'mute') {
                $api->call('restrictChatMember', ['chat_id' => $group['chat_id'], 'user_id' => $p['tg_user_id'], 'permissions' => Telegram::PERM_NONE, 'use_independent_chat_permissions' => true, 'until_date' => 0]);
                DB::run("UPDATE tg_members SET status = 'muted', muted_until = NULL WHERE group_id = ? AND tg_user_id = ?", [$group['id'], $p['tg_user_id']]);
            } else {
                Moderation::perform($c['fail_action'], $group, ['tgUserId' => $p['tg_user_id'], 'actor' => 'Bot (captcha)', 'source' => 'bot', 'reason' => "Không xác minh captcha: $why", 'notify' => $c['notify_fail']]);
            }
        } catch (Throwable $e) {
            // thiếu quyền: để nguyên trạng thái cấm chat tạm thời
        }
        Repo::log((int)$group['id'], 'Bot', 'captcha_fail', $m, "Không qua captcha ($why) → $label", ['source' => 'bot']);
    }

    /** Xử lý người hết giờ (gọi từ webhook và cron) */
    public static function sweep(int $limit = 20): int
    {
        $n = 0;
        foreach (DB::all('SELECT * FROM captcha_pending WHERE expires_at <= ? ORDER BY expires_at LIMIT ' . (int)$limit, [now()]) as $p) {
            $group = Repo::group((int)$p['group_id']);
            $api = $group ? Repo::apiForGroup($group) : null;
            if (!$group || !$api) { DB::run('DELETE FROM captcha_pending WHERE id = ?', [$p['id']]); continue; }
            self::fail($api, $group, $p, 'Hết ' . self::config()['timeout'] . ' giây không xác minh');
            $n++;
        }
        return $n;
    }

    /** Người đang chờ rời nhóm → xoá yêu cầu */
    public static function left(array $group, $tgUserId, ?Telegram $api = null): void
    {
        $p = DB::get('SELECT * FROM captcha_pending WHERE group_id = ? AND tg_user_id = ?', [$group['id'], $tgUserId]);
        if (!$p) return;
        DB::run('DELETE FROM captcha_pending WHERE id = ?', [$p['id']]);
        if ($api && $p['message_id']) $api->tryCall('deleteMessage', ['chat_id' => $group['chat_id'], 'message_id' => $p['message_id']]);
    }
}

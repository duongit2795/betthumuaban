<?php
defined('APP') or exit;

/**
 * Học tập bắt buộc: thành viên chưa hoàn thành bài học mà nhắn trong nhóm → xoá tin + nhắc vào bot học.
 * Trong bot: nút "Học tập" (đổi tên được) → lần lượt các câu hỏi do admin đặt, trả lời khớp đáp án là qua.
 * Học xong 1 lần là chat được ở mọi nhóm áp dụng. Cấu hình lưu ở Settings 'learn'.
 */
class Learn
{
    const SCOPES = ['all' => 'Tất cả thành viên chưa học', 'new' => 'Chỉ thành viên mới vào nhóm (sau khi bật)'];
    const MODES = ['exact' => 'Khớp chính xác', 'contains' => 'Có chứa đáp án'];
    const HIT_RULE_ID = -2; // tg_rule_hits: giới hạn tần suất nhắc trong nhóm

    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],            // rỗng = tất cả nhóm
        'scope' => 'all',
        'exempt_admins' => true,
        'button' => '📚 Học tập',  // nút trong bot
        'group_button' => '📚 Vào học ngay',
        'cooldown' => 60,
        'autodel' => 60,
        'group_text' => "📚 {mention}, bạn cần <b>hoàn thành bài học</b> trước khi chat trong nhóm.\nBấm nút bên dưới để vào bot học nhé, chỉ mất 1 phút!",
        'notify_done' => true,
        'done_autodel' => 0,
        'done_text' => "🎓 {mention} vừa <b>hoàn thành bài học</b> và đã có thể chat trong nhóm. Chào mừng bạn!",
        'intro_text' => "📚 <b>Bài học dành cho thành viên</b>\nCó <b>{total}</b> câu hỏi. Trả lời đúng hết là bạn có thể chat trong nhóm.",
        'correct_text' => '✅ Chính xác!',
        'wrong_text' => '❌ Chưa đúng, hãy đọc kỹ câu hỏi và thử lại nhé.',
        'finish_text' => "🎉 Chúc mừng {name}! Bạn đã <b>hoàn thành bài học</b>.\nGiờ bạn có thể quay lại nhóm và chat bình thường.",
    ];

    // ================= Cấu hình =================
    public static function config(): array
    {
        $c = json_decode((string)Settings::get('learn'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['enabled', 'exempt_admins', 'notify_done'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['scope']) && isset(self::SCOPES[$in['scope']])) {
            if ($in['scope'] === 'new' && $c['scope'] !== 'new') Settings::set('learn_since', (string)now());
            $c['scope'] = $in['scope'];
        }
        foreach (['cooldown' => 86400, 'autodel' => 172800, 'done_autodel' => 172800] as $k => $max) {
            if (isset($in[$k])) $c[$k] = max(0, min($max, (int)$in[$k]));
        }
        foreach (['button' => 30, 'group_button' => 40] as $k => $max) {
            if (isset($in[$k])) {
                $t = trim(mb_substr(preg_replace('/\s+/u', ' ', (string)$in[$k]), 0, $max));
                $c[$k] = $t !== '' ? $t : self::DEFAULTS[$k];
            }
        }
        if (str_starts_with($c['button'], '/')) throw new HttpError('Tên nút không được bắt đầu bằng dấu /');
        foreach (['group_text', 'done_text', 'intro_text', 'correct_text', 'wrong_text', 'finish_text'] as $k) {
            if (isset($in[$k])) {
                $t = trim(mb_substr((string)$in[$k], 0, 3000));
                $c[$k] = $t !== '' ? $t : self::DEFAULTS[$k];
            }
        }
        Settings::set('learn', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    public static function appliesTo(array $group): bool
    {
        $c = self::config();
        return $c['enabled'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    // ================= Câu hỏi =================
    public static function questions(bool $enabledOnly = true): array
    {
        return DB::all('SELECT * FROM learn_questions' . ($enabledOnly ? ' WHERE enabled = 1' : '') . ' ORDER BY sort, id');
    }

    public static function questionRow(array $q): array
    {
        return [
            'id' => (int)$q['id'], 'sort' => (int)$q['sort'], 'question' => $q['question'],
            'answers' => json_decode((string)$q['answers'], true) ?: [], 'match_mode' => $q['match_mode'],
            'hint' => (string)$q['hint'], 'enabled' => (int)$q['enabled'],
        ];
    }

    /** Chuẩn hoá để so khớp: bỏ khoảng trắng thừa, ký tự ẩn, không phân biệt hoa thường */
    public static function norm(string $s): string
    {
        $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return mb_strtolower(trim($s));
    }

    public static function isCorrect(array $q, string $answer): bool
    {
        $a = self::norm($answer);
        if ($a === '') return false;
        foreach (json_decode((string)$q['answers'], true) ?: [] as $ok) {
            $ok = self::norm((string)$ok);
            if ($ok === '') continue;
            if ($q['match_mode'] === 'contains' ? str_contains($a, $ok) : $a === $ok) return true;
        }
        return false;
    }

    // ================= Tiến độ =================
    public static function progress($tgUserId): ?array
    {
        return DB::get('SELECT * FROM learn_progress WHERE tg_user_id = ?', [$tgUserId]);
    }

    /** Tạo / cập nhật dòng tiến độ (chưa hoàn thành thì đánh dấu cần học) */
    private static function touch(array $user, ?int $groupId = null): array
    {
        $p = self::progress($user['id']);
        $info = ['first_name' => mb_substr((string)($user['first_name'] ?? ''), 0, 100), 'last_name' => isset($user['last_name']) ? mb_substr($user['last_name'], 0, 100) : null,
            'username' => $user['username'] ?? null, 'updated_at' => now()];
        if (!$p) {
            DB::insert('learn_progress', ['tg_user_id' => $user['id'], 'status' => 'required', 'step' => 0, 'wrong' => 0,
                'groups_json' => json_encode($groupId ? [$groupId] : [])] + $info);
        } else {
            $groups = json_decode((string)$p['groups_json'], true) ?: [];
            if ($groupId && !in_array($groupId, $groups, true)) $groups[] = $groupId;
            DB::update('learn_progress', ['groups_json' => json_encode(array_slice($groups, -20))] + $info, 'tg_user_id = ?', [$user['id']]);
        }
        return self::progress($user['id']);
    }

    public static function botLink(string $botUsername): string
    {
        return 'https://t.me/' . $botUsername . '?start=learn';
    }

    // ================= Trong nhóm =================
    /** Thành viên vừa vào nhóm (chế độ "chỉ thành viên mới") → đánh dấu cần học */
    public static function onJoin(array $group, array $user): void
    {
        if (!empty($user['is_bot']) || !self::appliesTo($group) || self::config()['scope'] !== 'new') return;
        $p = self::progress($user['id']);
        if ($p && $p['status'] === 'done') return;
        self::touch($user, (int)$group['id']);
    }

    /**
     * Gọi khi thành viên nhắn tin trong nhóm.
     * @return bool true nếu tin đã bị xoá (dừng xử lý tiếp)
     */
    public static function check(Telegram $api, array $bot, array $group, array $user, array $msg, bool $isAdmin): bool
    {
        if (!empty($user['is_bot']) || !self::appliesTo($group)) return false;
        $c = self::config();
        if ($isAdmin && $c['exempt_admins']) return false;
        $p = self::progress($user['id']);
        if ($p && $p['status'] === 'done') return false;
        if ($c['scope'] === 'new' && !$p) return false;         // thành viên cũ không phải học
        if (!DB::val('SELECT COUNT(*) FROM learn_questions WHERE enabled = 1')) return false; // chưa có câu hỏi → không chặn

        $gid = (int)$group['id'];
        $chat = $group['chat_id'];
        $api->tryCall('deleteMessage', ['chat_id' => $chat, 'message_id' => $msg['message_id']]);
        ChatLog::markDeleted($chat, $msg['message_id'], 'Chưa hoàn thành bài học');
        self::touch($user, $gid);

        // Không nhắc lại cùng 1 người trong thời gian chờ
        if ($c['cooldown'] > 0) {
            $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [self::HIT_RULE_ID, $gid, $user['id']]);
            if (now() - $last < $c['cooldown']) return true;
        }
        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [self::HIT_RULE_ID, $gid, $user['id']]);
        DB::insert('tg_rule_hits', ['rule_id' => self::HIT_RULE_ID, 'group_id' => $gid, 'tg_user_id' => $user['id'], 'last_at' => now()]);

        $m = Repo::member($gid, $user['id']) ?: Repo::upsertMember($gid, $user);
        Repo::log($gid, 'Bot', 'learn', $m, 'Xoá tin + nhắc hoàn thành bài học', ['source' => 'bot', 'reason' => 'Chưa hoàn thành bài học'], Moderation::evidence($msg));
        $text = strtr($c['group_text'], ['{mention}' => Repo::mention($m), '{name}' => tg_esc(Repo::name($m)), '{group}' => tg_esc($group['title']), '{bot}' => '@' . tg_esc($bot['username'])]);
        $extra = ['reply_markup' => ['inline_keyboard' => [[['text' => $c['group_button'], 'url' => self::botLink((string)$bot['username']), 'style' => 'primary']]]]];
        $sent = $api->trySend($chat, $text, $extra);
        if ($sent && !empty($sent['message_id']) && $c['autodel'] > 0) DelQueue::add($api, (int)$bot['id'], $chat, [$sent['message_id']], $c['autodel']);
        return true;
    }

    // ================= Trong bot (chat riêng) =================
    /** Bàn phím nút của bot (cấu hình ở trang Bàn phím bot) */
    public static function replyKeyboard(): ?array
    {
        return BotKeyboard::markup();
    }

    /**
     * Xử lý tin nhắn riêng liên quan tới học tập.
     * @return bool true nếu đã xử lý
     */
    public static function onPrivate(Telegram $api, array $msg, ?string $key, ?string $payload): bool
    {
        $c = self::config();
        if (!$c['enabled']) return false;
        $from = $msg['from'];
        $text = trim((string)($msg['text'] ?? ''));
        $chat = $msg['chat']['id'];
        $isStart = ($payload !== null && str_starts_with($payload, 'learn')) || $key === 'learn' || $text === $c['button'];
        $p = self::progress($from['id']);

        if ($isStart) {
            self::start($api, $chat, $from, $p);
            return true;
        }
        // Đang học: tin nhắn thường (không phải lệnh) là câu trả lời
        if ($p && $p['status'] === 'learning' && $text !== '' && !str_starts_with($text, '/')) {
            self::answer($api, $chat, $from, $p, $text);
            return true;
        }
        return false;
    }

    private static function send(Telegram $api, $chat, string $text): void
    {
        $kb = self::replyKeyboard();
        $api->trySend($chat, $text, $kb ? ['reply_markup' => $kb] : []);
    }

    private static function vars(array $from, int $total): array
    {
        return ['{name}' => tg_esc(trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''))), '{total}' => (string)$total,
            '{mention}' => Repo::mention(['tg_user_id' => $from['id'], 'first_name' => $from['first_name'] ?? '', 'last_name' => $from['last_name'] ?? null])];
    }

    private static function start(Telegram $api, $chat, array $from, ?array $p): void
    {
        $c = self::config();
        $qs = self::questions();
        if (!$qs) { self::send($api, $chat, '📚 Hiện chưa có bài học nào. Bạn có thể chat trong nhóm bình thường.'); return; }
        if ($p && $p['status'] === 'done') { self::send($api, $chat, '🎓 Bạn đã <b>hoàn thành bài học</b> rồi, có thể chat trong nhóm bình thường.'); return; }
        $p = self::touch($from);
        $step = min((int)$p['step'], count($qs));
        DB::update('learn_progress', ['status' => 'learning', 'started_at' => $p['started_at'] ?: now(), 'updated_at' => now()], 'tg_user_id = ?', [$from['id']]);
        if ($step === 0) self::send($api, $chat, strtr($c['intro_text'], self::vars($from, count($qs))));
        if ($step >= count($qs)) { self::finish($api, $chat, $from); return; }
        self::ask($api, $chat, $qs, $step);
    }

    private static function ask(Telegram $api, $chat, array $qs, int $step): void
    {
        $q = $qs[$step];
        $total = count($qs);
        $bar = str_repeat('🟩', $step) . str_repeat('⬜', max(0, min($total, 10) - min($step, 10)));
        self::send($api, $chat, "📖 <b>Câu " . ($step + 1) . "/$total</b>  " . ($total <= 10 ? $bar : '') . "\n\n" . $q['question'] . "\n\n✍️ <i>Gõ câu trả lời của bạn vào ô chat.</i>");
    }

    private static function answer(Telegram $api, $chat, array $from, array $p, string $text): void
    {
        $c = self::config();
        $qs = self::questions();
        $step = (int)$p['step'];
        if (!$qs || $step >= count($qs)) { self::finish($api, $chat, $from); return; }
        $q = $qs[$step];
        if (!self::isCorrect($q, $text)) {
            DB::run('UPDATE learn_progress SET wrong = wrong + 1, updated_at = ? WHERE tg_user_id = ?', [now(), $from['id']]);
            $msg = $c['wrong_text'];
            if (trim((string)$q['hint']) !== '') $msg .= "\n💡 Gợi ý: " . $q['hint'];
            self::send($api, $chat, $msg);
            return;
        }
        $step++;
        DB::run('UPDATE learn_progress SET step = ?, updated_at = ? WHERE tg_user_id = ?', [$step, now(), $from['id']]);
        if ($step >= count($qs)) { self::finish($api, $chat, $from, $c['correct_text']); return; }
        self::send($api, $chat, $c['correct_text']);
        self::ask($api, $chat, $qs, $step);
    }

    /** Hoàn thành: báo trong bot + thông báo ở các nhóm đã bị nhắc */
    public static function finish(?Telegram $api, $chat, array $from, string $prefix = '', string $by = ''): void
    {
        $c = self::config();
        $p = self::progress($from['id']);
        if (!$p) { self::touch($from); $p = self::progress($from['id']); }
        DB::update('learn_progress', ['status' => 'done', 'done_at' => now(), 'updated_at' => now()], 'tg_user_id = ?', [$from['id']]);
        $total = count(self::questions());
        if ($api && $chat) self::send($api, $chat, trim($prefix . "\n\n" . strtr($c['finish_text'], self::vars($from, $total))));

        // Thông báo vào các nhóm đã nhắc học (nếu bật)
        $gids = json_decode((string)$p['groups_json'], true) ?: [];
        foreach ($gids as $gid) {
            $group = DB::get('SELECT * FROM tg_groups WHERE id = ? AND active = 1', [$gid]);
            if (!$group) continue;
            $m = Repo::member((int)$gid, $from['id']) ?: Repo::upsertMember((int)$gid, $from);
            Repo::log((int)$gid, $by ?: 'Bot', 'learn_done', $m, $by ? "Admin $by đánh dấu đã học" : "Hoàn thành bài học ($total câu)", ['source' => $by ? 'web' : 'bot']);
            if (!$c['notify_done']) continue;
            $gapi = Repo::apiForGroup($group);
            if (!$gapi) continue;
            $sent = $gapi->trySend($group['chat_id'], strtr($c['done_text'], ['{mention}' => Repo::mention($m), '{name}' => tg_esc(Repo::name($m)), '{group}' => tg_esc($group['title'])]));
            if ($sent && !empty($sent['message_id']) && $c['done_autodel'] > 0 && $group['bot_id']) {
                DelQueue::add($gapi, (int)$group['bot_id'], $group['chat_id'], [$sent['message_id']], $c['done_autodel']);
            }
        }
    }
}

<?php
defined('APP') or exit;

/**
 * Lệnh tự tạo: KHI (lệnh / từ khoá…) + NẾU (điều kiện) → THÌ (chuỗi hành động).
 */
class Rules
{
    const TRIGGERS = ['command', 'contains', 'exact', 'starts', 'regex'];
    const ACTIONS = ['reply', 'send', 'delete', 'points', 'warn', 'mute', 'unmute', 'kick', 'ban', 'unban', 'pin'];
    const MOD_ACTIONS = ['warn', 'mute', 'unmute', 'kick', 'ban', 'unban'];

    // ================= Lưu / kiểm tra dữ liệu =================
    public static function sanitize(array $in): array
    {
        $type = in_array($in['trigger_type'] ?? '', self::TRIGGERS, true) ? $in['trigger_type'] : 'command';
        $value = trim((string)($in['trigger_value'] ?? ''));
        if ($type === 'command') {
            $value = strtolower(ltrim($value, '/'));
            if (!preg_match('/^[a-z0-9_]{1,32}$/', $value)) throw new HttpError('Tên lệnh chỉ gồm chữ thường không dấu, số, dấu _ (tối đa 32 ký tự)');
        } else {
            if ($value === '') throw new HttpError('Nhập từ khoá kích hoạt');
            if ($type === 'regex' && @preg_match(self::regex($value), '') === false) throw new HttpError('Biểu thức regex không hợp lệ');
            $value = mb_substr($value, 0, 500);
        }
        $name = trim((string)($in['name'] ?? ''));
        if ($name === '') $name = $type === 'command' ? "Lệnh /$value" : $value;

        $c = (array)($in['conditions'] ?? []);
        $cond = [
            'who' => in_array($c['who'] ?? 'all', ['all', 'admin', 'member'], true) ? ($c['who'] ?? 'all') : 'all',
            'min_points' => max(0, (int)($c['min_points'] ?? 0)),
            'require_reply' => !empty($c['require_reply']),
            'cooldown' => max(0, min(31536000, (int)($c['cooldown'] ?? 0))),
            'chance' => max(1, min(100, (int)($c['chance'] ?? 100))),
        ];

        $actions = [];
        foreach (array_slice((array)($in['actions'] ?? []), 0, 10) as $a) {
            $t = $a['type'] ?? '';
            if (!in_array($t, self::ACTIONS, true)) continue;
            $act = ['type' => $t, 'who' => ($a['who'] ?? 'sender') === 'target' ? 'target' : 'sender'];
            if (in_array($t, ['reply', 'send'], true)) {
                $act['text'] = mb_substr((string)($a['text'] ?? ''), 0, 4000);
                if (trim($act['text']) === '') throw new HttpError('Hành động trả lời/gửi tin cần có nội dung');
            }
            if ($t === 'points') {
                $act['amount'] = max(-100000, min(100000, (int)($a['amount'] ?? 0)));
                if ($act['amount'] === 0) throw new HttpError('Số điểm phải khác 0');
            }
            if ($t === 'mute') $act['minutes'] = max(0, min(525600, (int)($a['minutes'] ?? 60)));
            if (in_array($t, self::MOD_ACTIONS, true)) $act['reason'] = mb_substr((string)($a['reason'] ?? ''), 0, 200);
            $actions[] = $act;
        }
        if (!$actions) throw new HttpError('Thêm ít nhất 1 hành động');

        $gid = (int)($in['group_id'] ?? 0);
        if ($gid && !Repo::group($gid)) throw new HttpError('Nhóm không tồn tại');

        return [
            'group_id' => $gid ?: null,
            'name' => mb_substr($name, 0, 120),
            'trigger_type' => $type,
            'trigger_value' => $value,
            'conditions' => json_encode($cond, JSON_UNESCAPED_UNICODE),
            'actions' => json_encode($actions, JSON_UNESCAPED_UNICODE),
            'enabled' => !array_key_exists('enabled', $in) || !empty($in['enabled']) ? 1 : 0,
            'show_in_menu' => !empty($in['show_in_menu']) ? 1 : 0,
            'priority' => max(-100, min(100, (int)($in['priority'] ?? 0))),
        ];
    }

    public static function row(array $r): array
    {
        return [
            'id' => (int)$r['id'],
            'group_id' => $r['group_id'] ? (int)$r['group_id'] : null,
            'group_title' => $r['group_title'] ?? null,
            'name' => $r['name'],
            'trigger_type' => $r['trigger_type'],
            'trigger_value' => $r['trigger_value'],
            'conditions' => json_decode($r['conditions'] ?: '{}', true) ?: [],
            'actions' => json_decode($r['actions'] ?: '[]', true) ?: [],
            'enabled' => (int)$r['enabled'],
            'show_in_menu' => (int)$r['show_in_menu'],
            'priority' => (int)$r['priority'],
            'uses' => (int)$r['uses'],
            'last_used' => $r['last_used'] ? (int)$r['last_used'] : null,
        ];
    }

    private static function regex(string $v): string
    {
        return '~' . str_replace('~', '\~', $v) . '~iu';
    }

    private static function words(string $v): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[|\n]/u', $v)), 'strlen'));
    }

    // ================= Chạy khi có tin nhắn =================
    /**
     * @return int cờ: HANDLED = lệnh /tự tạo đã xử lý (bỏ qua lệnh có sẵn), DELETED = tin nhắn gốc đã bị xoá
     */
    const HANDLED = 1;
    const DELETED = 2;

    public static function run(Telegram $api, string $botUsername, int $botTgId, array $group, array $msg, array $member, bool $isAdmin, string $text, callable $isAdminFn): int
    {
        if ($text === '') return 0;
        $rules = DB::all('SELECT * FROM tg_rules WHERE enabled = 1 AND (group_id IS NULL OR group_id = ?) ORDER BY priority DESC, id ASC', [$group['id']]);
        if (!$rules) return 0;

        $cmd = null;
        $cmdArgs = '';
        if (preg_match('~^/([a-zA-Z0-9_]+)(?:@([a-zA-Z0-9_]+))?(?:\s+(.*))?$~s', $text, $mm)) {
            if (empty($mm[2]) || strcasecmp($mm[2], $botUsername) === 0) {
                $cmd = strtolower($mm[1]);
                $cmdArgs = trim($mm[3] ?? '');
            }
        }

        $fired = 0;
        $flags = 0;
        foreach ($rules as $r) {
            $args = null;
            $v = $r['trigger_value'];
            switch ($r['trigger_type']) {
                case 'command':
                    if ($cmd !== null && $cmd === $v) $args = $cmdArgs;
                    break;
                case 'contains':
                    foreach (self::words($v) as $w) if (mb_stripos($text, $w) !== false) { $args = $text; break; }
                    break;
                case 'exact':
                    foreach (self::words($v) as $w) if (mb_strtolower(trim($text)) === mb_strtolower($w)) { $args = ''; break; }
                    break;
                case 'starts':
                    foreach (self::words($v) as $w) if (mb_stripos($text, $w) === 0) { $args = trim(mb_substr($text, mb_strlen($w))); break; }
                    break;
                case 'regex':
                    if (@preg_match(self::regex($v), $text, $rm)) $args = $rm[1] ?? $rm[0];
                    break;
            }
            if ($args === null) continue;
            if ($r['trigger_type'] !== 'command' && $cmd !== null) continue; // từ khoá không bắt lệnh

            $isCmd = $r['trigger_type'] === 'command';
            if (self::exec($api, $botTgId, $group, $msg, $member, $isAdmin, $r, $args, $isAdminFn, $isCmd)) $flags |= self::DELETED;
            if ($isCmd) return $flags | self::HANDLED;
            if (++$fired >= 3 || ($flags & self::DELETED)) break;
        }
        return $flags;
    }

    private static function exec(Telegram $api, int $botTgId, array $group, array $msg, array $member, bool $isAdmin, array $r, string $args, callable $isAdminFn, bool $isCmd): bool
    {
        $deleted = false;
        $c = json_decode($r['conditions'] ?: '{}', true) ?: [];
        $actions = json_decode($r['actions'] ?: '[]', true) ?: [];
        $chatId = $msg['chat']['id'];
        $gid = (int)$group['id'];
        $say = function (string $t) use ($api, $msg, $isCmd) {
            if ($isCmd) $api->trySend($msg['chat']['id'], $t, ['reply_parameters' => ['message_id' => $msg['message_id'], 'allow_sending_without_reply' => true]]);
        };

        // ---- NẾU: điều kiện ----
        $who = $c['who'] ?? 'all';
        if ($who === 'admin' && !$isAdmin) { $say('⛔ Chỉ quản trị viên dùng được lệnh này.'); return $deleted; }
        if ($who === 'member' && $isAdmin) return false;
        $minPts = (int)($c['min_points'] ?? 0);
        if ($minPts > 0 && (int)$member['points'] < $minPts) { $say("⭐ Cần ít nhất <b>$minPts</b> điểm để dùng lệnh này (bạn có {$member['points']})."); return $deleted; }

        $target = null;
        $targetMsg = $msg['reply_to_message'] ?? null;
        if ($targetMsg && isset($targetMsg['from']) && empty($targetMsg['forum_topic_created']) && $targetMsg['from']['id'] != $botTgId) {
            $target = Repo::upsertMember($gid, $targetMsg['from']);
        }
        if (!empty($c['require_reply']) && !$target) { $say('↩️ Hãy reply tin nhắn của một thành viên khi dùng lệnh này.'); return $deleted; }

        $cd = (int)($c['cooldown'] ?? 0);
        if ($cd > 0) {
            $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [$r['id'], $gid, $member['tg_user_id']]);
            $left = $last + $cd - now();
            if ($left > 0) { $say('⏳ Bạn cần đợi <b>' . self::human($left) . '</b> nữa.'); return $deleted; }
        }
        $chance = (int)($c['chance'] ?? 100);
        $lucky = $chance >= 100 || random_int(1, 100) <= $chance;

        // Ghi nhận lượt dùng (kể cả khi trượt tỉ lệ, để cooldown có tác dụng)
        if ($cd > 0) {
            if (!DB::run('UPDATE tg_rule_hits SET last_at = ? WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [now(), $r['id'], $gid, $member['tg_user_id']])) {
                DB::insert('tg_rule_hits', ['rule_id' => $r['id'], 'group_id' => $gid, 'tg_user_id' => $member['tg_user_id'], 'last_at' => now()]);
            }
        }
        DB::run('UPDATE tg_rules SET uses = uses + 1, last_used = ? WHERE id = ?', [now(), $r['id']]);
        if (!$lucky) { $say('🎲 Lần này không may rồi, thử lại sau nhé!'); return $deleted; }

        // ---- THÌ: hành động ----
        $actor = ($r['trigger_type'] === 'command' ? '/' . $r['trigger_value'] : $r['name']) . ' (lệnh tự tạo)';
        foreach ($actions as $a) {
            $isTarget = ($a['who'] ?? 'sender') === 'target';
            $subject = $isTarget ? $target : $member;
            try {
                switch ($a['type']) {
                    case 'reply':
                    case 'send':
                        $txt = self::vars($a['text'], $group, $member, $target, $args, $r);
                        $extra = [];
                        if ($a['type'] === 'reply') {
                            $replyTo = $isTarget && $targetMsg ? $targetMsg['message_id'] : $msg['message_id'];
                            $extra['reply_parameters'] = ['message_id' => $replyTo, 'allow_sending_without_reply' => true];
                        }
                        if (isset($msg['message_thread_id']) && !empty($msg['is_topic_message'])) $extra['message_thread_id'] = $msg['message_thread_id'];
                        $api->trySend($chatId, $txt, $extra);
                        break;
                    case 'delete':
                        $mid = $isTarget ? ($targetMsg['message_id'] ?? null) : $msg['message_id'];
                        if ($mid) $api->tryCall('deleteMessage', ['chat_id' => $chatId, 'message_id' => $mid]);
                        if (!$isTarget) $deleted = true;
                        break;
                    case 'pin':
                        $mid = $isTarget ? ($targetMsg['message_id'] ?? null) : $msg['message_id'];
                        if ($mid) $api->tryCall('pinChatMessage', ['chat_id' => $chatId, 'message_id' => $mid, 'disable_notification' => true]);
                        break;
                    case 'points':
                        if (!$subject) break;
                        if ($isTarget && $a['amount'] > 0 && $subject['tg_user_id'] == $member['tg_user_id']) {
                            $say('🙅 Không thể tự tặng điểm cho chính mình.');
                            return $deleted;
                        }
                        $cur = (int)DB::val('SELECT points FROM tg_members WHERE id = ?', [$subject['id']]);
                        DB::run('UPDATE tg_members SET points = ? WHERE id = ?', [max(0, $cur + (int)$a['amount']), $subject['id']]);
                        Repo::log($gid, $actor, 'points', $subject, ($a['amount'] > 0 ? '+' : '') . $a['amount']);
                        break;
                    default: // warn, mute, unmute, kick, ban, unban
                        if (!$subject || !in_array($a['type'], self::MOD_ACTIONS, true)) break;
                        if (in_array($a['type'], ['warn', 'mute', 'kick', 'ban'], true) && $isAdminFn((int)$subject['tg_user_id'])) {
                            $say('⛔ Không thể xử lý quản trị viên.');
                            break;
                        }
                        Moderation::perform($a['type'], $group, [
                            'tgUserId' => $subject['tg_user_id'],
                            'actor' => $actor,
                            'reason' => $a['reason'] ?? '',
                            'minutes' => $a['minutes'] ?? null,
                        ]);
                }
            } catch (Throwable $e) {
                $say('❌ ' . tg_esc($e->getMessage()));
            }
        }
        return $deleted;
    }

    public static function vars(string $text, array $group, array $m, ?array $target, string $args, array $rule): string
    {
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]) ?: $m;
        $rank = fn($x) => 1 + (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND points > ?', [$group['id'], $x['points']]);
        $map = [
            '{name}' => tg_esc(Repo::name($m)),
            '{mention}' => Repo::mention($m),
            '{username}' => $m['username'] ? '@' . tg_esc($m['username']) : tg_esc(Repo::name($m)),
            '{id}' => (string)$m['tg_user_id'],
            '{points}' => (string)$m['points'],
            '{rank}' => (string)$rank($m),
            '{warns}' => (string)$m['warns'],
            '{messages}' => (string)$m['messages'],
            '{group}' => tg_esc($group['title']),
            '{args}' => tg_esc($args),
            '{count}' => (string)((int)$rule['uses'] + 1),
            '{date}' => date('d/m/Y'),
            '{time}' => date('H:i'),
            '{random}' => (string)random_int(1, 100),
        ];
        if ($target) {
            $target = DB::get('SELECT * FROM tg_members WHERE id = ?', [$target['id']]) ?: $target;
            $map += [
                '{target_name}' => tg_esc(Repo::name($target)),
                '{target_mention}' => Repo::mention($target),
                '{target_points}' => (string)$target['points'],
                '{target_rank}' => (string)$rank($target),
            ];
        } else {
            $map += ['{target_name}' => '', '{target_mention}' => '', '{target_points}' => '', '{target_rank}' => ''];
        }
        return strtr($text, $map);
    }

    private static function human(int $s): string
    {
        if ($s >= 86400) return floor($s / 86400) . ' ngày ' . floor(($s % 86400) / 3600) . ' giờ';
        if ($s >= 3600) return floor($s / 3600) . ' giờ ' . floor(($s % 3600) / 60) . ' phút';
        if ($s >= 60) return floor($s / 60) . ' phút';
        return $s . ' giây';
    }

    /** Danh sách lệnh tự tạo để hiển thị trong /help */
    public static function helpLines(int $groupId, bool $isAdmin): array
    {
        $out = [];
        foreach (DB::all("SELECT * FROM tg_rules WHERE enabled = 1 AND trigger_type = 'command' AND (group_id IS NULL OR group_id = ?) ORDER BY trigger_value", [$groupId]) as $r) {
            $c = json_decode($r['conditions'] ?: '{}', true) ?: [];
            if (($c['who'] ?? 'all') === 'admin' && !$isAdmin) continue;
            $out[] = '/' . $r['trigger_value'] . ' – ' . tg_esc($r['name']);
        }
        return $out;
    }

    // ================= Menu lệnh Telegram =================
    /** Đồng bộ menu lệnh (nút "/" trong Telegram) cho mọi bot đang bật. */
    public static function syncMenus(): array
    {
        $errors = [];
        $rules = DB::all("SELECT * FROM tg_rules WHERE enabled = 1 AND show_in_menu = 1 AND trigger_type = 'command' ORDER BY trigger_value");
        $split = function (array $list) {
            $mem = [];
            $adm = [];
            foreach ($list as $r) {
                $c = json_decode($r['conditions'] ?: '{}', true) ?: [];
                $item = ['command' => $r['trigger_value'], 'description' => mb_substr($r['name'], 0, 250)];
                if (($c['who'] ?? 'all') !== 'admin') $mem[] = $item;
                $adm[] = $item;
            }
            return [$mem, $adm];
        };
        [$gMem, $gAdm] = $split(array_filter($rules, fn($r) => !$r['group_id']));

        foreach (DB::all('SELECT * FROM tg_bots WHERE enabled = 1') as $b) {
            $api = new Telegram($b['token']);
            $api->registerCommands($gMem, $gAdm);

            // Menu riêng cho các nhóm có lệnh riêng
            $adminOnly = Telegram::menuAdminOnly();
            $prev = json_decode((string)Settings::get('menu_chats_' . $b['id']), true) ?: [];
            $now = [];
            foreach (DB::all('SELECT * FROM tg_groups WHERE active = 1 AND (bot_id = ? OR bot_id IS NULL)', [$b['id']]) as $g) {
                $own = array_filter($rules, fn($r) => (int)$r['group_id'] === (int)$g['id']);
                if (!$own) continue;
                [$m, $a] = $split($own);
                [$baseM, $baseA] = Telegram::baseCommands();
                $chat = $g['chat_id'];
                if ($adminOnly) {
                    $api->tryCall('deleteMyCommands', ['scope' => ['type' => 'chat', 'chat_id' => $chat]]);
                } elseif (!$api->tryCall('setMyCommands', ['commands' => self::mergeCmds($baseM, $gMem, $m), 'scope' => ['type' => 'chat', 'chat_id' => $chat]])) {
                    $errors[] = $g['title'];
                }
                $api->tryCall('setMyCommands', ['commands' => self::mergeCmds($baseA, $gAdm, $a), 'scope' => ['type' => 'chat_administrators', 'chat_id' => $chat]]);
                $now[] = (string)$chat;
            }
            foreach (array_diff($prev, $now) as $chat) {
                $api->tryCall('deleteMyCommands', ['scope' => ['type' => 'chat', 'chat_id' => $chat]]);
                $api->tryCall('deleteMyCommands', ['scope' => ['type' => 'chat_administrators', 'chat_id' => $chat]]);
            }
            Settings::set('menu_chats_' . $b['id'], json_encode(array_values($now)));
        }
        return $errors;
    }

    private static function mergeCmds(array ...$lists): array
    {
        $out = [];
        foreach ($lists as $l) foreach ($l as $c) $out[$c['command']] = $c;
        return array_slice(array_values($out), 0, 100);
    }
}

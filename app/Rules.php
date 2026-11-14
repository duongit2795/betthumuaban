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
            // Tự động xoá
            'autodel' => !empty($c['autodel']),
            'autodel_trigger' => !empty($c['autodel_trigger']),
            'autodel_reply' => !empty($c['autodel_reply']),
            'autodel_target' => !empty($c['autodel_target']),
        ];
        // Thời gian xoá riêng cho từng loại tin (giây, tối đa 2 ngày)
        $common = (int)($c['autodel_after'] ?? 30);
        foreach (['trigger', 'reply', 'target'] as $k) {
            $cond["autodel_{$k}_after"] = max(0, min(172800, (int)($c["autodel_{$k}_after"] ?? $common)));
        }
        if ($cond['autodel'] && !$cond['autodel_trigger'] && !$cond['autodel_reply'] && !$cond['autodel_target']) {
            throw new HttpError('Tự động xoá: chọn ít nhất 1 loại tin cần xoá');
        }

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

    public static function run(Telegram $api, string $botUsername, int $botTgId, array $group, array $msg, array $member, bool $isAdmin, string $text, callable $isAdminFn, int $botId = 0): int
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
            if (self::exec($api, $botId, $botTgId, $group, $msg, $member, $isAdmin, $r, $args, $isAdminFn, $isCmd)) $flags |= self::DELETED;
            if ($isCmd) return $flags | self::HANDLED;
            if (++$fired >= 3 || ($flags & self::DELETED)) break;
        }
        return $flags;
    }

    private static function exec(Telegram $api, int $botId, int $botTgId, array $group, array $msg, array $member, bool $isAdmin, array $r, string $args, callable $isAdminFn, bool $isCmd): bool
    {
        $sent = [];
        $state = ['ran' => false, 'skip' => false];
        $deleted = self::execInner($api, $botTgId, $group, $msg, $member, $isAdmin, $r, $args, $isAdminFn, $isCmd, $sent, $state);

        // 🧹 Tự động xoá: lệnh /… luôn dọn (kể cả khi báo lỗi điều kiện); từ khoá chỉ dọn khi đã chạy
        $c = json_decode($r['conditions'] ?: '{}', true) ?: [];
        if (!empty($c['autodel']) && !$state['skip'] && ($isCmd || $state['ran'])) {
            $common = (int)($c['autodel_after'] ?? 30);
            $after = fn(string $k) => (int)($c["autodel_{$k}_after"] ?? $common);
            $chatId = $msg['chat']['id'];
            if (!empty($c['autodel_trigger']) && !$deleted) {
                DelQueue::add($api, $botId, $chatId, [$msg['message_id']], $after('trigger'));
                if ($after('trigger') === 0) $deleted = true;
            }
            if (!empty($c['autodel_reply'])) DelQueue::add($api, $botId, $chatId, $sent, $after('reply'));
            if (!empty($c['autodel_target']) && isset($msg['reply_to_message']['message_id'])) {
                DelQueue::add($api, $botId, $chatId, [$msg['reply_to_message']['message_id']], $after('target'));
            }
        }
        return $deleted;
    }

    private static function execInner(Telegram $api, int $botTgId, array $group, array $msg, array $member, bool $isAdmin, array $r, string $args, callable $isAdminFn, bool $isCmd, array &$sent, array &$state): bool
    {
        $deleted = false;
        $c = json_decode($r['conditions'] ?: '{}', true) ?: [];
        $actions = json_decode($r['actions'] ?: '[]', true) ?: [];
        $chatId = $msg['chat']['id'];
        $gid = (int)$group['id'];
        $say = function (string $t) use ($api, $msg, $isCmd, &$sent) {
            if (!$isCmd) return;
            $res = $api->trySend($msg['chat']['id'], $t, ['reply_parameters' => ['message_id' => $msg['message_id'], 'allow_sending_without_reply' => true]]);
            if (!empty($res['message_id'])) $sent[] = $res['message_id'];
        };

        // ---- NẾU: điều kiện ----
        $who = $c['who'] ?? 'all';
        if ($who === 'admin' && !$isAdmin) { $say('⛔ Chỉ quản trị viên dùng được lệnh này.'); return $deleted; }
        if ($who === 'member' && $isAdmin) { $state['skip'] = true; return false; }
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
        $state['ran'] = true;
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
                        $res = $api->trySend($chatId, $txt, $extra);
                        if (!empty($res['message_id'])) $sent[] = $res['message_id'];
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

    const WEEKDAYS = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];

    /** Thay biến {…} trong nội dung. Giá trị do người dùng nhập đều được escape. */
    public static function vars(string $text, array $group, array $m, ?array $target, string $args, array $rule): string
    {
        if (!str_contains($text, '{')) return $text;
        $need = fn(string ...$keys) => (bool)array_filter($keys, fn($k) => str_contains($text, $k));
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$m['id']]) ?: $m;
        $s = Repo::settings($group);
        $rank = fn($x) => 1 + (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND points > ?', [$group['id'], $x['points']]);
        $user = function (array $x, string $p) use ($rank, $s, $need) {
            $out = [
                "{{$p}name}" => tg_esc(Repo::name($x)),
                "{{$p}first_name}" => tg_esc($x['first_name'] ?? ''),
                "{{$p}last_name}" => tg_esc($x['last_name'] ?? ''),
                "{{$p}mention}" => Repo::mention($x),
                "{{$p}username}" => !empty($x['username']) ? '@' . tg_esc($x['username']) : tg_esc(Repo::name($x)),
                "{{$p}id}" => (string)$x['tg_user_id'],
                "{{$p}points}" => (string)$x['points'],
                "{{$p}messages}" => (string)$x['messages'],
                "{{$p}warns}" => (string)$x['warns'],
                "{{$p}role}" => !empty($x['is_admin']) ? 'Quản trị viên' : 'Thành viên',
                "{{$p}joined}" => !empty($x['joined_at']) ? date('d/m/Y', (int)$x['joined_at']) : '—',
                "{{$p}days}" => !empty($x['joined_at']) ? (string)max(0, (int)floor((now() - (int)$x['joined_at']) / 86400)) : '0',
            ];
            if ($need("{{$p}rank}")) $out["{{$p}rank}"] = (string)$rank($x);
            return $out;
        };

        $map = $user($m, '');
        $map['{fullname}'] = $map['{name}'];
        $map['{warn_limit}'] = (string)$s['warn_limit'];
        $map['{warns_left}'] = (string)max(0, (int)$s['warn_limit'] - (int)$m['warns']);

        // Người được reply
        if ($target) {
            $target = DB::get('SELECT * FROM tg_members WHERE id = ?', [$target['id']]) ?: $target;
            $map += $user($target, 'target_');
        } else {
            foreach (['name', 'first_name', 'last_name', 'mention', 'username', 'id', 'points', 'messages', 'warns', 'role', 'joined', 'days', 'rank'] as $k) $map["{target_$k}"] = '';
        }

        // Nhóm
        $map['{group}'] = tg_esc($group['title']);
        $map['{group_id}'] = (string)$group['chat_id'];
        $map['{group_link}'] = $group['username'] ? 'https://t.me/' . tg_esc($group['username']) : '';
        $map['{member_count}'] = (string)$group['member_count'];
        $map['{rules}'] = (string)($s['rules_text'] ?? '');
        if ($need('{messages_today}')) $map['{messages_today}'] = (string)(int)DB::val('SELECT messages FROM daily_stats WHERE group_id = ? AND day = ?', [$group['id'], date('Y-m-d')]);
        if ($need('{top1}', '{top1_points}', '{top3}')) {
            $top = DB::all("SELECT * FROM tg_members WHERE group_id = ? AND is_bot = 0 AND status <> 'banned' ORDER BY points DESC, messages DESC LIMIT 3", [$group['id']]);
            $map['{top1}'] = $top ? tg_esc(Repo::name($top[0])) : '—';
            $map['{top1_points}'] = $top ? (string)$top[0]['points'] : '0';
            $medals = ['🥇', '🥈', '🥉'];
            $map['{top3}'] = implode("\n", array_map(fn($x, $i) => $medals[$i] . ' ' . tg_esc(Repo::name($x)) . ' — ' . $x['points'] . ' điểm', $top, array_keys($top)));
        }
        if ($need('{bot}')) {
            $b = $group['bot_id'] ? DB::val('SELECT username FROM tg_bots WHERE id = ?', [$group['bot_id']]) : null;
            $map['{bot}'] = $b ? '@' . tg_esc($b) : '';
        }

        // Thời gian (giờ Việt Nam)
        $map['{date}'] = date('d/m/Y');
        $map['{time}'] = date('H:i');
        $map['{datetime}'] = date('H:i d/m/Y');
        $map['{weekday}'] = self::WEEKDAYS[(int)date('w')];
        $map['{day}'] = date('d');
        $map['{month}'] = date('m');
        $map['{year}'] = date('Y');

        // Phần chữ gõ sau lệnh
        $words = preg_split('/\s+/u', trim($args), -1, PREG_SPLIT_NO_EMPTY);
        $map['{args}'] = tg_esc($args);
        for ($k = 1; $k <= 3; $k++) $map["{arg$k}"] = tg_esc($words[$k - 1] ?? '');

        // Lệnh
        $map['{count}'] = (string)((int)$rule['uses'] + 1);
        $map['{command}'] = $rule['trigger_type'] === 'command' ? '/' . $rule['trigger_value'] : tg_esc($rule['name']);
        $map['{random}'] = (string)random_int(1, 100);

        // {random:1-6} và {choice:a|b|c}
        $text = preg_replace_callback('/\{random:(-?\d+)-(-?\d+)\}/', function ($x) {
            [$a, $b] = [(int)$x[1], (int)$x[2]];
            return (string)random_int(min($a, $b), max($a, $b));
        }, $text);
        $text = preg_replace_callback('/\{choice:([^{}]*)\}/u', function ($x) {
            $opts = array_values(array_filter(array_map('trim', explode('|', $x[1])), 'strlen'));
            return $opts ? $opts[random_int(0, count($opts) - 1)] : '';
        }, $text);

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

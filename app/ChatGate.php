<?php
defined('APP') or exit;

/**
 * Điều kiện nhắn tin giữa các nhóm. VD: muốn chat ở nhóm B phải có ≥ 50 điểm ở nhóm A.
 * Chưa đạt → xoá tin + nhắc (giới hạn tần suất, tự xoá). Danh sách quy tắc lưu ở Settings 'chat_gates'.
 */
class ChatGate
{
    const METRICS = [
        'points' => ['Điểm tương tác', 'điểm'],
        'messages' => ['Số tin nhắn', 'tin nhắn'],
        'member' => ['Là thành viên', ''],
    ];
    const HIT_RULE_ID = -3; // tg_rule_hits: giới hạn tần suất nhắc (tg_rule_hits.rule_id = -3 - id quy tắc)

    const DEFAULT_TEXT = "🔒 {mention}, để nhắn tin trong nhóm này bạn cần ít nhất <b>{need}</b> {unit} ở nhóm <b>{source}</b>.\nBạn đang có <b>{have}</b>, còn thiếu <b>{missing}</b>. Hãy tương tác ở nhóm {source} để tích thêm nhé!";
    const DEFAULT_TEXT_MEMBER = "🔒 {mention}, bạn cần <b>tham gia nhóm {source}</b> trước khi nhắn tin ở đây. Vào nhóm rồi quay lại chat nhé!";

    public static function all(): array
    {
        $list = json_decode((string)Settings::get('chat_gates'), true);
        return is_array($list) ? array_values($list) : [];
    }

    private static function store(array $list): void
    {
        Settings::set('chat_gates', json_encode(array_values($list), JSON_UNESCAPED_UNICODE));
    }

    public static function save(array $in): array
    {
        $list = self::all();
        $id = (int)($in['id'] ?? 0);
        $gid = (int)($in['group_id'] ?? 0);
        $src = (int)($in['source_id'] ?? 0);
        if (!$gid || !Repo::group($gid)) throw new HttpError('Chọn nhóm áp dụng (nhóm cần điều kiện mới được chat)');
        if (!$src || !Repo::group($src)) throw new HttpError('Chọn nhóm dùng để tính điều kiện');
        $metric = isset(self::METRICS[$in['metric'] ?? '']) ? $in['metric'] : 'points';
        if ($metric === 'member' && $src === $gid) throw new HttpError('Điều kiện "là thành viên" phải chọn nhóm khác');
        $min = max(0, min(100000000, (int)($in['min'] ?? 0)));
        if ($metric !== 'member' && $min < 1) throw new HttpError('Nhập mức tối thiểu (lớn hơn 0)');
        $btnUrl = trim((string)($in['button_url'] ?? ''));
        if (str_starts_with($btnUrl, '@')) $btnUrl = 'https://t.me/' . substr($btnUrl, 1);
        if ($btnUrl !== '' && !preg_match('~^(https?://|tg://)\S+$~i', $btnUrl)) throw new HttpError('Link nút không hợp lệ (https://… hoặc @username)');
        $text = trim(mb_substr((string)($in['text'] ?? ''), 0, 2000));
        $rule = [
            'id' => $id ?: (max(array_merge([0], array_column($list, 'id'))) + 1),
            'enabled' => !empty($in['enabled']),
            'group_id' => $gid,
            'source_id' => $src,
            'metric' => $metric,
            'min' => $metric === 'member' ? 0 : $min,
            'exempt_admins' => !array_key_exists('exempt_admins', $in) || !empty($in['exempt_admins']),
            'cooldown' => max(0, min(86400, (int)($in['cooldown'] ?? 60))),
            'autodel' => max(0, min(172800, (int)($in['autodel'] ?? 60))),
            'text' => $text,
            'button_text' => trim(mb_substr((string)($in['button_text'] ?? ''), 0, 40)),
            'button_url' => $btnUrl,
        ];
        $found = false;
        foreach ($list as $i => $r) if ((int)$r['id'] === $rule['id']) { $list[$i] = $rule; $found = true; }
        if (!$found) {
            if (count($list) >= 50) throw new HttpError('Tối đa 50 quy tắc');
            $list[] = $rule;
        }
        self::store($list);
        return $rule;
    }

    public static function delete(int $id): void
    {
        self::store(array_filter(self::all(), fn($r) => (int)$r['id'] !== $id));
    }

    /** Giá trị hiện có của 1 người theo quy tắc */
    public static function value(array $rule, $tgUserId): int
    {
        $m = DB::get('SELECT points, messages, status FROM tg_members WHERE group_id = ? AND tg_user_id = ?', [$rule['source_id'], $tgUserId]);
        if (!$m) return 0;
        if ($rule['metric'] === 'member') return in_array($m['status'], ['member', 'muted'], true) ? 1 : 0;
        return (int)$m[$rule['metric']];
    }

    public static function passes(array $rule, $tgUserId): bool
    {
        $v = self::value($rule, $tgUserId);
        return $rule['metric'] === 'member' ? $v > 0 : $v >= (int)$rule['min'];
    }

    /** Link mặc định của nhóm nguồn (nếu nhóm công khai) */
    private static function sourceLink(array $src): ?string
    {
        return !empty($src['username']) ? 'https://t.me/' . $src['username'] : null;
    }

    /**
     * Gọi khi thành viên nhắn tin trong nhóm.
     * @return bool true nếu tin bị xoá (dừng xử lý tiếp)
     */
    public static function check(Telegram $api, int $botId, array $group, array $user, array $msg, bool $isAdmin): bool
    {
        if (!empty($user['is_bot'])) return false;
        $gid = (int)$group['id'];
        foreach (self::all() as $rule) {
            if (empty($rule['enabled']) || (int)$rule['group_id'] !== $gid) continue;
            if ($isAdmin && !empty($rule['exempt_admins'])) continue;
            if (self::passes($rule, $user['id'])) continue;
            $src = Repo::group((int)$rule['source_id']);
            if (!$src) continue;

            // Không đạt → xoá tin
            $api->tryCall('deleteMessage', ['chat_id' => $group['chat_id'], 'message_id' => $msg['message_id']]);
            $have = self::value($rule, $user['id']);
            $unit = self::METRICS[$rule['metric']][1];
            ChatLog::markDeleted($group['chat_id'], $msg['message_id'], $rule['metric'] === 'member' ? 'Chưa vào nhóm ' . $src['title'] : "Chưa đủ {$rule['min']} $unit ở {$src['title']}");

            // Nhắc (giới hạn tần suất mỗi người)
            $hitId = self::HIT_RULE_ID - (int)$rule['id'];
            if ((int)$rule['cooldown'] > 0) {
                $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [$hitId, $gid, $user['id']]);
                if (now() - $last < (int)$rule['cooldown']) return true;
            }
            DB::run('DELETE FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [$hitId, $gid, $user['id']]);
            DB::insert('tg_rule_hits', ['rule_id' => $hitId, 'group_id' => $gid, 'tg_user_id' => $user['id'], 'last_at' => now()]);

            $m = Repo::member($gid, $user['id']) ?: Repo::upsertMember($gid, $user);
            Repo::log($gid, 'Bot', 'gate', $m, $rule['metric'] === 'member' ? 'Chưa vào nhóm ' . $src['title'] : "Có $have/{$rule['min']} $unit ở {$src['title']}",
                ['source' => 'bot', 'reason' => 'Điều kiện nhắn tin'], Moderation::evidence($msg));
            $tpl = $rule['text'] !== '' ? $rule['text'] : ($rule['metric'] === 'member' ? self::DEFAULT_TEXT_MEMBER : self::DEFAULT_TEXT);
            $text = strtr($tpl, [
                '{mention}' => Repo::mention($m), '{name}' => tg_esc(Repo::name($m)),
                '{need}' => number_format((int)$rule['min'], 0, ',', '.'), '{have}' => number_format($have, 0, ',', '.'),
                '{missing}' => number_format(max(0, (int)$rule['min'] - $have), 0, ',', '.'), '{unit}' => $unit,
                '{source}' => tg_esc($src['title']), '{group}' => tg_esc($group['title']),
            ]);
            $url = $rule['button_url'] ?: self::sourceLink($src);
            $extra = $url ? ['reply_markup' => ['inline_keyboard' => [[['text' => $rule['button_text'] ?: '👉 Vào nhóm ' . mb_substr($src['title'], 0, 25), 'url' => $url]]]]] : [];
            $sent = $api->trySend($group['chat_id'], $text, $extra);
            if ($sent && !empty($sent['message_id']) && (int)$rule['autodel'] > 0) DelQueue::add($api, $botId, $group['chat_id'], [$sent['message_id']], (int)$rule['autodel']);
            return true;
        }
        return false;
    }
}

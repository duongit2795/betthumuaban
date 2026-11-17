<?php
defined('APP') or exit;

/**
 * Điểm danh hằng ngày trên web: thành viên chọn nhóm → nhận điểm tương tác của nhóm đó (1 lần / ngày / nhóm).
 * Điểm danh nhiều ngày liên tiếp được thưởng thêm. Cấu hình lưu ở Settings 'checkin'.
 */
class Checkin
{
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],          // rỗng = mọi nhóm
        'points' => 5,           // điểm cơ bản mỗi lần
        'streak_bonus' => 1,     // thưởng thêm mỗi ngày liên tiếp
        'streak_max' => 7,       // tối đa số ngày được cộng thưởng
        'notify' => false,       // báo vào nhóm
        'notify_autodel' => 60,
        'notify_text' => "📅 {mention} vừa điểm danh: <b>+{points} điểm</b> · chuỗi {streak} ngày 🔥\nTổng: {total} điểm",
        'cmd_autodel' => 60,     // lệnh /diemdanh trong nhóm: tự xoá lệnh + tin trả lời sau N giây (0 = không)
    ];

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('checkin'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['enabled', 'notify'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['points'])) $c['points'] = max(1, min(100000, (int)$in['points']));
        if (isset($in['streak_bonus'])) $c['streak_bonus'] = max(0, min(100000, (int)$in['streak_bonus']));
        if (isset($in['streak_max'])) $c['streak_max'] = max(0, min(365, (int)$in['streak_max']));
        if (isset($in['notify_autodel'])) $c['notify_autodel'] = max(0, min(172800, (int)$in['notify_autodel']));
        if (isset($in['cmd_autodel'])) $c['cmd_autodel'] = max(0, min(172800, (int)$in['cmd_autodel']));
        if (isset($in['notify_text'])) {
            $t = trim(mb_substr((string)$in['notify_text'], 0, 1000));
            $c['notify_text'] = $t !== '' ? $t : self::DEFAULTS['notify_text'];
        }
        Settings::set('checkin', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    public static function applies(array $c, int $groupId): bool
    {
        return !$c['groups'] || in_array($groupId, $c['groups'], true);
    }

    /** Điểm nhận được ở ngày thứ $streak của chuỗi */
    public static function reward(array $c, int $streak): int
    {
        return (int)$c['points'] + min(max(0, $streak - 1), (int)$c['streak_max']) * (int)$c['streak_bonus'];
    }

    /** Chuỗi hiện tại (tính tới hôm qua / hôm nay) */
    public static function streak($tgUserId, int $groupId): array
    {
        $last = DB::get('SELECT day, streak FROM checkins WHERE tg_user_id = ? AND group_id = ? ORDER BY day DESC LIMIT 1', [$tgUserId, $groupId]);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if (!$last) return ['today' => false, 'streak' => 0];
        if ($last['day'] === $today) return ['today' => true, 'streak' => (int)$last['streak']];
        if ($last['day'] === $yesterday) return ['today' => false, 'streak' => (int)$last['streak']];
        return ['today' => false, 'streak' => 0];
    }

    /** Điểm danh. Trả về [points, streak, total] */
    /** Điểm danh từ web (tài khoản đã liên kết Telegram) */
    public static function perform(array $u, int $groupId): array
    {
        if (!$u['tg_user_id']) throw new HttpError('Hãy liên kết Telegram ở trang Tài khoản trước khi điểm danh');
        $group = Repo::group($groupId);
        if (!$group || !$group['active']) throw new HttpError('Không tìm thấy nhóm', 404);
        $m = Repo::member($groupId, $u['tg_user_id']);
        if (!$m || !in_array($m['status'], ['member', 'muted'], true)) throw new HttpError('Bạn cần đang ở trong nhóm này mới điểm danh được');
        return self::run($group, $m, 'web');
    }

    /**
     * Lõi điểm danh cho 1 thành viên (web hoặc lệnh /diemdanh trong nhóm).
     * Lỗi "đã điểm danh" có mã 409 để lệnh trong nhóm báo nhẹ nhàng.
     */
    public static function run(array $group, array $m, string $source = 'web'): array
    {
        $c = self::config();
        if (!$c['enabled']) throw new HttpError('Tính năng điểm danh đang tắt');
        $groupId = (int)$group['id'];
        if (!self::applies($c, $groupId)) throw new HttpError('Nhóm này không áp dụng điểm danh');
        $st = self::streak($m['tg_user_id'], $groupId);
        if ($st['today']) throw new HttpError('Hôm nay bạn đã điểm danh nhóm này rồi, quay lại vào ngày mai nhé!', 409);
        $streak = $st['streak'] + 1;
        $pts = self::reward($c, $streak);
        try {
            DB::insert('checkins', ['tg_user_id' => $m['tg_user_id'], 'group_id' => $groupId, 'day' => date('Y-m-d'), 'points' => $pts, 'streak' => $streak, 'created_at' => now()]);
        } catch (PDOException $e) {
            throw new HttpError('Hôm nay bạn đã điểm danh nhóm này rồi', 409);
        }
        DB::run('UPDATE tg_members SET points = points + ? WHERE id = ?', [$pts, $m['id']]);
        $total = (int)DB::val('SELECT points FROM tg_members WHERE id = ?', [$m['id']]);
        MemberTag::auto($group, $m['tg_user_id']);
        Repo::log($groupId, Repo::name($m) . ($source === 'web' ? ' (web)' : ' (Telegram)'), 'checkin', $m, "+$pts điểm · chuỗi $streak ngày" . ($source === 'web' ? '' : ' · /diemdanh'),
            ['source' => $source === 'web' ? 'web' : 'telegram', 'amount' => $pts, 'before' => $total - $pts, 'after' => $total, 'streak' => $streak]);
        if ($source === 'web' && $c['notify'] && ($api = Repo::apiForGroup($group))) {
            $sent = $api->trySend($group['chat_id'], strtr($c['notify_text'], ['{mention}' => Repo::mention($m), '{name}' => tg_esc(Repo::name($m)),
                '{points}' => (string)$pts, '{streak}' => (string)$streak, '{total}' => (string)$total, '{group}' => tg_esc($group['title'])]));
            if ($sent && !empty($sent['message_id']) && $c['notify_autodel'] > 0 && $group['bot_id']) {
                DelQueue::add($api, (int)$group['bot_id'], $group['chat_id'], [$sent['message_id']], (int)$c['notify_autodel']);
            }
        }
        return ['points' => $pts, 'streak' => $streak, 'total' => $total, 'next' => self::reward($c, $streak + 1)];
    }
}

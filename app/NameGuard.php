<?php
defined('APP') or exit;

/**
 * Cấm tên chứa từ cấm: thành viên có tên / @username chứa từ cấm (VD "Admin", "Hỗ trợ", "CSKH")
 * hoặc đặt tên trùng quản trị viên → cấm / đuổi / cấm chat ngay khi vào nhóm, khi nhắn tin, từ chối yêu cầu tham gia.
 * So khớp thông minh: bỏ dấu, không phân biệt hoa thường, bỏ khoảng trắng / ký tự chen giữa (A.d.m.i.n),
 * chữ kiểu (𝐀𝐝𝐦𝐢𝐧), chữ Kirin giả Latin (Аdmin), số thay chữ (4dm1n).
 * Cấu hình: Settings 'name_guard'.
 */
class NameGuard
{
    const ACTIONS = ['ban' => 'Cấm khỏi nhóm (vĩnh viễn)', 'kick' => 'Đuổi khỏi nhóm (vào lại được)', 'mute' => 'Cấm chat'];
    const SUGGEST = ['admin', 'administrator', 'quản trị', 'support', 'hỗ trợ', 'cskh', 'chăm sóc khách hàng', 'telegram', 'official', 'airdrop', 'kiếm tiền online', 'việc làm online', 'tuyển ctv', 'vay tiền'];
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],
        'words' => [],
        'smart' => true,           // so khớp thông minh (chống lách)
        'check_name' => true,
        'check_username' => true,
        'impersonate' => false,    // tên trùng / giả quản trị viên của nhóm
        'action' => 'ban',
        'mute_minutes' => 0,       // 0 = vĩnh viễn
        'on_join' => true,
        'on_message' => true,      // người đổi tên sau khi vào / thành viên cũ
        'on_request' => true,      // từ chối yêu cầu tham gia
        'delete_msg' => true,      // xoá tin nhắn / tin "đã tham gia"
        'notify' => true,          // báo vào nhóm
        'allow_ids' => [],         // bỏ qua các ID này
    ];
    const HIT_RULE_ID = -6; // tg_rule_hits: chống xử lý 2 lần (tin "đã vào" + chat_member)

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('name_guard'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['enabled', 'smart', 'check_name', 'check_username', 'impersonate', 'on_join', 'on_message', 'on_request', 'delete_msg', 'notify'] as $k) {
            if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        }
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['action']) && isset(self::ACTIONS[$in['action']])) $c['action'] = $in['action'];
        if (isset($in['mute_minutes'])) $c['mute_minutes'] = max(0, min(525600, (int)$in['mute_minutes']));
        if (isset($in['words'])) {
            $list = is_array($in['words']) ? $in['words'] : preg_split('/[\r\n,]+/u', (string)$in['words']);
            $out = [];
            foreach ($list as $w) {
                $w = trim(mb_substr((string)$w, 0, 60));
                if ($w !== '' && !in_array(mb_strtolower($w), array_map('mb_strtolower', $out), true)) $out[] = $w;
            }
            $c['words'] = array_slice($out, 0, 500);
        }
        if (isset($in['allow_ids'])) {
            preg_match_all('/\d{3,20}/', is_array($in['allow_ids']) ? implode(' ', $in['allow_ids']) : (string)$in['allow_ids'], $mm);
            $c['allow_ids'] = array_values(array_unique(array_map('strval', $mm[0])));
        }
        if ($c['enabled'] && !$c['words'] && !$c['impersonate']) throw new HttpError('Nhập ít nhất 1 từ cấm (hoặc bật chống giả tên quản trị viên)');
        Settings::set('name_guard', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    public static function applies(array $c, array $group): bool
    {
        return $c['enabled'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    /** Chuẩn hoá để so khớp. smart: bỏ dấu, chữ kiểu, Kirin giả Latin, số thay chữ, bỏ mọi ký tự không phải chữ / số */
    public static function norm(string $s, bool $smart): string
    {
        if (!$smart) return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
        if (class_exists('Normalizer')) $s = (string)Normalizer::normalize($s, Normalizer::FORM_KC); // 𝐀𝐝𝐦𝐢𝐧, ａｄｍｉｎ → Admin
        $s = mb_strtolower($s);
        $s = strtr($s, ['đ' => 'd', 'ð' => 'd',
            // Kirin / Hy Lạp trông giống Latin
            'а' => 'a', 'в' => 'b', 'е' => 'e', 'ё' => 'e', 'к' => 'k', 'м' => 'm', 'н' => 'h', 'о' => 'o', 'р' => 'p', 'с' => 'c', 'т' => 't', 'у' => 'y', 'х' => 'x', 'і' => 'i', 'ї' => 'i', 'ј' => 'j', 'ѕ' => 's', 'ԁ' => 'd', 'ո' => 'n',
            'α' => 'a', 'β' => 'b', 'ε' => 'e', 'ι' => 'i', 'κ' => 'k', 'ν' => 'v', 'ο' => 'o', 'ρ' => 'p', 'τ' => 't', 'υ' => 'u', 'χ' => 'x',
            // số / ký hiệu thay chữ
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '!' => 'i', '|' => 'i']);
        if (class_exists('Normalizer')) $s = preg_replace('/\p{Mn}+/u', '', (string)Normalizer::normalize($s, Normalizer::FORM_D)); // bỏ dấu tiếng Việt
        else $s = strtr($s, self::viMap());
        $s = str_replace('l', 'i', $s); // l ↔ I (adrnin / AdmIn khó phân biệt)
        return preg_replace('/[^a-z0-9]+/', '', $s);
    }

    /** Tách chữ (theo khoảng trắng / ký tự chen giữa / chữ hoa giữa từ "BạnAdmin") → [chuỗi đã chuẩn hoá, các vị trí ranh giới] */
    private static function tokens(string $s): array
    {
        if (class_exists('Normalizer')) $s = (string)Normalizer::normalize($s, Normalizer::FORM_KC);
        $parts = preg_split('/[^\p{L}\p{N}@$!|]+|(?<=\p{Ll})(?=\p{Lu})|(?<=\p{L})(?=\p{N})|(?<=\p{N})(?=\p{L})/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $str = ''; $cuts = [0 => true];
        foreach ($parts as $p) {
            $n = self::norm($p, true);
            if ($n === '') continue;
            $str .= $n; $cuts[strlen($str)] = true;
        }
        return [$str, $cuts];
    }

    /**
     * Từ cấm có nằm trong tên? (chế độ thông minh)
     * Âm tiết ngắn (≤ 3 chữ) ở đầu / cuối từ cấm phải khớp trọn âm tiết: "hỗ trợ" không bắt nhầm "Hồ Trọng".
     */
    private static function smartHas(array $hay, string $word): bool
    {
        [$h, $cuts] = $hay;
        $wt = array_values(array_filter(array_map(fn($p) => self::norm($p, true), preg_split('/\s+/u', trim($word))), 'strlen'));
        $w = implode('', $wt);
        if ($w === '' || $h === '') return false;
        $needStart = strlen($wt[0]) <= 3;
        $needEnd = strlen(end($wt)) <= 3;
        for ($pos = strpos($h, $w); $pos !== false; $pos = strpos($h, $w, $pos + 1)) {
            if ((!$needStart || isset($cuts[$pos])) && (!$needEnd || isset($cuts[$pos + strlen($w)]))) return true;
        }
        return false;
    }

    private static function viMap(): array
    {
        static $m = null;
        if ($m) return $m;
        $m = [];
        foreach (['a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ', 'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ'] as $to => $chars) {
            foreach (mb_str_split($chars) as $ch) $m[$ch] = $to;
        }
        return $m;
    }

    /**
     * Tên người này có vi phạm? Trả về lý do (VD 'Tên chứa từ cấm “admin”') hoặc null.
     */
    public static function match(array $c, array $user, ?array $group = null): ?string
    {
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $uname = (string)($user['username'] ?? '');
        $hay = [];
        $prep = fn($v) => $c['smart'] ? self::tokens($v) : self::norm($v, false);
        if ($c['check_name'] && $name !== '') $hay['Tên'] = $prep($name);
        if ($c['check_username'] && $uname !== '') $hay['Username'] = $prep(str_replace('_', ' ', $uname));
        foreach ($c['words'] as $w) {
            $w = (string)$w;
            foreach ($hay as $label => $h) {
                $hit = $c['smart'] ? self::smartHas($h, $w) : (($nw = self::norm($w, false)) !== '' && str_contains($h, $nw));
                if ($hit) return $label . ' chứa từ cấm “' . $w . '”';
            }
        }
        if ($c['impersonate'] && $group) {
            $me = self::norm($name, true);
            $meU = self::norm($uname, true);
            foreach (DB::all('SELECT tg_user_id, first_name, last_name, username FROM tg_members WHERE group_id = ? AND is_admin = 1', [$group['id']]) as $a) {
                if ((string)$a['tg_user_id'] === (string)$user['id']) continue;
                $an = self::norm(trim($a['first_name'] . ' ' . $a['last_name']), true);
                $au = self::norm((string)$a['username'], true);
                $label = trim($a['first_name'] . ' ' . $a['last_name']) ?: '@' . $a['username'];
                if (mb_strlen($an) >= 3 && ($me === $an || $meU === $an)) return 'Giả tên quản trị viên “' . $label . '”';
                if (mb_strlen($au) >= 4 && ($me === $au || $meU === $au || str_contains($me, $au) || str_contains($meU, $au))) return 'Giả tên quản trị viên “' . $label . '”';
            }
        }
        return null;
    }

    /** Có được bỏ qua người này không */
    private static function exempt(array $c, array $user, bool $isAdmin): bool
    {
        return $isAdmin || !empty($user['is_bot']) || in_array((string)$user['id'], $c['allow_ids'], true);
    }

    /**
     * Kiểm tra 1 người (vào nhóm / nhắn tin). Vi phạm → xử lý.
     * @param string $when 'join' | 'message'
     * @return bool true nếu đã xử lý (dừng xử lý tiếp)
     */
    public static function check(Telegram $api, array $group, array $user, bool $isAdmin, string $when, ?array $msg = null): bool
    {
        $c = self::config();
        if (!self::applies($c, $group) || self::exempt($c, $user, $isAdmin)) return false;
        if (($when === 'join' && !$c['on_join']) || ($when === 'message' && !$c['on_message'])) return false;
        $why = self::match($c, $user, $group);
        if (!$why) return false;
        if ($msg && $c['delete_msg']) $api->tryCall('deleteMessage', ['chat_id' => $group['chat_id'], 'message_id' => $msg['message_id']]);
        $gid = (int)$group['id'];
        // Cùng 1 lần vào nhóm Telegram gửi 2 cập nhật → chỉ xử lý 1 lần / 30 giây
        $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [self::HIT_RULE_ID, $gid, $user['id']]);
        if (now() - $last < 30) return true;
        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = ? AND group_id = ? AND tg_user_id = ?', [self::HIT_RULE_ID, $gid, $user['id']]);
        DB::insert('tg_rule_hits', ['rule_id' => self::HIT_RULE_ID, 'group_id' => $gid, 'tg_user_id' => $user['id'], 'last_at' => now()]);
        Repo::upsertMember($gid, $user);
        self::punish($group, $user['id'], $why, $c, $msg);
        return true;
    }

    private static function punish(array $group, $tgUserId, string $why, array $c, ?array $msg = null): void
    {
        try {
            Moderation::perform($c['action'], $group, [
                'tgUserId' => $tgUserId, 'actor' => 'Bot', 'source' => 'bot', 'reason' => $why, 'notify' => $c['notify'],
                'minutes' => $c['mute_minutes'], 'meta' => ['name_guard' => 1], 'evidence' => $msg ? Moderation::evidence($msg) : null,
            ]);
        } catch (Throwable $e) {
            Settings::set('name_guard_error', mb_substr($e->getMessage(), 0, 200) . ' · ' . date('H:i d/m'));
        }
    }

    /** Yêu cầu tham gia: tên vi phạm → từ chối luôn. @return bool true nếu đã từ chối */
    public static function onRequest(Telegram $api, array $group, array $req): bool
    {
        $c = self::config();
        $u = $req['from'];
        if (!self::applies($c, $group) || !$c['on_request'] || self::exempt($c, $u, false)) return false;
        $why = self::match($c, $u, $group);
        if (!$why) return false;
        $api->tryCall('declineChatJoinRequest', ['chat_id' => $group['chat_id'], 'user_id' => $u['id']]);
        $gid = (int)$group['id'];
        $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        DB::run("DELETE FROM join_requests WHERE group_id = ? AND tg_user_id = ? AND status = 'pending'", [$gid, $u['id']]);
        DB::insert('join_requests', ['group_id' => $gid, 'tg_user_id' => $u['id'], 'name' => mb_substr($name, 0, 190), 'username' => $u['username'] ?? null,
            'bio' => isset($req['bio']) ? mb_substr($req['bio'], 0, 250) : null, 'status' => 'declined', 'created_at' => now(), 'decided_at' => now(), 'decided_by' => 'Bot (tên cấm)']);
        Repo::log($gid, 'Bot', 'name_ban', ['tg_user_id' => $u['id'], 'first_name' => $u['first_name'] ?? '', 'last_name' => $u['last_name'] ?? null, 'username' => $u['username'] ?? null],
            'Từ chối yêu cầu tham gia · ' . $why, ['source' => 'bot']);
        return true;
    }

    /** Quét thành viên đang có trong nhóm (đã lưu) tìm tên vi phạm */
    public static function scan(array $c, ?int $groupId = null): array
    {
        $where = "m.status IN ('member', 'muted') AND m.is_admin = 0" . ($groupId ? ' AND m.group_id = ' . (int)$groupId : '');
        $out = [];
        foreach (DB::all("SELECT m.id, m.group_id, m.tg_user_id, m.first_name, m.last_name, m.username, m.status, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND $where ORDER BY m.id DESC LIMIT 20000") as $m) {
            if ($c['groups'] && !in_array((int)$m['group_id'], $c['groups'], true)) continue;
            if (in_array((string)$m['tg_user_id'], $c['allow_ids'], true)) continue;
            $why = self::match($c, ['id' => $m['tg_user_id'], 'first_name' => $m['first_name'], 'last_name' => $m['last_name'], 'username' => $m['username']], ['id' => $m['group_id']]);
            if ($why) $out[] = ['id' => (int)$m['id'], 'group_id' => (int)$m['group_id'], 'group' => $m['title'], 'tg_user_id' => (string)$m['tg_user_id'],
                'name' => trim($m['first_name'] . ' ' . $m['last_name']), 'username' => $m['username'], 'status' => $m['status'], 'why' => $why];
            if (count($out) >= 300) break;
        }
        return $out;
    }

    /** Xử lý các thành viên được chọn sau khi quét */
    public static function punishMembers(array $ids, ?array $draft = null): array
    {
        $c = self::config();
        if ($draft) $c = array_merge($c, array_intersect_key($draft, array_flip(['words', 'smart', 'check_name', 'check_username', 'impersonate', 'allow_ids'])));
        $ok = 0; $err = [];
        foreach (array_slice(array_map('intval', $ids), 0, 300) as $id) {
            $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$id]);
            $group = $m ? Repo::group((int)$m['group_id']) : null;
            if (!$m || !$group) continue;
            $why = self::match($c, ['id' => $m['tg_user_id'], 'first_name' => $m['first_name'], 'last_name' => $m['last_name'], 'username' => $m['username']], $group);
            if (!$why) continue;
            try {
                Moderation::perform($c['action'], $group, ['tgUserId' => $m['tg_user_id'], 'actor' => 'Bot', 'source' => 'web', 'reason' => $why . ' (quét danh sách)', 'notify' => false, 'minutes' => $c['mute_minutes'], 'meta' => ['name_guard' => 1]]);
                $ok++;
            } catch (Throwable $e) { $err[] = trim($m['first_name'] . ' ' . $m['last_name']) . ': ' . $e->getMessage(); }
        }
        return ['ok' => $ok, 'errors' => array_slice($err, 0, 10)];
    }
}

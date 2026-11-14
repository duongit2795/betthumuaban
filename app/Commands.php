<?php
defined('APP') or exit;

/**
 * Danh mục lệnh mặc định của bot + cấu hình đổi tên / bật tắt (lưu ở Settings 'builtin_cmds').
 * Dùng chung cho: xử lý lệnh, menu "/" Telegram, /help, trang "Lệnh mặc định" trên web.
 */
class Commands
{
    /**
     * key => [phạm vi (member|admin|private), tên gốc, mô tả menu, chi tiết, cú pháp, [ví dụ], ghi chú, [tên khác mặc định]]
     */
    const CATALOG = [
        // ---- Thành viên (trong nhóm)
        'help' => ['member', 'help', 'Danh sách lệnh', 'Hiện danh sách lệnh. Admin thấy thêm lệnh quản trị; lệnh tự tạo của nhóm cũng hiện ở đây.', '/help', ['/help'], 'Gõ /start trong nhóm cũng hiện danh sách này.'],
        'rules' => ['member', 'rules', 'Nội quy nhóm', 'Hiện nội quy của nhóm (đặt ở Cài đặt nhóm → Nội quy, hoặc bằng lệnh đặt nội quy).', '/rules', ['/rules'], ''],
        'me' => ['member', 'me', 'Thông tin & điểm của bạn', 'Xem thông tin của chính bạn trong nhóm: điểm, hạng, số tin nhắn, cảnh cáo, ngày vào nhóm.', '/me', ['/me'], ''],
        'top' => ['member', 'top', 'Bảng xếp hạng tương tác', 'Hiện 10 thành viên có điểm tương tác cao nhất nhóm.', '/top', ['/top'], ''],
        'info' => ['member', 'info', 'Thông tin thành viên', 'Xem thông tin một thành viên. Không chỉ định ai thì hiện thông tin của chính bạn.', '/info (reply) | /info @username | /info ID', ['Reply tin rồi gõ /info', '/info @lan99', '/info 123456789'], ''],
        'amnesty' => ['member', 'anxa', 'Ân xá thành viên (trả phí)', 'Hiện bảng giá ân xá và nút mở trang Ân xá trên web. Reply tin của người bị phạt để xem họ đang bị gì và phí ân xá.', '/anxa | /anxa (reply)', ['/anxa', 'Reply tin rồi gõ /anxa'], 'Chỉ hoạt động khi admin web bật tính năng Ân xá.'],
        'id' => ['member', 'id', 'Xem ID', 'Hiện ID của nhóm, của bạn và của người được reply (nếu có).', '/id', ['/id', 'Reply tin rồi gõ /id'], ''],
        // ---- Quản trị (admin nhóm / admin web đã liên kết Telegram)
        'warn' => ['admin', 'warn', 'Cảnh cáo (reply)', 'Cảnh cáo thành viên. Đủ giới hạn cảnh cáo thì tự xử lý theo Cài đặt nhóm (cấm chat / đuổi / cấm).', '/warn [lý do]', ['Reply tin rồi gõ /warn Spam link', '/warn @lan99 Nói tục'], 'Nội dung tin được reply được lưu làm bằng chứng.'],
        'unwarn' => ['admin', 'unwarn', 'Gỡ 1 cảnh cáo', 'Giảm 1 lần cảnh cáo của thành viên.', '/unwarn', ['Reply tin rồi gõ /unwarn'], ''],
        'resetwarn' => ['admin', 'resetwarn', 'Xoá hết cảnh cáo', 'Đặt số cảnh cáo của thành viên về 0.', '/resetwarn', ['/resetwarn @lan99'], ''],
        'mute' => ['admin', 'mute', 'Cấm chat: /mute 30m', 'Cấm thành viên gửi tin nhắn trong một khoảng thời gian. Không ghi thời gian thì dùng thời gian mặc định của nhóm.', '/mute [thời gian] [lý do]', ['/mute 30m Nói tục', '/mute 2h', '/mute 1d Spam', '/mute @lan99 45'], 'Thời gian: 30 hoặc 30m/30p = 30 phút · 2h/2g = 2 giờ · 1d/1n = 1 ngày.'],
        'unmute' => ['admin', 'unmute', 'Bỏ cấm chat', 'Cho thành viên nhắn tin lại ngay.', '/unmute', ['Reply tin rồi gõ /unmute', '/unmute 123456789'], ''],
        'kick' => ['admin', 'kick', 'Đuổi khỏi nhóm', 'Đuổi thành viên khỏi nhóm. Họ vẫn vào lại được bằng link mời.', '/kick [lý do]', ['/kick Quảng cáo'], ''],
        'ban' => ['admin', 'ban', 'Cấm vĩnh viễn', 'Cấm thành viên khỏi nhóm, không vào lại được cho tới khi được gỡ cấm.', '/ban [lý do]', ['Reply tin rồi gõ /ban Lừa đảo', '/ban 123456789'], ''],
        'unban' => ['admin', 'unban', 'Gỡ cấm', 'Gỡ cấm để thành viên có thể vào lại nhóm.', '/unban ID', ['/unban 123456789', '/unban @lan99'], ''],
        'addpoints' => ['admin', 'addpoints', 'Cộng/trừ điểm', 'Cộng hoặc trừ điểm tương tác của thành viên. Số âm để trừ.', '/addpoints <số> [lý do]', ['/addpoints 10 Hỗ trợ nhiệt tình', '/addpoints -5'], ''],
        'del' => ['admin', 'del', 'Xoá tin nhắn (reply)', 'Xoá tin nhắn được reply và cả tin lệnh.', '/del (reply)', ['Reply tin rồi gõ /del'], ''],
        'pin' => ['admin', 'pin', 'Ghim tin nhắn (reply)', 'Ghim tin nhắn được reply.', '/pin (reply)', ['Reply tin rồi gõ /pin'], ''],
        'unpin' => ['admin', 'unpin', 'Bỏ ghim tin nhắn', 'Bỏ ghim tin nhắn được reply.', '/unpin (reply)', ['Reply tin đang ghim rồi gõ /unpin'], ''],
        'setrules' => ['admin', 'setrules', 'Đặt nội quy nhóm', 'Đặt nội quy nhóm bằng nội dung gõ sau lệnh, hoặc reply một tin chứa nội quy.', '/setrules <nội dung>', ['/setrules 1. Tôn trọng nhau 2. Không spam'], ''],
        'reload' => ['admin', 'reload', 'Làm mới danh sách admin', 'Cập nhật lại danh sách quản trị viên của nhóm từ Telegram.', '/reload', ['/reload'], 'Dùng sau khi thêm/bớt admin trên Telegram.'],
        // ---- Chat riêng với bot
        'start' => ['private', 'start', 'Bắt đầu', 'Lời chào, hướng dẫn và ID Telegram của bạn.', '/start', ['/start'], 'Không đổi tên được vì Telegram dùng /start khi mở bot lần đầu.'],
        'login' => ['private', 'login', 'Đăng nhập web: /login MÃ', 'Đăng nhập web bằng mã 6 ký tự lấy ở trang đăng nhập. Bot sẽ hỏi xác nhận.', '/login MÃ', ['/login K7QX4P', 'Hoặc chỉ gửi mã: K7QX4P'], '', ['dangnhap']],
        'link' => ['private', 'link', 'Liên kết tài khoản web: /link MÃ', 'Liên kết Telegram với tài khoản web đã có bằng mã tạo ở trang Tài khoản.', '/link MÃ', ['/link 8F3A2C1D'], ''],
        'balance' => ['private', 'sodu', 'Số dư ví & cách nạp tiền', 'Xem số dư ví VND, nội dung chuyển khoản nạp tiền và các hình phạt có thể ân xá.', '/sodu', ['/sodu'], 'Cần liên kết Telegram với tài khoản web.', ['naptien']],
        'pme' => ['private', 'me', 'Điểm của tôi', 'Xem điểm và số tin nhắn của bạn ở tất cả các nhóm có bot.', '/me', ['/me'], ''],
    ];
    const FIXED = ['start']; // không đổi tên được
    const SCOPE_LABEL = ['member' => 'Thành viên', 'admin' => 'Quản trị', 'private' => 'Chat riêng'];

    private static ?array $cfg = null;

    public static function config(): array
    {
        if (self::$cfg !== null) return self::$cfg;
        $saved = json_decode((string)Settings::get('builtin_cmds'), true) ?: [];
        $out = [];
        foreach (self::CATALOG as $k => $c) {
            $s = $saved[$k] ?? [];
            $out[$k] = [
                'enabled' => array_key_exists('enabled', $s) ? (bool)$s['enabled'] : true,
                'aliases' => array_key_exists('aliases', $s) ? array_values($s['aliases']) : ($c[7] ?? []),
                'keep_original' => array_key_exists('keep_original', $s) ? (bool)$s['keep_original'] : true,
                'description' => ($s['description'] ?? '') !== '' ? $s['description'] : $c[2],
            ];
        }
        return self::$cfg = $out;
    }

    /** Các tên đang dùng được của một lệnh (tên chính đứng đầu) */
    public static function names(string $key): array
    {
        $c = self::config()[$key] ?? null;
        if (!$c || !$c['enabled']) return [];
        $orig = self::CATALOG[$key][1];
        $aliases = $c['aliases'];
        if (!$aliases) return [$orig];
        // Tên phụ có sẵn (VD /dangnhap) → tên gốc vẫn là tên chính; tên do admin đặt → tên mới là tên chính
        if ($aliases === (self::CATALOG[$key][7] ?? [])) return array_values(array_unique(array_merge([$orig], $aliases)));
        $names = $aliases;
        if ($c['keep_original'] || in_array($key, self::FIXED, true)) $names[] = $orig;
        return array_values(array_unique($names));
    }

    /** Tên hiển thị chính (VD trong menu, /help, hướng dẫn): ưu tiên tên mới */
    public static function name(string $key): string
    {
        $n = self::names($key);
        return $n[0] ?? self::CATALOG[$key][1];
    }

    /** Tên lệnh gõ vào → key; null nếu không phải lệnh mặc định hoặc đã tắt */
    public static function resolve(string $cmd, string $where): ?string
    {
        $cmd = strtolower($cmd);
        foreach (self::CATALOG as $k => $c) {
            if (($where === 'private') !== ($c[0] === 'private')) continue;
            if (in_array($cmd, self::names($k), true)) return $k;
        }
        return null;
    }

    /** Danh sách cho menu "/" Telegram: [thành viên, admin, chat riêng] */
    public static function menus(): array
    {
        $mem = $adm = $prv = [];
        foreach (self::CATALOG as $k => $c) {
            if (!self::names($k)) continue;
            $item = ['command' => self::name($k), 'description' => mb_substr(self::config()[$k]['description'], 0, 250)];
            if ($c[0] === 'member') { $mem[] = $item; $adm[] = $item; }
            elseif ($c[0] === 'admin') $adm[] = $item;
            else $prv[] = $item;
        }
        return [$mem, $adm, $prv];
    }

    /** Lưu cấu hình 1 lệnh (đã kiểm tra trùng tên) */
    public static function save(string $key, array $in): array
    {
        if (!isset(self::CATALOG[$key])) throw new HttpError('Không có lệnh này');
        $scope = self::CATALOG[$key][0] === 'private' ? 'private' : 'group';
        $aliases = [];
        if (!in_array($key, self::FIXED, true)) {
            foreach ((array)($in['aliases'] ?? []) as $a) {
                $a = strtolower(ltrim(trim((string)$a), '/'));
                if ($a === '') continue;
                if (!preg_match('/^[a-z0-9_]{1,32}$/', $a)) throw new HttpError("Tên /$a không hợp lệ: chỉ chữ thường không dấu, số, dấu _ (tối đa 32 ký tự)");
                if ($a === self::CATALOG[$key][1]) continue;
                // Không trùng tên (gốc hoặc thay thế) của lệnh khác cùng phạm vi
                foreach (self::CATALOG as $k2 => $c2) {
                    if ($k2 === $key || (($c2[0] === 'private') !== ($scope === 'private'))) continue;
                    $other = array_merge([$c2[1]], self::config()[$k2]['aliases']);
                    if (in_array($a, $other, true)) throw new HttpError("Tên /$a đang được dùng cho lệnh /{$c2[1]}");
                }
                $aliases[] = $a;
            }
        }
        $aliases = array_values(array_unique(array_slice($aliases, 0, 5)));
        $keep = in_array($key, self::FIXED, true) || !$aliases || !empty($in['keep_original']);
        $desc = trim(mb_substr((string)($in['description'] ?? ''), 0, 250));
        $all = json_decode((string)Settings::get('builtin_cmds'), true) ?: [];
        $all[$key] = [
            'enabled' => in_array($key, self::FIXED, true) ? true : !empty($in['enabled']),
            'aliases' => $aliases,
            'keep_original' => $keep,
            'description' => $desc === self::CATALOG[$key][2] ? '' : $desc,
        ];
        Settings::set('builtin_cmds', json_encode($all, JSON_UNESCAPED_UNICODE));
        self::$cfg = null;
        return self::row($key);
    }

    public static function reset(?string $key = null): void
    {
        $all = json_decode((string)Settings::get('builtin_cmds'), true) ?: [];
        if ($key) unset($all[$key]); else $all = [];
        Settings::set('builtin_cmds', json_encode($all, JSON_UNESCAPED_UNICODE));
        self::$cfg = null;
    }

    public static function row(string $key): array
    {
        $c = self::CATALOG[$key];
        $cfg = self::config()[$key];
        return [
            'key' => $key,
            'scope' => $c[0],
            'scope_label' => self::SCOPE_LABEL[$c[0]],
            'original' => $c[1],
            'names' => self::names($key),
            'aliases' => $cfg['aliases'],
            'keep_original' => $cfg['keep_original'],
            'enabled' => $cfg['enabled'],
            'description' => $cfg['description'],
            'default_description' => $c[2],
            'detail' => $c[3],
            'usage' => self::rename($c[4], $key),
            'examples' => array_map(fn($e) => self::rename($e, $key), $c[5]),
            'note' => $c[6],
            'fixed' => in_array($key, self::FIXED, true),
            'customized' => $cfg['aliases'] !== ($c[7] ?? []) || !$cfg['enabled'] || !$cfg['keep_original'] || $cfg['description'] !== $c[2],
        ];
    }

    /** Thay tên gốc trong cú pháp/ví dụ bằng tên đang dùng */
    private static function rename(string $s, string $key): string
    {
        $orig = self::CATALOG[$key][1];
        return preg_replace('~/' . preg_quote($orig, '~') . '\b~', '/' . self::name($key), $s);
    }

    public static function all(): array
    {
        return array_map([self::class, 'row'], array_keys(self::CATALOG));
    }
}

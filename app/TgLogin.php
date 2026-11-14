<?php
defined('APP') or exit;

/**
 * Đăng nhập web bằng Telegram:
 *  1. Web tạo mã (gắn với phiên trình duyệt đang mở).
 *  2. Người dùng nhắn mã cho bot trong chat riêng → bot hỏi xác nhận.
 *  3. Bấm "Xác nhận" → web (đang chờ) đăng nhập vào tài khoản gắn với Telegram đó.
 */
class TgLogin
{
    const TTL = 300; // 5 phút
    const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // bỏ ký tự dễ nhầm 0/O, 1/I

    public static function enabled(): bool
    {
        return Settings::get('tg_login') !== '0' && (bool)DB::val('SELECT COUNT(*) FROM tg_bots WHERE enabled = 1');
    }

    public static function bots(): array
    {
        return array_map(fn($b) => $b['username'], DB::all('SELECT username FROM tg_bots WHERE enabled = 1 AND username IS NOT NULL ORDER BY id'));
    }

    private static function sess(): string
    {
        return hash('sha256', session_id() . '|' . cfg('secret'));
    }

    public static function create(): array
    {
        DB::run('DELETE FROM tg_login_codes WHERE expires_at < ?', [now() - 3600]);
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        } while (DB::val('SELECT id FROM tg_login_codes WHERE code = ?', [$code]));
        // Mỗi phiên chỉ giữ 1 mã còn hiệu lực
        DB::run("UPDATE tg_login_codes SET status = 'expired' WHERE sess = ? AND status = 'pending'", [self::sess()]);
        DB::insert('tg_login_codes', [
            'code' => $code,
            'sess' => self::sess(),
            'status' => 'pending',
            'ip' => mb_substr((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
            'ua' => mb_substr(self::device((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 120),
            'created_at' => now(),
            'expires_at' => now() + self::TTL,
        ]);
        return ['code' => $code, 'expires_in' => self::TTL];
    }

    /** Mô tả thiết bị ngắn gọn từ User-Agent */
    public static function device(string $ua): string
    {
        $os = preg_match('/iPhone|iPad/i', $ua) ? 'iPhone/iPad' : (preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS X/i', $ua) ? 'macOS' : (preg_match('/Linux/i', $ua) ? 'Linux' : 'Không rõ'))));
        $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/OPR\/|Opera/', $ua) ? 'Opera' : (preg_match('/CriOS|Chrome\//', $ua) ? 'Chrome' : (preg_match('/FxiOS|Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'Trình duyệt'))));
        return "$br trên $os";
    }

    /** Web hỏi trạng thái mã (chỉ phiên đã tạo mã mới được hỏi) */
    public static function check(string $code): array
    {
        $row = DB::get('SELECT * FROM tg_login_codes WHERE code = ? AND sess = ?', [strtoupper(trim($code)), self::sess()]);
        if (!$row) return ['status' => 'invalid'];
        if ($row['status'] === 'pending' && $row['expires_at'] < now()) {
            DB::run("UPDATE tg_login_codes SET status = 'expired' WHERE id = ?", [$row['id']]);
            return ['status' => 'expired'];
        }
        if ($row['status'] === 'waiting') return ['status' => 'waiting', 'name' => $row['tg_name']];
        if ($row['status'] !== 'approved') return ['status' => $row['status']];
        if ($row['expires_at'] < now() - 60) return ['status' => 'expired'];

        // Đã xác nhận → tìm hoặc tạo tài khoản web
        $u = DB::get('SELECT * FROM app_users WHERE tg_user_id = ?', [$row['tg_user_id']]);
        if (!$u) {
            if (Settings::get('allow_register') !== '1') {
                DB::run("UPDATE tg_login_codes SET status = 'used' WHERE id = ?", [$row['id']]);
                fail('Telegram này chưa có tài khoản web và đăng ký đang tắt. Hãy nhờ admin tạo tài khoản.', 403);
            }
            $u = self::createUser((int)$row['tg_user_id'], (string)$row['tg_name'], (string)$row['tg_username']);
        }
        if ($u['disabled']) fail('Tài khoản đã bị khoá', 403);
        DB::run("UPDATE tg_login_codes SET status = 'used' WHERE id = ?", [$row['id']]);
        Auth::login($u);
        Repo::log(null, $u['username'], 'login', null, 'Đăng nhập bằng Telegram · ' . $row['ua']);
        return ['status' => 'done', 'user' => Auth::publicUser($u), 'created' => !empty($u['_new'])];
    }

    private static function createUser(int $tgId, string $name, string $tgUsername): array
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9_.]/', '', $tgUsername)) ?: 'tg' . $tgId;
        $base = substr(strlen($base) < 3 ? 'tg' . $tgId : $base, 0, 28);
        $username = $base;
        for ($i = 2; DB::get('SELECT id FROM app_users WHERE LOWER(username) = LOWER(?)', [$username]); $i++) $username = $base . $i;
        $id = DB::insert('app_users', [
            'username' => $username,
            'password_hash' => '', // chưa đặt mật khẩu: chỉ đăng nhập bằng Telegram cho tới khi tự đặt
            'display_name' => mb_substr($name ?: $username, 0, 60),
            'role' => 'member',
            'tg_user_id' => $tgId,
            'created_at' => now(),
        ]);
        $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$id]);
        $u['_new'] = true;
        return $u;
    }

    // ================= Phía bot =================
    /** Người dùng gửi mã cho bot → hỏi xác nhận. Trả về false nếu không phải mã đăng nhập. */
    public static function onCode(Telegram $api, array $msg, string $code): bool
    {
        $from = $msg['from'];
        $row = DB::get('SELECT * FROM tg_login_codes WHERE code = ?', [strtoupper($code)]);
        $reply = fn($t, $extra = []) => $api->trySend($msg['chat']['id'], $t, $extra);
        if (!$row) return false;
        if ($row['status'] !== 'pending' || $row['expires_at'] < now()) {
            $reply('⌛ Mã đăng nhập đã hết hạn hoặc đã được dùng. Hãy lấy mã mới trên web.');
            return true;
        }
        $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        DB::run("UPDATE tg_login_codes SET status = 'waiting', tg_user_id = ?, tg_name = ?, tg_username = ? WHERE id = ?",
            [$from['id'], mb_substr($name, 0, 190), $from['username'] ?? null, $row['id']]);
        $acc = DB::get('SELECT username FROM app_users WHERE tg_user_id = ?', [$from['id']]);
        $site = Settings::get('site_name');
        $reply("🔐 <b>Xác nhận đăng nhập " . tg_esc($site) . "</b>\n\n"
            . "💻 Thiết bị: " . tg_esc($row['ua']) . "\n"
            . "🌐 IP: <code>" . tg_esc($row['ip']) . "</code>\n"
            . "👤 Tài khoản: " . ($acc ? '<b>' . tg_esc($acc['username']) . '</b>' : 'tạo mới từ Telegram của bạn') . "\n\n"
            . "⚠️ Chỉ bấm <b>Xác nhận</b> nếu <b>chính bạn</b> vừa lấy mã này trên web. Không gửi mã cho người khác.", [
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => '✅ Xác nhận đăng nhập', 'callback_data' => 'login_ok:' . $row['code'], 'style' => 'success'],
                ['text' => '❌ Huỷ', 'callback_data' => 'login_no:' . $row['code'], 'style' => 'danger'],
            ]]],
        ]);
        return true;
    }

    /** Bấm nút Xác nhận / Huỷ trong chat với bot */
    public static function onCallback(Telegram $api, array $cb): void
    {
        if (!preg_match('/^login_(ok|no):([A-Z0-9]{6})$/', (string)($cb['data'] ?? ''), $m)) return;
        $row = DB::get('SELECT * FROM tg_login_codes WHERE code = ?', [$m[2]]);
        $edit = function (string $t) use ($api, $cb) {
            $api->tryCall('editMessageText', ['chat_id' => $cb['message']['chat']['id'], 'message_id' => $cb['message']['message_id'], 'text' => $t, 'parse_mode' => 'HTML']);
        };
        if (!$row || (string)$row['tg_user_id'] !== (string)$cb['from']['id']) {
            $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'Mã không hợp lệ']);
            return;
        }
        if ($row['status'] !== 'waiting' || $row['expires_at'] < now()) {
            $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'Mã đã hết hạn']);
            $edit('⌛ Mã đăng nhập đã hết hạn. Hãy lấy mã mới trên web.');
            return;
        }
        if ($m[1] === 'ok') {
            DB::run("UPDATE tg_login_codes SET status = 'approved' WHERE id = ?", [$row['id']]);
            $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'Đã xác nhận']);
            $edit("✅ <b>Đăng nhập thành công!</b>\nQuay lại trình duyệt, web sẽ tự vào trong giây lát.\n\n💻 " . tg_esc($row['ua']));
        } else {
            DB::run("UPDATE tg_login_codes SET status = 'denied' WHERE id = ?", [$row['id']]);
            $api->tryCall('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'Đã huỷ']);
            $edit('❌ Đã huỷ yêu cầu đăng nhập.');
        }
    }
}

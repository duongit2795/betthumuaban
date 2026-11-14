<?php
defined('APP') or exit;

class Api
{
    private array $in;
    private string $method;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $raw = file_get_contents('php://input');
        $this->in = $raw ? (json_decode($raw, true) ?: []) : [];
        $this->in = array_merge($_GET, $this->in);
    }

    private function in(string $k, $def = null)
    {
        return $this->in[$k] ?? $def;
    }

    private function int(string $k, int $def = 0): int
    {
        return isset($this->in[$k]) && is_numeric($this->in[$k]) ? (int)$this->in[$k] : $def;
    }

    public function dispatch(string $route): void
    {
        Auth::start();
        if ($route !== 'status' && $route !== 'install' && !installed()) fail('Ứng dụng chưa được cài đặt', 503);
        if ($this->method === 'POST') Auth::checkCsrf();

        $map = [
            'status' => 'status',
            'install' => 'install',
            'auth/login' => 'login',
            'auth/register' => 'register',
            'auth/logout' => 'logout',
            'me' => 'me',
            'me/update' => 'meUpdate',
            'me/link-code' => 'meLinkCode',
            'me/unlink' => 'meUnlink',
            'leaderboard' => 'leaderboard',
            'dashboard' => 'dashboard',
            'bots' => 'bots',
            'bots/add' => 'botAdd',
            'bots/toggle' => 'botToggle',
            'bots/refresh' => 'botRefresh',
            'bots/delete' => 'botDelete',
            'groups' => 'groups',
            'group' => 'group',
            'group/settings' => 'groupSettings',
            'group/send' => 'groupSend',
            'group/sync' => 'groupSync',
            'group/leave' => 'groupLeave',
            'group/delete' => 'groupDelete',
            'group/reset-points' => 'groupResetPoints',
            'chat' => 'chat',
            'chat/poll' => 'chatPoll',
            'chat/send' => 'chatSend',
            'chat/delete' => 'chatDelete',
            'chat/pin' => 'chatPin',
            'members' => 'members',
            'member' => 'member',
            'member/action' => 'memberAction',
            'member/note' => 'memberNote',
            'member/add' => 'memberAdd',
            'logs' => 'logs',
            'users' => 'users',
            'users/save' => 'userSave',
            'users/delete' => 'userDelete',
            'rules' => 'rules',
            'rules/save' => 'ruleSave',
            'rules/delete' => 'ruleDelete',
            'rules/toggle' => 'ruleToggle',
            'rules/bulk' => 'ruleBulk',
            'rules/menu-mode' => 'ruleMenuMode',
            'system' => 'system',
            'system/settings' => 'systemSettings',
            'system/check-update' => 'checkUpdate',
            'system/update' => 'doUpdate',
            'system/migrate' => 'migrate',
        ];
        $writeRoutes = ['install', 'auth/login', 'auth/register', 'auth/logout', 'me/update', 'me/link-code', 'me/unlink'];
        if (!isset($map[$route])) fail('Không tìm thấy API', 404);
        $fn = $map[$route];
        $isWrite = in_array($route, $writeRoutes, true) || str_contains($route, '/') && !in_array($route, ['system/check-update', 'chat/poll'], true);
        if ($isWrite && $this->method !== 'POST') fail('Phương thức không hợp lệ', 405);
        json_out(['ok' => true, 'data' => $this->$fn()]);
    }

    // ================= Công khai =================
    private function status(): array
    {
        $inst = installed();
        return [
            'installed' => $inst,
            'csrf' => Auth::csrf(),
            'user' => $inst ? Auth::publicUser(Auth::user()) : null,
            'site_name' => $inst ? Settings::get('site_name') : 'Tele Manager',
            'allow_register' => $inst ? Settings::get('allow_register') === '1' : false,
            'version' => APP_VERSION,
            'php' => PHP_VERSION,
            'checks' => $inst ? null : [
                'pdo_sqlite' => extension_loaded('pdo_sqlite'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'curl' => extension_loaded('curl'),
                'zip' => class_exists('ZipArchive'),
                'mbstring' => extension_loaded('mbstring'),
                'writable' => is_writable(DATA_DIR) || is_writable(ROOT),
            ],
        ];
    }

    private function install(): array
    {
        if (installed()) fail('Ứng dụng đã được cài đặt');
        $driver = $this->in('driver') === 'mysql' ? 'mysql' : 'sqlite';
        $username = trim((string)$this->in('username'));
        $password = (string)$this->in('password');
        if (!preg_match('/^[a-zA-Z0-9_.]{3,32}$/', $username)) fail('Tên đăng nhập 3-32 ký tự (chữ, số, _ .)');
        if (strlen($password) < 6) fail('Mật khẩu tối thiểu 6 ký tự');
        if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0755, true)) fail('Không tạo được thư mục data/. Hãy cấp quyền ghi cho thư mục web.');
        if (!is_writable(DATA_DIR)) fail('Thư mục data/ không có quyền ghi (chown www:www)');

        if ($driver === 'mysql') {
            $db = [
                'driver' => 'mysql',
                'host' => trim((string)$this->in('db_host', 'localhost')) ?: 'localhost',
                'port' => (int)$this->in('db_port', 3306) ?: 3306,
                'name' => trim((string)$this->in('db_name')),
                'user' => trim((string)$this->in('db_user')),
                'pass' => (string)$this->in('db_pass'),
            ];
            if (!$db['name'] || !$db['user']) fail('Nhập đủ tên database và user MySQL');
        } else {
            if (!extension_loaded('pdo_sqlite')) fail('PHP chưa bật pdo_sqlite, hãy chọn MySQL');
            $db = ['driver' => 'sqlite', 'file' => 'db_' . bin2hex(random_bytes(8)) . '.sqlite'];
        }
        try {
            DB::connect($db);
            DB::migrate();
        } catch (PDOException $e) {
            fail('Không kết nối được database: ' . $e->getMessage());
        }
        $config = ['db' => $db, 'secret' => bin2hex(random_bytes(32)), 'installed_at' => now()];
        $php = "<?php\n// Tự sinh khi cài đặt. KHÔNG chia sẻ file này.\nreturn " . var_export($config, true) . ";\n";

        DB::insert('app_users', [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'display_name' => trim((string)$this->in('display_name')) ?: $username,
            'role' => 'admin',
            'created_at' => now(),
        ]);
        Settings::set('site_name', trim((string)$this->in('site_name')) ?: 'Tele Manager');
        Settings::set('site_url', site_url());
        Settings::set('cron_key', bin2hex(random_bytes(12)));
        if (!file_put_contents(CONFIG_FILE, $php)) fail('Không ghi được data/config.php');
        @file_put_contents(DATA_DIR . '/index.html', '');
        @file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
        $u = DB::get('SELECT * FROM app_users WHERE LOWER(username) = LOWER(?)', [$username]);
        Auth::login($u);
        return ['user' => Auth::publicUser($u)];
    }

    private function login(): array
    {
        Auth::throttle('login');
        $u = DB::get('SELECT * FROM app_users WHERE LOWER(username) = LOWER(?)', [trim((string)$this->in('username'))]);
        if (!$u || !password_verify((string)$this->in('password'), $u['password_hash'])) fail('Sai tên đăng nhập hoặc mật khẩu', 401);
        if ($u['disabled']) fail('Tài khoản đã bị khoá', 403);
        Auth::login($u);
        return ['user' => Auth::publicUser($u)];
    }

    private function register(): array
    {
        if (Settings::get('allow_register') !== '1') fail('Đăng ký đang tắt', 403);
        Auth::throttle('register', 5, 3600);
        $username = trim((string)$this->in('username'));
        $password = (string)$this->in('password');
        if (!preg_match('/^[a-zA-Z0-9_.]{3,32}$/', $username)) fail('Tên đăng nhập 3-32 ký tự (chữ, số, _ .)');
        if (strlen($password) < 6) fail('Mật khẩu tối thiểu 6 ký tự');
        if (DB::get('SELECT id FROM app_users WHERE LOWER(username) = LOWER(?)', [$username])) fail('Tên đăng nhập đã tồn tại');
        $id = DB::insert('app_users', [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'display_name' => mb_substr(trim((string)$this->in('display_name')), 0, 60) ?: $username,
            'role' => 'member',
            'created_at' => now(),
        ]);
        $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$id]);
        Auth::login($u);
        return ['user' => Auth::publicUser($u)];
    }

    private function logout(): array
    {
        Auth::logout();
        return ['csrf' => Auth::csrf()];
    }

    // ================= Thành viên =================
    private function me(): array
    {
        $u = Auth::requireUser();
        $memberships = [];
        if ($u['tg_user_id']) {
            Repo::sweepMutes();
            $rows = DB::all('SELECT m.*, g.title, g.username AS group_username, g.id AS gid FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE m.tg_user_id = ? ORDER BY m.points DESC', [$u['tg_user_id']]);
            foreach ($rows as $r) {
                $rank = 1 + (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND points > ?', [$r['group_id'], $r['points']]);
                $total = (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND status IN ('member','muted')", [$r['group_id']]);
                $g = Repo::group((int)$r['group_id']);
                $s = Repo::settings($g);
                $memberships[] = [
                    'group_id' => (int)$r['gid'],
                    'title' => $r['title'],
                    'group_username' => $r['group_username'],
                    'points' => (int)$r['points'],
                    'messages' => (int)$r['messages'],
                    'warns' => (int)$r['warns'],
                    'warn_limit' => (int)$s['warn_limit'],
                    'status' => $r['status'],
                    'is_admin' => (int)$r['is_admin'],
                    'muted_until' => $r['muted_until'] ? (int)$r['muted_until'] : null,
                    'rank' => $rank,
                    'total' => $total,
                    'joined_at' => $r['joined_at'] ? (int)$r['joined_at'] : null,
                    'last_active' => $r['last_active'] ? (int)$r['last_active'] : null,
                ];
            }
        }
        return [
            'user' => Auth::publicUser($u),
            'link_code' => $u['link_code'],
            'bots' => array_map(fn($b) => $b['username'], DB::all("SELECT username FROM tg_bots WHERE enabled = 1 AND username IS NOT NULL ORDER BY id")),
            'memberships' => $memberships,
        ];
    }

    private function meUpdate(): array
    {
        $u = Auth::requireUser();
        $upd = [];
        $name = trim((string)$this->in('display_name', ''));
        if ($name !== '') $upd['display_name'] = mb_substr($name, 0, 60);
        $new = (string)$this->in('new_password', '');
        if ($new !== '') {
            if (!password_verify((string)$this->in('current_password'), $u['password_hash'])) fail('Mật khẩu hiện tại không đúng');
            if (strlen($new) < 6) fail('Mật khẩu mới tối thiểu 6 ký tự');
            $upd['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        }
        if ($upd) DB::update('app_users', $upd, 'id = ?', [$u['id']]);
        return ['user' => Auth::publicUser(DB::get('SELECT * FROM app_users WHERE id = ?', [$u['id']]))];
    }

    private function meLinkCode(): array
    {
        $u = Auth::requireUser();
        $code = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        DB::run('UPDATE app_users SET link_code = ? WHERE id = ?', [$code, $u['id']]);
        return ['code' => $code];
    }

    private function meUnlink(): array
    {
        $u = Auth::requireUser();
        DB::run('UPDATE app_users SET tg_user_id = NULL WHERE id = ?', [$u['id']]);
        return [];
    }

    private function leaderboard(): array
    {
        $u = Auth::requireUser();
        $gid = $this->int('group_id');
        if ($u['role'] !== 'admin') {
            if (!$u['tg_user_id'] || !Repo::member($gid, $u['tg_user_id'])) fail('Bạn không thuộc nhóm này', 403);
        }
        $g = Repo::group($gid) ?: fail('Không tìm thấy nhóm', 404);
        $rows = DB::all("SELECT tg_user_id, username, first_name, last_name, points, messages, is_admin FROM tg_members WHERE group_id = ? AND is_bot = 0 AND status <> 'banned' ORDER BY points DESC, messages DESC LIMIT 100", [$gid]);
        return [
            'group' => ['id' => (int)$g['id'], 'title' => $g['title']],
            'me' => $u['tg_user_id'] ? (string)$u['tg_user_id'] : null,
            'rows' => array_map(fn($r) => [
                'tg_user_id' => (string)$r['tg_user_id'],
                'name' => Repo::name($r),
                'username' => $r['username'],
                'points' => (int)$r['points'],
                'messages' => (int)$r['messages'],
                'is_admin' => (int)$r['is_admin'],
            ], $rows),
        ];
    }

    // ================= Quản trị =================
    private function dashboard(): array
    {
        Auth::requireAdmin();
        Repo::sweepMutes();
        $today = date('Y-m-d');
        $days = [];
        for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = ['messages' => 0, 'joins' => 0, 'leaves' => 0];
        $from = array_key_first($days);
        foreach (DB::all('SELECT day, SUM(messages) AS m, SUM(joins) AS j, SUM(leaves) AS l FROM daily_stats WHERE day >= ? GROUP BY day', [$from]) as $r) {
            if (isset($days[$r['day']])) $days[$r['day']] = ['messages' => (int)$r['m'], 'joins' => (int)$r['j'], 'leaves' => (int)$r['l']];
        }
        $chart = [];
        foreach ($days as $d => $v) $chart[] = ['day' => $d] + $v;

        return [
            'stats' => [
                'groups' => (int)DB::val('SELECT COUNT(*) FROM tg_groups WHERE active = 1'),
                'members' => (int)DB::val("SELECT COUNT(DISTINCT tg_user_id) FROM tg_members WHERE is_bot = 0 AND status IN ('member','muted')"),
                'messages_today' => (int)DB::val('SELECT COALESCE(SUM(messages),0) FROM daily_stats WHERE day = ?', [$today]),
                'joins_today' => (int)DB::val('SELECT COALESCE(SUM(joins),0) FROM daily_stats WHERE day = ?', [$today]),
                'actions_24h' => (int)DB::val("SELECT COUNT(*) FROM app_logs WHERE created_at > ? AND action IN ('ban','kick','mute','warn','delete')", [now() - 86400]),
                'banned' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE status = 'banned'"),
                'muted' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE status = 'muted'"),
                'bots' => (int)DB::val('SELECT COUNT(*) FROM tg_bots WHERE enabled = 1'),
                'users' => (int)DB::val('SELECT COUNT(*) FROM app_users'),
            ],
            'chart' => $chart,
            'top_members' => array_map(fn($r) => [
                'id' => (int)$r['id'], 'name' => Repo::name($r), 'username' => $r['username'], 'points' => (int)$r['points'], 'group' => $r['title'], 'tg_user_id' => (string)$r['tg_user_id'],
            ], DB::all("SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE m.is_bot = 0 AND m.status <> 'banned' ORDER BY m.points DESC LIMIT 5")),
            'recent_logs' => $this->formatLogs(DB::all('SELECT l.*, g.title AS group_title FROM app_logs l LEFT JOIN tg_groups g ON g.id = l.group_id ORDER BY l.id DESC LIMIT 8')),
            'has_bot' => (bool)DB::val('SELECT COUNT(*) FROM tg_bots'),
        ];
    }

    // ---- Bots ----
    private function webhookUrl(int $botId): string
    {
        return site_url() . '/webhook.php?bot=' . $botId;
    }

    public static function webhookSecret(int $botId): string
    {
        return substr(hash_hmac('sha256', 'wh' . $botId, (string)cfg('secret')), 0, 48);
    }

    private function setWebhook(array $bot): void
    {
        $api = new Telegram($bot['token']);
        $url = $this->webhookUrl((int)$bot['id']);
        if (!str_starts_with($url, 'https://')) {
            DB::run("UPDATE tg_bots SET status = 'error', last_error = ? WHERE id = ?", ['Webhook cần HTTPS. Hãy bật SSL trong aaPanel và đặt Địa chỉ web bắt đầu bằng https://', $bot['id']]);
            throw new HttpError('Telegram yêu cầu HTTPS. Bật SSL cho website trong aaPanel rồi cập nhật "Địa chỉ web" trong Hệ thống.');
        }
        try {
            $api->call('setWebhook', [
                'url' => $url,
                'secret_token' => self::webhookSecret((int)$bot['id']),
                'allowed_updates' => ['message', 'edited_message', 'chat_member', 'my_chat_member', 'callback_query'],
                'max_connections' => 40,
            ]);
            $this->registerMenus($api);
            DB::run("UPDATE tg_bots SET status = 'running', last_error = NULL WHERE id = ?", [$bot['id']]);
        } catch (TelegramException $e) {
            DB::run("UPDATE tg_bots SET status = 'error', last_error = ? WHERE id = ?", [$e->getMessage(), $bot['id']]);
            throw new HttpError('Không đặt được webhook: ' . $e->getMessage());
        }
    }

    private function bots(): array
    {
        Auth::requireAdmin();
        $rows = DB::all('SELECT b.*, (SELECT COUNT(*) FROM tg_groups g WHERE g.bot_id = b.id AND g.active = 1) AS groups FROM tg_bots b ORDER BY b.id');
        return array_map(fn($b) => [
            'id' => (int)$b['id'],
            'username' => $b['username'],
            'name' => $b['name'],
            'tg_bot_id' => (string)$b['tg_bot_id'],
            'token_hint' => substr($b['token'], 0, 6) . '…' . substr($b['token'], -4),
            'enabled' => (int)$b['enabled'],
            'status' => $b['status'],
            'last_error' => $b['last_error'],
            'groups' => (int)$b['groups'],
            'created_at' => (int)$b['created_at'],
            'webhook_url' => $this->webhookUrl((int)$b['id']),
        ], $rows);
    }

    private function botAdd(): array
    {
        Auth::requireAdmin();
        $token = trim((string)$this->in('token'));
        if (!preg_match('/^\d+:[\w-]{30,}$/', $token)) fail('Token không đúng định dạng (lấy từ @BotFather)');
        if (DB::get('SELECT id FROM tg_bots WHERE token = ?', [$token])) fail('Bot này đã được thêm');
        try {
            $me = (new Telegram($token))->call('getMe');
        } catch (TelegramException $e) {
            fail('Token không hợp lệ: ' . $e->getMessage());
        }
        $id = DB::insert('tg_bots', [
            'token' => $token, 'tg_bot_id' => $me['id'], 'username' => $me['username'], 'name' => $me['first_name'],
            'enabled' => 1, 'status' => 'stopped', 'created_at' => now(),
        ]);
        Repo::log(null, Auth::user()['username'], 'bot_add', null, '@' . $me['username']);
        $warn = null;
        try {
            $this->setWebhook(DB::get('SELECT * FROM tg_bots WHERE id = ?', [$id]));
        } catch (HttpError $e) {
            $warn = $e->getMessage();
        }
        return ['id' => $id, 'username' => $me['username'], 'warning' => $warn, 'privacy' => empty($me['can_read_all_group_messages'])];
    }

    private function botToggle(): array
    {
        Auth::requireAdmin();
        $bot = DB::get('SELECT * FROM tg_bots WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy bot', 404);
        $on = (bool)$this->in('enabled');
        DB::run('UPDATE tg_bots SET enabled = ? WHERE id = ?', [$on ? 1 : 0, $bot['id']]);
        if ($on) $this->setWebhook($bot);
        else {
            (new Telegram($bot['token']))->tryCall('deleteWebhook');
            DB::run("UPDATE tg_bots SET status = 'stopped' WHERE id = ?", [$bot['id']]);
        }
        Repo::log(null, Auth::user()['username'], $on ? 'bot_on' : 'bot_off', null, '@' . $bot['username']);
        return [];
    }

    private function botRefresh(): array
    {
        Auth::requireAdmin();
        $bot = DB::get('SELECT * FROM tg_bots WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy bot', 404);
        $api = new Telegram($bot['token']);
        try {
            $me = $api->call('getMe');
            DB::run('UPDATE tg_bots SET username = ?, name = ? WHERE id = ?', [$me['username'], $me['first_name'], $bot['id']]);
        } catch (TelegramException $e) {
            DB::run("UPDATE tg_bots SET status = 'error', last_error = ? WHERE id = ?", [$e->getMessage(), $bot['id']]);
            fail('Token lỗi: ' . $e->getMessage());
        }
        if ($bot['enabled']) $this->setWebhook($bot);
        $info = $api->tryCall('getWebhookInfo') ?: [];
        if (!empty($info['last_error_message']) && ($info['last_error_date'] ?? 0) > now() - 600) {
            DB::run('UPDATE tg_bots SET last_error = ? WHERE id = ?', ['Webhook: ' . $info['last_error_message'], $bot['id']]);
        }
        return [
            'pending' => $info['pending_update_count'] ?? 0,
            'last_error' => $info['last_error_message'] ?? null,
            'last_error_date' => $info['last_error_date'] ?? null,
            'url' => $info['url'] ?? null,
            'privacy' => empty($me['can_read_all_group_messages']),
        ];
    }

    private function botDelete(): array
    {
        Auth::requireAdmin();
        $bot = DB::get('SELECT * FROM tg_bots WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy bot', 404);
        (new Telegram($bot['token']))->tryCall('deleteWebhook');
        DB::run('UPDATE tg_groups SET bot_id = NULL WHERE bot_id = ?', [$bot['id']]);
        DB::run('DELETE FROM tg_bots WHERE id = ?', [$bot['id']]);
        Repo::log(null, Auth::user()['username'], 'bot_delete', null, '@' . $bot['username']);
        return [];
    }

    // ---- Nhóm ----
    private function groupRow(array $g): array
    {
        return [
            'id' => (int)$g['id'],
            'chat_id' => (string)$g['chat_id'],
            'title' => $g['title'],
            'type' => $g['type'],
            'username' => $g['username'],
            'member_count' => (int)$g['member_count'],
            'tracked' => (int)($g['tracked'] ?? 0),
            'messages_today' => (int)($g['messages_today'] ?? 0),
            'bot_is_admin' => (int)$g['bot_is_admin'],
            'active' => (int)$g['active'],
            'bot_id' => $g['bot_id'] ? (int)$g['bot_id'] : null,
            'bot_username' => $g['bot_username'] ?? null,
            'created_at' => (int)$g['created_at'],
        ];
    }

    private function groups(): array
    {
        Auth::requireAdmin();
        $rows = DB::all("SELECT g.*, b.username AS bot_username,
            (SELECT COUNT(*) FROM tg_members m WHERE m.group_id = g.id AND m.is_bot = 0 AND m.status IN ('member','muted')) AS tracked,
            (SELECT COALESCE(SUM(messages),0) FROM daily_stats d WHERE d.group_id = g.id AND d.day = ?) AS messages_today
            FROM tg_groups g LEFT JOIN tg_bots b ON b.id = g.bot_id ORDER BY g.active DESC, g.updated_at DESC", [date('Y-m-d')]);
        return array_map([$this, 'groupRow'], $rows);
    }

    private function group(): array
    {
        Auth::requireAdmin();
        Repo::sweepMutes();
        $id = $this->int('id');
        $g = DB::get("SELECT g.*, b.username AS bot_username,
            (SELECT COUNT(*) FROM tg_members m WHERE m.group_id = g.id AND m.is_bot = 0 AND m.status IN ('member','muted')) AS tracked
            FROM tg_groups g LEFT JOIN tg_bots b ON b.id = g.bot_id WHERE g.id = ?", [$id]) ?: fail('Không tìm thấy nhóm', 404);
        $chart = [];
        $from = date('Y-m-d', strtotime('-13 day'));
        $byDay = [];
        foreach (DB::all('SELECT * FROM daily_stats WHERE group_id = ? AND day >= ?', [$id, $from]) as $r) $byDay[$r['day']] = $r;
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $chart[] = ['day' => $d, 'messages' => (int)($byDay[$d]['messages'] ?? 0), 'joins' => (int)($byDay[$d]['joins'] ?? 0), 'leaves' => (int)($byDay[$d]['leaves'] ?? 0)];
        }
        return [
            'group' => $this->groupRow($g),
            'settings' => Repo::settings($g),
            'chart' => $chart,
            'counts' => [
                'banned' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND status = 'banned'", [$id]),
                'muted' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND status = 'muted'", [$id]),
                'warned' => (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND warns > 0', [$id]),
                'admins' => (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_admin = 1', [$id]),
                'points' => (int)DB::val('SELECT COALESCE(SUM(points),0) FROM tg_members WHERE group_id = ?', [$id]),
            ],
            'bots' => array_map(fn($b) => ['id' => (int)$b['id'], 'username' => $b['username']], DB::all('SELECT id, username FROM tg_bots ORDER BY id')),
        ];
    }

    private function loadGroup(): array
    {
        return Repo::group($this->int('id') ?: $this->int('group_id')) ?: fail('Không tìm thấy nhóm', 404);
    }

    private function groupSettings(): array
    {
        $u = Auth::requireAdmin();
        $g = $this->loadGroup();
        $s = Repo::saveSettings($g, (array)$this->in('settings', []));
        $botId = $this->int('bot_id');
        if ($botId && DB::get('SELECT id FROM tg_bots WHERE id = ?', [$botId])) DB::run('UPDATE tg_groups SET bot_id = ? WHERE id = ?', [$botId, $g['id']]);
        Repo::log((int)$g['id'], $u['username'], 'settings', null, 'Cập nhật cài đặt nhóm');
        return ['settings' => $s];
    }

    private function groupSend(): array
    {
        $u = Auth::requireAdmin();
        $g = $this->loadGroup();
        $text = trim((string)$this->in('text'));
        if ($text === '') fail('Nội dung trống');
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot');
        try {
            $params = ['chat_id' => $g['chat_id'], 'text' => $text, 'link_preview_options' => ['is_disabled' => !$this->in('preview')]];
            if ($this->in('html')) $params['parse_mode'] = 'HTML';
            if ($this->in('silent')) $params['disable_notification'] = true;
            $r = $api->call('sendMessage', $params);
        } catch (TelegramException $e) {
            fail('Gửi thất bại: ' . $e->getMessage());
        }
        if ($this->in('pin')) $api->tryCall('pinChatMessage', ['chat_id' => $g['chat_id'], 'message_id' => $r['message_id']]);
        Repo::log((int)$g['id'], $u['username'], 'send', null, mb_substr($text, 0, 200));
        return ['message_id' => $r['message_id']];
    }

    private function groupSync(): array
    {
        Auth::requireAdmin();
        $g = $this->loadGroup();
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot');
        try {
            $chat = $api->call('getChat', ['chat_id' => $g['chat_id']]);
            $count = $api->call('getChatMemberCount', ['chat_id' => $g['chat_id']]);
        } catch (TelegramException $e) {
            if (stripos($e->getMessage(), 'not found') !== false || stripos($e->getMessage(), 'kicked') !== false) {
                DB::run('UPDATE tg_groups SET active = 0 WHERE id = ?', [$g['id']]);
            }
            fail('Không đồng bộ được: ' . $e->getMessage());
        }
        DB::run('UPDATE tg_groups SET title = ?, username = ?, member_count = ?, active = 1, updated_at = ? WHERE id = ?', [$chat['title'] ?? $g['title'], $chat['username'] ?? null, $count, now(), $g['id']]);
        $admins = Repo::tgAdmins(Repo::group((int)$g['id']), $api, true);
        return ['member_count' => $count, 'admins' => count($admins)];
    }

    private function groupLeave(): array
    {
        $u = Auth::requireAdmin();
        $g = $this->loadGroup();
        $api = Repo::apiForGroup($g);
        if ($api) $api->tryCall('leaveChat', ['chat_id' => $g['chat_id']]);
        DB::run('UPDATE tg_groups SET active = 0 WHERE id = ?', [$g['id']]);
        Repo::log((int)$g['id'], $u['username'], 'bot_removed', null, 'Bot rời nhóm (từ web)');
        return [];
    }

    private function groupDelete(): array
    {
        Auth::requireAdmin();
        $g = $this->loadGroup();
        Repo::deleteGroup((int)$g['id']);
        return [];
    }

    private function groupResetPoints(): array
    {
        $u = Auth::requireAdmin();
        $g = $this->loadGroup();
        DB::run('UPDATE tg_members SET points = 0 WHERE group_id = ?', [$g['id']]);
        Repo::log((int)$g['id'], $u['username'], 'reset_points', null, 'Đặt lại điểm toàn nhóm');
        return [];
    }

    // ---- Chat nhóm ----
    private const CHAT_SQL = 'SELECT c.*, mb.id AS member_id, mb.status, mb.is_admin, mb.warns, mb.points, mb.first_name AS m_first, mb.last_name AS m_last
        FROM tg_messages c LEFT JOIN tg_members mb ON mb.group_id = c.group_id AND mb.tg_user_id = c.tg_user_id';

    private function chatRow(array $r): array
    {
        return [
            'id' => (int)$r['id'],
            'message_id' => (string)$r['message_id'],
            'tg_user_id' => (string)$r['tg_user_id'],
            'name' => $r['name'],
            'username' => $r['username'],
            'is_bot' => (int)$r['is_bot'],
            'kind' => $r['kind'],
            'media' => $r['media'],
            'text' => $r['text'],
            'reply_to' => $r['reply_to'] ? (string)$r['reply_to'] : null,
            'reply_name' => $r['reply_name'],
            'reply_text' => $r['reply_text'],
            'deleted' => (int)$r['deleted'],
            'note' => $r['note'],
            'edited' => (int)$r['edited'],
            'pinned' => (int)$r['pinned'],
            'created_at' => (int)$r['created_at'],
            'member' => $r['member_id'] ? [
                'id' => (int)$r['member_id'],
                'status' => $r['status'],
                'is_admin' => (int)$r['is_admin'],
                'warns' => (int)$r['warns'],
                'points' => (int)$r['points'],
            ] : null,
        ];
    }

    /** 30 tin gần nhất, hoặc 30 tin cũ hơn before_id */
    private function chat(): array
    {
        Auth::requireAdmin();
        Repo::sweepMutes();
        $g = $this->loadGroup();
        $before = $this->int('before_id');
        $limit = max(10, min(100, $this->int('limit', 30)));
        $rows = $before
            ? DB::all(self::CHAT_SQL . " WHERE c.group_id = ? AND c.id < ? ORDER BY c.id DESC LIMIT $limit", [$g['id'], $before])
            : DB::all(self::CHAT_SQL . " WHERE c.group_id = ? ORDER BY c.id DESC LIMIT $limit", [$g['id']]);
        $rows = array_reverse($rows);
        $oldest = $rows ? (int)$rows[0]['id'] : 0;
        return [
            'messages' => array_map([$this, 'chatRow'], $rows),
            'has_more' => $oldest ? (bool)DB::val('SELECT 1 FROM tg_messages WHERE group_id = ? AND id < ? LIMIT 1', [$g['id'], $oldest]) : false,
            'warn_limit' => (int)Repo::settings($g)['warn_limit'],
            'server_time' => now(),
            'total' => (int)DB::val('SELECT COUNT(*) FROM tg_messages WHERE group_id = ?', [$g['id']]),
        ];
    }

    /** Tin mới sau after_id + tin đã thay đổi (xoá/sửa/ghim) từ since */
    private function chatPoll(): array
    {
        Auth::requireAdmin();
        $g = $this->loadGroup();
        $after = $this->int('after_id');
        $since = $this->int('since');
        $new = DB::all(self::CHAT_SQL . ' WHERE c.group_id = ? AND c.id > ? ORDER BY c.id ASC LIMIT 100', [$g['id'], $after]);
        $changed = $since ? DB::all(self::CHAT_SQL . ' WHERE c.group_id = ? AND c.id <= ? AND c.updated_at >= ? ORDER BY c.id LIMIT 100', [$g['id'], $after, $since]) : [];
        return [
            'messages' => array_map([$this, 'chatRow'], $new),
            'changed' => array_map([$this, 'chatRow'], $changed),
            'server_time' => now(),
        ];
    }

    private function chatMsg(): array
    {
        $c = DB::get('SELECT * FROM tg_messages WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy tin nhắn', 404);
        $g = Repo::group((int)$c['group_id']) ?: fail('Không tìm thấy nhóm', 404);
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot');
        return [$c, $g, $api];
    }

    private function chatSend(): array
    {
        $u = Auth::requireAdmin();
        $g = $this->loadGroup();
        $text = trim((string)$this->in('text'));
        if ($text === '') fail('Nội dung trống');
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot');
        $params = ['chat_id' => $g['chat_id'], 'text' => mb_substr($text, 0, 4096), 'link_preview_options' => ['is_disabled' => true]];
        if ($this->in('html')) $params['parse_mode'] = 'HTML';
        if ($rid = $this->int('reply_to')) $params['reply_parameters'] = ['message_id' => $rid, 'allow_sending_without_reply' => true];
        try {
            $r = $api->call('sendMessage', $params);
        } catch (TelegramException $e) {
            fail('Gửi thất bại: ' . $e->getMessage());
        }
        Repo::log((int)$g['id'], $u['username'], 'send', null, mb_substr($text, 0, 200));
        return ['message_id' => $r['message_id']];
    }

    private function chatDelete(): array
    {
        $u = Auth::requireAdmin();
        [$c, $g, $api] = $this->chatMsg();
        try {
            $api->call('deleteMessage', ['chat_id' => $g['chat_id'], 'message_id' => $c['message_id']]);
        } catch (TelegramException $e) {
            $m = $e->getMessage();
            if (stripos($m, 'not found') !== false || stripos($m, "can't be deleted") !== false) {
                ChatLog::markDeleted($g['chat_id'], $c['message_id'], 'Không còn trên Telegram');
            } else {
                fail('Không xoá được: ' . (stripos($m, 'rights') !== false ? 'bot chưa có quyền xoá tin' : $m));
            }
        }
        DB::run('UPDATE tg_messages SET note = ? WHERE id = ?', ['Xoá bởi ' . $u['username'] . ' (Web)', $c['id']]);
        Repo::log((int)$g['id'], $u['username'] . ' (Web)', 'delete', ['tg_user_id' => $c['tg_user_id'], 'first_name' => $c['name']], mb_substr((string)$c['text'], 0, 120));
        return [];
    }

    private function chatPin(): array
    {
        $u = Auth::requireAdmin();
        [$c, $g, $api] = $this->chatMsg();
        $unpin = (bool)$this->in('unpin');
        try {
            $api->call($unpin ? 'unpinChatMessage' : 'pinChatMessage', ['chat_id' => $g['chat_id'], 'message_id' => $c['message_id'], 'disable_notification' => true]);
        } catch (TelegramException $e) {
            fail('Không thực hiện được: ' . $e->getMessage());
        }
        DB::run('UPDATE tg_messages SET pinned = ?, updated_at = ? WHERE id = ?', [$unpin ? 0 : 1, now(), $c['id']]);
        Repo::log((int)$g['id'], $u['username'] . ' (Web)', $unpin ? 'unpin' : 'pin', null, mb_substr((string)$c['text'], 0, 120));
        return [];
    }

    // ---- Thành viên ----
    private function memberRow(array $m): array
    {
        return [
            'id' => (int)$m['id'],
            'group_id' => (int)$m['group_id'],
            'tg_user_id' => (string)$m['tg_user_id'],
            'name' => Repo::name($m),
            'username' => $m['username'],
            'is_bot' => (int)$m['is_bot'],
            'is_admin' => (int)$m['is_admin'],
            'status' => $m['status'],
            'messages' => (int)$m['messages'],
            'points' => (int)$m['points'],
            'warns' => (int)$m['warns'],
            'muted_until' => $m['muted_until'] ? (int)$m['muted_until'] : null,
            'note' => $m['note'],
            'joined_at' => $m['joined_at'] ? (int)$m['joined_at'] : null,
            'last_active' => $m['last_active'] ? (int)$m['last_active'] : null,
        ];
    }

    private function members(): array
    {
        Auth::requireAdmin();
        Repo::sweepMutes();
        $gid = $this->int('group_id');
        $where = ['group_id = ?'];
        $p = [$gid];
        $q = trim((string)$this->in('q', ''));
        if ($q !== '') {
            $where[] = '(LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ? OR LOWER(username) LIKE ? OR CAST(tg_user_id AS CHAR) LIKE ?)';
            $like = '%' . mb_strtolower(ltrim($q, '@')) . '%';
            array_push($p, $like, $like, $like, $like);
        }
        $status = (string)$this->in('status', '');
        if ($status === 'admin') $where[] = 'is_admin = 1';
        elseif ($status === 'warned') $where[] = 'warns > 0';
        elseif ($status === 'active') $where[] = "status IN ('member','muted')";
        elseif (in_array($status, ['member', 'muted', 'banned', 'left'], true)) { $where[] = 'status = ?'; $p[] = $status; }
        if (!$this->in('bots')) $where[] = 'is_bot = 0';
        $sorts = ['points' => 'points DESC', 'messages' => 'messages DESC', 'recent' => 'COALESCE(last_active,0) DESC', 'joined' => 'COALESCE(joined_at,0) DESC', 'warns' => 'warns DESC', 'name' => 'first_name ASC'];
        $sort = $sorts[$this->in('sort', 'points')] ?? $sorts['points'];
        $page = max(1, $this->int('page', 1));
        $per = 30;
        $w = implode(' AND ', $where);
        $total = (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE $w", $p);
        $rows = DB::all("SELECT * FROM tg_members WHERE $w ORDER BY $sort, id ASC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        return ['total' => $total, 'page' => $page, 'pages' => max(1, (int)ceil($total / $per)), 'rows' => array_map([$this, 'memberRow'], $rows)];
    }

    private function member(): array
    {
        Auth::requireAdmin();
        Repo::sweepMutes();
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy thành viên', 404);
        $g = Repo::group((int)$m['group_id']);
        $rank = 1 + (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND points > ?', [$m['group_id'], $m['points']]);
        $logs = DB::all('SELECT * FROM app_logs WHERE group_id = ? AND target_tg_id = ? ORDER BY id DESC LIMIT 30', [$m['group_id'], $m['tg_user_id']]);
        $other = DB::all('SELECT m.points, m.status, g.title, g.id FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE m.tg_user_id = ? AND m.group_id <> ?', [$m['tg_user_id'], $m['group_id']]);
        $webUser = DB::get('SELECT username, role FROM app_users WHERE tg_user_id = ?', [$m['tg_user_id']]);
        return [
            'member' => $this->memberRow($m),
            'rank' => $rank,
            'warn_limit' => (int)Repo::settings($g)['warn_limit'],
            'group' => ['id' => (int)$g['id'], 'title' => $g['title']],
            'logs' => $this->formatLogs($logs),
            'other_groups' => array_map(fn($r) => ['id' => (int)$r['id'], 'title' => $r['title'], 'points' => (int)$r['points'], 'status' => $r['status']], $other),
            'web_user' => $webUser,
        ];
    }

    private function memberAction(): array
    {
        $u = Auth::requireAdmin();
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy thành viên', 404);
        $action = (string)$this->in('action');
        if (!in_array($action, Moderation::ACTIONS, true)) fail('Hành động không hợp lệ');
        $g = Repo::group((int)$m['group_id']);
        try {
            $r = Moderation::perform($action, $g, [
                'tgUserId' => $m['tg_user_id'],
                'actor' => ($u['display_name'] ?: $u['username']) . ' (Web)',
                'reason' => (string)$this->in('reason', ''),
                'minutes' => $this->in('minutes') !== null && $this->in('minutes') !== '' ? (int)$this->in('minutes') : null,
                'amount' => $this->in('amount'),
                'notify' => (bool)$this->in('notify', true),
            ]);
        } catch (TelegramException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'not enough rights') !== false) $msg = 'Bot chưa có quyền quản trị (cấm/hạn chế thành viên) trong nhóm';
            if (stripos($msg, 'administrator') !== false) $msg = 'Không thể xử lý quản trị viên của nhóm';
            fail('Telegram: ' . $msg);
        }
        return ['member' => $this->memberRow($r)];
    }

    private function memberNote(): array
    {
        Auth::requireAdmin();
        DB::run('UPDATE tg_members SET note = ? WHERE id = ?', [mb_substr((string)$this->in('note', ''), 0, 1000), $this->int('id')]);
        return [];
    }

    /** Thêm thành viên theo ID để xử lý (ví dụ ban trước người chưa vào nhóm) */
    private function memberAdd(): array
    {
        Auth::requireAdmin();
        $g = $this->loadGroup();
        $tg = trim((string)$this->in('tg_user_id'));
        if (!preg_match('/^\d{4,}$/', $tg)) fail('ID Telegram không hợp lệ');
        $api = Repo::apiForGroup($g);
        $user = ['id' => (int)$tg, 'first_name' => $tg];
        $status = 'left';
        if ($api) {
            try {
                $cm = $api->call('getChatMember', ['chat_id' => $g['chat_id'], 'user_id' => (int)$tg]);
                $user = $cm['user'];
                $status = ['kicked' => 'banned', 'left' => 'left', 'restricted' => 'muted'][$cm['status']] ?? 'member';
            } catch (Throwable $e) {}
        }
        $m = Repo::upsertMember((int)$g['id'], $user, ['status' => $status]);
        return ['member' => $this->memberRow($m)];
    }

    // ---- Nhật ký ----
    private function formatLogs(array $rows): array
    {
        return array_map(fn($l) => [
            'id' => (int)$l['id'],
            'group_id' => $l['group_id'] ? (int)$l['group_id'] : null,
            'group_title' => $l['group_title'] ?? null,
            'actor' => $l['actor'],
            'action' => $l['action'],
            'target_tg_id' => $l['target_tg_id'] ? (string)$l['target_tg_id'] : null,
            'target_name' => $l['target_name'],
            'detail' => $l['detail'],
            'created_at' => (int)$l['created_at'],
        ], $rows);
    }

    private function logs(): array
    {
        Auth::requireAdmin();
        $where = ['1=1'];
        $p = [];
        if ($gid = $this->int('group_id')) { $where[] = 'l.group_id = ?'; $p[] = $gid; }
        if ($a = (string)$this->in('action', '')) {
            if ($a === 'moderation') $where[] = "l.action IN ('ban','unban','kick','mute','unmute','warn','unwarn','resetwarn','delete')";
            elseif ($a === 'points') $where[] = "l.action IN ('points','setpoints','reset_points')";
            elseif ($a === 'members') $where[] = "l.action IN ('join','leave')";
            elseif ($a === 'system') $where[] = "l.action NOT IN ('ban','unban','kick','mute','unmute','warn','unwarn','resetwarn','delete','points','setpoints','join','leave')";
        }
        if ($q = trim((string)$this->in('q', ''))) {
            $where[] = '(LOWER(l.target_name) LIKE ? OR LOWER(l.actor) LIKE ? OR CAST(l.target_tg_id AS CHAR) LIKE ?)';
            $like = '%' . mb_strtolower($q) . '%';
            array_push($p, $like, $like, $like);
        }
        $page = max(1, $this->int('page', 1));
        $per = 40;
        $w = implode(' AND ', $where);
        $total = (int)DB::val("SELECT COUNT(*) FROM app_logs l WHERE $w", $p);
        $rows = DB::all("SELECT l.*, g.title AS group_title FROM app_logs l LEFT JOIN tg_groups g ON g.id = l.group_id WHERE $w ORDER BY l.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        return ['total' => $total, 'page' => $page, 'pages' => max(1, (int)ceil($total / $per)), 'rows' => $this->formatLogs($rows)];
    }

    // ---- Tài khoản web ----
    private function users(): array
    {
        Auth::requireAdmin();
        return array_map([Auth::class, 'publicUser'], DB::all('SELECT * FROM app_users ORDER BY role ASC, id ASC'));
    }

    private function userSave(): array
    {
        $me = Auth::requireAdmin();
        $id = $this->int('id');
        $role = $this->in('role') === 'admin' ? 'admin' : 'member';
        $data = [
            'display_name' => mb_substr(trim((string)$this->in('display_name', '')), 0, 60),
            'role' => $role,
            'disabled' => $this->in('disabled') ? 1 : 0,
        ];
        $tg = trim((string)$this->in('tg_user_id', ''));
        $data['tg_user_id'] = preg_match('/^\d{4,}$/', $tg) ? $tg : null;
        $pw = (string)$this->in('password', '');
        if ($pw !== '') {
            if (strlen($pw) < 6) fail('Mật khẩu tối thiểu 6 ký tự');
            $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        }
        if ($id) {
            $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$id]) ?: fail('Không tìm thấy tài khoản', 404);
            if ($id === (int)$me['id'] && ($role !== 'admin' || $data['disabled'])) fail('Không thể tự hạ quyền hoặc khoá chính mình');
            DB::update('app_users', $data, 'id = ?', [$id]);
        } else {
            $username = trim((string)$this->in('username'));
            if (!preg_match('/^[a-zA-Z0-9_.]{3,32}$/', $username)) fail('Tên đăng nhập 3-32 ký tự (chữ, số, _ .)');
            if (DB::get('SELECT id FROM app_users WHERE LOWER(username) = LOWER(?)', [$username])) fail('Tên đăng nhập đã tồn tại');
            if ($pw === '') fail('Nhập mật khẩu');
            $data['username'] = $username;
            $data['display_name'] = $data['display_name'] ?: $username;
            $data['created_at'] = now();
            $id = DB::insert('app_users', $data);
        }
        return Auth::publicUser(DB::get('SELECT * FROM app_users WHERE id = ?', [$id]));
    }

    private function userDelete(): array
    {
        $me = Auth::requireAdmin();
        $id = $this->int('id');
        if ($id === (int)$me['id']) fail('Không thể xoá chính mình');
        DB::run('DELETE FROM app_users WHERE id = ?', [$id]);
        return [];
    }

    // ---- Lệnh tự tạo ----
    private function registerMenus(Telegram $api): void
    {
        $mem = [];
        $adm = [];
        foreach (DB::all("SELECT * FROM tg_rules WHERE enabled = 1 AND show_in_menu = 1 AND trigger_type = 'command' AND group_id IS NULL") as $r) {
            $c = json_decode($r['conditions'] ?: '{}', true) ?: [];
            $item = ['command' => $r['trigger_value'], 'description' => mb_substr($r['name'], 0, 250)];
            if (($c['who'] ?? 'all') !== 'admin') $mem[] = $item;
            $adm[] = $item;
        }
        $api->registerCommands($mem, $adm);
    }

    private function rules(): array
    {
        Auth::requireAdmin();
        $gid = $this->int('group_id');
        $sql = 'SELECT r.*, g.title AS group_title FROM tg_rules r LEFT JOIN tg_groups g ON g.id = r.group_id';
        $rows = $gid ? DB::all("$sql WHERE r.group_id IS NULL OR r.group_id = ? ORDER BY r.group_id IS NULL, r.priority DESC, r.id", [$gid])
            : DB::all("$sql ORDER BY r.group_id IS NOT NULL, r.priority DESC, r.id");
        return [
            'rules' => array_map([Rules::class, 'row'], $rows),
            'menu_admin_only' => Telegram::menuAdminOnly(),
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT id, title FROM tg_groups ORDER BY active DESC, title')),
        ];
    }

    private function ruleSave(): array
    {
        $u = Auth::requireAdmin();
        $data = Rules::sanitize($this->in);
        $id = $this->int('id');
        if ($data['trigger_type'] === 'command') {
            $dup = DB::get("SELECT id FROM tg_rules WHERE trigger_type = 'command' AND trigger_value = ? AND id <> ? AND ((group_id IS NULL AND ? IS NULL) OR group_id = ?)",
                [$data['trigger_value'], $id, $data['group_id'], $data['group_id']]);
            if ($dup) fail('Đã có lệnh /' . $data['trigger_value'] . ' trong phạm vi này');
        }
        if ($id) {
            DB::get('SELECT id FROM tg_rules WHERE id = ?', [$id]) ?: fail('Không tìm thấy lệnh', 404);
            DB::update('tg_rules', $data, 'id = ?', [$id]);
        } else {
            $data['created_at'] = now();
            $id = DB::insert('tg_rules', $data);
        }
        Repo::log($data['group_id'], $u['username'], 'rule', null, 'Lưu lệnh: ' . $data['name']);
        $errors = Rules::syncMenus();
        return ['rule' => Rules::row(DB::get('SELECT r.*, g.title AS group_title FROM tg_rules r LEFT JOIN tg_groups g ON g.id = r.group_id WHERE r.id = ?', [$id])), 'menu_errors' => $errors];
    }

    private function ruleDelete(): array
    {
        $u = Auth::requireAdmin();
        $r = DB::get('SELECT * FROM tg_rules WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy lệnh', 404);
        DB::run('DELETE FROM tg_rules WHERE id = ?', [$r['id']]);
        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = ?', [$r['id']]);
        Repo::log($r['group_id'] ? (int)$r['group_id'] : null, $u['username'], 'rule', null, 'Xoá lệnh: ' . $r['name']);
        Rules::syncMenus();
        return [];
    }

    /** Bật/tắt hàng loạt: enabled hoặc show_in_menu cho danh sách id */
    private function ruleBulk(): array
    {
        $u = Auth::requireAdmin();
        $field = $this->in('field');
        if (!in_array($field, ['enabled', 'show_in_menu'], true)) fail('Trường không hợp lệ');
        $ids = array_values(array_filter(array_map('intval', (array)$this->in('ids', [])), fn($x) => $x > 0));
        if (!$ids) fail('Không có lệnh nào');
        $v = $this->in('value') ? 1 : 0;
        $where = 'id IN (' . implode(',', $ids) . ')' . ($field === 'show_in_menu' ? " AND trigger_type = 'command'" : '');
        $n = DB::run("UPDATE tg_rules SET $field = ? WHERE $where", [$v]);
        $label = $field === 'enabled' ? ($v ? 'Bật' : 'Tắt') . ' tất cả lệnh' : ($v ? 'Hiện' : 'Ẩn') . ' tất cả lệnh trong menu Telegram';
        Repo::log(null, $u['username'], 'rule', null, "$label ($n lệnh)");
        $errors = Rules::syncMenus();
        return ['updated' => $n, 'menu_errors' => $errors];
    }

    /** Ai thấy menu lệnh khi gõ "/" trong nhóm: mọi người hoặc chỉ admin */
    private function ruleMenuMode(): array
    {
        $u = Auth::requireAdmin();
        $on = (bool)$this->in('admin_only');
        Settings::set('menu_admin_only', $on ? '1' : '0');
        Repo::log(null, $u['username'], 'rule', null, $on ? 'Menu lệnh: chỉ admin thấy' : 'Menu lệnh: mọi người thấy');
        return ['admin_only' => $on, 'menu_errors' => Rules::syncMenus()];
    }

    private function ruleToggle(): array
    {
        Auth::requireAdmin();
        DB::run('UPDATE tg_rules SET enabled = ? WHERE id = ?', [$this->in('enabled') ? 1 : 0, $this->int('id')]);
        Rules::syncMenus();
        return [];
    }

    // ---- Hệ thống ----
    private function system(): array
    {
        Auth::requireAdmin();
        return [
            'settings' => Settings::publicAll(),
            'version' => Updater::current(),
            'detected_url' => (function () {
                $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
                $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
                return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $dir;
            })(),
            'cron_url' => site_url() . '/cron.php?key=' . Settings::get('cron_key'),
            'env' => [
                'php' => PHP_VERSION,
                'db' => DB::driver(),
                'zip' => class_exists('ZipArchive'),
                'curl' => extension_loaded('curl'),
                'writable' => is_writable(ROOT) && is_writable(ROOT . '/app'),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            ],
        ];
    }

    private function systemSettings(): array
    {
        $u = Auth::requireAdmin();
        $in = (array)$this->in('settings', []);
        $urlChanged = false;
        foreach (['site_name', 'site_url', 'allow_register', 'update_repo', 'update_branch', 'update_token'] as $k) {
            if (!array_key_exists($k, $in)) continue;
            $v = trim((string)$in[$k]);
            if ($k === 'update_token' && $v === '••••••') continue;
            if ($k === 'allow_register') $v = $v && $v !== '0' ? '1' : '0';
            if ($k === 'site_url') {
                $v = rtrim($v, '/');
                if ($v !== '' && !preg_match('~^https?://~', $v)) fail('Địa chỉ web phải bắt đầu bằng http:// hoặc https://');
                $urlChanged = $v !== Settings::get('site_url');
            }
            Settings::set($k, $v);
        }
        $errors = [];
        if ($urlChanged) {
            foreach (DB::all('SELECT * FROM tg_bots WHERE enabled = 1') as $b) {
                try { $this->setWebhook($b); } catch (HttpError $e) { $errors[] = '@' . $b['username'] . ': ' . $e->getMessage(); }
            }
        }
        Repo::log(null, $u['username'], 'system', null, 'Cập nhật cài đặt hệ thống');
        return ['settings' => Settings::publicAll(), 'webhook_errors' => $errors];
    }

    private function checkUpdate(): array
    {
        Auth::requireAdmin();
        return Updater::check();
    }

    private function doUpdate(): array
    {
        $u = Auth::requireAdmin();
        $r = Updater::update();
        Repo::log(null, $u['username'], 'update', null, 'Cập nhật lên ' . substr($r['sha'], 0, 7));
        return $r;
    }

    private function migrate(): array
    {
        Auth::requireAdmin();
        DB::migrate();
        return ['schema' => DB::SCHEMA_VERSION];
    }
}

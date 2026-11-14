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
            'auth/tg-code' => 'tgCode',
            'auth/tg-check' => 'tgCheck',
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
            'commands' => 'commands',
            'commands/save' => 'commandSave',
            'commands/reset' => 'commandReset',
            'commands/feature' => 'commandFeature',
            'ads' => 'ads',
            'ads/image' => 'adImage',
            'ads/campaign/save' => 'adCampaignSave',
            'ads/campaign/delete' => 'adCampaignDelete',
            'ads/campaign/run' => 'adCampaignRun',
            'ads/campaign/stop' => 'adCampaignStop',
            'ads/ad/save' => 'adSave',
            'ads/ad/delete' => 'adDelete',
            'ads/ad/move' => 'adMove',
            'system' => 'system',
            'system/settings' => 'systemSettings',
            'system/check-update' => 'checkUpdate',
            'system/update' => 'doUpdate',
            'system/migrate' => 'migrate',
            'system/test-update-notice' => 'testUpdateNotice',
            'wallet' => 'wallet',
            'wallet/check' => 'walletCheck',
            'amnesty' => 'amnesty',
            'amnesty/search' => 'amnestySearch',
            'amnesty/do' => 'amnestyDo',
            'amnesty/lookup' => 'amnestyLookup',
            'payments' => 'payments',
            'payments/save' => 'paymentsSave',
            'payments/test' => 'paymentsTest',
            'payments/sync' => 'paymentsSync',
            'payments/assign' => 'paymentsAssign',
            'payments/ignore' => 'paymentsIgnore',
            'users/balance' => 'userBalance',
            'users/tx' => 'userTx',
        ];
        $writeRoutes = ['install', 'auth/login', 'auth/register', 'auth/logout', 'me/update', 'me/link-code', 'me/unlink'];
        if (!isset($map[$route])) fail('Không tìm thấy API', 404);
        $fn = $map[$route];
        $isWrite = in_array($route, $writeRoutes, true) || str_contains($route, '/') && !in_array($route, ['system/check-update', 'chat/poll', 'ads/image', 'amnesty/search', 'users/tx'], true);
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
            'access' => $inst && Auth::user() ? Access::summary(Auth::user()) : null,
            'site_name' => $inst ? Settings::get('site_name') : 'Tele Manager',
            'allow_register' => $inst ? Settings::get('allow_register') === '1' : false,
            'tg_login' => $inst && TgLogin::enabled() ? ['bots' => TgLogin::bots(), 'cmd' => Commands::name('login')] : null,
            'pay' => $inst ? ['wallet' => Wallet::config()['enabled'] && Wallet::config()['account_no'] !== '', 'amnesty' => Amnesty::config()['enabled']] : null,
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
        if ($u && $u['password_hash'] === '') fail('Tài khoản này chưa đặt mật khẩu. Hãy dùng "Đăng nhập bằng Telegram".', 401);
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

    /** Tạo mã đăng nhập bằng Telegram cho phiên trình duyệt hiện tại */
    private function tgCode(): array
    {
        if (!TgLogin::enabled()) fail('Đăng nhập bằng Telegram đang tắt', 403);
        Auth::throttle('tgcode', 15, 600);
        $r = TgLogin::create();
        $r['bots'] = TgLogin::bots();
        return $r;
    }

    private function tgCheck(): array
    {
        return TgLogin::check((string)$this->in('code', ''));
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
            'access' => Access::summary($u),
            'link_code' => $u['link_code'],
            'bots' => array_map(fn($b) => $b['username'], DB::all("SELECT username FROM tg_bots WHERE enabled = 1 AND username IS NOT NULL ORDER BY id")),
            'link_cmd' => Commands::name('link'),
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
            // Tài khoản tạo từ Telegram chưa có mật khẩu: được đặt mới không cần mật khẩu cũ
            if ($u['password_hash'] !== '' && !password_verify((string)$this->in('current_password'), $u['password_hash'])) fail('Mật khẩu hiện tại không đúng');
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
        if (!Access::can($u, 'view', $gid)) {
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
        $u = Auth::requireUser();
        Access::require($u, 'view');
        $ids = Access::groupIds($u); // null = tất cả
        $web = Access::isWebAdmin($u);
        $g = fn(string $col) => Access::sqlIn($ids, $col);
        Repo::sweepMutes();
        $today = date('Y-m-d');
        $days = [];
        for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = ['messages' => 0, 'joins' => 0, 'leaves' => 0];
        $from = array_key_first($days);
        foreach (DB::all('SELECT day, SUM(messages) AS m, SUM(joins) AS j, SUM(leaves) AS l FROM daily_stats WHERE day >= ? AND ' . $g('group_id') . ' GROUP BY day', [$from]) as $r) {
            if (isset($days[$r['day']])) $days[$r['day']] = ['messages' => (int)$r['m'], 'joins' => (int)$r['j'], 'leaves' => (int)$r['l']];
        }
        $chart = [];
        foreach ($days as $d => $v) $chart[] = ['day' => $d] + $v;

        return [
            'stats' => [
                'groups' => (int)DB::val('SELECT COUNT(*) FROM tg_groups WHERE active = 1 AND ' . $g('id')),
                'members' => (int)DB::val("SELECT COUNT(DISTINCT tg_user_id) FROM tg_members WHERE is_bot = 0 AND status IN ('member','muted') AND " . $g('group_id')),
                'messages_today' => (int)DB::val('SELECT COALESCE(SUM(messages),0) FROM daily_stats WHERE day = ? AND ' . $g('group_id'), [$today]),
                'joins_today' => (int)DB::val('SELECT COALESCE(SUM(joins),0) FROM daily_stats WHERE day = ? AND ' . $g('group_id'), [$today]),
                'actions_24h' => (int)DB::val("SELECT COUNT(*) FROM app_logs WHERE created_at > ? AND action IN ('ban','kick','mute','warn','delete') AND " . $g('group_id'), [now() - 86400]),
                'banned' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE status = 'banned' AND " . $g('group_id')),
                'muted' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE status = 'muted' AND " . $g('group_id')),
                'bots' => $web ? (int)DB::val('SELECT COUNT(*) FROM tg_bots WHERE enabled = 1') : null,
                'users' => $web ? (int)DB::val('SELECT COUNT(*) FROM app_users') : null,
            ],
            'chart' => $chart,
            'top_members' => array_map(fn($r) => [
                'id' => (int)$r['id'], 'name' => Repo::name($r), 'username' => $r['username'], 'points' => (int)$r['points'], 'group' => $r['title'], 'tg_user_id' => (string)$r['tg_user_id'],
            ], DB::all("SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE m.is_bot = 0 AND m.status <> 'banned' AND " . $g('m.group_id') . ' ORDER BY m.points DESC LIMIT 5')),
            'recent_logs' => Access::can($u, 'logs') ? $this->formatLogs(DB::all('SELECT l.*, g.title AS group_title FROM app_logs l LEFT JOIN tg_groups g ON g.id = l.group_id WHERE ' . $g('l.group_id') . ' ORDER BY l.id DESC LIMIT 8')) : [],
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
        $u = $this->gate('view');
        $rows = DB::all("SELECT g.*, b.username AS bot_username,
            (SELECT COUNT(*) FROM tg_members m WHERE m.group_id = g.id AND m.is_bot = 0 AND m.status IN ('member','muted')) AS tracked,
            (SELECT COALESCE(SUM(messages),0) FROM daily_stats d WHERE d.group_id = g.id AND d.day = ?) AS messages_today
            FROM tg_groups g LEFT JOIN tg_bots b ON b.id = g.bot_id WHERE " . Access::sqlIn(Access::groupIds($u), 'g.id') . " ORDER BY g.active DESC, g.updated_at DESC", [date('Y-m-d')]);
        return array_map([$this, 'groupRow'], $rows);
    }

    private function group(): array
    {
        $id = $this->int('id');
        $u = $this->gate('view', ['id' => $id]);
        Repo::sweepMutes();
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
            'access' => Access::summary($u)['perms'],
            'bots' => !Access::isWebAdmin($u) ? [] : array_map(fn($b) => ['id' => (int)$b['id'], 'username' => $b['username']], DB::all('SELECT id, username FROM tg_bots ORDER BY id')),
        ];
    }

    /** Kiểm tra quyền theo nhóm; trả về người dùng hiện tại */
    private function gate(string $perm, ?array $group = null): array
    {
        $u = Auth::requireUser();
        Access::require($u, $perm, $group ? (int)$group['id'] : null);
        return $u;
    }

    private function loadGroup(): array
    {
        return Repo::group($this->int('id') ?: $this->int('group_id')) ?: fail('Không tìm thấy nhóm', 404);
    }

    private function groupSettings(): array
    {
        $g = $this->loadGroup();
        $u = $this->gate('settings', $g);
        $s = Repo::saveSettings($g, (array)$this->in('settings', []));
        $botId = $this->int('bot_id');
        if ($botId && Access::isWebAdmin($u) && DB::get('SELECT id FROM tg_bots WHERE id = ?', [$botId])) DB::run('UPDATE tg_groups SET bot_id = ? WHERE id = ?', [$botId, $g['id']]);
        Repo::log((int)$g['id'], $u['username'], 'settings', null, 'Cập nhật cài đặt nhóm');
        return ['settings' => $s];
    }

    private function groupSend(): array
    {
        $g = $this->loadGroup();
        $u = $this->gate('send', $g);
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
        $g = $this->loadGroup();
        $this->gate('view', $g);
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
        $g = $this->loadGroup();
        $u = $this->gate('danger', $g);
        $api = Repo::apiForGroup($g);
        if ($api) $api->tryCall('leaveChat', ['chat_id' => $g['chat_id']]);
        DB::run('UPDATE tg_groups SET active = 0 WHERE id = ?', [$g['id']]);
        Repo::log((int)$g['id'], $u['username'], 'bot_removed', null, 'Bot rời nhóm (từ web)');
        return [];
    }

    private function groupDelete(): array
    {
        $g = $this->loadGroup();
        $u = $this->gate('danger', $g);
        Repo::deleteGroup((int)$g['id']);
        return [];
    }

    private function groupResetPoints(): array
    {
        $g = $this->loadGroup();
        $u = $this->gate('danger', $g);
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
        $g = $this->loadGroup();
        $this->gate('chat', $g);
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
        $g = $this->loadGroup();
        $this->gate('chat', $g);
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
        $g = $this->loadGroup();
        $u = $this->gate('send', $g);
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
        [$c, $g, $api] = $this->chatMsg();
        $u = $this->gate('chat', $g);
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
        Repo::log((int)$g['id'], $u['username'] . ' (Web)', 'delete', ['tg_user_id' => $c['tg_user_id'], 'first_name' => $c['name']], 'Xoá tin nhắn',
            ['source' => 'web', 'link' => Moderation::link($g, (int)$c['message_id'])], Moderation::evidenceFromChat($c));
        return [];
    }

    private function chatPin(): array
    {
        [$c, $g, $api] = $this->chatMsg();
        $u = $this->gate('chat', $g);
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
        $gid = $this->int('group_id');
        $this->gate('view', ['id' => $gid]);
        Repo::sweepMutes();
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
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy thành viên', 404);
        $this->gate('view', ['id' => (int)$m['group_id']]);
        Repo::sweepMutes();
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
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy thành viên', 404);
        $action = (string)$this->in('action');
        if (!in_array($action, Moderation::ACTIONS, true)) fail('Hành động không hợp lệ');
        $u = $this->gate(in_array($action, ['points', 'setpoints'], true) ? 'points' : 'moderate', ['id' => (int)$m['group_id']]);
        if (!Access::isWebAdmin($u) && $m['is_admin'] && in_array($action, ['ban', 'kick', 'mute', 'warn'], true)) fail('Quản trị viên nhóm không thể xử lý quản trị viên khác', 403);
        $g = Repo::group((int)$m['group_id']);
        try {
            // Thao tác từ tab Chat: lấy tin nhắn đó làm bằng chứng
            $ev = null;
            if ($cid = $this->int('chat_msg_id')) {
                $row = DB::get('SELECT * FROM tg_messages WHERE id = ? AND group_id = ?', [$cid, $g['id']]);
                if ($row) $ev = Moderation::evidenceFromChat($row);
            }
            $r = Moderation::perform($action, $g, [
                'tgUserId' => $m['tg_user_id'],
                'actor' => ($u['display_name'] ?: $u['username']) . ' (Web)',
                'actorTgId' => $u['tg_user_id'],
                'source' => 'web',
                'evidence' => $ev,
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
        $m = DB::get('SELECT group_id FROM tg_members WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy thành viên', 404);
        $this->gate('moderate', ['id' => (int)$m['group_id']]);
        DB::run('UPDATE tg_members SET note = ? WHERE id = ?', [mb_substr((string)$this->in('note', ''), 0, 1000), $this->int('id')]);
        return [];
    }

    /** Thêm thành viên theo ID để xử lý (ví dụ ban trước người chưa vào nhóm) */
    private function memberAdd(): array
    {
        $g = $this->loadGroup();
        $this->gate('moderate', $g);
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
            'meta' => !empty($l['meta']) ? (json_decode($l['meta'], true) ?: null) : null,
            'evidence' => !empty($l['evidence']) ? (json_decode($l['evidence'], true) ?: null) : null,
            'created_at' => (int)$l['created_at'],
        ], $rows);
    }

    private function logs(): array
    {
        $u = Auth::requireUser();
        $gid = $this->int('group_id');
        Access::require($u, 'logs', $gid ?: null);
        $where = [Access::sqlIn(Access::groupIds($u), 'l.group_id')];
        $p = [];
        if ($gid) { $where[] = 'l.group_id = ?'; $p[] = $gid; }
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
        return array_map(function ($x) {
            $row = Auth::publicUser($x);
            $row['managed_groups'] = $x['role'] === 'admin' ? null : count(Access::groupIds($x) ?? []);
            return $row;
        }, DB::all('SELECT * FROM app_users ORDER BY role ASC, id ASC'));
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

    /** Lệnh có sửa được bởi người dùng này không: lệnh dùng chung chỉ admin web */
    private function canEditRule(array $u, ?int $groupId): bool
    {
        return $groupId === null ? Access::isWebAdmin($u) : Access::can($u, 'rules', $groupId);
    }

    private function rules(): array
    {
        $u = Auth::requireUser();
        $gid = $this->int('group_id');
        Access::require($u, 'view', $gid ?: null);
        $ids = Access::groupIds($u);
        $sql = 'SELECT r.*, g.title AS group_title FROM tg_rules r LEFT JOIN tg_groups g ON g.id = r.group_id';
        // Quản trị viên nhóm: thấy lệnh của nhóm mình + lệnh dùng chung (chỉ xem)
        $scope = '(r.group_id IS NULL OR ' . Access::sqlIn($ids, 'r.group_id') . ')';
        $rows = $gid ? DB::all("$sql WHERE (r.group_id IS NULL OR r.group_id = ?) ORDER BY r.group_id IS NULL, r.priority DESC, r.id", [$gid])
            : DB::all("$sql WHERE $scope ORDER BY r.group_id IS NOT NULL, r.priority DESC, r.id");
        return [
            'rules' => array_map(function ($r) use ($u) {
                $row = Rules::row($r);
                $row['can_edit'] = $this->canEditRule($u, $row['group_id']);
                return $row;
            }, $rows),
            'menu_admin_only' => Telegram::menuAdminOnly(),
            'web_admin' => Access::isWebAdmin($u),
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']],
                DB::all('SELECT id, title FROM tg_groups WHERE ' . Access::sqlIn($ids, 'id') . ' ORDER BY active DESC, title')),
        ];
    }

    private function ruleSave(): array
    {
        $u = Auth::requireUser();
        $data = Rules::sanitize($this->in);
        $id = $this->int('id');
        if (!$this->canEditRule($u, $data['group_id'])) {
            fail($data['group_id'] === null ? 'Chỉ admin web được tạo lệnh dùng chung cho tất cả nhóm. Hãy chọn nhóm của bạn ở mục "Áp dụng cho".' : 'Bạn không có quyền sửa lệnh của nhóm này', 403);
        }
        if ($data['trigger_type'] === 'command') {
            $dup = DB::get("SELECT id FROM tg_rules WHERE trigger_type = 'command' AND trigger_value = ? AND id <> ? AND ((group_id IS NULL AND ? IS NULL) OR group_id = ?)",
                [$data['trigger_value'], $id, $data['group_id'], $data['group_id']]);
            if ($dup) fail('Đã có lệnh /' . $data['trigger_value'] . ' trong phạm vi này');
        }
        if ($id) {
            $old = DB::get('SELECT * FROM tg_rules WHERE id = ?', [$id]) ?: fail('Không tìm thấy lệnh', 404);
            if (!$this->canEditRule($u, $old['group_id'] ? (int)$old['group_id'] : null)) fail('Bạn không có quyền sửa lệnh này', 403);
            DB::update('tg_rules', $data, 'id = ?', [$id]);
        } else {
            $data['created_at'] = now();
            $id = DB::insert('tg_rules', $data);
        }
        Repo::log($data['group_id'], $u['username'], 'rule', null, 'Lưu lệnh: ' . $data['name']);
        $errors = Rules::syncMenus();
        $row = Rules::row(DB::get('SELECT r.*, g.title AS group_title FROM tg_rules r LEFT JOIN tg_groups g ON g.id = r.group_id WHERE r.id = ?', [$id]));
        $row['can_edit'] = true;
        return ['rule' => $row, 'menu_errors' => $errors];
    }

    private function loadRuleForEdit(array $u): array
    {
        $r = DB::get('SELECT * FROM tg_rules WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy lệnh', 404);
        if (!$this->canEditRule($u, $r['group_id'] ? (int)$r['group_id'] : null)) fail('Chỉ admin web được sửa lệnh dùng chung', 403);
        return $r;
    }

    private function ruleDelete(): array
    {
        $u = Auth::requireUser();
        $r = $this->loadRuleForEdit($u);
        DB::run('DELETE FROM tg_rules WHERE id = ?', [$r['id']]);
        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = ?', [$r['id']]);
        Repo::log($r['group_id'] ? (int)$r['group_id'] : null, $u['username'], 'rule', null, 'Xoá lệnh: ' . $r['name']);
        Rules::syncMenus();
        return [];
    }

    /** Bật/tắt hàng loạt: enabled hoặc show_in_menu cho danh sách id (chỉ các lệnh được phép sửa) */
    private function ruleBulk(): array
    {
        $u = Auth::requireUser();
        Access::require($u, 'view');
        $field = $this->in('field');
        if (!in_array($field, ['enabled', 'show_in_menu'], true)) fail('Trường không hợp lệ');
        $ids = array_values(array_filter(array_map('intval', (array)$this->in('ids', [])), fn($x) => $x > 0));
        if (!$ids) fail('Không có lệnh nào');
        $allowed = [];
        foreach (DB::all('SELECT id, group_id FROM tg_rules WHERE id IN (' . implode(',', $ids) . ')') as $r) {
            if ($this->canEditRule($u, $r['group_id'] ? (int)$r['group_id'] : null)) $allowed[] = (int)$r['id'];
        }
        if (!$allowed) fail('Bạn không có quyền sửa các lệnh này', 403);
        $v = $this->in('value') ? 1 : 0;
        $where = 'id IN (' . implode(',', $allowed) . ')' . ($field === 'show_in_menu' ? " AND trigger_type = 'command'" : '');
        $n = DB::run("UPDATE tg_rules SET $field = ? WHERE $where", [$v]);
        $label = $field === 'enabled' ? ($v ? 'Bật' : 'Tắt') . ' tất cả lệnh' : ($v ? 'Hiện' : 'Ẩn') . ' tất cả lệnh trong menu Telegram';
        Repo::log(null, $u['username'], 'rule', null, "$label ($n lệnh)");
        $errors = Rules::syncMenus();
        return ['updated' => $n, 'skipped' => count($ids) - count($allowed), 'menu_errors' => $errors];
    }

    // ---- Lệnh mặc định ----
    private function commands(): array
    {
        $u = Auth::requireUser();
        Access::require($u, 'view');
        // Lệnh tự tạo trùng tên lệnh mặc định (lệnh tự tạo được ưu tiên)
        $ruleNames = [];
        foreach (DB::all("SELECT r.trigger_value, r.name, g.title FROM tg_rules r LEFT JOIN tg_groups g ON g.id = r.group_id WHERE r.trigger_type = 'command' AND r.enabled = 1") as $r) {
            $ruleNames[$r['trigger_value']][] = $r['title'] ?: 'Tất cả nhóm';
        }
        $rows = array_map(function ($c) use ($ruleNames) {
            $c['overridden'] = [];
            if ($c['scope'] !== 'private') foreach ($c['names'] as $n) if (isset($ruleNames[$n])) $c['overridden'][] = ['name' => $n, 'where' => $ruleNames[$n]];
            return $c;
        }, Commands::all());
        return [
            'commands' => $rows,
            'can_edit' => Access::isWebAdmin($u),
            'bot' => TgLogin::bots()[0] ?? null,
            'features' => [
                'require_username' => UsernameGuard::config() + ['actions' => UsernameGuard::ACTIONS, 'default_text' => UsernameGuard::DEFAULTS['text']],
            ],
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT id, title FROM tg_groups WHERE active = 1 ORDER BY title')),
        ];
    }

    private function commandSave(): array
    {
        $u = Auth::requireAdmin();
        $key = (string)$this->in('key');
        $row = Commands::save($key, $this->in);
        Repo::log(null, $u['username'], 'system', null, 'Sửa lệnh mặc định /' . $row['original'] . ($row['aliases'] ? ' → /' . implode(', /', $row['aliases']) : '') . ($row['enabled'] ? '' : ' (tắt)'));
        return ['command' => $row, 'menu_errors' => Rules::syncMenus()];
    }

    /** Lưu tính năng mặc định (hiện có: yêu cầu đặt username) */
    private function commandFeature(): array
    {
        $u = Auth::requireAdmin();
        if ($this->in('feature') !== 'require_username') fail('Tính năng không hợp lệ');
        $c = UsernameGuard::save((array)$this->in('config', []));
        Repo::log(null, $u['username'], 'system', null, 'Yêu cầu đặt username: ' . ($c['enabled'] ? 'bật · ' . UsernameGuard::ACTIONS[$c['action']] : 'tắt'));
        return ['config' => $c];
    }

    private function commandReset(): array
    {
        $u = Auth::requireAdmin();
        $key = (string)$this->in('key', '');
        Commands::reset($key !== '' ? $key : null);
        Repo::log(null, $u['username'], 'system', null, $key !== '' ? "Đặt lại lệnh mặc định /$key" : 'Đặt lại toàn bộ lệnh mặc định');
        return ['menu_errors' => Rules::syncMenus()];
    }

    /** Ai thấy menu lệnh khi gõ "/" trong nhóm: mọi người hoặc chỉ admin (chỉ admin web) */
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
        $u = Auth::requireUser();
        $r = $this->loadRuleForEdit($u);
        DB::run('UPDATE tg_rules SET enabled = ? WHERE id = ?', [$this->in('enabled') ? 1 : 0, $r['id']]);
        Rules::syncMenus();
        return [];
    }

    // ---- Quảng cáo xoay vòng (chỉ admin web) ----
    private function ads(): array
    {
        Auth::requireAdmin();
        $camps = [];
        foreach (DB::all('SELECT * FROM tg_ad_campaigns ORDER BY id') as $c) {
            $row = Ads::campaignRow($c);
            $row['ads'] = array_map([Ads::class, 'adRow'], DB::all('SELECT * FROM tg_ads WHERE campaign_id = ? ORDER BY sort, id', [$c['id']]));
            $row['posts'] = array_map(fn($p) => [
                'group' => $p['title'], 'message_id' => (int)$p['message_id'], 'ad_id' => (int)$p['ad_id'],
                'created_at' => (int)$p['created_at'], 'last_rotate_at' => (int)$p['last_rotate_at'],
                'link' => Moderation::link($p, (int)$p['message_id']),
            ], DB::all('SELECT p.*, g.title, g.username, g.chat_id FROM tg_ad_posts p JOIN tg_groups g ON g.id = p.group_id WHERE p.campaign_id = ? AND p.active = 1', [$c['id']]));
            $camps[] = $row;
        }
        return [
            'campaigns' => $camps,
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'], 'bot' => $g['bot_username']],
                DB::all('SELECT g.id, g.title, b.username AS bot_username FROM tg_groups g LEFT JOIN tg_bots b ON b.id = g.bot_id WHERE g.active = 1 ORDER BY g.title')),
            'last_tick' => (int)Settings::get('ads_last_tick') ?: null,
            'cron_url' => site_url() . '/cron.php?key=' . Settings::get('cron_key'),
            'cron_path' => ROOT . '/cron.php',
            'cron_cmd' => cron_command(),
            'intervals' => Ads::INTERVALS,
        ];
    }

    /** Ảnh quảng cáo (xem trước trên web) */
    private function adImage(): array
    {
        Auth::requireAdmin();
        $a = DB::get('SELECT image FROM tg_ads WHERE id = ?', [$this->int('id')]);
        $path = $a ? Ads::imagePath($a['image']) : null;
        if (!$path) fail('Không có ảnh', 404);
        header('Content-Type: ' . file_mime($path));
        header('Cache-Control: private, max-age=86400');
        readfile($path);
        exit;
    }

    private function loadCampaign(): array
    {
        return DB::get('SELECT * FROM tg_ad_campaigns WHERE id = ?', [$this->int('id') ?: $this->int('campaign_id')]) ?: fail('Không tìm thấy chiến dịch', 404);
    }

    private function adCampaignSave(): array
    {
        $u = Auth::requireAdmin();
        $id = $this->int('id');
        $old = $id ? $this->loadCampaign() : null;
        $name = trim(mb_substr((string)$this->in('name', ''), 0, 120));
        if ($name === '') fail('Nhập tên chiến dịch');
        $groups = array_values(array_unique(array_map('intval', array_filter((array)$this->in('group_ids', []), 'is_numeric'))));
        $interval = (int)$this->in('interval_sec', 10);
        if (!in_array($interval, Ads::INTERVALS, true)) $interval = 10;
        $time = (string)$this->in('daily_time', '00:00');
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) fail('Giờ đăng bài mới không hợp lệ (HH:MM)');
        $data = [
            'name' => $name,
            'enabled' => $this->in('enabled', true) ? 1 : 0,
            'group_ids' => json_encode($groups),
            'bot_mode' => $this->in('bot_mode') === 'custom' ? 'custom' : 'default',
            'interval_sec' => $interval,
            'rotate_order' => $this->in('rotate_order') === 'random' ? 'random' : 'sequence',
            'daily_new' => $this->in('daily_new', true) ? 1 : 0,
            'daily_time' => $time,
            'pin' => $this->in('pin', true) ? 1 : 0,
            'pin_silent' => $this->in('pin_silent', true) ? 1 : 0,
            'delete_old' => $this->in('delete_old') ? 1 : 0,
            'updated_at' => now(),
        ];
        // Bot quảng cáo riêng: kiểm tra token
        $token = trim((string)$this->in('bot_token', ''));
        if ($data['bot_mode'] === 'custom') {
            if ($token !== '') {
                if (!preg_match('/^\d+:[\w-]{30,}$/', $token)) fail('Token bot quảng cáo không đúng định dạng');
                try { $me = (new Telegram($token))->call('getMe'); } catch (TelegramException $e) { fail('Token bot quảng cáo không hợp lệ: ' . $e->getMessage()); }
                $data['bot_token'] = $token;
                $data['bot_username'] = $me['username'];
            } elseif (!$old || !$old['bot_token']) {
                fail('Nhập token của bot quảng cáo (lấy từ @BotFather)');
            }
        }
        if ($id) {
            $botChanged = ($old['bot_mode'] !== $data['bot_mode']) || isset($data['bot_token']);
            DB::update('tg_ad_campaigns', $data, 'id = ?', [$id]);
            if ($botChanged) Ads::stop($old); // đổi bot → đăng lại bằng bot mới
            // Bỏ nhóm khỏi chiến dịch → gỡ ghim bài ở nhóm đó
            foreach (DB::all('SELECT * FROM tg_ad_posts WHERE campaign_id = ? AND active = 1', [$id]) as $p) {
                if (!in_array((int)$p['group_id'], $groups, true)) DB::run('UPDATE tg_ad_posts SET active = 0 WHERE id = ?', [$p['id']]);
            }
        } else {
            $data['created_at'] = now();
            $id = DB::insert('tg_ad_campaigns', $data);
        }
        Repo::log(null, $u['username'], 'system', null, 'Lưu chiến dịch quảng cáo: ' . $name);
        return ['campaign' => Ads::campaignRow(DB::get('SELECT * FROM tg_ad_campaigns WHERE id = ?', [$id]))];
    }

    private function adCampaignDelete(): array
    {
        $u = Auth::requireAdmin();
        $c = $this->loadCampaign();
        Ads::stop($c);
        foreach (DB::all('SELECT image FROM tg_ads WHERE campaign_id = ?', [$c['id']]) as $a) if ($a['image']) @unlink(Ads::IMG_DIR . '/' . basename($a['image']));
        DB::run('DELETE FROM tg_ads WHERE campaign_id = ?', [$c['id']]);
        DB::run('DELETE FROM tg_ad_posts WHERE campaign_id = ?', [$c['id']]);
        DB::run('DELETE FROM tg_ad_campaigns WHERE id = ?', [$c['id']]);
        Repo::log(null, $u['username'], 'system', null, 'Xoá chiến dịch quảng cáo: ' . $c['name']);
        return [];
    }

    /** Đăng bài mới & ghim ngay (không đợi tới giờ) */
    private function adCampaignRun(): array
    {
        $u = Auth::requireAdmin();
        $c = $this->loadCampaign();
        $ads = DB::all('SELECT * FROM tg_ads WHERE campaign_id = ? AND enabled = 1 AND image IS NOT NULL ORDER BY sort, id', [$c['id']]);
        if (!$ads) fail('Chiến dịch chưa có bài quảng cáo nào đang bật');
        $gids = json_decode((string)$c['group_ids'], true) ?: [];
        if (!$gids) fail('Chưa chọn nhóm để đăng');
        DB::run('UPDATE tg_ad_campaigns SET enabled = 1 WHERE id = ?', [$c['id']]);
        $c['enabled'] = 1;
        $done = [];
        $errors = [];
        foreach ($gids as $gid) {
            $g = DB::get('SELECT * FROM tg_groups WHERE id = ? AND active = 1', [$gid]);
            if (!$g) continue;
            $old = DB::get('SELECT * FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ? AND active = 1', [$c['id'], $gid]);
            try {
                Ads::post($c, $g, $old, $ads, $old ? (int)$old['ad_id'] : null);
                $done[] = $g['title'];
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'not enough rights') !== false || stripos($msg, 'chat not found') !== false || stripos($msg, 'not a member') !== false) {
                    $msg = 'Bot ' . ($c['bot_mode'] === 'custom' ? '@' . $c['bot_username'] : 'của nhóm') . ' chưa ở trong nhóm hoặc thiếu quyền gửi ảnh / ghim tin';
                }
                $errors[] = $g['title'] . ': ' . $msg;
            }
        }
        Repo::log(null, $u['username'], 'system', null, 'Phát quảng cáo ngay: ' . $c['name']);
        return ['done' => $done, 'errors' => $errors];
    }

    private function adCampaignStop(): array
    {
        $u = Auth::requireAdmin();
        $c = $this->loadCampaign();
        Ads::stop($c);
        DB::run('UPDATE tg_ad_campaigns SET enabled = 0 WHERE id = ?', [$c['id']]);
        Repo::log(null, $u['username'], 'system', null, 'Dừng quảng cáo: ' . $c['name']);
        return [];
    }

    private function adSave(): array
    {
        Auth::requireAdmin();
        $id = $this->int('id');
        $old = $id ? (DB::get('SELECT * FROM tg_ads WHERE id = ?', [$id]) ?: fail('Không tìm thấy bài', 404)) : null;
        $c = DB::get('SELECT * FROM tg_ad_campaigns WHERE id = ?', [$old ? $old['campaign_id'] : $this->int('campaign_id')]) ?: fail('Không tìm thấy chiến dịch', 404);
        $caption = trim((string)$this->in('caption', ''));
        if (mb_strlen(html_entity_decode(strip_tags($caption), ENT_QUOTES | ENT_HTML5, 'UTF-8')) > Ads::CAPTION_MAX) fail('Nội dung tối đa ' . Ads::CAPTION_MAX . ' ký tự (giới hạn chú thích ảnh của Telegram)');
        $data = [
            'title' => trim(mb_substr((string)$this->in('title', ''), 0, 120)),
            'caption' => $caption,
            'buttons' => json_encode(Ads::cleanButtons($this->in('buttons', [])), JSON_UNESCAPED_UNICODE),
            'enabled' => $this->in('enabled', true) ? 1 : 0,
            'updated_at' => now(),
        ];
        $img = (string)$this->in('image', '');
        if ($img !== '') {
            $data['image'] = Ads::saveImage($img, $old['image'] ?? null);
            $data['file_ids'] = null; // ảnh mới → tải lại lên Telegram
        } elseif (!$old || !$old['image']) {
            fail('Chọn ảnh cho bài quảng cáo');
        }
        if ($old) DB::update('tg_ads', $data, 'id = ?', [$id]);
        else {
            $data += ['campaign_id' => $c['id'], 'sort' => (int)DB::val('SELECT COALESCE(MAX(sort), 0) + 1 FROM tg_ads WHERE campaign_id = ?', [$c['id']]), 'created_at' => now()];
            $id = DB::insert('tg_ads', $data);
        }
        return ['ad' => Ads::adRow(DB::get('SELECT * FROM tg_ads WHERE id = ?', [$id]))];
    }

    private function adDelete(): array
    {
        Auth::requireAdmin();
        $a = DB::get('SELECT * FROM tg_ads WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy bài', 404);
        if ($a['image']) @unlink(Ads::IMG_DIR . '/' . basename($a['image']));
        DB::run('DELETE FROM tg_ads WHERE id = ?', [$a['id']]);
        return [];
    }

    /** Đổi thứ tự bài: dir = -1 (lên) / 1 (xuống) */
    private function adMove(): array
    {
        Auth::requireAdmin();
        $a = DB::get('SELECT * FROM tg_ads WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy bài', 404);
        $list = DB::all('SELECT id FROM tg_ads WHERE campaign_id = ? ORDER BY sort, id', [$a['campaign_id']]);
        $ids = array_map(fn($r) => (int)$r['id'], $list);
        $i = array_search((int)$a['id'], $ids, true);
        $j = $i + ($this->int('dir') < 0 ? -1 : 1);
        if ($j >= 0 && $j < count($ids)) { [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]]; }
        foreach ($ids as $k => $aid) DB::run('UPDATE tg_ads SET sort = ? WHERE id = ?', [$k + 1, $aid]);
        return [];
    }

    // ---- Hệ thống ----
    private function system(): array
    {
        Auth::requireAdmin();
        return [
            'settings' => Settings::publicAll(),
            'access' => [
                'enabled' => Access::enabled(),
                'perms' => Access::perms(),
                'catalog' => array_map(fn($p) => ['default' => $p[0], 'name' => $p[1], 'desc' => $p[2]], Access::PERMS),
                'managers' => array_map(fn($x) => ['id' => (int)$x['id'], 'name' => $x['display_name'] ?: $x['username'], 'groups' => count(Access::groupIds($x) ?? [])],
                    array_values(array_filter(DB::all("SELECT * FROM app_users WHERE role <> 'admin' AND disabled = 0 AND tg_user_id IS NOT NULL"), fn($x) => (bool)Access::groupIds($x)))),
            ],
            'version' => Updater::current(),
            'update_notice' => [
                'enabled' => UpdateNotifier::enabled(),
                'hours' => UpdateNotifier::hours(),
                'last_check' => (int)Settings::get('update_last_check') ?: null,
                'last_error' => Settings::get('update_last_error') ?: null,
                'admins' => array_map(fn($x) => ['name' => $x['display_name'] ?: $x['username'], 'linked' => (bool)$x['tg_user_id']],
                    DB::all("SELECT * FROM app_users WHERE role = 'admin' AND disabled = 0 ORDER BY id")),
            ],
            'detected_url' => (function () {
                $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
                $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
                return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $dir;
            })(),
            'cron_url' => site_url() . '/cron.php?key=' . Settings::get('cron_key'),
            'cron_cmd' => cron_command(),
            'cron_last' => (int)Settings::get('cron_last_run') ?: null,
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
        if (array_key_exists('group_admin_access', $in)) Settings::set('group_admin_access', $in['group_admin_access'] ? '1' : '0');
        if (isset($in['group_admin_perms']) && is_array($in['group_admin_perms'])) {
            $perms = [];
            foreach (Access::PERMS as $k => $_) $perms[$k] = !empty($in['group_admin_perms'][$k]);
            Settings::set('group_admin_perms', json_encode($perms));
        }
        if (array_key_exists('tg_login', $in)) Settings::set('tg_login', $in['tg_login'] ? '1' : '0');
        if (array_key_exists('update_notify', $in)) Settings::set('update_notify', $in['update_notify'] ? '1' : '0');
        if (array_key_exists('update_check_hours', $in)) Settings::set('update_check_hours', (string)max(1, min(168, (int)$in['update_check_hours'])));
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

    /** Gửi thử thông báo cập nhật cho admin qua Telegram */
    private function testUpdateNotice(): array
    {
        Auth::requireAdmin();
        if (!UpdateNotifier::recipients()) fail('Chưa có admin web nào liên kết Telegram. Vào Tài khoản → Liên kết Telegram trước.');
        try {
            $c = Updater::check();
        } catch (HttpError $e) {
            fail('Không kiểm tra được GitHub: ' . $e->getMessage());
        }
        return ['results' => UpdateNotifier::notify($c, true), 'has_update' => $c['has_update']];
    }

    private function migrate(): array
    {
        Auth::requireAdmin();
        DB::migrate();
        return ['schema' => DB::SCHEMA_VERSION];
    }

    // ================= Ví & nạp tiền =================
    private function txRow(array $t): array
    {
        return [
            'id' => (int)$t['id'],
            'amount' => (int)$t['amount'],
            'balance_after' => (int)$t['balance_after'],
            'type' => $t['type'],
            'type_label' => Wallet::TYPES[$t['type']] ?? $t['type'],
            'note' => $t['note'],
            'ref' => $t['ref'],
            'created_at' => (int)$t['created_at'],
        ];
    }

    private function wallet(): array
    {
        $u = Auth::requireUser();
        $c = Wallet::config();
        $before = $this->int('before');
        $rows = DB::all('SELECT * FROM wallet_tx WHERE user_id = ?' . ($before ? ' AND id < ' . $before : '') . ' ORDER BY id DESC LIMIT 30', [$u['id']]);
        $amount = max(0, $this->int('amount'));
        return [
            'balance' => Wallet::balance((int)$u['id']),
            'enabled' => $c['enabled'] && $c['account_no'] !== '',
            'auto' => $c['enabled'] && $c['has_api'],
            'bank' => $c['bank'],
            'bank_name' => Wallet::BANKS[$c['bank']] ?? $c['bank'],
            'account_no' => $c['account_no'],
            'account_name' => $c['account_name'],
            'code' => Wallet::code($u),
            'min' => $c['min'],
            'qr' => Wallet::qrUrl($u, $amount),
            'last_sync' => $c['last_sync'],
            'history' => array_map([$this, 'txRow'], $rows),
            'has_more' => count($rows) === 30,
            'stats' => [
                'deposit' => (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND type = 'deposit'", [$u['id']]),
                'spent' => -(int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND type = 'amnesty'", [$u['id']]),
            ],
        ];
    }

    /** "Tôi đã chuyển khoản": đồng bộ ngay (giới hạn 1 lần / 10 giây / người) */
    private function walletCheck(): array
    {
        $u = Auth::requireUser();
        $before = Wallet::balance((int)$u['id']);
        $key = 'pay_check_' . $u['id'];
        $last = (int)($_SESSION[$key] ?? 0);
        if (now() - $last >= 10) {
            $_SESSION[$key] = now();
            if (now() - (int)Wallet::config()['last_sync'] >= 5) Wallet::sync(true);
        }
        $after = Wallet::balance((int)$u['id']);
        return ['balance' => $after, 'added' => $after - $before];
    }

    // ================= Ân xá =================
    private function amnestyMember(array $m, array $cfg, ?array $group = null): array
    {
        $row = $this->memberRow($m);
        $row['group_title'] = $group['title'] ?? $m['title'] ?? null;
        $row['punishments'] = [];
        foreach (Amnesty::punishments($m, $cfg) as $k => $desc) {
            $row['punishments'][] = ['kind' => $k, 'label' => Amnesty::KINDS[$k][1], 'icon' => Amnesty::KINDS[$k][2], 'desc' => $desc, 'price' => $cfg['prices'][$k]];
        }
        return $row;
    }

    private function amnesty(): array
    {
        $u = Auth::requireUser();
        Repo::sweepMutes();
        $cfg = Amnesty::config();
        $mine = [];
        if ($u['tg_user_id']) {
            foreach (DB::all('SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND ' . Amnesty::punishedSql('m'), [$u['tg_user_id']]) as $m) {
                $r = $this->amnestyMember($m, $cfg);
                if ($r['punishments']) $mine[] = $r;
            }
        }
        $groups = [];
        if ($cfg['allow_others'] || Access::isWebAdmin($u)) {
            $ids = Amnesty::groupIds($u);
            $groups = array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'],
                'punished' => (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE is_bot = 0 AND group_id = ? AND ' . Amnesty::punishedSql(), [$g['id']])],
                DB::all('SELECT id, title FROM tg_groups WHERE active = 1 AND ' . Access::sqlIn($ids, 'id') . ' ORDER BY title'));
        }
        $recent = DB::all("SELECT t.*, u.display_name, u.username FROM wallet_tx t JOIN app_users u ON u.id = t.user_id WHERE t.type = 'amnesty' ORDER BY t.id DESC LIMIT 10");
        return [
            'enabled' => $cfg['enabled'],
            'prices' => $cfg['prices'],
            'kinds' => array_map(fn($k) => ['label' => $k[1], 'icon' => $k[2]], Amnesty::KINDS),
            'allow_others' => $cfg['allow_others'] || Access::isWebAdmin($u),
            'note' => $cfg['note'],
            'balance' => Wallet::balance((int)$u['id']),
            'linked' => (bool)$u['tg_user_id'],
            'mine' => $mine,
            'groups' => $groups,
            'all_groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT id, title FROM tg_groups WHERE active = 1 ORDER BY title')),
            'recent' => array_map(fn($t) => ['payer' => $t['display_name'] ?: $t['username'], 'note' => preg_replace('/^Ân xá: /u', '', (string)$t['note']), 'amount' => -(int)$t['amount'], 'created_at' => (int)$t['created_at']], $recent),
        ];
    }

    private function amnestySearch(): array
    {
        $u = Auth::requireUser();
        $cfg = Amnesty::config();
        $gid = $this->int('group_id');
        if (!$cfg['allow_others'] && !Access::isWebAdmin($u)) fail('Admin chưa cho phép ân xá người khác', 403);
        $ids = Amnesty::groupIds($u);
        if ($ids !== null && !in_array($gid, $ids, true)) fail('Bạn không thuộc nhóm này', 403);
        $g = Repo::group($gid) ?: fail('Không tìm thấy nhóm', 404);
        Repo::sweepMutes();
        $q = trim((string)$this->in('q', ''));
        $where = 'group_id = ? AND is_bot = 0 AND ' . Amnesty::punishedSql();
        $p = [$gid];
        if ($q !== '') {
            $where .= ' AND (LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ? OR LOWER(username) LIKE ? OR CAST(tg_user_id AS CHAR) LIKE ?)';
            $like = '%' . mb_strtolower(ltrim($q, '@')) . '%';
            array_push($p, $like, $like, $like, $like);
        }
        $rows = DB::all("SELECT * FROM tg_members WHERE $where ORDER BY CASE status WHEN 'banned' THEN 0 WHEN 'muted' THEN 1 ELSE 2 END, last_active DESC LIMIT 50", $p);
        return ['group' => ['id' => (int)$g['id'], 'title' => $g['title']], 'rows' => array_values(array_filter(array_map(fn($m) => $this->amnestyMember($m, $cfg, $g), $rows), fn($r) => $r['punishments']))];
    }

    /** Ân xá theo ID Telegram: hỏi Telegram trạng thái thật (kể cả người bị cấm trước khi có web) */
    private function amnestyLookup(): array
    {
        $u = Auth::requireUser();
        $cfg = Amnesty::config();
        if (!$cfg['enabled']) fail('Tính năng ân xá đang tắt');
        $g = Repo::group($this->int('group_id'));
        if (!$g || !$g['active']) fail('Chọn nhóm', 404);
        $key = 'amnesty_lookup_' . $u['id'];
        $hits = array_filter((array)($_SESSION[$key] ?? []), fn($t) => $t > now() - 60);
        if (count($hits) >= 15) fail('Tra cứu quá nhanh, thử lại sau 1 phút', 429);
        $hits[] = now();
        $_SESSION[$key] = array_values($hits);
        $m = Amnesty::lookup($u, $g, (string)$this->in('who', ''));
        $row = $this->amnestyMember($m, $cfg, $g);
        if (!$row['punishments']) fail(Repo::name($m) . ' (' . $m['tg_user_id'] . ') hiện không bị cấm, cấm chat hay có cảnh cáo trong nhóm này.', 404);
        return $row;
    }

    private function amnestyDo(): array
    {
        $u = Auth::requireUser();
        $r = Amnesty::perform($u, $this->int('member_id'), (string)$this->in('kind'), (bool)$this->in('by_id'));
        return ['balance' => $r['balance'], 'price' => $r['price'], 'label' => $r['label'], 'invite_link' => $r['invite_link'], 'member' => $this->memberRow($r['member'])];
    }

    // ================= Quản trị thanh toán (admin web) =================
    private function bankRow(array $t): array
    {
        return [
            'id' => (int)$t['id'], 'txid' => $t['txid'], 'amount' => (int)$t['amount'], 'description' => $t['description'],
            'tx_date' => $t['tx_date'], 'status' => $t['status'], 'created_at' => (int)$t['created_at'],
            'user' => $t['user_id'] ? ['id' => (int)$t['user_id'], 'name' => $t['display_name'] ?: $t['username']] : null,
        ];
    }

    private function payments(): array
    {
        Auth::requireAdmin();
        $status = (string)$this->in('status', '');
        $where = in_array($status, ['credited', 'unmatched', 'below_min', 'ignored'], true) ? 'WHERE b.status = ?' : '';
        $rows = DB::all("SELECT b.*, u.display_name, u.username FROM bank_tx b LEFT JOIN app_users u ON u.id = b.user_id $where ORDER BY b.id DESC LIMIT 100", $where ? [$status] : []);
        $sum = fn($type) => (int)DB::val('SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE type = ?', [$type]);
        return [
            'wallet' => Wallet::config(),
            'amnesty' => Amnesty::config() + ['default_message' => Amnesty::DEFAULT_MESSAGE],
            'banks' => Wallet::BANKS,
            'bank_tx' => array_map([$this, 'bankRow'], $rows),
            'stats' => [
                'deposit' => $sum('deposit'),
                'amnesty' => -$sum('amnesty'),
                'refund' => $sum('refund'),
                'balances' => (int)DB::val('SELECT COALESCE(SUM(balance),0) FROM app_users'),
                'unmatched' => (int)DB::val("SELECT COUNT(*) FROM bank_tx WHERE status IN ('unmatched','below_min')"),
                'amnesty_count' => (int)DB::val("SELECT COUNT(*) FROM wallet_tx WHERE type = 'amnesty'"),
            ],
        ];
    }

    private function paymentsSave(): array
    {
        $me = Auth::requireAdmin();
        if (is_array($this->in('wallet'))) Wallet::saveConfig($this->in('wallet'));
        if (is_array($this->in('amnesty'))) Amnesty::saveConfig($this->in('amnesty'));
        Repo::log(null, $me['username'], 'payments_settings', null, 'Cập nhật cài đặt nạp tiền / ân xá');
        return ['wallet' => Wallet::config(), 'amnesty' => Amnesty::config()];
    }

    /** Gọi thử API ngân hàng: chỉ hiện số giao dịch đọc được và vài dòng mẫu, không cộng tiền */
    private function paymentsTest(): array
    {
        Auth::requireAdmin();
        try {
            $list = Wallet::fetch();
        } catch (HttpError $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        $in = array_values(array_filter($list, fn($t) => $t['in']));
        return [
            'ok' => true,
            'total' => count($list),
            'incoming' => count($in),
            'sample' => array_map(fn($t) => $t + ['match' => ($m = Wallet::matchUser($t['description'])) ? ($m['display_name'] ?: $m['username']) : null], array_slice($in, 0, 5)),
        ];
    }

    private function paymentsSync(): array
    {
        Auth::requireAdmin();
        $r = Wallet::sync(true);
        $c = Wallet::config();
        return $r + ['error' => $c['last_error']];
    }

    private function findUser(): array
    {
        $id = $this->int('user_id');
        $name = trim((string)$this->in('username', ''));
        $u = $id ? DB::get('SELECT * FROM app_users WHERE id = ?', [$id]) : ($name !== '' ? DB::get('SELECT * FROM app_users WHERE LOWER(username) = LOWER(?)', [ltrim($name, '@')]) : null);
        return $u ?: fail('Không tìm thấy tài khoản', 404);
    }

    private function paymentsAssign(): array
    {
        $me = Auth::requireAdmin();
        $u = $this->findUser();
        Wallet::assign($this->int('id'), (int)$u['id'], $me['username']);
        Repo::log(null, $me['username'], 'wallet_assign', null, 'Gán giao dịch #' . $this->int('id') . ' cho ' . $u['username']);
        return ['ok' => true];
    }

    private function paymentsIgnore(): array
    {
        Auth::requireAdmin();
        DB::run("UPDATE bank_tx SET status = 'ignored' WHERE id = ? AND status <> 'credited'", [$this->int('id')]);
        return ['ok' => true];
    }

    /** Admin cộng / trừ số dư */
    private function userBalance(): array
    {
        $me = Auth::requireAdmin();
        $u = $this->findUser();
        $amount = $this->int('amount');
        $note = trim((string)$this->in('note', '')) ?: 'Admin điều chỉnh';
        if ($amount === 0) fail('Nhập số tiền (số âm để trừ)');
        $note = "$note (bởi {$me['username']})";
        $bal = $amount > 0 ? Wallet::credit((int)$u['id'], $amount, 'admin', $note) : Wallet::debit((int)$u['id'], -$amount, 'admin', $note);
        Repo::log(null, $me['username'], 'wallet_adjust', null, ($amount > 0 ? '+' : '') . Wallet::money($amount) . " cho {$u['username']} · $note");
        return ['balance' => $bal];
    }

    private function userTx(): array
    {
        Auth::requireAdmin();
        $u = $this->findUser();
        return ['balance' => (int)$u['balance'], 'rows' => array_map([$this, 'txRow'], DB::all('SELECT * FROM wallet_tx WHERE user_id = ? ORDER BY id DESC LIMIT 100', [$u['id']]))];
    }
}

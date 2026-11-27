<?php
defined('APP') or exit;

class Api
{
    /** Loại update Telegram bot nhận (thêm loại mới → tăng WEBHOOK_V để tự cập nhật bot đã cài) */
    const UPDATES = ['message', 'edited_message', 'chat_member', 'my_chat_member', 'callback_query', 'message_reaction', 'chat_join_request'];
    const WEBHOOK_V = '3';

    /** Đăng ký lại loại update cho các bot đã cài (1 lần sau khi cập nhật phiên bản) */
    public static function refreshWebhooks(): void
    {
        if (Settings::get('webhook_v') === self::WEBHOOK_V) return;
        Settings::set('webhook_v', self::WEBHOOK_V);
        $base = site_url();
        if (!str_starts_with($base, 'https://')) return;
        foreach (DB::all('SELECT * FROM tg_bots WHERE enabled = 1') as $b) {
            (new Telegram($b['token']))->tryCall('setWebhook', [
                'url' => $base . '/webhook.php?bot=' . $b['id'], 'secret_token' => self::webhookSecret((int)$b['id']),
                'allowed_updates' => self::UPDATES, 'max_connections' => 40,
            ]);
        }
    }

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
            'me/logs' => 'meLogs',
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
            'chat/media' => 'chatMedia',
            'members' => 'members',
            'member' => 'member',
            'member/action' => 'memberAction',
            'member/note' => 'memberNote',
            'member/tag' => 'memberTag',
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
            'ads/log' => 'adLog',
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
            'payments/adjust' => 'paymentsAdjust',
            'users/balance' => 'userBalance',
            'users/tx' => 'userTx',
            'checkin' => 'checkin',
            'checkin/do' => 'checkinDo',
            'checkin/save' => 'checkinSave',
            'checkin/config' => 'checkinConfig',
            'tags' => 'tags',
            'tags/save' => 'tagsSave',
            'tags/apply' => 'tagsApply',
            'exchange' => 'exchange',
            'exchange/do' => 'exchangeDo',
            'exchange/buy' => 'exchangeBuy',
            'exchange/save' => 'exchangeSave',
            'antiraid' => 'antiRaid',
            'antiraid/save' => 'antiRaidSave',
            'antiraid/lock' => 'antiRaidLock',
            'antiraid/decide' => 'antiRaidDecide',
            'antiraid/decide-all' => 'antiRaidDecideAll',
            'features/reset' => 'featureReset',
            'features/group' => 'groupFeatures',
            'features/username' => 'usernameFeature',
            'settings/export' => 'settingsExport',
            'settings/import' => 'settingsImport',
            'dm' => 'dm',
            'dm/count' => 'dmCount',
            'dm/send' => 'dmSend',
            'dm/test' => 'dmTest',
            'dm/status' => 'dmStatus',
            'dm/stop' => 'dmStop',
            'dm/invite' => 'dmInvite',
            'unban' => 'unban',
            'unban/submit' => 'unbanSubmit',
            'unban/list' => 'unbanList',
            'unban/decide' => 'unbanDecide',
            'unbanreq' => 'unbanReq',
            'unbanreq/save' => 'unbanReqSave',
            'plan' => 'plan',
            'plan/buy' => 'planBuy',
            'admin' => 'adminPanel',
            'admin/plan/save' => 'adminPlanSave',
            'admin/plan/delete' => 'adminPlanDelete',
            'admin/grant' => 'adminGrant',
            'admin/lock' => 'adminLock',
            'admin/config' => 'adminConfig',
            'nameguard' => 'nameGuard',
            'nameguard/save' => 'nameGuardSave',
            'nameguard/test' => 'nameGuardTest',
            'nameguard/scan' => 'nameGuardScan',
            'nameguard/punish' => 'nameGuardPunish',
            'watermark' => 'watermark',
            'watermark/save' => 'watermarkSave',
            'watermark/preview' => 'watermarkPreview',
            'captcha' => 'captcha',
            'captcha/save' => 'captchaSave',
            'gift' => 'gift',
            'gift/redeem' => 'giftRedeem',
            'gift/save' => 'giftSave',
            'gift/delete' => 'giftDelete',
            'vip' => 'vip',
            'vip/buy' => 'vipBuy',
            'vip/save' => 'vipSave',
            'vip/grant' => 'vipGrant',
            'tagshop' => 'tagShop',
            'tagshop/buy' => 'tagShopBuy',
            'gates' => 'gates',
            'gates/save' => 'gateSave',
            'gates/delete' => 'gateDelete',
            'gates/check' => 'gateCheck',
            'keyboard' => 'keyboard',
            'keyboard/save' => 'keyboardSave',
            'learn' => 'learn',
            'learn/save' => 'learnSave',
            'learn/question/save' => 'learnQuestionSave',
            'learn/question/delete' => 'learnQuestionDelete',
            'learn/question/move' => 'learnQuestionMove',
            'learn/reset' => 'learnReset',
            'learn/pass' => 'learnPass',
        ];
        $writeRoutes = ['install', 'auth/login', 'auth/register', 'auth/logout', 'me/update', 'me/link-code', 'me/unlink'];
        if (!isset($map[$route])) fail('Không tìm thấy API', 404);
        $fn = $map[$route];
        $isWrite = in_array($route, $writeRoutes, true) || str_contains($route, '/') && !in_array($route, ['system/check-update', 'chat/poll', 'ads/image', 'me/logs', 'amnesty/search', 'users/tx', 'chat/media', 'features/group', 'features/username', 'checkin/config', 'unban/list', 'dm/status'], true);
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
            'pay' => $inst ? $this->payFlags(Auth::user()) : null,
            'tenant' => $inst ? (function () {
                $u = Auth::user();
                $on = Tenant::config()['enabled'];
                return ['enabled' => $on, 'me' => $u && ($on || !empty($u['tenant'])) ? Tenant::summary($u) : null];
            })() : null,
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

    /** Cờ hiện menu tiền: bật ở bất kỳ không gian nào người này liên quan (web chính / khách thuê có nhóm họ đang ở) */
    private function payFlags(?array $u): array
    {
        $sps = $u ? array_column(Space::forUser($u), 'id') : [0];
        $any = fn(callable $f) => (bool)array_filter($sps, $f);
        return [
            'wallet' => $any(fn($sp) => ($w = Wallet::config($sp))['enabled'] && $w['account_no'] !== ''),
            'amnesty' => $any(fn($sp) => Amnesty::config($sp)['enabled']),
            'checkin' => GroupFeature::anyEnabled('checkin'),
            'tagshop' => MemberTag::config()['shop'],
            'exchange' => $any(fn($sp) => Exchange::config($sp)['enabled']),
            'vip' => $any(fn($sp) => Vip::config($sp)['enabled']),
            'spaces' => count($sps) > 1,
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
                'allowed_updates' => self::UPDATES,
                'max_connections' => 40,
            ]);
            $this->registerMenus($api);
            DB::run("UPDATE tg_bots SET status = 'running', last_error = NULL WHERE id = ?", [$bot['id']]);
        } catch (TelegramException $e) {
            DB::run("UPDATE tg_bots SET status = 'error', last_error = ? WHERE id = ?", [$e->getMessage(), $bot['id']]);
            throw new HttpError('Không đặt được webhook: ' . $e->getMessage());
        }
    }

    /** Admin web: mọi bot; khách thuê: bot của mình */
    private function botUser(): array
    {
        $u = Auth::requireUser();
        if (!Access::isWebAdmin($u) && !Access::isTenant($u)) fail('Bạn chưa thuê gói. Vào mục Gói thuê để bắt đầu.', 403);
        return $u;
    }

    private function loadBot(array $u): array
    {
        $bot = DB::get('SELECT * FROM tg_bots WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy bot', 404);
        if (!Access::isWebAdmin($u) && (int)$bot['owner_id'] !== (int)$u['id']) fail('Không tìm thấy bot', 404);
        return $bot;
    }

    private function bots(): array
    {
        $u = $this->botUser();
        $web = Access::isWebAdmin($u);
        $rows = DB::all('SELECT b.*, u.username AS owner_name, (SELECT COUNT(*) FROM tg_groups g WHERE g.bot_id = b.id AND g.active = 1) AS groups FROM tg_bots b LEFT JOIN app_users u ON u.id = b.owner_id'
            . ($web ? '' : ' WHERE b.owner_id = ' . (int)$u['id']) . ' ORDER BY b.id');
        return array_map(fn($b) => [
            'owner' => $b['owner_id'] ? ['id' => (int)$b['owner_id'], 'username' => $b['owner_name']] : null,
            'blocked' => Tenant::blockReason((int)$b['id'], null),
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
        $u = $this->botUser();
        $web = Access::isWebAdmin($u);
        if (!$web) {
            $p = Tenant::activePlan($u) ?: fail('Gói thuê đã hết hạn. Gia hạn để thêm bot.', 402);
            if (Tenant::usage((int)$u['id'])['bots'] >= $p['max_bots']) fail("Gói {$p['name']} cho tối đa {$p['max_bots']} bot. Nâng cấp gói để thêm.", 402);
        }
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
            'enabled' => 1, 'status' => 'stopped', 'created_at' => now(), 'owner_id' => $web ? null : (int)$u['id'],
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
        $bot = $this->loadBot($this->botUser());
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
        $bot = $this->loadBot($this->botUser());
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
        $bot = $this->loadBot($this->botUser());
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
            'owner' => ($oid = Tenant::groupOwner($g)) ? ['id' => $oid, 'username' => (Tenant::user($oid)['username'] ?? '?')] : null,
            'blocked' => $g['bot_id'] && $g['active'] ? Tenant::blockReason((int)$g['bot_id'], (int)$g['id']) : null,
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
            'access' => array_combine(array_keys(Access::PERMS), array_map(fn($k) => Access::can($u, $k, (int)$id), array_keys(Access::PERMS))), // quyền theo đúng nhóm này (gói thuê / quyền admin nhóm)
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
        DB::run('UPDATE tg_members SET points = 0, points_spent = 0 WHERE group_id = ?', [$g['id']]);
        Repo::log((int)$g['id'], $u['username'], 'reset_points', null, 'Đặt lại điểm toàn nhóm');
        return [];
    }

    // ---- Chat nhóm ----
    private const CHAT_SQL = 'SELECT c.*, mb.id AS member_id, mb.status, mb.is_admin, mb.warns, mb.points, mb.tag AS m_tag, mb.first_name AS m_first, mb.last_name AS m_last
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
            'has_file' => !empty($r['file_id']),
            'has_thumb' => !empty($r['thumb_id']) || in_array($r['kind'], ['photo'], true),
            'file_name' => $r['file_name'] ?? null,
            'file_size' => isset($r['file_size']) ? (int)$r['file_size'] : null,
            'mime' => $r['mime'] ?? null,
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
                'tag' => $r['m_tag'] ?? null,
            ] : null,
        ];
    }

    /** Ảnh / video / tệp của 1 tin trong tab Chat (tải qua bot, không lộ token). Hỗ trợ Range để phát video. */
    private function chatMedia(): void
    {
        $g = $this->loadGroup();
        $this->gate('chat', $g);
        $row = DB::get('SELECT * FROM tg_messages WHERE group_id = ? AND message_id = ?', [$g['id'], (string)$this->in('message_id', '')]) ?: fail('Không tìm thấy tin nhắn', 404);
        [$path, $mime] = ChatLog::download($g, $row, (bool)$this->in('thumb'));
        session_write_close();
        $size = filesize($path);
        $start = 0;
        $end = $size - 1;
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=604800');
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');
        if ($this->in('dl')) {
            $name = $row['file_name'] ?: ('tep_' . $row['message_id']);
            header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        }
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $rm)) {
            if ($rm[1] !== '') { $start = (int)$rm[1]; if ($rm[2] !== '') $end = min($end, (int)$rm[2]); }
            elseif ($rm[2] !== '') $start = max(0, $size - (int)$rm[2]);
            if ($start > $end || $start >= $size) { http_response_code(416); header("Content-Range: bytes */$size"); exit; }
            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
        }
        header('Content-Length: ' . ($end - $start + 1));
        $fh = fopen($path, 'rb');
        fseek($fh, $start);
        $left = $end - $start + 1;
        while ($left > 0 && !feof($fh)) { $buf = fread($fh, min(65536, $left)); echo $buf; $left -= strlen($buf); flush(); }
        fclose($fh);
        exit;
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
            'tag' => $m['tag'] ?? null,
            'tag_source' => $m['tag_source'] ?? null,
            'tag_until' => !empty($m['tag_until']) ? (int)$m['tag_until'] : null,
            'points_open' => Exchange::open($m),
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

    /** Nhật ký của chính mình: chỉ các dòng mà tài khoản Telegram của mình là đối tượng */
    private function meLogs(): array
    {
        $u = Auth::requireUser();
        if (!$u['tg_user_id']) return ['linked' => false, 'total' => 0, 'page' => 1, 'pages' => 1, 'rows' => []];
        $where = ['l.target_tg_id = ?'];
        $p = [$u['tg_user_id']];
        $a = (string)$this->in('action', '');
        if ($a === 'moderation') $where[] = "l.action IN ('ban','unban','kick','mute','unmute','warn','unwarn','resetwarn','delete','username','learn','learn_done','gate')";
        elseif ($a === 'points') $where[] = "l.action IN ('points','setpoints','reset_points','checkin')";
        elseif ($a === 'members') $where[] = "l.action IN ('join','leave')";
        $page = max(1, $this->int('page', 1));
        $per = 40;
        $w = implode(' AND ', $where);
        $total = (int)DB::val("SELECT COUNT(*) FROM app_logs l WHERE $w", $p);
        $rows = DB::all("SELECT l.*, g.title AS group_title, g.settings AS group_settings FROM app_logs l LEFT JOIN tg_groups g ON g.id = l.group_id WHERE $w ORDER BY l.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        $out = [];
        foreach ($this->formatLogs($rows) as $i => $l) {
            // Ẩn thông tin nội bộ; ẩn tên người xử lý nếu nhóm tắt "hiện người xử lý"
            $s = json_decode((string)($rows[$i]['group_settings'] ?? ''), true) ?: [];
            $showAdmin = !array_key_exists('mod_show_admin', $s) || !empty($s['mod_show_admin']);
            $isMod = in_array($l['action'], ['ban', 'unban', 'kick', 'mute', 'unmute', 'warn', 'unwarn', 'resetwarn', 'points', 'setpoints'], true);
            if ($isMod && !$showAdmin && !in_array($l['actor'], ['Bot', 'Telegram', 'system'], true) && !str_contains((string)$l['actor'], '(ân xá)')) $l['actor'] = 'Quản trị viên';
            if ($l['meta']) $l['meta'] = array_intersect_key($l['meta'], array_flip(['source', 'reason', 'minutes', 'until', 'warns', 'limit', 'amount', 'before', 'after', 'previous_until', 'escalated_from', 'escalate_to', 'link', 'amnesty']));
            if (isset($l['meta']['amnesty'])) $l['meta']['amnesty'] = array_intersect_key($l['meta']['amnesty'], array_flip(['kind', 'price', 'payer', 'self', 'before']));
            $out[] = $l;
        }
        return ['linked' => true, 'total' => $total, 'page' => $page, 'pages' => max(1, (int)ceil($total / $per)), 'rows' => $out];
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
            elseif ($a === 'points') $where[] = "l.action IN ('points','setpoints','reset_points','checkin')";
            elseif ($a === 'members') $where[] = "l.action IN ('join','leave')";
            elseif ($a === 'system') $where[] = "l.action NOT IN ('ban','unban','kick','mute','unmute','warn','unwarn','resetwarn','delete','points','setpoints','join','leave','checkin')";
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
        // Khách thuê / nhóm của khách thuê: lệnh dùng chung của chủ web không áp dụng → không hiện
        $noGlobal = (Access::isTenant($u) && !Access::isWebAdmin($u)) || ($gid && Tenant::groupOwner(Repo::group($gid)));
        $scope = $noGlobal ? Access::sqlIn($ids, 'r.group_id') : '(r.group_id IS NULL OR ' . Access::sqlIn($ids, 'r.group_id') . ')';
        $rows = $gid ? DB::all("$sql WHERE " . ($noGlobal ? 'r.group_id = ?' : '(r.group_id IS NULL OR r.group_id = ?)') . " ORDER BY r.group_id IS NULL, r.priority DESC, r.id", [$gid])
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
        if ($this->in('feature') !== 'require_username') fail('Tính năng không hợp lệ');
        [$u, $g] = $this->featScope();
        $c = UsernameGuard::save((array)$this->in('config', []), $g, $u['username']);
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Yêu cầu đặt username: ' . ($c['enabled'] ? 'bật · ' . UsernameGuard::ACTIONS[$c['action']] : 'tắt'));
        return ['config' => $c];
    }

    /** Yêu cầu đặt Username: cài đặt chung / riêng nhóm (trang riêng) */
    private function usernameFeature(): array
    {
        [$u, $g] = $this->featScope();
        return [
            'features' => ['require_username' => UsernameGuard::config($g) + ['actions' => UsernameGuard::ACTIONS, 'default_text' => UsernameGuard::DEFAULTS['text']]],
            'scope' => $this->featInfo('require_username', $g), 'groups' => $this->featGroups($g), 'can_edit' => true,
        ];
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
            if (Ads::isSchedule($c)) {
                // Lịch theo từng nhóm: lần gửi trước, lần gửi tới
                $row['schedule'] = [];
                foreach ($row['group_ids'] as $gid) {
                    $last = (int)DB::val('SELECT MAX(created_at) FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ?', [$c['id'], $gid]);
                    $row['schedule'][] = ['group_id' => $gid, 'last' => $last ?: null, 'next' => $c['enabled'] ? Ads::nextAt($c, $last) : null];
                }
            }
            $camps[] = $row;
        }
        return [
            'campaigns' => $camps,
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'], 'bot' => $g['bot_username']],
                DB::all('SELECT g.id, g.title, b.username AS bot_username FROM tg_groups g LEFT JOIN tg_bots b ON b.id = g.bot_id WHERE g.active = 1 ORDER BY g.title')),
            'last_tick' => (int)Settings::get('ads_last_tick') ?: null,
            'ads_log' => Settings::get('ads_log') === '1',
            'cron_url' => site_url() . '/cron.php?key=' . Settings::get('cron_key'),
            'cron_path' => ROOT . '/cron.php',
            'cron_cmd' => cron_command(),
            'intervals' => Ads::INTERVALS,
            'every' => Ads::EVERY,
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
        $type = $old ? ($old['type'] ?? 'rotate') : ($this->in('type') === 'schedule' ? 'schedule' : 'rotate');
        $sched = [];
        if ($type === 'schedule') {
            $isHm = fn($x) => (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $x);
            $mode = $this->in('sched_mode') === 'times' ? 'times' : 'interval';
            $every = (int)$this->in('every_min', 60);
            if ($every < 1 || $every > 10080) fail('Chu kỳ gửi: 1 phút – 7 ngày');
            $times = [];
            $rawTimes = $this->in('sched_times', '');
            if (is_array($rawTimes)) $rawTimes = implode(',', $rawTimes);
            foreach (preg_split('/[\s,;]+/', (string)$rawTimes) as $t) {
                if ($t === '') continue;
                if (preg_match('/^(\d{1,2})(?:[:hg.](\d{2}))?$/u', $t, $tm)) $t = sprintf('%02d:%02d', (int)$tm[1], (int)($tm[2] ?? 0));
                if (!$isHm($t)) fail("Giờ gửi \"$t\" không hợp lệ (VD 08:00, 12:30, 20:00)");
                $times[] = $t;
            }
            $times = array_values(array_unique($times));
            sort($times);
            if ($mode === 'times' && !$times) fail('Nhập ít nhất 1 giờ gửi, VD 08:00, 12:00, 20:00');
            if (count($times) > 48) fail('Tối đa 48 mốc giờ mỗi ngày');
            $days = implode('', array_unique(array_filter(str_split(preg_replace('/[^1-7]/', '', (string)$this->in('sched_days', '1234567'))))));
            if ($days === '') fail('Chọn ít nhất 1 ngày trong tuần');
            $wf = trim((string)$this->in('win_from', ''));
            $wt = trim((string)$this->in('win_to', ''));
            if (($wf !== '' && !$isHm($wf)) || ($wt !== '' && !$isHm($wt))) fail('Khung giờ gửi không hợp lệ (HH:MM)');
            $sched = [
                'sched_mode' => $mode, 'every_min' => $every, 'sched_times' => json_encode($times), 'sched_days' => $days,
                'win_from' => $wf, 'win_to' => $wt, 'silent' => $this->in('silent', true) ? 1 : 0,
                'auto_delete_min' => max(0, min(10080, (int)$this->in('auto_delete_min', 0))),
            ];
        }
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
            'type' => $type,
            'updated_at' => now(),
        ] + $sched;
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
        $ads = Ads::adsFor($c);
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
                $lastAd = (int)DB::val('SELECT ad_id FROM tg_ad_posts WHERE campaign_id = ? AND group_id = ? ORDER BY id DESC LIMIT 1', [$c['id'], $gid]) ?: null;
                Ads::post($c, $g, $old, $ads, $lastAd);
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

    /** Bật / tắt ghi nhật ký mỗi lần đăng quảng cáo (tắt thì xoá luôn nhật ký quảng cáo cũ) */
    private function adLog(): array
    {
        Auth::requireAdmin();
        $on = (bool)$this->in('on');
        Settings::set('ads_log', $on ? '1' : '0');
        $removed = $on ? 0 : DB::run("DELETE FROM app_logs WHERE action = 'ad_post'");
        return ['on' => $on, 'removed' => $removed];
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
        $sched = Ads::isSchedule($c);
        $img = (string)$this->in('image', '');
        $removeImg = $sched && $this->in('remove_image') && $img === '';
        $hasImg = $img !== '' || ($old && $old['image'] && !$removeImg);
        $len = mb_strlen(html_entity_decode(strip_tags($caption), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($hasImg && $len > Ads::CAPTION_MAX) fail('Nội dung tối đa ' . Ads::CAPTION_MAX . ' ký tự khi có ảnh (giới hạn chú thích ảnh của Telegram)');
        if (!$hasImg && $len > Ads::TEXT_MAX) fail('Nội dung tối đa ' . Ads::TEXT_MAX . ' ký tự');
        if (!$hasImg && $sched && $caption === '') fail('Bài không có ảnh thì cần nội dung');
        $data = [
            'title' => trim(mb_substr((string)$this->in('title', ''), 0, 120)),
            'caption' => $caption,
            'buttons' => json_encode(Ads::cleanButtons($this->in('buttons', [])), JSON_UNESCAPED_UNICODE),
            'enabled' => $this->in('enabled', true) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($img !== '') {
            $data['image'] = Ads::saveImage($img, $old['image'] ?? null);
            $data['file_ids'] = null; // ảnh mới → tải lại lên Telegram
        } elseif ($removeImg && $old && $old['image']) {
            @unlink(Ads::IMG_DIR . '/' . basename($old['image']));
            $data['image'] = null;
            $data['file_ids'] = null;
        } elseif (!$sched && (!$old || !$old['image'])) {
            fail('Chọn ảnh cho bài quảng cáo (chiến dịch xoay vòng cần ảnh)');
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

    // ---- Không gian tiền (0 = chủ web, N = khách thuê) ----
    /** Trang thành viên: không gian theo tham số sp */
    private function memberSp(): int
    {
        $sp = $this->int('sp');
        if ($sp && !Space::valid($sp)) fail('Không tìm thấy', 404);
        return $sp;
    }

    /** Người này có quản lý không gian $sp không: admin web → 0; khách thuê → không gian của mình */
    private function isSpaceAdmin(array $u, int $sp): bool
    {
        return $sp === 0 ? Access::isWebAdmin($u) : Access::isTenant($u) && (int)$u['id'] === $sp;
    }

    /** Trang quản trị tiền: [user, sp] */
    private function spAdmin(): array
    {
        $u = Auth::requireUser();
        if (Access::isWebAdmin($u)) return [$u, 0];
        if (Access::isTenant($u)) return [$u, (int)$u['id']];
        fail('Chỉ admin web hoặc khách thuê được vào mục này', 403);
    }

    /** Thông tin không gian gửi cho giao diện */
    private function spaceInfo(array $u, int $sp): array
    {
        return ['id' => $sp, 'title' => Space::title($sp), 'spaces' => Space::forUser($u), 'admin' => $this->isSpaceAdmin($u, $sp),
            'allowed' => array_combine(array_keys(Space::FEATURES), array_map(fn($f) => Space::allows($sp, $f), array_keys(Space::FEATURES)))];
    }

    private function wallet(): array
    {
        $u = Auth::requireUser();
        $sp = $this->memberSp();
        $c = Wallet::config($sp);
        $before = $this->int('before');
        $rows = DB::all('SELECT * FROM wallet_tx WHERE user_id = ? AND space_id = ?' . ($before ? ' AND id < ' . $before : '') . ' ORDER BY id DESC LIMIT 30', [$u['id'], $sp]);
        $amount = max(0, $this->int('amount'));
        return [
            'space' => $this->spaceInfo($u, $sp),
            'balance' => Wallet::balance((int)$u['id'], $sp),
            'enabled' => $c['enabled'] && $c['account_no'] !== '',
            'auto' => $c['enabled'] && $c['has_api'],
            'bank' => $c['bank'],
            'bank_name' => Wallet::BANKS[$c['bank']] ?? $c['bank'],
            'account_no' => $c['account_no'],
            'account_name' => $c['account_name'],
            'code' => Wallet::code($u, $sp),
            'min' => $c['min'],
            'qr' => Wallet::qrUrl($u, $amount, $sp),
            'last_sync' => $c['last_sync'],
            'history' => array_map([$this, 'txRow'], $rows),
            'has_more' => count($rows) === 30,
            'stats' => [
                'deposit' => (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND space_id = ? AND type = 'deposit'", [$u['id'], $sp]),
                'spent' => -(int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND space_id = ? AND amount < 0", [$u['id'], $sp]),
            ],
        ];
    }

    /** "Tôi đã chuyển khoản": đồng bộ ngay (giới hạn 1 lần / 10 giây / người) */
    private function walletCheck(): array
    {
        $u = Auth::requireUser();
        $sp = $this->memberSp();
        $before = Wallet::balance((int)$u['id'], $sp);
        $key = 'pay_check_' . $u['id'];
        $last = (int)($_SESSION[$key] ?? 0);
        if (now() - $last >= 10) {
            $_SESSION[$key] = now();
            if (now() - (int)Wallet::config($sp)['last_sync'] >= 5) Wallet::sync(true, $sp);
        }
        $after = Wallet::balance((int)$u['id'], $sp);
        return ['balance' => $after, 'added' => $after - $before];
    }

    // ================= Ân xá =================
    private function amnestyMember(array $m, array $cfg, ?array $group = null): array
    {
        $row = $this->memberRow($m);
        $row['group_title'] = $group['title'] ?? $m['title'] ?? null;
        // Danh sách hình phạt đang có (chỉ để hiển thị) + 1 giá ân xá toàn bộ
        $row['punishments'] = [];
        foreach (Amnesty::punishments($m, $cfg) as $k => $desc) {
            $row['punishments'][] = ['kind' => $k, 'label' => Amnesty::KINDS[$k][1], 'icon' => Amnesty::KINDS[$k][2], 'desc' => $desc];
        }
        $row['price'] = $row['punishments'] ? $cfg['price'] : null;
        return $row;
    }

    private function amnesty(): array
    {
        $u = Auth::requireUser();
        $sp = $this->memberSp();
        Repo::sweepMutes();
        $cfg = Amnesty::config($sp);
        $mine = [];
        if ($u['tg_user_id']) {
            foreach (DB::all('SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND ' . Space::groupsSql($sp, 'g') . ' AND m.tg_user_id = ? AND ' . Amnesty::punishedSql('m'), [$u['tg_user_id']]) as $m) {
                $r = $this->amnestyMember($m, $cfg);
                if ($r['punishments']) $mine[] = $r;
            }
        }
        $groups = [];
        if ($cfg['allow_others'] || Access::isWebAdmin($u)) {
            $ids = Amnesty::groupIds($u);
            $groups = array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'],
                'punished' => (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE is_bot = 0 AND group_id = ? AND ' . Amnesty::punishedSql(), [$g['id']])],
                DB::all('SELECT g.id, g.title FROM tg_groups g WHERE g.active = 1 AND ' . Space::groupsSql($sp, 'g') . ' AND ' . Access::sqlIn($ids, 'g.id') . ' ORDER BY g.title'));
        }
        $recent = DB::all("SELECT t.*, u.display_name, u.username FROM wallet_tx t JOIN app_users u ON u.id = t.user_id WHERE t.type = 'amnesty' AND t.space_id = ? ORDER BY t.id DESC LIMIT 10", [$sp]);
        return [
            'space' => $this->spaceInfo($u, $sp),
            'enabled' => $cfg['enabled'],
            'price' => $cfg['price'],
            'kinds' => array_map(fn($k) => ['label' => $k[1], 'icon' => $k[2]], Amnesty::KINDS),
            'allow_others' => $cfg['allow_others'] || Access::isWebAdmin($u),
            'note' => $cfg['note'],
            'balance' => Wallet::balance((int)$u['id'], $sp),
            'linked' => (bool)$u['tg_user_id'],
            'mine' => $mine,
            'groups' => $groups,
            'all_groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT g.id, g.title FROM tg_groups g WHERE g.active = 1 AND ' . Space::groupsSql($sp, 'g') . ' ORDER BY g.title')),
            'recent' => array_map(fn($t) => ['payer' => $t['display_name'] ?: $t['username'], 'note' => preg_replace('/^Ân xá: /u', '', (string)$t['note']), 'amount' => -(int)$t['amount'], 'created_at' => (int)$t['created_at']], $recent),
        ];
    }

    private function amnestySearch(): array
    {
        $u = Auth::requireUser();
        $gid = $this->int('group_id');
        $cfg = Amnesty::config(Space::ofGroupId($gid));
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
        $g = Repo::group($this->int('group_id'));
        if (!$g || !$g['active']) fail('Chọn nhóm', 404);
        $cfg = Amnesty::config(Space::ofGroup($g));
        if (!$cfg['enabled']) fail('Tính năng ân xá đang tắt');
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
        [$u, $sp] = $this->spAdmin();
        $status = (string)$this->in('status', '');
        $where = 'WHERE b.space_id = ' . $sp . (in_array($status, ['credited', 'unmatched', 'below_min', 'ignored'], true) ? ' AND b.status = ?' : '');
        $rows = DB::all("SELECT b.*, u.display_name, u.username FROM bank_tx b LEFT JOIN app_users u ON u.id = b.user_id $where ORDER BY b.id DESC LIMIT 100", str_contains($where, '?') ? [$status] : []);
        $sum = fn($type) => (int)DB::val('SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE type = ? AND space_id = ?', [$type, $sp]);
        return [
            'space' => $this->spaceInfo($u, $sp),
            'wallet' => Wallet::config($sp),
            'amnesty' => Amnesty::config($sp) + ['default_message' => Amnesty::DEFAULT_MESSAGE],
            'banks' => Wallet::BANKS,
            'bank_tx' => array_map([$this, 'bankRow'], $rows),
            'stats' => [
                'deposit' => $sum('deposit'),
                'amnesty' => -$sum('amnesty'),
                'refund' => $sum('refund'),
                'balances' => (int)($sp ? DB::val('SELECT COALESCE(SUM(balance),0) FROM space_wallets WHERE space_id = ?', [$sp]) : DB::val('SELECT COALESCE(SUM(balance),0) FROM app_users')),
                'unmatched' => (int)DB::val("SELECT COUNT(*) FROM bank_tx WHERE space_id = ? AND status IN ('unmatched','below_min')", [$sp]),
                'amnesty_count' => (int)DB::val("SELECT COUNT(*) FROM wallet_tx WHERE type = 'amnesty' AND space_id = ?", [$sp]),
                'tag' => -$sum('tag'),
            ],
        ];
    }

    private function paymentsSave(): array
    {
        [$me, $sp] = $this->spAdmin();
        if (is_array($this->in('wallet'))) Wallet::saveConfig($this->in('wallet'), $sp);
        if (is_array($this->in('amnesty'))) Amnesty::saveConfig($this->in('amnesty'), $sp);
        Repo::log(null, $me['username'], 'payments_settings', null, 'Cập nhật cài đặt nạp tiền / ân xá' . ($sp ? ' (khách thuê)' : ''));
        return ['wallet' => Wallet::config($sp), 'amnesty' => Amnesty::config($sp)];
    }

    /** Gọi thử API ngân hàng: chỉ hiện số giao dịch đọc được và vài dòng mẫu, không cộng tiền */
    private function paymentsTest(): array
    {
        [, $sp] = $this->spAdmin();
        try {
            $list = Wallet::fetch($sp);
        } catch (HttpError $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        $in = array_values(array_filter($list, fn($t) => $t['in']));
        return [
            'ok' => true,
            'total' => count($list),
            'incoming' => count($in),
            'sample' => array_map(fn($t) => $t + ['match' => ($m = Wallet::matchUser($t['description'], $sp)) ? ($m['display_name'] ?: $m['username']) : null], array_slice($in, 0, 5)),
        ];
    }

    private function paymentsSync(): array
    {
        [, $sp] = $this->spAdmin();
        $r = Wallet::sync(true, $sp);
        $c = Wallet::config($sp);
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
        [$me, $sp] = $this->spAdmin();
        $u = $this->findUser();
        if ((int)DB::val('SELECT space_id FROM bank_tx WHERE id = ?', [$this->int('id')]) !== $sp) fail('Không tìm thấy giao dịch', 404);
        Wallet::assign($this->int('id'), (int)$u['id'], $me['username']);
        Repo::log(null, $me['username'], 'wallet_assign', null, 'Gán giao dịch #' . $this->int('id') . ' cho ' . $u['username']);
        return ['ok' => true];
    }

    private function paymentsIgnore(): array
    {
        [, $sp] = $this->spAdmin();
        DB::run("UPDATE bank_tx SET status = 'ignored' WHERE id = ? AND space_id = ? AND status <> 'credited'", [$this->int('id'), $sp]);
        return ['ok' => true];
    }

    /** Chủ không gian (admin web / khách thuê) cộng / trừ số dư ví thành viên trong không gian của mình */
    private function paymentsAdjust(): array
    {
        [$me, $sp] = $this->spAdmin();
        $u = $this->findUser();
        $amount = $this->int('amount');
        if ($amount === 0) fail('Nhập số tiền (số âm để trừ)');
        $note = (trim((string)$this->in('note', '')) ?: 'Điều chỉnh') . " (bởi {$me['username']})";
        $bal = $amount > 0 ? Wallet::credit((int)$u['id'], $amount, 'admin', $note, null, [], $sp) : Wallet::debit((int)$u['id'], -$amount, 'admin', $note, [], $sp);
        Repo::log(null, $me['username'], 'wallet_adjust', null, ($amount > 0 ? '+' : '') . Wallet::money($amount) . " cho {$u['username']} · $note" . ($sp ? ' (ví khách thuê)' : ''));
        return ['balance' => $bal];
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

    // ================= Học tập =================
    private function learnRow(array $p): array
    {
        return [
            'tg_user_id' => (string)$p['tg_user_id'], 'status' => $p['status'], 'step' => (int)$p['step'], 'wrong' => (int)$p['wrong'],
            'name' => Repo::name($p + ['tg_user_id' => $p['tg_user_id']]), 'username' => $p['username'],
            'groups' => json_decode((string)$p['groups_json'], true) ?: [],
            'started_at' => $p['started_at'] ? (int)$p['started_at'] : null, 'done_at' => $p['done_at'] ? (int)$p['done_at'] : null, 'updated_at' => (int)$p['updated_at'],
        ];
    }

    private function learn(): array
    {
        [, $lg] = $this->featScope();
        $status = (string)$this->in('status', '');
        $q = trim((string)$this->in('q', ''));
        $where = [];
        $p = [];
        if (in_array($status, ['required', 'learning', 'done'], true)) { $where[] = 'status = ?'; $p[] = $status; }
        if ($q !== '') {
            $where[] = '(LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ? OR LOWER(username) LIKE ? OR CAST(tg_user_id AS CHAR) LIKE ?)';
            $like = '%' . mb_strtolower(ltrim($q, '@')) . '%';
            array_push($p, $like, $like, $like, $like);
        }
        $rows = DB::all('SELECT * FROM learn_progress' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY updated_at DESC LIMIT 100', $p);
        $count = fn($st) => (int)DB::val('SELECT COUNT(*) FROM learn_progress WHERE status = ?', [$st]);
        if ($lg) { $rows = []; } // chế độ nhóm: không xem tiến độ chung
        return [
            'config' => Learn::config($lg),
            'scope' => $this->featInfo('learn', $lg),
            'defaults' => Learn::DEFAULTS,
            'scopes' => Learn::SCOPES,
            'modes' => Learn::MODES,
            'questions' => $lg ? [] : array_map([Learn::class, 'questionRow'], Learn::questions(false)),
            'question_count' => count(Learn::questions(true)),
            'groups' => $this->featGroups($lg),
            'bots' => array_map(fn($b) => $b['username'], DB::all('SELECT username FROM tg_bots WHERE enabled = 1 AND username IS NOT NULL ORDER BY id')),
            'cmd' => Commands::name('learn'),
            'stats' => ['done' => $count('done'), 'learning' => $count('learning'), 'required' => $count('required')],
            'progress' => array_map([$this, 'learnRow'], $rows),
        ];
    }

    private function learnSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = Learn::save((array)$this->in('config', []), $g, $u['username']);
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Cài đặt Học tập: ' . ($c['enabled'] ? 'bật' : 'tắt'));
        return ['config' => $c];
    }

    private function learnQuestionSave(): array
    {
        Auth::requireAdmin();
        $id = $this->int('id');
        $question = trim((string)$this->in('question', ''));
        if (Learn::norm($question) === '') fail('Nhập nội dung câu hỏi');
        if (mb_strlen($question) > 3500) fail('Câu hỏi quá dài (tối đa ~3500 ký tự)');
        $answers = $this->in('answers', []);
        if (is_string($answers)) $answers = preg_split('/\r?\n/', $answers);
        $answers = array_values(array_unique(array_filter(array_map(fn($a) => trim(mb_substr((string)$a, 0, 500)), (array)$answers), fn($a) => $a !== '')));
        if (!$answers) fail('Nhập ít nhất 1 đáp án đúng');
        if (count($answers) > 20) fail('Tối đa 20 đáp án cho 1 câu');
        $data = [
            'question' => $question,
            'answers' => json_encode($answers, JSON_UNESCAPED_UNICODE),
            'match_mode' => isset(Learn::MODES[$this->in('match_mode')]) ? $this->in('match_mode') : 'exact',
            'hint' => trim(mb_substr((string)$this->in('hint', ''), 0, 500)),
            'enabled' => $this->in('enabled', true) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($id) {
            DB::get('SELECT id FROM learn_questions WHERE id = ?', [$id]) ?: fail('Không tìm thấy câu hỏi', 404);
            DB::update('learn_questions', $data, 'id = ?', [$id]);
        } else {
            $data += ['sort' => (int)DB::val('SELECT COALESCE(MAX(sort), 0) + 1 FROM learn_questions'), 'created_at' => now()];
            $id = DB::insert('learn_questions', $data);
        }
        return ['question' => Learn::questionRow(DB::get('SELECT * FROM learn_questions WHERE id = ?', [$id]))];
    }

    private function learnQuestionDelete(): array
    {
        Auth::requireAdmin();
        DB::run('DELETE FROM learn_questions WHERE id = ?', [$this->int('id')]);
        return [];
    }

    private function learnQuestionMove(): array
    {
        Auth::requireAdmin();
        $ids = array_map(fn($r) => (int)$r['id'], DB::all('SELECT id FROM learn_questions ORDER BY sort, id'));
        $i = array_search($this->int('id'), $ids, true);
        if ($i === false) fail('Không tìm thấy câu hỏi', 404);
        $j = $i + ($this->int('dir') < 0 ? -1 : 1);
        if ($j >= 0 && $j < count($ids)) [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $k => $qid) DB::run('UPDATE learn_questions SET sort = ? WHERE id = ?', [$k + 1, $qid]);
        return [];
    }

    /** Bắt học lại từ đầu */
    private function learnReset(): array
    {
        $u = Auth::requireAdmin();
        $tg = (string)$this->in('tg_user_id', '');
        if (!preg_match('/^\d{3,15}$/', $tg)) fail('ID Telegram không hợp lệ');
        if (!DB::run("UPDATE learn_progress SET status = 'required', step = 0, wrong = 0, done_at = NULL, updated_at = ? WHERE tg_user_id = ?", [now(), $tg])) {
            DB::insert('learn_progress', ['tg_user_id' => $tg, 'status' => 'required', 'step' => 0, 'wrong' => 0, 'groups_json' => '[]', 'updated_at' => now()]);
        }
        Repo::log(null, $u['username'], 'system', null, "Học tập: bắt học lại ID $tg");
        return [];
    }

    /** Đánh dấu đã học (cho chat ngay) */
    private function learnPass(): array
    {
        $u = Auth::requireAdmin();
        $tg = (string)$this->in('tg_user_id', '');
        if (!preg_match('/^\d{3,15}$/', $tg)) fail('ID Telegram không hợp lệ');
        $p = Learn::progress($tg);
        $m = DB::get('SELECT * FROM tg_members WHERE tg_user_id = ? ORDER BY last_active DESC', [$tg]);
        $from = ['id' => $tg, 'first_name' => $p['first_name'] ?? $m['first_name'] ?? $tg, 'last_name' => $p['last_name'] ?? $m['last_name'] ?? null, 'username' => $p['username'] ?? $m['username'] ?? null];
        Learn::finish(null, null, $from, '', $u['username']);
        return [];
    }

    // ================= Bàn phím bot (chat riêng) =================
    private function keyboard(): array
    {
        Auth::requireAdmin();
        return [
            'config' => BotKeyboard::config(),
            'actions' => array_map(fn($a) => ['name' => $a[0], 'desc' => $a[1]], BotKeyboard::ACTIONS),
            'learn_enabled' => GroupFeature::anyEnabled('learn'),
            'bots' => array_map(fn($b) => $b['username'], DB::all('SELECT username FROM tg_bots WHERE enabled = 1 AND username IS NOT NULL ORDER BY id')),
            'max' => BotKeyboard::MAX,
        ];
    }

    private function keyboardSave(): array
    {
        $u = Auth::requireAdmin();
        $c = BotKeyboard::save((array)$this->in('config', []));
        Repo::log(null, $u['username'], 'system', null, 'Bàn phím bot: ' . count($c['buttons']) . ' nút' . ($c['enabled'] ? '' : ' (tắt)'));
        return ['config' => $c];
    }

    // ================= Điều kiện nhắn tin giữa các nhóm =================
    private function gates(): array
    {
        Auth::requireAdmin();
        $groups = DB::all('SELECT id, title, username FROM tg_groups WHERE active = 1 ORDER BY title');
        $rules = array_map(function ($r) {
            $r['blocked'] = (int)DB::val('SELECT COUNT(*) FROM tg_rule_hits WHERE rule_id = ? AND last_at > ?', [ChatGate::HIT_RULE_ID - (int)$r['id'], now() - 86400]);
            return $r;
        }, ChatGate::all());
        return [
            'rules' => $rules,
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'], 'public' => (bool)$g['username']], $groups),
            'metrics' => array_map(fn($m) => ['name' => $m[0], 'unit' => $m[1]], ChatGate::METRICS),
            'default_text' => ChatGate::DEFAULT_TEXT,
            'default_text_member' => ChatGate::DEFAULT_TEXT_MEMBER,
        ];
    }

    private function gateSave(): array
    {
        $u = Auth::requireAdmin();
        $r = ChatGate::save((array)$this->in('rule', []));
        Repo::log(null, $u['username'], 'system', null, 'Lưu điều kiện nhắn tin #' . $r['id']);
        return ['rule' => $r];
    }

    private function gateDelete(): array
    {
        Auth::requireAdmin();
        ChatGate::delete($this->int('id'));
        return [];
    }

    /** Kiểm tra thử 1 người (ID / @username) có qua các điều kiện không */
    private function gateCheck(): array
    {
        Auth::requireAdmin();
        $who = ltrim(trim((string)$this->in('who', '')), '@');
        $tg = preg_match('/^\d{3,15}$/', $who) ? $who : DB::val('SELECT tg_user_id FROM tg_members WHERE LOWER(username) = LOWER(?) ORDER BY last_active DESC', [$who]);
        if (!$tg) fail('Không tìm thấy thành viên này', 404);
        $out = [];
        foreach (ChatGate::all() as $r) {
            $g = Repo::group((int)$r['group_id']);
            $s = Repo::group((int)$r['source_id']);
            $out[] = ['id' => (int)$r['id'], 'group' => $g['title'] ?? '?', 'source' => $s['title'] ?? '?', 'metric' => $r['metric'], 'min' => (int)$r['min'],
                'have' => ChatGate::value($r, $tg), 'pass' => ChatGate::passes($r, $tg), 'enabled' => !empty($r['enabled'])];
        }
        $m = DB::get('SELECT * FROM tg_members WHERE tg_user_id = ? ORDER BY last_active DESC', [$tg]);
        return ['tg_user_id' => (string)$tg, 'name' => $m ? Repo::name($m) : (string)$tg, 'results' => $out];
    }

    // ================= Điểm danh trên web =================
    private function checkin(): array
    {
        $u = Auth::requireUser();
        $c = Checkin::config();
        $groups = [];
        if ($u['tg_user_id']) {
            foreach (DB::all("SELECT m.points, m.status, g.id, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND m.status IN ('member','muted') ORDER BY g.title", [$u['tg_user_id']]) as $r) {
                $gc = Checkin::config(['id' => (int)$r['id']]); // nhóm có thể có cài đặt riêng
                if (!$gc['enabled'] || !Checkin::applies($gc, (int)$r['id'])) continue;
                $st = Checkin::streak($u['tg_user_id'], (int)$r['id']);
                $days = array_column(DB::all('SELECT day FROM checkins WHERE tg_user_id = ? AND group_id = ? AND day >= ?', [$u['tg_user_id'], $r['id'], date('Y-m-d', strtotime('-6 day'))]), 'day');
                $groups[] = ['id' => (int)$r['id'], 'title' => $r['title'], 'points' => (int)$r['points'], 'today' => $st['today'], 'streak' => $st['streak'],
                    'reward' => Checkin::reward($gc, $st['streak'] + 1), 'week' => $days,
                    'total_days' => (int)DB::val('SELECT COUNT(*) FROM checkins WHERE tg_user_id = ? AND group_id = ?', [$u['tg_user_id'], $r['id']])];
            }
        }
        $out = ['enabled' => $c['enabled'] || (bool)$groups || GroupFeature::anyEnabled('checkin'), 'linked' => (bool)$u['tg_user_id'], 'points' => $c['points'], 'streak_bonus' => $c['streak_bonus'], 'streak_max' => $c['streak_max'],
            'groups' => $groups, 'today' => date('Y-m-d'), 'week' => array_map(fn($i) => date('Y-m-d', strtotime("-$i day")), range(6, 0))];
        if (Access::isWebAdmin($u)) {
            $out['admin'] = [
                'config' => $c,
                'all_groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT id, title FROM tg_groups WHERE active = 1 ORDER BY title')),
                'today_count' => (int)DB::val('SELECT COUNT(*) FROM checkins WHERE day = ?', [date('Y-m-d')]),
                'today_points' => (int)DB::val('SELECT COALESCE(SUM(points),0) FROM checkins WHERE day = ?', [date('Y-m-d')]),
                'default_text' => Checkin::DEFAULTS['notify_text'],
            ];
        }
        return $out;
    }

    /** Cài đặt điểm danh của 1 nhóm (hoặc chung) */
    private function checkinConfig(): array
    {
        [, $g] = $this->featScope();
        $w = $g ? ' AND group_id = ' . (int)$g['id'] : '';
        return ['config' => Checkin::config($g), 'scope' => $this->featInfo('checkin', $g), 'default_text' => Checkin::DEFAULTS['notify_text'],
            'today_count' => (int)DB::val('SELECT COUNT(*) FROM checkins WHERE day = ?' . $w, [date('Y-m-d')]),
            'today_points' => (int)DB::val('SELECT COALESCE(SUM(points),0) FROM checkins WHERE day = ?' . $w, [date('Y-m-d')])];
    }

    private function checkinDo(): array
    {
        $u = Auth::requireUser();
        return Checkin::perform($u, $this->int('group_id'));
    }

    private function checkinSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = Checkin::save((array)$this->in('config', []), $g, $u['username']);
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Điểm danh: ' . ($c['enabled'] ? "bật · {$c['points']} điểm" : 'tắt'));
        return ['config' => $c];
    }

    // ================= Tag thành viên =================
    /** Đổi tag 1 thành viên từ web (quyền xử lý thành viên) */
    private function memberTag(): array
    {
        $m = DB::get('SELECT * FROM tg_members WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy thành viên', 404);
        $g = Repo::group((int)$m['group_id']) ?: fail('Không tìm thấy nhóm', 404);
        $u = $this->gate('moderate', $g);
        $row = MemberTag::set($g, $m['tg_user_id'], (string)$this->in('tag', ''), ($u['display_name'] ?: $u['username']) . ' (Web)', 'manual', (bool)$this->in('notify', true));
        return $this->memberRow($row);
    }

    private function tags(): array
    {
        Auth::requireAdmin();
        $c = MemberTag::config();
        $groups = DB::all('SELECT id, title FROM tg_groups WHERE active = 1 ORDER BY title');
        return [
            'config' => $c,
            'defaults' => MemberTag::DEFAULTS,
            'error' => Settings::get('member_tags_error') ?: null,
            'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'],
                'tagged' => (int)DB::val("SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND tag IS NOT NULL AND tag <> ''", [$g['id']])], $groups),
            'recent' => array_map(fn($r) => ['name' => Repo::name($r), 'tg_user_id' => (string)$r['tg_user_id'], 'tag' => $r['tag'], 'source' => $r['tag_source'], 'group' => $r['title'], 'points' => (int)$r['points']],
                DB::all("SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tag IS NOT NULL AND m.tag <> '' ORDER BY m.points DESC LIMIT 50")),
            'cmd' => Commands::name('tag'),
        ];
    }

    private function tagsSave(): array
    {
        $u = Auth::requireAdmin();
        $c = MemberTag::save((array)$this->in('config', []));
        Repo::log(null, $u['username'], 'system', null, 'Cài đặt tag thành viên' . ($c['auto'] ? ' · tự động ' . count($c['auto_levels']) . ' mốc' : ''));
        return ['config' => $c];
    }

    /** Áp dụng tag theo điểm ngay cho thành viên hiện có (tối đa 150 người mỗi lần, tránh giới hạn Telegram) */
    private function tagsApply(): array
    {
        Auth::requireAdmin();
        $c = MemberTag::config();
        if (!$c['auto'] || !$c['auto_levels']) fail('Bật "Tag tự động theo điểm" và thêm mốc điểm trước');
        @set_time_limit(120);
        session_write_close();
        $min = (int)$c['auto_levels'][0]['min'];
        $where = $c['auto_groups'] ? ' AND m.group_id IN (' . implode(',', array_map('intval', $c['auto_groups'])) . ')' : '';
        $rows = DB::all("SELECT m.* FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.is_bot = 0 AND m.status IN ('member','muted') AND m.points >= ?
            AND (m.tag_source IS NULL OR m.tag_source <> 'manual' OR m.tag IS NULL OR m.tag = '')$where ORDER BY m.points DESC LIMIT 400", [$min]);
        $done = 0; $skip = 0; $fail = 0; $err = null;
        foreach ($rows as $m) {
            $want = MemberTag::levelTag($c, (int)$m['points']);
            if ($want === '' || $want === (string)$m['tag'] || ($m['is_admin'] && !$c['auto_admins'])) { $skip++; continue; }
            if ($done + $fail >= 150) break;
            try {
                MemberTag::set(Repo::group((int)$m['group_id']), $m['tg_user_id'], $want, 'Bot (tag theo điểm)', 'auto', false, 'bot');
                $done++;
            } catch (HttpError $e) { $fail++; $err = $e->getMessage(); }
            usleep(120000);
        }
        return ['done' => $done, 'skipped' => $skip, 'failed' => $fail, 'error' => $err];
    }

    // ================= Cửa hàng tag (thành viên) =================
    private function tagShop(): array
    {
        $u = Auth::requireUser();
        $c = MemberTag::config();
        $groups = [];
        if ($u['tg_user_id']) {
            foreach (DB::all("SELECT m.tag, m.tag_source, m.tag_until, m.is_admin, g.id, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND m.status IN ('member','muted') ORDER BY g.title", [$u['tg_user_id']]) as $r) {
                if (!MemberTag::shopApplies($c, (int)$r['id'])) continue;
                $groups[] = ['id' => (int)$r['id'], 'title' => $r['title'], 'tag' => $r['tag'], 'source' => $r['tag_source'], 'until' => $r['tag_until'] ? (int)$r['tag_until'] : null, 'is_admin' => (bool)$r['is_admin']];
            }
        }
        $mine = DB::all("SELECT * FROM wallet_tx WHERE user_id = ? AND type IN ('tag','tag_fail') ORDER BY id DESC LIMIT 10", [$u['id']]);
        return [
            'enabled' => $c['shop'], 'linked' => (bool)$u['tg_user_id'], 'balance' => Wallet::balance((int)$u['id']),
            'items' => $c['shop_items'], 'custom' => $c['shop_custom'], 'custom_price' => $c['shop_custom_price'], 'days' => $c['shop_days'],
            'banned' => array_values(array_filter(array_map('trim', preg_split('/[,\n]+/u', (string)$c['shop_banned'])))),
            'groups' => $groups, 'wallet' => Wallet::config()['enabled'],
            'history' => array_map([$this, 'txRow'], $mine),
        ];
    }

    private function tagShopBuy(): array
    {
        $u = Auth::requireUser();
        return MemberTag::buy($u, $this->int('group_id'), (string)$this->in('tag', ''), (bool)$this->in('custom'));
    }

    // ================= Đổi điểm ↔ tiền =================
    private function exchange(): array
    {
        $u = Auth::requireUser();
        $sp = $this->memberSp();
        $c = Exchange::config($sp);
        $groups = [];
        if ($u['tg_user_id']) {
            foreach (DB::all("SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND m.status IN ('member','muted') ORDER BY g.title", [$u['tg_user_id']]) as $m) {
                if (!Exchange::applies($c, (int)$m['group_id'], $sp)) continue;
                $groups[] = ['id' => (int)$m['group_id'], 'title' => $m['title'], 'total' => (int)$m['points'], 'open' => Exchange::open($m), 'spent' => (int)$m['points_spent']];
            }
        }
        $out = ['space' => $this->spaceInfo($u, $sp), 'enabled' => $c['enabled'], 'linked' => (bool)$u['tg_user_id'], 'balance' => Wallet::balance((int)$u['id'], $sp),
            'rate_points' => $c['rate_points'], 'rate_money' => $c['rate_money'], 'min_points' => $c['min_points'], 'daily_max' => $c['daily_max'],
            'buy' => $c['buy'], 'buy_money' => $c['buy_money'], 'buy_points' => $c['buy_points'],
            'today' => (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND space_id = ? AND type = 'exchange' AND created_at >= ?", [$u['id'], $sp, strtotime('today')]),
            'groups' => $groups,
            'history' => array_map([$this, 'txRow'], DB::all("SELECT * FROM wallet_tx WHERE user_id = ? AND space_id = ? AND type IN ('exchange','buy_points') ORDER BY id DESC LIMIT 10", [$u['id'], $sp]))];
        if ($this->isSpaceAdmin($u, $sp)) {
            $out['admin'] = ['config' => ['enabled' => $c['enabled_raw']] + $c, 'all_groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT g.id, g.title FROM tg_groups g WHERE g.active = 1 AND ' . Space::groupsSql($sp, 'g') . ' ORDER BY g.title')),
                'paid' => (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE type = 'exchange' AND space_id = ?", [$sp]), 'paid_today' => (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE type = 'exchange' AND space_id = ? AND created_at >= ?", [$sp, strtotime('today')])];
        }
        return $out;
    }

    private function exchangeDo(): array
    {
        $u = Auth::requireUser();
        return Exchange::toMoney($u, $this->int('group_id'), $this->int('points'));
    }

    private function exchangeBuy(): array
    {
        $u = Auth::requireUser();
        return Exchange::buyPoints($u, $this->int('group_id'), $this->int('money'));
    }

    private function exchangeSave(): array
    {
        [$u, $sp] = $this->spAdmin();
        $c = Exchange::save((array)$this->in('config', []), $sp);
        Repo::log(null, $u['username'], 'system', null, 'Đổi điểm: ' . ($c['enabled'] ? "bật · {$c['rate_points']} điểm = " . Wallet::money($c['rate_money']) : 'tắt'));
        return ['config' => $c];
    }

    // ================= Mã quà tặng =================
    private function gift(): array
    {
        $u = Auth::requireUser();
        $sp = $this->memberSp();
        $who = $u['tg_user_id'] ? (string)$u['tg_user_id'] : 'u' . $u['id'];
        $out = ['mine' => array_map(fn($r) => ['code' => $r['code'], 'type' => $r['type'], 'amount' => (int)$r['amount'], 'created_at' => (int)$r['created_at']],
            DB::all('SELECT r.created_at, c.code, c.type, c.amount FROM gift_redemptions r JOIN gift_codes c ON c.id = r.code_id WHERE r.who = ? ORDER BY r.id DESC LIMIT 20', [$who])),
            'linked' => (bool)$u['tg_user_id'], 'cmd' => Commands::name('giftcode'),
            'bot' => DB::val('SELECT username FROM tg_bots WHERE enabled = 1 AND ' . ($sp ? 'owner_id = ' . $sp : 'owner_id IS NULL') . ' ORDER BY id LIMIT 1'), 'space' => $this->spaceInfo($u, $sp)];
        if ($this->isSpaceAdmin($u, $sp)) {
            $out['admin'] = [
                'codes' => array_map(function ($r) { $x = GiftCode::row($r); $x['group_title'] = $r['group_title']; $x['cond_text'] = GiftCode::describe($x['conditions']); return $x; },
                    DB::all('SELECT c.*, g.title AS group_title FROM gift_codes c LEFT JOIN tg_groups g ON g.id = c.group_id WHERE c.space_id = ? ORDER BY c.id DESC LIMIT 200', [$sp])),
                'types' => GiftCode::TYPES,
                'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT g.id, g.title FROM tg_groups g WHERE g.active = 1 AND ' . Space::groupsSql($sp, 'g') . ' ORDER BY g.title')),
                'recent' => array_map(fn($r) => ['code' => $r['code'], 'who' => $r['display_name'] ?: ($r['tg_user_id'] ?: $r['who']), 'created_at' => (int)$r['created_at']],
                    DB::all('SELECT r.*, c.code, u.display_name FROM gift_redemptions r JOIN gift_codes c ON c.id = r.code_id LEFT JOIN app_users u ON u.id = r.user_id WHERE c.space_id = ? ORDER BY r.id DESC LIMIT 30', [$sp])),
            ];
        }
        return $out;
    }

    private function giftRedeem(): array
    {
        $u = Auth::requireUser();
        $r = GiftCode::redeem($u, $u['tg_user_id'] ?: null, (string)$this->in('code', ''));
        return $r + ['balance' => Wallet::balance((int)$u['id'], (int)$r['space'])];
    }

    private function giftSave(): array
    {
        [$u, $sp] = $this->spAdmin();
        if ($sp && !Space::allows($sp, 'giftcode')) fail('Gói thuê của bạn chưa có tính năng Mã quà tặng', 402);
        $r = GiftCode::save((array)$this->in('code', []), $sp);
        Repo::log(null, $u['username'], 'system', null, "Lưu mã quà tặng {$r['code']}");
        return ['code' => $r];
    }

    private function giftDelete(): array
    {
        [, $sp] = $this->spAdmin();
        if (!DB::run('DELETE FROM gift_codes WHERE id = ? AND space_id = ?', [$this->int('id'), $sp])) fail('Không tìm thấy mã', 404);
        DB::run('DELETE FROM gift_redemptions WHERE code_id = ?', [$this->int('id')]);
        return [];
    }

    // ================= Gói VIP =================
    private function vip(): array
    {
        $u = Auth::requireUser();
        $sp = $this->memberSp();
        $c = Vip::config($sp);
        $until = Vip::until($u['tg_user_id'], $sp);
        $out = ['space' => $this->spaceInfo($u, $sp), 'enabled' => $c['enabled'], 'linked' => (bool)$u['tg_user_id'], 'balance' => Wallet::balance((int)$u['id'], $sp), 'until' => $until, 'active' => $until && $until > now(),
            'plans' => $c['plans'], 'perks' => ['tag' => $c['tag'], 'gates' => $c['perk_gates'], 'learn' => $c['perk_learn'], 'links' => $c['perk_links'], 'multiplier' => $c['multiplier']]];
        if ($this->isSpaceAdmin($u, $sp)) {
            $c['enabled'] = $c['enabled_raw'];
            $out['admin'] = ['config' => $c, 'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title']], DB::all('SELECT g.id, g.title FROM tg_groups g WHERE g.active = 1 AND ' . Space::groupsSql($sp, 'g') . ' ORDER BY g.title')),
                'members' => array_map(function ($v) {
                    $m = DB::get('SELECT first_name, last_name, username FROM tg_members WHERE tg_user_id = ? ORDER BY last_active DESC', [$v['tg_user_id']]);
                    return ['tg_user_id' => (string)$v['tg_user_id'], 'name' => $m ? Repo::name($m + ['tg_user_id' => $v['tg_user_id']]) : (string)$v['tg_user_id'], 'until' => (int)$v['until'], 'active' => (int)$v['until'] > now()];
                }, $sp ? DB::all('SELECT * FROM space_vip WHERE space_id = ? ORDER BY until DESC LIMIT 100', [$sp]) : DB::all('SELECT * FROM vip_members ORDER BY until DESC LIMIT 100')),
                'revenue' => -(int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE type = 'vip' AND space_id = ?", [$sp])];
        }
        return $out;
    }

    private function vipBuy(): array
    {
        $u = Auth::requireUser();
        return Vip::buy($u, $this->int('plan'), $this->memberSp());
    }

    private function vipSave(): array
    {
        [$u, $sp] = $this->spAdmin();
        $c = Vip::save((array)$this->in('config', []), $sp);
        Repo::log(null, $u['username'], 'system', null, 'Gói VIP: ' . ($c['enabled'] ? 'bật · ' . count($c['plans']) . ' gói' : 'tắt'));
        return ['config' => $c];
    }

    /** Admin tặng / trừ ngày VIP theo ID Telegram (số âm để rút ngắn) */
    private function vipGrant(): array
    {
        [$u, $sp] = $this->spAdmin();
        $tg = ltrim(trim((string)$this->in('who', '')), '@');
        if (!preg_match('/^\d{3,15}$/', $tg)) $tg = (string)DB::val('SELECT tg_user_id FROM tg_members WHERE LOWER(username) = LOWER(?) ORDER BY last_active DESC', [$tg]);
        if (!$tg) fail('Không tìm thấy thành viên (nhập ID hoặc @username)', 404);
        $days = $this->int('days');
        if ($days === 0) fail('Nhập số ngày');
        if ($days > 0) $until = Vip::extend($tg, $days, 'admin ' . $u['username'] . ' tặng', $sp);
        else {
            $cur = Vip::until($tg, $sp) ?? now();
            $until = max(now() - 1, $cur + $days * 86400);
            if ($sp) DB::run('UPDATE space_vip SET until = ?, notified = 0 WHERE space_id = ? AND tg_user_id = ?', [$until, $sp, $tg]);
            else DB::run('UPDATE vip_members SET until = ?, notified = 0 WHERE tg_user_id = ?', [$until, $tg]);
        }
        return ['until' => $until];
    }

    // ================= Cài đặt tính năng theo nhóm =================
    /**
     * Phạm vi cài đặt tính năng: có group_id → cài đặt riêng của nhóm đó (quản trị viên nhóm có quyền "Cài đặt nhóm");
     * không có → cài đặt chung (chỉ admin web). Trả về [user, group|null].
     */
    private function featScope(): array
    {
        $gid = $this->int('group_id');
        if (!$gid) return [Auth::requireAdmin(), null];
        $g = Repo::group($gid) ?: fail('Không tìm thấy nhóm', 404);
        return [$this->gate('settings', $g), $g];
    }

    /** Danh sách nhóm hiển thị trên trang tính năng: chế độ nhóm chỉ có chính nhóm đó */
    private function featGroups(?array $g): array
    {
        $rows = $g ? [$g] : DB::all('SELECT g.id, g.title, g.bot_is_admin FROM tg_groups g WHERE g.active = 1 AND ' . Tenant::ownerGroupsSql('g') . ' ORDER BY g.title');
        return array_map(fn($r) => ['id' => (int)$r['id'], 'title' => $r['title'], 'bot_admin' => (int)$r['bot_is_admin']], $rows);
    }

    /** Thông tin phạm vi cho giao diện */
    private function featInfo(string $key, ?array $g): array
    {
        $over = array_map(fn($r) => ['id' => (int)$r['id'], 'title' => $r['title'], 'enabled' => !empty((json_decode((string)$r['config'], true) ?: [])['enabled'])],
            DB::all('SELECT g.id, g.title, f.config FROM group_features f JOIN tg_groups g ON g.id = f.group_id WHERE f.feature = ? AND g.active = 1 ORDER BY g.title', [$key]));
        return [
            'key' => $key,
            'group' => $g ? ['id' => (int)$g['id'], 'title' => $g['title']] : null,
            'custom' => $g ? GroupFeature::get($key, (int)$g['id']) !== null : false,
            'overrides' => $g ? [] : $over,
            'all_groups' => $g ? [] : array_map(fn($r) => ['id' => (int)$r['id'], 'title' => $r['title']], DB::all('SELECT g.id, g.title FROM tg_groups g WHERE g.active = 1 AND ' . Tenant::ownerGroupsSql('g') . ' ORDER BY g.title')),
            'global_enabled' => (bool)(json_decode((string)Settings::get($key), true)['enabled'] ?? false),
        ];
    }

    /** Bỏ cài đặt riêng của nhóm → dùng lại cài đặt chung */
    private function featureReset(): array
    {
        $key = (string)$this->in('feature');
        if (!isset(GroupFeature::FEATURES[$key])) fail('Tính năng không hợp lệ');
        [$u, $g] = $this->featScope();
        if (!$g) fail('Thiếu nhóm');
        GroupFeature::reset($key, (int)$g['id']);
        Repo::log((int)$g['id'], $u['username'], 'settings', null, GroupFeature::FEATURES[$key][1] . ': dùng lại cài đặt chung');
        return [];
    }

    /** Các tính năng của 1 nhóm (tab Tính năng trong trang nhóm) */
    private function groupFeatures(): array
    {
        $g = $this->loadGroup();
        $this->gate('settings', $g);
        return ['features' => GroupFeature::summary($g)];
    }

    // ================= Nhắn riêng thành viên qua bot =================
    /** Nhóm được nhắn: quyền "Gửi tin vào nhóm" + gói thuê có "Nhắn riêng thành viên" (nhóm khách thuê) */
    private function dmGroups(array $u): array
    {
        $ids = Access::groupIds($u);
        $out = [];
        foreach (DB::all('SELECT * FROM tg_groups WHERE active = 1 AND bot_id IS NOT NULL AND ' . Access::sqlIn($ids, 'id') . ' ORDER BY title') as $g) {
            if (!Access::can($u, 'send', (int)$g['id']) || !Tenant::allows($g, 'dm')) continue;
            $out[(int)$g['id']] = $g;
        }
        return $out;
    }

    private function dmPick(array $u): array
    {
        $allowed = $this->dmGroups($u);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->in('group_ids', [])), fn($id) => isset($allowed[$id]))));
        if (!$ids) fail('Chọn ít nhất 1 nhóm');
        return $ids;
    }

    private function dmCampaigns(array $u): array
    {
        $botIds = array_unique(array_map(fn($g) => (int)$g['bot_id'], $this->dmGroups($u)));
        if (!$botIds) return [];
        return array_map([DmBroadcast::class, 'row'], DB::all('SELECT * FROM dm_campaigns WHERE bot_id IN (' . implode(',', $botIds) . ') ORDER BY id DESC LIMIT 30'));
    }

    private function dm(): array
    {
        $u = $this->gate('send');
        return [
            'groups' => array_values(array_map(fn($g) => ['id' => (int)$g['id'], 'title' => $g['title'], 'bot' => DB::val('SELECT username FROM tg_bots WHERE id = ?', [$g['bot_id']])] + DmBroadcast::groupStats($g), $this->dmGroups($u))),
            'campaigns' => $this->dmCampaigns($u), 'vars' => DmBroadcast::VARS, 'me' => $u['tg_user_id'] ? (string)$u['tg_user_id'] : null, 'speed' => DmBroadcast::PER_SECOND,
        ];
    }

    private function dmCount(): array
    {
        $u = $this->gate('send');
        return ['count' => DmBroadcast::count($this->dmPick($u), (bool)$this->in('include_left'))];
    }

    private function dmImage(): ?string
    {
        $img = (string)$this->in('image', '');
        return $img !== '' ? Ads::saveImage($img) : null;
    }

    private function dmSend(): array
    {
        $u = $this->gate('send');
        $ids = DmBroadcast::create($this->dmPick($u), (string)$this->in('text', ''), $this->dmImage(), (string)$this->in('buttons', ''), (bool)$this->in('silent'), (bool)$this->in('include_left'), $u['display_name'] ?: $u['username']);
        // Trả kết quả cho trình duyệt ngay, rồi gửi tiếp ở nền (cron mỗi phút sẽ gửi phần còn lại)
        if (!Repo::$testApi) register_shutdown_function(function () {
            if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
            ignore_user_abort(true);
            @set_time_limit(150);
            try { DmBroadcast::run(110); } catch (Throwable $e) {}
        });
        return ['ids' => $ids];
    }

    private function dmTest(): array
    {
        $u = $this->gate('send');
        if (!$u['tg_user_id']) fail('Liên kết Telegram ở trang Tài khoản để nhận tin gửi thử');
        $ids = $this->dmPick($u);
        $img = $this->dmImage();
        try { DmBroadcast::test($ids[0], $u['tg_user_id'], (string)$this->in('text', ''), $img, (string)$this->in('buttons', '')); }
        finally { if ($img) @unlink(Ads::imagePath($img)); }
        return [];
    }

    private function dmStatus(): array
    {
        $u = $this->gate('send');
        return ['campaigns' => $this->dmCampaigns($u)];
    }

    private function dmStop(): array
    {
        $u = $this->gate('send');
        $c = DB::get('SELECT * FROM dm_campaigns WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy', 404);
        if (!in_array((int)$c['bot_id'], array_map(fn($g) => (int)$g['bot_id'], $this->dmGroups($u)), true)) fail('Không tìm thấy', 404);
        DmBroadcast::stop((int)$c['id']);
        return [];
    }

    private function dmInvite(): array
    {
        $u = $this->gate('send');
        $allowed = $this->dmGroups($u);
        $g = $allowed[$this->int('group_id')] ?? fail('Chọn nhóm');
        DmBroadcast::invite($g, (string)$this->in('text', ''), (string)$this->in('button', ''));
        Repo::log((int)$g['id'], $u['username'], 'dm_send', null, 'Đăng lời mời Start bot để nhận tin riêng', ['source' => 'web']);
        return [];
    }

    // ================= Yêu cầu mở band =================
    /** Trang thành viên: các nhóm đang nhận yêu cầu + yêu cầu của tôi */
    private function unban(): array
    {
        $u = Auth::requireUser();
        $groups = [];
        foreach (DB::all('SELECT * FROM tg_groups WHERE active = 1 ORDER BY title') as $g) {
            if (!UnbanRequest::applies($g)) continue;
            $groups[] = ['id' => (int)$g['id'], 'title' => $g['title'], 'username' => $g['username'],
                'bot' => DB::val('SELECT username FROM tg_bots WHERE id = ?', [$g['bot_id']])];
        }
        return [
            'groups' => $groups,
            'me' => ['tg_user_id' => $u['tg_user_id'] ? (string)$u['tg_user_id'] : null, 'username' => null],
            'mine' => array_map([UnbanRequest::class, 'row'], DB::all('SELECT r.*, g.title AS group_title FROM unban_requests r JOIN tg_groups g ON g.id = r.group_id WHERE r.requester_id = ? ORDER BY r.id DESC LIMIT 30', [$u['id']])),
        ];
    }

    private function unbanSubmit(): array
    {
        $u = Auth::requireUser();
        return ['request' => UnbanRequest::submit($u, $this->int('group_id'), (string)$this->in('tg_user_id', ''), (string)$this->in('username', ''), (string)$this->in('reason', ''))];
    }

    /** Quản lý: yêu cầu ở các nhóm mình có quyền xử lý thành viên */
    private function unbanList(): array
    {
        $u = $this->gate('moderate');
        $ids = Access::groupIds($u);
        if ($ids !== null) $ids = array_values(array_filter($ids, fn($id) => Access::can($u, 'moderate', $id)));
        $status = (string)$this->in('status', 'pending');
        $where = Access::sqlIn($ids, 'r.group_id') . (isset(UnbanRequest::STATUS[$status]) ? " AND r.status = '$status'" : '');
        return [
            'rows' => array_map([UnbanRequest::class, 'row'], DB::all("SELECT r.*, g.title AS group_title FROM unban_requests r JOIN tg_groups g ON g.id = r.group_id WHERE $where ORDER BY r.id DESC LIMIT 100")),
            'pending' => (int)DB::val("SELECT COUNT(*) FROM unban_requests r WHERE r.status = 'pending' AND " . Access::sqlIn($ids, 'r.group_id')),
        ];
    }

    private function unbanDecide(): array
    {
        $r = DB::get('SELECT * FROM unban_requests WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy yêu cầu', 404);
        $g = Repo::group((int)$r['group_id']) ?: fail('Không tìm thấy nhóm', 404);
        $u = $this->gate('moderate', $g);
        return ['request' => UnbanRequest::decide((int)$r['id'], (bool)$this->in('approve'), $u['display_name'] ?: $u['username'], (string)$this->in('note', ''))];
    }

    /** Cài đặt tính năng (chung / riêng nhóm) */
    private function unbanReq(): array
    {
        [, $g] = $this->featScope();
        return ['config' => UnbanRequest::config($g), 'defaults' => UnbanRequest::DEFAULTS, 'scope' => $this->featInfo(UnbanRequest::KEY, $g), 'groups' => $this->featGroups($g),
            'link' => $g ? UnbanRequest::link($g) : site_url() . '/#/unban'];
    }

    private function unbanReqSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = UnbanRequest::save((array)$this->in('config', []), $g, $u['username']);
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Yêu cầu mở band: ' . ($c['enabled'] ? 'bật' : 'tắt'));
        return ['config' => $c];
    }

    // ================= Cho thuê: gói thuê của khách =================
    private function orderRow(array $o): array
    {
        return ['id' => (int)$o['id'], 'user' => isset($o['username']) ? ['id' => (int)$o['user_id'], 'name' => $o['display_name'] ?: $o['username'], 'username' => $o['username']] : null,
            'plan' => $o['plan_name'], 'days' => (int)$o['days'], 'amount' => (int)$o['amount'], 'kind' => $o['kind'], 'until_after' => $o['until_after'] ? (int)$o['until_after'] : null,
            'by' => $o['by_user'], 'note' => $o['note'], 'created_at' => (int)$o['created_at']];
    }

    private function plan(): array
    {
        $u = Auth::requireUser();
        $c = Tenant::config();
        return [
            'enabled' => $c['enabled'], 'intro' => $c['intro'], 'features' => Tenant::FEATURES,
            'plans' => Tenant::plans(), 'me' => Tenant::summary($u), 'balance' => Wallet::balance((int)$u['id']),
            'used_free' => array_map('intval', array_column(DB::all("SELECT DISTINCT o.plan_id FROM tenant_orders o JOIN plans p ON p.id = o.plan_id WHERE o.user_id = ? AND p.price = 0 AND o.kind IN ('buy','renew')", [$u['id']]), 'plan_id')),
            'orders' => array_map([$this, 'orderRow'], DB::all('SELECT * FROM tenant_orders WHERE user_id = ? ORDER BY id DESC LIMIT 30', [$u['id']])),
            'wallet' => Wallet::config()['enabled'] && Wallet::config()['account_no'] !== '',
        ];
    }

    private function planBuy(): array
    {
        $u = Auth::requireUser();
        return Tenant::buy($u, $this->int('plan_id'));
    }

    // ================= /admin: quản lý cho thuê (chủ web) =================
    private function adminPanel(): array
    {
        Auth::requireAdmin();
        $q = trim((string)$this->in('q', ''));
        $where = 'u.tenant = 1';
        $p = [];
        if ($q !== '') { $where .= ' AND (LOWER(u.username) LIKE ? OR LOWER(u.display_name) LIKE ? OR CAST(u.tg_user_id AS CHAR) LIKE ?)'; $l = '%' . mb_strtolower($q) . '%'; $p = [$l, $l, $l]; }
        $tenants = array_map(function ($r) {
            $us = Tenant::usage((int)$r['id']);
            $pl = Tenant::plan((int)$r['plan_id']);
            return ['id' => (int)$r['id'], 'username' => $r['username'], 'name' => $r['display_name'] ?: $r['username'], 'tg_user_id' => $r['tg_user_id'] ? (string)$r['tg_user_id'] : null,
                'plan' => $pl ? ['id' => $pl['id'], 'name' => $pl['name'], 'max_groups' => $pl['max_groups'], 'max_bots' => $pl['max_bots']] : null,
                'until' => $r['plan_until'] ? (int)$r['plan_until'] : null, 'active' => !$r['disabled'] && (int)$r['plan_until'] > now(), 'disabled' => (bool)$r['disabled'],
                'balance' => (int)$r['balance'], 'bots' => $us['bots'], 'groups' => $us['groups'],
                'paid' => (int)DB::val('SELECT COALESCE(SUM(amount),0) FROM tenant_orders WHERE user_id = ?', [$r['id']]), 'created_at' => (int)$r['created_at']];
        }, DB::all("SELECT u.* FROM app_users u WHERE $where ORDER BY u.plan_until DESC LIMIT 300", $p));
        $sum = fn($since) => (int)DB::val('SELECT COALESCE(SUM(amount),0) FROM tenant_orders WHERE created_at >= ?', [$since]);
        return [
            'config' => Tenant::config(), 'features' => Tenant::FEATURES,
            'plans' => array_map(fn($pl) => $pl + ['users' => (int)DB::val('SELECT COUNT(*) FROM app_users WHERE tenant = 1 AND plan_id = ? AND plan_until > ?', [$pl['id'], now()])], Tenant::plans(false)),
            'tenants' => $tenants,
            'orders' => array_map([$this, 'orderRow'], DB::all('SELECT o.*, u.username, u.display_name FROM tenant_orders o JOIN app_users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 50')),
            'stats' => [
                'tenants' => (int)DB::val('SELECT COUNT(*) FROM app_users WHERE tenant = 1'),
                'active' => (int)DB::val('SELECT COUNT(*) FROM app_users WHERE tenant = 1 AND disabled = 0 AND plan_until > ?', [now()]),
                'expiring' => (int)DB::val('SELECT COUNT(*) FROM app_users WHERE tenant = 1 AND disabled = 0 AND plan_until BETWEEN ? AND ?', [now(), now() + 7 * 86400]),
                'revenue_30' => $sum(now() - 30 * 86400), 'revenue_today' => $sum(strtotime('today')), 'revenue_all' => $sum(0),
                'bots' => (int)DB::val('SELECT COUNT(*) FROM tg_bots WHERE owner_id IS NOT NULL'),
                'groups' => (int)DB::val('SELECT COUNT(*) FROM tg_groups g JOIN tg_bots b ON b.id = g.bot_id WHERE b.owner_id IS NOT NULL AND g.active = 1'),
            ],
        ];
    }

    private function adminPlanSave(): array
    {
        $u = Auth::requireAdmin();
        $p = Tenant::savePlan((array)$this->in('plan', []));
        Repo::log(null, $u['username'], 'system', null, "Lưu gói thuê {$p['name']} · " . Wallet::money($p['price']) . " / {$p['days']} ngày");
        return ['plan' => $p];
    }

    private function adminPlanDelete(): array
    {
        $u = Auth::requireAdmin();
        $p = Tenant::plan($this->int('id')) ?: fail('Không tìm thấy gói', 404);
        if (DB::val('SELECT COUNT(*) FROM app_users WHERE plan_id = ?', [$p['id']])) {
            DB::run('UPDATE plans SET enabled = 0 WHERE id = ?', [$p['id']]);
            return ['disabled' => true]; // còn khách dùng → chỉ ngừng bán
        }
        DB::run('DELETE FROM plans WHERE id = ?', [$p['id']]);
        Repo::log(null, $u['username'], 'system', null, "Xoá gói thuê {$p['name']}");
        return ['disabled' => false];
    }

    /** Gia hạn / tặng / đổi gói cho 1 tài khoản (theo id hoặc tên đăng nhập) */
    private function adminGrant(): array
    {
        $a = Auth::requireAdmin();
        $uid = $this->int('user_id');
        if (!$uid && ($name = trim((string)$this->in('username'))) !== '') $uid = (int)DB::val('SELECT id FROM app_users WHERE LOWER(username) = LOWER(?)', [ltrim($name, '@')]);
        if (!$uid) fail('Không tìm thấy tài khoản');
        $days = max(-3650, min(3650, $this->int('days')));
        if (!$days) fail('Nhập số ngày');
        return ['until' => Tenant::grant($uid, $this->int('plan_id'), $days, $a['username'], (string)$this->in('note', ''))];
    }

    private function adminLock(): array
    {
        $a = Auth::requireAdmin();
        $t = DB::get('SELECT * FROM app_users WHERE id = ? AND tenant = 1', [$this->int('user_id')]) ?: fail('Không tìm thấy khách thuê', 404);
        if ($t['role'] === 'admin') fail('Không thể khoá admin web');
        $off = (bool)$this->in('disabled');
        DB::run('UPDATE app_users SET disabled = ? WHERE id = ?', [$off ? 1 : 0, $t['id']]);
        Repo::log(null, $a['username'], 'system', null, ($off ? 'Khoá' : 'Mở khoá') . ' khách thuê ' . $t['username']);
        return [];
    }

    private function adminConfig(): array
    {
        $a = Auth::requireAdmin();
        $c = Tenant::saveConfig((array)$this->in('config', []));
        Repo::log(null, $a['username'], 'system', null, 'Cho thuê: ' . ($c['enabled'] ? 'bật' : 'tắt'));
        return ['config' => $c];
    }

    // ================= Xuất / nhập cài đặt =================
    const EXPORT_APP = 'tg-group-manager';

    /** Xuất cài đặt: có group_id → cài đặt của nhóm đó; không có → cài đặt chung (admin web). Không chứa token / mật khẩu / ví. */
    private function settingsExport(): array
    {
        [$u, $g] = $this->featScope();
        $features = [];
        foreach (GroupFeature::FEATURES as $key => [$cls]) {
            $c = $cls::config($g);
            unset($c['_custom'], $c['groups'], $c['allow_ids']);
            $features[$key] = $c;
        }
        $rules = DB::all('SELECT * FROM tg_rules WHERE ' . ($g ? 'group_id = ' . (int)$g['id'] : 'group_id IS NULL') . ' ORDER BY priority DESC, id');
        $out = [
            'app' => self::EXPORT_APP, 'type' => $g ? 'group' : 'global', 'version' => trim((string)@file_get_contents(ROOT . '/VERSION')),
            'exported_at' => date('c'), 'source' => $g ? $g['title'] : (string)Settings::get('site_name'),
            'features' => $features,
            'rules' => array_map(function ($r) {
                $x = Rules::row($r);
                return array_intersect_key($x, array_flip(['name', 'trigger_type', 'trigger_value', 'conditions', 'actions', 'enabled', 'show_in_menu', 'priority']));
            }, $rules),
        ];
        if ($g) {
            $st = Repo::settings($g);
            $out['settings'] = $st;
        } else {
            $out['commands'] = json_decode((string)Settings::get('builtin_cmds'), true) ?: null;
            $out['keyboard'] = json_decode((string)Settings::get('bot_keyboard'), true) ?: null;
        }
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'settings', null, 'Xuất file cài đặt');
        return ['file' => $out, 'name' => 'cai-dat-' . ($g ? preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $g['title']) ?: 'nhom')) : 'chung') . '-' . date('Ymd') . '.json'];
    }

    /**
     * Nhập cài đặt từ file đã xuất (của nhóm này, nhóm khác hoặc cài đặt chung).
     * parts: settings (cài đặt nhóm) / features (tính năng) / rules (lệnh tự tạo). dry = true → chỉ xem trước.
     */
    private function settingsImport(): array
    {
        [$u, $g] = $this->featScope();
        $f = $this->in('file');
        if (is_string($f)) $f = json_decode($f, true);
        if (!is_array($f) || ($f['app'] ?? '') !== self::EXPORT_APP) fail('File không đúng định dạng cài đặt của hệ thống này');
        $parts = array_values(array_intersect((array)$this->in('parts', ['settings', 'features', 'rules']), ['settings', 'features', 'rules', 'commands']));
        $feats = array_intersect_key(is_array($f['features'] ?? null) ? $f['features'] : [], GroupFeature::FEATURES);
        $rules = is_array($f['rules'] ?? null) ? array_slice($f['rules'], 0, 300) : [];
        $preview = [
            'type' => $f['type'] ?? '?', 'source' => (string)($f['source'] ?? ''), 'version' => (string)($f['version'] ?? ''), 'exported_at' => (string)($f['exported_at'] ?? ''),
            'features' => array_map(fn($k) => ['key' => $k, 'name' => GroupFeature::FEATURES[$k][1], 'enabled' => !empty($feats[$k]['enabled'])], array_keys($feats)),
            'rules' => count($rules), 'has_settings' => $g && is_array($f['settings'] ?? null), 'has_commands' => !$g && is_array($f['commands'] ?? null),
        ];
        if ($this->in('dry')) return ['preview' => $preview];

        $done = []; $errors = [];
        if (in_array('settings', $parts, true) && $g && is_array($f['settings'] ?? null)) {
            Repo::saveSettings($g, $f['settings']);
            $done[] = 'Cài đặt nhóm';
        }
        if (in_array('features', $parts, true)) {
            foreach ($feats as $key => $cfg) {
                if (!is_array($cfg)) continue;
                $cls = GroupFeature::FEATURES[$key][0];
                unset($cfg['groups'], $cfg['_custom']);
                try { $cls::save($cfg, $g, $u['username']); $done[] = GroupFeature::FEATURES[$key][1]; }
                catch (Throwable $e) { $errors[] = GroupFeature::FEATURES[$key][1] . ': ' . $e->getMessage(); }
            }
        }
        if (in_array('rules', $parts, true) && $rules) {
            if ($g) Access::require($u, 'rules', (int)$g['id']);
            $mode = $this->in('rules_mode') === 'replace' ? 'replace' : 'add';
            $scope = $g ? 'group_id = ' . (int)$g['id'] : 'group_id IS NULL';
            if ($mode === 'replace') DB::run("DELETE FROM tg_rules WHERE $scope");
            $added = 0; $skipped = 0;
            foreach ($rules as $r) {
                if (!is_array($r)) continue;
                try {
                    $data = Rules::sanitize(['group_id' => $g ? (int)$g['id'] : null] + $r);
                    $data['group_id'] = $g ? (int)$g['id'] : null;
                    if ($data['trigger_type'] === 'command' && DB::val("SELECT id FROM tg_rules WHERE trigger_type = 'command' AND trigger_value = ? AND $scope", [$data['trigger_value']])) { $skipped++; continue; }
                    $data['created_at'] = now();
                    DB::insert('tg_rules', $data);
                    $added++;
                } catch (Throwable $e) { $errors[] = 'Lệnh “' . ($r['name'] ?? '?') . '”: ' . $e->getMessage(); }
            }
            $done[] = "$added lệnh tự tạo" . ($skipped ? " (bỏ qua $skipped lệnh trùng tên)" : '');
            Rules::syncMenus();
        }
        if (in_array('commands', $parts, true) && !$g) {
            if (is_array($f['commands'] ?? null)) { Settings::set('builtin_cmds', json_encode($f['commands'], JSON_UNESCAPED_UNICODE)); $done[] = 'Lệnh mặc định'; }
            if (is_array($f['keyboard'] ?? null)) { Settings::set('bot_keyboard', json_encode($f['keyboard'], JSON_UNESCAPED_UNICODE)); $done[] = 'Nút bàn phím bot'; }
            Rules::syncMenus();
        }
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'settings', null, 'Nhập cài đặt từ file “' . mb_substr($preview['source'], 0, 60) . '”: ' . implode(', ', $done));
        return ['done' => $done, 'errors' => array_slice($errors, 0, 20)];
    }

    // ================= Cấm tên chứa từ cấm =================
    private function nameGuard(): array
    {
        [, $g] = $this->featScope();
        $err = (string)Settings::get('name_guard_error');
        $since = now() - 7 * 86400;
        return [
            'config' => NameGuard::config($g), 'scope' => $this->featInfo('name_guard', $g), 'actions' => NameGuard::ACTIONS, 'suggest' => NameGuard::SUGGEST, 'error' => $err !== '' ? $err : null,
            'week' => (int)DB::val("SELECT COUNT(*) FROM app_logs WHERE created_at >= ? AND (action = 'name_ban' OR meta LIKE ?)" . ($g ? ' AND group_id = ' . (int)$g['id'] : ''), [$since, '%"name_guard":1%']),
            'groups' => $this->featGroups($g),
        ];
    }

    private function nameGuardSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = NameGuard::save((array)$this->in('config', []), $g, $u['username']);
        Settings::set('name_guard_error', '');
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Cấm tên chứa từ cấm: ' . ($c['enabled'] ? 'bật · ' . count($c['words']) . ' từ · ' . NameGuard::ACTIONS[$c['action']] : 'tắt'));
        return ['config' => $c];
    }

    /** Thử 1 tên với cấu hình đang sửa (chưa lưu) */
    private function nameGuardTest(): array
    {
        $this->featScope();
        $c = $this->nameGuardDraft();
        $name = trim(mb_substr((string)$this->in('name', ''), 0, 128));
        $uname = ltrim(trim(mb_substr((string)$this->in('username', ''), 0, 64)), '@');
        $gid = (int)$this->in('group_id', 0);
        return ['why' => NameGuard::match($c, ['id' => 0, 'first_name' => $name, 'username' => $uname], $gid ? ['id' => $gid] : null),
            'norm' => $c['smart'] ? NameGuard::norm($name, true) : null];
    }

    private function nameGuardDraft(): array
    {
        $gid = $this->int('group_id');
        $c = NameGuard::config($gid ? Repo::group($gid) : null);
        $d = (array)$this->in('config', []);
        foreach (['smart', 'check_name', 'check_username', 'impersonate'] as $k) if (array_key_exists($k, $d)) $c[$k] = !empty($d[$k]);
        if (isset($d['words'])) $c['words'] = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/u', (string)$d['words'])), 'strlen'));
        if (isset($d['allow_ids'])) { preg_match_all('/\d{3,20}/', (string)$d['allow_ids'], $mm); $c['allow_ids'] = $mm[0]; }
        if (isset($d['groups'])) $c['groups'] = array_map('intval', (array)$d['groups']);
        if ($gid) $c['groups'] = [$gid]; // chế độ nhóm: chỉ quét nhóm này
        return $c;
    }

    private function nameGuardScan(): array
    {
        $this->featScope();
        $c = $this->nameGuardDraft();
        if (!$c['words'] && !$c['impersonate']) fail('Chưa có từ cấm nào để quét');
        $gid = $this->int('group_id');
        return ['items' => NameGuard::scan($c, $gid ?: null), 'action' => NameGuard::ACTIONS[$c['action']]];
    }

    private function nameGuardPunish(): array
    {
        [$u, $g] = $this->featScope();
        $ids = (array)$this->in('ids', []);
        if ($g) $ids = array_column(DB::all('SELECT id FROM tg_members WHERE group_id = ? AND id IN (' . (implode(',', array_map('intval', $ids)) ?: '0') . ')', [$g['id']]), 'id'); // chỉ thành viên nhóm mình
        $r = NameGuard::punishMembers($ids, $this->nameGuardDraft());
        Repo::log(null, $u['username'], 'system', null, 'Cấm tên chứa từ cấm: xử lý ' . $r['ok'] . ' thành viên từ danh sách quét');
        return $r;
    }

    // ================= Đóng dấu ảnh chống copy =================
    private function watermark(): array
    {
        [, $g] = $this->featScope();
        $err = (string)Settings::get('watermark_error');
        return [
            'config' => Watermark::config($g), 'scope' => $this->featInfo('watermark', $g), 'defaults' => Watermark::DEFAULTS, 'styles' => Watermark::STYLES, 'ready' => Watermark::ready(),
            'count' => (int)Settings::get('watermark_count'), 'error' => $err !== '' ? $err : null,
            'groups' => $this->featGroups($g),
        ];
    }

    private function watermarkSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = Watermark::save((array)$this->in('config', []), $g, $u['username']);
        Settings::set('watermark_error', '');
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Đóng dấu ảnh: ' . ($c['enabled'] ? 'bật · ' . Watermark::STYLES[$c['style']] : 'tắt'));
        return ['config' => $c];
    }

    /** Xem trước: trộn cấu hình đang sửa (chưa lưu) + ảnh mẫu (tuỳ chọn, base64) */
    private function watermarkPreview(): array
    {
        [, $g0] = $this->featScope();
        $in = (array)$this->in('config', []);
        $c = Watermark::config($g0);
        foreach (['style', 'color'] as $k) if (isset($in[$k])) $c[$k] = (string)$in[$k];
        if (!isset(Watermark::STYLES[$c['style']])) $c['style'] = 'tile';
        foreach (['opacity' => [5, 100], 'size' => [2, 12]] as $k => [$a, $b]) if (isset($in[$k])) $c[$k] = max($a, min($b, (int)$in[$k]));
        if (isset($in['text']) && trim((string)$in['text']) !== '') $c['text'] = mb_substr((string)$in['text'], 0, 200);
        $bin = null;
        $img = (string)$this->in('image', '');
        if ($img !== '') {
            $bin = base64_decode(preg_replace('~^data:image/[a-z]+;base64,~', '', $img), true);
            if ($bin === false || strlen($bin) > 8 * 1024 * 1024) throw new HttpError('Ảnh mẫu không hợp lệ hoặc quá lớn (tối đa 8MB)');
        }
        $gid = (int)$this->in('group_id', 0);
        $g = $gid ? DB::get('SELECT title FROM tg_groups WHERE id = ?', [$gid]) : null;
        $g = $g ?: DB::get('SELECT title FROM tg_groups WHERE active = 1 ORDER BY id LIMIT 1') ?: ['title' => 'Nhóm của bạn'];
        return ['image' => Watermark::preview($c, ['id' => 0, 'title' => $g['title']], $bin)];
    }

    // ================= Captcha khi vào nhóm =================
    private function captcha(): array
    {
        [, $g] = $this->featScope();
        $since = now() - 7 * 86400;
        $gw = $g ? ' AND group_id = ' . (int)$g['id'] : '';
        $cnt = fn($a) => (int)DB::val('SELECT COUNT(*) FROM app_logs WHERE action = ? AND created_at >= ?' . $gw, [$a, $since]);
        return [
            'config' => Captcha::config($g), 'scope' => $this->featInfo('captcha', $g), 'defaults' => Captcha::DEFAULTS, 'modes' => Captcha::MODES, 'fail' => Captcha::FAIL,
            'groups' => $this->featGroups($g),
            'stats' => ['asked' => $cnt('captcha'), 'passed' => $cnt('captcha_ok'), 'failed' => $cnt('captcha_fail'), 'pending' => (int)DB::val('SELECT COUNT(*) FROM captcha_pending WHERE 1=1' . $gw)],
        ];
    }

    private function captchaSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = Captcha::save((array)$this->in('config', []), $g, $u['username']);
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Captcha: ' . ($c['enabled'] ? 'bật · ' . Captcha::MODES[$c['mode']] . ' · ' . $c['timeout'] . 's' : 'tắt'));
        return ['config' => $c];
    }

    // ================= Chống raid + duyệt yêu cầu tham gia =================
    private function antiRaid(): array
    {
        [, $sg] = $this->featScope();
        $groups = [];
        foreach ($sg ? [$sg] : DB::all('SELECT * FROM tg_groups WHERE active = 1 ORDER BY title') as $g) {
            $st = AntiRaid::state((int)$g['id']);
            $groups[] = ['id' => (int)$g['id'], 'title' => $g['title'], 'bot_admin' => (int)$g['bot_is_admin'], 'public' => (bool)$g['username'],
                'locked' => $st && (int)$st['until'] > now(), 'until' => $st ? (int)$st['until'] : null, 'why' => $st['why'] ?? null,
                'pending' => (int)DB::val("SELECT COUNT(*) FROM join_requests WHERE group_id = ? AND status = 'pending'", [$g['id']]),
                'joins_hour' => (int)DB::val('SELECT COUNT(*) FROM join_events WHERE group_id = ? AND created_at > ?', [$g['id'], now() - 3600])];
        }
        $reqs = DB::all("SELECT r.*, g.title FROM join_requests r JOIN tg_groups g ON g.id = r.group_id WHERE r.status = 'pending'" . ($sg ? ' AND r.group_id = ' . (int)$sg['id'] : '') . " ORDER BY r.id DESC LIMIT 200");
        return [
            'config' => AntiRaid::config($sg), 'scope' => $this->featInfo('antiraid', $sg), 'defaults' => AntiRaid::DEFAULTS, 'actions' => AntiRaid::ACTIONS, 'groups' => $groups,
            'requests' => array_map(fn($r) => ['id' => (int)$r['id'], 'group_id' => (int)$r['group_id'], 'group' => $r['title'], 'tg_user_id' => (string)$r['tg_user_id'],
                'name' => $r['name'], 'username' => $r['username'], 'bio' => $r['bio'], 'created_at' => (int)$r['created_at']], $reqs),
            'decided' => (int)DB::val("SELECT COUNT(*) FROM join_requests WHERE status = 'approved' AND decided_by = 'Bot (tự duyệt)' AND decided_at > ?", [now() - 86400]),
        ];
    }

    private function antiRaidSave(): array
    {
        [$u, $g] = $this->featScope();
        $c = AntiRaid::save((array)$this->in('config', []), $g, $u['username']);
        Repo::log($g ? (int)$g['id'] : null, $u['username'], 'system', null, 'Chống raid: ' . ($c['enabled'] ? "bật · {$c['threshold']} người / {$c['window']}s → khoá {$c['lock_minutes']} phút" : 'tắt'));
        return ['config' => $c];
    }

    /** Khoá / mở khoá tay (minutes = 0 → mở) */
    private function antiRaidLock(): array
    {
        $g = $this->loadGroup();
        $u = $this->gate('moderate', $g);
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot hoạt động');
        $min = $this->int('minutes');
        if ($min > 0) AntiRaid::lock($api, $g, min(1440, $min), 'Admin khoá tay', 0, $u['username']);
        else AntiRaid::unlock($api, $g, $u['username']);
        return ['locked' => AntiRaid::locked((int)$g['id'])];
    }

    private function antiRaidDecide(): array
    {
        $r = DB::get('SELECT * FROM join_requests WHERE id = ?', [$this->int('id')]) ?: fail('Không tìm thấy yêu cầu', 404);
        $g = Repo::group((int)$r['group_id']) ?: fail('Không tìm thấy nhóm', 404);
        $u = $this->gate('moderate', $g);
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot hoạt động');
        AntiRaid::decide($api, $g, (int)$r['id'], (bool)$this->in('approve'), $u['username']);
        return [];
    }

    private function antiRaidDecideAll(): array
    {
        $g = $this->loadGroup();
        $u = $this->gate('moderate', $g);
        $api = Repo::apiForGroup($g) ?: fail('Nhóm chưa có bot hoạt động');
        @set_time_limit(120);
        $ok = 0; $err = 0;
        foreach (DB::all("SELECT id FROM join_requests WHERE group_id = ? AND status = 'pending' ORDER BY id LIMIT 200", [$g['id']]) as $r) {
            try { if (AntiRaid::decide($api, $g, (int)$r['id'], (bool)$this->in('approve'), $u['username'])) $ok++; } catch (HttpError $e) { $err++; }
            usleep(60000);
        }
        return ['done' => $ok, 'failed' => $err];
    }
}

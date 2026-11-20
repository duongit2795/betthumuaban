<?php
defined('APP') or exit;

/** PDO wrapper hỗ trợ SQLite và MySQL/MariaDB. */
class DB
{
    private static ?PDO $pdo = null;
    private static string $driver = 'sqlite';
    const SCHEMA_VERSION = 21;

    public static function connect(?array $c = null): PDO
    {
        if (self::$pdo && $c === null) return self::$pdo;
        $c = $c ?? cfg('db');
        if (!$c) throw new RuntimeException('Chưa cấu hình database');
        self::$driver = $c['driver'];
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if ($c['driver'] === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int)($c['port'] ?: 3306), $c['name']);
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
            $pdo->exec("SET time_zone = '+00:00'");
        } else {
            $path = DATA_DIR . '/' . $c['file'];
            $pdo = new PDO('sqlite:' . $path, null, null, $opts);
            $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
        }
        return self::$pdo = $pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function q(string $sql, array $p = []): PDOStatement
    {
        $st = self::connect()->prepare($sql);
        $st->execute(array_values($p));
        return $st;
    }

    public static function get(string $sql, array $p = []): ?array
    {
        $r = self::q($sql, $p)->fetch();
        return $r === false ? null : $r;
    }

    public static function all(string $sql, array $p = []): array
    {
        return self::q($sql, $p)->fetchAll();
    }

    public static function val(string $sql, array $p = [])
    {
        $r = self::q($sql, $p)->fetchColumn();
        return $r === false ? null : $r;
    }

    public static function run(string $sql, array $p = []): int
    {
        return self::q($sql, $p)->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        self::q($sql, array_values($data));
        return (int)self::connect()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $wp = []): int
    {
        $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));
        return self::run("UPDATE $table SET $set WHERE $where", array_merge(array_values($data), $wp));
    }

    public static function tx(callable $fn)
    {
        $pdo = self::connect();
        $pdo->beginTransaction();
        try {
            $r = $fn();
            $pdo->commit();
            return $r;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Tạo/nâng cấp bảng. An toàn khi chạy nhiều lần. */
    public static function migrate(): void
    {
        $my = self::$driver === 'mysql';
        $pk = $my ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $big = $my ? 'BIGINT' : 'INTEGER';
        $str = $my ? 'VARCHAR(191)' : 'TEXT';
        $txt = $my ? 'MEDIUMTEXT' : 'TEXT';
        $suffix = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

        $tables = [
            "CREATE TABLE IF NOT EXISTS app_settings (
                k VARCHAR(64) NOT NULL PRIMARY KEY,
                v $txt
            )",
            "CREATE TABLE IF NOT EXISTS app_users (
                id $pk,
                username $str NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name $str,
                role VARCHAR(16) NOT NULL DEFAULT 'member',
                tg_user_id $big,
                link_code VARCHAR(32),
                disabled INT NOT NULL DEFAULT 0,
                created_at INT NOT NULL,
                last_login INT
            )",
            "CREATE TABLE IF NOT EXISTS tg_bots (
                id $pk,
                token VARCHAR(191) NOT NULL UNIQUE,
                tg_bot_id $big,
                username $str,
                name $str,
                enabled INT NOT NULL DEFAULT 1,
                status VARCHAR(16) NOT NULL DEFAULT 'stopped',
                last_error TEXT,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_groups (
                id $pk,
                chat_id $big NOT NULL UNIQUE,
                bot_id INT,
                title $str,
                type VARCHAR(16),
                username $str,
                member_count INT DEFAULT 0,
                bot_is_admin INT DEFAULT 0,
                active INT NOT NULL DEFAULT 1,
                settings $txt,
                admins_json TEXT,
                admins_at INT DEFAULT 0,
                created_at INT NOT NULL,
                updated_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_members (
                id $pk,
                group_id INT NOT NULL,
                tg_user_id $big NOT NULL,
                username $str,
                first_name $str,
                last_name $str,
                is_bot INT DEFAULT 0,
                is_admin INT DEFAULT 0,
                status VARCHAR(16) NOT NULL DEFAULT 'member',
                messages INT NOT NULL DEFAULT 0,
                points INT NOT NULL DEFAULT 0,
                warns INT NOT NULL DEFAULT 0,
                muted_until INT,
                note TEXT,
                last_point_at INT DEFAULT 0,
                flood_start INT DEFAULT 0,
                flood_count INT DEFAULT 0,
                joined_at INT,
                last_active INT,
                UNIQUE (group_id, tg_user_id)
            )",
            "CREATE TABLE IF NOT EXISTS app_logs (
                id $pk,
                group_id INT,
                actor $str,
                action VARCHAR(32) NOT NULL,
                target_tg_id $big,
                target_name $str,
                detail TEXT,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_rules (
                id $pk,
                group_id INT,
                name $str NOT NULL,
                trigger_type VARCHAR(16) NOT NULL,
                trigger_value TEXT NOT NULL,
                conditions TEXT,
                actions TEXT,
                enabled INT NOT NULL DEFAULT 1,
                show_in_menu INT NOT NULL DEFAULT 0,
                priority INT NOT NULL DEFAULT 0,
                uses INT NOT NULL DEFAULT 0,
                last_used INT,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_rule_hits (
                rule_id INT NOT NULL,
                group_id INT NOT NULL,
                tg_user_id $big NOT NULL,
                last_at INT NOT NULL,
                PRIMARY KEY (rule_id, group_id, tg_user_id)
            )",
            "CREATE TABLE IF NOT EXISTS tg_delete_queue (
                id $pk,
                bot_id INT NOT NULL,
                chat_id $big NOT NULL,
                message_id $big NOT NULL,
                delete_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_messages (
                id $pk,
                group_id INT NOT NULL,
                message_id $big NOT NULL,
                tg_user_id $big,
                name $str,
                username $str,
                is_bot INT NOT NULL DEFAULT 0,
                kind VARCHAR(16) NOT NULL DEFAULT 'text',
                media $str,
                text TEXT,
                reply_to $big,
                reply_name $str,
                reply_text TEXT,
                deleted INT NOT NULL DEFAULT 0,
                note $str,
                edited INT NOT NULL DEFAULT 0,
                pinned INT NOT NULL DEFAULT 0,
                created_at INT NOT NULL,
                updated_at INT NOT NULL,
                UNIQUE (group_id, message_id)
            )",
            "CREATE TABLE IF NOT EXISTS tg_login_codes (
                id $pk,
                code VARCHAR(16) NOT NULL UNIQUE,
                sess VARCHAR(64) NOT NULL,
                status VARCHAR(16) NOT NULL DEFAULT 'pending',
                tg_user_id $big,
                tg_name $str,
                tg_username $str,
                ip VARCHAR(64),
                ua VARCHAR(191),
                created_at INT NOT NULL,
                expires_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_ad_campaigns (
                id $pk,
                name $str NOT NULL,
                enabled INT NOT NULL DEFAULT 1,
                group_ids TEXT,
                bot_mode VARCHAR(16) NOT NULL DEFAULT 'default',
                bot_token VARCHAR(191),
                bot_username $str,
                interval_sec INT NOT NULL DEFAULT 10,
                rotate_order VARCHAR(16) NOT NULL DEFAULT 'sequence',
                daily_new INT NOT NULL DEFAULT 1,
                daily_time VARCHAR(5) NOT NULL DEFAULT '00:00',
                pin INT NOT NULL DEFAULT 1,
                pin_silent INT NOT NULL DEFAULT 1,
                delete_old INT NOT NULL DEFAULT 0,
                last_error TEXT,
                created_at INT NOT NULL,
                updated_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_ads (
                id $pk,
                campaign_id INT NOT NULL,
                title $str,
                image VARCHAR(191),
                caption TEXT,
                buttons TEXT,
                enabled INT NOT NULL DEFAULT 1,
                sort INT NOT NULL DEFAULT 0,
                file_ids TEXT,
                shows INT NOT NULL DEFAULT 0,
                created_at INT NOT NULL,
                updated_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_ad_posts (
                id $pk,
                campaign_id INT NOT NULL,
                group_id INT NOT NULL,
                chat_id $big NOT NULL,
                message_id $big NOT NULL,
                bot_key VARCHAR(32) NOT NULL,
                ad_id INT,
                active INT NOT NULL DEFAULT 1,
                last_rotate_at INT NOT NULL DEFAULT 0,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS daily_stats (
                group_id INT NOT NULL,
                day VARCHAR(10) NOT NULL,
                messages INT NOT NULL DEFAULT 0,
                joins INT NOT NULL DEFAULT 0,
                leaves INT NOT NULL DEFAULT 0,
                PRIMARY KEY (group_id, day)
            )",
            "CREATE TABLE IF NOT EXISTS wallet_tx (
                id $pk,
                user_id INT NOT NULL,
                amount $big NOT NULL,
                balance_after $big NOT NULL DEFAULT 0,
                type VARCHAR(16) NOT NULL,
                ref VARCHAR(64),
                note TEXT,
                meta TEXT,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS learn_questions (
                id $pk,
                sort INT NOT NULL DEFAULT 0,
                question TEXT NOT NULL,
                answers TEXT,
                match_mode VARCHAR(16) NOT NULL DEFAULT 'exact',
                hint TEXT,
                enabled INT NOT NULL DEFAULT 1,
                created_at INT NOT NULL,
                updated_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS learn_progress (
                tg_user_id $big NOT NULL PRIMARY KEY,
                status VARCHAR(16) NOT NULL DEFAULT 'required',
                step INT NOT NULL DEFAULT 0,
                wrong INT NOT NULL DEFAULT 0,
                groups_json TEXT,
                first_name $str,
                last_name $str,
                username $str,
                started_at INT,
                done_at INT,
                updated_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tg_reaction_hits (
                id $pk,
                rule_id INT NOT NULL,
                chat_id $big NOT NULL,
                message_id $big NOT NULL,
                tg_user_id $big NOT NULL,
                created_at INT NOT NULL,
                UNIQUE (rule_id, chat_id, message_id, tg_user_id)
            )",
            "CREATE TABLE IF NOT EXISTS checkins (
                id $pk,
                tg_user_id $big NOT NULL,
                group_id INT NOT NULL,
                day VARCHAR(10) NOT NULL,
                points INT NOT NULL DEFAULT 0,
                streak INT NOT NULL DEFAULT 1,
                created_at INT NOT NULL,
                UNIQUE (tg_user_id, group_id, day)
            )",
            "CREATE TABLE IF NOT EXISTS gift_codes (
                id $pk,
                code VARCHAR(32) NOT NULL UNIQUE,
                type VARCHAR(16) NOT NULL,
                amount INT NOT NULL DEFAULT 0,
                group_id INT,
                max_uses INT NOT NULL DEFAULT 1,
                uses INT NOT NULL DEFAULT 0,
                expires_at INT,
                enabled INT NOT NULL DEFAULT 1,
                note $str,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS gift_redemptions (
                id $pk,
                code_id INT NOT NULL,
                who VARCHAR(32) NOT NULL,
                user_id INT,
                tg_user_id $big,
                created_at INT NOT NULL,
                UNIQUE (code_id, who)
            )",
            "CREATE TABLE IF NOT EXISTS vip_members (
                tg_user_id $big NOT NULL PRIMARY KEY,
                until INT NOT NULL,
                notified INT NOT NULL DEFAULT 0,
                created_at INT NOT NULL,
                updated_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS captcha_pending (
                id $pk,
                group_id INT NOT NULL,
                tg_user_id $big NOT NULL,
                answer VARCHAR(32) NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                message_id $big,
                expires_at INT NOT NULL,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS join_events (
                id $pk,
                group_id INT NOT NULL,
                tg_user_id $big NOT NULL,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS plans (
                id $pk,
                name $str NOT NULL,
                description TEXT,
                price INT NOT NULL DEFAULT 0,
                days INT NOT NULL DEFAULT 30,
                max_groups INT NOT NULL DEFAULT 1,
                max_bots INT NOT NULL DEFAULT 1,
                features TEXT,
                enabled INT NOT NULL DEFAULT 1,
                sort INT NOT NULL DEFAULT 0,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS tenant_orders (
                id $pk,
                user_id INT NOT NULL,
                plan_id INT,
                plan_name $str,
                days INT NOT NULL,
                amount INT NOT NULL DEFAULT 0,
                kind VARCHAR(16) NOT NULL,
                until_after INT,
                by_user $str,
                note $str,
                created_at INT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS group_features (
                id $pk,
                group_id INT NOT NULL,
                feature VARCHAR(32) NOT NULL,
                config TEXT NOT NULL,
                updated_at INT NOT NULL,
                updated_by $str
            )",
            "CREATE TABLE IF NOT EXISTS join_requests (
                id $pk,
                group_id INT NOT NULL,
                tg_user_id $big NOT NULL,
                name $str,
                username $str,
                bio TEXT,
                status VARCHAR(16) NOT NULL DEFAULT 'pending',
                created_at INT NOT NULL,
                decided_at INT,
                decided_by $str
            )",
            "CREATE TABLE IF NOT EXISTS bank_tx (
                id $pk,
                txid VARCHAR(64) NOT NULL UNIQUE,
                amount $big NOT NULL,
                description TEXT,
                tx_date VARCHAR(40),
                user_id INT,
                status VARCHAR(16) NOT NULL,
                created_at INT NOT NULL
            )",
        ];
        $pdo = self::connect();
        foreach ($tables as $sql) $pdo->exec($sql . $suffix);

        $indexes = [
            'idx_members_points' => 'tg_members (group_id, points)',
            'idx_members_tg' => 'tg_members (tg_user_id)',
            'idx_logs_group' => 'app_logs (group_id, created_at)',
            'idx_logs_time' => 'app_logs (created_at)',
            'idx_delq_time' => 'tg_delete_queue (delete_at)',
            'idx_msg_updated' => 'tg_messages (group_id, updated_at)',
            'idx_wallet_user' => 'wallet_tx (user_id, id)',
            'idx_bank_status' => 'bank_tx (status, id)',
            'idx_learn_status' => 'learn_progress (status, updated_at)',
            'idx_checkin_day' => 'checkins (group_id, day)',
            'idx_captcha_exp' => 'captcha_pending (expires_at)',
            'idx_join_events' => 'join_events (group_id, created_at)',
            'idx_join_req' => 'join_requests (group_id, status)',
            'idx_group_feat' => 'group_features (group_id, feature)',
            'idx_tenant_orders' => 'tenant_orders (user_id, created_at)',
        ];
        foreach ($indexes as $name => $def) {
            try {
                $pdo->exec($my ? "CREATE INDEX $name ON $def" : "CREATE INDEX IF NOT EXISTS $name ON $def");
            } catch (PDOException $e) { /* đã tồn tại */ }
        }

        // v21: cho thuê (khách thuê có bot riêng, gói thuê)
        self::addColumn('tg_bots', 'owner_id', 'INT');
        self::addColumn('app_users', 'plan_id', 'INT');
        self::addColumn('app_users', 'plan_until', 'INT');
        self::addColumn('app_users', 'tenant', 'INT NOT NULL DEFAULT 0');
        // v6: nhật ký lưu chi tiết + bằng chứng vi phạm
        self::addColumn('app_logs', 'meta', 'TEXT');
        self::addColumn('app_logs', 'evidence', 'TEXT');
        // v18: captcha khi vào nhóm, điều kiện dùng mã quà tặng
        self::addColumn('tg_members', 'captcha_ok', 'INT NOT NULL DEFAULT 0');
        self::addColumn('gift_codes', 'conditions', 'TEXT');
        // v17: đổi điểm (điểm đã đổi → điểm mở = tổng − đã đổi)
        self::addColumn('tg_members', 'points_spent', 'INT NOT NULL DEFAULT 0');
        // v16: mua tag bằng số dư ví (hạn dùng)
        self::addColumn('tg_members', 'tag_until', 'INT');
        // v15: tag thành viên
        self::addColumn('tg_members', 'tag', $my ? 'VARCHAR(64)' : 'TEXT');
        self::addColumn('tg_members', 'tag_source', 'VARCHAR(8)');
        self::addColumn('tg_members', 'tag_try_at', 'INT');
        // v12: xem ảnh / video / tệp trong tab Chat
        self::addColumn('tg_messages', 'file_id', 'TEXT');
        self::addColumn('tg_messages', 'thumb_id', 'TEXT');
        self::addColumn('tg_messages', 'file_name', $my ? 'VARCHAR(191)' : 'TEXT');
        self::addColumn('tg_messages', 'file_size', 'INT');
        self::addColumn('tg_messages', 'mime', 'VARCHAR(64)');
        // v9: quảng cáo gửi theo lịch
        self::addColumn('tg_ad_campaigns', 'type', "VARCHAR(16) NOT NULL DEFAULT 'rotate'");
        self::addColumn('tg_ad_campaigns', 'sched_mode', "VARCHAR(16) NOT NULL DEFAULT 'interval'");
        self::addColumn('tg_ad_campaigns', 'every_min', 'INT NOT NULL DEFAULT 60');
        self::addColumn('tg_ad_campaigns', 'sched_times', 'TEXT');
        self::addColumn('tg_ad_campaigns', 'sched_days', "VARCHAR(16) NOT NULL DEFAULT '1234567'");
        self::addColumn('tg_ad_campaigns', 'win_from', "VARCHAR(5) NOT NULL DEFAULT ''");
        self::addColumn('tg_ad_campaigns', 'win_to', "VARCHAR(5) NOT NULL DEFAULT ''");
        self::addColumn('tg_ad_campaigns', 'silent', 'INT NOT NULL DEFAULT 1');
        self::addColumn('tg_ad_campaigns', 'auto_delete_min', 'INT NOT NULL DEFAULT 0');
        // v8: ví VND
        self::addColumn('app_users', 'balance', ($my ? 'BIGINT' : 'INTEGER') . ' NOT NULL DEFAULT 0');
        // Các migration theo phiên bản schema đặt ở đây trong tương lai
        $v = (int)(Settings::get('schema_version') ?? 0);
        // v13: nhật ký quảng cáo mặc định tắt → dọn các dòng cũ
        if ($v > 0 && $v < 13) $pdo->exec("DELETE FROM app_logs WHERE action = 'ad_post'");
        if ($v < self::SCHEMA_VERSION) Settings::set('schema_version', (string)self::SCHEMA_VERSION);
    }

    /** Thêm cột nếu chưa có (dùng cho migration sau này). */
    public static function addColumn(string $table, string $col, string $def): void
    {
        try {
            self::connect()->exec("ALTER TABLE $table ADD COLUMN $col $def");
        } catch (PDOException $e) { /* đã có */ }
    }
}

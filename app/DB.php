<?php
defined('APP') or exit;

/** PDO wrapper hỗ trợ SQLite và MySQL/MariaDB. */
class DB
{
    private static ?PDO $pdo = null;
    private static string $driver = 'sqlite';
    const SCHEMA_VERSION = 2;

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
            "CREATE TABLE IF NOT EXISTS daily_stats (
                group_id INT NOT NULL,
                day VARCHAR(10) NOT NULL,
                messages INT NOT NULL DEFAULT 0,
                joins INT NOT NULL DEFAULT 0,
                leaves INT NOT NULL DEFAULT 0,
                PRIMARY KEY (group_id, day)
            )",
        ];
        $pdo = self::connect();
        foreach ($tables as $sql) $pdo->exec($sql . $suffix);

        $indexes = [
            'idx_members_points' => 'tg_members (group_id, points)',
            'idx_members_tg' => 'tg_members (tg_user_id)',
            'idx_logs_group' => 'app_logs (group_id, created_at)',
            'idx_logs_time' => 'app_logs (created_at)',
        ];
        foreach ($indexes as $name => $def) {
            try {
                $pdo->exec($my ? "CREATE INDEX $name ON $def" : "CREATE INDEX IF NOT EXISTS $name ON $def");
            } catch (PDOException $e) { /* đã tồn tại */ }
        }

        // Các migration theo phiên bản schema đặt ở đây trong tương lai
        $v = (int)(Settings::get('schema_version') ?? 0);
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

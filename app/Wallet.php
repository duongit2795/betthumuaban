<?php
defined('APP') or exit;

/**
 * Ví VND của tài khoản web + nạp tiền tự động qua lịch sử giao dịch ngân hàng (API web2m).
 * Link API chứa thông tin đăng nhập ngân hàng: chỉ lưu trong database (Settings 'pay_api_url'),
 * không bao giờ trả về giao diện.
 */
class Wallet
{
    const TYPES = [
        'deposit' => 'Nạp tiền',
        'amnesty' => 'Ân xá',
        'amnesty_fail' => 'Ân xá lỗi (đã hoàn)',
        'tag' => 'Mua tag',
        'tag_fail' => 'Mua tag lỗi (đã hoàn)',
        'exchange' => 'Đổi điểm',
        'buy_points' => 'Mua điểm',
        'gift' => 'Mã quà tặng',
        'vip' => 'Mua VIP',
        'plan' => 'Thuê gói',
        'escrow' => 'Giao dịch trung gian',
        'refund' => 'Hoàn tiền',
        'admin' => 'Admin điều chỉnh',
    ];
    const BANKS = ['ACB' => 'ACB', 'VCB' => 'Vietcombank', 'TCB' => 'Techcombank', 'MB' => 'MB Bank', 'VPB' => 'VPBank', 'TPB' => 'TPBank', 'BIDV' => 'BIDV', 'ICB' => 'VietinBank'];

    // ================= Cấu hình (theo không gian: 0 = chủ web, N = khách thuê) =================
    public static function config(int $sp = 0): array
    {
        $g = fn($k, $d = null) => Space::get($k, $sp, $d);
        return [
            'enabled' => $g('pay_enabled') === '1' && Space::allows($sp, 'wallet'),
            'enabled_raw' => $g('pay_enabled') === '1',
            'bank' => $g('pay_bank') ?: 'ACB',
            'account_no' => (string)$g('pay_account_no', ''),
            'account_name' => (string)$g('pay_account_name', ''),
            'prefix' => strtoupper($g('pay_prefix') ?: ($sp ? 'NAP' . $sp . 'U' : 'NAPTIEN')),
            'min' => (int)($g('pay_min') ?: 10000),
            'has_api' => (bool)$g('pay_api_url'),
            'last_sync' => (int)$g('pay_last_sync') ?: null,
            'last_error' => $g('pay_last_error') ?: null,
            'shop_name' => $sp ? (string)$g('shop_name', '') : '',
        ];
    }

    public static function saveConfig(array $in, int $sp = 0): array
    {
        $set = fn($k, $v) => Space::set($k, $sp, $v);
        if (array_key_exists('enabled', $in)) $set('pay_enabled', !empty($in['enabled']) ? '1' : '0');
        if (isset($in['bank']) && isset(self::BANKS[$in['bank']])) $set('pay_bank', $in['bank']);
        if (isset($in['account_no'])) $set('pay_account_no', preg_replace('/\D/', '', (string)$in['account_no']));
        if (isset($in['account_name'])) $set('pay_account_name', mb_strtoupper(trim(mb_substr((string)$in['account_name'], 0, 80))));
        if ($sp && isset($in['shop_name'])) $set('shop_name', trim(mb_substr((string)$in['shop_name'], 0, 60)));
        if (isset($in['prefix'])) {
            $p = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$in['prefix']));
            if (strlen($p) < 2 || strlen($p) > 12 || !preg_match('/[A-Z]$/', $p)) throw new HttpError('Tiền tố nội dung chuyển khoản: 2–12 chữ cái / số không dấu, kết thúc bằng chữ cái');
            $set('pay_prefix', $p);
        }
        if (isset($in['min'])) $set('pay_min', (string)max(1000, (int)$in['min']));
        $url = trim((string)($in['api_url'] ?? ''));
        if ($url !== '' && $url !== '••••••') {
            if (!preg_match('~^https://\S+$~', $url)) throw new HttpError('Link API phải bắt đầu bằng https://');
            $set('pay_api_url', $url);
        }
        if (!empty($in['clear_api'])) $set('pay_api_url', '');
        return self::config($sp);
    }

    /** Nội dung chuyển khoản của tài khoản, VD NAPTIEN12 */
    public static function code(array $u, int $sp = 0): string
    {
        return self::config($sp)['prefix'] . (int)$u['id'];
    }

    public static function qrUrl(array $u, int $amount = 0, int $sp = 0): ?string
    {
        $c = self::config($sp);
        if (!$c['account_no']) return null;
        $q = http_build_query(array_filter(['amount' => $amount ?: null, 'addInfo' => self::code($u, $sp), 'accountName' => $c['account_name'] ?: null]));
        return 'https://img.vietqr.io/image/' . rawurlencode($c['bank']) . '-' . rawurlencode($c['account_no']) . '-compact2.png?' . $q;
    }

    // ================= Số dư (không gian 0: app_users.balance; khách thuê: space_wallets) =================
    public static function balance(int $userId, int $sp = 0): int
    {
        if ($sp === 0) return (int)DB::val('SELECT balance FROM app_users WHERE id = ?', [$userId]);
        return (int)DB::val('SELECT balance FROM space_wallets WHERE space_id = ? AND user_id = ?', [$sp, $userId]);
    }

    private static function tx(int $userId, int $amount, int $bal, string $type, string $note, ?string $ref, array $meta, int $sp): void
    {
        DB::insert('wallet_tx', ['user_id' => $userId, 'amount' => $amount, 'balance_after' => $bal, 'type' => $type, 'ref' => $ref, 'space_id' => $sp,
            'note' => mb_substr($note, 0, 250), 'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null, 'created_at' => now()]);
    }

    /** Cộng tiền (số dương) */
    public static function credit(int $userId, int $amount, string $type, string $note, ?string $ref = null, array $meta = [], int $sp = 0): int
    {
        if ($amount <= 0) throw new HttpError('Số tiền không hợp lệ');
        return DB::tx(function () use ($userId, $amount, $type, $note, $ref, $meta, $sp) {
            if ($sp === 0) DB::run('UPDATE app_users SET balance = balance + ? WHERE id = ?', [$amount, $userId]);
            elseif (!DB::run('UPDATE space_wallets SET balance = balance + ?, updated_at = ? WHERE space_id = ? AND user_id = ?', [$amount, now(), $sp, $userId])) {
                DB::insert('space_wallets', ['space_id' => $sp, 'user_id' => $userId, 'balance' => $amount, 'updated_at' => now()]);
            }
            $bal = self::balance($userId, $sp);
            self::tx($userId, $amount, $bal, $type, $note, $ref, $meta, $sp);
            return $bal;
        });
    }

    /** Trừ tiền nếu đủ số dư (không bao giờ âm). Trả về số dư mới. */
    public static function debit(int $userId, int $amount, string $type, string $note, array $meta = [], int $sp = 0): int
    {
        if ($amount <= 0) throw new HttpError('Số tiền không hợp lệ');
        return DB::tx(function () use ($userId, $amount, $type, $note, $meta, $sp) {
            $ok = $sp === 0
                ? DB::run('UPDATE app_users SET balance = balance - ? WHERE id = ? AND balance >= ?', [$amount, $userId, $amount])
                : DB::run('UPDATE space_wallets SET balance = balance - ?, updated_at = ? WHERE space_id = ? AND user_id = ? AND balance >= ?', [$amount, now(), $sp, $userId, $amount]);
            if (!$ok) {
                throw new HttpError('Số dư không đủ. Cần ' . self::money($amount) . ', bạn có ' . self::money(self::balance($userId, $sp)) . ($sp ? ' (ví ' . Space::title($sp) . ')' : '') . '. Hãy nạp thêm tiền.', 402);
            }
            $bal = self::balance($userId, $sp);
            self::tx($userId, -$amount, $bal, $type, $note, null, $meta, $sp);
            return $bal;
        });
    }

    public static function money(int $v): string
    {
        return number_format($v, 0, ',', '.') . 'đ';
    }

    // ================= Đồng bộ giao dịch ngân hàng =================
    /** Gọi API, trả về danh sách giao dịch đã chuẩn hoá */
    /** Dùng cho kiểm thử: thay bước gọi API ngân hàng (nhận $sp, trả dữ liệu thô) */
    public static $fetcher = null;

    public static function fetch(int $sp = 0): array
    {
        if (self::$fetcher) return self::normalize((self::$fetcher)($sp));
        $url = (string)Space::get('pay_api_url', $sp, '');
        if ($url === '') throw new HttpError('Chưa nhập link API lịch sử giao dịch');
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new HttpError('Không gọi được API ngân hàng: ' . $err);
        $data = json_decode((string)$body, true);
        if (!is_array($data)) throw new HttpError("API trả về dữ liệu không phải JSON (HTTP $code)");
        if (isset($data['status']) && ($data['status'] === false || $data['status'] === 'false') && empty($data['transactions'])) {
            throw new HttpError('API báo lỗi: ' . ($data['message'] ?? $data['msg'] ?? 'không rõ'));
        }
        return self::normalize($data);
    }

    /** Đọc linh hoạt nhiều định dạng (transactions / data / history …) */
    public static function normalize(array $data): array
    {
        $list = null;
        foreach (['transactions', 'data', 'history', 'result', 'records', 'items'] as $k) {
            if (isset($data[$k]) && is_array($data[$k])) {
                $list = $data[$k];
                if (isset($list['transactions']) && is_array($list['transactions'])) $list = $list['transactions'];
                break;
            }
        }
        if ($list === null && array_is_list($data)) $list = $data;
        $out = [];
        foreach ((array)$list as $t) {
            if (!is_array($t)) continue;
            $pick = function (array $keys) use ($t) {
                foreach ($keys as $k) if (isset($t[$k]) && $t[$k] !== '') return $t[$k];
                return null;
            };
            $rawAmount = $pick(['amount', 'creditAmount', 'credit', 'Amount', 'so_tien', 'transactionAmount']);
            $amount = (int)round((float)preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string)$rawAmount)));
            $type = strtoupper((string)$pick(['type', 'transactionType', 'Type', 'cd']));
            $isIn = $type !== '' ? in_array($type, ['IN', 'CREDIT', 'C', '+', 'CR'], true) : $amount > 0;
            $desc = (string)$pick(['description', 'content', 'remark', 'Description', 'noi_dung', 'addDescription', 'transactionContent']);
            $id = (string)$pick(['transactionID', 'transactionId', 'transactionNumber', 'id', 'refNo', 'reference', 'tid', 'TransactionID']);
            $date = (string)$pick(['transactionDate', 'postingDate', 'date', 'when', 'activeDatetime', 'createdAt']);
            if ($id === '') $id = 'h' . substr(md5($date . '|' . $amount . '|' . $desc), 0, 20); // API không có mã giao dịch
            $out[] = ['id' => mb_substr($id, 0, 64), 'amount' => abs($amount), 'in' => $isIn && $amount != 0, 'description' => mb_substr($desc, 0, 500), 'date' => mb_substr($date, 0, 40)];
        }
        return $out;
    }

    /** Tìm tài khoản theo nội dung chuyển khoản: PREFIX + ID (bỏ dấu cách, không phân biệt hoa thường) */
    public static function matchUser(string $desc, int $sp = 0): ?array
    {
        $prefix = self::config($sp)['prefix'];
        $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $desc));
        if (!preg_match('/' . preg_quote($prefix, '/') . '(\d{1,9})(?!\d)/', $norm, $m)) return null;
        return DB::get('SELECT * FROM app_users WHERE id = ?', [(int)$m[1]]);
    }

    /**
     * Đồng bộ: giao dịch tiền vào mới → cộng tiền đúng tài khoản. Chống trùng bằng mã giao dịch.
     * @return array{new:int, credited:int, unmatched:int}
     */
    public static function sync(bool $force = false, int $sp = 0): array
    {
        $c = self::config($sp);
        $res = ['new' => 0, 'credited' => 0, 'unmatched' => 0];
        if (!$c['enabled'] || !$c['has_api']) return $res;
        if (!$force && now() - (int)$c['last_sync'] < 15) return $res; // tối đa 4 lần/phút
        Space::set('pay_last_sync', $sp, (string)now());
        try {
            $list = self::fetch($sp);
            Space::set('pay_last_error', $sp, '');
        } catch (Throwable $e) {
            Space::set('pay_last_error', $sp, mb_substr($e->getMessage(), 0, 300));
            return $res;
        }
        foreach ($list as $t) {
            if (!$t['in'] || $t['amount'] <= 0) continue;
            $txid = $sp ? mb_substr("s$sp-" . $t['id'], 0, 64) : $t['id']; // mỗi khách 1 tài khoản ngân hàng: tránh trùng mã giữa các khách
            if (DB::val('SELECT id FROM bank_tx WHERE txid = ?', [$txid])) continue;
            $res['new']++;
            // Tiền giao dịch trung gian (nội dung GDTG + mã) → ghi vào giao dịch, không cộng ví
            if ($sp === 0 && class_exists('Escrow') && preg_match('/' . preg_quote(Escrow::config()['prefix'], '/') . '\d/', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $t['description'])))) {
                try {
                    DB::insert('bank_tx', ['txid' => $txid, 'amount' => $t['amount'], 'description' => $t['description'], 'tx_date' => $t['date'], 'space_id' => 0, 'user_id' => null, 'status' => 'escrow', 'created_at' => now()]);
                } catch (PDOException $e) { continue; }
                if (Escrow::matchPayment(['id' => $txid] + $t)) continue;
                DB::run("UPDATE bank_tx SET status = 'unmatched' WHERE txid = ?", [$txid]); // mã không tồn tại → để admin xử lý
                $res['unmatched']++;
                continue;
            }
            $u = self::matchUser($t['description'], $sp);
            $ok = $u && !$u['disabled'] && $t['amount'] >= $c['min'];
            try {
                DB::insert('bank_tx', ['txid' => $txid, 'amount' => $t['amount'], 'description' => $t['description'], 'tx_date' => $t['date'], 'space_id' => $sp,
                    'user_id' => $ok ? $u['id'] : null, 'status' => $ok ? 'credited' : ($u ? 'below_min' : 'unmatched'), 'created_at' => now()]);
            } catch (PDOException $e) {
                continue; // tiến trình khác vừa xử lý giao dịch này
            }
            if ($ok) {
                self::credit((int)$u['id'], $t['amount'], 'deposit', 'Nạp tiền qua ' . $c['bank'], $txid, ['description' => $t['description'], 'date' => $t['date']], $sp);
                $res['credited']++;
                self::notifyDeposit($u, $t['amount'], $sp);
            } else {
                $res['unmatched']++;
            }
        }
        return $res;
    }

    /** Đồng bộ mọi không gian có link API (cron) */
    public static function syncAll(): void
    {
        self::sync();
        foreach (DB::all("SELECT k FROM app_settings WHERE k LIKE 'pay_api_url@%' AND v <> ''") as $r) {
            $sp = (int)substr($r['k'], strlen('pay_api_url@'));
            if ($sp > 0) { try { self::sync(false, $sp); } catch (Throwable $e) {} }
        }
    }

    /** Gán tay giao dịch không khớp cho 1 tài khoản (admin) */
    public static function assign(int $bankTxId, int $userId, string $by): void
    {
        $t = DB::get('SELECT * FROM bank_tx WHERE id = ?', [$bankTxId]) ?: throw new HttpError('Không tìm thấy giao dịch', 404);
        if ($t['status'] === 'credited') throw new HttpError('Giao dịch này đã được cộng tiền');
        $u = DB::get('SELECT * FROM app_users WHERE id = ?', [$userId]) ?: throw new HttpError('Không tìm thấy tài khoản', 404);
        if (!DB::run("UPDATE bank_tx SET status = 'credited', user_id = ? WHERE id = ? AND status <> 'credited'", [$userId, $bankTxId])) throw new HttpError('Giao dịch đã được xử lý');
        $sp = (int)($t['space_id'] ?? 0);
        self::credit($userId, (int)$t['amount'], 'deposit', "Nạp tiền (admin $by gán tay)", $t['txid'], ['description' => $t['description']], $sp);
        self::notifyDeposit($u, (int)$t['amount'], $sp);
    }

    /** Báo nạp tiền thành công qua Telegram (nếu tài khoản đã liên kết) */
    private static function notifyDeposit(array $u, int $amount, int $sp = 0): void
    {
        if (empty($u['tg_user_id'])) return;
        $api = Space::api($sp);
        if (!$api) return;
        $api->trySend($u['tg_user_id'], "💰 <b>Nạp tiền thành công</b>" . ($sp ? ' · ' . tg_esc(Space::title($sp)) : '') . "\n➕ " . self::money($amount) . "\n💳 Số dư: <b>" . self::money(self::balance((int)$u['id'], $sp)) . "</b>\n🕒 " . date('H:i d/m/Y'));
    }
}

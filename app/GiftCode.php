<?php
defined('APP') or exit;

/**
 * Mã quà tặng (giftcode): nhập trên web hoặc gửi /code MÃ cho bot.
 * Phần thưởng: tiền ví / điểm ở 1 nhóm / ngày VIP. Mỗi người dùng 1 lần / mã.
 */
class GiftCode
{
    const TYPES = ['money' => 'Tiền ví', 'points' => 'Điểm nhóm', 'vip' => 'Ngày VIP'];

    public static function row(array $r): array
    {
        return [
            'id' => (int)$r['id'], 'code' => $r['code'], 'type' => $r['type'], 'amount' => (int)$r['amount'],
            'group_id' => $r['group_id'] ? (int)$r['group_id'] : null,
            'max_uses' => (int)$r['max_uses'], 'uses' => (int)$r['uses'], 'expires_at' => $r['expires_at'] ? (int)$r['expires_at'] : null,
            'enabled' => (int)$r['enabled'], 'note' => $r['note'], 'created_at' => (int)$r['created_at'],
            'conditions' => self::conds($r),
        ];
    }

    /** Điều kiện dùng mã (đã chuẩn hoá) */
    public static function conds(array $r): array
    {
        $c = json_decode((string)($r['conditions'] ?? ''), true) ?: [];
        return [
            'group_id' => !empty($c['group_id']) ? (int)$c['group_id'] : null, // nhóm dùng để xét điều kiện
            'member' => !empty($c['member']),                // phải đang ở nhóm đó
            'min_points' => max(0, (int)($c['min_points'] ?? 0)),
            'min_messages' => max(0, (int)($c['min_messages'] ?? 0)),
            'min_days' => max(0, (int)($c['min_days'] ?? 0)), // vào nhóm ít nhất N ngày
            'min_deposit' => max(0, (int)($c['min_deposit'] ?? 0)), // tổng đã nạp ví
            'vip_only' => !empty($c['vip_only']),
            'learned' => !empty($c['learned']),              // đã hoàn thành Học tập
        ];
    }

    /** Mô tả điều kiện cho người dùng xem */
    public static function describe(array $c): array
    {
        $g = $c['group_id'] ? Repo::group($c['group_id']) : null;
        $gn = $g ? $g['title'] : 'nhóm';
        $out = [];
        if ($c['member']) $out[] = "Đang là thành viên $gn";
        if ($c['min_points']) $out[] = 'Có ≥ ' . number_format($c['min_points'], 0, ',', '.') . " điểm ở $gn";
        if ($c['min_messages']) $out[] = 'Đã nhắn ≥ ' . number_format($c['min_messages'], 0, ',', '.') . " tin ở $gn";
        if ($c['min_days']) $out[] = "Vào $gn ≥ {$c['min_days']} ngày";
        if ($c['min_deposit']) $out[] = 'Đã nạp ≥ ' . Wallet::money($c['min_deposit']) . ' vào ví';
        if ($c['vip_only']) $out[] = 'Đang là VIP';
        if ($c['learned']) $out[] = 'Đã hoàn thành bài học';
        return $out;
    }

    /** Kiểm tra điều kiện, không đạt thì báo lỗi rõ ràng */
    private static function checkConds(array $c, ?array $u, $tgUserId): void
    {
        $needGroup = $c['member'] || $c['min_points'] || $c['min_messages'] || $c['min_days'];
        if (($needGroup || $c['vip_only'] || $c['learned']) && !$tgUserId) throw new HttpError('Mã này yêu cầu liên kết Telegram với tài khoản web');
        if ($needGroup) {
            $g = $c['group_id'] ? Repo::group($c['group_id']) : null;
            if (!$g) throw new HttpError('Nhóm điều kiện của mã không còn tồn tại');
            $m = Repo::member((int)$g['id'], $tgUserId);
            $in = $m && in_array($m['status'], ['member', 'muted'], true);
            if (!$in) throw new HttpError('Mã này dành cho thành viên nhóm ' . $g['title']);
            if ($c['min_points'] && (int)$m['points'] < $c['min_points']) throw new HttpError('Cần ≥ ' . number_format($c['min_points'], 0, ',', '.') . ' điểm ở nhóm ' . $g['title'] . ' (bạn có ' . number_format((int)$m['points'], 0, ',', '.') . ')');
            if ($c['min_messages'] && (int)$m['messages'] < $c['min_messages']) throw new HttpError('Cần đã nhắn ≥ ' . number_format($c['min_messages'], 0, ',', '.') . ' tin ở nhóm ' . $g['title'] . ' (bạn có ' . number_format((int)$m['messages'], 0, ',', '.') . ')');
            if ($c['min_days']) {
                $days = $m['joined_at'] ? intdiv(now() - (int)$m['joined_at'], 86400) : 0;
                if ($days < $c['min_days']) throw new HttpError("Cần vào nhóm {$g['title']} ít nhất {$c['min_days']} ngày (bạn mới $days ngày)");
            }
        }
        if ($c['min_deposit']) {
            $dep = $u ? (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM wallet_tx WHERE user_id = ? AND type = 'deposit'", [$u['id']]) : 0;
            if ($dep < $c['min_deposit']) throw new HttpError('Mã này dành cho người đã nạp ≥ ' . Wallet::money($c['min_deposit']) . ' (bạn đã nạp ' . Wallet::money($dep) . ')');
        }
        if ($c['vip_only'] && !Vip::active($tgUserId)) throw new HttpError('Mã này chỉ dành cho thành viên VIP');
        if ($c['learned']) {
            $p = Learn::progress($tgUserId);
            if (!$p || $p['status'] !== 'done') throw new HttpError('Mã này yêu cầu hoàn thành bài học trước');
        }
    }

    public static function save(array $in): array
    {
        $id = (int)($in['id'] ?? 0);
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)($in['code'] ?? '')));
        if ($code === '') $code = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        if (strlen($code) < 3 || strlen($code) > 32) throw new HttpError('Mã 3–32 ký tự (chữ, số, - _)');
        $dup = DB::val('SELECT id FROM gift_codes WHERE code = ? AND id <> ?', [$code, $id]);
        if ($dup) throw new HttpError("Mã $code đã tồn tại");
        $type = isset(self::TYPES[$in['type'] ?? '']) ? $in['type'] : 'money';
        $amount = (int)($in['amount'] ?? 0);
        if ($amount <= 0) throw new HttpError($type === 'money' ? 'Nhập số tiền thưởng' : ($type === 'vip' ? 'Nhập số ngày VIP' : 'Nhập số điểm thưởng'));
        if ($type === 'money' && $amount > 100000000) throw new HttpError('Số tiền quá lớn');
        $gid = (int)($in['group_id'] ?? 0);
        if ($type === 'points' && (!$gid || !Repo::group($gid))) throw new HttpError('Mã thưởng điểm cần chọn nhóm');
        $exp = trim((string)($in['expires'] ?? ''));
        $expires = $exp !== '' ? strtotime($exp . (strlen($exp) <= 10 ? ' 23:59:59' : '')) : null;
        if ($exp !== '' && !$expires) throw new HttpError('Ngày hết hạn không hợp lệ');
        $data = [
            'code' => $code, 'type' => $type, 'amount' => $amount, 'group_id' => $type === 'points' ? $gid : null,
            'max_uses' => max(0, (int)($in['max_uses'] ?? 1)),
            'expires_at' => $expires ?: null, 'enabled' => !array_key_exists('enabled', $in) || !empty($in['enabled']) ? 1 : 0,
            'note' => mb_substr(trim((string)($in['note'] ?? '')), 0, 190),
            'conditions' => json_encode(self::conds(['conditions' => json_encode((array)($in['conditions'] ?? []))]), JSON_UNESCAPED_UNICODE),
        ];
        $cd = self::conds($data);
        if (($cd['member'] || $cd['min_points'] || $cd['min_messages'] || $cd['min_days']) && (!$cd['group_id'] || !Repo::group($cd['group_id']))) {
            throw new HttpError('Điều kiện theo nhóm: chọn nhóm để xét');
        }
        if ($id) DB::update('gift_codes', $data, 'id = ?', [$id]);
        else $id = DB::insert('gift_codes', $data + ['uses' => 0, 'created_at' => now()]);
        return self::row(DB::get('SELECT * FROM gift_codes WHERE id = ?', [$id]));
    }

    /**
     * Dùng mã. $u = tài khoản web (bắt buộc với tiền ví), $tgUserId = Telegram của người nhập.
     * @return array{type:string, amount:int, text:string}
     */
    public static function redeem(?array $u, $tgUserId, string $code): array
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $code));
        if ($code === '') throw new HttpError('Nhập mã quà tặng');
        $key = 'gift_try_' . ($tgUserId ?: ('u' . ($u['id'] ?? 0)));
        $tries = array_filter(json_decode((string)Settings::get($key), true) ?: [], fn($t) => $t > now() - 600);
        if (count($tries) >= 10) throw new HttpError('Nhập sai quá nhiều lần, thử lại sau 10 phút', 429);
        $g = DB::get('SELECT * FROM gift_codes WHERE code = ?', [$code]);
        $bad = function (string $msg) use ($key, $tries) { $tries[] = now(); Settings::set($key, json_encode(array_values($tries))); throw new HttpError($msg); };
        if (!$g || !$g['enabled']) $bad('Mã không tồn tại hoặc đã bị tắt');
        if ($g['expires_at'] && (int)$g['expires_at'] < now()) $bad('Mã đã hết hạn');
        if ($g['max_uses'] > 0 && (int)$g['uses'] >= (int)$g['max_uses']) $bad('Mã đã hết lượt sử dụng');
        if (!$tgUserId && $g['type'] !== 'money') throw new HttpError('Hãy liên kết Telegram ở trang Tài khoản để nhận phần thưởng này');
        if ($g['type'] === 'money' && !$u) throw new HttpError('Mã thưởng tiền cần tài khoản web: đăng nhập web bằng Telegram trước');
        $who = $tgUserId ? (string)$tgUserId : 'u' . $u['id'];
        if (DB::get('SELECT id FROM gift_redemptions WHERE code_id = ? AND who = ?', [$g['id'], $who])) throw new HttpError('Bạn đã dùng mã này rồi');
        self::checkConds(self::conds($g), $u, $tgUserId); // điều kiện dùng mã (không tính là nhập sai)

        // Giữ lượt trước (chống 2 người dùng lượt cuối cùng lúc), lỗi thì trả lại
        $cond = $g['max_uses'] > 0 ? ' AND uses < max_uses' : '';
        if (!DB::run("UPDATE gift_codes SET uses = uses + 1 WHERE id = ?$cond", [$g['id']])) throw new HttpError('Mã đã hết lượt sử dụng');
        try {
            DB::insert('gift_redemptions', ['code_id' => $g['id'], 'who' => $who, 'user_id' => $u['id'] ?? null, 'tg_user_id' => $tgUserId ?: null, 'created_at' => now()]);
        } catch (PDOException $e) {
            DB::run('UPDATE gift_codes SET uses = uses - 1 WHERE id = ?', [$g['id']]);
            throw new HttpError('Bạn đã dùng mã này rồi');
        }
        try {
            $amount = (int)$g['amount'];
            if ($g['type'] === 'money') {
                Wallet::credit((int)$u['id'], $amount, 'gift', "Mã quà tặng $code", $code);
                $text = '+' . Wallet::money($amount) . ' vào ví';
            } elseif ($g['type'] === 'points') {
                $grp = Repo::group((int)$g['group_id']);
                if (!$grp) throw new HttpError('Nhóm của mã không còn tồn tại');
                $m = Repo::member((int)$grp['id'], $tgUserId);
                if (!$m || !in_array($m['status'], ['member', 'muted'], true)) throw new HttpError('Mã này dành cho thành viên nhóm ' . $grp['title'] . '. Hãy vào nhóm trước.');
                DB::run('UPDATE tg_members SET points = points + ? WHERE id = ?', [$amount, $m['id']]);
                Repo::log((int)$grp['id'], Repo::name($m), 'points', $m, "+$amount điểm (mã quà tặng $code)", ['source' => 'web', 'amount' => $amount, 'before' => (int)$m['points'], 'after' => (int)$m['points'] + $amount]);
                MemberTag::auto($grp, $tgUserId);
                $text = '+' . number_format($amount, 0, ',', '.') . ' điểm ở nhóm ' . $grp['title'];
            } else {
                $until = Vip::extend($tgUserId, $amount, "mã quà tặng $code");
                $text = "+$amount ngày VIP (đến " . date('d/m/Y', $until) . ')';
            }
        } catch (Throwable $e) {
            DB::run('DELETE FROM gift_redemptions WHERE code_id = ? AND who = ?', [$g['id'], $who]);
            DB::run('UPDATE gift_codes SET uses = uses - 1 WHERE id = ?', [$g['id']]);
            throw $e instanceof HttpError ? $e : new HttpError($e->getMessage());
        }
        Repo::log($g['type'] === 'points' ? (int)$g['group_id'] : null, $u ? ($u['display_name'] ?: $u['username']) : (string)$tgUserId, 'gift', null, "Dùng mã $code: $text", ['source' => 'web']);
        return ['type' => $g['type'], 'amount' => (int)$g['amount'], 'text' => $text, 'code' => $code];
    }
}

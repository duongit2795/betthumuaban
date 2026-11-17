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
        ];
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
        ];
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

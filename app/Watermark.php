<?php
defined('APP') or exit;

/**
 * Đóng dấu ảnh chống copy: thành viên gửi ảnh → bot tải ảnh, in chữ mờ (tên, @username, tên nhóm…),
 * gửi lại ảnh đã đóng dấu kèm chú thích "Ảnh của @xxx gửi" rồi xoá ảnh gốc.
 * Cần PHP GD + FreeType; phông chữ đóng gói sẵn ở assets/fonts. Cấu hình: Settings 'watermark'.
 */
class Watermark
{
    const FONT = ROOT . '/assets/fonts/DejaVuSans-Bold.ttf';
    const STYLES = ['tile' => 'Lặp chéo khắp ảnh (khó xoá nhất)', 'corner' => 'Góc dưới bên phải', 'center' => 'Giữa ảnh'];
    const DEFAULTS = [
        'enabled' => false,
        'groups' => [],
        'exempt_admins' => true,
        'exempt_vip' => false,
        'documents' => true,          // cả ảnh gửi dạng tệp (jpg / png)
        'text' => '{name} · {username} · {group}',
        'style' => 'tile',
        'opacity' => 28,              // % độ đậm chữ
        'size' => 4,                  // cỡ chữ = % chiều rộng ảnh
        'color' => 'white',
        'caption' => '📷 Ảnh của {tag} gửi',
        'keep_caption' => true,       // giữ chú thích gốc phía dưới
    ];

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('watermark'), true) ?: [];
        return array_merge(self::DEFAULTS, array_intersect_key($c, self::DEFAULTS));
    }

    public static function save(array $in): array
    {
        $c = self::config();
        foreach (['enabled', 'exempt_admins', 'exempt_vip', 'documents', 'keep_caption'] as $k) if (array_key_exists($k, $in)) $c[$k] = !empty($in[$k]);
        if (isset($in['groups'])) $c['groups'] = array_values(array_unique(array_map('intval', array_filter((array)$in['groups'], 'is_numeric'))));
        if (isset($in['style']) && isset(self::STYLES[$in['style']])) $c['style'] = $in['style'];
        if (isset($in['color']) && in_array($in['color'], ['white', 'black'], true)) $c['color'] = $in['color'];
        if (isset($in['opacity'])) $c['opacity'] = max(5, min(100, (int)$in['opacity']));
        if (isset($in['size'])) $c['size'] = max(2, min(12, (int)$in['size']));
        foreach (['text' => 200, 'caption' => 300] as $k => $max) {
            if (isset($in[$k])) { $t = trim(mb_substr((string)$in[$k], 0, $max)); $c[$k] = $t !== '' ? $t : self::DEFAULTS[$k]; }
        }
        if ($c['enabled'] && !self::ready()) throw new HttpError('Máy chủ thiếu PHP GD + FreeType: aaPanel → App Store → PHP → Cài extension "gd" (bản có FreeType)');
        Settings::set('watermark', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    /** Máy chủ có đủ GD + FreeType + phông chữ? */
    public static function ready(): bool
    {
        return function_exists('imagettftext') && function_exists('imagecreatefromstring') && is_file(self::FONT);
    }

    public static function applies(array $c, array $group): bool
    {
        return $c['enabled'] && (!$c['groups'] || in_array((int)$group['id'], $c['groups'], true));
    }

    /** Chữ đóng dấu cho 1 người */
    public static function label(array $c, array $user, array $group): string
    {
        $clean = fn($v) => trim(preg_replace('/\s+/u', ' ', preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', (string)$v))); // phông không có emoji
        $name = $clean(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $id = (string)($user['id'] ?? '');
        $t = strtr($c['text'], ['{name}' => $name !== '' ? $name : 'ID ' . $id, '{username}' => !empty($user['username']) ? '@' . $user['username'] : '',
            '{id}' => $id, '{group}' => $clean($group['title']), '{date}' => date('d/m/Y')]);
        $lines = [];
        foreach (explode("\n", $t) as $l) { // bỏ dấu phân cách thừa khi thiếu thông tin (VD chưa có @username)
            $l = trim(preg_replace('/(\s*·\s*){2,}/u', ' · ', $l), " ·\t");
            if ($l !== '') $lines[] = $l;
        }
        return implode("\n", $lines);
    }

    /**
     * In chữ mờ lên ảnh (dữ liệu nhị phân). Trả về JPEG đã đóng dấu.
     */
    public static function render(string $bin, string $text, array $c): string
    {
        $im = @imagecreatefromstring($bin);
        if (!$im) throw new RuntimeException('Không đọc được ảnh');
        // Vẽ lên nền trắng (ảnh PNG trong suốt không bị đen) và thu nhỏ ảnh quá lớn (tối đa 2560px như Telegram)
        $sw = imagesx($im); $sh0 = imagesy($im);
        $k = min(1, 2560 / max($sw, $sh0));
        $w = max(1, (int)round($sw * $k)); $h = max(1, (int)round($sh0 * $k));
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $w, $h, $sw, $sh0);
        imagedestroy($im);
        $im = $dst;
        imagealphablending($im, true);
        $fs = max(12, (int)round($w * $c['size'] / 100));
        $alpha = 127 - (int)round(127 * $c['opacity'] / 100);
        $isWhite = $c['color'] !== 'black';
        $fg = $isWhite ? imagecolorallocatealpha($im, 255, 255, 255, $alpha) : imagecolorallocatealpha($im, 0, 0, 0, $alpha);
        $sh = $isWhite ? imagecolorallocatealpha($im, 0, 0, 0, min(127, $alpha + 25)) : imagecolorallocatealpha($im, 255, 255, 255, min(127, $alpha + 25));
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), 'strlen'));
        $draw = function (float $angle, int $x, int $y, string $t) use ($im, &$fs, $fg, $sh) {
            $d = max(1, (int)round($fs / 14));
            imagettftext($im, $fs, $angle, $x + $d, $y + $d, $sh, self::FONT, $t); // bóng chữ: đọc được trên mọi nền
            imagettftext($im, $fs, $angle, $x, $y, $fg, self::FONT, $t);
        };
        $box = function (string $t, float $angle = 0) use (&$fs) {
            $b = imagettfbbox($fs, $angle, self::FONT, $t);
            return [abs($b[2] - $b[0]), abs($b[7] - $b[1])];
        };
        if ($c['style'] === 'tile') {
            $line = implode('   ·   ', $lines);
            [$tw] = $box($line);
            $angle = 28;
            // Lưới chữ chạy dọc theo trục đã xoay → các hàng song song, cách đều, không chồng nhau
            $ca = cos(deg2rad($angle)); $sa = sin(deg2rad($angle));
            $stepU = $tw + $fs * 3;
            $stepV = (int)($fs * 3.4);
            $cx = $w / 2; $cy = $h / 2;
            $R = (int)(sqrt($w * $w + $h * $h) / 2) + $stepU;
            for ($r = 0, $v = -$R; $v <= $R; $v += $stepV, $r++) {
                $off = ($r % 2) * $stepU / 2;
                for ($u = -$R - $off; $u <= $R; $u += $stepU) {
                    $x = (int)($cx + $u * $ca + $v * $sa);
                    $y = (int)($cy - $u * $sa + $v * $ca);
                    if ($x < -$tw - $fs || $x > $w + $fs || $y < -$fs || $y > $h + $tw * $sa + $fs) continue;
                    $draw($angle, $x, $y, $line);
                }
            }
        } else {
            // Chữ dài hơn ảnh → tách mỗi phần (cách nhau bằng ·) thành 1 dòng, vẫn dài thì thu nhỏ chữ
            $widest = fn() => max(array_map(fn($t) => $box($t)[0], $lines));
            if ($widest() > $w * 0.92) {
                $split = [];
                foreach ($lines as $l) foreach (preg_split('/\s*·\s*/u', $l) as $p) if ($p !== '') $split[] = $p;
                $lines = $split;
            }
            while ($fs > 10 && $widest() > $w * 0.92) $fs = max(10, (int)floor($fs * 0.9));
            $lh = (int)($fs * 1.45);
            $pad = (int)($fs * 0.9);
            $total = $lh * count($lines);
            foreach ($lines as $i => $t) {
                [$tw] = $box($t);
                if ($c['style'] === 'center') { $x = (int)(($w - $tw) / 2); $y = (int)(($h - $total) / 2) + $lh * ($i + 1) - (int)($lh * 0.3); }
                else { $x = $w - $tw - $pad; $y = $h - $pad - $total + $lh * ($i + 1) - (int)($lh * 0.3); }
                $draw(0, max($pad, $x), $y, $t);
            }
        }
        ob_start();
        imagejpeg($im, null, 90);
        imagedestroy($im);
        return (string)ob_get_clean();
    }

    /** Ảnh mẫu (ảnh tải lên hoặc nền chuyển màu) đã đóng dấu → data URI để xem trước trên web */
    public static function preview(array $c, array $group, ?string $bin = null): string
    {
        if (!self::ready()) throw new HttpError('Máy chủ thiếu PHP GD + FreeType nên chưa xem trước / đóng dấu được');
        if ($bin === null || $bin === '') {
            $w = 960; $h = 640; $im = imagecreatetruecolor($w, $h);
            for ($y = 0; $y < $h; $y++) { // nền chuyển màu + vài khối để thấy chữ trên nền sáng & tối
                $k = $y / $h;
                imageline($im, 0, $y, $w, $y, imagecolorallocate($im, (int)(40 + 180 * $k), (int)(110 + 60 * (1 - $k)), (int)(200 - 120 * $k)));
            }
            imagefilledellipse($im, 720, 200, 300, 300, imagecolorallocate($im, 250, 230, 120));
            imagefilledrectangle($im, 80, 380, 420, 600, imagecolorallocate($im, 30, 30, 40));
            imagefilledrectangle($im, 470, 450, 900, 600, imagecolorallocate($im, 245, 245, 245));
            ob_start(); imagejpeg($im, null, 90); imagedestroy($im); $bin = (string)ob_get_clean();
        }
        $u = ['id' => 123456789, 'first_name' => 'Nguyễn', 'last_name' => 'Lan', 'username' => 'lan99'];
        return 'data:image/jpeg;base64,' . base64_encode(self::render($bin, self::label($c, $u, $group), $c));
    }

    /** Tải file Telegram theo file_id */
    /** Dùng cho kiểm thử: thay bước tải ảnh */
    public static $fetcher = null;

    private static function fetch(Telegram $api, string $fileId): string
    {
        if (self::$fetcher) return (self::$fetcher)($fileId);
        $f = $api->call('getFile', ['file_id' => $fileId]);
        if (empty($f['file_path'])) throw new RuntimeException('Telegram không trả về đường dẫn tệp');
        if (($f['file_size'] ?? 0) > 20 * 1024 * 1024) throw new RuntimeException('Ảnh quá lớn');
        $ch = curl_init('https://api.telegram.org/file/bot' . $api->token . '/' . $f['file_path']);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true]);
        $bin = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($bin) || $bin === '' || $code !== 200) throw new RuntimeException('Tải ảnh thất bại (HTTP ' . $code . ')');
        return $bin;
    }

    /**
     * Gọi khi có tin nhắn trong nhóm. Ảnh → đóng dấu, gửi lại, xoá gốc.
     * @return bool true nếu đã thay ảnh (tin gốc đã xoá)
     */
    public static function handle(Telegram $api, array $group, array $msg, array $member, bool $isAdmin): bool
    {
        $c = self::config();
        if (!self::applies($c, $group) || !empty($msg['sender_chat']) || !empty($msg['from']['is_bot'])) return false;
        if ($isAdmin && $c['exempt_admins']) return false;
        $fileId = null;
        if (!empty($msg['photo'])) $fileId = end($msg['photo'])['file_id'];
        elseif ($c['documents'] && !empty($msg['document']['file_id']) && preg_match('~^image/(jpeg|png|webp)$~', (string)($msg['document']['mime_type'] ?? ''))) $fileId = $msg['document']['file_id'];
        if (!$fileId || !self::ready()) return false;
        if ($c['exempt_vip'] && Vip::active($msg['from']['id'], (int)$group['id'])) return false;

        $tmp = null;
        try {
            @set_time_limit(60);
            $out = self::render(self::fetch($api, $fileId), self::label($c, $msg['from'], $group), $c);
            if (!is_dir(DATA_DIR . '/media')) @mkdir(DATA_DIR . '/media', 0755, true);
            $tmp = DATA_DIR . '/media/wm_' . bin2hex(random_bytes(6)) . '.jpg';
            file_put_contents($tmp, $out);
            $tag = !empty($msg['from']['username']) ? '@' . tg_esc($msg['from']['username']) : Repo::mention($member);
            $cap = strtr($c['caption'], ['{tag}' => $tag, '{mention}' => Repo::mention($member), '{name}' => tg_esc(Repo::name($member)), '{group}' => tg_esc($group['title'])]);
            $orig = trim((string)($msg['caption'] ?? ''));
            if ($c['keep_caption'] && $orig !== '') $cap .= "\n\n" . tg_esc($orig);
            $params = ['chat_id' => $group['chat_id'], 'caption' => mb_substr($cap, 0, 1024), 'parse_mode' => 'HTML'];
            if (!empty($msg['reply_to_message']['message_id']) && empty($msg['reply_to_message']['forum_topic_created'])) {
                $params['reply_parameters'] = ['message_id' => $msg['reply_to_message']['message_id'], 'allow_sending_without_reply' => true];
            }
            if (!empty($msg['is_topic_message']) && isset($msg['message_thread_id'])) $params['message_thread_id'] = $msg['message_thread_id'];
            if (!empty($msg['has_media_spoiler'])) $params['has_spoiler'] = true;
            $sent = $api->callMultipart('sendPhoto', $params, ['photo' => $tmp]);
        } catch (Throwable $e) {
            Settings::set('watermark_error', mb_substr($e->getMessage(), 0, 200) . ' · ' . date('H:i d/m'));
            return false; // lỗi: giữ nguyên ảnh gốc
        } finally {
            if ($tmp) @unlink($tmp);
        }
        // Gửi được rồi mới xoá ảnh gốc
        $api->tryCall('deleteMessage', ['chat_id' => $group['chat_id'], 'message_id' => $msg['message_id']]);
        ChatLog::markDeleted($group['chat_id'], $msg['message_id'], 'Đã đóng dấu & gửi lại');
        if (is_array($sent)) ChatLog::store($sent, (int)$group['id']);
        Settings::set('watermark_count', (string)((int)Settings::get('watermark_count') + 1));
        return true;
    }
}

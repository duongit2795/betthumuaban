<?php
defined('APP') or exit;

/** Lưu tin nhắn nhóm để xem trong tab Chat trên web. */
class ChatLog
{
    const KEEP_DAYS = 30;
    private static array $gidCache = [];

    private static function groupId($chatId): ?int
    {
        if (!array_key_exists((string)$chatId, self::$gidCache)) {
            $id = DB::val('SELECT id FROM tg_groups WHERE chat_id = ?', [$chatId]);
            self::$gidCache[(string)$chatId] = $id ? (int)$id : null;
        }
        return self::$gidCache[(string)$chatId];
    }

    /** Loại tin và nội dung hiển thị */
    public static function describe(array $m): array
    {
        $text = (string)($m['text'] ?? $m['caption'] ?? '');
        $kinds = [
            'photo' => '📷 Ảnh', 'video' => '🎬 Video', 'animation' => '🎞 GIF', 'voice' => '🎤 Ghi âm',
            'audio' => '🎵 Âm thanh', 'video_note' => '⏺ Video tròn', 'location' => '📍 Vị trí', 'contact' => '👤 Danh bạ',
        ];
        foreach ($kinds as $k => $label) {
            if (isset($m[$k])) return [$k, $text !== '' ? $text : $label, $label];
        }
        if (isset($m['sticker'])) return ['sticker', ($m['sticker']['emoji'] ?? '') . ' Sticker', '🧩 Sticker'];
        if (isset($m['document'])) return ['document', $text !== '' ? $text : ($m['document']['file_name'] ?? 'Tệp'), '📎 ' . ($m['document']['file_name'] ?? 'Tệp')];
        if (isset($m['poll'])) return ['poll', $m['poll']['question'] ?? '', '📊 Bình chọn'];
        if (isset($m['dice'])) return ['dice', ($m['dice']['emoji'] ?? '🎲') . ' ' . ($m['dice']['value'] ?? ''), '🎲'];
        return ['text', $text, ''];
    }

    /** Mã file Telegram của tin có ảnh / video / tệp → [file_id, thumb_id, file_name, file_size, mime] */
    public static function fileInfo(array $m): array
    {
        if (!empty($m['photo'])) {
            $sizes = $m['photo'];
            $full = end($sizes);
            $thumb = $sizes[0];
            foreach ($sizes as $s) if (($s['width'] ?? 0) >= 320) { $thumb = $s; break; } // ảnh nhỏ vừa đủ nét
            return [$full['file_id'], $thumb['file_id'], null, $full['file_size'] ?? null, 'image/jpeg'];
        }
        foreach (['video', 'animation', 'video_note', 'sticker', 'voice', 'audio', 'document'] as $k) {
            if (empty($m[$k]['file_id'])) continue;
            $f = $m[$k];
            $thumb = $f['thumbnail']['file_id'] ?? ($f['thumb']['file_id'] ?? null);
            $mime = $f['mime_type'] ?? ['video_note' => 'video/mp4', 'voice' => 'audio/ogg', 'sticker' => !empty($f['is_video']) ? 'video/webm' : (!empty($f['is_animated']) ? 'application/x-tgsticker' : 'image/webp')][$k] ?? null;
            return [$f['file_id'], $thumb, isset($f['file_name']) ? mb_substr($f['file_name'], 0, 190) : null, $f['file_size'] ?? null, $mime ? mb_substr($mime, 0, 64) : null];
        }
        return [null, null, null, null, null];
    }

    private static function name(?array $u, ?array $senderChat = null): string
    {
        if ($senderChat) return (string)($senderChat['title'] ?? 'Kênh');
        if (!$u) return '';
        $n = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        return $n !== '' ? $n : (isset($u['username']) ? '@' . $u['username'] : (string)($u['id'] ?? ''));
    }

    /** Lưu một tin nhắn (đến hoặc do bot gửi). */
    public static function store(array $m, ?int $groupId = null, string $kindOverride = '', string $textOverride = ''): void
    {
        try {
            $chat = $m['chat'] ?? [];
            if (!in_array($chat['type'] ?? '', ['group', 'supergroup'], true)) return;
            $gid = $groupId ?: self::groupId($chat['id']);
            if (!$gid) return;
            [$kind, $text, $label] = self::describe($m);
            if ($kindOverride !== '') { $kind = $kindOverride; $text = $textOverride; $label = ''; }
            $from = $m['from'] ?? null;
            $reply = $m['reply_to_message'] ?? null;
            $replyText = null;
            if ($reply && empty($reply['forum_topic_created'])) $replyText = mb_substr(self::describe($reply)[1], 0, 140);
            else $reply = null;
            [$fileId, $thumbId, $fileName, $fileSize, $mime] = self::fileInfo($m);
            DB::insert('tg_messages', [
                'file_id' => $fileId, 'thumb_id' => $thumbId, 'file_name' => $fileName, 'file_size' => $fileSize, 'mime' => $mime,
                'group_id' => $gid,
                'message_id' => $m['message_id'],
                'tg_user_id' => $from['id'] ?? ($m['sender_chat']['id'] ?? 0),
                'name' => mb_substr(self::name($from, $m['sender_chat'] ?? null), 0, 190),
                'username' => $from['username'] ?? null,
                'is_bot' => !empty($from['is_bot']) ? 1 : 0,
                'kind' => $kind,
                'media' => $label ?: null,
                'text' => mb_substr($text, 0, 4000),
                'reply_to' => $reply['message_id'] ?? null,
                'reply_name' => $reply ? mb_substr(self::name($reply['from'] ?? null, $reply['sender_chat'] ?? null), 0, 190) : null,
                'reply_text' => $replyText,
                'created_at' => (int)($m['date'] ?? now()) ?: now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Trùng tin (Telegram gửi lại) hoặc lỗi khác: bỏ qua, không ảnh hưởng xử lý bot
        }
    }

    public static function edited(array $m): void
    {
        try {
            $gid = self::groupId($m['chat']['id'] ?? 0);
            if (!$gid) return;
            [, $text] = self::describe($m);
            DB::run('UPDATE tg_messages SET text = ?, edited = 1, updated_at = ? WHERE group_id = ? AND message_id = ?', [mb_substr($text, 0, 4000), now(), $gid, $m['message_id']]);
        } catch (Throwable $e) {}
    }

    public static function markDeleted($chatId, $messageId, ?string $note = null): void
    {
        try {
            $gid = self::groupId($chatId);
            if (!$gid) return;
            if ($note !== null) DB::run('UPDATE tg_messages SET deleted = 1, note = ?, updated_at = ? WHERE group_id = ? AND message_id = ?', [mb_substr($note, 0, 190), now(), $gid, $messageId]);
            else DB::run('UPDATE tg_messages SET deleted = 1, updated_at = ? WHERE group_id = ? AND message_id = ? AND deleted = 0', [now(), $gid, $messageId]);
        } catch (Throwable $e) {}
    }

    public static function markPinned($chatId, $messageId): void
    {
        try {
            $gid = self::groupId($chatId);
            if ($gid) DB::run('UPDATE tg_messages SET pinned = 1, updated_at = ? WHERE group_id = ? AND message_id = ?', [now(), $gid, $messageId]);
        } catch (Throwable $e) {}
    }

    public static function cleanup(): void
    {
        DB::run('DELETE FROM tg_messages WHERE created_at < ?', [now() - self::KEEP_DAYS * 86400]);
        // Ảnh / video đã tải về để xem: giữ 7 ngày
        foreach (glob(self::MEDIA_DIR . '/m_*') ?: [] as $f) if (filemtime($f) < now() - 7 * 86400) @unlink($f);
    }

    const MEDIA_DIR = DATA_DIR . '/media';
    const MAX_DOWNLOAD = 20 * 1024 * 1024; // giới hạn tải file của Bot API

    /**
     * Tải file của 1 tin về (lưu tạm trong data/media) qua bot của nhóm.
     * @return array{0:string,1:string} [đường dẫn, mime]
     */
    public static function download(array $group, array $row, bool $thumb): array
    {
        $fid = $thumb ? ($row['thumb_id'] ?: $row['file_id']) : $row['file_id'];
        if (!$fid) throw new HttpError('Tin này không có ảnh / tệp đính kèm', 404);
        $useThumb = $thumb && $row['thumb_id'];
        $mime = $useThumb ? 'image/jpeg' : ($row['mime'] ?: 'application/octet-stream');
        if (!$useThumb && $row['kind'] === 'sticker' && $mime === 'application/x-tgsticker') throw new HttpError('Sticker động (.tgs) không xem được trên web', 415);
        if (!is_dir(self::MEDIA_DIR) && !@mkdir(self::MEDIA_DIR, 0755, true)) throw new HttpError('Không tạo được thư mục data/media');
        @file_put_contents(self::MEDIA_DIR . '/index.html', '');
        $path = self::MEDIA_DIR . '/m_' . $group['id'] . '_' . $row['message_id'] . ($useThumb ? '_t' : '_f');
        if (is_file($path) && filesize($path) > 0) return [$path, $mime];
        if (!$useThumb && (int)$row['file_size'] > self::MAX_DOWNLOAD) throw new HttpError('Tệp lớn hơn 20 MB, Telegram không cho bot tải về. Hãy mở trong Telegram.', 413);
        $api = Repo::apiForGroup($group);
        if (!$api) throw new HttpError('Nhóm chưa có bot hoạt động');
        try {
            $f = $api->call('getFile', ['file_id' => $fid]);
        } catch (TelegramException $e) {
            throw new HttpError('Không lấy được tệp từ Telegram: ' . $e->getMessage(), 502);
        }
        if (empty($f['file_path'])) throw new HttpError('Telegram không trả về đường dẫn tệp', 502);
        $tmp = $path . '.part';
        $fh = fopen($tmp, 'w');
        $ch = curl_init('https://api.telegram.org/file/bot' . $api->token . '/' . $f['file_path']);
        curl_setopt_array($ch, [CURLOPT_FILE => $fh, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true]);
        $ok = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);
        if (!$ok || $code !== 200 || filesize($tmp) === 0) { @unlink($tmp); throw new HttpError('Tải tệp từ Telegram thất bại (HTTP ' . $code . ')', 502); }
        rename($tmp, $path);
        if ($useThumb || $mime === 'application/octet-stream') {
            $det = file_mime($path);
            if ($det && $det !== 'application/octet-stream') $mime = $det;
        }
        return [$path, $mime];
    }
}

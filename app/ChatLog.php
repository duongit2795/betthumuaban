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
            DB::insert('tg_messages', [
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
    }
}

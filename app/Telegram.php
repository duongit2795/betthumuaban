<?php
defined('APP') or exit;

class TelegramException extends Exception
{
    public ?int $tgCode;
    public function __construct(string $msg, ?int $code = null)
    {
        parent::__construct($msg);
        $this->tgCode = $code;
    }
}

/** Client gọn nhẹ cho Telegram Bot API (dùng cURL). */
class Telegram
{
    const PERM_NONE = [
        'can_send_messages' => false, 'can_send_audios' => false, 'can_send_documents' => false,
        'can_send_photos' => false, 'can_send_videos' => false, 'can_send_video_notes' => false,
        'can_send_voice_notes' => false, 'can_send_polls' => false, 'can_send_other_messages' => false,
        'can_add_web_page_previews' => false,
    ];

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public static function permAll(): array
    {
        return array_map(fn() => true, self::PERM_NONE);
    }

    public function call(string $method, array $params = [], int $timeout = 15)
    {
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new TelegramException('Không kết nối được Telegram: ' . $err);
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['ok'])) {
            throw new TelegramException($data['description'] ?? 'Telegram lỗi', $data['error_code'] ?? null);
        }
        return $data['result'];
    }

    public function send($chatId, string $text, array $extra = [])
    {
        return $this->call('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ], $extra));
    }

    /** Gửi không quan tâm lỗi */
    public function trySend($chatId, string $text, array $extra = [])
    {
        try {
            return $this->send($chatId, $text, $extra);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function tryCall(string $method, array $params = [])
    {
        try {
            return $this->call($method, $params);
        } catch (Throwable $e) {
            return null;
        }
    }

    /** @return array{0: array, 1: array} [lệnh thành viên, lệnh admin] */
    public static function baseCommands(): array
    {
        $member = [
            ['command' => 'help', 'description' => 'Danh sách lệnh'],
            ['command' => 'rules', 'description' => 'Nội quy nhóm'],
            ['command' => 'me', 'description' => 'Thông tin & điểm của bạn'],
            ['command' => 'top', 'description' => 'Bảng xếp hạng tương tác'],
            ['command' => 'info', 'description' => 'Thông tin thành viên (reply)'],
            ['command' => 'id', 'description' => 'Xem ID'],
        ];
        $admin = array_merge($member, [
            ['command' => 'warn', 'description' => 'Cảnh cáo (reply)'],
            ['command' => 'unwarn', 'description' => 'Gỡ 1 cảnh cáo'],
            ['command' => 'mute', 'description' => 'Cấm chat: /mute 30 (phút)'],
            ['command' => 'unmute', 'description' => 'Bỏ cấm chat'],
            ['command' => 'kick', 'description' => 'Đuổi khỏi nhóm'],
            ['command' => 'ban', 'description' => 'Cấm vĩnh viễn'],
            ['command' => 'unban', 'description' => 'Gỡ cấm: /unban ID'],
            ['command' => 'del', 'description' => 'Xoá tin nhắn (reply)'],
            ['command' => 'pin', 'description' => 'Ghim tin nhắn (reply)'],
            ['command' => 'addpoints', 'description' => 'Cộng/trừ điểm: /addpoints 10'],
            ['command' => 'setrules', 'description' => 'Đặt nội quy nhóm'],
        ]);
        return [$member, $admin];
    }

    /** Đăng ký menu lệnh; $memberExtra/$adminExtra là lệnh tự tạo dùng chung mọi nhóm */
    public function registerCommands(array $memberExtra = [], array $adminExtra = []): void
    {
        [$member, $admin] = self::baseCommands();
        $merge = function (array $a, array $b) {
            $o = [];
            foreach (array_merge($a, $b) as $c) $o[$c['command']] = $c;
            return array_slice(array_values($o), 0, 100);
        };
        $this->tryCall('setMyCommands', ['commands' => $merge($member, $memberExtra), 'scope' => ['type' => 'all_group_chats']]);
        $this->tryCall('setMyCommands', ['commands' => $merge($admin, $adminExtra), 'scope' => ['type' => 'all_chat_administrators']]);
        $this->tryCall('setMyCommands', ['commands' => [
            ['command' => 'start', 'description' => 'Bắt đầu'],
            ['command' => 'link', 'description' => 'Liên kết tài khoản web: /link MÃ'],
            ['command' => 'me', 'description' => 'Điểm của tôi'],
        ], 'scope' => ['type' => 'all_private_chats']]);
    }
}

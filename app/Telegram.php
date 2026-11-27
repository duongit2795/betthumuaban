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
        self::afterCall($method, $params, $data['result']);
        return $data['result'];
    }

    /**
     * Gọi song song nhiều request cùng 1 method (curl_multi) — dùng cho gửi tin hàng loạt.
     * @return array mỗi phần tử: ['ok' => bool, 'result' => mixed, 'code' => ?int, 'error' => ?string, 'retry_after' => ?int]
     */
    public function multi(string $method, array $paramsList, int $timeout = 20): array
    {
        $mh = curl_multi_init();
        $hs = [];
        foreach ($paramsList as $i => $params) {
            $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 8]);
            curl_multi_add_handle($mh, $ch);
            $hs[$i] = $ch;
        }
        do {
            $st = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running && $st === CURLM_OK);
        $out = [];
        foreach ($hs as $i => $ch) {
            $body = curl_multi_getcontent($ch);
            $err = curl_error($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            $d = is_string($body) && $body !== '' ? json_decode($body, true) : null;
            $out[$i] = is_array($d) && !empty($d['ok'])
                ? ['ok' => true, 'result' => $d['result'], 'code' => null, 'error' => null, 'retry_after' => null]
                : ['ok' => false, 'result' => null, 'code' => $d['error_code'] ?? null, 'error' => $d['description'] ?? ($err ?: 'Không kết nối được Telegram'), 'retry_after' => $d['parameters']['retry_after'] ?? null];
        }
        curl_multi_close($mh);
        return $out;
    }

    /** Ghi lại tin bot gửi / tin bị xoá / tin được ghim cho tab Chat */
    protected static function afterCall(string $method, array $params, $result): void
    {
        if (in_array($method, ['sendMessage', 'sendPhoto', 'sendDocument', 'sendAnimation', 'sendSticker', 'sendPoll'], true) && is_array($result)) {
            ChatLog::store($result);
        } elseif ($method === 'deleteMessage') {
            ChatLog::markDeleted($params['chat_id'], $params['message_id']);
        } elseif ($method === 'pinChatMessage') {
            ChatLog::markPinned($params['chat_id'], $params['message_id']);
        }
    }

    /** Gọi API có kèm tệp (multipart): $files = ['photo' => '/đường/dẫn/ảnh.jpg'] */
    public function callMultipart(string $method, array $params, array $files, int $timeout = 60)
    {
        $post = [];
        foreach ($params as $k => $v) $post[$k] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'true' : 'false') : (string)$v);
        foreach ($files as $k => $path) $post[$k] = new CURLFile($path, file_mime($path), basename($path));
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 8]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new TelegramException('Không kết nối được Telegram: ' . $err);
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['ok'])) throw new TelegramException($data['description'] ?? 'Telegram lỗi', $data['error_code'] ?? null);
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

    /** @return array{0: array, 1: array} [lệnh thành viên, lệnh admin] (theo tên đặt ở trang Lệnh mặc định) */
    public static function baseCommands(): array
    {
        [$member, $admin] = Commands::menus();
        return [$member, $admin];
    }

    /** Menu "/" chỉ hiện cho quản trị viên nhóm? (cài đặt toàn hệ thống) */
    public static function menuAdminOnly(): bool
    {
        return Settings::get('menu_admin_only') === '1';
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
        if (self::menuAdminOnly()) {
            // Thành viên thường không thấy menu: xoá menu nhóm và menu mặc định
            $this->tryCall('deleteMyCommands', ['scope' => ['type' => 'all_group_chats']]);
            $this->tryCall('deleteMyCommands', ['scope' => ['type' => 'default']]);
        } else {
            $this->tryCall('setMyCommands', ['commands' => $merge($member, $memberExtra), 'scope' => ['type' => 'all_group_chats']]);
        }
        $this->tryCall('setMyCommands', ['commands' => $merge($admin, $adminExtra), 'scope' => ['type' => 'all_chat_administrators']]);
        $this->tryCall('setMyCommands', ['commands' => Commands::menus()[2], 'scope' => ['type' => 'all_private_chats']]);
    }
}

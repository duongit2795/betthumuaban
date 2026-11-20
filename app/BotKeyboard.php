<?php
defined('APP') or exit;

/**
 * Bàn phím nút bấm của bot trong chat riêng (reply keyboard), admin tự thêm nút.
 * Mỗi nút: chữ hiển thị, hàng, hành động (học tập / số dư / điểm / lời chào / trả lời tự soạn / gửi link).
 * Cấu hình lưu ở Settings 'bot_keyboard'.
 */
class BotKeyboard
{
    /** hành động => [tên, mô tả] */
    const ACTIONS = [
        'learn' => ['📚 Học tập', 'Bắt đầu / tiếp tục bài học bắt buộc (ẩn khi Học tập đang tắt)'],
        'balance' => ['💳 Số dư ví', 'Số dư, cách nạp tiền, hình phạt có thể ân xá (như /sodu)'],
        'points' => ['⭐ Điểm của tôi', 'Điểm & tin nhắn ở các nhóm (như /me)'],
        'start' => ['👋 Lời chào', 'Lời chào & hướng dẫn của bot (như /start)'],
        'reply' => ['💬 Trả lời nội dung', 'Bot trả lời nội dung bạn soạn (có thể kèm nút link)'],
        'link' => ['🔗 Mở link', 'Bot gửi 1 nút bấm mở link (web, kênh, nhóm…)'],
    ];
    const MAX = 24;

    public static function defaults(): array
    {
        return ['enabled' => true, 'placeholder' => '', 'buttons' => [
            ['text' => Learn::config()['button'] ?: '📚 Học tập', 'row' => 0, 'action' => 'learn', 'reply' => '', 'url' => '', 'url_label' => ''],
        ]];
    }

    public static function config(): array
    {
        $c = json_decode((string)Settings::get('bot_keyboard'), true);
        if (!is_array($c)) return self::defaults();
        return ['enabled' => !empty($c['enabled']), 'placeholder' => (string)($c['placeholder'] ?? ''), 'buttons' => array_values((array)($c['buttons'] ?? []))];
    }

    public static function save(array $in): array
    {
        $out = [];
        $seen = [];
        foreach (array_slice((array)($in['buttons'] ?? []), 0, self::MAX) as $b) {
            $text = trim(mb_substr(preg_replace('/\s+/u', ' ', (string)($b['text'] ?? '')), 0, 40));
            if ($text === '') continue;
            if (str_starts_with($text, '/')) throw new HttpError("Nút \"$text\": không được bắt đầu bằng dấu /");
            $key = mb_strtolower($text);
            if (isset($seen[$key])) throw new HttpError("Có 2 nút trùng tên \"$text\"");
            $seen[$key] = true;
            $action = isset(self::ACTIONS[$b['action'] ?? '']) ? $b['action'] : 'reply';
            $url = trim((string)($b['url'] ?? ''));
            if (str_starts_with($url, '@')) $url = 'https://t.me/' . substr($url, 1);
            $reply = trim(mb_substr((string)($b['reply'] ?? ''), 0, 4000));
            if ($action === 'link' && !preg_match('~^(https?://|tg://)\S+$~i', $url)) throw new HttpError("Nút \"$text\": link không hợp lệ (cần https://… hoặc @username)");
            if ($action === 'reply' && $reply === '') throw new HttpError("Nút \"$text\": nhập nội dung bot sẽ trả lời");
            if ($action === 'reply' && $url !== '' && !preg_match('~^(https?://|tg://)\S+$~i', $url)) throw new HttpError("Nút \"$text\": link kèm theo không hợp lệ");
            $out[] = ['text' => $text, 'row' => max(0, min(50, (int)($b['row'] ?? count($out)))), 'action' => $action,
                'reply' => $reply, 'url' => $url, 'url_label' => trim(mb_substr((string)($b['url_label'] ?? ''), 0, 40))];
        }
        $c = ['enabled' => !empty($in['enabled']), 'placeholder' => trim(mb_substr((string)($in['placeholder'] ?? ''), 0, 64)), 'buttons' => $out];
        Settings::set('bot_keyboard', json_encode($c, JSON_UNESCAPED_UNICODE));
        return $c;
    }

    /** Nút có hiện không (nút Học tập ẩn khi Học tập tắt…) */
    private static function visible(array $b): bool
    {
        if ($b['action'] === 'learn') return GroupFeature::anyEnabled('learn');
        if ($b['action'] === 'balance') return Commands::names('balance') !== [];
        if ($b['action'] === 'points') return Commands::names('pme') !== [];
        return true;
    }

    /** reply_markup cho chat riêng; null nếu tắt / không có nút */
    public static function markup(): ?array
    {
        $c = self::config();
        if (!$c['enabled']) return null;
        $rows = [];
        foreach ($c['buttons'] as $b) {
            if (!self::visible($b)) continue;
            $rows[(int)$b['row']][] = ['text' => $b['text']];
        }
        if (!$rows) return null;
        ksort($rows);
        $kb = ['keyboard' => array_values(array_map(fn($r) => array_slice($r, 0, 4), $rows)), 'resize_keyboard' => true, 'is_persistent' => true];
        if ($c['placeholder'] !== '') $kb['input_field_placeholder'] = $c['placeholder'];
        return $kb;
    }

    /** Tìm nút theo chữ người dùng vừa bấm */
    public static function match(string $text): ?array
    {
        $c = self::config();
        if (!$c['enabled'] || $text === '') return null;
        $t = mb_strtolower(trim($text));
        foreach ($c['buttons'] as $b) if (mb_strtolower($b['text']) === $t && self::visible($b)) return $b;
        return null;
    }

    /** Thực hiện nút trả lời / mở link. Trả về true nếu đã xử lý. */
    public static function respond(Telegram $api, $chatId, array $b, array $from): bool
    {
        $kb = null;
        if ($b['url'] !== '') $kb = ['inline_keyboard' => [[['text' => $b['url_label'] ?: ($b['action'] === 'link' ? $b['text'] : '🔗 Mở link'), 'url' => $b['url']]]]];
        if ($b['action'] === 'reply') {
            $text = strtr($b['reply'], ['{name}' => tg_esc(trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''))), '{id}' => (string)$from['id'],
                '{username}' => !empty($from['username']) ? '@' . tg_esc($from['username']) : '']);
            $api->trySend($chatId, $text, $kb ? ['reply_markup' => $kb] : []);
            return true;
        }
        if ($b['action'] === 'link') {
            $api->trySend($chatId, $b['reply'] !== '' ? $b['reply'] : '👇 Bấm nút bên dưới:', ['reply_markup' => $kb]);
            return true;
        }
        return false;
    }
}

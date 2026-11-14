<?php
defined('APP') or exit;

/**
 * Hàng đợi xoá tin nhắn hẹn giờ.
 * - Hẹn 0 giây: xoá ngay.
 * - Hẹn ngắn (≤ WAIT_MAX giây): webhook chờ rồi xoá luôn sau khi đã trả lời Telegram.
 * - Hẹn dài: được xoá khi có webhook tiếp theo hoặc khi cron.php chạy (nên đặt cron mỗi 1 phút).
 */
class DelQueue
{
    const WAIT_MAX = 20;
    private static int $soonest = 0;

    public static function add(Telegram $api, int $botId, $chatId, array $messageIds, int $after): void
    {
        $messageIds = array_values(array_filter($messageIds));
        if (!$messageIds) return;
        if ($after <= 0) {
            foreach ($messageIds as $id) $api->tryCall('deleteMessage', ['chat_id' => $chatId, 'message_id' => $id]);
            return;
        }
        $at = now() + $after;
        foreach ($messageIds as $id) {
            DB::insert('tg_delete_queue', ['bot_id' => $botId, 'chat_id' => $chatId, 'message_id' => $id, 'delete_at' => $at]);
        }
        if (!self::$soonest || $at < self::$soonest) self::$soonest = $at;
    }

    /** Xoá các tin đã đến hạn. Trả về số tin đã xử lý. */
    public static function process(int $limit = 100): int
    {
        $rows = DB::all('SELECT q.*, b.token FROM tg_delete_queue q LEFT JOIN tg_bots b ON b.id = q.bot_id WHERE q.delete_at <= ? ORDER BY q.delete_at LIMIT ' . (int)$limit, [now()]);
        if (!$rows) return 0;
        $apis = [];
        foreach ($rows as $r) {
            DB::run('DELETE FROM tg_delete_queue WHERE id = ?', [$r['id']]);
            if (!$r['token']) continue;
            $apis[$r['token']] ??= new Telegram($r['token']);
            $apis[$r['token']]->tryCall('deleteMessage', ['chat_id' => $r['chat_id'], 'message_id' => $r['message_id']]);
        }
        // Telegram chỉ cho xoá tin trong 48 giờ: bỏ các mục quá cũ
        DB::run('DELETE FROM tg_delete_queue WHERE delete_at < ?', [now() - 172800]);
        return count($rows);
    }

    /** Gọi cuối webhook: chờ các tin hẹn xoá ngắn trong request này rồi xoá. */
    public static function finishRequest(): void
    {
        self::process();
        $deadline = time() + self::WAIT_MAX;
        while (self::$soonest && self::$soonest <= $deadline) {
            $wait = self::$soonest - time();
            if ($wait > 0) sleep($wait);
            self::$soonest = 0;
            self::process();
            $next = (int)DB::val('SELECT MIN(delete_at) FROM tg_delete_queue');
            if ($next && $next <= $deadline) self::$soonest = $next;
        }
    }
}

<?php
require __DIR__ . '/app/bootstrap.php';

try {
    if (installed()) {
        DB::connect();
        if ((int)(Settings::get('schema_version') ?? 0) < DB::SCHEMA_VERSION) DB::migrate();
    }
    (new Api())->dispatch(trim((string)($_GET['r'] ?? 'status'), '/'));
} catch (HttpError $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    error_log('[tele-manager] ' . $e);
    json_out(['ok' => false, 'error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
}

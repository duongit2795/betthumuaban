<?php
require __DIR__ . '/app/bootstrap.php';
$siteName = 'Tele Manager';
if (installed()) {
    try { DB::connect(); $siteName = Settings::get('site_name') ?: $siteName; } catch (Throwable $e) {}
}
$v = e(APP_VERSION . '-' . substr(md5((string)@filemtime(__DIR__ . '/assets/app.js') . @filemtime(__DIR__ . '/assets/app.css')), 0, 6));
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f5f7fb" media="(prefers-color-scheme: light)">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<title><?= e($siteName) ?></title>
<link rel="icon" href="assets/icon.svg" type="image/svg+xml">
<link rel="manifest" href="assets/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css?v=<?= $v ?>">
<script>try{var t=localStorage.getItem('theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body>
<div id="app"><div class="boot"><div class="spinner"></div></div></div>
<div id="toasts" aria-live="polite"></div>
<script>window.SITE_NAME = <?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="assets/app.js?v=<?= $v ?>"></script>
</body>
</html>

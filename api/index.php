<?php

// Siapkan direktori /tmp untuk filesystem serverless Vercel (read-only environment)
$tempDirs = [
    '/tmp/views',
    '/tmp/cache',
    '/tmp/sessions',
    '/tmp/storage',
    '/tmp/storage/app',
    '/tmp/storage/app/public',
    '/tmp/storage/framework',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($tempDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Tandai lingkungan Vercel dan paksa HTTPS
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

// Fallback APP_KEY agar enkripsi cookie dan sesi Laravel tidak gagal
if (empty($_ENV['APP_KEY']) && empty(getenv('APP_KEY'))) {
    $appKey = 'base64:SmdaQpG8y5cxK49o8+mcbaO9NYxzBzEHhXVpBTZS5zU=';
    putenv('APP_KEY=' . $appKey);
    $_ENV['APP_KEY'] = $appKey;
    $_SERVER['APP_KEY'] = $appKey;
}

// Pastikan koneksi SQLite menggunakan database di /tmp yang writable
$dbConn = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');
if ($dbConn === 'sqlite') {
    $sqliteTmp = '/tmp/database.sqlite';
    $seedDb = __DIR__ . '/../database/database.sqlite';

    if (!file_exists($sqliteTmp)) {
        if (file_exists($seedDb)) {
            @copy($seedDb, $sqliteTmp);
        } else {
            @touch($sqliteTmp);
        }
    }

    putenv('DB_DATABASE=' . $sqliteTmp);
    $_ENV['DB_DATABASE'] = $sqliteTmp;
    $_SERVER['DB_DATABASE'] = $sqliteTmp;
}

// Forward request ke entry point aplikasi Laravel
require __DIR__ . '/../public/index.php';

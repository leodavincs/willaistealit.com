<?php
// Sadece LOKAL gelistirme icin: php -S localhost:8000 router.php
// Uretimde .htaccess kullanilir; bu dosya calismaz.
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/');

if ($path === '') {
    require __DIR__ . '/index.php';
    return true;
}

if (preg_match('#^/og/([a-z0-9-]+)\.png$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/og.php';
    return true;
}

// Gercek dosya varsa built-in server servis etsin
$file = __DIR__ . $path;
if (is_file($file)) {
    return false;
}

if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

if ($path === '/llms.txt') {
    require __DIR__ . '/llms.php';
    return true;
}

if (in_array($path, ['/methodology', '/sponsor', '/changelog'], true)) {
    require __DIR__ . $path . '.php';
    return true;
}

if (preg_match('#^/([a-z0-9-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/job.php';
    return true;
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;

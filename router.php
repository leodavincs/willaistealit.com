<?php
// Sadece LOKAL gelistirme icin: php -S localhost:8000 router.php
// Kural ICERMEZ — route.php'ye devreder. Uretimde .htaccess ayni seyi yapar.
declare(strict_types=1);

require_once __DIR__ . '/inc/routing.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Guvenlik GERCEK DOSYA KONTROLUNDEN ONCE (spec 1.8).
if (path_is_forbidden($path)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return true;
}

// Gercek dosya varsa built-in server servis etsin (assets, fonts, .well-known).
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/route.php';
return true;

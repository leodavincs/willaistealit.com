<?php
/**
 * Front controller — sitenin TEK giris noktasi.
 * .htaccess (uretim) ve router.php (lokal) ikisi de buraya devreder;
 * URL kurali baska hicbir yerde yasamaz.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/routes_cache.php';
require_once __DIR__ . '/inc/dispatch.php';

$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$action = dispatch_for(resolve_path($path, load_routes()));

http_response_code($action['status']);
foreach ($action['headers'] as $name => $value) {
    header($name . ': ' . $value);
}

if ($action['include'] === null) {
    exit;
}

foreach ($action['get'] as $k => $v) {
    $_GET[$k] = $v;
}

require __DIR__ . '/' . $action['include'];

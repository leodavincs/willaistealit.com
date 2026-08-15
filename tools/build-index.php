<?php
/**
 * data/jobs/*.json -> cache/index.json (client-side arama index'i)
 * Ayrica sayfa ve OG cache'ini temizler.
 *   CLI: php tools/build-index.php
 *   Web: /tools/build-index.php?key=BUILD_KEY
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!build_key_ok($_GET['key'] ?? null)) {
        http_response_code(403);
        exit("forbidden — set BUILD_KEY in inc/config.php first\n");
    }
}

$jobs  = load_all_jobs();
$index = [];

foreach ($jobs as $slug => $job) {
    $index[] = [
        's'  => $slug,
        't'  => (string)($job['title'] ?? $slug),
        'a'  => implode(' ', (array)($job['aka'] ?? [])),
        'v'  => (string)($job['verdict'] ?? 'shrinking'),
        'c'  => (string)($job['category'] ?? ''),
        'o'  => (string)($job['oneLiner'] ?? ''),
        'u'  => (string)($job['safeUntil'] ?? ''),
        'd'  => empty($job['sources']) ? 1 : 0,
    ];
}

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0775, true);
}

$payload = [
    'generated' => gmdate('c'),
    'count'     => count($index),
    'jobs'      => $index,
];

$ok = file_put_contents(INDEX_FILE, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
if ($ok === false) {
    echo "HATA: cache/index.json yazilamadi (izinleri kontrol et)\n";
    exit($cli ? 1 : 0);
}

require_once __DIR__ . '/../inc/routes_cache.php';
$conflicts = null;
$routes    = build_routes($conflicts);
if ($conflicts !== []) {
    echo "HATA: slug cakismasi\n";
    foreach ($conflicts as $c) {
        echo "  x $c\n";
    }
    exit($cli ? 1 : 0);
}
$routesOk = atomic_write(
    ROUTES_FILE,
    (string)json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);
echo $routesOk
    ? "route tablosu -> cache/routes.json\n"
    : "UYARI: routes.json yazilamadi (istek aninda uretilecek)\n";

$cleared = clear_cache();

echo count($index) . " entry indexlendi -> cache/index.json\n";
echo "$cleared cache dosyasi temizlendi\n";

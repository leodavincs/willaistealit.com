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
        'tr' => (string)($job['titleTr'] ?? ''),
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

$cleared = clear_cache();

echo count($index) . " entry indexlendi -> cache/index.json\n";
echo "$cleared cache dosyasi temizlendi\n";

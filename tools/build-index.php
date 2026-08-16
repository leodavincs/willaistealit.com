<?php
/**
 * data/jobs/*.json -> cache/index-<dil>.json (client-side arama index'i)
 * Aktif dil basina bir dosya; yayinlanmamis dil icin indeks URETILMEZ.
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

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0775, true);
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

// Indeks YALNIZCA aktif diller icin uretilir: yayinlanmamis bir dilin indeksini
// yazmak, o dil kapaliyken bile arama verisi sizdirmak olurdu.
// Sira onemli: TAZE $routes okunur. load_routes() cache'i okusaydi, activeLangs'e
// yeni bir dil eklendigi ilk build'de o dilin indeksi SESSIZCE atlanirdi.
$built = [];
foreach ((array)($routes['activeLangs'] ?? [DEFAULT_LANG]) as $lang) {
    $index = [];
    foreach (load_all_jobs($lang) as $id => $job) {
        $aka     = implode(' ', (array)($job['aka'] ?? []));
        $index[] = [
            's'  => $id,
            't'  => (string)($job['title'] ?? $id),
            'a'  => $aka,
            // Katlanmis arama metni: aka BURAYA girer, adres tablosuna DEGIL (spec 7).
            'f'  => search_fold((string)($job['title'] ?? $id) . ' ' . $aka),
            'v'  => (string)($job['verdict'] ?? 'shrinking'),
            'c'  => (string)($job['category'] ?? ''),
            'o'  => (string)($job['oneLiner'] ?? ''),
            'u'  => (string)($job['safeUntil'] ?? ''),
            'd'  => empty($job['sources']) ? 1 : 0,
        ];
    }

    $written = atomic_write(index_file($lang), (string)json_encode(
        ['generated' => gmdate('c'), 'count' => count($index), 'jobs' => $index],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ));
    if (!$written) {
        echo "HATA: cache/index-$lang.json yazilamadi (izinleri kontrol et)\n";
        exit($cli ? 1 : 0);
    }
    $built[$lang] = count($index);
}

$cleared = clear_cache();

foreach ($built as $lang => $n) {
    echo "$n entry indexlendi -> cache/index-$lang.json\n";
}
echo "$cleared cache dosyasi temizlendi\n";

<?php
/**
 * Tum OG kartlarini onceden uret (ilk ziyaretcinin beklememesi icin).
 *   CLI: php tools/build-og.php
 *   Web: /tools/build-og.php?key=BUILD_KEY
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/ogcard.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!build_key_ok($_GET['key'] ?? null)) {
        http_response_code(403);
        exit("forbidden — set BUILD_KEY in inc/config.php first\n");
    }
}

if (!og_ready()) {
    echo "HATA: GD/FreeType yok ya da fonts/ eksik\n";
    exit($cli ? 1 : 0);
}

$routes = load_routes();
$active = (array)($routes['activeLangs'] ?? [DEFAULT_LANG]);

$n = 0;
foreach ($active as $lang) {
    // Ana sayfa karti yalnizca Ingilizce yayinlanir (resolve_og, spec 5.6).
    $targets = $lang === DEFAULT_LANG ? ['home' => ''] : [];
    foreach ((array)($routes['published'] ?? []) as $id => $langs) {
        if (in_array($lang, (array)$langs, true)) {
            $targets[(string)($routes['ids'][$id][$lang] ?? $id)] = (string)$id;
        }
    }

    foreach ($targets as $slug => $id) {
        $file = og_cache_file($lang, (string)$slug);
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0775, true);
        }
        $img = og_render($id === '' ? null : load_job($id, $lang), (string)$slug, $lang);
        if (@imagepng($img, $file, 6)) {
            $n++;
        }
    }
}

echo "$n OG karti uretildi -> cache/og/<dil>/\n";

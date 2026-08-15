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
    if (($_GET['key'] ?? '') !== BUILD_KEY) {
        http_response_code(403);
        exit("forbidden\n");
    }
}

if (!og_ready()) {
    echo "HATA: GD/FreeType yok ya da fonts/ eksik\n";
    exit($cli ? 1 : 0);
}

if (!is_dir(OG_DIR)) {
    @mkdir(OG_DIR, 0775, true);
}

$n = 0;
foreach (['home' => null] + load_all_jobs() as $slug => $job) {
    $img = og_render($job, (string)$slug);
    if (@imagepng($img, OG_DIR . '/' . $slug . '.png', 6)) {
        $n++;
    }
}

echo "$n OG karti uretildi -> cache/og/\n";

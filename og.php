<?php
/**
 * Dinamik OG karti — 1200x630 PNG uretir, cache/og/ altina yazar.
 *   /og/accountant.png  -> entry karti
 *   /og/home.png        -> site geneli kart
 * Emoji GD'de guvenilir degil: verdict renk + metinle cizilir.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/ogcard.php';

$slug = (string)($_GET['slug'] ?? 'home');
$lang = (string)($_GET['lang'] ?? DEFAULT_LANG);

if (!in_array($lang, LANGS, true)) {
    http_response_code(404);
    exit;
}

if (!valid_slug($slug)) {
    http_response_code(404);
    exit;
}

// Gelen slug o dilin canonical slug'i; entry kimligi ondan ters cozulur.
$id  = $slug === 'home' ? '' : (string)(resolve_job_id($lang, $slug, load_routes()) ?? $slug);
// Kart o DILIN metnini tasir: TR karti TR basligi ve TR verdict etiketini gosterir.
$job = $slug === 'home' ? null : load_job($id, $lang);
if ($slug !== 'home' && $job === null) {
    http_response_code(404);
    exit;
}

$cacheFile = og_cache_file($lang, $slug);
// Kaynak zamani: entry'nin TUM bagimlilik dosyalarinin en yenisi (spec 8.1).
$newest = filemtime(__DIR__ . '/inc/ogcard.php');
if ($slug === 'home') {
    $newest = max($newest, filemtime(__FILE__));
} else {
    foreach (entry_dependency_files($id, $lang) as $f) {
        $newest = max($newest, filemtime($f));
    }
}

// Cache gecerliyse dogrudan servis et.
if (is_file($cacheFile) && filemtime($cacheFile) >= $newest) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=2592000');
    header('Content-Length: ' . (string)filesize($cacheFile));
    readfile($cacheFile);
    exit;
}

if (!og_ready()) {
    http_response_code(500);
    exit('OG rendering unavailable: GD with FreeType and fonts/ are required.');
}

$img = og_render($job, $slug, $lang);

if (!is_dir(dirname($cacheFile))) {
    @mkdir(dirname($cacheFile), 0775, true);
}
@imagepng($img, $cacheFile, 6);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=2592000');
imagepng($img, null, 6);

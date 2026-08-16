<?php
/**
 * /sitemap.xml — dinamik uretiliyor, build adimi yok.
 * Yeni entry eklendiginde kendiliginden buyur; Search Console'a bir kez gonderilir.
 *
 * lastmod ICERIK tarihidir. filemtime() BILEREK kullanilmaz: build, cache
 * temizligi veya sablon duzenlemesi lastmod'u oynatirsa sitemap yalan soyler
 * (spec 5.2). Tarihi bilinmeyen satir lastmod'suz yayinlanir.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$routes = load_routes();
$active = (array)($routes['activeLangs'] ?? [DEFAULT_LANG]);

// Sabit sayfalarin tarama ipuclari. Slug'i o dilde tanimli olmayan sayfa atlanir.
$pageMeta = [
    'methodology' => ['monthly', '0.7'],
    'changelog'   => ['weekly',  '0.6'],
    'landscape'   => ['weekly',  '0.8'],
    'sponsor'     => ['monthly', '0.3'],
];

$urls = [];
foreach ($active as $lang) {
    $ids = [];
    foreach ((array)($routes['published'] ?? []) as $id => $langs) {
        if (in_array($lang, (array)$langs, true)) {
            $ids[] = (string)$id;
        }
    }
    sort($ids);

    $entryDates = [];
    foreach ($ids as $id) {
        $entryDates[$id] = entry_lastmod($id, $lang);
    }
    // Ana sayfa bir listedir: icerigi tamamen entry'lerden gelir.
    $homeDate = array_filter($entryDates) === [] ? '' : max(array_filter($entryDates));

    $urls[] = ['loc'        => url_for($lang, 'home', '', $routes),
               'lastmod'    => $homeDate,
               'changefreq' => 'daily',
               'priority'   => '1.0',
               'alternates' => alternates_for('home', '', $routes)];

    foreach ($pageMeta as $key => [$freq, $prio]) {
        if (!isset($routes['pageSlugs'][$lang][$key])) {
            continue;
        }
        $urls[] = ['loc'        => url_for($lang, 'page', $key, $routes),
                   'lastmod'    => page_reviewed($lang, $key),
                   'changefreq' => $freq,
                   'priority'   => $prio,
                   'alternates' => alternates_for('page', $key, $routes)];
    }

    foreach ($ids as $id) {
        $urls[] = ['loc'        => url_for($lang, 'job', $id, $routes),
                   'lastmod'    => $entryDates[$id],
                   'changefreq' => 'monthly',
                   'priority'   => '0.9',
                   'alternates' => alternates_for('job', $id, $routes)];
    }
}

$x = static fn (string $s): string => htmlspecialchars($s, ENT_XML1);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . $x($u['loc']) . "</loc>\n";
    // Bos tarih basilmaz: tarihsiz satir, uydurma tarihli satirdan iyidir.
    if ($u['lastmod'] !== '') {
        echo '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
    }
    echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    // x-default DAHIL tum kume: HTML <head> ile sitemap ayni kumeyi tasimali,
    // yoksa Google'a celiskili sinyal gider (spec 5.2).
    foreach ($u['alternates'] as $code => $href) {
        echo '    <xhtml:link rel="alternate" hreflang="' . $x($code)
           . '" href="' . $x($href) . "\"/>\n";
    }
    echo "  </url>\n";
}
echo "</urlset>\n";

<?php
/**
 * /sitemap.xml — dinamik uretiliyor, build adimi yok.
 * Yeni entry eklendiginde kendiliginden buyur; Search Console'a bir kez gonderilir.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$jobs = load_all_jobs();

/** lastReviewed (YYYY-MM) -> W3C tarih; yoksa dosya zamani. */
$lastmod = static function (array $job, string $slug): string {
    $ym = (string)($job['lastReviewed'] ?? '');
    if (preg_match('/^\d{4}-\d{2}$/', $ym) === 1) {
        return $ym . '-01';
    }
    $newest = 0;
    foreach (entry_dependency_files($slug, DEFAULT_LANG) as $f) {
        $newest = max($newest, filemtime($f));
    }
    return date('Y-m-d', $newest > 0 ? $newest : time());
};

$newest = '1970-01-01';
foreach ($jobs as $slug => $job) {
    $d = $lastmod($job, (string)$slug);
    if ($d > $newest) {
        $newest = $d;
    }
}

$urls = [
    ['loc' => SITE_URL . '/',            'lastmod' => $newest, 'changefreq' => 'daily',   'priority' => '1.0'],
    ['loc' => SITE_URL . '/methodology', 'lastmod' => date('Y-m-d', (int)filemtime(__DIR__ . '/methodology.php')), 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => SITE_URL . '/changelog',   'lastmod' => $newest, 'changefreq' => 'weekly',  'priority' => '0.6'],
    ['loc' => SITE_URL . '/landscape',   'lastmod' => $newest, 'changefreq' => 'weekly',  'priority' => '0.8'],
    ['loc' => SITE_URL . '/sponsor',     'lastmod' => date('Y-m-d', (int)filemtime(__DIR__ . '/sponsor.php')), 'changefreq' => 'monthly', 'priority' => '0.3'],
];

foreach ($jobs as $slug => $job) {
    $urls[] = [
        'loc'        => job_url((string)$slug),
        'lastmod'    => $lastmod($job, (string)$slug),
        'changefreq' => 'monthly',
        'priority'   => '0.9',
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    echo '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
    echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";

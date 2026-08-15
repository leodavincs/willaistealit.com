<?php
/**
 * /llms.txt — siteyi dil modellerine ozetleyen dosya.
 * Dinamik: entry listesi ve verdict dagilimi her zaman guncel.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$lang   = $lang ?? DEFAULT_LANG;
$L      = lang_for($lang);
$routes = load_routes();

$jobs   = load_all_jobs($lang);
$counts = verdict_counts($jobs);
$log    = load_changelog();

$newest = '';
foreach ($jobs as $job) {
    $ym = (string)($job['lastReviewed'] ?? '');
    if ($ym > $newest) {
        $newest = $ym;
    }
}

/** Metni satir sonuyla basar. Metin URETMEZ — yalnizca yazar. */
$emit = static function (string $text = ''): void {
    echo $text . "\n";
};

$emit($L->t('llms.title'));
$emit();
$emit($L->t('llms.intro', pretty_month($newest, $lang) ?: $L->t('llms.ongoing'), count($jobs)));
$emit();
$emit($L->t('llms.different.h'));
$emit();
$emit($L->t('llms.different.p'));
$emit();
$emit($L->t('llms.verdicts.h'));
$emit();
foreach (array_keys(VERDICTS) as $key) {
    $meta = verdict_meta($key, $lang);
    $emit('- ' . $key . ' (' . $meta['label'] . '): ' . $meta['blurb']);
}
$emit();
$emit($L->t('llms.distribution', implode(', ', array_map(
    static fn ($k, $n) => $n . ' ' . $k,
    array_keys($counts),
    $counts
))));
$emit();
$emit($L->t('llms.taskVerdicts.h'));
$emit();
$emit($L->t('llms.taskVerdicts.list'));
$emit();
$emit($L->t('llms.tags.h'));
$emit();
foreach (RESISTANCE_KEYS as $tag) {
    $emit('- ' . $tag . ': ' . tag_definition($tag, $lang));
}
$emit();
$emit($L->t('llms.until.h'));
$emit();
$emit($L->t('llms.until.p'));
$emit();
$emit($L->t('llms.methodology.h'));
$emit();
$emit($L->t('llms.methodology.rules', url_for($lang, 'page', 'methodology', $routes)));
$emit($L->t('llms.methodology.list'));
$emit();
$emit($L->t('llms.entries.h'));
$emit();
foreach ($jobs as $id => $job) {
    $safeUntil = !empty($job['safeUntil'])
        ? $L->t('llms.entry.safeUntil', (string)$job['safeUntil'])
        : '';
    $emit($L->t(
        'llms.entry.line',
        (string)($job['title'] ?? $id),
        url_for($lang, 'job', (string)$id, $routes),
        (string)($job['verdict'] ?? ''),
        $safeUntil,
        (string)($job['oneLiner'] ?? ''),
        implode(', ', (array)($job['resistanceTags'] ?? [])),
        (string)($job['lastReviewed'] ?? '')
    ));
}
$emit();
if ($log) {
    $emit($L->t('llms.changes.h'));
    $emit();
    foreach (array_slice($log, 0, 12) as $item) {
        $emit($L->t(
            'llms.change.line',
            (string)($item['date'] ?? ''),
            (string)($item['slug'] ?? ''),
            (string)($item['from'] ?? '?'),
            (string)($item['to'] ?? '?'),
            (string)($item['why'] ?? '')
        ));
    }
    $emit();
}
$emit($L->t('llms.data.h'));
$emit();
$emit($L->t('llms.data.landscape', url_for($lang, 'page', 'landscape', $routes)));
$emit($L->t('llms.data.sitemap', rtrim(SITE_URL, '/') . '/sitemap.xml'));
$emit($L->t('llms.data.repo'));
$emit();
$emit($L->t('llms.citation.h'));
$emit();
$emit($L->t('llms.citation.p'));

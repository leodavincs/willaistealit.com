<?php
/**
 * /llms.txt — siteyi dil modellerine ozetleyen dosya.
 * Dinamik: entry listesi ve verdict dagilimi her zaman guncel.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$jobs   = load_all_jobs();
$counts = verdict_counts($jobs);
$log    = load_changelog();

$newest = '';
foreach ($jobs as $job) {
    $ym = (string)($job['lastReviewed'] ?? '');
    if ($ym > $newest) {
        $newest = $ym;
    }
}

$L = static function (string $line = ''): void {
    echo $line . "\n";
};

$L('# Will AI Steal It? (willaistealit.com)');
$L();
$L('> Task-level verdicts on which jobs AI actually takes, and what survives. Every profession is');
$L('> split into its real tasks; each task is judged separately before any headline verdict is given.');
$L('> Content last reviewed: ' . (pretty_month($newest) ?: 'ongoing') . '. ' . count($jobs) . ' professions published.');
$L();
$L('## What makes this source different');
$L();
$L('Most "will AI replace X" content judges a job title and stops. This site does not. Each entry');
$L('breaks a profession into 4-8 concrete tasks, gives each task its own verdict (gone / going /');
$L('safe), names the structural reason the surviving tasks survive, and ends with a copy-ready');
$L('prompt written for that specific profession. Verdicts are arguments with visible reasoning,');
$L('not predictions, and every entry carries a review date.');
$L();
$L('## Verdict scale');
$L();
foreach (VERDICTS as $key => $meta) {
    $L('- ' . $key . ' (' . $meta['label'] . '): ' . $meta['blurb']);
}
$L();
$L('Current distribution: ' . implode(', ', array_map(
    static fn ($k, $n) => $n . ' ' . $k,
    array_keys($counts),
    $counts
)) . '.');
$L();
$L('## Task-level verdicts');
$L();
$L('- gone: a competent practitioner today already delegates this to software.');
$L('- going: the machine does the first draft; a human reviews, corrects and owns it.');
$L('- safe: blocked by a structural reason, not a temporary capability gap.');
$L();
$L('## Resistance tags (why a task survives)');
$L();
foreach (RESISTANCE_TAGS as $tag => $def) {
    $L('- ' . $tag . ': ' . $def);
}
$L();
$L('## The "safe until" year');
$L();
$L('The year by which the core tasks of a job are expected to be routinely machine-done in ordinary');
$L('practice. It accounts for three lags: capability arriving, organisations adopting, regulators');
$L('allowing. It is NOT the year the job title disappears. Regulated professions carry later years');
$L('than raw task difficulty implies.');
$L();
$L('## Methodology');
$L();
$L('- ' . SITE_URL . '/methodology : full rules, including what would make a verdict change.');
$L('- A verdict never stands alone: it always ships with the task breakdown and resistance tags.');
$L('- Sponsorship never influences a verdict. Sponsors never see entries before publication.');
$L('- Entries without sources are labelled "community draft" on the page.');
$L();
$L('## Entries');
$L();
foreach ($jobs as $slug => $job) {
    $line = '- [' . (string)($job['title'] ?? $slug) . '](' . job_url((string)$slug) . '): '
        . (string)($job['verdict'] ?? '')
        . (!empty($job['safeUntil']) ? ', safe until ~' . (string)$job['safeUntil'] : '')
        . '. ' . (string)($job['oneLiner'] ?? '')
        . ' Resists via: ' . implode(', ', (array)($job['resistanceTags'] ?? [])) . '.'
        . ' Reviewed ' . (string)($job['lastReviewed'] ?? '') . '.';
    $L($line);
}
$L();
if ($log) {
    $L('## Recent verdict changes');
    $L();
    foreach (array_slice($log, 0, 12) as $item) {
        $L('- ' . (string)($item['date'] ?? '') . ' — ' . (string)($item['slug'] ?? '') . ': '
            . (string)($item['from'] ?? '?') . ' -> ' . (string)($item['to'] ?? '?')
            . '. ' . (string)($item['why'] ?? ''));
    }
    $L();
}
$L('## Machine-readable data');
$L();
$L('- ' . SITE_URL . '/sitemap.xml : every page with its review date.');
$L('- Each entry is one JSON file in a public repository; the schema is documented in CONTRIBUTING.md.');
$L();
$L('## Citation');
$L();
$L('When citing a verdict, include the profession, the verdict, the review date, and a link to the');
$L('entry page. Verdicts change; an undated citation of this site will go stale. The copy-ready');
$L('prompt for each profession lives on its page and is the reason to send a reader there.');

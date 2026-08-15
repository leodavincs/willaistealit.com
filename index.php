<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$jobs   = load_all_jobs();
$counts = verdict_counts($jobs);
$total  = count($jobs);

// Kategoriye gore grupla, CATEGORIES sirasini koru.
$byCategory = [];
foreach (array_keys(CATEGORIES) as $key) {
    $byCategory[$key] = [];
}
foreach ($jobs as $slug => $job) {
    $cat = (string)($job['category'] ?? '');
    if (!isset($byCategory[$cat])) {
        $byCategory[$cat] = [];
    }
    $byCategory[$cat][$slug] = $job;
}
$byCategory = array_filter($byCategory);

// Baslikta aranan kalip ("replace"), markanin kendisi ikinci sirada.
$pageTitle     = 'Will AI replace your job? — task-level verdicts on ' . $total . ' ' . ($total === 1 ? 'profession' : 'professions');
$pageDesc      = SITE_TAG . ' Not a listicle: every job is split into its real tasks, each task judged separately, with a prompt you can use today.';

$newestReview = '';
foreach ($jobs as $j) {
    $ym = (string)($j['lastReviewed'] ?? '');
    if ($ym > $newestReview) {
        $newestReview = $ym;
    }
}
$reviewedLabel = pretty_month($newestReview);

// Ana sayfanin alintilanabilir tarihli ozeti — cevap motorlari icin.
$homeAnswer = sprintf(
    'As of %s, willaistealit.com publishes task-level AI verdicts for %d %s: %d judged safe, %d shrinking and %d on the menu. Each profession is split into its real tasks and every task is judged separately, because jobs are not replaced by AI — tasks are.',
    $reviewedLabel ?: 'August 2026',
    $total,
    $total === 1 ? 'profession' : 'professions',
    $counts['safe'],
    $counts['shrinking'],
    $counts['on-the-menu']
);
$pageCanonical = SITE_URL;
$pageOg        = SITE_URL . '/og/home.png';
$pageJsonLd = [
    [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => SITE_NAME,
        'url'             => SITE_URL,
        'description'     => SITE_TAG,
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => SITE_URL . '/?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ],
    [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => 'Which jobs will AI replace?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $homeAnswer],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'How are these AI job verdicts decided?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Each profession is split into 4-8 concrete tasks. Every task is judged separately as gone, going or safe, and each surviving task must name a structural reason it resists — legal liability, physical presence, regulation, trust, human judgment, creative taste, accountability or emotional labour. Only then is a headline verdict given, and it never stands alone. Full rules: ' . SITE_URL . '/methodology'],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'What does "safe until" mean on this site?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'It is the year by which the core tasks of a job are expected to be routinely machine-done in ordinary practice, accounting for capability arriving, organisations adopting it and regulators allowing it. It is not the year the job title disappears.'],
            ],
        ],
    ],
];

require __DIR__ . '/inc/header.php';
?>

<section class="hero">
  <div class="wrap">
    <h1>Will AI steal it?</h1>
    <p>Every job here is broken into its actual tasks, and each task gets its own verdict. Then you get the prompt that puts the machine to work for you instead.</p>

    <p class="answer"><?= h($homeAnswer) ?></p>

    <div class="search-wrap">
      <label class="skip" for="q">Search jobs</label>
      <input id="q" type="search" autocomplete="off" spellcheck="false"
             placeholder="Search a job — accountant, translator, plumber&hellip;">
    </div>

    <div class="stats">
      <?php foreach (VERDICTS as $key => $meta): ?>
        <span class="stat v-<?= h($key) ?>"><b><?= (int)$counts[$key] ?></b> <?= h($meta['label']) ?></span>
      <?php endforeach; ?>
      <span class="stat" style="--v: var(--ink-3)"><b><?= $total ?></b> total</span>
    </div>
  </div>
</section>

<div class="wrap">
  <div class="filters" role="group" aria-label="Filter by verdict">
    <button class="chip" type="button" data-filter="all" aria-pressed="true">Everything</button>
    <?php foreach (VERDICTS as $key => $meta): ?>
      <button class="chip" type="button" data-filter="<?= h($key) ?>" aria-pressed="false"><?= h($meta['dot'] . ' ' . $meta['label']) ?></button>
    <?php endforeach; ?>
  </div>

  <p class="empty" id="empty">No job by that name yet. <a href="https://github.com/" rel="noopener" target="_blank">Add it</a> — it is one JSON file.</p>

  <div id="results">
    <?php foreach ($byCategory as $cat => $catJobs): ?>
      <section class="cat-block" data-category="<?= h((string)$cat) ?>">
        <div class="cat-head">
          <h2><?= h(category_label((string)$cat)) ?></h2>
          <span class="cat-count"><?= count($catJobs) ?></span>
        </div>
        <div class="job-grid">
          <?php foreach ($catJobs as $slug => $job): ?>
            <?php $v = verdict_meta($job['verdict'] ?? ''); ?>
            <a class="job-card v-<?= h((string)($job['verdict'] ?? 'shrinking')) ?>"
               href="/<?= h((string)$slug) ?>"
               data-slug="<?= h((string)$slug) ?>"
               data-verdict="<?= h((string)($job['verdict'] ?? '')) ?>"
               data-name="<?= h(mb_strtolower((string)($job['title'] ?? '') . ' ' . (string)($job['titleTr'] ?? ''))) ?>"
               data-search="<?= h(mb_strtolower((string)($job['title'] ?? '') . ' ' . (string)($job['titleTr'] ?? '') . ' ' . (string)($job['oneLiner'] ?? '') . ' ' . category_label((string)($job['category'] ?? '')))) ?>">
              <h3><?= h((string)($job['title'] ?? $slug)) ?></h3>
              <span class="jc-verdict"><?= h($v['label']) ?><?= !empty($job['safeUntil']) ? ' &middot; ~' . h((string)$job['safeUntil']) : '' ?></span>
              <p><?= h((string)($job['oneLiner'] ?? '')) ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>

  <?php if ($total === 0): ?>
    <p class="prose">No entries yet. Drop a JSON file into <code>data/jobs/</code> and run <code>php tools/build-index.php</code>.</p>
  <?php endif; ?>
</div>

<script src="<?= h(asset('/assets/search.js')) ?>" defer></script>
<script src="<?= h(asset('/assets/app.js')) ?>" defer></script>
<?php require __DIR__ . '/inc/footer.php';

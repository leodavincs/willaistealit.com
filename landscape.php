<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$jobs = load_all_jobs();

// Yila gore grupla. safeUntil'i olmayanlar (safe verdict) ayri bir blokta.
$byYear    = [];
$noHorizon = [];
foreach ($jobs as $slug => $job) {
    $year = (string)($job['safeUntil'] ?? '');
    if (preg_match('/^\d{4}$/', $year) !== 1) {
        $noHorizon[$slug] = $job;
        continue;
    }
    $byYear[$year][$slug] = $job;
}
ksort($byYear);

$peak = 0;
foreach ($byYear as $group) {
    $peak = max($peak, count($group));
}

// En kalabalik yil — sayfanin manseti
$busiest = '';
foreach ($byYear as $year => $group) {
    if (count($group) === $peak) {
        $busiest = (string)$year;
        break;
    }
}

$newestReview = '';
foreach ($jobs as $j) {
    $ym = (string)($j['lastReviewed'] ?? '');
    if ($ym > $newestReview) {
        $newestReview = $ym;
    }
}

$horizonCount = count($jobs) - count($noHorizon);
$answer = sprintf(
    'As of %s, %d of the %d professions on willaistealit.com carry a time horizon — the year by which their core tasks are expected to be routinely machine-done. Those horizons run from %s to %s, and %s is the most crowded year with %d %s. The remaining %d are judged safe and carry no horizon at all.',
    pretty_month($newestReview) ?: 'August 2026',
    $horizonCount,
    count($jobs),
    $byYear ? (string)array_key_first($byYear) : '—',
    $byYear ? (string)array_key_last($byYear) : '—',
    $busiest !== '' ? $busiest : '—',
    $peak,
    $peak === 1 ? 'profession' : 'professions',
    count($noHorizon)
);

$pageTitle      = 'When will AI take my job? — the timeline';
$pageDesc       = 'Every profession on willaistealit.com plotted against the year its core tasks are expected to be machine-done. ' . $horizonCount . ' horizons, from ' . ($byYear ? (string)array_key_first($byYear) : '') . ' onward.';
$pageCanonical  = SITE_URL . '/landscape';
$pageAlternates = alternates_for('page', 'landscape', load_routes());
$pageOg         = SITE_URL . '/og/home.png';
$pageJsonLd     = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Dataset',
    'name'        => 'AI job replacement timeline',
    'description' => $answer,
    'url'         => $pageCanonical,
    'creator'     => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL],
    'variableMeasured' => 'Year by which a profession\'s core tasks are expected to be routinely machine-done',
    'license'     => 'https://creativecommons.org/licenses/by/4.0/',
];

require __DIR__ . '/inc/header.php';
?>

<div class="wrap wrap-wide">

  <header class="page-head">
    <h1>When the core goes</h1>
    <p class="lede">Every profession plotted against the year its core tasks are expected to be routinely machine-done. Not the year the job title disappears — <a href="/methodology">the difference matters</a>.</p>
  </header>

  <p class="answer" style="max-width:44em"><?= h($answer) ?></p>

  <?php if ($byYear): ?>
    <section class="timeline" aria-label="Professions by expected year">
      <?php foreach ($byYear as $year => $group): ?>
        <?php
        $n = count($group);
        // Yil icindeki verdict dagilimi — cubugun segmentleri
        $split = [];
        foreach ($group as $j) {
            $k = (string)($j['verdict'] ?? 'shrinking');
            $split[$k] = ($split[$k] ?? 0) + 1;
        }
        ?>
        <div class="tl-row<?= $n === $peak ? ' is-peak' : '' ?>">
          <div class="tl-year"><?= h((string)$year) ?></div>
          <div class="tl-plot">
            <div class="tl-bar" style="width: <?= (int)round($n / max($peak, 1) * 100) ?>%"
                 title="<?= h($n . ' ' . ($n === 1 ? 'profession' : 'professions')) ?>">
              <?php foreach (['on-the-menu', 'shrinking', 'safe'] as $k): ?>
                <?php if (empty($split[$k])) { continue; } ?>
                <span class="tl-seg v-<?= h($k) ?>" style="flex: <?= (int)$split[$k] ?>"></span>
              <?php endforeach; ?>
            </div>
            <ul class="tl-jobs">
              <?php foreach ($group as $slug => $job): ?>
                <li class="v-<?= h((string)($job['verdict'] ?? 'shrinking')) ?>">
                  <a href="/<?= h((string)$slug) ?>"><?= h((string)($job['title'] ?? $slug)) ?></a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="tl-count"><?= $n ?></div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($noHorizon): ?>
    <section class="block">
      <div class="block-head">
        <h2 class="block-title">No horizon</h2>
        <p class="block-note">Structurally resistant — no year applies.</p>
      </div>
      <ul class="tl-jobs tl-safe">
        <?php foreach ($noHorizon as $slug => $job): ?>
          <li class="v-<?= h((string)($job['verdict'] ?? 'safe')) ?>">
            <a href="/<?= h((string)$slug) ?>"><?= h((string)($job['title'] ?? $slug)) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <div class="disagree" style="margin-top:44px">
    <h2>These years are estimates, and they are meant to be argued with</h2>
    <p>A horizon accounts for three lags: capability arriving, employers adopting it, and regulators allowing it. That is why regulated professions sit further right than raw task difficulty suggests. <a href="/methodology">How we set them</a> &middot; <a href="/changelog">What has moved so far</a></p>
  </div>

</div>

<?php require __DIR__ . '/inc/footer.php';

<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$lang   = $lang ?? request_lang();
$L      = lang_for($lang);
$routes = load_routes();
$jobs   = load_all_jobs($lang);

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
// Yer tutucu SIRASI dile gore degisebilir: TR karsiligi konumlu (%1$s) yazildi.
$answer = $L->t(
    'page.landscape.answer',
    pretty_month($newestReview, $lang) ?: $L->t('geo.fallbackDate'),
    $horizonCount,
    count($jobs),
    $byYear ? (string)array_key_first($byYear) : '—',
    $byYear ? (string)array_key_last($byYear) : '—',
    $busiest !== '' ? $busiest : '—',
    $peak,
    $L->t($peak === 1 ? 'page.landscape.profession' : 'page.landscape.professions'),
    count($noHorizon)
);

$pageTitle      = $L->t('page.landscape.pageTitle');
$pageDesc       = $L->t('page.landscape.pageDesc', $horizonCount,
                        $byYear ? (string)array_key_first($byYear) : '');
$pageCanonical  = url_for($lang, 'page', 'landscape', $routes);
$pageAlternates = alternates_for('page', 'landscape', $routes);
$pageOg         = SITE_URL . '/og/home.png';
$pageJsonLd     = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Dataset',
    'inLanguage'  => $lang,
    'name'        => $L->t('page.landscape.jsonLdName'),
    'description' => $answer,
    'url'         => $pageCanonical,
    'creator'     => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL],
    'variableMeasured' => $L->t('page.landscape.jsonLdVar'),
    'license'     => 'https://creativecommons.org/licenses/by/4.0/',
];

require __DIR__ . '/inc/header.php';
?>

<div class="wrap wrap-wide">

  <header class="page-head">
    <h1><?= h($L->t('page.landscape.h1')) ?></h1>
    <p class="lede"><?= h($L->t('page.landscape.ledeA')) ?><a href="<?= h(path_for($lang, 'page', 'methodology', $routes)) ?>"><?= h($L->t('page.landscape.ledeLink')) ?></a><?= h($L->t('page.landscape.ledeB')) ?></p>
  </header>

  <p class="answer" style="max-width:44em"><?= h($answer) ?></p>

  <?php if ($byYear): ?>
    <section class="timeline" aria-label="<?= h($L->t('page.landscape.timelineAria')) ?>">
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
                 title="<?= h($n . ' ' . $L->t($n === 1 ? 'page.landscape.profession' : 'page.landscape.professions')) ?>">
              <?php foreach (['on-the-menu', 'shrinking', 'safe'] as $k): ?>
                <?php if (empty($split[$k])) { continue; } ?>
                <span class="tl-seg v-<?= h($k) ?>" style="flex: <?= (int)$split[$k] ?>"></span>
              <?php endforeach; ?>
            </div>
            <ul class="tl-jobs">
              <?php foreach ($group as $slug => $job): ?>
                <li class="v-<?= h((string)($job['verdict'] ?? 'shrinking')) ?>">
                  <a href="<?= h(path_for($lang, 'job', (string)$slug, $routes)) ?>"><?= h((string)($job['title'] ?? $slug)) ?></a>
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
        <h2 class="block-title"><?= h($L->t('page.landscape.noHorizon.h')) ?></h2>
        <p class="block-note"><?= h($L->t('page.landscape.noHorizon.p')) ?></p>
      </div>
      <ul class="tl-jobs tl-safe">
        <?php foreach ($noHorizon as $slug => $job): ?>
          <li class="v-<?= h((string)($job['verdict'] ?? 'safe')) ?>">
            <a href="<?= h(path_for($lang, 'job', (string)$slug, $routes)) ?>"><?= h((string)($job['title'] ?? $slug)) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <div class="disagree" style="margin-top:44px">
    <h2><?= h($L->t('page.landscape.estimates.h')) ?></h2>
    <p><?= h($L->t('page.landscape.estimates.p')) ?><a href="<?= h(path_for($lang, 'page', 'methodology', $routes)) ?>"><?= h($L->t('page.landscape.estimates.how')) ?></a> &middot; <a href="<?= h(path_for($lang, 'page', 'changelog', $routes)) ?>"><?= h($L->t('page.landscape.estimates.moved')) ?></a></p>
  </div>

</div>

<?php require __DIR__ . '/inc/footer.php';

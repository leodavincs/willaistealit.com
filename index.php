<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$jobs   = load_all_jobs();
$counts = verdict_counts($jobs);
$total  = count($jobs);

// Kategori basina sayi ve verdict kirilimi (mini cubuk icin)
$catStats = [];
foreach ($jobs as $job) {
    $cat = (string)($job['category'] ?? '');
    if ($cat === '') {
        continue;
    }
    $catStats[$cat]['n'] = ($catStats[$cat]['n'] ?? 0) + 1;
    $v = (string)($job['verdict'] ?? 'shrinking');
    $catStats[$cat]['v'][$v] = ($catStats[$cat]['v'][$v] ?? 0) + 1;
}
// CATEGORIES sirasini koru, bos olanlari at
$catStats = array_filter(array_merge(array_fill_keys(CATEGORY_KEYS, null), $catStats));

$newestReview = '';
foreach ($jobs as $j) {
    $ym = (string)($j['lastReviewed'] ?? '');
    if ($ym > $newestReview) {
        $newestReview = $ym;
    }
}

$pageTitle = 'Will AI replace your job? — task-level verdicts on ' . $total . ' ' . ($total === 1 ? 'profession' : 'professions');
$pageDesc  = SITE_TAG . ' Not a listicle: every job is split into its real tasks, each task judged separately, with a prompt you can use today.';

$homeAnswer = sprintf(
    'As of %s, willaistealit.com publishes task-level AI verdicts for %d %s: %d judged safe, %d shrinking and %d on the menu. Each profession is split into its real tasks and every task is judged separately, because jobs are not replaced by AI — tasks are.',
    pretty_month($newestReview) ?: 'August 2026',
    $total,
    $total === 1 ? 'profession' : 'professions',
    $counts['safe'],
    $counts['shrinking'],
    $counts['on-the-menu']
);

$pageCanonical = SITE_URL . '/';
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

<div class="wrap wrap-wide">

  <section class="index-head">
    <h1>Will AI steal it?</h1>
    <p class="index-lede">Every job split into its real tasks, each task judged on its own. Find yours.</p>

    <div class="search-wrap">
      <label class="skip" for="q">Search jobs</label>
      <?php /* Ipucu native placeholder degil, kendi katmanimiz: imleci metnin
               tam ucuna koyabilmek icin. JS kapaliyken de okunur kaliyor. */ ?>
      <input id="q" type="search" autocomplete="off" spellcheck="false"
             aria-describedby="q-hint">
      <span class="q-hint" id="q-hint" aria-hidden="true">
        <span class="q-lead">Search a job — </span><span class="q-word">accountant</span><span class="q-caret"></span>
      </span>
    </div>
  </section>

  <?php /* Manzara: tek satirlik yigilmis cubuk. Renk tek basina anlam tasimiyor —
           her segmentin altinda sayisi ve adi yazili. */ ?>
  <section class="landscape" aria-labelledby="landscape-h">
    <h2 class="skip" id="landscape-h">Verdict distribution</h2>
    <div class="bar" role="img" aria-label="<?= h(sprintf('%d shrinking, %d on the menu, %d safe, of %d professions', $counts['shrinking'], $counts['on-the-menu'], $counts['safe'], $total)) ?>">
      <?php foreach (['shrinking', 'on-the-menu', 'safe'] as $key): ?>
        <?php if ($counts[$key] === 0) { continue; } ?>
        <span class="bar-seg v-<?= h($key) ?>" style="flex: <?= (int)$counts[$key] ?>"
              title="<?= h($counts[$key] . ' ' . verdict_meta($key)['label']) ?>"></span>
      <?php endforeach; ?>
    </div>
    <ul class="legend">
      <?php foreach (['shrinking', 'on-the-menu', 'safe'] as $key): ?>
        <li class="v-<?= h($key) ?>"><b><?= (int)$counts[$key] ?></b> <?= h(mb_strtolower(verdict_meta($key)['label'])) ?></li>
      <?php endforeach; ?>
      <li class="legend-total"><b><?= $total ?></b> total</li>
      <li class="legend-link"><a href="/landscape">See the timeline &rarr;</a></li>
    </ul>
  </section>

  <div class="index-body">

    <?php /* Fasetli filtre rafi. Sayilar JS ile canli guncelleniyor:
             bir fasetin sayilari, kendisi disindaki tum filtrelere gore hesaplaniyor. */ ?>
    <aside class="facets" aria-label="Filters">
      <div class="facet-group">
        <h2 class="facet-title">Verdict</h2>
        <button class="facet" type="button" data-filter="all" aria-pressed="true">
          <span class="facet-name">All</span>
          <span class="facet-n" data-count-verdict="all"><?= $total ?></span>
        </button>
        <?php foreach (array_keys(VERDICTS) as $key): $meta = verdict_meta($key); ?>
          <button class="facet v-<?= h($key) ?>" type="button" data-filter="<?= h($key) ?>" aria-pressed="false">
            <span class="facet-dot"></span>
            <span class="facet-name"><?= h(mb_strtolower($meta['label'])) ?></span>
            <span class="facet-n" data-count-verdict="<?= h($key) ?>"><?= (int)$counts[$key] ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="facet-group">
        <h2 class="facet-title">Category</h2>
        <button class="facet" type="button" data-cat="all" aria-pressed="true">
          <span class="facet-name">All categories</span>
          <span class="facet-n" data-count-cat="all"><?= $total ?></span>
        </button>
        <?php foreach ($catStats as $key => $stat): ?>
          <button class="facet facet-cat" type="button" data-cat="<?= h((string)$key) ?>" aria-pressed="false">
            <span class="facet-name"><?= h(category_label((string)$key)) ?></span>
            <span class="facet-n" data-count-cat="<?= h((string)$key) ?>"><?= (int)$stat['n'] ?></span>
            <span class="facet-bar" aria-hidden="true">
              <?php foreach (['shrinking', 'on-the-menu', 'safe'] as $vk): ?>
                <?php if (empty($stat['v'][$vk])) { continue; } ?>
                <span class="v-<?= h($vk) ?>" style="flex: <?= (int)$stat['v'][$vk] ?>"></span>
              <?php endforeach; ?>
            </span>
          </button>
        <?php endforeach; ?>
      </div>

      <button class="facet-reset" type="button" id="reset" hidden>Clear filters</button>
    </aside>

    <div class="index-main">
      <table class="index-table" id="index-table">
        <thead>
          <tr>
            <th scope="col"><button type="button" class="sort" data-sort="name" aria-pressed="true">Job</button></th>
            <th scope="col"><button type="button" class="sort" data-sort="verdict" aria-pressed="false">Verdict</button></th>
            <th scope="col" class="col-until"><button type="button" class="sort" data-sort="until" aria-pressed="false">Until</button></th>
            <th scope="col" class="col-survives">What survives</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jobs as $slug => $job): ?>
            <?php
            $v    = verdict_meta($job['verdict'] ?? '');
            $name = (string)($job['title'] ?? $slug);
            $tags = (array)($job['resistanceTags'] ?? []);
            ?>
            <tr class="row v-<?= h((string)($job['verdict'] ?? 'shrinking')) ?>"
                data-name="<?= h(mb_strtolower($name)) ?>"
                data-search="<?= h(mb_strtolower($name . ' ' . implode(' ', (array)($job['aka'] ?? [])) . ' ' . (string)($job['oneLiner'] ?? '') . ' ' . implode(' ', $tags) . ' ' . category_label($job['category'] ?? ''))) ?>"
                data-verdict="<?= h((string)($job['verdict'] ?? '')) ?>"
                data-verdict-rank="<?= (int)array_search($job['verdict'] ?? '', ['safe', 'shrinking', 'on-the-menu'], true) ?>"
                data-until="<?= h((string)($job['safeUntil'] ?? '9999')) ?>"
                data-cat="<?= h((string)($job['category'] ?? '')) ?>">
              <th scope="row" class="cell-job">
                <a href="/<?= h((string)$slug) ?>"><?= h($name) ?></a>
                <span class="cell-one"><?= h((string)($job['oneLiner'] ?? '')) ?></span>
              </th>
              <td class="cell-verdict"><span class="dot"></span><?= h(mb_strtolower($v['label'])) ?></td>
              <td class="cell-until"><?= !empty($job['safeUntil']) ? '~' . h((string)$job['safeUntil']) : '<span class="nil">no horizon</span>' ?></td>
              <td class="cell-survives"><?= h(implode(', ', array_slice($tags, 0, 2))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p class="empty" id="empty">No job by that name yet.<?php if (has_github()): ?> <a href="<?= h(github_url('/blob/main/CONTRIBUTING.md')) ?>" rel="noopener" target="_blank">Add it</a> — it is one JSON file.<?php endif; ?></p>

      <?php if ($total === 0): ?>
        <p class="prose">No entries yet. Add a directory under <code>data/jobs/&lt;id&gt;/</code> and run <code>php tools/build-index.php</code>.</p>
      <?php endif; ?>
    </div>
  </div>

  <p class="index-answer"><?= h($homeAnswer) ?></p>

</div>

<script src="<?= h(asset('/assets/search.js')) ?>" defer></script>
<script src="<?= h(asset('/assets/app.js')) ?>" defer></script>
<?php require __DIR__ . '/inc/footer.php';

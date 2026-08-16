<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$lang   = $lang ?? request_lang();
$L      = lang_for($lang);
$routes = load_routes();
$jobs   = load_all_jobs($lang);
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

$plural    = $L->t($total === 1 ? 'home.profession' : 'home.professions');
$pageTitle = $L->t('home.pageTitle', $total, $plural);
$pageDesc  = $L->t('home.pageDesc', $L->t('site.tagline'));

$homeAnswer = $L->t(
    'home.answer',
    pretty_month($newestReview, $lang) ?: $L->t('geo.fallbackDate'),
    $total,
    $plural,
    $counts['safe'],
    $counts['shrinking'],
    $counts['on-the-menu']
);

$pageCanonical  = url_for($lang, 'home', '', $routes);
$pageAlternates = alternates_for('home', '', $routes);
$pageOg         = SITE_URL . '/og/home.png';
$pageJsonLd     = [
    [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => SITE_NAME,
        'url'             => SITE_URL,
        'description'     => $L->t('site.tagline'),
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
                'name'           => $L->t('home.faq.which.q'),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $homeAnswer],
            ],
            [
                '@type'          => 'Question',
                'name'           => $L->t('home.faq.how.q'),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $L->t('home.faq.how.a', url_for($lang, 'page', 'methodology', $routes))],
            ],
            [
                '@type'          => 'Question',
                'name'           => $L->t('home.faq.until.q'),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $L->t('home.faq.until.a')],
            ],
        ],
    ],
];

require __DIR__ . '/inc/header.php';
?>

<div class="wrap wrap-wide">

  <section class="index-head">
    <h1><?= h($L->t('home.h1')) ?></h1>
    <p class="index-lede"><?= h($L->t('home.lede')) ?></p>

    <div class="search-wrap">
      <label class="skip" for="q"><?= h($L->t('home.searchLabel')) ?></label>
      <?php /* Ipucu native placeholder degil, kendi katmanimiz: imleci metnin
               tam ucuna koyabilmek icin. JS kapaliyken de okunur kaliyor. */ ?>
      <input id="q" type="search" autocomplete="off" spellcheck="false"
             aria-describedby="q-hint">
      <span class="q-hint" id="q-hint" aria-hidden="true">
        <span class="q-lead"><?= h($L->t('home.searchHint')) ?></span><span class="q-word"><?= h($L->t('home.searchExample')) ?></span><span class="q-caret"></span>
      </span>
    </div>
  </section>

  <?php /* Manzara: tek satirlik yigilmis cubuk. Renk tek basina anlam tasimiyor —
           her segmentin altinda sayisi ve adi yazili. */ ?>
  <section class="landscape" aria-labelledby="landscape-h">
    <h2 class="skip" id="landscape-h"><?= h($L->t('home.distribution')) ?></h2>
    <div class="bar" role="img" aria-label="<?= h($L->t('home.barAria', $counts['shrinking'], $counts['on-the-menu'], $counts['safe'], $total)) ?>">
      <?php foreach (['shrinking', 'on-the-menu', 'safe'] as $key): ?>
        <?php if ($counts[$key] === 0) { continue; } ?>
        <span class="bar-seg v-<?= h($key) ?>" style="flex: <?= (int)$counts[$key] ?>"
              title="<?= h($counts[$key] . ' ' . verdict_meta($key, $lang)['label']) ?>"></span>
      <?php endforeach; ?>
    </div>
    <ul class="legend">
      <?php foreach (['shrinking', 'on-the-menu', 'safe'] as $key): ?>
        <li class="v-<?= h($key) ?>"><b><?= (int)$counts[$key] ?></b> <?= h(mb_strtolower(verdict_meta($key, $lang)['label'])) ?></li>
      <?php endforeach; ?>
      <li class="legend-total"><b><?= $total ?></b> <?= h($L->t('home.total')) ?></li>
      <li class="legend-link"><a href="<?= h(path_for($lang, 'page', 'landscape', $routes)) ?>"><?= h($L->t('home.timeline')) ?> &rarr;</a></li>
    </ul>
  </section>

  <div class="index-body">

    <?php /* Fasetli filtre rafi. Sayilar JS ile canli guncelleniyor:
             bir fasetin sayilari, kendisi disindaki tum filtrelere gore hesaplaniyor. */ ?>
    <aside class="facets" aria-label="<?= h($L->t('home.filters')) ?>">
      <div class="facet-group">
        <h2 class="facet-title"><?= h($L->t('home.verdict')) ?></h2>
        <button class="facet" type="button" data-filter="all" aria-pressed="true">
          <span class="facet-name"><?= h($L->t('home.all')) ?></span>
          <span class="facet-n" data-count-verdict="all"><?= $total ?></span>
        </button>
        <?php foreach (array_keys(VERDICTS) as $key): $meta = verdict_meta($key, $lang); ?>
          <button class="facet v-<?= h($key) ?>" type="button" data-filter="<?= h($key) ?>" aria-pressed="false">
            <span class="facet-dot"></span>
            <span class="facet-name"><?= h(mb_strtolower($meta['label'])) ?></span>
            <span class="facet-n" data-count-verdict="<?= h($key) ?>"><?= (int)$counts[$key] ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="facet-group">
        <h2 class="facet-title"><?= h($L->t('home.category')) ?></h2>
        <button class="facet" type="button" data-cat="all" aria-pressed="true">
          <span class="facet-name"><?= h($L->t('home.allCategories')) ?></span>
          <span class="facet-n" data-count-cat="all"><?= $total ?></span>
        </button>
        <?php foreach ($catStats as $key => $stat): ?>
          <button class="facet facet-cat" type="button" data-cat="<?= h((string)$key) ?>" aria-pressed="false">
            <span class="facet-name"><?= h(category_label((string)$key, $lang)) ?></span>
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

      <button class="facet-reset" type="button" id="reset" hidden><?= h($L->t('home.clear')) ?></button>
    </aside>

    <div class="index-main">
      <table class="index-table" id="index-table">
        <thead>
          <tr>
            <th scope="col"><button type="button" class="sort" data-sort="name" aria-pressed="true"><?= h($L->t('home.col.job')) ?></button></th>
            <th scope="col"><button type="button" class="sort" data-sort="verdict" aria-pressed="false"><?= h($L->t('home.verdict')) ?></button></th>
            <th scope="col" class="col-until"><button type="button" class="sort" data-sort="until" aria-pressed="false"><?= h($L->t('home.col.until')) ?></button></th>
            <th scope="col" class="col-survives"><?= h($L->t('home.col.survives')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jobs as $slug => $job): ?>
            <?php
            $v    = verdict_meta($job['verdict'] ?? '', $lang);
            $name = (string)($job['title'] ?? $slug);
            $tags = (array)($job['resistanceTags'] ?? []);
            ?>
            <tr class="row v-<?= h((string)($job['verdict'] ?? 'shrinking')) ?>"
                data-name="<?= h(search_fold($name)) ?>"
                data-search="<?= h(search_fold($name . ' ' . implode(' ', (array)($job['aka'] ?? [])) . ' ' . (string)($job['oneLiner'] ?? '') . ' ' . implode(' ', $tags) . ' ' . category_label($job['category'] ?? '', $lang))) ?>"
                data-verdict="<?= h((string)($job['verdict'] ?? '')) ?>"
                data-verdict-rank="<?= (int)array_search($job['verdict'] ?? '', ['safe', 'shrinking', 'on-the-menu'], true) ?>"
                data-until="<?= h((string)($job['safeUntil'] ?? '9999')) ?>"
                data-cat="<?= h((string)($job['category'] ?? '')) ?>">
              <th scope="row" class="cell-job">
                <a href="<?= h(path_for($lang, 'job', (string)$slug, $routes)) ?>"><?= h($name) ?></a>
                <span class="cell-one"><?= h((string)($job['oneLiner'] ?? '')) ?></span>
              </th>
              <td class="cell-verdict"><span class="dot"></span><?= h(mb_strtolower($v['label'])) ?></td>
              <td class="cell-until"><?= !empty($job['safeUntil']) ? '~' . h((string)$job['safeUntil']) : '<span class="nil">' . h($L->t('home.noHorizon')) . '</span>' ?></td>
              <td class="cell-survives"><?= h(implode(', ', array_slice($tags, 0, 2))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p class="empty" id="empty"><?= h($L->t('home.empty')) ?><?php if (has_github()): ?> <a href="<?= h(github_url('/blob/main/CONTRIBUTING.md')) ?>" rel="noopener" target="_blank"><?= h($L->t('home.addIt')) ?></a> <?= h($L->t('home.oneFile')) ?><?php endif; ?></p>

      <?php if ($total === 0): ?>
        <p class="prose"><?= $L->t('home.noEntries') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <p class="index-answer"><?= h($homeAnswer) ?></p>

</div>

<?php /* Arama haritasi: tek kaynak data/search-fold.json. search.js'ten ONCE. */ ?>
<script>window.__fold = <?= json_encode(search_fold_map(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= h(asset('/assets/search.js')) ?>" defer></script>
<script src="<?= h(asset('/assets/app.js')) ?>" defer></script>
<?php require __DIR__ . '/inc/footer.php';

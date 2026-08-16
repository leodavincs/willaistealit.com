<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$log  = load_changelog();
$jobs = load_all_jobs();

$lang          = $lang ?? request_lang();
$L             = lang_for($lang);
$routes        = load_routes();
$pageTitle      = $L->t('page.changelog.title') . ' — ' . SITE_NAME;
$pageDesc       = $L->t('page.changelog.desc');
$pageCanonical  = SITE_URL . '/changelog';
$pageAlternates = alternates_for('page', 'changelog', $routes);
$pageOg         = SITE_URL . '/og/home.png';
$pageJsonLd     = [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => $L->t('page.changelog.title'),
    'description'     => $pageDesc,
    'url'             => $pageCanonical,
    'numberOfItems'   => count($log),
    'itemListElement' => array_values(array_map(static function (array $e, int $i): array {
        return [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => sprintf('%s: %s → %s', (string)($e['title'] ?? $e['slug'] ?? ''), (string)($e['from'] ?? 'new'), (string)($e['to'] ?? '')),
            'item'     => SITE_URL . '/' . (string)($e['slug'] ?? ''),
        ];
    }, $log, array_keys($log))),
];

require __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <header class="page-head">
    <h1><?= h($L->t('page.changelog.title')) ?></h1>
    <p class="lede"><?= h($L->t('page.changelog.lede')) ?></p>
  </header>

  <div style="padding-top:24px">
    <?php if (!$log): ?>
      <p class="prose"><?= h($L->t('page.changelog.empty')) ?></p>
    <?php else: ?>
      <div class="log">
        <?php foreach ($log as $e): ?>
          <?php
          $slug  = (string)($e['slug'] ?? '');
          $known = $slug !== '' && isset($jobs[$slug]);
          $to    = (string)($e['to'] ?? '');
          $from  = $e['from'] ?? null;
          $toV   = verdict_meta($to);
          ?>
          <article class="log-item v-<?= h($to !== '' ? $to : 'shrinking') ?>">
            <time class="log-date" datetime="<?= h((string)($e['date'] ?? '')) ?>"><?= h((string)($e['date'] ?? '')) ?></time>
            <div class="log-body">
              <h3>
                <?php if ($known): ?>
                  <a href="/<?= h($slug) ?>"><?= h((string)($e['title'] ?? $slug)) ?></a>
                <?php else: ?>
                  <?= h((string)($e['title'] ?? $slug)) ?>
                <?php endif; ?>
              </h3>
              <p class="log-move">
                <?php if ($from === null || $from === ''): ?>
                  <span class="to">NEW ENTRY &middot; <?= h($toV['label']) ?></span>
                <?php else: ?>
                  <span class="from"><?= h(verdict_meta((string)$from)['label']) ?></span>
                  <span>&rarr;</span>
                  <span class="to"><?= h($toV['label']) ?></span>
                <?php endif; ?>
              </p>
              <p class="log-why"><?= h((string)($e['why'] ?? '')) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="disagree" style="margin-top:34px">
      <h2><?= h($L->t('page.changelog.how')) ?></h2>
      <p><?= h($L->t('page.changelog.howText')) ?> <a href="<?= h(path_for($lang, 'page', 'methodology', $routes)) ?>"><?= h($L->t('page.changelog.rules')) ?></a></p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/inc/footer.php';

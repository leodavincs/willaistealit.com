<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$lang          = $lang ?? DEFAULT_LANG;
$L             = lang_for($lang);
$routes        = load_routes();
$pageTitle     = $L->t('methodology.pageTitle') . ' — ' . SITE_NAME;
$pageDesc      = $L->t('methodology.pageDesc');
$pageCanonical = url_for($lang, 'page', 'methodology', $routes);
$pageOg        = SITE_URL . '/og/home.png';

require __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <header class="page-head">
    <h1><?= h($L->t('methodology.pageTitle')) ?></h1>
    <p class="lede"><?= h($L->t('methodology.lede')) ?></p>
  </header>

  <div class="doc">

    <h2><?= h($L->t('methodology.oneRule.h')) ?></h2>
    <p><?= $L->t('methodology.oneRule.p') ?></p>

    <h2><?= h($L->t('methodology.verdicts.h')) ?></h2>
    <table>
      <thead>
        <tr><th><?= h($L->t('methodology.verdicts.col1')) ?></th><th><?= h($L->t('methodology.verdicts.col2')) ?></th></tr>
      </thead>
      <tbody>
        <?php foreach (array_keys(VERDICTS) as $vKey): $meta = verdict_meta($vKey, $lang); ?>
          <tr>
            <td><span class="badge v-<?= h(strtolower(str_replace(' ', '-', $meta['label']))) ?>" style="--v: <?= h($meta['color']) ?>"><?= h($meta['label']) ?></span></td>
            <td><?= h($meta['blurb']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p><?= $L->t('methodology.verdicts.note') ?></p>

    <h3><?= h($L->t('methodology.taskVerdicts.h')) ?></h3>
    <ul>
      <li><?= $L->t('methodology.taskVerdicts.gone') ?></li>
      <li><?= $L->t('methodology.taskVerdicts.going') ?></li>
      <li><?= $L->t('methodology.taskVerdicts.safe') ?></li>
    </ul>

    <h2><?= h($L->t('methodology.tags.h')) ?></h2>
    <p><?= $L->t('methodology.tags.p') ?></p>
    <table>
      <thead><tr><th><?= h($L->t('methodology.tags.col1')) ?></th><th><?= h($L->t('methodology.tags.col2')) ?></th></tr></thead>
      <tbody>
        <?php foreach (RESISTANCE_KEYS as $tag): $def = tag_definition($tag, $lang); ?>
          <tr><td><code><?= h($tag) ?></code></td><td><?= h($def) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p><?= $L->t('methodology.tags.note') ?></p>

    <h2><?= $L->t('methodology.until.h') ?></h2>
    <p><?= $L->t('methodology.until.p1') ?></p>
    <p><?= $L->t('methodology.until.p2') ?></p>
    <p><?= $L->t('methodology.until.p3') ?></p>

    <h2><?= h($L->t('methodology.refuse.h')) ?></h2>
    <ul>
      <li><?= $L->t('methodology.refuse.doom') ?></li>
      <li><?= $L->t('methodology.refuse.hedging') ?></li>
      <li><?= $L->t('methodology.refuse.sponsor') ?> <a href="<?= h(path_for($lang, 'page', 'sponsor', $routes)) ?>"><?= h($L->t('methodology.refuse.sponsorLink')) ?></a></li>
      <li><?= $L->t('methodology.refuse.precision') ?></li>
    </ul>

    <h2><?= h($L->t('methodology.sources.h')) ?></h2>
    <p><?= $L->t('methodology.sources.p') ?></p>

    <h2><?= h($L->t('methodology.change.h')) ?></h2>
    <p><?= $L->t('methodology.change.p1') ?></p>
    <p><?= h($L->t('methodology.change.p2a')) ?> <a href="<?= h(path_for($lang, 'page', 'changelog', $routes)) ?>"><?= h($L->t('methodology.change.link')) ?></a><?= $L->t('methodology.change.p2b') ?></p>

    <h2><?= h($L->t('methodology.disagree.h')) ?></h2>
    <p><?= h($L->t('methodology.disagree.p')) ?><?= has_github() ? h($L->t('methodology.disagree.repo')) : '' ?><?= h($L->t('methodology.disagree.mid')) ?><?= has_github() ? h($L->t('methodology.disagree.github')) : h($L->t('methodology.disagree.noGithub')) ?></p>
    <p><?php if (has_github()): ?><a href="<?= h(github_url()) ?>" rel="noopener" target="_blank"><?= h($L->t('methodology.disagree.contribute')) ?></a> &middot; <?php endif; ?><a href="<?= h(path_for($lang, 'home', '', $routes)) ?>"><?= h($L->t('methodology.disagree.browse')) ?></a></p>

  </div>
</div>

<?php require __DIR__ . '/inc/footer.php';

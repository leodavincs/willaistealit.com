</main>
<footer class="site-foot">
  <div class="wrap">
    <p class="foot-lead"><?= h($L->t('foot.lead')) ?><?php if (has_github()): ?> <?= h($L->t('foot.disagree')) ?> <a href="<?= h(github_url()) ?>" rel="noopener" target="_blank"><?= h($L->t('foot.openPr')) ?></a>.<?php endif; ?></p>
    <nav class="foot-nav">
      <a href="<?= h(path_for($lang, 'home', '', $routes)) ?>"><?= h($L->t('foot.allJobs')) ?></a>
      <a href="<?= h(path_for($lang, 'page', 'methodology', $routes)) ?>"><?= h($L->t('foot.howWeDecide')) ?></a>
      <a href="<?= h(path_for($lang, 'page', 'landscape', $routes)) ?>"><?= h($L->t('nav.timeline')) ?></a>
      <a href="<?= h(path_for($lang, 'page', 'changelog', $routes)) ?>"><?= h($L->t('nav.changelog')) ?></a>
      <a href="<?= h(path_for($lang, 'page', 'sponsor', $routes)) ?>"><?= h($L->t('nav.sponsor')) ?></a>
      <?php if (has_github()): ?><a href="<?= h(github_url()) ?>" rel="noopener" target="_blank"><?= h($L->t('foot.contribute')) ?></a><?php endif; ?>
    </nav>
    <p class="foot-fine"><?= h($L->t('foot.fine')) ?> <a href="<?= h(path_for($lang, 'page', 'sponsor', $routes)) ?>"><?= h($L->t('foot.readRule')) ?></a>.</p>
  </div>
</footer>
</body>
</html>

<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

http_response_code(404);
$lang      = $lang ?? request_lang();
$L         = lang_for($lang);
$pageTitle = $L->t('page.404.title') . ' — ' . SITE_NAME;
$pageDesc  = $L->t('page.404.desc');
require __DIR__ . '/inc/header.php';
?>
<div class="wrap notfound">
  <h1><?= h($L->t('page.404.h1')) ?></h1>
  <p><?= h($L->t('page.404.text')) ?></p>
  <p><a class="btn" href="<?= h(path_for($lang, 'home', '', load_routes())) ?>"><?= h($L->t('page.404.cta')) ?></a></p>
</div>
<?php require __DIR__ . '/inc/footer.php';

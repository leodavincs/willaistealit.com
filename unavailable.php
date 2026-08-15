<?php
/**
 * Bir entry'nin bu dilde henuz yayinlanmadigi durum.
 * 404 + noindex; canonical YAZILMAZ (spec 5.4). Ingilizceye SESSIZ yonlendirme yok.
 * HTTP durumu ve X-Robots-Tag basligi route.php tarafindan gonderiliyor.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$L             = lang_for($lang);
$pageTitle     = $L->t('page.unavailable.title') . ' — ' . SITE_NAME;
$pageDesc      = $L->t('page.unavailable.desc');
$pageCanonical = false;   // var olmayan URL kendini canonical gosteremez
$pageNoindex   = true;
require __DIR__ . '/inc/header.php';
?>
<div class="wrap notfound">
  <h1><?= h($L->t('page.unavailable.title')) ?></h1>
  <p><?= h($L->t('page.unavailable.text')) ?></p>
  <p><a class="btn" href="<?= h(path_for($lang, 'home', '', load_routes())) ?>"><?= h($L->t('page.404.cta')) ?></a></p>
</div>
<?php require __DIR__ . '/inc/footer.php';

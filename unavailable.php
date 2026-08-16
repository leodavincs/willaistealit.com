<?php
/**
 * Bir entry'nin bu dilde henuz yayinlanmadigi durum.
 * 404 + noindex; canonical YAZILMAZ (spec 5.4). Ingilizceye SESSIZ yonlendirme yok.
 * HTTP durumu ve X-Robots-Tag basligi route.php tarafindan gonderiliyor.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$lang          = $lang ?? request_lang();
$id            = $id   ?? (string)($_GET['id'] ?? '');
$L             = lang_for($lang);
$pageTitle     = $L->t('page.unavailable.title') . ' — ' . SITE_NAME;
$pageDesc      = $L->t('page.unavailable.desc');
$pageCanonical = false;   // var olmayan URL kendini canonical gosteremez
$pageNoindex   = true;
// $pageAlternates KURULMAZ: sayfa 404'tur, hreflang kumesine girmez ve dil
// secicide gorunmez (spec 5.4). Asagidaki liste bir BAGLANTI listesidir —
// yonlendirme degil: Ingilizceye sessiz redirect yapilmaz.
$unavailableRoutes = load_routes();
$otherLangs = $id === '' ? [] : alternates_for('job', $id, $unavailableRoutes);
unset($otherLangs['x-default'], $otherLangs[$lang]);
require __DIR__ . '/inc/header.php';
?>
<div class="wrap notfound">
  <h1><?= h($L->t('page.unavailable.title')) ?></h1>
  <p><?= h($L->t('page.unavailable.text')) ?></p>
  <?php if ($otherLangs !== []): ?>
  <p><?= h($L->t('page.unavailable.availableIn')) ?></p>
  <ul class="lang-list">
    <?php foreach ($otherLangs as $code => $href): ?>
      <li><a href="<?= h($href) ?>" hreflang="<?= h($code) ?>"><?= h($L->t('lang.' . $code)) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <p><a class="btn" href="<?= h(path_for($lang, 'home', '', $unavailableRoutes)) ?>"><?= h($L->t('page.404.cta')) ?></a></p>
</div>
<?php require __DIR__ . '/inc/footer.php';

<?php
/**
 * Bir entry'nin bu dilde henuz yayinlanmadigi durum.
 * 404 + noindex; canonical YAZILMAZ (spec 5.4). Ingilizceye SESSIZ yonlendirme yok.
 * HTTP durumu ve X-Robots-Tag basligi route.php tarafindan gonderiliyor.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$pageTitle     = 'Not available in this language yet — ' . SITE_NAME;
$pageDesc      = 'This entry has not been published in this language yet.';
$pageCanonical = false;   // var olmayan URL kendini canonical gosteremez
$pageNoindex   = true;
require __DIR__ . '/inc/header.php';
?>
<div class="wrap notfound">
  <h1>Not available in this language yet</h1>
  <p>This entry exists, but it has not been written in this language. Read it in English,
  or open a PR to add the translation.</p>
  <p><a class="btn" href="/">Browse every verdict</a></p>
</div>
<?php require __DIR__ . '/inc/footer.php';

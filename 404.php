<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

http_response_code(404);
$pageTitle = 'Not found — ' . SITE_NAME;
$pageDesc  = 'No verdict at this address yet.';
require __DIR__ . '/inc/header.php';
?>
<div class="wrap notfound">
  <h1>404</h1>
  <p>No verdict at this address. Either the job does not exist here yet — or it already got stolen.</p>
  <p><a class="btn" href="/">Browse every verdict</a></p>
</div>
<?php require __DIR__ . '/inc/footer.php';

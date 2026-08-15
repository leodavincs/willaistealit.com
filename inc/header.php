<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

/** @var string $pageTitle */
/** @var string $pageDesc */
/** @var string|null $pageOg */
/** @var string|null $pageCanonical */
$pageTitle     = $pageTitle     ?? SITE_NAME;
$pageDesc      = $pageDesc      ?? SITE_TAG;
$pageOg        = $pageOg        ?? SITE_URL . '/og/home.png';
$pageCanonical = $pageCanonical ?? SITE_URL . '/';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<?php if (!empty($pageNoindex)): ?><meta name="robots" content="noindex, follow">
<?php endif; ?>
<?php /* $pageCanonical === false: var olmayan URL kendini canonical gosteremez (spec 5.4). */ ?>
<?php if ($pageCanonical !== false): ?><link rel="canonical" href="<?= h($pageCanonical) ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
<meta property="og:title" content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($pageDesc) ?>">
<?php if ($pageCanonical !== false): ?><meta property="og:url" content="<?= h($pageCanonical) ?>">
<?php endif; ?>
<meta property="og:image" content="<?= h($pageOg) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($pageTitle) ?>">
<meta name="twitter:description" content="<?= h($pageDesc) ?>">
<meta name="twitter:image" content="<?= h($pageOg) ?>">
<link rel="stylesheet" href="<?= h(asset('/assets/style.css')) ?>">
<script>
// Temayi CSS'ten ONCE uygula ki yanlis temanin bir kare gorunmesi olmasin.
// Kayitli tercih yoksa hicbir sey yazmiyoruz: CSS prefers-color-scheme'e birakiyor.
(function () {
  try {
    var t = localStorage.getItem('theme');
    if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
  } catch (e) {}
})();
</script>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<?php if (MATOMO_SITE_ID !== '' && is_live_host()): ?>
<script>
  // disableCookies, trackPageView'den ONCE gelmeli. Sonra gelirse ilk istekte
  // cerez yazilir ve cerez onayi gerekliligi geri doner.
  var _paq = window._paq = window._paq || [];
  _paq.push(['disableCookies']);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function () {
    var u = <?= json_encode(MATOMO_URL, JSON_UNESCAPED_SLASHES) ?>;
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', <?= json_encode(MATOMO_SITE_ID) ?>]);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
  })();
</script>
<?php endif; ?>
<?php
// $pageJsonLd tek nesne ya da nesne listesi olabilir; liste ise @graph'a sarilir.
if (!empty($pageJsonLd)) {
    $graph = array_is_list($pageJsonLd) ? $pageJsonLd : [$pageJsonLd];
    $ld    = count($graph) === 1
        ? $graph[0]
        : ['@context' => 'https://schema.org', '@graph' => array_map(
            static function (array $n): array { unset($n['@context']); return $n; },
            $graph
        )];
    ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php } ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-head">
  <div class="wrap head-inner">
    <a class="brand" href="/">
      <span class="brand-mark">will<span class="brand-ai">Ai</span>stealit</span>
      <span class="brand-q">?</span>
    </a>
    <nav class="nav">
      <a href="/landscape">Timeline</a>
      <a href="/methodology">Methodology</a>
      <a href="/changelog">Changelog</a>
      <a href="/sponsor">Sponsor</a>
      <?php if (has_github()): ?><a href="<?= h(github_url()) ?>" rel="noopener" target="_blank">GitHub</a><?php endif; ?>
      <button class="theme-btn" type="button" id="theme-toggle" aria-label="Switch between light and dark" title="Light / dark">
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true">
          <circle cx="12" cy="12" r="4"/>
          <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
        </svg>
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
        </svg>
      </button>
    </nav>
  </div>
</header>
<main id="main">

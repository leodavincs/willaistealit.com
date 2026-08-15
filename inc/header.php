<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

/** @var string $pageTitle */
/** @var string $pageDesc */
/** @var string|null $pageOg */
/** @var string|null $pageCanonical */
$pageTitle     = $pageTitle     ?? SITE_NAME;
$pageDesc      = $pageDesc      ?? SITE_TAG;
$pageOg        = $pageOg        ?? SITE_URL . '/assets/og-default.png';
$pageCanonical = $pageCanonical ?? SITE_URL;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<link rel="canonical" href="<?= h($pageCanonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
<meta property="og:title" content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($pageDesc) ?>">
<meta property="og:url" content="<?= h($pageCanonical) ?>">
<meta property="og:image" content="<?= h($pageOg) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($pageTitle) ?>">
<meta name="twitter:description" content="<?= h($pageDesc) ?>">
<meta name="twitter:image" content="<?= h($pageOg) ?>">
<link rel="stylesheet" href="<?= h(asset('/assets/style.css')) ?>">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
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
      <span class="brand-mark">will<span class="brand-ai">ai</span>stealit</span>
      <span class="brand-q">?</span>
    </a>
    <nav class="nav">
      <a href="/methodology">Methodology</a>
      <a href="/changelog">Changelog</a>
      <a href="/sponsor">Sponsor</a>
      <a href="https://github.com/" rel="noopener" target="_blank">GitHub</a>
    </nav>
  </div>
</header>
<main id="main">

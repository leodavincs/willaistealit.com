<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$slug = (string)($_GET['slug'] ?? '');

if (!valid_slug($slug)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Cache hit: sablonu hic calistirma.
if (serve_page_cache($slug)) {
    exit;
}

$job = load_job($slug);
if ($job === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$v        = verdict_meta($job['verdict'] ?? '');
$vClass   = 'v-' . ($job['verdict'] ?? 'shrinking');
$title    = (string)($job['title'] ?? $slug);
$oneLiner = (string)($job['oneLiner'] ?? '');
$isDraft  = empty($job['sources']);

$geoAnswer = geo_answer($job);
$reviewed  = pretty_month((string)($job['lastReviewed'] ?? ''));
$faq       = faq_pairs($job);
$related   = related_jobs($job);

// Baslikta "replace" — arama hacmi orada. "Steal" markanin kendisinde kaliyor.
$pageTitle     = sprintf('Will AI replace %ss? — %s %s', strtolower($title), $v['dot'], $v['label']);
$pageDesc      = $oneLiner !== '' ? $oneLiner : $v['blurb'];
$pageCanonical = job_url($slug);
$pageOg        = og_url($slug);
$modified      = ($job['lastReviewed'] ?? '') !== '' ? (string)$job['lastReviewed'] . '-01' : null;

$pageJsonLd = [
    [
        '@context'     => 'https://schema.org',
        '@type'        => 'Article',
        'headline'     => $pageTitle,
        'description'  => $geoAnswer,
        'url'          => $pageCanonical,
        'image'        => $pageOg,
        'about'        => [
            '@type'       => 'Occupation',
            'name'        => $title,
            'description' => $oneLiner,
            'occupationalCategory' => category_label($job['category'] ?? ''),
            'skills'      => implode(', ', array_map(
                static fn ($t) => str_replace('-', ' ', (string)$t),
                (array)($job['resistanceTags'] ?? [])
            )),
        ],
        'dateModified' => $modified,
        'publisher'    => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL],
        'isPartOf'     => ['@type' => 'WebSite', 'name' => SITE_NAME, 'url' => SITE_URL],
    ],
    [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(static fn (array $p): array => [
            '@type'          => 'Question',
            'name'           => $p['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p['a']],
        ], $faq),
    ],
    [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'All jobs', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => category_label($job['category'] ?? ''), 'item' => SITE_URL . '/?q=' . rawurlencode(category_label($job['category'] ?? ''))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $pageCanonical],
        ],
    ],
];
$pageJsonLd[0] = array_filter($pageJsonLd[0], static fn ($x) => $x !== null);

ob_start();
require __DIR__ . '/inc/header.php';
?>

<article class="<?= h($vClass) ?>">

  <header class="job-head">
    <div class="wrap">
      <p class="crumbs"><a href="/">All jobs</a> &nbsp;/&nbsp; <?= h(category_label($job['category'] ?? '')) ?></p>

      <h1 class="job-title">Will AI replace <?= h($title) ?>s?</h1>
      <p class="job-title-tr">
        <?php if (!empty($job['titleTr'])): ?><?= h($job['titleTr']) ?> &middot; <?php endif; ?>
        <?php if ($reviewed !== ''): ?>Last reviewed: <time datetime="<?= h((string)$job['lastReviewed']) ?>"><?= h($reviewed) ?></time><?php endif; ?>
      </p>

      <div class="verdict-row">
        <span class="badge badge-lg"><?= h($v['label']) ?></span>
        <?php if (!empty($job['safeUntil'])): ?>
          <span class="safe-until">safe until <strong>~<?= h((string)$job['safeUntil']) ?></strong></span>
        <?php endif; ?>
      </div>

      <p class="one-liner"><?= h($oneLiner) ?></p>

      <?php /* Baglamsiz alintilandiginda bile ayakta duran tarihli ozet — cevap motorlari
               cevabi buradan kuruyor. Sayfanin ilk duz paragrafi olmasi kasitli. */ ?>
      <p class="answer"><?= h($geoAnswer) ?></p>

      <?php if ($isDraft): ?>
        <p class="draft-note">Community draft — no sources attached yet. Have better evidence? Open a PR.</p>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!empty($job['summary'])): ?>
  <section class="block">
    <div class="wrap">
      <div class="block-head"><h2 class="block-title">The longer version</h2></div>
      <div class="prose"><p><?= nl2br(h((string)$job['summary'])) ?></p></div>
    </div>
  </section>
  <?php endif; ?>

  <section class="block">
    <div class="wrap">
      <div class="block-head">
        <h2 class="block-title">Task breakdown</h2>
        <p class="block-note">A verdict on a whole job is a slogan. This is where the argument lives.</p>
      </div>
      <div class="tasks">
        <?php foreach (($job['tasks'] ?? []) as $task): ?>
          <?php $tv = task_verdict_meta($task['verdict'] ?? ''); ?>
          <div class="task v-<?= h(($task['verdict'] ?? '') === 'gone' ? 'on-the-menu' : (($task['verdict'] ?? '') === 'safe' ? 'safe' : 'shrinking')) ?>">
            <span class="task-name"><?= h((string)($task['name'] ?? '')) ?></span>
            <span class="pill"><?= h($tv['label']) ?></span>
            <?php if (!empty($task['note'])): ?>
              <p class="task-note"><?= h((string)$task['note']) ?></p>
            <?php endif; ?>
            <?php if (!empty($task['tags'])): ?>
              <div class="task-tags">
                <?php foreach ($task['tags'] as $tag): ?>
                  <span class="tag" title="<?= h(tag_definition((string)$tag)) ?>"><?= h((string)$tag) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($job['resistanceTags'])): ?>
  <section class="block">
    <div class="wrap">
      <div class="block-head">
        <h2 class="block-title">Why the rest resists</h2>
        <p class="block-note"><a href="/methodology">How we decide &rarr;</a></p>
      </div>
      <div class="moat">
        <?php foreach ($job['resistanceTags'] as $tag): ?>
          <div class="moat-item">
            <span class="moat-key"><?= h((string)$tag) ?></span>
            <span class="moat-def"><?= h(tag_definition((string)$tag)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($job['whatSurvives'])): ?>
        <p class="survives"><?= h((string)$job['whatSurvives']) ?></p>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($job['adaptPrompt'])): ?>
  <section class="block" id="adapt">
    <div class="wrap">
      <div class="block-head">
        <h2 class="block-title">Use it before it uses you</h2>
        <p class="block-note">Paste this into Claude or ChatGPT and start today.</p>
      </div>
      <div class="artifact">
        <div class="artifact-bar">
          <span class="artifact-label">adapt prompt &middot; <?= h($slug) ?></span>
          <button class="btn" type="button" data-copy="#adapt-prompt" data-event="prompt_copy">Copy prompt</button>
        </div>
        <pre id="adapt-prompt"><?= h((string)$job['adaptPrompt']) ?></pre>
      </div>
      <?php if (!empty($job['adaptTools'])): ?>
        <div class="tool-list">
          <?php foreach ($job['adaptTools'] as $tool): ?>
            <span class="tag"><?= h((string)$tool) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="block">
    <div class="wrap">
      <div class="block-head"><h2 class="block-title">Share the verdict</h2></div>
      <div class="share-grid">
        <div class="card">
          <p class="card-job"><?= h(strtoupper($title)) ?></p>
          <p class="card-verdict"><?= h($v['label']) ?></p>
          <?php if (!empty($job['safeUntil'])): ?>
            <p class="card-until">safe until ~<?= h((string)$job['safeUntil']) ?></p>
          <?php endif; ?>
          <p class="card-survives"><b>What survives:</b> <?= h(implode(', ', (array)($job['resistanceTags'] ?? []))) ?></p>
          <p class="card-url">willaistealit.com/<?= h($slug) ?></p>
        </div>
        <div class="share-actions">
          <a class="btn" href="https://x.com/intent/tweet?text=<?= rawurlencode(share_text($job)) ?>"
             target="_blank" rel="noopener" data-event="share_x">Post on X</a>
          <button class="btn btn-ghost" type="button" data-copy-text="<?= h(job_url($slug)) ?>" data-event="share_copy">Copy link</button>
          <a class="btn btn-ghost" href="<?= h(og_url($slug)) ?>" target="_blank" rel="noopener">Open share image</a>
          <p class="share-hint">The image above is generated for this page and shows up automatically when you paste the link.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="block">
    <div class="wrap">
      <div class="block-head"><h2 class="block-title">Receipts</h2></div>
      <div class="meta-grid">
        <div class="meta-cell">
          <div class="meta-k">Verdict</div>
          <div class="meta-v"><?= h($v['dot'] . ' ' . $v['label']) ?></div>
        </div>
        <div class="meta-cell">
          <div class="meta-k">Category</div>
          <div class="meta-v"><?= h(category_label($job['category'] ?? '')) ?></div>
        </div>
        <?php if (!empty($job['safeUntil'])): ?>
        <div class="meta-cell">
          <div class="meta-k">Safe until</div>
          <div class="meta-v">~<?= h((string)$job['safeUntil']) ?></div>
        </div>
        <?php endif; ?>
        <div class="meta-cell">
          <div class="meta-k">Last reviewed</div>
          <div class="meta-v"><?= h((string)($job['lastReviewed'] ?? 'unknown')) ?></div>
        </div>
      </div>

      <?php if (!empty($job['sources'])): ?>
        <div class="block-head" style="margin-top:24px"><h2 class="block-title">Sources</h2></div>
        <ul class="src-list">
          <?php foreach ($job['sources'] as $src): ?>
            <li><a href="<?= h((string)$src) ?>" rel="noopener nofollow" target="_blank"><?= h((string)$src) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($faq): ?>
        <div class="block-head" style="margin-top:34px"><h2 class="block-title">Straight answers</h2></div>
        <div class="faq">
          <?php foreach ($faq as $pair): ?>
            <details class="faq-item">
              <summary><?= h($pair['q']) ?></summary>
              <p><?= h($pair['a']) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="disagree">
        <h2>Think this verdict is wrong?</h2>
        <p>Good — that is the point. Every entry is one JSON file. Change it, argue in the PR, and if the argument holds the verdict changes. <a href="https://github.com/" rel="noopener" target="_blank">Edit this entry</a> &middot; <a href="/methodology">Read the methodology</a></p>
      </div>
    </div>
  </section>

  <?php if ($related): ?>
  <section class="block">
    <div class="wrap">
      <div class="block-head">
        <h2 class="block-title">Jobs on the same fault line</h2>
        <p class="block-note">Same category, or the same reason for surviving.</p>
      </div>
      <div class="job-grid">
        <?php foreach ($related as $rSlug => $rJob): ?>
          <?php $rv = verdict_meta($rJob['verdict'] ?? ''); ?>
          <a class="job-card v-<?= h((string)($rJob['verdict'] ?? 'shrinking')) ?>" href="/<?= h((string)$rSlug) ?>">
            <h3>Will AI replace <?= h((string)($rJob['title'] ?? $rSlug)) ?>s?</h3>
            <span class="jc-verdict"><?= h($rv['label']) ?><?= !empty($rJob['safeUntil']) ? ' &middot; ~' . h((string)$rJob['safeUntil']) : '' ?></span>
            <p><?= h((string)($rJob['oneLiner'] ?? '')) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

</article>

<script src="<?= h(asset('/assets/app.js')) ?>" defer></script>
<?php
require __DIR__ . '/inc/footer.php';
$html = (string)ob_get_clean();
write_page_cache($slug, $html);
echo $html;

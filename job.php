<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$slug = (string)($_GET['slug'] ?? '');
$lang = (string)($_GET['lang'] ?? DEFAULT_LANG);

if (!valid_slug($slug)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Cache hit: sablonu hic calistirma.
if (serve_page_cache($slug, $lang)) {
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
$evidence = evidence_note($job, $lang);
$L        = lang_for($lang);
$routes   = load_routes();

$geoAnswer = geo_answer($job, $lang);
$reviewed  = pretty_month((string)($job['lastReviewed'] ?? ''), $lang);
$faq       = faq_pairs($job, $lang);
$related   = related_jobs($job);

// Baslikta "replace" — arama hacmi orada. "Steal" markanin kendisinde kaliyor.
$pageTitle      = $L->t('job.pageTitle', mb_strtolower($title), $v['dot'], $v['label']);
$pageDesc       = $oneLiner !== '' ? $oneLiner : $v['blurb'];
$pageCanonical  = job_url($slug, $lang);
$pageAlternates = alternates_for('job', $slug, $routes);
$pageOg         = og_url($slug, $lang);
$modified       = ($job['lastReviewed'] ?? '') !== '' ? (string)$job['lastReviewed'] . '-01' : null;

// Breadcrumb ogesi kaynak kimligidir; kok adres mevcut ciktida egik cizgisiz
// yaziliyor. rtrim ile tek kaynagi (url_for) koruyup bicimi ayni tutuyoruz.
$homeUrl = rtrim(url_for($lang, 'home', '', $routes), '/');

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
            'occupationalCategory' => category_label($job['category'] ?? '', $lang),
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
            ['@type' => 'ListItem', 'position' => 1, 'name' => $L->t('job.allJobs'), 'item' => $homeUrl],
            ['@type' => 'ListItem', 'position' => 2, 'name' => category_label($job['category'] ?? '', $lang), 'item' => $homeUrl . '/?q=' . rawurlencode(category_label($job['category'] ?? ''))],
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
      <p class="crumbs"><a href="<?= h(path_for($lang, 'home', '', $routes)) ?>"><?= h($L->t('job.allJobs')) ?></a> &nbsp;/&nbsp; <?= h(category_label($job['category'] ?? '', $lang)) ?></p>

      <h1 class="job-title"><?= h($L->t('job.h1', $title)) ?></h1>
      <p class="job-title-tr">
        <?php if ($reviewed !== ''): ?><?= h($L->t('job.lastReviewed')) ?> <time datetime="<?= h((string)$job['lastReviewed']) ?>"><?= h($reviewed) ?></time><?php endif; ?>
      </p>

      <div class="verdict-row">
        <span class="badge badge-lg"><?= h($v['label']) ?></span>
        <?php if (!empty($job['safeUntil'])): ?>
          <span class="safe-until"><?= h($L->t('job.safeUntilLabel')) ?> <strong>~<?= h((string)$job['safeUntil']) ?></strong></span>
        <?php endif; ?>
      </div>

      <p class="one-liner"><?= h($oneLiner) ?></p>

      <?php /* Baglamsiz alintilandiginda bile ayakta duran tarihli ozet — cevap motorlari
               cevabi buradan kuruyor. Sayfanin ilk duz paragrafi olmasi kasitli. */ ?>
      <p class="answer"><?= h($geoAnswer) ?></p>

      <?php if ($evidence !== null): ?>
        <p class="draft-note is-<?= h($evidence['level']) ?>">
          <strong><?= h($evidence['label']) ?></strong> — <?= h($evidence['text']) ?>
        </p>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!empty($job['summary'])): ?>
  <section class="block">
    <div class="wrap">
      <div class="block-head"><h2 class="block-title"><?= h($L->t('job.longerVersion')) ?></h2></div>
      <div class="prose"><p><?= nl2br(h((string)$job['summary'])) ?></p></div>
    </div>
  </section>
  <?php endif; ?>

  <section class="block">
    <div class="wrap">
      <div class="block-head">
        <h2 class="block-title"><?= h($L->t('job.taskBreakdown')) ?></h2>
        <p class="block-note"><?= h($L->t('job.taskBreakdown.note')) ?></p>
      </div>
      <div class="tasks">
        <?php foreach (($job['tasks'] ?? []) as $task): ?>
          <?php $tv = task_verdict_meta($task['verdict'] ?? '', $lang); ?>
          <div class="task v-<?= h(($task['verdict'] ?? '') === 'gone' ? 'on-the-menu' : (($task['verdict'] ?? '') === 'safe' ? 'safe' : 'shrinking')) ?>">
            <span class="task-name"><?= h((string)($task['name'] ?? '')) ?></span>
            <span class="pill"><?= h($tv['label']) ?></span>
            <?php if (!empty($task['note'])): ?>
              <p class="task-note"><?= h((string)$task['note']) ?></p>
            <?php endif; ?>
            <?php if (!empty($task['tags'])): ?>
              <div class="task-tags">
                <?php foreach ($task['tags'] as $tag): ?>
                  <span class="tag" title="<?= h(tag_definition((string)$tag, $lang)) ?>"><?= h((string)$tag) ?></span>
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
        <h2 class="block-title"><?= h($L->t('job.resists')) ?></h2>
        <p class="block-note"><a href="<?= h(path_for($lang, 'page', 'methodology', $routes)) ?>"><?= h($L->t('job.howWeDecide')) ?> &rarr;</a></p>
      </div>
      <div class="moat">
        <?php foreach ($job['resistanceTags'] as $tag): ?>
          <div class="moat-item">
            <span class="moat-key"><?= h((string)$tag) ?></span>
            <span class="moat-def"><?= h(tag_definition((string)$tag, $lang)) ?></span>
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
        <h2 class="block-title"><?= h($L->t('job.adapt.title')) ?></h2>
        <p class="block-note"><?= h($L->t('job.adapt.note')) ?></p>
      </div>
      <div class="artifact">
        <div class="artifact-bar">
          <span class="artifact-label"><?= h($L->t('job.adapt.label')) ?> &middot; <?= h($slug) ?></span>
          <button class="btn" type="button" data-copy="#adapt-prompt" data-event="prompt_copy"><?= h($L->t('job.adapt.copy')) ?></button>
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
      <div class="block-head"><h2 class="block-title"><?= h($L->t('job.share.title')) ?></h2></div>
      <div class="share-grid">
        <div class="card">
          <p class="card-job"><?= h(strtoupper($title)) ?></p>
          <p class="card-verdict"><?= h($v['label']) ?></p>
          <?php if (!empty($job['safeUntil'])): ?>
            <p class="card-until"><?= h($L->t('job.share.until', (string)$job['safeUntil'])) ?></p>
          <?php endif; ?>
          <p class="card-survives"><b><?= h($L->t('job.share.survives')) ?></b> <?= h(implode(', ', (array)($job['resistanceTags'] ?? []))) ?></p>
          <p class="card-url">willaistealit.com/<?= h($slug) ?></p>
        </div>
        <div class="share-actions">
          <a class="btn" href="https://x.com/intent/tweet?text=<?= rawurlencode(share_text($job, $lang)) ?>"
             target="_blank" rel="noopener" data-event="share_x"><?= h($L->t('job.share.postX')) ?></a>
          <button class="btn btn-ghost" type="button" data-copy-text="<?= h(job_url($slug, $lang)) ?>" data-event="share_copy"><?= h($L->t('job.share.copyLink')) ?></button>
          <a class="btn btn-ghost" href="<?= h(og_url($slug, $lang)) ?>" target="_blank" rel="noopener"><?= h($L->t('job.share.openImage')) ?></a>
          <p class="share-hint"><?= h($L->t('job.share.hint')) ?></p>
        </div>
      </div>
    </div>
  </section>

  <section class="block">
    <div class="wrap">
      <div class="block-head"><h2 class="block-title"><?= h($L->t('job.receipts')) ?></h2></div>
      <div class="meta-grid">
        <div class="meta-cell">
          <div class="meta-k"><?= h($L->t('job.meta.verdict')) ?></div>
          <div class="meta-v"><?= h($v['label']) ?></div>
        </div>
        <div class="meta-cell">
          <div class="meta-k"><?= h($L->t('job.meta.category')) ?></div>
          <div class="meta-v"><?= h(category_label($job['category'] ?? '', $lang)) ?></div>
        </div>
        <?php if (!empty($job['safeUntil'])): ?>
        <div class="meta-cell">
          <div class="meta-k"><?= h($L->t('job.meta.safeUntil')) ?></div>
          <div class="meta-v">~<?= h((string)$job['safeUntil']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($job['evidenceStrength'])): ?>
        <div class="meta-cell">
          <div class="meta-k"><?= h($L->t('job.meta.evidence')) ?></div>
          <div class="meta-v"><?= h((string)$job['evidenceStrength']) ?></div>
        </div>
        <?php endif; ?>
        <div class="meta-cell">
          <div class="meta-k"><?= h($L->t('job.meta.lastReviewed')) ?></div>
          <div class="meta-v"><?= h((string)($job['lastReviewed'] ?? $L->t('job.meta.unknown'))) ?></div>
        </div>
      </div>

      <?php if (!empty($job['sources'])): ?>
        <div class="block-head" style="margin-top:24px"><h2 class="block-title"><?= h($L->t('job.sources')) ?></h2></div>
        <ul class="src-list">
          <?php foreach ($job['sources'] as $src): ?>
            <li><a href="<?= h((string)$src) ?>" rel="noopener nofollow" target="_blank"><?= h((string)$src) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($faq): ?>
        <div class="block-head" style="margin-top:34px"><h2 class="block-title"><?= h($L->t('job.faq')) ?></h2></div>
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
        <h2><?= h($L->t('job.disagree.title')) ?></h2>
        <p><?= h($L->t('job.disagree.text')) ?>
        <?php if (has_github()): ?><a href="<?= h(github_url('/blob/main/data/jobs/' . $slug . '/' . $lang . '.json')) ?>" rel="noopener" target="_blank"><?= h($L->t('job.disagree.edit')) ?></a> &middot; <?php endif; ?><a href="<?= h(path_for($lang, 'page', 'methodology', $routes)) ?>"><?= h($L->t('job.disagree.method')) ?></a></p>
      </div>
    </div>
  </section>

  <?php if ($related): ?>
  <section class="block">
    <div class="wrap">
      <div class="block-head">
        <h2 class="block-title"><?= h($L->t('job.related.title')) ?></h2>
        <p class="block-note"><?= h($L->t('job.related.note')) ?></p>
      </div>
      <div class="job-grid">
        <?php foreach ($related as $rSlug => $rJob): ?>
          <?php $rv = verdict_meta($rJob['verdict'] ?? '', $lang); ?>
          <a class="job-card v-<?= h((string)($rJob['verdict'] ?? 'shrinking')) ?>" href="<?= h(path_for($lang, 'job', (string)$rSlug, $routes)) ?>">
            <h3><?= h($L->t('job.h1', (string)($rJob['title'] ?? $rSlug))) ?></h3>
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
write_page_cache($slug, $html, $lang);
echo $html;

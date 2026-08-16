<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$lang   = $lang ?? request_lang();
$L      = lang_for($lang);
$routes = load_routes();
$sent   = false;
$error  = '';

// Waitlist: veritabani yok, satir satir JSONL dosyasi.
// data/ klasoru .htaccess ile disaridan kapali.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $trap  = trim((string)($_POST['company'] ?? '')); // honeypot: bot doldurur

    if ($trap !== '') {
        $sent = true; // bot: sessizce yut
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = $L->t('page.sponsor.err.email');
    } else {
        $line = json_encode([
            'email' => $email,
            'note'  => mb_substr(trim((string)($_POST['note'] ?? '')), 0, 500),
            'at'    => gmdate('c'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $ok = @file_put_contents(ROOT . '/data/waitlist.jsonl', $line . "\n", FILE_APPEND | LOCK_EX);
        if ($ok === false) {
            $error = has_contact()
                ? $L->t('page.sponsor.err.saveContact', CONTACT_EMAIL)
                : $L->t('page.sponsor.err.save');
        } else {
            $sent = true;
        }
    }
}

$pageTitle      = $L->t('page.sponsor.pageTitle') . ' — ' . SITE_NAME;
$pageDesc       = $L->t('page.sponsor.pageDesc');
$pageCanonical  = url_for($lang, 'page', 'sponsor', $routes);
$pageAlternates = alternates_for('page', 'sponsor', $routes);
$pageOg         = SITE_URL . '/og/home.png';

$jobs = load_all_jobs($lang);

require __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <header class="page-head">
    <h1><?= h($L->t('page.sponsor.h1')) ?></h1>
    <p class="lede"><?= h($L->t('page.sponsor.lede')) ?></p>
  </header>

  <div class="doc">

    <h2><?= h($L->t('page.sponsor.rule.h')) ?></h2>
    <p><?= $L->t('page.sponsor.rule.p1') ?></p>
    <p><?= h($L->t('page.sponsor.rule.p2')) ?></p>

    <h2><?= h($L->t('page.sponsor.fits.h')) ?></h2>
    <p><?= h($L->t('page.sponsor.fits.p')) ?></p>
    <ul>
      <li><?= $L->t('page.sponsor.fits.reskilling') ?></li>
      <li><?= $L->t('page.sponsor.fits.tools') ?></li>
      <li><?= $L->t('page.sponsor.fits.career') ?></li>
      <li><?= $L->t('page.sponsor.fits.saas') ?></li>
    </ul>

    <h2><?= h($L->t('page.sponsor.how.h')) ?></h2>
    <ul>
      <li><?= $L->t('page.sponsor.how.fixed') ?></li>
      <li><?= $L->t('page.sponsor.how.slots') ?></li>
      <li><?= $L->t('page.sponsor.how.rate') ?></li>
      <li><?= $L->t('page.sponsor.how.plain') ?></li>
    </ul>

    <h2><?= h($L->t('page.sponsor.today.h')) ?></h2>
    <p><?= h($L->t('page.sponsor.today.p', count($jobs),
                   $L->t(count($jobs) === 1 ? 'page.sponsor.entry' : 'page.sponsor.entries'))) ?></p>

    <h2><?= h($L->t($sent ? 'page.sponsor.list.hSent' : 'page.sponsor.list.h')) ?></h2>
    <?php if ($sent): ?>
      <p><?= h($L->t('page.sponsor.list.sent')) ?></p>
    <?php else: ?>
      <p><?= h($L->t('page.sponsor.list.p')) ?></p>
      <?php if ($error !== ''): ?>
        <p style="color:#ef4444"><?= h($error) ?></p>
      <?php endif; ?>
      <?php /* action DIL BAGLAMINI korur: TR formu /tr/sponsorluk'a gonderir. */ ?>
      <form class="waitlist" method="post" action="<?= h(path_for($lang, 'page', 'sponsor', $routes)) ?>">
        <label class="skip" for="email"><?= h($L->t('page.sponsor.list.label')) ?></label>
        <input id="email" name="email" type="email" required placeholder="you@company.com" value="">
        <input type="text" name="company" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <button class="btn" type="submit"><?= h($L->t('page.sponsor.list.submit')) ?></button>
      </form>
      <p style="font-size:13px;color:var(--ink-3)"><?= h($L->t('page.sponsor.list.note')) ?></p>
    <?php endif; ?>

    <?php if (has_contact()): ?>
      <h2><?= h($L->t('page.sponsor.talk.h')) ?></h2>
      <p><?= $L->t('page.sponsor.talk.p',
                   '<a href="mailto:' . h(CONTACT_EMAIL) . '">' . h(CONTACT_EMAIL) . '</a>') ?></p>
    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/inc/footer.php';

<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$sent  = false;
$error = '';

// Waitlist: veritabani yok, satir satir JSONL dosyasi.
// data/ klasoru .htaccess ile disaridan kapali.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $trap  = trim((string)($_POST['company'] ?? '')); // honeypot: bot doldurur

    if ($trap !== '') {
        $sent = true; // bot: sessizce yut
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'That does not look like a working email address.';
    } else {
        $line = json_encode([
            'email' => $email,
            'note'  => mb_substr(trim((string)($_POST['note'] ?? '')), 0, 500),
            'at'    => gmdate('c'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $ok = @file_put_contents(ROOT . '/data/waitlist.jsonl', $line . "\n", FILE_APPEND | LOCK_EX);
        if ($ok === false) {
            $error = 'Could not save that — email hello@willaistealit.com instead.';
        } else {
            $sent = true;
        }
    }
}

$pageTitle     = 'Sponsor — ' . SITE_NAME;
$pageDesc      = 'Fixed-price placements on willaistealit.com. No subscriptions, no verdict influence, ever.';
$pageCanonical = SITE_URL . '/sponsor';
$pageOg        = SITE_URL . '/og/home.png';

$jobs = load_all_jobs();

require __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <header class="page-head">
    <h1>Sponsor</h1>
    <p class="lede">Not selling yet — traffic first. If you want one of the first slots, get on the list and you will hear from us before anyone else.</p>
  </header>

  <div class="doc">

    <h2>The rule that comes first</h2>
    <p><strong>Sponsorship never touches a verdict.</strong> Sponsors do not see entries before publication, cannot request a change to one, and cannot buy a mention inside an entry. If a sponsor's own profession is on the site, its verdict is written exactly as if they were not paying. This is not negotiable, and any sponsor who asks is refunded and dropped.</p>
    <p>The site is worth sponsoring precisely because people trust the verdicts. Selling that trust would destroy the thing you are buying access to.</p>

    <h2>Who this fits</h2>
    <p>Every visitor arrives asking whether their job is safe, and leaves looking for what to do about it. That makes this a natural fit for:</p>
    <ul>
      <li><strong>Reskilling and education platforms</strong> — the reader has just been told their role is narrowing and is actively looking for the next skill.</li>
      <li><strong>AI tools</strong> — every entry ends with a prompt and a tool list. Readers are in "what do I use" mode.</li>
      <li><strong>Career and hiring platforms</strong> — the audience is mid-career professionals reconsidering their trajectory.</li>
      <li><strong>Automation SaaS</strong> — the buyer is the person automating the tasks we just marked <code>going</code>.</li>
    </ul>

    <h2>How it will work</h2>
    <ul>
      <li><strong>Fixed price, one-off.</strong> A 30-day placement. No subscription, no auto-renewal, no invoice you forgot about.</li>
      <li><strong>8–10 slots, then it is full.</strong> Scarcity is the product; a wall of logos is worth nothing to anyone.</li>
      <li><strong>Your rate never rises.</strong> Prices go up as traffic does — but only for new sponsors. Whatever you pay first is what you keep paying.</li>
      <li><strong>Plain placements.</strong> Name, one line, link. No pop-ups, no interstitials, no tracking scripts we did not write.</li>
    </ul>

    <h2>Where it stands today</h2>
    <p><?= count($jobs) ?> published <?= count($jobs) === 1 ? 'entry' : 'entries' ?>, growing weekly. Slots open once traffic is meaningful enough that a placement is actually worth your money. Until then this page sells nothing.</p>

    <h2><?= $sent ? 'You are on the list' : 'Get first refusal' ?></h2>
    <?php if ($sent): ?>
      <p>Noted — you will hear from us before slots go public. No newsletter, no drip sequence: one email, when there is something to sell.</p>
    <?php else: ?>
      <p>Leave an email and we will contact you when slots open, before they are announced publicly. That is the entire commitment — no list, no newsletter.</p>
      <?php if ($error !== ''): ?>
        <p style="color:#ef4444"><?= h($error) ?></p>
      <?php endif; ?>
      <form class="waitlist" method="post" action="/sponsor">
        <label class="skip" for="email">Email address</label>
        <input id="email" name="email" type="email" required placeholder="you@company.com" value="">
        <input type="text" name="company" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <button class="btn" type="submit">Join the list</button>
      </form>
      <p style="font-size:13px;color:var(--ink-3)">Stored as a line in a file. Used for one thing: telling you when slots open.</p>
    <?php endif; ?>

    <h2>Rather just talk?</h2>
    <p>Write to <a href="mailto:hello@willaistealit.com">hello@willaistealit.com</a> with what you are selling and who you want to reach.</p>

  </div>
</div>

<?php require __DIR__ . '/inc/footer.php';

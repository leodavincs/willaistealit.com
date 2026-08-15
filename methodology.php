<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$pageTitle     = 'How we decide — ' . SITE_NAME;
$pageDesc      = 'The rules behind every verdict on willaistealit.com: task-level analysis, resistance tags, and what would make us change our mind.';
$pageCanonical = SITE_URL . '/methodology';
$pageOg        = SITE_URL . '/og/home.png';

require __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <header class="page-head">
    <h1>How we decide</h1>
    <p class="lede">A verdict you cannot defend is worthless. Here is exactly how each one is built, and what would make us change it.</p>
  </header>

  <div class="doc">

    <h2>The one rule</h2>
    <p><strong>Jobs are not atoms. Tasks are.</strong> Nobody is replaced by AI — tasks are. So we never start from the job title. We split the job into 4–8 real tasks, judge each one separately, and only then roll them up into a headline verdict. If the headline ever contradicts the task breakdown below it, the task breakdown wins and the headline gets fixed.</p>

    <h2>The three verdicts</h2>
    <table>
      <thead>
        <tr><th>Verdict</th><th>What it means</th></tr>
      </thead>
      <tbody>
        <?php foreach (array_keys(VERDICTS) as $vKey): $meta = verdict_meta($vKey); ?>
          <tr>
            <td><span class="badge v-<?= h(strtolower(str_replace(' ', '-', $meta['label']))) ?>" style="--v: <?= h($meta['color']) ?>"><?= h($meta['label']) ?></span></td>
            <td><?= h($meta['blurb']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p><strong>A verdict never stands alone.</strong> It always ships with the task breakdown, the resistance tags, and — for anything that is not <code>safe</code> — a year. If we cannot write those, we do not publish the entry.</p>

    <h3>Task-level verdicts</h3>
    <ul>
      <li><strong>gone</strong> — a competent practitioner today already delegates this to software. Not "could in theory": actually does.</li>
      <li><strong>going</strong> — the machine does the first draft; the human reviews, corrects and owns it. Hours collapse, the task survives in reduced form.</li>
      <li><strong>safe</strong> — blocked by a structural reason, not by a temporary capability gap. Every <code>safe</code> task must name at least one resistance tag.</li>
    </ul>

    <h2>Resistance tags</h2>
    <p>The question is never "is AI smart enough?" — it is <strong>"what structurally stops a machine from taking this, even if it is smart enough?"</strong> Capability gaps close. Structural walls do not, or close far more slowly. These are the walls we recognise:</p>
    <table>
      <thead><tr><th>Tag</th><th>The wall</th></tr></thead>
      <tbody>
        <?php foreach (RESISTANCE_KEYS as $tag): $def = tag_definition($tag); ?>
          <tr><td><code><?= h($tag) ?></code></td><td><?= h($def) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p>An entry carries 1–3 tags, strongest first. If the only argument for a job's survival is "AI is not good enough yet", that is not a tag — that is a countdown, and the entry gets a year instead.</p>

    <h2>The "safe until" year</h2>
    <p>This is the most provocative number on the site, so here is what it actually claims: <strong>the year by which we expect the core tasks of this job to be routinely machine-done in ordinary practice</strong> — not the year the job title disappears, and not the year the technology first becomes possible in a demo.</p>
    <p>It accounts for three lags that people forget: capability has to arrive, organisations have to adopt it, and regulation has to allow it. That is why regulated professions get later dates than their raw task difficulty suggests.</p>
    <p>It is an estimate, and it is meant to be argued with. That is the point.</p>

    <h2>What we refuse to do</h2>
    <ul>
      <li><strong>No doom for engagement.</strong> Every entry, including the red ones, ends with something you can use. If a verdict leaves you with nothing to do, the entry is incomplete.</li>
      <li><strong>No hedging into meaninglessness.</strong> "It depends" is not a verdict. We commit, and we publish the reasoning so you can attack it.</li>
      <li><strong>Sponsorship never touches a verdict.</strong> No sponsor has ever seen an entry before publication, and none ever will. <a href="/sponsor">The full rule is here.</a></li>
      <li><strong>No fake precision.</strong> We do not put a percentage on a job. We do not have that number and neither does anyone else quoting one at you.</li>
    </ul>

    <h2>Sources and drafts</h2>
    <p>Entries reviewed against published labour data, regulatory rules or industry evidence carry a <strong>Sources</strong> section. Entries without one are marked <strong>community draft</strong> on the page — the argument may still be good, but nobody has attached evidence to it yet. Attaching that evidence is the single most useful contribution you can make.</p>

    <h2>How verdicts change</h2>
    <p>Every entry carries a <code>lastReviewed</code> date, shown at the top of the page. When a major model or product ships, we re-open the affected entries and move verdicts if the task breakdown actually changed — not because the news cycle is loud. A verdict moves when a specific <em>task</em> changes state, and the headline follows.</p>
    <p>Every change is recorded and dated in the <a href="/changelog">verdict changelog</a>, because a site that quietly rewrites its own predictions is not worth reading. If you are citing a verdict, cite it with its review date — it will go stale, and that is by design.</p>

    <h2>Disagree</h2>
    <p>Each entry is a single JSON file<?= has_github() ? ' in a public repository' : '' ?>. If you do the job we wrote about and think we got it wrong, you are a better source than we are<?= has_github() ? ': open a pull request, change the verdict, and make the argument in the description. If it holds, it ships.' : '. Tell us, with the task that we got wrong.' ?></p>
    <p><?php if (has_github()): ?><a href="<?= h(github_url()) ?>" rel="noopener" target="_blank">Contribute on GitHub</a> &middot; <?php endif; ?><a href="/">Browse every verdict</a></p>

  </div>
</div>

<?php require __DIR__ . '/inc/footer.php';

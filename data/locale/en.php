<?php
/**
 * Ingilizce metin tablosu.
 * Sabit kaynakli degerler (verdict, tag, kategori) inc/config.php'den URETILEREK
 * tasindi — elle kopyalanmadi, transkripsiyon riski yok. Faz 3D'de config'deki
 * kopyalari kalkacak ve tek kaynak burasi olacak.
 * Duz key => string; ic ice dizi yok, yer tutucu icin sprintf kullanilir.
 */
declare(strict_types=1);

return [
    // --- site ---
    'site.tagline'                     => 'Task-level verdicts on which jobs AI actually takes.',

    // --- verdict etiketleri ve aciklamalari (inc/config.php dan tasindi) ---
    'verdict.safe.label'               => 'SAFE',
    'verdict.safe.blurb'               => 'The core of this job is structurally resistant. AI becomes a tool, not a replacement.',
    'verdict.shrinking.label'          => 'SHRINKING',
    'verdict.shrinking.blurb'          => 'Significant parts are being automated. The role narrows and shifts — it does not vanish.',
    'verdict.on-the-menu.label'        => 'ON THE MENU',
    'verdict.on-the-menu.blurb'        => 'The core tasks are going, and what is left will not support as many people. A time horizon applies.',

    // --- gorev verdict etiketleri ---
    'task.gone.label'                  => 'gone',
    'task.going.label'                 => 'going',
    'task.safe.label'                  => 'safe',

    // --- direnc tag tanimlari ---
    'tag.physical-presence'            => 'Requires hands and a body in the physical world.',
    'tag.legal-liability'              => 'A human must legally own the outcome and sign for it.',
    'tag.regulated'                    => 'A licence, permit or statutory wall stands in the way.',
    'tag.trust-relationship'           => 'The value is a personal trust relationship, not the output.',
    'tag.human-judgment'               => 'Contextual decisions under uncertainty that nobody wants to delegate.',
    'tag.creative-taste'               => 'Aesthetic judgment: AI can generate, it cannot choose.',
    'tag.accountability'               => '"Who gets blamed when this is wrong" demands a person.',
    'tag.physical-context'             => 'You have to be on site, in the room, at that moment.',
    'tag.emotional-labor'              => 'The emotional labour is the job itself.',

    // --- kategoriler ---
    'category.tech'                    => 'Tech & Engineering',
    'category.finance'                 => 'Finance & Accounting',
    'category.legal'                   => 'Legal',
    'category.health'                  => 'Health & Care',
    'category.education'               => 'Education',
    'category.creative'                => 'Media & Creative',
    'category.trades'                  => 'Trades & Field Work',
    'category.service'                 => 'Sales & Service',
    'category.ops'                     => 'Operations & Admin',
    'category.unknown'                 => 'Uncategorised',

    // --- ay adlari (intl kapaliyken fallback, spec 4.1) ---
    'month.1'  => 'January',   'month.2'  => 'February', 'month.3'  => 'March',
    'month.4'  => 'April',     'month.5'  => 'May',      'month.6'  => 'June',
    'month.7'  => 'July',      'month.8'  => 'August',   'month.9'  => 'September',
    'month.10' => 'October',   'month.11' => 'November', 'month.12' => 'December',
    'month.format' => '%s %s',

    // --- liste bagi ---
    'list.and' => 'and',

    // --- kanit notu (evidence_note) ---
    'evidence.draft.label' => 'Community draft',
    'evidence.draft.text'  => 'No evidence attached to this entry yet. The argument may still hold, but nobody has backed it with a source. Attaching one is the single most useful contribution you can make.',
    'evidence.thin.label'  => 'Thin evidence',
    'evidence.thin.text'   => 'This verdict rests on limited published evidence. It is an argument we will defend, but it deserves more sources than it has — if you know of better data, open a PR.',

    // --- uretilen GEO paragrafi (geo_answer) ---
    'geo.prefix'                  => 'As of %s, %s',
    'geo.verdict.safe'            => '%s is not being replaced by AI.',
    'geo.verdict.onthemenu'       => 'the core tasks of %s work are becoming machine-doable%s.',
    'geo.verdict.onthemenu.until' => ', with the shift expected to land by around %s',
    'geo.verdict.shrinking'       => 'the %s role is shrinking rather than disappearing%s.',
    'geo.verdict.shrinking.until' => ', with the core narrowing through roughly %s',
    'geo.gone'                    => ' AI has already absorbed %s.',
    'geo.safe'                    => ' What resists is %s.',
    'geo.resistance'              => ' The structural reason is %s.',
    'geo.fallbackDate'            => 'August 2026',

    // --- FAQ (faq_pairs) ---
    'faq.replace.q'    => 'Will AI replace %ss?',
    'faq.howLong.q'    => 'How long is %s work safe from AI?',
    'faq.howLong.a'    => 'Our estimate is roughly %s. That is the year by which the core tasks of this job are expected to be routinely machine-done in ordinary practice — after capability arrives, after employers adopt it, and after regulators allow it. It is not the year the job title disappears. Current verdict: %s.',
    'faq.whichTasks.q' => 'Which %s tasks is AI already doing?',
    'faq.whichTasks.a' => '%s. Each is judged separately rather than rolling the whole job into one answer.',
    'faq.whatSafe.q'   => 'What part of being %s is safe from AI?',
    'faq.howUse.q'     => 'How should %s use AI instead of competing with it?',
    'faq.howUse.a'     => 'Use it on the tasks already marked gone or going, and keep the judgment. There is a copy-ready prompt written for this specific job at %s.',

    // --- paylasim metni (share_text) ---
    'share.safeUntil' => ' — safe until ~%s',
    // --- navigasyon ve footer (Faz 3F) ---
    'nav.skip'               => 'Skip to content',
    'nav.timeline'           => 'Timeline',
    'nav.methodology'        => 'Methodology',
    'nav.changelog'          => 'Changelog',
    'nav.sponsor'            => 'Sponsor',
    'nav.github'             => 'GitHub',
    'nav.theme.aria'         => 'Switch between light and dark',
    'nav.theme.title'        => 'Light / dark',
    'foot.lead'              => 'Every verdict here is an argument, not a prophecy.',
    'foot.disagree'          => 'Disagree?',
    'foot.openPr'            => 'Open a PR',
    'foot.allJobs'           => 'All jobs',
    'foot.howWeDecide'       => 'How we decide',
    'foot.contribute'        => 'Contribute',
    'foot.fine'              => 'Sponsorship never touches a verdict.',
    'foot.readRule'          => 'Read the rule',
    // --- entry sayfasi (job.php, Faz 3F) ---
    'job.pageTitle'              => 'Will AI replace %ss? — %s %s',
    'job.h1'                     => 'Will AI replace %ss?',
    'job.allJobs'                => 'All jobs',
    'job.lastReviewed'           => 'Last reviewed:',
    'job.safeUntilLabel'         => 'safe until',
    'job.longerVersion'          => 'The longer version',
    'job.taskBreakdown'          => 'Task breakdown',
    'job.taskBreakdown.note'     => 'A verdict on a whole job is a slogan. This is where the argument lives.',
    'job.resists'                => 'Why the rest resists',
    'job.howWeDecide'            => 'How we decide',
    'job.adapt.title'            => 'Use it before it uses you',
    'job.adapt.note'             => 'Paste this into Claude or ChatGPT and start today.',
    'job.adapt.label'            => 'adapt prompt',
    'job.adapt.copy'             => 'Copy prompt',
    'job.share.title'            => 'Share the verdict',
    'job.share.until'            => 'safe until ~%s',
    'job.share.survives'         => 'What survives:',
    'job.share.postX'            => 'Post on X',
    'job.share.copyLink'         => 'Copy link',
    'job.share.openImage'        => 'Open share image',
    'job.share.hint'             => 'The image above is generated for this page and shows up automatically when you paste the link.',
    'job.receipts'               => 'Receipts',
    'job.meta.verdict'           => 'Verdict',
    'job.meta.category'          => 'Category',
    'job.meta.safeUntil'         => 'Safe until',
    'job.meta.evidence'          => 'Evidence',
    'job.meta.lastReviewed'      => 'Last reviewed',
    'job.meta.unknown'           => 'unknown',
    'job.sources'                => 'Sources',
    'job.faq'                    => 'Straight answers',
    'job.disagree.title'         => 'Think this verdict is wrong?',
    'job.disagree.text'          => 'Good — that is the point. Every entry is one JSON file. Change it, argue in the PR, and if the argument holds the verdict changes.',
    'job.disagree.edit'          => 'Edit this entry',
    'job.disagree.method'        => 'Read the methodology',
    'job.related.title'          => 'Jobs on the same fault line',
    'job.related.note'           => 'Same category, or the same reason for surviving.',
    // --- sabit sayfalar: 404, unavailable, changelog (Faz 3F) ---
    'page.404.title'               => 'Not found',
    'page.404.desc'                => 'No verdict at this address yet.',
    'page.404.h1'                  => '404',
    'page.404.text'                => 'No verdict at this address. Either the job does not exist here yet — or it already got stolen.',
    'page.404.cta'                 => 'Browse every verdict',
    'page.unavailable.title'       => 'Not available in this language yet',
    'page.unavailable.desc'        => 'This entry has not been published in this language yet.',
    'page.unavailable.text'        => 'This entry exists, but it has not been written in this language. Read it in English, or open a PR to add the translation.',
    'page.changelog.title'         => 'Verdict changelog',
    'page.changelog.desc'          => 'Every verdict change on willaistealit.com, dated, with the reason it moved. A site that quietly rewrites its own predictions is not worth reading.',
    'page.changelog.lede'          => 'Every verdict that moved, when it moved, and why. A site that quietly rewrites its own predictions is not worth reading.',
    'page.changelog.empty'         => 'Nothing has moved yet. When a model launch or a regulation actually changes a task breakdown, it gets recorded here.',
    'page.changelog.how'           => 'How a verdict moves',
    'page.changelog.howText'       => 'Not because the news was loud. A verdict changes when a specific task in the breakdown actually changes state — a tool ships that does it, or a regulator decides who may. The task moves first, the headline follows.',
    'page.changelog.rules'         => 'The full rules are here.',
];

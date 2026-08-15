<?php
declare(strict_types=1);

const SITE_NAME   = 'Will AI Steal It?';
const SITE_URL    = 'https://willaistealit.com';
const SITE_TAG    = 'Task-level verdicts on which jobs AI actually takes.';
const ROOT        = __DIR__ . '/..';
const JOBS_DIR    = ROOT . '/data/jobs';
const CACHE_DIR   = ROOT . '/cache';
const OG_DIR      = CACHE_DIR . '/og';
const PAGES_DIR   = CACHE_DIR . '/pages';
const INDEX_FILE  = CACHE_DIR . '/index.json';
const FONT_BOLD   = ROOT . '/fonts/Inter-Bold.ttf';
const FONT_REG    = ROOT . '/fonts/Inter-Regular.ttf';

// Sponsor slotlari Faz 2'de acilir. false birakildiginda /sponsor waitlist modunda kalir.
const SPONSORS_LIVE = false;

// tools/*.php dosyalarini web'den tetiklemek icin gereken anahtar.
// Deploy sonrasi Hostinger'da degistir.
const BUILD_KEY = 'change-me-before-deploy';

const VERDICTS = [
    'safe' => [
        'label' => 'SAFE',
        'dot'   => '🟢',
        'color' => '#22c55e',
        'rgb'   => [34, 197, 94],
        'blurb' => 'The core of this job is structurally resistant. AI becomes a tool, not a replacement.',
    ],
    'shrinking' => [
        'label' => 'SHRINKING',
        'dot'   => '🟡',
        'color' => '#eab308',
        'rgb'   => [234, 179, 8],
        'blurb' => 'Significant parts are being automated. The role narrows and shifts — it does not vanish.',
    ],
    'on-the-menu' => [
        'label' => 'ON THE MENU',
        'dot'   => '🔴',
        'color' => '#ef4444',
        'rgb'   => [239, 68, 68],
        'blurb' => 'The core tasks are becoming machine-doable. A time horizon applies.',
    ],
];

// Gorev seviyesi mini-verdict'ler
const TASK_VERDICTS = [
    'gone'  => ['label' => 'gone',  'color' => '#ef4444'],
    'going' => ['label' => 'going', 'color' => '#eab308'],
    'safe'  => ['label' => 'safe',  'color' => '#22c55e'],
];

const RESISTANCE_TAGS = [
    'physical-presence'  => 'Requires hands and a body in the physical world.',
    'legal-liability'    => 'A human must legally own the outcome and sign for it.',
    'regulated'          => 'A licence, permit or statutory wall stands in the way.',
    'trust-relationship' => 'The value is a personal trust relationship, not the output.',
    'human-judgment'     => 'Contextual decisions under uncertainty that nobody wants to delegate.',
    'creative-taste'     => 'Aesthetic judgment: AI can generate, it cannot choose.',
    'accountability'     => '"Who gets blamed when this is wrong" demands a person.',
    'physical-context'   => 'You have to be on site, in the room, at that moment.',
    'emotional-labor'    => 'The emotional labour is the job itself.',
];

const CATEGORIES = [
    'tech'      => 'Tech & Engineering',
    'finance'   => 'Finance & Accounting',
    'legal'     => 'Legal',
    'health'    => 'Health & Care',
    'education' => 'Education',
    'creative'  => 'Media & Creative',
    'trades'    => 'Trades & Field Work',
    'service'   => 'Sales & Service',
    'ops'       => 'Operations & Admin',
];

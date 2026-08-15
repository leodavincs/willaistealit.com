<?php
declare(strict_types=1);

/* Ortama ozel ve gizli ayarlar repoda DEGIL: inc/config.local.php icinde.
   Repo public oldugu icin BUILD_KEY buraya yazilamaz. Ornek dosya:
   inc/config.local.php.example — sunucuda kopyalanip doldurulur. */
@include __DIR__ . '/config.local.php';

const SITE_NAME   = 'Will AI Steal It?';
const SITE_URL    = 'https://willaistealit.com';
const SITE_TAG    = 'Task-level verdicts on which jobs AI actually takes.';

// ---- Deploy oncesi doldurulacak ----

// GitHub repo adresi. Bos birakilirsa sitedeki tum "contribute / open a PR"
// baglantilari gizlenir — kirik link gostermekten iyidir.
const GITHUB_URL = 'https://github.com/leodavincs/willaistealit.com';

// Iletisim adresi. config.local.php'den gelir; kutu acilmadan doldurma.
defined('CONTACT_EMAIL') || define('CONTACT_EMAIL', '');

// Matomo kendi sunucumuzda: stats.willaistealit.com. Site ID gizli bir deger
// degil, o yuzden repoda duruyor. Lokal gelistirmenin kendi trafigini olcmesini
// engelleyen sey bu sabit degil, header.php'deki is_live_host() kontrolu.
// Bos birakilirsa script hic basilmaz.
const MATOMO_URL     = 'https://stats.willaistealit.com/';
const MATOMO_SITE_ID = '1';
const ROOT        = __DIR__ . '/..';
const JOBS_DIR    = ROOT . '/data/jobs';
const CACHE_DIR   = ROOT . '/cache';
const OG_DIR      = CACHE_DIR . '/og';
const PAGES_DIR   = CACHE_DIR . '/pages';
const INDEX_FILE  = CACHE_DIR . '/index.json';
const ROUTES_FILE = CACHE_DIR . '/routes.json';
const FONT_BOLD   = ROOT . '/fonts/Fraunces.ttf';
const FONT_REG    = ROOT . '/fonts/Newsreader.ttf';

// Sponsor slotlari Faz 2'de acilir. false birakildiginda /sponsor waitlist modunda kalir.
const SPONSORS_LIVE = false;

// tools/*.php anahtari. GERCEK DEGER config.local.php'de — repo public.
// Tanimlanmazsa tools/ web'den hic calismaz (bkz. build_key_ok()).
defined('BUILD_KEY') || define('BUILD_KEY', 'change-me-before-deploy');

const VERDICTS = [
    'safe' => [
        'label' => 'SAFE',
        'dot'   => '🟢',
        'color' => '#2b7d52',
        'rgb'   => [43, 125, 82],
        'blurb' => 'The core of this job is structurally resistant. AI becomes a tool, not a replacement.',
    ],
    'shrinking' => [
        'label' => 'SHRINKING',
        'dot'   => '🟡',
        'color' => '#a8811f',
        'rgb'   => [168, 129, 31],
        'blurb' => 'Significant parts are being automated. The role narrows and shifts — it does not vanish.',
    ],
    'on-the-menu' => [
        'label' => 'ON THE MENU',
        'dot'   => '🔴',
        'color' => '#b34455',
        'rgb'   => [179, 68, 85],
        'blurb' => 'The core tasks are going, and what is left will not support as many people. A time horizon applies.',
    ],
];

// Gorev seviyesi mini-verdict'ler
const TASK_VERDICTS = [
    'gone'  => ['label' => 'gone',  'color' => '#b34455'],
    'going' => ['label' => 'going', 'color' => '#a8811f'],
    'safe'  => ['label' => 'safe',  'color' => '#2b7d52'],
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

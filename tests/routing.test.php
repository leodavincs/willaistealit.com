<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/routing.php';

// Faz 1 gercegi: yalnizca EN aktif olacak. Fixture'da TR de aktif —
// cozumleyici dilden BAGIMSIZ olmali, Faz 4'te yeniden yazilmamali.
$R = [
    'activeLangs' => ['en', 'tr'],
    'ids' => [
        'software-developer' => ['en' => 'software-developer', 'tr' => 'yazilim-gelistirici'],
        'accountant'         => ['en' => 'accountant'],
    ],
    'slugs' => [
        'en' => ['software-developer' => 'software-developer', 'accountant' => 'accountant'],
        'tr' => ['yazilim-gelistirici' => 'software-developer', 'yazilimci' => 'software-developer'],
        'es' => [],
    ],
    'published' => [
        'software-developer' => ['en', 'tr'],
        'accountant'         => ['en'],
    ],
    'pages'     => ['en' => ['methodology' => 'methodology'],
                    'tr' => ['metodoloji' => 'methodology'], 'es' => []],
    'pageSlugs' => ['en' => ['methodology' => 'methodology'],
                    'tr' => ['methodology' => 'metodoloji'], 'es' => []],
];

$r     = static fn (string $p): array => resolve_path($p, $R);
$redir = static fn (string $to): array => ['type' => 'redirect', 'status' => 301, 'location' => $to];

// --- Ana sayfalar ve normalizasyon (spec 1.1, 1.4) ---
t_eq(['type' => 'home', 'lang' => 'en'], $r('/'),    '/ EN ana sayfa');
t_eq($redir('/'),                        $r('/en'),  '/en -> /');
t_eq($redir('/'),                        $r('/en/'), '/en/ -> /');
t_eq($redir('/tr/'),                     $r('/tr'),  '/tr -> /tr/');
t_eq(['type' => 'home', 'lang' => 'tr'], $r('/tr/'), '/tr/ ana sayfa');

// --- Meslek sayfalari ---
t_eq(['type' => 'job', 'lang' => 'en', 'id' => 'software-developer'],
     $r('/software-developer'), 'EN entry');
t_eq(['type' => 'job', 'lang' => 'tr', 'id' => 'software-developer'],
     $r('/tr/yazilim-gelistirici'), 'TR entry');
t_eq($redir('/software-developer'),     $r('/software-developer/'),      'sondaki egik cizgi');
t_eq($redir('/tr/yazilim-gelistirici'), $r('/tr/yazilim-gelistirici/'),  'TR sondaki egik cizgi');

// --- Bilinen slug -> yerel canonical, TEK adim (spec 1.3, 1.4) ---
t_eq($redir('/tr/yazilim-gelistirici'), $r('/tr/software-developer'),  'id -> TR canonical');
t_eq($redir('/tr/yazilim-gelistirici'), $r('/tr/yazilimci'),           'formerSlug -> TR canonical');
t_eq($redir('/software-developer'),     $r('/en/yazilim-gelistirici'), '/en/<tr-slug> -> EN canonical');
t_eq($redir('/software-developer'),     $r('/en/software-developer/'), '/en/<en-slug>/ tek adimda');

// --- Bilinmeyen: KIRPMA YOK (spec 1.4) ---
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/en/not-a-real-job'), '/en/bilinmeyen -> 404');
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/unknown'),           'bilinmeyen -> 404');
t_eq(['type' => 'notfound', 'lang' => 'tr'], $r('/tr/unknown'),        'TR bilinmeyen -> 404');
// aka YONLENDIRME KAYNAGI DEGIL (spec 1.3): routes tablosunda hic yer almaz.
t_eq(['type' => 'notfound', 'lang' => 'tr'], $r('/tr/developer'),      'aka yonlendirmez');

// --- Yayinlanmamis dil (spec 5.4): 301 URETILMEZ ---
t_eq(['type' => 'unavailable', 'lang' => 'tr', 'id' => 'accountant'],
     $r('/tr/accountant'), 'TR de yayinlanmamis -> unavailable');

// --- Aktif olmayan dil ---
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/es/'),          'ES aktif degil');
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/es/cualquier'), 'ES aktif degil, entry');

// --- Sabit sayfalar ---
t_eq(['type' => 'page', 'lang' => 'en', 'key' => 'methodology'], $r('/methodology'),   'EN sabit sayfa');
t_eq(['type' => 'page', 'lang' => 'tr', 'key' => 'methodology'], $r('/tr/metodoloji'), 'TR sabit sayfa');
t_eq($redir('/tr/metodoloji'), $r('/tr/methodology'), 'sabit sayfa capraz slug');

// --- Uretilen dosyalar ---
t_eq(['type' => 'sitemap'], $r('/sitemap.xml'), 'sitemap');
t_eq(['type' => 'llms'],    $r('/llms.txt'),    'llms');

// --- OG: aktif dil ve yayin kontrolunden KACAMAZ ---
t_eq(['type' => 'og', 'lang' => 'en', 'slug' => 'accountant'],
     $r('/og/accountant.png'), 'EN OG');
t_eq(['type' => 'og', 'lang' => 'en', 'slug' => 'home'],
     $r('/og/home.png'), 'site geneli OG korunuyor');
t_eq(['type' => 'og', 'lang' => 'tr', 'slug' => 'yazilim-gelistirici'],
     $r('/og/tr/yazilim-gelistirici.png'), 'TR OG');
t_eq($redir('/og/tr/yazilim-gelistirici.png'),
     $r('/og/tr/software-developer.png'), 'OG: bilinen slug -> canonical, tek 301');
t_eq(['type' => 'notfound', 'lang' => 'tr'],
     $r('/og/tr/accountant.png'), 'OG: TR de yayinlanmamis -> 404');
t_eq(['type' => 'notfound', 'lang' => 'en'],
     $r('/og/es/yazilim-gelistirici.png'), 'OG: ES aktif degil -> 404');
t_eq(['type' => 'notfound', 'lang' => 'tr'],
     $r('/og/tr/hayali-meslek.png'), 'OG: bilinmeyen slug -> 404');

// --- GUVENLIK: tam fixture seti (spec 1.8) ---
// Bu blok, .htaccess/router.php gecisinden ONCE yesil olmak ZORUNDA.
$forbidden = [
    '/data/jobs/accountant.json',
    '/data/jobs/accountant/en.json',
    '/data/changelog.json',
    '/inc/config.local.php',
    '/inc/functions.php',
    '/inc/ttf.php',
    '/cache/routes.json',
    '/cache/index.json',
    '/cache/pages/en/accountant.html',
    '/docs/architecture/2026-08-15-cok-dilli-mimari.md',
    '/research/sources.json',
    '/tests/routing.test.php',
    '/.git/config',
    '/.env',
    '/.gitignore',
    '/README.md',
    '/CLAUDE.md',
    '/inc/config.local.php.example',
];
foreach ($forbidden as $p) {
    t_eq(['type' => 'forbidden'], $r($p), "yasak: $p");
    t_eq(true, path_is_forbidden($p), "path_is_forbidden: $p");
}

// Acik kalmasi GEREKENLER — asiri kisitlama da bir hatadir.
$open = [
    '/.well-known/security.txt',   // SSL yenileme ve security.txt buraya bakiyor
    '/assets/style.css',
    '/assets/search.js',
    '/fonts/Fraunces.ttf',
    '/og/accountant.png',
    '/tools/doctor.php',           // BUILD_KEY ile korunuyor, kapatilmiyor
    '/sitemap.xml',
    '/accountant',
];
foreach ($open as $p) {
    t_eq(false, path_is_forbidden($p), "acik: $p");
}

// --- OG cozumlemesi sayfa cozumlemesiyle ayni kapidan gecer ---
t_eq('og',       resolve_og('en', 'accountant', $R)['type'], 'EN OG acik');
t_eq('og',       resolve_og('tr', 'yazilim-gelistirici', $R)['type'], 'TR yayinliysa OG acik');
t_eq('notfound', resolve_og('es', 'yazilim-gelistirici', $R)['type'], 'aktif olmayan dilde OG 404');
t_eq('notfound', resolve_og('tr', 'accountant', $R)['type'], 'o dilde yayinlanmamis entry OG 404');
t_eq('en',       resolve_og('en', 'home', $R)['lang'],       'ana sayfa karti EN kalir');
// Eski EN yolu korunur: /og/<slug>.png dil klasoru ISTEMEZ (spec 5.6)
t_eq('/og/accountant.png',             og_path('en', 'accountant'), 'EN OG yolu prefix siz');
t_eq('/og/tr/yazilim-gelistirici.png', og_path('tr', 'yazilim-gelistirici'), 'TR OG yolu');

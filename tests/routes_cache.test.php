<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/routes_cache.php';

/** Bu testin kendi gecici alani. Gercek cache/routes.json'a DOKUNULMAZ. */
$tmpDir = sys_get_temp_dir() . '/wais-routes-' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0775, true);

$good = [
    'activeLangs' => ['en'],
    'ids'         => ['accountant' => ['en' => 'accountant']],
    'slugs'       => ['en' => ['accountant' => 'accountant'], 'tr' => [], 'es' => []],
    'published'   => ['accountant' => ['en']],
    'pages'       => ['en' => ['methodology' => 'methodology'], 'tr' => [], 'es' => []],
    'pageSlugs'   => ['en' => ['methodology' => 'methodology'], 'tr' => [], 'es' => []],
];

// --- routes_valid: sekil ---
t_eq(false, routes_valid(null),          'null gecersiz');
t_eq(false, routes_valid([]),            'bos dizi gecersiz');
t_eq(false, routes_valid('{"ids":1}'),   'dize gecersiz');
t_eq(false, routes_valid(['ids' => []]), 'eksik anahtar gecersiz');
t_eq(true,  routes_valid($good),         'tam tablo gecerli');

// --- routes_valid: semantik (spec 1.5) ---
$bad = $good; $bad['activeLangs'] = ['en', 'de'];
t_eq(false, routes_valid($bad), 'bilinmeyen dil kodu reddedilir');

$bad = $good; $bad['activeLangs'] = ['tr'];
t_eq(false, routes_valid($bad), 'varsayilan dil aktif degilse reddedilir');

$bad = $good; $bad['published']['hayali'] = ['en'];
t_eq(false, routes_valid($bad), 'published kimligi ids icinde yoksa reddedilir');

$bad = $good; $bad['published']['accountant'] = ['en', 'tr'];
t_eq(false, routes_valid($bad), 'yayinlanan dilde canonical slug yoksa reddedilir');

$bad = $good; $bad['slugs']['en']['hayali-slug'] = 'hayali-kimlik';
t_eq(false, routes_valid($bad), 'slug bilinmeyen kimlige gidiyorsa reddedilir');

$bad = $good; $bad['pageSlugs']['en']['methodology'] = 'baska-slug';
t_eq(false, routes_valid($bad), 'pages/pageSlugs karsilikli degilse reddedilir');

// --- atomik yazim ---
$f = $tmpDir . '/alt/a.json';
t_eq(true, atomic_write($f, '{"x":1}'), 'dizin yoksa olusturur ve yazar');
t_eq('{"x":1}', (string)file_get_contents($f), 'icerik dogru');
t_eq([], glob(dirname($f) . '/*.tmp') ?: [], 'gecici dosya birakilmadi');

// --- build_routes: gercek veriden uretir, cakismasiz olmali ---
$conflicts = null;
$routes = build_routes($conflicts);
t_eq(true,   routes_valid($routes),                              'uretilen tablo gecerli');
t_eq([],     $conflicts,                                         'mevcut veride slug cakismasi yok');
t_eq(['en'], $routes['activeLangs'],                             'Faz 1 de yalnizca EN aktif');
t_eq('accountant', $routes['slugs']['en']['accountant'] ?? null, 'accountant tabloda');
t_eq('accountant', $routes['ids']['accountant']['en'] ?? null,   'id -> EN slug');
// published, entry'nin GERCEK dil dosyalarindan hesaplanir — sabit bir liste
// degil. 4B3 boyunca her entry TR aliyor, o yuzden kume esitligi olarak yazildi.
t_eq(entry_langs('accountant'), $routes['published']['accountant'] ?? null,
     'accountant published = entry_langs');
t_eq(true, in_array('en', (array)($routes['published']['accountant'] ?? []), true),
     'kaynak dil her zaman yayinli');
// published TUM entry'ler icin dil dosyalariyla tutarli olmali.
foreach (array_keys((array)$routes['published']) as $pubId) {
    t_eq(entry_langs((string)$pubId), $routes['published'][$pubId], "published tutarli: $pubId");
}
t_eq('methodology', $routes['pages']['en']['methodology'] ?? null, 'sabit sayfa tabloda');

// --- load_routes: bozuk cache -> kaynaktan uretir, COKMEZ (spec 1.5) ---
$broken = $tmpDir . '/broken.json';
file_put_contents($broken, '{ bozuk json');
routes_cache_reset();
t_eq(true, routes_valid(load_routes($broken)), 'bozuk cache -> kaynaktan uretildi');

// Bozuk cache uzerine gecerli tablo YAZILMIS olmali.
routes_cache_reset();
t_eq(true, routes_valid(json_decode((string)file_get_contents($broken), true)),
     'bozuk cache duzeltilerek yazildi');

// --- load_routes: yazilamayan yol -> yine de calisir ---
routes_cache_reset();
t_eq(true, routes_valid(load_routes('/kesinlikle/olmayan/dizin/routes.json')),
     'yazilamayan cache -> bellekte uretildi');

// Temizlik
$rm = static function (string $dir) use (&$rm): void {
    foreach (glob($dir . '/*') ?: [] as $p) {
        is_dir($p) ? $rm($p) : @unlink($p);
    }
    @rmdir($dir);
};
$rm($tmpDir);
routes_cache_reset();

// --- TR sabit sayfa slug'lari (4B1) ---
// Slug'lar ASCII ve katlanmis olmali: valid_slug() yalnizca [a-z0-9-] kabul eder,
// 'zaman-çizelgesi' REDDEDILIR.
foreach (['methodology', 'landscape', 'changelog', 'sponsor'] as $pgKey) {
    t_eq(true, isset(PAGE_SLUGS['tr'][$pgKey]), "tr sayfa slug'i tanimli: $pgKey");
    $pgSlug = (string)(PAGE_SLUGS['tr'][$pgKey] ?? '');
    t_eq(true, valid_slug($pgSlug), "tr sayfa slug'i gecerli: $pgSlug");
    t_eq($pgSlug, strtolower($pgSlug), "tr sayfa slug'i kucuk harf: $pgSlug");
    // Ters esleme: slug -> anahtar
    t_eq($pgKey, (string)(build_routes()['pages']['tr'][$pgSlug] ?? ''), "tr slug -> anahtar: $pgSlug");
}

// TR slug'lari EN slug'lariyla ayni olmamali — ayni olsaydi ceviri yapilmamis demektir.
foreach (['methodology', 'landscape', 'changelog', 'sponsor'] as $pgKey) {
    t_eq(false, PAGE_SLUGS['tr'][$pgKey] === PAGE_SLUGS['en'][$pgKey],
         "tr slug EN'den farkli: $pgKey");
}

// Sayfa slug'i hicbir dilde meslek slug'iyla cakismamali.
$pgConflicts = null;
build_routes($pgConflicts);
t_eq([], (array)$pgConflicts, 'TR sayfa slug lari hicbir entry ile cakismiyor');

// --- Capraz dil yonlendirmesi: /tr/methodology -> 301 /tr/metodoloji ---
$pgR = build_routes();
$pgR['activeLangs'] = ['en', 'tr'];
t_eq(['type' => 'redirect', 'status' => 301, 'location' => '/tr/metodoloji'],
     resolve_path('/tr/methodology', $pgR), 'EN sayfa slug u TR canonical e 301');
t_eq(['type' => 'page', 'lang' => 'tr', 'key' => 'methodology'],
     resolve_path('/tr/metodoloji', $pgR), 'TR sabit sayfa cozulur');
t_eq(['type' => 'page', 'lang' => 'tr', 'key' => 'landscape'],
     resolve_path('/tr/zaman-cizelgesi', $pgR), 'TR zaman cizelgesi');
t_eq(['type' => 'page', 'lang' => 'tr', 'key' => 'changelog'],
     resolve_path('/tr/degisiklikler', $pgR), 'TR degisiklikler');
t_eq(['type' => 'page', 'lang' => 'tr', 'key' => 'sponsor'],
     resolve_path('/tr/sponsorluk', $pgR), 'TR sponsorluk');
// TR slug'i EN tarafinda kaybolmaz: bilinen slug kendi dilinin canonical'ina
// TEK adimda yonlendirilir (spec 1.3/1.4) — meslek slug'lariyla ayni kural.
t_eq(['type' => 'redirect', 'status' => 301, 'location' => '/methodology'],
     resolve_path('/metodoloji', $pgR), 'TR sayfa slug u EN canonical e 301');
t_eq(['type' => 'redirect', 'status' => 301, 'location' => '/tr/zaman-cizelgesi'],
     resolve_path('/tr/landscape', $pgR), 'EN sayfa slug u TR canonical e 301');

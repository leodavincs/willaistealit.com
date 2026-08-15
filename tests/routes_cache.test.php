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
t_eq(['en'], $routes['published']['accountant'] ?? null,         'EN de yayinli');
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

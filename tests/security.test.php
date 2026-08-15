<?php
/**
 * Guvenlik sinirinin UCTAN UCA davranisi: cozumleyici + dispatcher birlikte.
 * Bu dosya, .htaccess/router.php gecisinden ONCEKI commit'te yesil olmak zorunda.
 *
 * load_routes() DEGIL build_routes() kullaniliyor: load_routes() cache yoksa
 * gercek cache/routes.json'i yazar, testler gercek cache'e dokunmaz.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/dispatch.php';
require_once __DIR__ . '/../inc/routes_cache.php';

$live = build_routes();   // gercek veriden uretilmis tablo, dosyaya yazmadan

$forbidden = [
    '/data/jobs/accountant.json',
    '/inc/config.local.php',
    '/cache/routes.json',
    '/docs/architecture/2026-08-15-cok-dilli-mimari.md',
    '/research/sources.json',
    '/tests/security.test.php',
    '/.git/config',
    '/README.md',
];

foreach ($forbidden as $p) {
    $action = dispatch_for(resolve_path($p, $live));
    // 403 DEGIL 404: 403 "burada bir sey var" bilgisi sizdirir.
    t_eq(404,       $action['status'],  "uctan uca 404: $p");
    t_eq('404.php', $action['include'], "404 sablonu: $p");
    // Yasak yol, cozumleyiciden slug/id TASIMADAN cikmali.
    t_eq(['lang' => 'en'], $action['get'], "yasak yol slug/id tasimaz: $p");
}

// Yasak yol ASLA yonlendirmeye donusmez — acik yonlendirme yuzeyine kapali.
foreach ($forbidden as $p) {
    t_eq(null, dispatch_for(resolve_path($p, $live))['headers']['Location'] ?? null,
         "yasak yol yonlendirmez: $p");
}

// .well-known cozumleyici tarafindan KAPATILMAZ; varsa gercek dosya servis edilir,
// yoksa normal 404 doner (ikisi de dogru davranis).
t_eq(false, path_is_forbidden('/.well-known/security.txt'), '.well-known kapatilmaz');
t_eq(false, path_is_forbidden('/.well-known/acme-challenge/abc123'), 'acme challenge kapatilmaz');

// tools/ BUILD_KEY ile korunuyor, cozumleyici tarafindan kapatilmiyor (mevcut karar).
t_eq(false, path_is_forbidden('/tools/build-index.php'), 'tools kapatilmaz');

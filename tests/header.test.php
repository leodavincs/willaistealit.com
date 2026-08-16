<?php
/**
 * Dil secici. Canli routes cache'ine DOKUNULMAZ: header.php sentetik bir
 * $routes/$pageAlternates ciftiyle render edilir (spec 15, 5.4).
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

/** header.php'yi izole kapsamda render et — degiskenler yerel kalir. */
function render_header_fixture(array $routes, array $pageAlternates, string $lang = 'en'): string
{
    ob_start();
    require __DIR__ . '/../inc/header.php';
    return (string)ob_get_clean();
}

$oneLang = [
    'activeLangs' => ['en'],
    'ids'         => ['cashier' => ['en' => 'cashier']],
    'published'   => ['cashier' => ['en']],
    'pageSlugs'   => ['en' => PAGE_SLUGS['en'], 'tr' => [], 'es' => []],
];
$twoLang = $oneLang;
$twoLang['activeLangs'] = ['en', 'tr'];
$twoLang['ids']['cashier']['tr'] = 'kasiyer';
$twoLang['published']['cashier'] = ['en', 'tr'];
$twoLang['pageSlugs']['tr']      = ['methodology' => 'metodoloji'];

$alt2 = ['en' => 'https://willaistealit.com/cashier',
         'tr' => 'https://willaistealit.com/tr/kasiyer',
         'x-default' => 'https://willaistealit.com/cashier'];

// 1) Tek aktif dil: secici HIC basilmaz — <nav> bile yok.
$h = render_header_fixture($oneLang, $alt2);
t_eq(0, substr_count($h, 'lang-switch'), 'tek aktif dilde secici yok');
t_eq(0, substr_count($h, 'lang-cur'),    'tek aktif dilde mevcut-dil rozeti yok');

// 2) Iki aktif dil: secici basilir ve SAYFA ESDEGERINE gider, ana sayfaya degil.
$h = render_header_fixture($twoLang, $alt2);
t_eq(1, substr_count($h, 'class="lang-switch"'), 'iki aktif dilde secici basilir');
// Not: ayni URL <link rel="alternate"> icinde de gecer; secici baglantisini
// hreflang+metin birlikteligiyle ayirt ediyoruz.
t_eq(1, substr_count($h, '<a href="https://willaistealit.com/tr/kasiyer" hreflang="tr" title="Türkçe">TR</a>'),
     'secici esdegere gider, ana sayfaya degil');
t_eq(1, substr_count($h, 'lang-cur'),  'mevcut dil rozeti bir kez');
t_eq(0, substr_count($h, 'lang-soon'), 'esdegeri varken yakinda yok');

// 3) Aktif olmayan dil listeye HIC girmez — 'yakinda' dalina da dusemez.
t_eq(0, substr_count($h, 'Español'), 'aktif olmayan ES secicide yok');
t_eq(0, substr_count($h, '>ES<'),    'aktif olmayan ES kodu da yok');
t_eq(1, substr_count($h, '>TR</a>'), 'aktif TR kisa koduyla secicide var');
t_eq(1, substr_count($h, 'title="Türkçe"'), 'tam dil adi title olarak kaliyor');
t_eq(1, substr_count($h, '>EN</span>'), 'mevcut dil kisa koduyla');

// 4) Aktif dilin esdegeri yoksa: tiklanabilir bag DEGIL, pasif metin (spec 5.4).
$h = render_header_fixture($twoLang, ['en' => 'https://willaistealit.com/nurse',
                                      'x-default' => 'https://willaistealit.com/nurse']);
t_eq(1, substr_count($h, 'lang-soon'), 'esdegeri olmayan dil pasif');
t_eq(0, substr_count($h, '<a href="https://willaistealit.com/tr/'), 'pasif dil baglanti tasimaz');

// 5) $pageAlternates bos (404 / unavailable): secici yok.
$h = render_header_fixture($twoLang, []);
t_eq(0, substr_count($h, 'lang-switch'), 'alternates yoksa secici yok');

// 6) Otomatik yonlendirme / tarayici dili algilama EKLENMEDI (spec 1.6).
$h = render_header_fixture($twoLang, $alt2);
foreach (['navigator.language', 'Accept-Language', 'window.location.replace'] as $forbidden) {
    t_eq(false, str_contains($h, $forbidden), "otomatik yonlendirme izi yok: $forbidden");
}

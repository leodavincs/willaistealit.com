<?php
/**
 * Yonlendirme denetimi. Bu iki kural gercek katalogla KIRILAMAZ (activeLangs tek
 * dil, aka bos), o yuzden sentetik BOZUK tablolarla kanitlaniyor.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/routes_audit.php';

// --- routes_leak_errors: aka ve benzeri sizintilar ---
$saglam = [
    'slugs' => ['en' => ['cashier' => 'cashier', 'checkout-clerk' => 'cashier'],
                'tr' => [], 'es' => []],
];
$beklenen = ['en' => ['cashier' => 'cashier', 'checkout-clerk' => 'cashier']];
t_eq([], routes_leak_errors($saglam, $beklenen), 'canonical + formerSlug temiz gecer');

$sizintili = $saglam;
$sizintili['slugs']['en']['till-operator'] = 'cashier';      // aka adres tablosuna girdi
$hata = routes_leak_errors($sizintili, $beklenen);
t_eq(1, count($hata), 'sizan tek slug tek hata verir');
t_eq(true, str_contains($hata[0], "'till-operator'"), 'hata sizan slug u adlandirir');

// --- hreflang_reciprocity_errors ---
$iki = [
    'activeLangs' => ['en', 'tr'],
    'ids'       => ['cashier' => ['en' => 'cashier', 'tr' => 'kasiyer']],
    'slugs'     => ['en' => ['cashier' => 'cashier'], 'tr' => ['kasiyer' => 'cashier'], 'es' => []],
    'published' => ['cashier' => ['en', 'tr']],
    'pages'     => ['en' => [], 'tr' => [], 'es' => []],
    'pageSlugs' => ['en' => [], 'tr' => [], 'es' => []],
];
t_eq([], hreflang_reciprocity_errors($iki), 'karsilikli kume temiz gecer');

// (1) ve (3) dallari: alternates_for() kumeyi HER ZAMAN canonical'dan kurdugu
// icin uretimde tetiklenemezler. Savunma katmani olarak duruyorlar ve burada
// bozuk kume ENJEKTE EDILEREK kanitlaniyorlar — kanitlanmamis kural yazilmamis
// sayilir.
$kanonikDegil = ['job|en|cashier' => ['en' => 'https://willaistealit.com/cashier',
                                  'tr' => 'https://willaistealit.com/tr/eski-kasiyer']];
$eskiTablo = $iki;
$eskiTablo['slugs']['tr']['eski-kasiyer'] = 'cashier';   // formerSlug: 301'e duser
$h = hreflang_reciprocity_errors($eskiTablo, $kanonikDegil);
t_eq(true, str_contains(implode(' ', $h), 'kanonik olmayan'), 'kanonik olmayan hedef kirmizi verir');

$dilUyusmaz = ['job|en|cashier' => ['en' => 'https://willaistealit.com/cashier',
                                'tr' => 'https://willaistealit.com/cashier']];
$h = hreflang_reciprocity_errors($iki, $dilUyusmaz);
t_eq(true, str_contains(implode(' ', $h), "etiketi 'en'"), 'dil uyusmazligi kirmizi verir');

$cozulmez = ['job|en|cashier' => ['en' => 'https://willaistealit.com/cashier',
                              'tr' => 'https://willaistealit.com/tr/hicboyle']];
$h = hreflang_reciprocity_errors($iki, $cozulmez);
t_eq(true, str_contains(implode(' ', $h), 'cozulmuyor'), 'cozulmeyen hedef kirmizi verir (enjekte)');

// 4) Tek yonlu kume: hedef geri baglanti vermiyor.
//    cashier iki dilde yayinli, nurse yalniz EN'de; TR kumesi nurse'u gostermez.
$tekYon = $iki;
$tekYon['ids']['nurse']       = ['en' => 'nurse', 'tr' => 'hemsire'];
$tekYon['slugs']['en']['nurse']   = 'nurse';
$tekYon['slugs']['tr']['hemsire'] = 'nurse';
$tekYon['published']['nurse'] = ['en', 'tr'];
t_eq([], hreflang_reciprocity_errors($tekYon), 'iki entry de karsilikliysa temiz');

// Simdi TR yayinini kaldirmadan SLUG u bozalim: TR sayfasi baska entry ye cozuluyor.
$capraz = $tekYon;
$capraz['slugs']['tr']['hemsire'] = 'cashier';    // TR yolu yanlis entry ye gidiyor
$h = hreflang_reciprocity_errors($capraz);
t_eq(true, $h !== [], 'capraz cozulen hedef kirmizi verir');

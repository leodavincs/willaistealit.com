<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/locale.php';
require_once __DIR__ . '/../inc/entry.php';

$en  = lang_for('en');
$job = load_entry('accountant', 'en');

// --- Sozlesme ---
t_eq('en',  $en->code(),                        'dil kodu');
t_eq(true,  $en->has('verdict.safe.label'),     'anahtar var');
t_eq(false, $en->has('hicboyle.anahtar'),       'anahtar yok');
t_eq('en',  lang_for('hicboyle')->code(),       'bilinmeyen kod varsayilana duser');
t_eq(true,  lang_for('en') === lang_for('en'),  'ornek onbellekleniyor');

// --- Etiketler ve tanimlar ---
t_eq('SAFE',        $en->verdictLabel('safe'),        'verdict etiketi');
t_eq('SHRINKING',   $en->verdictLabel('shrinking'),   'verdict etiketi 2');
t_eq('ON THE MENU', $en->verdictLabel('on-the-menu'), 'verdict etiketi 3');
t_eq('gone',        $en->taskVerdictLabel('gone'),    'gorev verdict etiketi');
t_eq('safe',        $en->taskVerdictLabel('safe'),    'gorev verdict etiketi 2');
t_eq('Tech & Engineering', $en->categoryLabel('tech'), 'kategori etiketi');
t_eq('Uncategorised',      $en->categoryLabel('hicboyle'), 'bilinmeyen kategori');
t_eq('A human must legally own the outcome and sign for it.',
     $en->tagDefinition('legal-liability'), 'tag tanimi');
t_eq('', $en->tagDefinition('hicboyle'), 'bilinmeyen tag bos doner');

// --- Bicimlendirme: bugunku davranisla birebir ---
t_eq('August 2026',  $en->month('2026-08'), 'ay adi');
t_eq('January 2027', $en->month('2027-01'), 'ay adi 2');
t_eq('',             $en->month('bozuk'),   'bozuk ay bos doner');
t_eq('',             $en->month('2026-13'), 'gecersiz ay bos doner');
t_eq('',            $en->listPhrase([]),              'bos liste');
t_eq('a',           $en->listPhrase(['a']),           'tek ogeli liste');
t_eq('a and b',     $en->listPhrase(['a', 'b']),      'iki ogeli liste');
t_eq('a, b and c',  $en->listPhrase(['a', 'b', 'c']), 'uc ogeli liste');
t_eq('an accountant', $en->withArticle('accountant'), 'sesli harf artikeli');
t_eq('a lawyer',      $en->withArticle('lawyer'),     'sessiz harf artikeli');
t_eq('data entry',    $en->lowerFirst('Data entry'),  'ilk harf kucultme');
t_eq('CV screening',  $en->lowerFirst('CV screening'), 'kisaltma korunur');
t_eq('accountants',   $en->plural('accountant'),      'cogul');
t_eq('accountant',    $en->plural('accountant', 1),   'tekil');

// --- intl kapaliyken AYNI sonuc (spec 4.1) ---
intl_available(false);
t_eq('August 2026',  $en->month('2026-08'), 'intl kapali: ay adi ayni');
t_eq('January 2027', $en->month('2027-01'), 'intl kapali: ay adi 2 ayni');
intl_available(null);

// --- Uretilen cumleler ---
$geo = $en->geoAnswer($job);
t_eq(true, str_starts_with($geo, 'As of August 2026,'), 'geo tarihle basliyor');
t_eq(true, str_contains($geo, 'shrinking rather than disappearing'), 'geo verdict cumlesi');
t_eq(true, str_contains($geo, 'The structural reason is'), 'geo direnc cumlesi');

$url = 'https://willaistealit.com/accountant';
$faq = $en->faqPairs($job, $url);
t_eq('Will AI replace accountants?', $faq[0]['q'], 'ilk FAQ sorusu');
t_eq($geo, $faq[0]['a'], 'ilk FAQ cevabi geo paragrafi');
t_eq(true, str_contains($faq[count($faq) - 1]['a'], $url), 'son FAQ cevabi URL i tasir');

// --- URL PARAMETRE olarak gelir: Lang URL uretmez ---
$share = $en->shareText($job, $url);
t_eq(true, str_contains($share, $url),          'paylasim URL i');
t_eq(true, str_contains($share, 'SHRINKING'),   'paylasim verdict i');
t_eq(true, str_contains($share, 'ACCOUNTANT:'), 'paylasim basligi buyuk harf');

// --- Kanit notu ---
t_eq(null, $en->evidenceNote($job), 'guclu kanitta not yok');
t_eq('Community draft', $en->evidenceNote(['sources' => []])['label'], 'kaynaksiz: community draft');
t_eq('Thin evidence',
     $en->evidenceNote(['sources' => ['x'], 'evidenceStrength' => 'thin'])['label'], 'zayif kanit');

// --- Eksik anahtar sessiz kalmaz ---
t_eq('hicboyle.anahtar', $en->t('hicboyle.anahtar'), 'eksik anahtar kendini doner');

// --- Kapsam: her verdict/kategori/tag cevrilmis
//     (Faz 3D'de CATEGORY_KEYS / RESISTANCE_KEYS'e gecildi) ---
foreach (array_keys(VERDICTS) as $k) {
    t_eq(true, $en->verdictLabel($k) !== '' && $en->verdictBlurb($k) !== '', "en: '$k' verdict i cevrilmis");
}
foreach (CATEGORY_KEYS as $k) {
    t_eq(true, $en->has('category.' . $k), "en: '$k' kategorisi cevrilmis");
}
foreach (RESISTANCE_KEYS as $k) {
    t_eq(true, $en->has('tag.' . $k), "en: '$k' tag i cevrilmis");
}

// SITE_TAG hala config'de (Faz 3F'de sablonlarla birlikte tasinacak) — kaymasin diye sabitliyoruz
t_eq(SITE_TAG, $en->t('site.tagline'), 'SITE_TAG ile site.tagline ayni kalmali');

// template_files() locale ve dil dosyalarini gormeli — yoksa bayat cache sessizce servis edilir
$tf = template_files();
$hasFile = static fn (string $needle): bool => (bool)array_filter($tf, static fn ($f) => str_contains($f, $needle));
t_eq(true, $hasFile('/inc/lang/En.php'),    'sablon bagimliligi: dil sinifi');
t_eq(true, $hasFile('/inc/lang/Tr.php'),    'sablon bagimliligi: TR dil sinifi');
t_eq(true, $hasFile('/data/locale/en.php'), 'sablon bagimliligi: locale tablosu');
t_eq(true, $hasFile('/data/locale/tr.php'), 'sablon bagimliligi: TR locale tablosu');
t_eq(true, $hasFile('/inc/functions.php'),  'sablon bagimliligi: functions');
t_eq(true, $hasFile('/job.php'),            'sablon bagimliligi: job.php');

// ═══════════════════════════ TURKCE ═══════════════════════════
$tr = lang_for('tr');
t_eq('tr', $tr->code(), 'TR dil kodu');

t_eq('GÜVENDE',   $tr->verdictLabel('safe'),        'TR verdict etiketi');
t_eq('DARALIYOR', $tr->verdictLabel('shrinking'),   'TR verdict etiketi 2');
t_eq('MENÜDE',    $tr->verdictLabel('on-the-menu'), 'TR verdict etiketi 3');
t_eq('gitti',     $tr->taskVerdictLabel('gone'),    'TR gorev verdict i');
t_eq('gidiyor',   $tr->taskVerdictLabel('going'),   'TR gorev verdict i 2');
t_eq('kalıyor',   $tr->taskVerdictLabel('safe'),    'TR gorev verdict i 3');
t_eq('Finans ve Muhasebe', $tr->categoryLabel('finance'), 'TR kategori');
t_eq('Sınıflandırılmamış', $tr->categoryLabel('hicboyle'), 'TR bilinmeyen kategori');

// --- I / i / İ / ı: mb_strtolower('İ') birlesen nokta uretir, eslesmeyi bozar ---
t_eq('işe alım',      $tr->lowerFirst('İşe alım'),      'TR: İ -> i');
t_eq('ışıklandırma',  $tr->lowerFirst('Işıklandırma'),  'TR: I -> ı');
t_eq('idari işler',   $tr->lowerFirst('İdari işler'),   'TR: İ -> i (2)');
t_eq('veri girişi',   $tr->lowerFirst('Veri girişi'),   'TR: normal kucultme');
// Kisaltma korunur — Turkce buyultmeyle tespit edilir
t_eq('KDV beyanı',    $tr->lowerFirst('KDV beyanı'),    'TR kisaltma korunur');
t_eq('SGK bildirimi', $tr->lowerFirst('SGK bildirimi'), 'TR kisaltma korunur 2');
t_eq('İK süreçleri',  $tr->lowerFirst('İK süreçleri'),  'TR: İK kisaltmasi korunur');
// Tek harf kisaltma sayilmaz
t_eq('a maddesi',     $tr->lowerFirst('A maddesi'),     'TR tek harf kisaltma degil');

// --- Liste bagi, artikel, cogul ---
t_eq('a, b ve c', $tr->listPhrase(['a', 'b', 'c']), 'TR liste bagi');
t_eq('a ve b',    $tr->listPhrase(['a', 'b']),      'TR iki ogeli liste');
t_eq('a',         $tr->listPhrase(['a']),           'TR tek ogeli liste');
t_eq('muhasebeci', $tr->withArticle('muhasebeci'),  'TR de artikel YOK');
t_eq('avukatlar',  $tr->plural('avukat'),           'TR cogul: kalin unlu -lar');
t_eq('öğretmenler', $tr->plural('öğretmen'),        'TR cogul: ince unlu -ler');
t_eq('avukat',     $tr->plural('avukat', 1),        'TR tekil');

// --- Ay adlari + intl kapali/acik ESITLIGI ---
t_eq('Ağustos 2026', $tr->month('2026-08'), 'TR ay adi');
t_eq('Ocak 2027',    $tr->month('2027-01'), 'TR ay adi 2');
t_eq('Aralık 1999',  $tr->month('1999-12'), 'TR ay adi 3');
t_eq('',             $tr->month('bozuk'),   'TR bozuk ay');

// ═══════════════════════════ ISPANYOLCA ═══════════════════════════
$es = lang_for('es');
t_eq('es', $es->code(), 'ES dil kodu');

t_eq('A SALVO',    $es->verdictLabel('safe'),        'ES verdict etiketi');
t_eq('SE REDUCE',  $es->verdictLabel('shrinking'),   'ES verdict etiketi 2');
t_eq('EN EL MENÚ', $es->verdictLabel('on-the-menu'), 'ES verdict etiketi 3');
t_eq('ya desapareció',      $es->taskVerdictLabel('gone'),  'ES gorev verdict i');
t_eq('está desapareciendo', $es->taskVerdictLabel('going'), 'ES gorev verdict i 2');
t_eq('resiste',             $es->taskVerdictLabel('safe'),  'ES gorev verdict i 3');
t_eq('Tecnología e Ingeniería', $es->categoryLabel('tech'), 'ES kategori');
t_eq('Sin clasificar', $es->categoryLabel('hicboyle'), 'ES bilinmeyen kategori');

// --- y -> e kurali ve hie-/hia- istisnalari ---
t_eq('a, b y c',       $es->listPhrase(['a', 'b', 'c']),       'ES liste bagi: y');
t_eq('padres e hijos', $es->listPhrase(['padres', 'hijos']),   'ES hi- once e');
t_eq('agujas e hilo',  $es->listPhrase(['agujas', 'hilo']),    'ES i- once e');
t_eq('él e Irene',     $es->listPhrase(['él', 'Irene']),       'ES buyuk harf I- once e');
t_eq('sal e íntegro',  $es->listPhrase(['sal', 'íntegro']),    'ES aksanli í- once e');
t_eq('cobre y hierro', $es->listPhrase(['cobre', 'hierro']),   'ES hie- diptong: y kalir');
t_eq('agua y hielo',   $es->listPhrase(['agua', 'hielo']),     'ES hie- diptong 2: y kalir');
t_eq('tiempo y hiato', $es->listPhrase(['tiempo', 'hiato']),   'ES hia- diptong: y kalir');
t_eq('a, b e Italia',  $es->listPhrase(['a', 'b', 'Italia']),  'ES uc ogeli listede de e');

t_eq('una tarea', $es->withArticle('tarea'),  'ES disil artikel');
t_eq('un trabajo', $es->withArticle('trabajo'), 'ES eril artikel');
t_eq('tareas',    $es->plural('tarea'),       'ES cogul: unlu +s');
t_eq('doctores',  $es->plural('doctor'),      'ES cogul: sessiz +es');
t_eq('lápices',   $es->plural('lápiz'),       'ES cogul: z -> ces');

t_eq('agosto de 2026',    $es->month('2026-08'), 'ES ay adi');
t_eq('enero de 2027',     $es->month('2027-01'), 'ES ay adi 2');
t_eq('diciembre de 1999', $es->month('1999-12'), 'ES ay adi 3');
t_eq('', $es->month('2026-13'), 'ES gecersiz ay');

// ═══════════ intl KAPALI: uc dilde de AYNI sonuc (spec 4.1) ═══════════
$aylar = ['2026-08', '2027-01', '1999-12', '2026-06'];
$acik = [];
foreach ([$en, $tr, $es] as $L) {
    foreach ($aylar as $ym) {
        $acik[$L->code() . $ym] = $L->month($ym);
    }
}
intl_available(false);
foreach ([$en, $tr, $es] as $L) {
    foreach ($aylar as $ym) {
        t_eq($acik[$L->code() . $ym], $L->month($ym), "intl kapali ayni: {$L->code()} $ym");
    }
}
intl_available(null);

// ═══════════ UC DILDE TAM KAPSAM ═══════════
// (Faz 3D'de CATEGORY_KEYS / RESISTANCE_KEYS'e gecildi)
foreach ([$en, $tr, $es] as $L) {
    $c = $L->code();
    t_eq('', $L->month('bozuk'), "$c: bozuk ay bos doner");
    t_eq('', $L->listPhrase([]),  "$c: bos liste");
    foreach (array_keys(VERDICTS) as $k) {
        t_eq(true, $L->has("verdict.$k.label"), "$c: '$k' verdict etiketi var");
        t_eq(true, $L->has("verdict.$k.blurb"), "$c: '$k' verdict aciklamasi var");
    }
    foreach (array_keys(TASK_VERDICTS) as $k) {
        t_eq(true, $L->has("task.$k.label"), "$c: '$k' gorev verdict i var");
    }
    foreach (CATEGORY_KEYS as $k) {
        t_eq(true, $L->has('category.' . $k), "$c: '$k' kategorisi cevrilmis");
    }
    foreach (RESISTANCE_KEYS as $k) {
        t_eq(true, $L->has('tag.' . $k), "$c: '$k' tag i cevrilmis");
    }
    t_eq(true, $L->has('category.unknown'), "$c: bilinmeyen kategori metni var");
}

// ═══════════ TR/ES gercek entry uzerinde cumle uretiyor mu ═══════════
$trJob = load_entry('cashier', 'tr');
$esJob = load_entry('cashier', 'es');
t_eq(true, $trJob !== null, 'TR cashier yuklenebiliyor');
t_eq(true, $esJob !== null, 'ES cashier yuklenebiliyor');
t_eq(true, str_contains($tr->geoAnswer($trJob), 'Ağustos 2026'), 'TR geo tarihi');
t_eq(true, str_contains($es->geoAnswer($esJob), 'agosto de 2026'), 'ES geo tarihi');
t_eq(true, $tr->faqPairs($trJob, 'https://x/')[0]['q'] !== '', 'TR FAQ sorusu dolu');
t_eq(true, $es->faqPairs($esJob, 'https://x/')[0]['q'] !== '', 'ES FAQ sorusu dolu');
t_eq(true, str_contains($tr->shareText($trJob, 'https://x/'), 'MENÜDE'), 'TR paylasim verdict i');
t_eq(true, str_contains($es->shareText($esJob, 'https://x/'), 'EN EL MENÚ'), 'ES paylasim verdict i');

// ═══════════ SINIR: activeLangs hala ['en'], TR/ES yollari 404 ═══════════
require_once __DIR__ . '/../inc/routes_cache.php';
$R3 = load_routes();
t_eq(['en'], $R3['activeLangs'], 'Faz 3B: activeLangs hala yalnizca en');
foreach (['/tr/', '/tr/kasiyer', '/es/', '/es/cajero', '/og/tr/kasiyer.png'] as $path) {
    t_eq('notfound', resolve_path($path, $R3)['type'], "TR/ES yolu hala 404: $path");
}

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
//     (Faz 3D'de CATEGORIES -> CATEGORY_KEYS olunca bu iki satir guncellenecek) ---
foreach (array_keys(VERDICTS) as $k) {
    t_eq(true, $en->verdictLabel($k) !== '' && $en->verdictBlurb($k) !== '', "en: '$k' verdict i cevrilmis");
}
foreach (array_keys(CATEGORIES) as $k) {
    t_eq(true, $en->has('category.' . $k), "en: '$k' kategorisi cevrilmis");
}
foreach (array_keys(RESISTANCE_TAGS) as $k) {
    t_eq(true, $en->has('tag.' . $k), "en: '$k' tag i cevrilmis");
}

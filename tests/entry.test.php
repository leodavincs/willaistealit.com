<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/entry.php';

/* Gecici agac: canli data/jobs/ DEGISMEZ. */
$root = sys_get_temp_dir() . '/wais-entry-' . bin2hex(random_bytes(4));
$id   = 'cashier';
@mkdir($root . '/' . $id, 0775, true);

/* Kaynak: MIGRE EDILMIS agac. Duz dosyaya bagimlilik yok — o format artik yok. */
foreach (['common', 'en', 'tr', 'es'] as $f) {
    copy(JOBS_DIR . '/' . $id . '/' . $f . '.json', $root . '/' . $id . '/' . $f . '.json');
}
$common = json_decode((string)file_get_contents(JOBS_DIR . '/' . $id . '/common.json'), true);
$en     = json_decode((string)file_get_contents(JOBS_DIR . '/' . $id . '/en.json'), true);

/* Beklenen gorev listesi: taskOrder sirasinda duzlestirilmis EN gorevleri. */
$enTasks = array_map(static fn ($tid) => $en['tasks'][$tid], $common['taskOrder']);

// --- yayinlanmis diller ---
t_eq(['en', 'tr', 'es'], entry_langs($id, $root), 'uc dil de yayinlanmis');
t_eq([], entry_langs('hayali-meslek', $root), 'olmayan entry: dil yok');

// --- EN yukleme: duz dosyayla ayni yargi ---
$en_out = load_entry($id, 'en', $root);
t_eq($en['verdict'],        $en_out['verdict'],        'EN verdict');
t_eq($en['safeUntil'],      $en_out['safeUntil'],      'EN safeUntil');
t_eq($en['resistanceTags'], $en_out['resistanceTags'], 'EN resistanceTags');
t_eq($en['assessmentReviewed'],   $en_out['lastReviewed'],   'lastReviewed uyumluluk takma adi');
t_eq($en['geoAnswer'],      $en_out['geoAnswer'],      'geoAnswer korunur');
t_eq('service',               $en_out['category'],       'kategori common.json dan');
t_eq(6,                       count($en_out['tasks']),   'gorev sayisi');
t_eq($enTasks[0]['name'], $en_out['tasks'][0]['name'], 'gorev sirasi taskOrder a uyar');
t_eq($enTasks[3]['tags'], $en_out['tasks'][3]['tags'], 'gorev tag leri');

// --- TR yukleme: DUZYAZI yerel, YARGI devralinmis (spec 3.1) ---
$tr = load_entry($id, 'tr', $root);
t_eq('Kasiyer', $tr['title'],  'TR baslik yerel');
t_eq('kasiyer', $tr['slug'],   'TR slug yerel');
t_eq($en['verdict'],   $tr['verdict'],   'TR verdict EN den devralindi');
t_eq($en['safeUntil'], $tr['safeUntil'], 'TR safeUntil devralindi');
t_eq($en['sources'],   $tr['sources'],   'TR sources devralindi');
t_eq('global',           $tr['assessmentScope'],        'TR scope global');
t_eq('en',               $tr['assessmentSourceLocale'], 'TR kaynak dili en');
t_eq('2026-08-15',       $tr['translationReviewed'],    'TR ceviri tarihi');
t_eq(6, count($tr['tasks']), 'TR gorev sayisi');
t_eq('Ürün okutma ve ödeme alma', $tr['tasks'][0]['name'], 'TR gorev adi yerel');
t_eq($enTasks[0]['verdict'], $tr['tasks'][0]['verdict'], 'TR gorev yargisi devralindi');
t_eq($enTasks[3]['tags'],    $tr['tasks'][3]['tags'],    'TR gorev tag leri devralindi');
// Duzyazi ASLA devralinmaz: TR notu Ingilizce olamaz.
t_eq(true, str_contains($tr['tasks'][0]['note'], 'Self-servis'), 'TR notu yerel');

// --- ES yukleme ---
$es = load_entry($id, 'es', $root);
t_eq('Cajero', $es['title'], 'ES baslik');
t_eq($en['verdict'], $es['verdict'], 'ES verdict devralindi');

// --- Eksik/bozuk durumlar ---
t_eq(null, load_entry($id, 'de', $root),      'bilinmeyen dil');
t_eq(null, load_entry('hayali', 'en', $root), 'olmayan entry');
t_eq(null, load_entry('../etc', 'en', $root), 'path traversal reddedilir');

// Zorunlu ust duzey duzyazi eksikse o dil yayinlanmamis sayilir.
$orig = json_decode((string)file_get_contents($root . '/' . $id . '/es.json'), true);
$half = $orig;
unset($half['summary']);
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null,          load_entry($id, 'es', $root), 'eksik duzyazi -> yayinlanmamis');
t_eq(['en', 'tr'],  entry_langs($id, $root),      'eksik dil listeden duser');

// GOREV METNI eksikse de yayinlanmamis sayilir — ust duzey alanlar tam olsa bile.
$half = $orig;
unset($half['tasks']['floor-service']['note']);
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null,         load_entry($id, 'es', $root), 'eksik gorev notu -> yayinlanmamis');
t_eq(['en', 'tr'], entry_langs($id, $root),      'eksik gorev notu dili listeden dusurur');

$half = $orig;
$half['tasks']['floor-service']['name'] = '';
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null, load_entry($id, 'es', $root), 'bos gorev adi -> yayinlanmamis');

// Bir gorev tamamen eksikse de.
$half = $orig;
unset($half['tasks']['age-restricted']);
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null, load_entry($id, 'es', $root), 'eksik gorev -> yayinlanmamis');

// localTasks siradaki gorevi karsilayabilir.
$half = $orig;
$half['taskOrder']  = ['scan-payment', 'local-x'];
$half['localTasks'] = ['local-x' => ['name' => 'Tarea local', 'note' => 'Nota local.',
                                     'verdict' => 'safe', 'tags' => ['regulated']]];
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(['en', 'tr', 'es'], entry_langs($id, $root), 'localTasks siradaki gorevi karsilar');
t_eq(2, count(load_entry($id, 'es', $root)['tasks']), 'ezilmis taskOrder uygulanir');

file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($orig));

// --- Yerel kapsam: sahiplik, inheritedSources, taskOrder ezme, localTasks ---
$local = json_decode((string)file_get_contents($root . '/' . $id . '/tr.json'), true);
$local['assessmentScope']        = 'local';
$local['assessmentSourceLocale'] = 'tr';
$local['assessmentReviewed']     = '2026-10-03';
$local['verdict']                = 'shrinking';
$local['safeUntil']              = '2033';
$local['resistanceTags']         = ['regulated'];
$local['evidenceStrength']       = 'strong';
$local['inheritedSources']       = true;
$local['sources']                = ['https://www.turmob.org.tr/ornek'];
$local['taskOrder']              = ['scan-payment', 'local-kdv-uyum', 'floor-service'];
$local['localTasks']             = ['local-kdv-uyum' => [
    'name' => 'KDV ve fis mevzuati uyumu', 'note' => 'Yerel gorev.',
    'verdict' => 'safe', 'tags' => ['regulated'],
]];
$local['tasks']['scan-payment']['verdict'] = 'gone';
file_put_contents($root . '/' . $id . '/tr.json', (string)json_encode($local));

$lt = load_entry($id, 'tr', $root);
t_eq('shrinking', $lt['verdict'],   'yerel kapsam kendi verdict ini tasir');
t_eq('2033',      $lt['safeUntil'], 'yerel safeUntil');
t_eq(array_values(array_unique(array_merge($en['sources'], ['https://www.turmob.org.tr/ornek']))),
     $lt['sources'], 'inheritedSources: global + yerel birlesir, tekrarsiz');
t_eq(3, count($lt['tasks']), 'taskOrder dil dosyasinda ezilebilir');
t_eq('KDV ve fis mevzuati uyumu', $lt['tasks'][1]['name'], 'localTasks yukleniyor');
t_eq(['regulated'], $lt['tasks'][1]['tags'], 'localTasks tag leri');
t_eq('gone', $lt['tasks'][0]['verdict'], 'yerel gorev yargisi ezilebilir');

// inheritedSources kapaliyken YALNIZCA yerel kaynaklar
$local['inheritedSources'] = false;
file_put_contents($root . '/' . $id . '/tr.json', (string)json_encode($local));
t_eq(['https://www.turmob.org.tr/ornek'], load_entry($id, 'tr', $root)['sources'],
     'inheritedSources kapali: yalnizca yerel kaynaklar');

// Bagimlilik listesi inheritedSources acikken en.json'i icermeli
$local['inheritedSources'] = true;
file_put_contents($root . '/' . $id . '/tr.json', (string)json_encode($local));
t_eq(true, in_array($root . '/' . $id . '/en.json',
     entry_dependency_files($id, 'tr', $root), true), 'inheritedSources bagimliligi');

/* tr.json'i orijinal haline dondur — sonraki testler global kapsam bekliyor.
   Kaynak MIGRE EDILMIS agac; staging klasoru artik yok. */
copy(JOBS_DIR . '/' . $id . '/tr.json', $root . '/' . $id . '/tr.json');

// --- Cache bagimliliklari: TR, en.json'a BAGLI (spec 8.1) ---
$deps = entry_dependency_files($id, 'tr', $root);
t_eq(true, in_array($root . '/' . $id . '/en.json', $deps, true), 'TR bagimliligi en.json icerir');
t_eq(true, in_array($root . '/' . $id . '/common.json', $deps, true), 'common.json bagimlilikta');

/* Temizlik */
$rm = static function (string $d) use (&$rm): void {
    foreach (glob($d . '/*') ?: [] as $p) { is_dir($p) ? $rm($p) : @unlink($p); }
    @rmdir($d);
};
$rm($root);

// --- lastmod: dosya zamani DEGIL, icerik tarihi (spec 5.2) ---
// lastmod = max(assessmentReviewed, translationReviewed), her zaman YYYY-MM-DD.
t_eq('2026-08-01', entry_lastmod_from(['assessmentReviewed' => '2026-07-01',
                                       'translationReviewed' => '2026-08-01']), 'ceviri daha yeni');
t_eq('2026-09-01', entry_lastmod_from(['assessmentReviewed' => '2026-09-01',
                                       'translationReviewed' => '2026-08-01']), 'degerlendirme daha yeni');
t_eq('2026-09-01', entry_lastmod_from(['assessmentReviewed' => '2026-09-01']), 'ceviri tarihi yok');
t_eq('',           entry_lastmod_from([]), 'ikisi de yok');

// assessmentReviewed veride YYYY-MM tasiniyor; sozlesme YYYY-MM-DD, ayin ilki alinir.
t_eq('2026-08-01', entry_lastmod_from(['assessmentReviewed' => '2026-08']), 'YYYY-MM ayin ilkine acilir');
t_eq('2026-08-15', entry_lastmod_from(['assessmentReviewed' => '2026-08',
                                       'translationReviewed' => '2026-08-15']), 'ayni ay: ceviri gunu kazanir');
t_eq('2026-09-01', entry_lastmod_from(['assessmentReviewed' => '2026-09',
                                       'translationReviewed' => '2026-08-15']), 'sonraki ay degerlendirmeyi gecemez');
t_eq('',           entry_lastmod_from(['assessmentReviewed' => 'bozuk']), 'bicimi bozuk tarih sayilmaz');

// entry_lastmod(): gercek katalogdan okur, bilinmeyen kimlikte bos doner
t_eq('2026-08-01', entry_lastmod('cashier', 'en'), 'cashier EN lastmod');
t_eq('',           entry_lastmod('yok-boyle-bir-meslek', 'en'), 'bilinmeyen entry');

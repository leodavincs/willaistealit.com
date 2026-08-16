<?php
/**
 * Validator kurallari KIRIK GIRDIYLE kanitlanir. Bir kural, kirmizi verdigi
 * gosterilmeden yazilmis sayilmaz (spec 7).
 *
 * Her vaka gecici bir entry dizini kurar, validate.php'yi alt surec olarak
 * kosar ve beklenen mesaji arar. Dizin her durumda temizlenir.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

const VTEST_ID = 'vtest-fixture';

/** Gecerli bir temel entry — her vaka bunun UZERINE tek bir bozukluk koyar. */
function vtest_base(): array
{
    $common = [
        'id'        => VTEST_ID,
        'category'  => CATEGORY_KEYS[0],
        'taskOrder' => ['t1', 't2', 't3', 't4'],
    ];
    $tasks = [];
    foreach ($common['taskOrder'] as $i => $tid) {
        $tasks[$tid] = ['name' => "Task $i", 'note' => "Note for task $i.", 'verdict' => 'going'];
    }
    $en = [
        'slug'                   => 'vtest-job',
        'title'                  => 'Vtest Job',
        'oneLiner'               => 'A synthetic entry used only by the validator tests.',
        'summary'                => 'Synthetic entry for validator rule proofs.',
        'whatSurvives'           => 'Nothing real; this entry only exists inside a test.',
        'adaptPrompt'            => str_repeat('This is a synthetic adapt prompt used by validator tests. ', 6),
        'assessmentScope'        => 'global',
        'assessmentSourceLocale' => 'en',
        'assessmentReviewed'     => '2026-08',
        'verdict'                => 'shrinking',
        'safeUntil'              => '2030',
        'resistanceTags'         => [RESISTANCE_KEYS[0]],
        'sources'                => [['title' => 'Synthetic', 'url' => 'https://example.com/']],
        'evidenceStrength'       => 'moderate',
        'tasks'                  => $tasks,
    ];
    return ['common.json' => $common, 'en.json' => $en];
}

/** Fixture'i yaz, validate.php'yi kos, ciktiyi don, dizini HER DURUMDA temizle. */
function vtest_run(array $files): string
{
    $dir = JOBS_DIR . '/' . VTEST_ID;
    @mkdir($dir, 0775, true);
    try {
        foreach ($files as $name => $content) {
            file_put_contents($dir . '/' . $name, (string)json_encode($content, JSON_UNESCAPED_UNICODE));
        }
        return (string)shell_exec('php ' . escapeshellarg(ROOT . '/tools/validate.php') . ' 2>&1');
    } finally {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }
}

/** Beklenen mesaj cikti icinde var mi. */
function vtest_says(array $files, string $needle, string $label): void
{
    $out = vtest_run($files);
    t_eq(true, str_contains($out, $needle), $label);
}

// --- 0) Temel fixture TEMIZ olmali. Degilse asagidaki her vaka yalan soyler. ---
$baseOut = vtest_run(vtest_base());
t_eq(true, str_contains($baseOut, 'Hata yok.'), 'temel fixture hatasiz gecer');

// --- Slug kurallari ---
$f = vtest_base(); $f['en.json']['slug'] = 'en';
vtest_says($f, 'rezerve kelime', 'slug rezerve dil kodu olamaz');

$f = vtest_base(); $f['en.json']['slug'] = 'methodology';
vtest_says($f, 'rezerve kelime ya da sabit sayfa', 'slug sabit sayfayla cakisamaz');

$f = vtest_base(); $f['en.json']['slug'] = 'cashier';
vtest_says($f, "hem 'cashier'", 'slug baska entry ile cakisamaz');

$f = vtest_base(); $f['en.json']['formerSlugs'] = ['nurse'];
vtest_says($f, "'nurse' hem", 'formerSlug baska canonical i golgeleyemez');

// --- Gorev kurallari ---
$f = vtest_base(); $f['en.json']['tasks']['t2']['note'] = '';
vtest_says($f, "'t2' gorevinin notu bos", 'gorev notu bos olamaz');

$f = vtest_base(); $f['en.json']['tasks']['t2']['name'] = '';
vtest_says($f, "'t2' gorevinin adi bos", 'gorev adi bos olamaz');

$f = vtest_base(); unset($f['en.json']['tasks']['t3']);
vtest_says($f, "'t3' gorevi eksik", 'taskOrder daki gorev eksik olamaz');

$f = vtest_base(); $f['en.json']['localTasks'] = ['zz' => ['name' => 'X', 'note' => 'Y', 'verdict' => 'going']];
vtest_says($f, "localTasks 'zz' taskOrder'da yok", 'localTasks taskOrder da olmali');

// --- Verdict / safeUntil celiskileri ---
$f = vtest_base(); $f['en.json']['verdict'] = 'safe';
vtest_says($f, "'safe' verdict'te safeUntil olamaz", 'safe + safeUntil hata verir');

$f = vtest_base();
$f['en.json']['verdict'] = 'safe';
unset($f['en.json']['safeUntil']);
$f['en.json']['tasks']['t1']['verdict'] = 'gone';
vtest_says($f, "adet 'gone' gorev var", 'safe verdict te gone gorev olamaz');

// --- Devralma butunlugu (spec 3.1) ---
$tr = ['slug' => 'vtest-is', 'title' => 'Vtest Is', 'oneLiner' => 'Sentetik.',
       'summary' => 'Sentetik.', 'whatSurvives' => 'Hicbir sey.',
       'adaptPrompt' => str_repeat('Sentetik uyarlama metni. ', 12),
       'assessmentScope' => 'global', 'assessmentSourceLocale' => 'en',
       'translationReviewed' => '2026-08-15', 'tasks' => []];
foreach (['t1', 't2', 't3', 't4'] as $i => $tid) {
    $tr['tasks'][$tid] = ['name' => "Gorev $i", 'note' => "Gorev $i notu."];
}
$f = vtest_base(); $f['tr.json'] = $tr + [];
$f['tr.json']['verdict'] = 'gone';
vtest_says($f, "'verdict' tasiyamaz", 'global kapsamda yerel verdict override i HATA');

$f = vtest_base(); $f['tr.json'] = $tr;
unset($f['tr.json']['translationReviewed']);
vtest_says($f, "'translationReviewed' zorunlu", 'translationReviewed zorunlu');

$f = vtest_base(); $f['tr.json'] = $tr;
$f['tr.json']['assessmentScope'] = 'local';
vtest_says($f, 'yerel kapsam kendi dilini kaynak gostermeli', 'local kapsam kendi dilini gostermeli');

// --- Tazelik ---
$f = vtest_base(); $f['tr.json'] = $tr;
$f['tr.json']['translationReviewed'] = '2026-07-01';
$f['common.json']['taskOrder'] = ['t1', 't2', 't3', 't4'];
vtest_says($f, 'eski — bayat', 'bayat ceviri uyarisi');

// --- Yerel gone artisi ---
$f = vtest_base(); $f['tr.json'] = $tr;
$f['tr.json']['tasks']['t1']['verdict'] = 'gone';
$f['tr.json']['tasks']['t2']['verdict'] = 'gone';
vtest_says($f, "yerel 'gone' sayisi", 'yerel gone artisi uyarisi');

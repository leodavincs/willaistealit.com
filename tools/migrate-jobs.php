<?php
/**
 * Duz data/jobs/<slug>.json -> data/jobs/<id>/{common,en}.json donusumu.
 *
 *   php tools/migrate-jobs.php                        # dry-run raporu, HICBIR SEY yazmaz
 *   php tools/migrate-jobs.php --out="$OUT"           # tek kok altina paketler
 *   php tools/migrate-jobs.php --verify --out="$OUT"  # semantik esitlik raporu
 *   php tools/migrate-jobs.php --verify --out="$OUT" --report-ids
 *
 * Arac HICBIR dosyayi silmez ve canli data/ agacina YAZMAZ. Cikti:
 *   <out>/jobs/<id>/{common,en,tr,es}.json
 *   <out>/pending-tr-titles.json
 *   <out>/migration-report.json
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/entry.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

const I18N_DIR = ROOT . '/data/i18n';

/** K5: anlamsal esitlik yalnizca bu anahtarlar uzerinde tanimlidir. */
const COMPARE_KEYS = ['slug', 'title', 'category', 'verdict', 'safeUntil', 'oneLiner', 'summary',
                      'tasks', 'resistanceTags', 'whatSurvives', 'adaptPrompt', 'adaptTools',
                      'sources', 'lastReviewed', 'evidenceStrength', 'geoAnswer',
                      'formerSlugs', 'reviewed'];

const LIST_KEYS   = ['resistanceTags', 'sources', 'adaptTools', 'formerSlugs'];
const STRING_KEYS = ['slug', 'title', 'category', 'verdict', 'safeUntil', 'oneLiner',
                     'summary', 'whatSurvives', 'adaptPrompt', 'lastReviewed',
                     'evidenceStrength', 'geoAnswer'];
const BOOL_KEYS   = ['reviewed'];

/**
 * Gorev adindan kararli ID. Deterministik olmasi sart: ayni ad her zaman ayni ID.
 * ASCII katlama + durak kelime atma + ilk 3 anlamli kelime.
 */
function task_id_from_name(string $name, array $taken): string
{
    $stop = ['and','or','the','a','an','of','for','to','in','on','with','between','at','into','its','their'];
    $s = mb_strtolower($name, 'UTF-8');
    $s = strtr($s, ['ı'=>'i','ğ'=>'g','ş'=>'s','ö'=>'o','ü'=>'u','ç'=>'c',
                    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','&'=>' and ']);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? '';
    $words = array_values(array_filter(preg_split('/\s+/', trim($s)) ?: [],
                          static fn ($w) => $w !== '' && !in_array($w, $stop, true)));
    $id = implode('-', array_slice($words, 0, 3));
    $id = rtrim(substr($id, 0, 40), '-');
    if ($id === '') {
        $id = 'task';
    }
    $base = $id;
    $n = 2;
    while (in_array($id, $taken, true)) {
        $id = $base . '-' . $n++;
    }
    return $id;
}

/**
 * Eksik alan ile bos alan ayni semantiktedir. Normalizasyon TIP bazlidir —
 * genel gevsek karsilastirma DEGILDIR: dolu bir listenin bosalmasi ya da bir
 * dizenin degismesi hala fark olarak raporlanir.
 */
function norm_field(string $k, mixed $v): mixed
{
    if (in_array($k, LIST_KEYS, true)) {
        return array_values((array)($v ?? []));
    }
    if (in_array($k, STRING_KEYS, true)) {
        return (string)($v ?? '');      // eksik safeUntil ile '' ayni sayilir
    }
    if (in_array($k, BOOL_KEYS, true)) {
        return (bool)($v ?? false);
    }
    return $v;
}

function norm_tasks(mixed $tasks): array
{
    return array_map(static fn ($t) => [
        'name'    => (string)($t['name'] ?? ''),
        'verdict' => (string)($t['verdict'] ?? ''),
        'note'    => (string)($t['note'] ?? ''),
        'tags'    => array_values((array)($t['tags'] ?? [])),
    ], (array)$tasks);
}

/** @return string[] Farkli olan anahtarlar. */
function compare_entry(array $old, array $new): array
{
    $diffs = [];
    foreach (COMPARE_KEYS as $k) {
        $a = norm_field($k, $old[$k] ?? null);
        $b = norm_field($k, $new[$k] ?? null);
        if ($k === 'tasks') {
            $a = norm_tasks($a);
            $b = norm_tasks($b);
        }
        if ($a !== $b) {
            $diffs[] = $k;
        }
    }
    return $diffs;
}

/**
 * common.json icerigi. i18n'de elle yazilmis bir tane varsa O KAZANIR.
 * @return array{0:array,1:string} [common, id kaynagi]
 */
function migrate_common(string $id, array $flat): array
{
    $handmade = I18N_DIR . '/' . $id . '/common.json';
    $tasks    = (array)($flat['tasks'] ?? []);

    if (is_file($handmade)) {
        $common = json_decode((string)file_get_contents($handmade), true);
        if (!is_array($common) || !isset($common['taskOrder'])) {
            throw new RuntimeException("$id: i18n/common.json bozuk");
        }
        if (count($common['taskOrder']) !== count($tasks)) {
            throw new RuntimeException(sprintf(
                '%s: gorev sayilari uyusmuyor — i18n taskOrder %d, duz dosya %d. '
                . 'Konum konum eslestirme guvenli degil.',
                $id, count($common['taskOrder']), count($tasks)
            ));
        }
        return [$common, 'i18n'];
    }

    $order = [];
    foreach ($tasks as $t) {
        $order[] = task_id_from_name((string)($t['name'] ?? ''), $order);
    }
    return [[
        'id'        => $id,
        'category'  => (string)($flat['category'] ?? ''),
        'taskOrder' => $order,
    ], 'uretildi'];
}

/** en.json icerigi. EN yarginin SAHIBIDIR (spec 3.1). */
function migrate_en(string $id, array $flat, array $common): array
{
    $en = [
        'assessmentScope'        => 'global',
        'assessmentSourceLocale' => 'en',
        'assessmentReviewed'     => (string)($flat['lastReviewed'] ?? ''),
        'slug'                   => $id,
    ];
    if (!empty($flat['formerSlugs'])) {
        $en['formerSlugs'] = array_values((array)$flat['formerSlugs']);
    }

    // Duzyazi
    foreach (['title', 'oneLiner', 'summary'] as $f) {
        $en[$f] = (string)($flat[$f] ?? '');
    }

    // Gorevler: taskOrder sirasinda nesneye
    $en['tasks'] = [];
    foreach (array_values((array)$common['taskOrder']) as $i => $tid) {
        $t = (array)($flat['tasks'][$i] ?? []);
        $task = [
            'name'    => (string)($t['name'] ?? ''),
            'verdict' => (string)($t['verdict'] ?? ''),
            'note'    => (string)($t['note'] ?? ''),
        ];
        if (!empty($t['tags'])) {
            $task['tags'] = array_values((array)$t['tags']);
        }
        $en[(string)'tasks'][(string)$tid] = $task;
    }

    // Yargi — EN kaynaktir
    foreach (['verdict', 'safeUntil', 'resistanceTags', 'sources', 'evidenceStrength'] as $f) {
        if (isset($flat[$f])) {
            $en[$f] = $flat[$f];
        }
    }
    // Kalan duzyazi ve editoryal durum
    foreach (['whatSurvives', 'adaptPrompt', 'adaptTools', 'geoAnswer', 'reviewed'] as $f) {
        if (isset($flat[$f])) {
            $en[$f] = $flat[$f];
        }
    }
    return $en;
}

/**
 * --out hedefi guvenli mi. Genis silme hedefleri ve canli agac reddedilir.
 * $allowNonEmpty: --verify dolu dizin BEKLER, uretim modu bos dizin ister.
 */
function migrate_target_ok(string $out, string &$why, bool $allowNonEmpty = false): bool
{
    $real = realpath($out) ?: $out;
    $repo = realpath(ROOT) ?: ROOT;
    $home = getenv('HOME') ?: '/root';
    if ($out === '' || $real === '/' || $real === $home) {
        $why = 'kok, home ya da bos yol olamaz';
        return false;
    }
    if ($real === (realpath(JOBS_DIR) ?: JOBS_DIR)) {
        $why = 'canli JOBS_DIR olamaz';
        return false;
    }
    if ($real === $repo || str_starts_with($real . '/', $repo . '/')) {
        $why = 'repo kokunun icinde olamaz (git add kazasi riski)';
        return false;
    }
    if (!$allowNonEmpty && is_dir($real) && (glob($real . '/*') ?: []) !== []) {
        $why = 'hedef dizin bos degil (uretim modu bos dizin ister)';
        return false;
    }
    return true;
}

function jwrite(string $file, array $data): void
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($file, json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . "\n");
}

// ---------------------------------------------------------------- argumanlar
$out = null;
$verify = $reportIds = false;
foreach ($argv as $a) {
    if (str_starts_with($a, '--out=')) {
        $out = substr($a, 6);
    } elseif ($a === '--verify') {
        $verify = true;
    } elseif ($a === '--report-ids') {
        $reportIds = true;
    }
}

if ($out !== null) {
    $why = '';
    if (!migrate_target_ok($out, $why, $verify)) {
        fwrite(STDERR, "HATA: --out kabul edilmedi — $why\n");
        exit(1);
    }
}

// ---------------------------------------------------------------- donusum
$rows = [];
$titlesTr = [];
$errors = [];

foreach (glob(JOBS_DIR . '/*.json') ?: [] as $path) {
    $id   = basename($path, '.json');
    $flat = json_decode((string)file_get_contents($path), true);
    if (!is_array($flat)) {
        $errors[] = "$id: gecersiz JSON";
        continue;
    }
    try {
        [$common, $src] = migrate_common($id, $flat);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
        continue;
    }
    $en = migrate_en($id, $flat, $common);

    $langs = ['en'];
    foreach (['tr', 'es'] as $l) {
        if (is_file(I18N_DIR . '/' . $id . '/' . $l . '.json')) {
            $langs[] = $l;
        }
    }
    if (!empty($flat['titleTr'])) {
        $titlesTr[$id] = (string)$flat['titleTr'];
    }

    $rows[$id] = ['common' => $common, 'en' => $en, 'idSource' => $src,
                  'tasks' => count($common['taskOrder']), 'langs' => $langs,
                  'titleTr' => $flat['titleTr'] ?? null, 'flat' => $flat];
}

// ---------------------------------------------------------------- rapor
printf("%-28s %-6s %-10s %-12s %s\n", 'ENTRY', 'GOREV', 'ID KAYNAGI', 'DILLER', 'titleTr');
foreach ($rows as $id => $r) {
    printf("%-28s %-6d %-10s %-12s %s\n", $id, $r['tasks'], $r['idSource'],
           implode(',', $r['langs']), $r['titleTr'] !== null ? 'yakalandi' : '-');
}
printf("\n%d entry, %d titleTr yakalandi\n", count($rows), count($titlesTr));

if ($errors !== []) {
    echo "\n" . count($errors) . " HATA:\n";
    foreach ($errors as $e) {
        echo "  x $e\n";
    }
    exit(1);
}

if ($reportIds) {
    echo "\nGOREV ID ESLESMELERI (i18n kaynakli entry'ler gozle dogrulanacak)\n";
    foreach ($rows as $id => $r) {
        if ($r['idSource'] !== 'i18n') {
            continue;
        }
        echo "\n  $id\n";
        foreach (array_values($r['common']['taskOrder']) as $i => $tid) {
            printf("    %-28s <- %s\n", $tid, $r['flat']['tasks'][$i]['name'] ?? '?');
        }
    }
}

if ($out === null) {
    echo "\n(dry-run — hicbir dosya yazilmadi. --out=<dizin> ile uretin.)\n";
    exit(0);
}

// ---------------------------------------------------------------- yazma
if (!$verify) {
    foreach ($rows as $id => $r) {
        jwrite($out . '/jobs/' . $id . '/common.json', $r['common']);
        jwrite($out . '/jobs/' . $id . '/en.json', $r['en']);
        foreach (['tr', 'es'] as $l) {
            $srcFile = I18N_DIR . '/' . $id . '/' . $l . '.json';
            if (is_file($srcFile)) {
                if (!is_dir($out . '/jobs/' . $id)) {
                    mkdir($out . '/jobs/' . $id, 0775, true);
                }
                copy($srcFile, $out . '/jobs/' . $id . '/' . $l . '.json');
            }
        }
    }
    jwrite($out . '/pending-tr-titles.json', $titlesTr);
    jwrite($out . '/migration-report.json', [
        'generated' => gmdate('c'),
        'ids'       => array_map(static fn ($r) => $r['tasks'], $rows),
        'idSource'  => array_map(static fn ($r) => $r['idSource'], $rows),
        'langs'     => array_map(static fn ($r) => $r['langs'], $rows),
    ]);
    printf("\nyazildi: %s/jobs (%d entry), pending-tr-titles.json, migration-report.json\n",
           $out, count($rows));
    exit(0);
}

// ---------------------------------------------------------------- verify
if (!is_dir($out . '/jobs')) {
    fwrite(STDERR, "HATA: $out/jobs yok — once --out ile uretin.\n");
    exit(1);
}

echo "\nSEMANTIK ESITLIK (K5: 18 anahtar, titleTr KASITLI olarak disarida)\n";
$bad = 0;
foreach ($rows as $id => $r) {
    $old = load_job($id);                          // eski yukleyici, duz dosya
    $new = load_entry($id, 'en', $out . '/jobs');  // yeni yukleyici
    if ($old === null || $new === null) {
        printf("  HATA %-26s yuklenemedi (eski:%s yeni:%s)\n", $id,
               $old === null ? 'null' : 'ok', $new === null ? 'null' : 'ok');
        $bad++;
        continue;
    }
    $diffs = compare_entry($old, $new);
    if ($diffs === []) {
        printf("  ok   %-26s farksiz\n", $id);
    } else {
        printf("  FARK %-26s %s\n", $id, implode(', ', $diffs));
        $bad++;
    }
}

printf("\n%d/%d entry farksiz\n", count($rows) - $bad, count($rows));
if ($bad === 0) {
    echo "Kasitli tek fark: titleTr kaldirildi (K4) — data/pending-tr-titles.json'a tasiniyor.\n";
}
exit($bad === 0 ? 0 : 1);

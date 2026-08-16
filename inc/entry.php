<?php
/**
 * Cok dilli entry yukleyici.
 * common.json + <kaynak dil>.json + <istenen dil>.json -> sablonlarin bekledigi duz dizi.
 * "Yargi devralinir, duzyazi devralinmaz" (spec 3.1) burada uygulanir.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/routing.php';

/** Devralinabilir yargi alanlari (spec 3.1). */
const INHERITED_FIELDS = ['verdict', 'safeUntil', 'resistanceTags', 'sources',
                          'evidenceStrength', 'assessmentReviewed'];

/** Devralinamayan duzyazi alanlari — eksikse o dil YAYINLANMAMIS sayilir (spec 3.2). */
const REQUIRED_PROSE = ['slug', 'title', 'oneLiner', 'summary', 'tasks',
                        'whatSurvives', 'adaptPrompt'];

function entry_dir(string $id, ?string $root = null): string
{
    return ($root ?? JOBS_DIR) . '/' . $id;
}

/** Tek dosyayi oku; yoksa ya da bozuksa null. */
function entry_read(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/**
 * Bir dil dosyasi tek basina yayinlanabilir mi.
 * Ust duzey duzyazinin varligi YETMEZ: gorev metinleri devralinmaz (spec 3.2),
 * bu yuzden siradaki HER gorev icin bu dilde name ve note bulunmali.
 * Kontrol burada olmak zorunda — route cache validator kosmadan da uretilebiliyor,
 * yani eksik gorev metni tasiyan bir dil aksi halde "published" sayilabilirdi.
 */
function entry_lang_publishable(?array $doc, ?array $common = null): bool
{
    if ($doc === null) {
        return false;
    }
    foreach (REQUIRED_PROSE as $f) {
        if (!isset($doc[$f]) || $doc[$f] === '' || $doc[$f] === []) {
            return false;
        }
    }
    // Sira dil dosyasinda ezilebilir; localTasks da gecerli bir kaynaktir (spec 2.3).
    $order = (array)($doc['taskOrder'] ?? $common['taskOrder'] ?? []);
    if ($order === []) {
        return false;
    }
    foreach ($order as $tid) {
        $tid  = (string)$tid;
        $task = $doc['tasks'][$tid] ?? $doc['localTasks'][$tid] ?? null;
        if (!is_array($task)
            || (string)($task['name'] ?? '') === ''
            || (string)($task['note'] ?? '') === '') {
            return false;
        }
    }
    return true;
}

/** @return string[] Bu entry'nin yayinlanmis dilleri, LANGS sirasinda. */
function entry_langs(string $id, ?string $root = null): array
{
    $dir    = entry_dir($id, $root);
    $common = entry_read($dir . '/common.json');
    if ($common === null) {
        return [];
    }
    $out = [];
    foreach (LANGS as $lang) {
        if (entry_lang_publishable(entry_read($dir . '/' . $lang . '.json'), $common)) {
            $out[] = $lang;
        }
    }
    return $out;
}

/**
 * Cache gecerliligi icin bagimlilik dosyalari (spec 8.1).
 * TR sayfasi en.json'a BAGLIDIR — devralma yuzunden.
 */
function entry_dependency_files(string $id, string $lang, ?string $root = null): array
{
    $dir   = entry_dir($id, $root);
    $files = [$dir . '/common.json', $dir . '/' . $lang . '.json'];
    $doc   = entry_read($dir . '/' . $lang . '.json');
    $src   = (string)($doc['assessmentSourceLocale'] ?? DEFAULT_LANG);
    $files[] = $dir . '/' . $src . '.json';   // TR sayfasi en.json'a BAGLI
    // inheritedSources ise global kaynak dosyasi da bagimliliktir.
    if (!empty($doc['inheritedSources'])) {
        $files[] = $dir . '/' . DEFAULT_LANG . '.json';
    }
    return array_values(array_unique(array_filter($files, 'is_file')));
}

/**
 * Bir entry'yi istenen dilde yukle.
 * @return array|null Sablonlarin bekledigi duz dizi; yayinlanmamissa null.
 */
function load_entry(string $id, string $lang = DEFAULT_LANG, ?string $root = null): ?array
{
    if (!valid_slug($id) || !in_array($lang, LANGS, true)) {
        return null;
    }
    $dir    = entry_dir($id, $root);
    $common = entry_read($dir . '/common.json');
    $doc    = entry_read($dir . '/' . $lang . '.json');
    if ($common === null || !entry_lang_publishable($doc, $common)) {
        return null;
    }

    // Yargi kaynagi: kendisi kaynaksa kendisi, degilse sourceLocale'in dosyasi.
    $srcLang = (string)($doc['assessmentSourceLocale'] ?? DEFAULT_LANG);
    $src     = $srcLang === $lang ? $doc : entry_read($dir . '/' . $srcLang . '.json');
    if ($src === null) {
        return null;
    }

    $out = [
        'id'       => $id,
        'slug'     => (string)$doc['slug'],
        'lang'     => $lang,
        'category' => (string)($common['category'] ?? ''),
        'title'    => (string)$doc['title'],
        'oneLiner' => (string)$doc['oneLiner'],
        'summary'  => (string)$doc['summary'],
        'whatSurvives' => (string)$doc['whatSurvives'],
        'adaptPrompt'  => (string)$doc['adaptPrompt'],
        'adaptTools'   => (array)($doc['adaptTools'] ?? []),
        'aka'          => (array)($doc['aka'] ?? []),
        'formerSlugs'  => (array)($doc['formerSlugs'] ?? []),
        'assessmentScope'        => (string)($doc['assessmentScope'] ?? 'global'),
        'assessmentSourceLocale' => $srcLang,
        'translationReviewed'    => (string)($doc['translationReviewed'] ?? ''),
    ];

    // Yargi alanlari: kaynak dosyadan (spec 3.1, K1).
    foreach (INHERITED_FIELDS as $f) {
        if (isset($src[$f])) {
            $out[$f] = $src[$f];
        }
    }

    // inheritedSources: yerel kaynaklar global kaynaklarin YERINE degil USTUNE gelir
    // (spec 3.3). Yalnizca kaynak dosyanin kendisi bunu talep edebilir.
    if ($srcLang === $lang && !empty($doc['inheritedSources'])) {
        $global = entry_read($dir . '/' . DEFAULT_LANG . '.json');
        $out['sources'] = array_values(array_unique(array_merge(
            (array)($global['sources'] ?? []),
            (array)($doc['sources'] ?? [])
        )));
    }

    // Uyumluluk takma adi: 14 cagri yeri Faz 2'de dokunulmadan kalsin (K3).
    $out['lastReviewed'] = (string)($out['assessmentReviewed'] ?? '');
    if (isset($doc['geoAnswer'])) {
        $out['geoAnswer'] = (string)$doc['geoAnswer'];
    }
    if (isset($src['reviewed'])) {
        $out['reviewed'] = $src['reviewed'];
    }

    // Gorevler: taskOrder sirasinda LISTEYE duzlestirilir (K3).
    // Sira dil dosyasinda ezilebilir; localTasks yalnizca o dilde bulunur (spec 2.3).
    $out['tasks'] = [];
    $srcTasks   = (array)($src['tasks'] ?? []);
    $localTasks = (array)($doc['localTasks'] ?? []);
    $order      = (array)($doc['taskOrder'] ?? $common['taskOrder'] ?? []);
    foreach ($order as $tid) {
        $tid  = (string)$tid;
        $mine = (array)($doc['tasks'][$tid] ?? $localTasks[$tid] ?? []);
        $from = (array)($srcTasks[$tid] ?? []);
        if ($mine === [] && $from === []) {
            continue;
        }
        $task = ['name' => (string)($mine['name'] ?? $from['name'] ?? '')];
        // Gorev yargisi da devralinir; yerel dosya ACIKCA ezebilir (spec 3.3).
        $task['verdict'] = (string)($mine['verdict'] ?? $from['verdict'] ?? '');
        $note = (string)($mine['note'] ?? '');
        if ($note !== '') {
            $task['note'] = $note;
        } elseif (isset($from['note']) && $srcLang === $lang) {
            $task['note'] = (string)$from['note'];
        }
        $tags = $mine['tags'] ?? $from['tags'] ?? null;
        if ($tags !== null && $tags !== []) {
            $task['tags'] = array_values((array)$tags);
        }
        $out['tasks'][] = $task;
    }

    return $out;
}

/**
 * Sayfanin gercekten anlamli icerik degisikligi gordugu tarih (spec 5.2).
 * Dosya mtime'i BILEREK kullanilmaz: build ve sablon duzenlemesi lastmod'u
 * oynatirsa sitemap yalan soyler.
 *
 * assessmentReviewed veride YYYY-MM tasinir, translationReviewed YYYY-MM-DD.
 * Ikisi de ayin ilkine acilarak karsilastirilir; sozlesme her zaman YYYY-MM-DD.
 * Bicimi tanimayan deger tarih sayilmaz — uydurulmus tarih, tarihsizlikten kotudur.
 */
function entry_lastmod_from(array $job): string
{
    $dates = [];
    foreach ([$job['assessmentReviewed'] ?? '', $job['translationReviewed'] ?? ''] as $raw) {
        $d = (string)$raw;
        if (preg_match('/^\d{4}-\d{2}$/', $d) === 1) {
            $d .= '-01';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1) {
            $dates[] = $d;
        }
    }
    return $dates === [] ? '' : max($dates);
}

function entry_lastmod(string $id, string $lang): string
{
    $job = load_entry($id, $lang);
    return $job === null ? '' : entry_lastmod_from($job);
}

/**
 * Sabit sayfalarin editoryal inceleme tarihi (data/page-reviewed.json).
 * lastmod ailesi burada yasiyor: entry'ler entry_lastmod(), sabit sayfalar bu.
 * Bilinmeyen tarih BOS doner ve sitemap o satiri lastmod'suz yayinlar —
 * uydurulmus bir tarih, lastmod'un hic olmamasindan kotudur (spec 5.2).
 */
function page_reviewed(string $lang, string $key): string
{
    static $table = null;
    if ($table === null) {
        $raw   = @file_get_contents(ROOT . '/data/page-reviewed.json');
        $table = $raw === false ? [] : (array)json_decode($raw, true);
    }
    return (string)($table[$lang][$key] ?? '');
}

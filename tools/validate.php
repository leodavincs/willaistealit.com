<?php
/**
 * Sema dogrulayici.
 *   CLI:  php tools/validate.php
 *   Web:  /tools/validate.php?key=BUILD_KEY   (.htaccess bunu 404'ler; lokalde kullan)
 * Cikis kodu: hata varsa 1.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/routes_cache.php';   // PAGE_SLUGS
require_once __DIR__ . '/../inc/routes_audit.php';   // routes_leak_errors, hreflang_reciprocity_errors

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!build_key_ok($_GET['key'] ?? null)) {
        http_response_code(403);
        exit("forbidden — set BUILD_KEY in inc/config.php first\n");
    }
}

const REQUIRED_FIELDS = ['slug', 'title', 'oneLiner', 'summary', 'tasks',
                         'whatSurvives', 'adaptPrompt'];

/** Yargi alanlari — YALNIZCA kaynak dosya tasiyabilir (spec 3.1). */
const JUDGMENT_FIELDS = ['verdict', 'safeUntil', 'resistanceTags', 'sources',
                         'evidenceStrength', 'assessmentReviewed'];

$errors   = [];
$warnings = [];
$count    = 0;

$reserved = array_merge(LANGS, ['og', 'tools', 'assets', 'fonts']);

foreach (glob(JOBS_DIR . '/*/common.json') ?: [] as $commonPath) {
    $id  = basename(dirname($commonPath));
    $dir = dirname($commonPath);
    $count++;

    if (!valid_slug($id)) {
        $errors[] = "$id: dizin adi gecerli bir slug degil";
        continue;
    }

    $common = json_decode((string)file_get_contents($commonPath), true);
    if (!is_array($common)) {
        $errors[] = "$id/common.json: gecersiz JSON — " . json_last_error_msg();
        continue;
    }
    if (($common['id'] ?? null) !== $id) {
        $errors[] = "$id/common.json: 'id' dizin adiyla uyusmuyor";
    }
    if (!in_array($common['category'] ?? '', CATEGORY_KEYS, true)) {
        $errors[] = "$id/common.json: bilinmeyen kategori '" . ($common['category'] ?? '') . "'";
    }
    $order = (array)($common['taskOrder'] ?? []);
    if ($order === []) {
        $errors[] = "$id/common.json: 'taskOrder' bos";
    }
    if (count($order) !== count(array_unique($order))) {
        $errors[] = "$id/common.json: 'taskOrder' tekrarli id iceriyor";
    }
    if (count($order) < 4 || count($order) > 8) {
        $warnings[] = "$id: " . count($order) . " gorev var — plan 4-8 arasi oneriyor";
    }

    $seenSlugs = [];
    foreach (LANGS as $lang) {
        $file = $dir . '/' . $lang . '.json';
        if (!is_file($file)) {
            continue;
        }
        $where = "$id/$lang.json";
        $doc   = json_decode((string)file_get_contents($file), true);
        if (!is_array($doc)) {
            $errors[] = "$where: gecersiz JSON — " . json_last_error_msg();
            continue;
        }

        foreach (REQUIRED_FIELDS as $f) {
            if (!isset($doc[$f]) || $doc[$f] === '' || $doc[$f] === []) {
                $errors[] = "$where: zorunlu alan eksik veya bos — '$f'";
            }
        }

        // --- Sahiplik (spec 3.1) ---
        $srcLang = (string)($doc['assessmentSourceLocale'] ?? DEFAULT_LANG);
        $isOwner = $srcLang === $lang;
        if (!in_array($srcLang, LANGS, true)) {
            $errors[] = "$where: bilinmeyen assessmentSourceLocale '$srcLang'";
        }
        $scope = (string)($doc['assessmentScope'] ?? '');
        if (!in_array($scope, ['global', 'local'], true)) {
            $errors[] = "$where: assessmentScope 'global' ya da 'local' olmali";
        }
        if ($scope === 'local' && !$isOwner) {
            $errors[] = "$where: yerel kapsam kendi dilini kaynak gostermeli";
        }

        foreach (JUDGMENT_FIELDS as $f) {
            $has = isset($doc[$f]);
            if ($isOwner && !$has && $f !== 'safeUntil') {
                $errors[] = "$where: kaynak dosya '$f' tasimak zorunda";
            }
            if (!$isOwner && $has) {
                $errors[] = "$where: kaynak olmayan dosya '$f' tasiyamaz — devralinir";
            }
        }
        if (!$isOwner && empty($doc['translationReviewed'])) {
            $errors[] = "$where: 'translationReviewed' zorunlu";
        }
        if ($scope === 'local' && empty($doc['sources'])) {
            $errors[] = "$where: yerel degerlendirme kendi kaynagini tasimali (spec 7.1)";
        }

        // --- Slug ---
        $slug = (string)($doc['slug'] ?? '');
        if (!valid_slug($slug)) {
            $errors[] = "$where: gecersiz slug '$slug'";
        } elseif (in_array($slug, $reserved, true) || isset(PAGE_SLUGS[$lang][$slug])
                  || in_array($slug, PAGE_SLUGS[$lang], true)) {
            $errors[] = "$where: '$slug' rezerve kelime ya da sabit sayfa slug'i";
        }
        foreach ((array)($doc['formerSlugs'] ?? []) as $former) {
            if (!valid_slug((string)$former)) {
                $errors[] = "$where: gecersiz formerSlug '$former'";
            }
        }
        $seenSlugs[$lang] = $slug;

        // --- Gorevler: sira + metin kapsami ---
        $langOrder = (array)($doc['taskOrder'] ?? $order);
        $local     = (array)($doc['localTasks'] ?? []);
        foreach ($langOrder as $tid) {
            $tid  = (string)$tid;
            $task = $doc['tasks'][$tid] ?? $local[$tid] ?? null;
            if (!is_array($task)) {
                $errors[] = "$where: '$tid' gorevi eksik";
                continue;
            }
            if ((string)($task['name'] ?? '') === '') {
                $errors[] = "$where: '$tid' gorevinin adi bos";
            }
            if ((string)($task['note'] ?? '') === '') {
                $errors[] = "$where: '$tid' gorevinin notu bos — not devralinmaz";
            }
            $tv = $task['verdict'] ?? null;
            if ($isOwner && !isset(TASK_VERDICTS[(string)$tv])) {
                $errors[] = "$where: '$tid' gorev verdict'i gone|going|safe olmali";
            }
            if (!$isOwner && $tv !== null && !isset(TASK_VERDICTS[(string)$tv])) {
                $errors[] = "$where: '$tid' gecersiz gorev verdict override'i";
            }
            foreach ((array)($task['tags'] ?? []) as $tag) {
                if (!in_array($tag, RESISTANCE_KEYS, true)) {
                    $errors[] = "$where: bilinmeyen direnc tag'i '$tag'";
                }
            }
        }
        foreach (array_keys($local) as $ltid) {
            if (!in_array((string)$ltid, $langOrder, true)) {
                $errors[] = "$where: localTasks '$ltid' taskOrder'da yok";
            }
        }

        // --- Yuklenmis haliyle rubrik esikleri ---
        $job = load_entry($id, $lang);
        if ($job === null) {
            $errors[] = "$where: yuklenemedi (yayinlanmamis sayilir)";
            continue;
        }
        if (!isset(VERDICTS[$job['verdict'] ?? ''])) {
            $errors[] = "$where: bilinmeyen verdict '" . ($job['verdict'] ?? '') . "'";
        }
        $gone = 0;
        foreach ($job['tasks'] as $tk) {
            if (($tk['verdict'] ?? '') === 'gone') {
                $gone++;
            }
        }
        if (($job['verdict'] ?? '') === 'safe' && $gone > 0) {
            // CONTRIBUTING.md'de yayinlanmis sert esik.
            $errors[] = "$where: 'safe' verdict'te $gone adet 'gone' gorev var";
        }
        if (($job['verdict'] ?? '') === 'safe' && !empty($job['safeUntil'])) {
            $errors[] = "$where: 'safe' verdict'te safeUntil olamaz";
        }
        if (($job['verdict'] ?? '') !== 'safe' && empty($job['safeUntil'])) {
            $warnings[] = "$where: safe olmayan verdict'te safeUntil bekleniyor";
        }
        if (!empty($job['safeUntil']) && !preg_match('/^(19|20)\d{2}$/', (string)$job['safeUntil'])) {
            $errors[] = "$where: safeUntil 4 haneli yil olmali";
        }
        if (!preg_match('/^(19|20)\d{2}-(0[1-9]|1[0-2])(-\d{2})?$/', (string)($job['lastReviewed'] ?? ''))) {
            $errors[] = "$where: assessmentReviewed YYYY-MM ya da YYYY-MM-DD olmali";
        }
        if (mb_strlen((string)$job['adaptPrompt']) < 200) {
            $warnings[] = "$where: adaptPrompt cok kisa";
        }
        if (empty($job['sources'])) {
            $warnings[] = "$where: 'sources' bos — community draft olarak isaretlenecek";
        }
        if (mb_strlen((string)$job['oneLiner']) > 120) {
            $warnings[] = "$where: oneLiner 120 karakteri asiyor — OG kartinda kesilir";
        }
        // Tazelik: ceviri, guncellenen degerlendirmenin gerisinde mi (spec 3.4).
        // Sayfa ayni hesabi entry_translation_stale() ile yapiyor — tek nokta.
        if (!$isOwner && entry_translation_stale($job) !== '') {
            $warnings[] = "$where: ceviri (" . (string)($doc['translationReviewed'] ?? '')
                        . ") degerlendirmeden (" . (string)($job['lastReviewed'] ?? '') . ") eski — bayat";
        }

        // Yerel override sonrasi 'gone' sayisi kaynak dilden fazlaysa UYARI:
        // ceviri, devraldigi yargidan daha karamsar hale gelmis demektir. Hata
        // degil — 🟡/🔴 ayrimi makineyle olculemez, insan yeniden degerlendirir.
        if (!$isOwner) {
            $srcJob = load_entry($id, $srcLang);
            if ($srcJob !== null) {
                $srcGone = 0;
                foreach ((array)($srcJob['tasks'] ?? []) as $tk) {
                    if (($tk['verdict'] ?? '') === 'gone') {
                        $srcGone++;
                    }
                }
                if ($gone > $srcGone) {
                    $warnings[] = "$where: yerel 'gone' sayisi ($gone) kaynak dilden ($srcGone) fazla"
                                . " — yargiyi yeniden degerlendir";
                }
            }
        }
    }

    if (!is_file($dir . '/' . DEFAULT_LANG . '.json')) {
        $errors[] = "$id: " . DEFAULT_LANG . ".json yok — kaynak dil zorunlu";
    }
}

// Ayni dilde slug tekilligi ve formerSlug golgelemesi
$byLang = [];
foreach (glob(JOBS_DIR . '/*/common.json') ?: [] as $commonPath) {
    $id = basename(dirname($commonPath));
    foreach (LANGS as $lang) {
        $file = dirname($commonPath) . '/' . $lang . '.json';
        if (!is_file($file)) {
            continue;
        }
        $doc = json_decode((string)file_get_contents($file), true);
        foreach (array_merge([(string)($doc['slug'] ?? '')], (array)($doc['formerSlugs'] ?? [])) as $s) {
            if ($s === '') {
                continue;
            }
            if (isset($byLang[$lang][$s]) && $byLang[$lang][$s] !== $id) {
                $errors[] = "$lang: '$s' hem '{$byLang[$lang][$s]}' hem '$id' tarafindan isteniyor";
            }
            $byLang[$lang][$s] = $id;
        }
    }
}

// --- Yonlendirme tablosunun butunlugu (spec 7) ---
// build_routes() catisma bulursa bunlar SESSIZ kalmamali: cakisan bir slug
// yayinda "hangi entry once gelirse o" davranisi uretir.
$vConflicts = null;
$vRoutes    = build_routes($vConflicts);
foreach ((array)$vConflicts as $c) {
    $errors[] = "routes: $c";
}

// 'aka' ARAMA verisidir, ADRES degil: yonlendirme tablosuna sizmamali.
// Kural inc/routes_audit.php'de SAF fonksiyon olarak yasiyor — sentetik bozuk
// tablolarla test edilebilsin diye (tests/routes_audit.test.php).
$vExpected = [];
foreach (LANGS as $lang) {
    foreach (array_keys((array)($vRoutes['published'] ?? [])) as $rid) {
        if (!in_array($lang, (array)$vRoutes['published'][$rid], true)) {
            continue;
        }
        $rdoc = load_entry((string)$rid, $lang);
        if ($rdoc === null) {
            continue;
        }
        $vExpected[$lang][(string)$rdoc['slug']] = (string)$rid;
        foreach ((array)($rdoc['formerSlugs'] ?? []) as $former) {
            if (valid_slug((string)$former)) {
                $vExpected[$lang][(string)$former] = (string)$rid;
            }
        }
    }
}
foreach (routes_leak_errors($vRoutes, $vExpected) as $e) {
    $errors[] = $e;
}

// hreflang karsilikliligi — GERCEK URL cozumlemesiyle (spec 5.1).
foreach (hreflang_reciprocity_errors($vRoutes) as $e) {
    $errors[] = $e;
}

// --- Editoryal ceviri blocker'i (Faz 3F) ---
// Bir dil AKTIF ise o dilde bekleyen editoryal anahtar KALMAMALI. Aktif olmayan
// dillerde yalnizca bilgi amacli uyari — lansmandan once kapanmasi gereken is.
$active = load_routes()['activeLangs'] ?? [DEFAULT_LANG];
foreach (LANGS as $lang) {
    $pending = locale_pending($lang);
    if ($pending === []) {
        continue;
    }
    $n = count($pending);
    if (in_array($lang, $active, true)) {
        $errors[] = "locale/$lang: $n editoryal anahtar cevrilmemis — bu dil AKTIF, "
                  . "lansman blocker'i (ilk: " . implode(', ', array_slice($pending, 0, 3)) . ')';
    } else {
        $warnings[] = "locale/$lang: $n editoryal anahtar cevrilmeyi bekliyor — "
                    . "bu dil aktive edilmeden once sifirlanmali";
    }
}

// --- Arama katlama haritasi (spec 6.1) ---
// Harita PHP ve JS tarafindan PAYLASILIR. Bozulursa iki taraf sessizce ayrisir,
// o yuzden fixture'lar burada da kosuluyor.
$foldRaw  = (string)@file_get_contents(ROOT . '/data/search-fold.json');
$foldSpec = json_decode($foldRaw, true);
if (!is_array($foldSpec) || !isset($foldSpec['map'], $foldSpec['fixtures'])) {
    $errors[] = 'search-fold: data/search-fold.json okunamadi ya da map/fixtures eksik';
} else {
    // json_decode mukerrer anahtarda SESSIZCE sonuncuyu alir; ham metinde sayiyoruz.
    preg_match_all('/"(.)"\s*:/u', $foldRaw, $m);
    $dups = array_keys(array_filter(array_count_values($m[1]), static fn (int $c): bool => $c > 1));
    if ($dups !== []) {
        $errors[] = 'search-fold: mukerrer harita anahtari: ' . implode(', ', $dups);
    }

    foreach ($foldSpec['fixtures'] as $in => $expected) {
        $got = search_fold((string)$in);
        if ($got !== (string)$expected) {
            $errors[] = "search-fold: '$in' -> '$got' bekleniyordu '$expected'";
        }
    }

    // JS tarafi ayni sonucu veriyor mu. node yoksa atlanir — ama sessizce degil.
    if ($cli) {
        $node = trim((string)@shell_exec('command -v node 2>/dev/null'));
        if ($node === '') {
            $warnings[] = 'search-fold: node bulunamadi, JS/PHP parity kontrolu ATLANDI';
        } else {
            $out = [];
            $rc  = 0;
            exec('node ' . escapeshellarg(ROOT . '/tools/fold-check.js') . ' 2>&1', $out, $rc);
            if ($rc !== 0) {
                $errors[] = 'search-fold: JS fold() PHP ile ayrisiyor — ' . implode(' | ', $out);
            }
        }
    }
}

// --- Sabit sayfa inceleme tarihleri (spec 5.2) ---
// Sitemap lastmod'u data/page-reviewed.json'dan gelir. Eksik tarih UYARI: sitemap
// o satiri lastmod'suz basar ve gecerli XML uretir. Bicimi bozuk tarih HATA:
// sessizce yanlis bir lastmod yayinlamak, hic yayinlamamaktan kotudur.
foreach ($active as $lang) {
    foreach (array_keys(PAGE_SLUGS[$lang] ?? []) as $key) {
        $d = page_reviewed((string)$lang, (string)$key);
        if ($d === '') {
            $warnings[] = "page-reviewed/$lang: '$key' icin inceleme tarihi yok — "
                        . "sitemap o satiri lastmod'suz yayinlar";
            continue;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) !== 1) {
            $errors[] = "page-reviewed/$lang: '$key' tarihi YYYY-MM-DD degil: '$d'";
        }
    }
}

// --- changelog tutarliligi ---
$log = load_changelog();
foreach ($log as $i => $e) {
    $where = "changelog.json[$i]";
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($e['date'] ?? ''))) {
        $errors[] = "$where: date YYYY-MM-DD olmali";
    }
    $slug = (string)($e['slug'] ?? '');
    if (!valid_slug($slug)) {
        $errors[] = "$where: gecersiz slug '$slug'";
    } elseif (!is_file(JOBS_DIR . '/' . $slug . '/common.json')) {
        $warnings[] = "$where: '$slug' entry'si artik yok";
    }
    if (!isset(VERDICTS[(string)($e['to'] ?? '')])) {
        $errors[] = "$where: 'to' gecerli bir verdict olmali";
    }
    $from = $e['from'] ?? null;
    if ($from !== null && $from !== '' && !isset(VERDICTS[(string)$from])) {
        $errors[] = "$where: 'from' null ya da gecerli bir verdict olmali";
    }
    if (empty($e['why'])) {
        $warnings[] = "$where: 'why' bos — degisikligin sebebi yazilmali";
    }
}

// --- yayinda olup changelog'a girmemis entry'ler ---
$logged = array_column($log, 'slug');
foreach (array_keys(load_all_jobs()) as $slug) {
    if (!in_array($slug, $logged, true)) {
        $warnings[] = "$slug: changelog.json'da kaydi yok — tazelik sinyali eksik kalir";
    }
}

$out = static function (string $s) use ($cli): void {
    echo $s . "\n";
};

$out("$count entry tarandi (dizin yapisi).");
if ($warnings) {
    $out("\n" . count($warnings) . " uyari:");
    foreach ($warnings as $w) {
        $out("  ! $w");
    }
}
if ($errors) {
    $out("\n" . count($errors) . " HATA:");
    foreach ($errors as $e) {
        $out("  x $e");
    }
    if ($cli) {
        exit(1);
    }
    http_response_code(422);
    exit;
}
$out("\nHata yok.");

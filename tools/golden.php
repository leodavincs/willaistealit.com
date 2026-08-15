<?php
/**
 * Ingilizce ciktinin golden referansi. Iki katman (spec 12.2):
 *   Katman 1 — byte: ham govde tests/golden/<ad>.<uzanti>
 *   Katman 2 — semantik: icerik TURUNE gore cikarilan alanlar tests/golden/<ad>.json
 *
 *   ./tools/golden.sh --capture              # yakala
 *   ./tools/golden.sh --check [--semantic]   # karsilastir
 *   php tools/golden.php --self-test         # karsilastirici kirmizi verebiliyor mu
 *
 * --self-test SUNUCU GEREKTIRMEZ ve HICBIR KAYNAK DOSYAYA DOKUNMAZ: yakalanmis
 * golden'i okur, kopyayi BELLEKTE bozar ve karsilastiricinin reddettigini kanitlar.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

const GOLDEN_DIR = ROOT . '/tests/golden';

/** [ad => [yol, uzanti]] — 11 HTML + 1 metin + 1 XML + 2 binary = 15 hedef. */
const TARGETS = [
    'home'                     => ['/',                         'html'],
    'cashier'                  => ['/cashier',                  'html'],
    'accountant'               => ['/accountant',               'html'],
    'nurse'                    => ['/nurse',                    'html'],
    'translator'               => ['/translator',               'html'],
    'administrative-assistant' => ['/administrative-assistant',  'html'],
    'methodology'              => ['/methodology',              'html'],
    'landscape'                => ['/landscape',                'html'],
    'changelog'                => ['/changelog',                'html'],
    'sponsor'                  => ['/sponsor',                  'html'],
    'notfound'                 => ['/unknown',                  'html'],
    'llms'                     => ['/llms.txt',                 'txt'],
    'sitemap'                  => ['/sitemap.xml',              'xml'],
    'og-cashier'               => ['/og/cashier.png',           'png'],
    'og-home'                  => ['/og/home.png',              'png'],
];

function golden_body_path(string $name): string
{
    return GOLDEN_DIR . '/' . $name . '.' . TARGETS[$name][1];
}

function golden_meta_path(string $name): string
{
    return GOLDEN_DIR . '/' . $name . '.json';
}

/** @return array{status:int,contentType:string,body:string,error:string} */
function golden_fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    return ['status' => $code, 'contentType' => $ct,
            'body' => $body === false ? '' : (string)$body, 'error' => $err];
}

// ---------------------------------------------------------------- cikaricilar

function golden_norm_ws(string $s): string
{
    return trim((string)preg_replace('/[ \t]+/', ' ', str_replace("\r\n", "\n", $s)));
}

/** JSON-LD: anahtarlari ozyinelemeli siralar — sira farki anlamsizdir. */
function golden_sort_deep(mixed $v): mixed
{
    if (!is_array($v)) {
        return $v;
    }
    $v = array_map('golden_sort_deep', $v);
    if (!array_is_list($v)) {
        ksort($v);
    }
    return $v;
}

function golden_extract_html(array $res): array
{
    $b = $res['body'];
    $one = static function (string $re) use ($b): ?string {
        return preg_match($re, $b, $m) === 1 ? golden_norm_ws($m[1]) : null;
    };

    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $b, $ld);
    $jsonLd = [];
    foreach ($ld[1] as $raw) {
        $jsonLd[] = golden_sort_deep(json_decode($raw, true));
    }

    preg_match_all('#<span class="task-name">(.*?)</span>#s', $b, $tn);
    preg_match_all('#<span class="pill">(.*?)</span>#s', $b, $tp);
    $tasks = [];
    foreach ($tn[1] as $i => $name) {
        $tasks[] = golden_norm_ws($name) . ' [' . golden_norm_ws($tp[1][$i] ?? '') . ']';
    }

    preg_match_all('#\shref="([^"]*)"#', $b, $hrefs);
    $links = array_values(array_unique($hrefs[1]));
    sort($links);

    return [
        'status'       => $res['status'],
        'contentType'  => strtolower(explode(';', $res['contentType'])[0]),
        'htmlLang'     => $one('#<html lang="([a-z-]+)"#i'),
        'title'        => $one('#<title>(.*?)</title>#s'),
        'description'  => $one('#<meta name="description" content="([^"]*)"#i'),
        'canonical'    => $one('#<link rel="canonical" href="([^"]*)"#i'),
        'robots'       => $one('#<meta name="robots" content="([^"]*)"#i'),
        'h1'           => $one('#<h1[^>]*>(.*?)</h1>#s'),
        'verdictLabel' => $one('#<span class="badge badge-lg">(.*?)</span>#s'),
        'taskCount'    => count($tasks),
        'tasks'        => $tasks,
        'jsonLd'       => $jsonLd,
        'links'        => $links,
    ];
}

function golden_extract_text(array $res): array
{
    return [
        'status'      => $res['status'],
        'contentType' => strtolower(explode(';', $res['contentType'])[0]),
        'body'        => golden_norm_ws($res['body']),
    ];
}

/** Sitemap XML olarak PARSE edilir — duz metin karsilastirmasi degil. */
function golden_extract_sitemap(array $res): array
{
    $out = ['status' => $res['status'],
            'contentType' => strtolower(explode(';', $res['contentType'])[0]),
            'urls' => []];

    $prev = libxml_use_internal_errors(true);
    $xml  = simplexml_load_string($res['body']);
    libxml_use_internal_errors($prev);
    if ($xml === false) {
        $out['urls'] = ['XML PARSE EDILEMEDI'];
        return $out;
    }

    foreach ($xml->url as $u) {
        $row = [
            'loc'        => (string)$u->loc,
            'lastmod'    => (string)$u->lastmod,
            'changefreq' => (string)$u->changefreq,
            'priority'   => (string)$u->priority,
            'alternates' => [],
        ];
        foreach ($u->children('http://www.w3.org/1999/xhtml')->link ?? [] as $alt) {
            $row['alternates'][] = (string)$alt['hreflang'] . '=' . (string)$alt['href'];
        }
        sort($row['alternates']);
        $out['urls'][] = $row;
    }
    usort($out['urls'], static fn ($a, $b) => strcmp($a['loc'], $b['loc']));
    return $out;
}

/** Icerik turune gore cikarici sec. HTML cikaricisi XML/metne UYGULANMAZ. */
function golden_extract(string $name, array $res): array
{
    $ct = strtolower($res['contentType']);
    if (str_contains($ct, 'text/html')) {
        return golden_extract_html($res);
    }
    if (str_contains($ct, 'xml')) {
        return golden_extract_sitemap($res);
    }
    if (str_contains($ct, 'image/')) {
        return ['status' => $res['status'],
                'contentType' => strtolower(explode(';', $ct)[0]),
                'md5' => md5($res['body'])];
    }
    return golden_extract_text($res);
}

/** @return string[] Farkli olan ust duzey alanlar. */
function golden_diff(array $a, array $b): array
{
    $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
    $out = [];
    foreach ($keys as $k) {
        if (($a[$k] ?? null) !== ($b[$k] ?? null)) {
            $out[] = $k;
        }
    }
    return $out;
}

// ---------------------------------------------------------------- self-test

function golden_self_test(): int
{
    $cases = [
        // Etikete DOGRUDAN dokunulur: sayfanin verdict'i ne olursa olsun calisir.
        // (Ilk deneme 'SHRINKING' arayip degistiriyordu; cashier 'ON THE MENU'
        //  oldugu icin hicbir sey bozulmuyordu ve self-test bunu yakaladi.)
        ['cashier', 'text/html; charset=UTF-8',
         static fn (string $b): string => (string)preg_replace(
             '#(<span class="badge badge-lg">)([^<]*)#', '$1BOZUK', $b, 1),
         'verdictLabel'],
        ['sitemap', 'application/xml; charset=utf-8',
         static fn (string $b): string => (string)preg_replace('#<priority>0\.9</priority>#', '<priority>0.8</priority>', $b, 1),
         'urls'],
        ['llms', 'text/plain; charset=utf-8',
         static fn (string $b): string => $b . "\nBOZULDU\n",
         'body'],
        ['og-cashier', 'image/png',
         static fn (string $b): string => $b . 'x',
         'md5'],
    ];

    $fail = 0;
    foreach ($cases as [$name, $ct, $corrupt, $field]) {
        $file = golden_body_path($name);
        if (!is_file($file)) {
            printf("  HATA %-12s golden yok (%s) — once --capture\n", $name, basename($file));
            $fail++;
            continue;
        }
        $body = (string)file_get_contents($file);
        $good = golden_extract($name, ['status' => 200, 'contentType' => $ct, 'body' => $body]);
        $bad  = golden_extract($name, ['status' => 200, 'contentType' => $ct, 'body' => $corrupt($body)]);

        if (golden_diff($good, $good) !== []) {
            printf("  HATA %-12s bozulmamis kopya farkli gorunuyor\n", $name);
            $fail++;
            continue;
        }
        $diff = golden_diff($good, $bad);
        if ($diff === []) {
            printf("  HATA %-12s bozulma YAKALANMADI (%s)\n", $name, $field);
            $fail++;
        } elseif (!in_array($field, $diff, true)) {
            printf("  HATA %-12s yakalandi ama yanlis alanda: %s\n", $name, implode(',', $diff));
            $fail++;
        } else {
            printf("  ok   %-12s bozulma '%s' alaninda yakalandi\n", $name, $field);
        }
    }

    echo $fail === 0
        ? "\nKarsilastirici dort icerik turunde de kirmizi verebiliyor.\n"
        : "\n$fail vaka basarisiz.\n";
    return $fail === 0 ? 0 : 1;
}

// ---------------------------------------------------------------- capture / check

function golden_write(string $name, array $res): void
{
    if (!is_dir(GOLDEN_DIR)) {
        mkdir(GOLDEN_DIR, 0775, true);
    }
    file_put_contents(golden_body_path($name), $res['body']);
    file_put_contents(golden_meta_path($name), json_encode(
        golden_extract($name, $res),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . "\n");
}

function golden_run(string $mode, string $base, bool $semanticOnly): int
{
    $byteFail = $semFail = 0;
    $n = 0;

    foreach (TARGETS as $name => [$path, $ext]) {
        $res = golden_fetch($base . $path);
        if ($res['error'] !== '') {
            printf("  HATA %-26s curl: %s\n", $name, $res['error']);
            return 1;
        }
        $n++;

        if ($mode === '--capture') {
            golden_write($name, $res);
            printf("  yakalandi %-26s %-24s %d bayt\n", $name,
                   explode(';', $res['contentType'])[0], strlen($res['body']));
            continue;
        }

        $bodyFile = golden_body_path($name);
        $metaFile = golden_meta_path($name);
        if (!is_file($bodyFile) || !is_file($metaFile)) {
            printf("  HATA %-26s golden yok — once --capture\n", $name);
            return 1;
        }

        $byteSame = (string)file_get_contents($bodyFile) === $res['body'];
        $old  = json_decode((string)file_get_contents($metaFile), true);
        $new  = golden_extract($name, $res);
        $diff = golden_diff((array)$old, $new);

        if ($diff !== []) {
            printf("  HATA %-26s semantik fark: %s\n", $name, implode(', ', $diff));
            $semFail++;
        } elseif (!$byteSame && !$semanticOnly) {
            printf("  UYARI %-25s byte farkli, semantik ayni\n", $name);
            $byteFail++;
        } else {
            printf("  ok   %-26s%s\n", $name, $byteSame ? '' : ' (byte farkli, semantik ayni)');
        }
    }

    if ($mode === '--capture') {
        printf("\n%d hedef yakalandi -> tests/golden/\n", $n);
        return 0;
    }

    printf("\n%d/%d semantik ayni", $n - $semFail, $n);
    if (!$semanticOnly) {
        printf(" · %d/%d byte-identical", $n - $byteFail - $semFail, $n);
    }
    echo "\n";

    if ($semFail > 0) {
        return 1;
    }
    return ($semanticOnly || $byteFail === 0) ? 0 : 1;
}

// ---------------------------------------------------------------- giris
$args = array_slice($argv, 1);
$mode = '--check';
$semanticOnly = false;
$base = '';
foreach ($args as $a) {
    if ($a === '--capture' || $a === '--check' || $a === '--self-test') {
        $mode = $a;
    } elseif ($a === '--semantic') {
        $semanticOnly = true;
    } elseif (str_starts_with($a, 'http')) {
        $base = rtrim($a, '/');
    }
}

if ($mode === '--self-test') {
    exit(golden_self_test());
}
if (!function_exists('curl_init')) {
    fwrite(STDERR, "HATA: curl uzantisi yok.\n");
    exit(1);
}
if ($base === '') {
    fwrite(STDERR, "HATA: temel adres verilmedi (./tools/golden.sh kullanin).\n");
    exit(1);
}
exit(golden_run($mode, $base, $semanticOnly));

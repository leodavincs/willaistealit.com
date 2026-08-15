<?php
/**
 * HTTP smoke matrisi. Calisan bir sunucuya karsi kosar:
 *   ./tools/smoke.sh                          (onerilen — sunucu omrunu yonetir)
 *   php tools/smoke.php https://willaistealit.com
 * Kontrol: status, Location, yonlendirme ADEDI, canonical'in TAM degeri,
 * <html lang>, ve 404 govdesinde icerik sizintisi olmamasi.
 */
declare(strict_types=1);

if (!function_exists('curl_init')) {
    fwrite(STDERR, "HATA: curl uzantisi yok. php tools/doctor.php ile kontrol et.\n");
    exit(1);
}

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8123', '/');

/** Tek istek — yonlendirmeyi TAKIP ETMEZ; zincir uzunlugunu olcebilmek icin. */
function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hlen = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    // curl_close() YOK: PHP 8.0'dan beri etkisiz, 8.5'te deprecated uyarisi basiyor.

    if ($raw === false) {
        return ['status' => 0, 'location' => null, 'canonical' => null,
                'lang' => null, 'body' => '', 'error' => $err];
    }

    $raw  = (string)$raw;
    $head = substr($raw, 0, $hlen);
    $body = substr($raw, $hlen);

    preg_match('/^Location:\s*(.+)$/mi', $head, $m);
    preg_match('#<link rel="canonical" href="([^"]+)"#i', $body, $c);
    preg_match('#<html lang="([a-z-]+)"#i', $body, $l);

    return ['status' => $code, 'location' => isset($m[1]) ? trim($m[1]) : null,
            'canonical' => $c[1] ?? null, 'lang' => $l[1] ?? null,
            'body' => $body, 'error' => ''];
}

/** Yonlendirme zincirini takip eder, adet dondurur. */
function hops(string $base, string $path, int $max = 5): int
{
    $n = 0;
    $url = $base . $path;
    while ($n < $max) {
        $r = fetch($url);
        if ($r['location'] === null) {
            return $n;
        }
        $n++;
        $url = str_starts_with($r['location'], 'http') ? $r['location'] : $base . $r['location'];
    }
    return $max;
}

/* [yol, status, hedef yol|null, lang|null, canonical|null] */
$matrix = [
    ['/',                          200, null,          'en',  'https://willaistealit.com/'],
    ['/en',                        301, '/',           null,  null],
    ['/en/',                       301, '/',           null,  null],
    ['/accountant',                200, null,          'en',  'https://willaistealit.com/accountant'],
    ['/accountant/',               301, '/accountant', null,  null],
    ['/en/accountant',             301, '/accountant', null,  null],
    ['/en/accountant/',            301, '/accountant', null,  null],
    ['/methodology',               200, null,          'en',  null],
    ['/en/not-a-real-job',         404, null,          null,  null],
    ['/unknown',                   404, null,          null,  null],
    ['/sitemap.xml',               200, null,          null,  null],
    ['/llms.txt',                  200, null,          null,  null],
    ['/og/accountant.png',         200, null,          null,  null],
    ['/og/home.png',               200, null,          null,  null],
    ['/og/tr/accountant.png',      404, null,          null,  null],
    // Guvenlik (spec 1.8)
    ['/data/jobs/accountant.json', 404, null,          null,  null],
    ['/inc/config.php',            404, null,          null,  null],
    ['/cache/index.json',          404, null,          null,  null],
    ['/research/sources.json',     404, null,          null,  null],
    ['/tests/run.php',             404, null,          null,  null],
    ['/.git/config',               404, null,          null,  null],
    ['/README.md',                 404, null,          null,  null],
    // Acik kalmasi gerekenler
    ['/assets/style.css',          200, null,          null,  null],
    ['/.well-known/smoke-probe.txt', 200, null,        null,  null],
];

/** 404 govdesi hassas icerik sizdirmamali. */
$leaks = ['adaptPrompt', 'BUILD_KEY', 'config.local', '<?php', 'resistanceTags'];

$fail = 0;
foreach ($matrix as [$path, $status, $target, $lang, $canonical]) {
    $r    = fetch($base . $path);
    $ok   = true;
    $note = '';

    if ($r['error'] !== '') {
        $ok = false;
        $note .= ' [curl: ' . $r['error'] . ']';
    }
    if ($r['status'] !== $status) {
        $ok = false;
        $note .= sprintf(' [status: %d, beklenen: %d]', $r['status'], $status);
    }
    if ($status === 301) {
        $reached = parse_url((string)$r['location'], PHP_URL_PATH) ?: $r['location'];
        if ($reached !== $target) {
            $ok = false;
            $note .= " [hedef: $reached, beklenen: $target]";
        }
        $n = hops($base, $path);
        if ($n !== 1) {
            $ok = false;
            $note .= " [zincir: $n adim, 1 olmali]";
        }
    }
    if ($lang !== null && $r['lang'] !== $lang) {
        $ok = false;
        $note .= sprintf(' [lang: %s, beklenen: %s]', (string)$r['lang'], $lang);
    }
    if ($canonical !== null && $r['canonical'] !== $canonical) {
        $ok = false;
        $note .= sprintf(' [canonical: %s, beklenen: %s]', (string)$r['canonical'], $canonical);
    }
    if ($status === 404) {
        foreach ($leaks as $needle) {
            if (str_contains($r['body'], $needle)) {
                $ok = false;
                $note .= " [SIZINTI: govdede '$needle' var]";
            }
        }
    }

    printf("%-32s %s %d%s\n", $path, $ok ? 'ok  ' : 'HATA', $r['status'], $note);
    if (!$ok) {
        $fail++;
    }
}

echo "\n" . ($fail === 0 ? "Matris temiz.\n" : "$fail satir basarisiz.\n");
exit($fail === 0 ? 0 : 1);

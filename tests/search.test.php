<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/search.php';

$spec = json_decode((string)file_get_contents(ROOT . '/data/search-fold.json'), true);

foreach ($spec['fixtures'] as $in => $expected) {
    t_eq($expected, search_fold($in), "fold: $in");
}

// intl kapaliyken de AYNI sonuc — fallback yolu sinanmadan guvenilmez.
intl_available(false);
foreach ($spec['fixtures'] as $in => $expected) {
    t_eq($expected, search_fold($in), "fold (intl kapali): $in");
}
intl_available(null);

// Harita JSON'unun kendisi: anahtarlar tek karakter, degerler ASCII kucuk harf.
foreach ($spec['map'] as $from => $to) {
    t_eq(1, mb_strlen((string)$from), "harita anahtari tek karakter: $from");
    t_eq($to, strtolower((string)$to), "harita degeri kucuk harf: $from");
}

// --- Dil basina arama indeksi (spec 6.3) ---
// Yol KULLANICI GIRDISINDEN kurulamaz: bilinmeyen kod varsayilana duser.
t_eq(CACHE_DIR . '/index-en.json', index_file('en'),  'EN indeks yolu');
t_eq(CACHE_DIR . '/index-tr.json', index_file('tr'),  'TR indeks yolu');
t_eq(CACHE_DIR . '/index-en.json', index_file(),      'varsayilan dil');
t_eq(CACHE_DIR . '/index-en.json', index_file('zz'),  'bilinmeyen dil varsayilana duser');
t_eq(CACHE_DIR . '/index-en.json', index_file('../../etc/passwd'), 'path traversal kapali');

// Eksik, bos ya da bozuk indeks siteyi cokertmez: arama sunucuda basilan
// tam liste uzerinde calismaya devam eder (spec 6.3).
$tmp = index_file('es');
@unlink($tmp);
t_eq([], load_index('es'), 'indeks dosyasi yoksa bos dizi');
file_put_contents($tmp, '');
t_eq([], load_index('es'), 'bos indeks bos dizi');
file_put_contents($tmp, '{bozuk');
t_eq([], load_index('es'), 'bozuk indeks bos dizi');
file_put_contents($tmp, '"duz metin"');
t_eq([], load_index('es'), 'dizi olmayan indeks bos dizi');
@unlink($tmp);

// aka YONLENDIRME tablosuna sizmaz (spec 7): arama verisidir, adres degil.
// Kume esitligi olarak yaziliyor — 'aka' bugun katalogda BOS oldugu icin
// "aka yok mu" diye bakmak hicbir sey kanitlamaz. Bu test, slug tablosuna
// canonical + formerSlug DISINDA bir sey girerse kirmizi verir.
$akaRoutes = load_routes();
foreach (LANGS as $akaLang) {
    $expected = [];
    foreach (array_keys((array)($akaRoutes['published'] ?? [])) as $id) {
        if (!in_array($akaLang, (array)$akaRoutes['published'][$id], true)) {
            continue;
        }
        $job = load_entry((string)$id, $akaLang);
        if ($job === null) {
            continue;
        }
        $expected[(string)$job['slug']] = (string)$id;
        foreach ((array)($job['formerSlugs'] ?? []) as $former) {
            if (valid_slug((string)$former)) {
                $expected[(string)$former] = (string)$id;
            }
        }
    }
    ksort($expected);
    $got = (array)($akaRoutes['slugs'][$akaLang] ?? []);
    ksort($got);
    t_eq($expected, $got, "$akaLang: routes slug tablosu YALNIZ canonical + formerSlug");
}

// Indeks kayitlari 'a' (aka) alanini tasir: arama verisine katilir.
$idxFile = index_file('en');
if (is_file($idxFile)) {
    $idx = load_index('en');
    t_eq(true, isset($idx['jobs'][0]['a']), "indeks kaydi 'a' (aka) alani tasir");
    t_eq(true, isset($idx['jobs'][0]['f']), "indeks kaydi 'f' (katlanmis) alani tasir");
}

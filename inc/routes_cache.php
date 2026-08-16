<?php
/**
 * Route tablosunun uretimi ve onbellegi.
 * routes.json bir PERFORMANS optimizasyonudur, zorunlu deploy artefakti DEGILDIR:
 * yoksa, bossa, bozuksa ya da SEMANTIK olarak tutarsizsa tablo bellekte uretilir
 * ve site calismaya devam eder.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/routing.php';

/** Sabit sayfalarin dil basina slug'lari. Faz 1'de yalnizca EN dolu. */
const PAGE_SLUGS = [
    'en' => ['methodology' => 'methodology', 'landscape' => 'landscape',
             'changelog'   => 'changelog',   'sponsor'   => 'sponsor'],
    'tr' => [],
    'es' => [],
];

/** Gecici dosyaya yaz, sonra rename — yarim yazilmis dosya kimseye gorunmez. */
function atomic_write(string $file, string $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $data, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * SEMANTIK dogrulama. Yalnizca anahtar varligi yetmez: sekil olarak dogru ama
 * icerigi tutarsiz bir cache butun siteyi yanlis 404'e dusurur.
 */
function routes_valid(mixed $d): bool
{
    if (!is_array($d)) {
        return false;
    }
    foreach (['activeLangs', 'ids', 'slugs', 'published', 'pages', 'pageSlugs'] as $k) {
        if (!isset($d[$k]) || !is_array($d[$k])) {
            return false;
        }
    }
    if ($d['ids'] === []) {
        return false;
    }

    // activeLangs yalnizca bilinen dilleri tasir ve varsayilan dil aktif olmali.
    foreach ($d['activeLangs'] as $l) {
        if (!in_array($l, LANGS, true)) {
            return false;
        }
    }
    if (!in_array(DEFAULT_LANG, $d['activeLangs'], true)) {
        return false;
    }

    // Her published kimligi ids icinde bulunmali; yayinlanan her dilde canonical slug olmali.
    foreach ($d['published'] as $id => $langs) {
        if (!isset($d['ids'][$id]) || !is_array($langs)) {
            return false;
        }
        foreach ($langs as $l) {
            if (!in_array($l, LANGS, true)) {
                return false;
            }
            $slug = $d['ids'][$id][$l] ?? null;
            if (!is_string($slug) || $slug === '') {
                return false;
            }
        }
    }

    // slugs degerleri gecerli bir meslek kimligine gitmeli.
    foreach ($d['slugs'] as $lang => $map) {
        if (!in_array($lang, LANGS, true) || !is_array($map)) {
            return false;
        }
        foreach ($map as $id) {
            if (!isset($d['ids'][$id])) {
                return false;
            }
        }
    }

    // pages ve pageSlugs karsilikli olmali (slug -> anahtar -> ayni slug).
    foreach ($d['pages'] as $lang => $map) {
        if (!is_array($map)) {
            return false;
        }
        foreach ($map as $slug => $key) {
            if (($d['pageSlugs'][$lang][$key] ?? null) !== $slug) {
                return false;
            }
        }
    }
    foreach ($d['pageSlugs'] as $lang => $map) {
        if (!is_array($map)) {
            return false;
        }
        foreach ($map as $key => $slug) {
            if (($d['pages'][$lang][$slug] ?? null) !== $key) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Kaynak JSON'lardan route tablosu. Faz 1: veri hala duz dosya, yalnizca EN.
 * @param array|null $conflicts Slug cakismalari buraya yazilir (istek aninda
 *                              sessiz kalinir; build-index.php hata verir).
 */
function build_routes(?array &$conflicts = null): array
{
    $conflicts = [];
    $ids = $published = [];
    $slugs = ['en' => [], 'tr' => [], 'es' => []];

    $claim = static function (string $lang, string $slug, string $id) use (&$slugs, &$conflicts): void {
        if (in_array($slug, LANGS, true)) {
            $conflicts[] = "$lang: '$slug' rezerve dil kodu";
            return;
        }
        if (isset(PAGE_SLUGS[$lang][$slug]) || in_array($slug, PAGE_SLUGS[$lang], true)) {
            $conflicts[] = "$lang: '$slug' sabit sayfa slug'iyla cakisiyor";
            return;
        }
        if (isset($slugs[$lang][$slug]) && $slugs[$lang][$slug] !== $id) {
            $conflicts[] = "$lang: '$slug' hem '{$slugs[$lang][$slug]}' hem '$id' tarafindan isteniyor";
            return;
        }
        $slugs[$lang][$slug] = $id;
    };

    foreach (glob(JOBS_DIR . '/*/common.json') ?: [] as $path) {
        $id = basename(dirname($path));
        // published GERCEK dil dosyalarindan hesaplanir; activeLangs ayri bir
        // eksendir ve Faz 2'de ['en'] kalir, yani TR/ES servis EDILMEZ.
        $langs = entry_langs($id);
        if ($langs === []) {
            continue;
        }
        $published[$id] = $langs;
        $ids[$id] = [];
        foreach ($langs as $lang) {
            $job = load_entry($id, $lang);
            if ($job === null) {
                continue;
            }
            $ids[$id][$lang] = (string)$job['slug'];
            $claim($lang, (string)$job['slug'], $id);
            foreach ((array)($job['formerSlugs'] ?? []) as $former) {
                if (valid_slug((string)$former)) {
                    $claim($lang, (string)$former, $id);
                }
            }
        }
    }

    $pages = [];
    foreach (PAGE_SLUGS as $lang => $map) {
        $pages[$lang] = [];
        foreach ($map as $key => $slug) {
            $pages[$lang][$slug] = $key;      // slug -> anahtar
        }
    }

    return [
        'activeLangs' => ['en'],
        'ids'         => $ids,
        'slugs'       => $slugs,
        'published'   => $published,
        'pages'       => $pages,
        'pageSlugs'   => PAGE_SLUGS,          // anahtar -> slug
    ];
}

/** Test icin bellek onbellegini bosaltir. */
function routes_cache_reset(): void
{
    $GLOBALS['__routes'] = [];
}

/**
 * Once cache; gecersizse kaynaktan uret ve YAZABILIYORSAN yaz.
 * Yazamamak hata degildir — istek bellekteki tabloyla tamamlanir.
 * @param string|null $file Test icin enjekte edilebilir; null ise ROUTES_FILE.
 */
/**
 * Hangi route tablosu okunacak. WAISI_ROUTES_FILE, TR satirlarini canli
 * cache/routes.json'a DOKUNMADAN kosabilmek icin var (spec 12.1).
 *
 * Uretimde ASLA devreye girmez: canli hostta ortam degiskeni yok sayilir.
 * Boylece is yarida kesilse bile TR acik kalamaz — aktivasyon kapisi (4C1)
 * kazara atlanamaz.
 */
function routes_file(): string
{
    $override = (string)(getenv('WAISI_ROUTES_FILE') ?: '');
    if ($override !== '' && !is_live_host() && is_file($override)) {
        return $override;
    }
    return ROUTES_FILE;
}

function load_routes(?string $file = null): array
{
    $file = $file ?? routes_file();
    if (isset($GLOBALS['__routes'][$file]) && is_array($GLOBALS['__routes'][$file])) {
        return $GLOBALS['__routes'][$file];
    }

    if (is_file($file)) {
        $raw  = @file_get_contents($file);
        $data = $raw === false ? null : json_decode($raw, true);
        if (routes_valid($data)) {
            return $GLOBALS['__routes'][$file] = $data;
        }
    }

    $data = build_routes();
    atomic_write($file, (string)json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $GLOBALS['__routes'][$file] = $data;
}

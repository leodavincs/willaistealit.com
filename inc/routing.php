<?php
/**
 * SAF URL cozumleyici. Dosya sistemine, $_SERVER'a ve global duruma DOKUNMAZ —
 * girdisi (path, routes), ciktisi bir karar dizisi. Boyle oldugu icin
 * .htaccess ve router.php ayni kurallari paylasabiliyor ve hepsi test edilebiliyor.
 */
declare(strict_types=1);

const LANGS        = ['en', 'tr', 'es'];
const DEFAULT_LANG = 'en';

/** Dil prefix'i. Ingilizce prefix'sizdir — kabul edilmis SEO istisnasi (spec 1.1). */
function lang_prefix(string $lang): string
{
    return $lang === DEFAULT_LANG ? '/' : '/' . $lang . '/';
}

/**
 * Guvenlik siniri. GERCEK DOSYA KONTROLUNDEN ONCE calisir (spec 1.8) —
 * ters sirada data/jobs/*.json gercek dosya oldugu icin ham servis edilir.
 */
function path_is_forbidden(string $path): bool
{
    if (preg_match('#^/(data|inc|cache|docs|research|tests)(/|$)#', $path) === 1) {
        return true;
    }
    // Nokta ile baslayan her yol segmenti; .well-known disarida.
    if (preg_match('#(^|/)\.(?!well-known(/|$))#', $path) === 1) {
        return true;
    }
    if (preg_match('#\.(md|example)$#i', $path) === 1) {
        return true;
    }
    return false;
}

function route_redirect(string $to): array
{
    return ['type' => 'redirect', 'status' => 301, 'location' => $to];
}

/** Bir slug'i meslek kimligine cozer: bu dilin tablosu, id'nin kendisi, baska dil. */
function resolve_job_id(string $lang, string $slug, array $routes): ?string
{
    $id = $routes['slugs'][$lang][$slug] ?? null;
    if ($id !== null) {
        return (string)$id;
    }
    if (isset($routes['ids'][$slug])) {
        return $slug;
    }
    foreach (LANGS as $l) {
        if (isset($routes['slugs'][$l][$slug])) {
            return (string)$routes['slugs'][$l][$slug];
        }
    }
    return null;
}

/** OG kart yolu. EN prefix'siz, digerleri /og/<lang>/ altinda (spec 5.6). */
function og_path(string $lang, string $slug): string
{
    return $lang === DEFAULT_LANG
        ? '/og/' . $slug . '.png'
        : '/og/' . $lang . '/' . $slug . '.png';
}

/**
 * OG cozumlemesi. Sayfa cozumlemesiyle AYNI kurallara tabi: aktif dil, kayitli
 * slug, yayin durumu. Gorsel istegi oldugu icin yayinlanmamis durum HTML
 * unavailable sayfasi degil 404 doner.
 */
function resolve_og(string $lang, string $slug, array $routes): array
{
    // Site geneli kart: entry degil, mevcut yol korunuyor (og.php 'home' destekliyor).
    if ($slug === 'home' && $lang === DEFAULT_LANG) {
        return ['type' => 'og', 'lang' => DEFAULT_LANG, 'slug' => 'home'];
    }

    if (!in_array($lang, (array)($routes['activeLangs'] ?? [DEFAULT_LANG]), true)) {
        return ['type' => 'notfound', 'lang' => DEFAULT_LANG];
    }

    $id = resolve_job_id($lang, $slug, $routes);
    if ($id === null) {
        return ['type' => 'notfound', 'lang' => $lang];
    }
    if (!in_array($lang, (array)($routes['published'][$id] ?? []), true)) {
        return ['type' => 'notfound', 'lang' => $lang];
    }

    $canon = $routes['ids'][$id][$lang] ?? null;
    if ($canon === null) {
        return ['type' => 'notfound', 'lang' => $lang];
    }
    return $slug === $canon
        ? ['type' => 'og', 'lang' => $lang, 'slug' => (string)$canon]
        : route_redirect(og_path($lang, (string)$canon));
}

/**
 * @param string $path Sorgu dizesi AYIKLANMIS yol (/tr/yazilim-gelistirici)
 * @param array  $routes cache/routes.json icerigi (bkz. build_routes())
 */
function resolve_path(string $path, array $routes): array
{
    if ($path === '') {
        $path = '/';
    }

    // 1. Guvenlik her seyden once.
    if (path_is_forbidden($path)) {
        return ['type' => 'forbidden'];
    }

    // 2. OG kartlari — dil alt klasoru opsiyonel (spec 5.6).
    if (preg_match('#^/og/(?:(tr|es)/)?([a-z0-9-]+)\.png$#', $path, $m) === 1) {
        return resolve_og($m[1] !== '' ? $m[1] : DEFAULT_LANG, $m[2], $routes);
    }

    // 3. Uretilen statik dosyalar.
    if ($path === '/sitemap.xml') {
        return ['type' => 'sitemap'];
    }
    if ($path === '/llms.txt') {
        return ['type' => 'llms'];
    }

    $trailing = $path !== '/' && str_ends_with($path, '/');
    $clean    = trim($path, '/');
    $seg      = $clean === '' ? [] : explode('/', $clean);

    if ($seg === []) {
        return ['type' => 'home', 'lang' => DEFAULT_LANG];
    }

    // 4. Dil prefix'i.
    $lang = null;
    if (in_array($seg[0], LANGS, true)) {
        $lang = (string)array_shift($seg);
    }

    // 4a. /en ve /en/ her zaman koke iner (spec 1.4).
    if ($lang === DEFAULT_LANG && $seg === []) {
        return route_redirect('/');
    }

    // 4b. Aktif olmayan dil hic yokmus gibi davranir.
    if ($lang !== null && !in_array($lang, (array)($routes['activeLangs'] ?? [DEFAULT_LANG]), true)) {
        return ['type' => 'notfound', 'lang' => DEFAULT_LANG];
    }

    // 4c. Dil ana sayfasi — egik cizgi ZORUNLU (spec 1.1).
    if ($lang !== null && $seg === []) {
        return $trailing
            ? ['type' => 'home', 'lang' => $lang]
            : route_redirect('/' . $lang . '/');
    }

    $lang = $lang ?? DEFAULT_LANG;

    // 5. Tek segment disinda hicbir sey yok.
    if (count($seg) !== 1) {
        return ['type' => 'notfound', 'lang' => $lang];
    }
    $slug = $seg[0];

    // 6. Sabit sayfa mi?
    $key = $routes['pages'][$lang][$slug] ?? null;
    if ($key !== null) {
        $canon = lang_prefix($lang) . $slug;
        return $path === $canon
            ? ['type' => 'page', 'lang' => $lang, 'key' => (string)$key]
            : route_redirect($canon);
    }
    // Baska dilin sabit sayfa slug'i mi? (/tr/methodology -> /tr/metodoloji)
    foreach (LANGS as $l) {
        $k = $routes['pages'][$l][$slug] ?? null;
        if ($k !== null && isset($routes['pageSlugs'][$lang][$k])) {
            return route_redirect(lang_prefix($lang) . $routes['pageSlugs'][$lang][$k]);
        }
    }

    // 7. Meslek.
    $id = resolve_job_id($lang, $slug, $routes);
    if ($id === null) {
        return ['type' => 'notfound', 'lang' => $lang];   // aka buraya duser: 404
    }

    // 8. Hedef dilde yayinlanmis mi? Degilse 301 URETILMEZ (spec 1.3).
    if (!in_array($lang, (array)($routes['published'][$id] ?? []), true)) {
        return ['type' => 'unavailable', 'lang' => $lang, 'id' => $id];
    }

    $canonSlug = $routes['ids'][$id][$lang] ?? null;
    if ($canonSlug === null) {
        return ['type' => 'unavailable', 'lang' => $lang, 'id' => $id];
    }
    $canon = lang_prefix($lang) . $canonSlug;

    // 9. Tek adimda kanonik bicime in — egik cizgi ve alias ayni 301'de cozulur.
    return $path === $canon
        ? ['type' => 'job', 'lang' => $lang, 'id' => $id]
        : route_redirect($canon);
}

<?php
/**
 * Yonlendirme tablosunun denetimi. SAF: diskten okumaz, $routes'u parametre
 * alir — bu yuzden sentetik BOZUK tablolarla test edilebilir. Kural, kirmizi
 * verdigi kanitlanmadan yazilmis sayilmaz (spec 7).
 */
declare(strict_types=1);

require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/routing.php';

/**
 * 'aka' ARAMA verisidir, ADRES degil. slugs haritasi yalnizca canonical ve
 * formerSlug tasimali.
 * @param array<string,array<string,string>> $expected lang => slug => id
 * @return string[]
 */
function routes_leak_errors(array $routes, array $expected): array
{
    $out = [];
    foreach (LANGS as $lang) {
        $extra = array_diff_key((array)($routes['slugs'][$lang] ?? []), (array)($expected[$lang] ?? []));
        foreach (array_keys($extra) as $slug) {
            $out[] = "routes/$lang: '$slug' canonical ya da formerSlug degil — adres tablosuna sizmis";
        }
    }
    return $out;
}

/**
 * hreflang karsilikliligi. Kumeyi kendisiyle karsilastirmak HICBIR ZAMAN kirmizi
 * vermez; her alternate URL resolve_path() ile GERCEKTEN cozulur ve uc sey ayri
 * ayri aranir (spec 5.1):
 *   1. URL kanonik mi (301 degil)
 *   2. hreflang kodu hedef sayfanin gercek dili mi
 *   3. hedefin kendi kumesinde kaynagin URL'i var mi
 *
 * $sets normalde alternates_for()'dan kurulur. Testler BOZUK bir kume enjekte
 * edebilsin diye parametre: (1) ve (3) dallari uretimde alternates_for hep
 * canonical'dan kurdugu icin tetiklenemez — savunma katmanidir, ve savunma
 * katmani da kirmizi verdigi KANITLANMADAN yazilmis sayilmaz.
 * @param array<string,array<string,string>>|null $sets 'lang|id' => kod => URL
 * @return string[]
 */
function hreflang_reciprocity_errors(array $routes, ?array $sets = null): array
{
    // Denetlenen hedefler: entry'ler + sabit sayfalar + ana sayfa. Yalnizca
    // entry'lere bakmak, sabit sayfa hreflang'ini denetimsiz birakirdi.
    if ($sets === null) {
        $sets = [];
        $active = (array)($routes['activeLangs'] ?? [DEFAULT_LANG]);
        foreach (array_keys((array)($routes['published'] ?? [])) as $id) {
            foreach ($active as $lang) {
                if (!in_array($lang, (array)$routes['published'][$id], true)) {
                    continue;
                }
                $sets['job|' . $lang . '|' . (string)$id] = alternates_for('job', (string)$id, $routes);
            }
        }
        foreach (array_keys((array)(PAGE_SLUGS[DEFAULT_LANG] ?? [])) as $key) {
            foreach ($active as $lang) {
                if (!isset($routes['pageSlugs'][$lang][$key])) {
                    continue;
                }
                $sets['page|' . $lang . '|' . (string)$key] = alternates_for('page', (string)$key, $routes);
            }
        }
        foreach ($active as $lang) {
            $sets['home|' . $lang . '|'] = alternates_for('home', '', $routes);
        }
    }

    $out = [];
    foreach ($sets as $key => $set) {
        $parts   = explode('|', (string)$key, 3);
        $srcType = $parts[0] ?? 'job';
        $srcLang = $parts[1] ?? DEFAULT_LANG;
        $id      = $parts[2] ?? '';
        $srcUrl  = url_for($srcLang, $srcType, $id, $routes);
        $label   = $srcType . '/' . ($id !== '' ? $id : 'home');

        foreach ($set as $code => $href) {
            if ($code === 'x-default') {
                continue;
            }
            // rtrim YOK: '/tr/' kanonik ana sayfadir, '/tr' ise 301'dir.
            $path  = (string)parse_url((string)$href, PHP_URL_PATH);
            $route = resolve_path($path === '' ? '/' : $path, $routes);

            if (($route['type'] ?? '') === 'redirect') {
                $out[] = "hreflang/$label: '$code' kanonik olmayan URL gosteriyor ($href)";
                continue;
            }
            if (!in_array($route['type'] ?? '', ['job', 'page', 'home'], true)) {
                $out[] = "hreflang/$label: '$code' bir sayfaya cozulmuyor ($href)";
                continue;
            }
            if ((string)($route['lang'] ?? '') !== (string)$code) {
                $out[] = "hreflang/$label: '$code' etiketi '" . (string)($route['lang'] ?? '?')
                       . "' dilindeki bir sayfayi gosteriyor";
                continue;
            }
            $tKey = match ((string)$route['type']) {
                'job'  => (string)($route['id'] ?? ''),
                'page' => (string)($route['key'] ?? ''),
                default => '',
            };
            $back = $sets[(string)$route['type'] . '|' . $code . '|' . $tKey] ?? null;
            if ($back === null || !in_array($srcUrl, $back, true)) {
                $out[] = "hreflang/$label: '$code' karsilik vermiyor — geri baglanti yok";
            }
        }
    }
    return $out;
}

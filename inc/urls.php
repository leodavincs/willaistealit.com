<?php
/**
 * URL uretiminin TEK kaynagi. Sablonlarda elle href kurulmaz.
 * Ingilizcenin prefix'siz olmasi burada tek bir kosul olarak yasar (spec 1.7).
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/routing.php';

/**
 * @param string $type 'home' | 'job' | 'page' | 'og'
 * @param string $key  job/og icin meslek ID'si, page icin sayfa anahtari
 */
function url_for(string $lang, string $type, string $key, array $routes): string
{
    $base = rtrim(SITE_URL, '/');

    if ($type === 'home') {
        return $base . lang_prefix($lang);
    }
    if ($type === 'og') {
        return $base . og_path($lang, (string)($routes['ids'][$key][$lang] ?? $key));
    }

    $slug = $type === 'page'
        ? (string)($routes['pageSlugs'][$lang][$key] ?? $key)
        : (string)($routes['ids'][$key][$lang] ?? $key);

    return $base . lang_prefix($lang) . $slug;
}

/**
 * hreflang kumesi. YALNIZCA yayinlanan dillerden kurulur — karsilikliligi
 * yapisal olarak garanti eden sey bu (spec 5.1).
 * @return array<string,string> 'en'|'tr'|'es'|'x-default' => mutlak URL
 */
function alternates_for(string $type, string $key, array $routes): array
{
    $out    = [];
    $active = (array)($routes['activeLangs'] ?? [DEFAULT_LANG]);

    foreach (LANGS as $lang) {
        if (!in_array($lang, $active, true)) {
            continue;
        }
        if ($type === 'job' && !in_array($lang, (array)($routes['published'][$key] ?? []), true)) {
            continue;
        }
        if ($type === 'page' && !isset($routes['pageSlugs'][$lang][$key])) {
            continue;
        }
        $out[$lang] = url_for($lang, $type, $key, $routes);
    }

    // x-default her zaman Ingilizce (spec 5.1).
    if (isset($out[DEFAULT_LANG])) {
        $out['x-default'] = $out[DEFAULT_LANG];
    }
    return $out;
}

<?php
/**
 * Locale fabrikasi. Dil sinifini ve metin tablosunu birlestirir, ornegi onbellekler.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/routing.php';
require_once __DIR__ . '/lang/Base.php';

/**
 * intl var mi. Test icin ezilebilir — fallback yolu sinanmadan guvenilmez.
 * intl_available(false) ezer, intl_available(null) ezmeyi kaldirir.
 */
function intl_available(?bool $force = null): bool
{
    static $forced = null;
    if (func_num_args() > 0) {
        $forced = $force;
    }
    return $forced ?? extension_loaded('intl');
}

/** Ham metin tablosu — dil sinifi olmadan da okunabilsin diye ayri. */
function locale_table(string $code): array
{
    static $tables = [];
    if (!isset($tables[$code])) {
        $tables[$code] = (array)(require ROOT . '/data/locale/' . $code . '.php');
    }
    return $tables[$code];
}

/** @return Lang Bilinmeyen kod varsayilan dile duser. */
function lang_for(string $code = DEFAULT_LANG): Lang
{
    static $cache = [];

    if (!in_array($code, LANGS, true)) {
        $code = DEFAULT_LANG;
    }
    if (isset($cache[$code])) {
        return $cache[$code];
    }

    $class = ucfirst($code);
    require_once __DIR__ . '/lang/' . $class . '.php';

    return $cache[$code] = new $class(locale_table($code));
}

/** Editoryal on ekler (data/locale/editorial.php). */
function editorial_namespaces(): array
{
    static $ns = null;
    if ($ns === null) {
        $manifest = require ROOT . '/data/locale/editorial.php';
        $ns = (array)($manifest['namespaces'] ?? []);
    }
    return $ns;
}

function is_editorial_key(string $key): bool
{
    foreach (editorial_namespaces() as $prefix) {
        if (str_starts_with($key, $prefix)) {
            return true;
        }
    }
    return false;
}

/** Kaynak dildeki editoryal anahtarlarin tamami. */
function locale_editorial_keys(): array
{
    $keys = array_filter(array_keys(locale_table(DEFAULT_LANG)), 'is_editorial_key');
    sort($keys);
    return $keys;
}

/**
 * Bir dilde HENUZ CEVRILMEMIS editoryal anahtarlar.
 * Statik bir liste degil, tablolardan HESAPLANIR — koddan ayrisamaz.
 */
function locale_pending(string $lang): array
{
    if ($lang === DEFAULT_LANG) {
        return [];
    }
    $table = locale_table($lang);
    return array_values(array_filter(
        locale_editorial_keys(),
        static fn (string $k): bool => !isset($table[$k])
    ));
}

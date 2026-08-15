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
    $strings = require ROOT . '/data/locale/' . $code . '.php';

    return $cache[$code] = new $class($strings);
}

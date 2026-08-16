<?php
/**
 * Aramada karsilastirilan tek bicim. PHP ve JS AYNI haritayi kullanir:
 * harita data/search-fold.json'da yasar, JS'e index.php uzerinden gomulur.
 * Iki tarafa ayri harita yazmak sessiz ayrisma uretir (spec 6.1).
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/locale.php';

/** @return array<string,string> */
function search_fold_map(): array
{
    static $map = null;
    if ($map === null) {
        $spec = json_decode((string)@file_get_contents(ROOT . '/data/search-fold.json'), true);
        $map  = (array)($spec['map'] ?? []);
    }
    return $map;
}

/**
 * Sira: harita -> NFD -> birlesen isaretler -> kucuk harf -> noktalama/bosluk.
 * Harita BASTA cunku NFD iki Turkce harfi cozmez: 'ı' hic ayrismaz, 'İ' ise
 * ayrisip 'I' kalir ve mb_strtolower('I') Turkcede 'ı' degil 'i' verir —
 * tesadufen dogru, ama tesadufe dayanmaz.
 */
function search_fold(string $s): string
{
    $s = strtr($s, search_fold_map());                                 // 1. harita
    if (intl_available() && class_exists('Normalizer')) {
        $s = (string)Normalizer::normalize($s, Normalizer::FORM_D);    // 2. NFD
    }
    $s = (string)preg_replace('/\p{Mn}+/u', '', $s);                   // 3. birlesen isaretler
    $s = mb_strtolower($s, 'UTF-8');                                   // 4. kucuk harf
    $s = (string)preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);           // 5. noktalama/bosluk
    return trim((string)preg_replace('/\s+/u', ' ', $s));
}

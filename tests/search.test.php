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

<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/ttf.php';

$fonts = [
    'Fraunces'   => __DIR__ . '/../fonts/Fraunces.ttf',
    'Newsreader' => __DIR__ . '/../fonts/Newsreader.ttf',
];

foreach ($fonts as $name => $file) {
    t_eq([], ttf_missing_codepoints($file, [0x0041]), "$name: A (U+0041) bulunmali");
    // Okuyucunun EKSIGI TESPIT EDEBILDIGININ kaniti. Bu test yesilse okuyucu
    // her seye "var" demiyor demektir; kirmizi olursa okuyucu bozuktur.
    t_eq([0x4E00], ttf_missing_codepoints($file, [0x4E00]), "$name: U+4E00 bulunmamali");
    t_eq([], ttf_missing_codepoints($file, ttf_required_codepoints()), "$name: TR+ES seti tam");
}

// Okunamayan dosya: hepsi eksik sayilir, hata firlatilmaz.
t_eq([0x0041], ttf_missing_codepoints(__DIR__ . '/yok.ttf', [0x0041]), 'olmayan dosya');

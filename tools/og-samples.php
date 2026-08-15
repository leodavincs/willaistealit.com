<?php
/**
 * EN/TR/ES ornek tuvalleri — GOZLE incelenmek uzere.
 * Cmap testi glifin VAR oldugunu soyler; bu script IYI DURDUGUNU gosterir.
 * inc/ogcard.php'ye DOKUNMAZ: yalnizca primitiflerini (og_wrap/og_text/og_text_width)
 * tuketir. Uretim renderer'i verdict etiketini Ingilizce VERDICTS'ten uretiyor,
 * o yuzden yerel etiketler ancak boyle bir tuvalde sinanabiliyor (locale Faz 3).
 *
 * Dil basina IKI sayfa uretilir; hepsini tek tuvale sigdirmak satirlari
 * ust uste bindiriyor ve inceleme okunaksiz oluyor:
 *   <lang>-verdicts.png  uretimdeki 96pt'te verdict etiketleri
 *   <lang>-type.png      karakter seti, sarma davranisi, kucuk punto
 *
 *   php tools/og-samples.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/ogcard.php';

if (!og_ready()) {
    exit("GD + FreeType ve fonts/ gerekli.\n");
}

$dir = CACHE_DIR . '/og-samples';
if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
    exit("cache/og-samples olusturulamadi.\n");
}

$sheets = [
    'en' => [
        'title'    => 'SOFTWARE DEVELOPER',
        'verdicts' => ['SAFE', 'SHRINKING', 'ON THE MENU'],
        'charset'  => 'ABCÇDEFGĞHIİJKLMNOÖPRSŞTUÜVYZ',
        'lower'    => 'abcçdefgğhıijklmnoöprsştuüvyz',
        'sentence' => 'The boilerplate is gone. The 3am pager is not, and it never will be.',
        'until'    => 'safe until ~2032',
    ],
    'tr' => [
        'title'    => 'İŞE ALIM UZMANI',
        'verdicts' => ['GÜVENDE', 'DARALIYOR', 'MENÜDE'],
        'charset'  => 'Ç Ğ İ Ö Ş Ü — ÇĞİÖŞÜ',
        'lower'    => 'ç ğ ı i ö ş ü — çğıiöşü',
        'sentence' => 'Özgeçmiş taraması gitti; işe alma kararı ve şirketin itibarı insanda kalıyor.',
        'until'    => '~2030 yılına kadar güvende',
    ],
    'es' => [
        'title'    => '¿DESARROLLADOR?',
        'verdicts' => ['A SALVO', 'SE REDUCE', 'EN EL MENÚ'],
        'charset'  => 'Á É Í Ó Ú Ü Ñ ¿ ¡ — ÁÉÍÓÚÜÑ',
        'lower'    => 'á é í ó ú ü ñ ¿ ¡ — áéíóúüñ',
        'sentence' => 'La programación básica ya desapareció; el diseño y la responsabilidad resisten.',
        'until'    => 'a salvo hasta ~2029',
    ],
];

/** Bos tuval + palet. Site ile ayni kagit zemin. */
function sample_canvas(): array
{
    $img = imagecreatetruecolor(OG_W, OG_H);
    imageantialias($img, true);
    $c = [
        'bg'   => imagecolorallocate($img, 246, 243, 238),
        'ink'  => imagecolorallocate($img, 28, 26, 23),
        'ink2' => imagecolorallocate($img, 85, 80, 74),
        'ink3' => imagecolorallocate($img, 138, 130, 121),
    ];
    imagefilledrectangle($img, 0, 0, OG_W, OG_H, $c['bg']);
    return [$img, $c];
}

$warnings = [];
$maxWidth = OG_W - PAD * 2;

foreach ($sheets as $lang => $s) {
    // --- Sayfa 1: verdict etiketleri, uretimdeki 96pt ---
    [$img, $c] = sample_canvas();
    og_text($img, 26, PAD, 70, $c['ink3'], FONT_BOLD, mb_strtoupper($lang) . ' — VERDICT (96pt)');
    $y = 210;
    foreach ($s['verdicts'] as $label) {
        og_text($img, 96, PAD, $y, $c['ink'], FONT_BOLD, $label);
        $w = og_text_width($label, FONT_BOLD, 96);
        // Uretimde 96pt sigmayinca 52pt'e kadar kuculuyor; simdiden bilelim.
        if ($w > $maxWidth) {
            $warnings[] = sprintf('%s: "%s" 96pt te %dpx, sinir %dpx — uretimde kuculecek',
                                  $lang, $label, $w, $maxWidth);
        }
        $y += 140;
    }
    imagepng($img, $dir . '/' . $lang . '-verdicts.png', 6);
    echo "yazildi: cache/og-samples/$lang-verdicts.png\n";

    // --- Sayfa 2: karakter seti, sarma, kucuk punto ---
    [$img, $c] = sample_canvas();
    og_text($img, 26, PAD, 70, $c['ink3'], FONT_BOLD, mb_strtoupper($lang) . ' — TYPE');

    og_text($img, 44, PAD, 160, $c['ink'],  FONT_BOLD, $s['charset']);
    og_text($img, 44, PAD, 225, $c['ink2'], FONT_REG,  $s['charset']);
    og_text($img, 44, PAD, 300, $c['ink'],  FONT_BOLD, $s['lower']);
    og_text($img, 44, PAD, 365, $c['ink2'], FONT_REG,  $s['lower']);

    // Sarma davranisi: og_wrap aksanli metinde satiri dogru kiriyor mu
    $ly = 450;
    foreach (og_wrap($s['sentence'], FONT_REG, 30, $maxWidth, 2) as $line) {
        og_text($img, 30, PAD, $ly, $c['ink'], FONT_REG, $line);
        $ly += 42;
    }

    og_text($img, 32, PAD, OG_H - 50, $c['ink2'], FONT_REG, $s['until']);
    imagepng($img, $dir . '/' . $lang . '-type.png', 6);
    echo "yazildi: cache/og-samples/$lang-type.png\n";
}

foreach ($warnings as $w) {
    echo "UYARI: $w\n";
}

echo "\nALTISINI DA ACIP GOZLE INCELE. Aranacaklar:\n";
echo "  - tofu kutusu (box) YOK\n";
echo "  - GUVENDE / DARALIYOR / MENUDE ve A SALVO / SE REDUCE / EN EL MENU okunuyor\n";
echo "  - aksanli BUYUK harflerin isaretleri kesilmiyor\n";
echo "  - satir kirilmasi kelime ortasindan gecmiyor, kerning bozuk degil\n";

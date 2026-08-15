<?php
/** OG karti cizimi — og.php (istek aninda) ve tools/build-og.php (toplu) paylasir. */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const OG_W = 1200;
const OG_H = 630;
const PAD  = 72;

/** Metni verilen genislige gore satirlara bol, tasarsa son satiri kis. */
function og_wrap(string $text, string $font, float $size, int $maxWidth, int $maxLines = 3): array
{
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    $lines = [];
    $line  = '';
    foreach ($words as $word) {
        $try = $line === '' ? $word : $line . ' ' . $word;
        $box = imagettfbbox($size, 0, $font, $try);
        if (($box[2] - $box[0]) > $maxWidth && $line !== '') {
            $lines[] = $line;
            $line = $word;
            if (count($lines) === $maxLines) {
                $line = '';
                break;
            }
        } else {
            $line = $try;
        }
    }
    if (count($lines) < $maxLines && $line !== '') {
        $lines[] = $line;
    }

    if (mb_strlen(implode(' ', $lines)) < mb_strlen(trim($text)) && $lines) {
        $last = rtrim((string)array_pop($lines), " ,.;:");
        while ($last !== '') {
            $box = imagettfbbox($size, 0, $font, $last . '…');
            if (($box[2] - $box[0]) <= $maxWidth) {
                break;
            }
            $last = mb_substr($last, 0, -1);
        }
        $lines[] = $last . '…';
    }
    return $lines;
}

function og_text($img, float $size, int $x, int $y, int $color, string $font, string $text): void
{
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}

function og_text_width(string $text, string $font, float $size): int
{
    $box = imagettfbbox($size, 0, $font, $text);
    return $box[2] - $box[0];
}

function og_ready(): bool
{
    return function_exists('imagettftext') && is_file(FONT_BOLD) && is_file(FONT_REG);
}

/**
 * Karti ciz. $job null ise ana sayfa karti uretir.
 * @return \GdImage
 */
function og_render(?array $job, string $slug)
{
    $img = imagecreatetruecolor(OG_W, OG_H);
    imageantialias($img, true);

    $rgb = $job !== null ? verdict_meta($job['verdict'] ?? '')['rgb'] : [242, 243, 245];

    // Kagit zemin — site ile ayni palet
    $bg     = imagecolorallocate($img, 246, 243, 238);
    $ink    = imagecolorallocate($img, 28, 26, 23);
    $ink2   = imagecolorallocate($img, 85, 80, 74);
    $ink3   = imagecolorallocate($img, 138, 130, 121);
    $line   = imagecolorallocate($img, 221, 215, 205);
    $vcolor = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);

    imagefilledrectangle($img, 0, 0, OG_W, OG_H, $bg);

    // Ust serit + asagi dogru sonen cok soluk parlama
    imagefilledrectangle($img, 0, 0, OG_W, 8, $vcolor);
    $glowH = 300;
    for ($i = 0; $i < $glowH; $i++) {
        $alpha = (int)round(104 + ($i / $glowH) * 23);
        if ($alpha >= 127) {
            break;
        }
        $glow = imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);
        imagefilledrectangle($img, 0, 8 + $i, OG_W, 9 + $i, $glow);
    }

    if ($job === null) {
        $y = 250;
        foreach (og_wrap('Will AI steal it?', FONT_BOLD, 88, OG_W - PAD * 2, 2) as $l) {
            og_text($img, 88, PAD, $y, $ink, FONT_BOLD, $l);
            $y += 100;
        }
        foreach (og_wrap('Task-level verdicts on which jobs AI actually takes — and what survives.', FONT_REG, 30, OG_W - PAD * 2, 2) as $l) {
            og_text($img, 30, PAD, $y + 10, $ink2, FONT_REG, $l);
            $y += 46;
        }
        imagefilledrectangle($img, PAD, OG_H - 118, OG_W - PAD, OG_H - 117, $line);
        og_text($img, 24, PAD, OG_H - 70, $ink3, FONT_REG, 'willaistealit.com');
        return $img;
    }

    $v     = verdict_meta($job['verdict'] ?? '');
    $title = mb_strtoupper((string)($job['title'] ?? $slug));

    // Meslek adi: harf araligi acilmis kucuk baslik (sigmiyorsa normal)
    $spaced = implode(' ', preg_split('//u', $title, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    if (og_text_width($spaced, FONT_BOLD, 26) > OG_W - PAD * 2) {
        $spaced = $title;
    }
    if (og_text_width($spaced, FONT_BOLD, 26) > OG_W - PAD * 2) {
        $spaced = mb_substr($title, 0, 42) . '…';
    }
    og_text($img, 26, PAD, 122, $ink3, FONT_BOLD, $spaced);

    // Verdict — kartin merkezi
    $vSize = 96.0;
    while (og_text_width($v['label'], FONT_BOLD, $vSize) > OG_W - PAD * 2 && $vSize > 52) {
        $vSize -= 4;
    }
    og_text($img, $vSize, PAD, 268, $vcolor, FONT_BOLD, $v['label']);

    $y = 330;
    if (!empty($job['safeUntil'])) {
        og_text($img, 32, PAD, $y, $ink2, FONT_REG, 'safe until ~' . (string)$job['safeUntil']);
        $y += 26;
    }

    $survives = !empty($job['resistanceTags'])
        ? implode(', ', array_slice((array)$job['resistanceTags'], 0, 3))
        : (string)($job['oneLiner'] ?? '');
    og_text($img, 22, PAD, $y + 62, $ink3, FONT_BOLD, 'WHAT SURVIVES');
    $ly = $y + 106;
    foreach (og_wrap($survives, FONT_REG, 30, OG_W - PAD * 2, 2) as $l) {
        og_text($img, 30, PAD, $ly, $ink, FONT_REG, $l);
        $ly += 44;
    }

    imagefilledrectangle($img, PAD, OG_H - 118, OG_W - PAD, OG_H - 117, $line);
    og_text($img, 24, PAD, OG_H - 70, $ink3, FONT_REG, 'willaistealit.com/' . $slug);
    imagefilledellipse($img, OG_W - PAD - 14, OG_H - 78, 22, 22, $vcolor);

    return $img;
}

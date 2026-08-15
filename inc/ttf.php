<?php
/**
 * TTF cmap okuyucu. Yalnizca tools/ tarafindan kullanilir, site calismasinda yer almaz.
 * Amac: bir code point'in fontta GERCEKTEN glifi var mi.
 * imagettfbbox() bunu soyleyemez — eksik glif icin .notdef kutusunu olcer.
 * Salt okunur ve sinir kontrollu: bozuk font hata firlatmaz, "hepsi eksik" der.
 */
declare(strict_types=1);

/** @return int[] Fontta bulunamayan code point'ler. Font okunamazsa hepsi eksik. */
function ttf_missing_codepoints(string $file, array $codepoints): array
{
    $raw = @file_get_contents($file);
    if ($raw === false || strlen($raw) < 12) {
        return array_map('intval', array_values($codepoints));
    }
    $cmap = ttf_find_table($raw, 'cmap');
    $sub  = $cmap === null ? null : ttf_best_subtable($raw, $cmap);
    if ($sub === null) {
        return array_map('intval', array_values($codepoints));
    }

    $missing = [];
    foreach ($codepoints as $cp) {
        if (ttf_glyph_for($raw, $sub, (int)$cp) === 0) {
            $missing[] = (int)$cp;
        }
    }
    return $missing;
}

function ttf_u16(string $s, int $o): int
{
    return $o >= 0 && $o + 1 < strlen($s) ? (ord($s[$o]) << 8) | ord($s[$o + 1]) : 0;
}

function ttf_u32(string $s, int $o): int
{
    return (ttf_u16($s, $o) << 16) | ttf_u16($s, $o + 2);
}

/** @return int|null Tablonun dosya icindeki ofseti. */
function ttf_find_table(string $raw, string $tag): ?int
{
    $num = ttf_u16($raw, 4);
    for ($i = 0; $i < $num; $i++) {
        $rec = 12 + $i * 16;
        if ($rec + 16 > strlen($raw)) {
            return null;
        }
        if (substr($raw, $rec, 4) === $tag) {
            $off = ttf_u32($raw, $rec + 8);
            return $off < strlen($raw) ? $off : null;
        }
    }
    return null;
}

/**
 * En iyi cmap alt tablosu. Tercih: (3,10) tam Unicode > (3,1) BMP > (0,*) Unicode.
 * @return int|null Alt tablonun MUTLAK ofseti.
 */
function ttf_best_subtable(string $raw, int $cmap): ?int
{
    $n = ttf_u16($raw, $cmap + 2);
    $best = null;
    $bestRank = -1;
    for ($i = 0; $i < $n; $i++) {
        $rec = $cmap + 4 + $i * 8;
        if ($rec + 8 > strlen($raw)) {
            break;
        }
        $plat = ttf_u16($raw, $rec);
        $enc  = ttf_u16($raw, $rec + 2);
        $off  = $cmap + ttf_u32($raw, $rec + 4);
        $rank = -1;
        if ($plat === 3 && $enc === 10) {
            $rank = 3;
        } elseif ($plat === 3 && $enc === 1) {
            $rank = 2;
        } elseif ($plat === 0) {
            $rank = 1;
        }
        if ($rank > $bestRank && $off + 4 < strlen($raw)) {
            $best = $off;
            $bestRank = $rank;
        }
    }
    return $best;
}

/** @return int Glif indeksi; 0 = kapsam disi (.notdef). */
function ttf_glyph_for(string $raw, int $sub, int $cp): int
{
    $format = ttf_u16($raw, $sub);

    if ($format === 4) {
        if ($cp > 0xFFFF) {
            return 0;
        }
        $segX2  = ttf_u16($raw, $sub + 6);
        $seg    = intdiv($segX2, 2);
        $endP   = $sub + 14;
        $startP = $endP + $segX2 + 2;        // araya reservedPad(2) giriyor
        $deltaP = $startP + $segX2;
        $rangeP = $deltaP + $segX2;
        if ($rangeP + $segX2 > strlen($raw)) {
            return 0;
        }
        for ($i = 0; $i < $seg; $i++) {
            if (ttf_u16($raw, $endP + $i * 2) < $cp) {
                continue;
            }
            $start = ttf_u16($raw, $startP + $i * 2);
            if ($start > $cp) {
                return 0;
            }
            $delta = ttf_u16($raw, $deltaP + $i * 2);
            $range = ttf_u16($raw, $rangeP + $i * 2);
            if ($range === 0) {
                return ($cp + $delta) & 0xFFFF;
            }
            // idRangeOffset, KENDI konumundan itibaren sayilir — spec'teki adres aritmetigi.
            $addr = $rangeP + $i * 2 + $range + ($cp - $start) * 2;
            if ($addr + 1 >= strlen($raw)) {
                return 0;
            }
            $g = ttf_u16($raw, $addr);
            return $g === 0 ? 0 : (($g + $delta) & 0xFFFF);
        }
        return 0;
    }

    if ($format === 12) {
        $groups = ttf_u32($raw, $sub + 12);
        for ($i = 0; $i < $groups; $i++) {
            $g = $sub + 16 + $i * 12;
            if ($g + 12 > strlen($raw)) {
                return 0;
            }
            $s = ttf_u32($raw, $g);
            $e = ttf_u32($raw, $g + 4);
            if ($cp >= $s && $cp <= $e) {
                return ttf_u32($raw, $g + 8) + ($cp - $s);
            }
        }
        return 0;
    }

    if ($format === 6) {
        $first = ttf_u16($raw, $sub + 6);
        $count = ttf_u16($raw, $sub + 8);
        if ($cp < $first || $cp >= $first + $count) {
            return 0;
        }
        return ttf_u16($raw, $sub + 10 + ($cp - $first) * 2);
    }

    return 0;
}

/** Spec 9.1 — minimum karakter seti. */
function ttf_required_codepoints(): array
{
    $chars = 'ÇĞİÖŞÜçğıiöşü' . 'ÁÉÍÓÚÜÑ¿¡áéíóúüñ';
    $out = [];
    foreach (preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
        $out[mb_ord($ch, 'UTF-8')] = true;
    }
    ksort($out);
    return array_keys($out);
}

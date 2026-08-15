<?php
/**
 * Bagimlilik yok: proje composer kullanmiyor, test framework'u de kullanmiyor.
 * Karsilastirma === ile yapilir — tip sapmasi da hata sayilsin.
 */
declare(strict_types=1);

$GLOBALS['T'] = ['pass' => 0, 'fail' => 0, 'msgs' => []];

function t_json(mixed $v): string
{
    return (string)json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function t_eq(mixed $expected, mixed $actual, string $label): void
{
    if ($expected === $actual) {
        $GLOBALS['T']['pass']++;
        return;
    }
    $GLOBALS['T']['fail']++;
    $GLOBALS['T']['msgs'][] = sprintf(
        "  x %s\n      beklenen: %s\n      gelen:    %s",
        $label,
        t_json($expected),
        t_json($actual)
    );
}

function t_done(): void
{
    foreach ($GLOBALS['T']['msgs'] as $m) {
        echo $m . "\n";
    }
    printf("\n%d gecti, %d kaldi\n", $GLOBALS['T']['pass'], $GLOBALS['T']['fail']);
    exit($GLOBALS['T']['fail'] > 0 ? 1 : 0);
}

<?php
/**
 * Tum testleri kosar: php tests/run.php
 * Cikis kodu: kalan varsa 1.
 */
declare(strict_types=1);

require __DIR__ . '/lib.php';

$files = glob(__DIR__ . '/*.test.php') ?: [];
sort($files);
foreach ($files as $f) {
    require $f;
}

t_done();

<?php
declare(strict_types=1);

t_eq('a', 'a', 'esit dizeler gecer');
t_eq([1, 2, 3], [1, 2, 3], 'dizi karsilastirmasi');
t_eq(['a' => 1], ['a' => 1], 'iliskisel dizi karsilastirmasi');
t_eq('{"a":1}', t_json(['a' => 1]), 't_json JSON uretir');
t_eq('"ğüş"', t_json('ğüş'), 't_json Turkce harfleri kacirmaz');

<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/dispatch.php';

t_eq(['status' => 301, 'headers' => ['Location' => '/software-developer'],
      'include' => null, 'get' => []],
     dispatch_for(['type' => 'redirect', 'status' => 301, 'location' => '/software-developer']),
     '301 govde basmadan');

t_eq(['status' => 200, 'headers' => [], 'include' => 'index.php', 'get' => ['lang' => 'en']],
     dispatch_for(['type' => 'home', 'lang' => 'en']), 'ana sayfa');

t_eq(['status' => 200, 'headers' => [], 'include' => 'job.php',
      'get' => ['slug' => 'accountant', 'lang' => 'en']],
     dispatch_for(['type' => 'job', 'lang' => 'en', 'id' => 'accountant']), 'entry -> job.php');

t_eq(['status' => 200, 'headers' => [], 'include' => 'methodology.php', 'get' => ['lang' => 'en']],
     dispatch_for(['type' => 'page', 'lang' => 'en', 'key' => 'methodology']), 'sabit sayfa');

// 'forbidden' 403 DEGIL 404 doner: 403 "burada bir sey var" der.
t_eq(['status' => 404, 'headers' => [], 'include' => '404.php', 'get' => ['lang' => 'en']],
     dispatch_for(['type' => 'forbidden']), 'yasak yol ipucu vermez');

t_eq(['status' => 404, 'headers' => [], 'include' => '404.php', 'get' => ['lang' => 'tr']],
     dispatch_for(['type' => 'notfound', 'lang' => 'tr']), '404');

// Yayinlanmamis ceviri: 404 + noindex, canonical YOK (spec 5.4)
t_eq(['status' => 404, 'headers' => ['X-Robots-Tag' => 'noindex, follow'],
      'include' => 'unavailable.php', 'get' => ['lang' => 'tr', 'id' => 'accountant']],
     dispatch_for(['type' => 'unavailable', 'lang' => 'tr', 'id' => 'accountant']),
     'unavailable 404 + noindex');

t_eq(['status' => 200, 'headers' => [], 'include' => 'og.php',
      'get' => ['slug' => 'accountant', 'lang' => 'en']],
     dispatch_for(['type' => 'og', 'lang' => 'en', 'slug' => 'accountant']), 'OG');

t_eq(['status' => 200, 'headers' => [], 'include' => 'sitemap.php', 'get' => []],
     dispatch_for(['type' => 'sitemap']), 'sitemap');
t_eq(['status' => 200, 'headers' => [], 'include' => 'llms.php', 'get' => []],
     dispatch_for(['type' => 'llms']), 'llms');

// Bilinmeyen tip guvenli tarafa duser.
t_eq(['status' => 404, 'headers' => [], 'include' => '404.php', 'get' => ['lang' => 'en']],
     dispatch_for(['type' => 'hicboyle']), 'bilinmeyen tip -> 404');

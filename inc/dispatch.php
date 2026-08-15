<?php
/**
 * Cozum sonucunu HTTP eylemine cevirir. SAF: hicbir sey basmaz, header gondermez —
 * o is route.php'nin. Boyle oldugu icin test edilebiliyor.
 * 'include' degeri KULLANICI GIRDISINDEN gelmez: sabit liste ya da routes
 * tablosundaki sayfa anahtaridir.
 */
declare(strict_types=1);

/**
 * @return array{status:int,headers:array<string,string>,include:?string,get:array<string,string>}
 */
function dispatch_for(array $route): array
{
    $type = (string)($route['type'] ?? 'notfound');
    $lang = (string)($route['lang'] ?? 'en');

    return match ($type) {
        'redirect' => ['status'  => (int)$route['status'],
                       'headers' => ['Location' => (string)$route['location']],
                       'include' => null, 'get' => []],

        'home'     => ['status' => 200, 'headers' => [], 'include' => 'index.php',
                       'get' => ['lang' => $lang]],

        'job'      => ['status' => 200, 'headers' => [], 'include' => 'job.php',
                       'get' => ['slug' => (string)$route['id'], 'lang' => $lang]],

        'page'     => ['status' => 200, 'headers' => [],
                       'include' => (string)$route['key'] . '.php',
                       'get' => ['lang' => $lang]],

        'og'       => ['status' => 200, 'headers' => [], 'include' => 'og.php',
                       'get' => ['slug' => (string)$route['slug'], 'lang' => $lang]],

        'sitemap'  => ['status' => 200, 'headers' => [], 'include' => 'sitemap.php', 'get' => []],
        'llms'     => ['status' => 200, 'headers' => [], 'include' => 'llms.php',    'get' => []],

        // Yayinlanmamis ceviri: 404 + noindex, canonical YOK (spec 5.4).
        'unavailable' => ['status' => 404, 'headers' => ['X-Robots-Tag' => 'noindex, follow'],
                          'include' => 'unavailable.php',
                          'get' => ['lang' => $lang, 'id' => (string)$route['id']]],

        default    => ['status' => 404, 'headers' => [], 'include' => '404.php',
                       'get' => ['lang' => $type === 'forbidden' ? 'en' : $lang]],
    };
}

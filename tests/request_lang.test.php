<?php
/**
 * Istek dili sablonlara ULASIYOR mu. route.php dili $_GET['lang'] ile geciriyor;
 * bir sablon onu okumazsa sayfa sessizce Ingilizce render eder (spec 1.7).
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

/** Sablonu izole kapsamda, verilen istek diliyle render et. */
function render_template_with_lang(string $template, string $reqLang): string
{
    $getBak  = $_GET;
    $hostBak = $_SERVER['HTTP_HOST'] ?? null;
    unset($_SERVER['HTTP_HOST']);
    $_GET = ['lang' => $reqLang];
    try {
        ob_start();
        require __DIR__ . '/../' . $template;
        return (string)ob_get_clean();
    } finally {
        $_GET = $getBak;
        if ($hostBak !== null) { $_SERVER['HTTP_HOST'] = $hostBak; }
    }
}

// Onizleme tablosu: TR aktif. Canli cache/routes.json'a DOKUNULMAZ.
$rlPrev = sys_get_temp_dir() . '/waisi-routes-reqlang.json';
$rlR    = build_routes();
$rlR['activeLangs'] = ['en', 'tr'];
$rlR['pageSlugs']['tr'] = ['methodology' => 'metodoloji', 'changelog' => 'degisiklikler'];
file_put_contents($rlPrev, (string)json_encode($rlR));
putenv('WAISI_ROUTES_FILE=' . $rlPrev);
unset($GLOBALS['__routes']);

foreach (['index.php' => 'ana sayfa', '404.php' => '404',
          'unavailable.php' => 'unavailable'] as $tpl => $ad) {
    $html = render_template_with_lang($tpl, 'tr');
    t_eq(true, str_contains($html, '<html lang="tr">'), "$ad: istek dili TR render edilir");
    t_eq(false, str_contains($html, '<html lang="en">'), "$ad: EN e sessizce dusmez");
}

// Kaynak dil hala varsayilan: lang verilmezse EN.
$html = render_template_with_lang('index.php', 'en');
t_eq(true, str_contains($html, '<html lang="en">'), 'ana sayfa: EN varsayilan');

// Bilinmeyen dil kodu varsayilana duser — $_GET dogrudan sablona giremez.
$html = render_template_with_lang('index.php', '../../etc/passwd');
t_eq(true, str_contains($html, '<html lang="en">'), 'bilinmeyen dil kodu varsayilana duser');

putenv('WAISI_ROUTES_FILE');
unset($GLOBALS['__routes']);
@unlink($rlPrev);

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

// --- unavailable: yayinlanan dillere BAGLANTI verir, yonlendirme YAPMAZ (spec 5.4) ---
$uPrev = sys_get_temp_dir() . '/waisi-routes-unavail.json';
$uR    = build_routes();
$uR['activeLangs'] = ['en', 'tr'];
file_put_contents($uPrev, (string)json_encode($uR));
putenv('WAISI_ROUTES_FILE=' . $uPrev);
unset($GLOBALS['__routes']);

// run.php butun test dosyalarini AYNI kapsamda kosuyor; $lang/$id onceki
// testlerden sizabilir ve sablonun $_GET okumasini golgeler. Izole ediyoruz.
$uHtml = (static function (): string {
    $_GET = ['lang' => 'tr', 'id' => 'nurse'];    // nurse TR'de yayinlanmamis
    ob_start();
    require __DIR__ . '/../unavailable.php';
    return (string)ob_get_clean();
})();

t_eq(true,  str_contains($uHtml, 'lang-list'),                    'unavailable: dil listesi basilir');
t_eq(true,  str_contains($uHtml, 'hreflang="en"'),                'unavailable: EN baglantisi var');
t_eq(false, str_contains($uHtml, 'rel="canonical"'),              'unavailable: canonical YOK');
t_eq(false, str_contains($uHtml, 'rel="alternate"'),              'unavailable: hreflang kumesi YOK');
t_eq(false, str_contains($uHtml, 'lang-switch'),                  'unavailable: dil secici YOK');
t_eq(true,  str_contains($uHtml, 'name="robots" content="noindex'), 'unavailable: noindex');
t_eq(true,  str_contains($uHtml, '<html lang="tr">'),             'unavailable: istenen dilde render');
// Sessiz yonlendirme yok: sayfa Location basligi ya da meta refresh tasimaz.
t_eq(false, str_contains($uHtml, 'http-equiv="refresh"'),         'unavailable: meta refresh YOK');

putenv('WAISI_ROUTES_FILE');
unset($GLOBALS['__routes']);
@unlink($uPrev);

// --- Entry sayfasi istek dilinde YUKLENMELI (spec 1.7) ---
// job.php entry'yi dilsiz yuklerse sayfa TR adresinde Ingilizce icerik gosterir:
// baslik, verdict etiketi ve "ilgili isler" blogu hep EN kalir.
$jPrev = sys_get_temp_dir() . '/waisi-routes-jobtpl.json';
$jR    = build_routes();
$jR['activeLangs'] = ['en', 'tr'];
file_put_contents($jPrev, (string)json_encode($jR));
putenv('WAISI_ROUTES_FILE=' . $jPrev);
unset($GLOBALS['__routes']);

$jHtml = (static function (): string {
    $_GET = ['slug' => 'accountant', 'lang' => 'tr'];
    ob_start();
    require __DIR__ . '/../job.php';
    return (string)ob_get_clean();
})();

t_eq(true,  str_contains($jHtml, 'Muhasebeci'),   'entry sayfasi TR basligi gosterir');
t_eq(false, str_contains($jHtml, 'SHRINKING'),    'verdict etiketi Ingilizce kalmaz');
t_eq(true,  str_contains($jHtml, 'DARALIYOR'),    'verdict etiketi TR');
// oneLiner TR olmali — EN metni sayfada hic gecmemeli
t_eq(false, str_contains($jHtml, 'The data entry is already gone'), 'EN oneLiner sizmaz');
t_eq(true,  str_contains($jHtml, 'Veri girişi çoktan gitti'),       'TR oneLiner basilir');
// "Ilgili isler" blogu da TR olmali
t_eq(false, str_contains($jHtml, 'Will AI replace software developers'), 'ilgili isler EN kalmaz');

putenv('WAISI_ROUTES_FILE');
unset($GLOBALS['__routes']);
@unlink($jPrev);

// --- Sabit sayfalar hedef dilde GERCEKTEN yerellesmis mi ---
// locale_pending() bunu goremez: metin locale tablosunda hic yoksa "bekleyen"
// sayilmaz. Bu yuzden sayfayi RENDER edip kaynak dil metni ariyoruz.
$spPrev = sys_get_temp_dir() . '/waisi-routes-static.json';
$spR    = build_routes();
$spR['activeLangs'] = ['en', 'tr'];
file_put_contents($spPrev, (string)json_encode($spR));
putenv('WAISI_ROUTES_FILE=' . $spPrev);
unset($GLOBALS['__routes']);

/** Sabit sayfayi izole kapsamda, verilen dil ve POST govdesiyle render et. */
function render_page_in(string $template, string $lang, array $post = []): string
{
    return (static function () use ($template, $lang, $post): string {
        $_GET  = ['lang' => $lang];
        $_POST = $post;
        $_SERVER['REQUEST_METHOD'] = $post === [] ? 'GET' : 'POST';
        ob_start();
        require __DIR__ . '/../' . $template;
        $html = (string)ob_get_clean();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        return $html;
    })();
}

// Her aktif sabit sayfa, TR'de kaynak dil metni SIZDIRMAMALI.
$staticPages = [
    'landscape.php'   => ['tr' => 'Çekirdek gittiğinde',  'en' => 'When the core goes'],
    'sponsor.php'     => ['tr' => 'Sponsorluk',           'en' => 'Not selling yet'],
    'methodology.php' => ['tr' => 'Nasıl karar veriyoruz', 'en' => 'The one rule'],
    'changelog.php'   => ['tr' => 'Yargı değişiklikleri',  'en' => 'Every verdict that moved'],
];
foreach ($staticPages as $tpl => $probe) {
    $html = render_page_in($tpl, 'tr');
    t_eq(true,  str_contains($html, $probe['tr']), "$tpl: TR metni basiliyor");
    t_eq(false, str_contains($html, $probe['en']), "$tpl: EN metni sizmiyor");
    t_eq(true,  str_contains($html, '<html lang="tr">'), "$tpl: html lang tr");
}

// --- Sponsor formu: action, dogrulama ve basari yollari da TR olmali ---
$spGet = render_page_in('sponsor.php', 'tr');
t_eq(true, str_contains($spGet, 'action="/tr/sponsorluk"'), 'sponsor: form action dil baglamini korur');
t_eq(true, str_contains($spGet, 'Listeye katıl'),           'sponsor: gonder dugmesi TR');

$spBad = render_page_in('sponsor.php', 'tr', ['email' => 'bu-bir-eposta-degil']);
t_eq(true,  str_contains($spBad, 'çalışan bir e-posta adresine benzemiyor'), 'sponsor: hata mesaji TR');
t_eq(false, str_contains($spBad, 'does not look like'),                      'sponsor: EN hata sizmiyor');

// Honeypot yolu: sessizce basarili sayilir ve dosyaya YAZMAZ.
$spBot = render_page_in('sponsor.php', 'tr', ['email' => 'bot@example.com', 'company' => 'bot']);
t_eq(true,  str_contains($spBot, 'Listedesiniz'),      'sponsor: basari basligi TR');
t_eq(false, str_contains($spBot, 'You are on the list'), 'sponsor: EN basari sizmiyor');

// EN tarafi bozulmadi.
$spEn = render_page_in('sponsor.php', 'en');
t_eq(true, str_contains($spEn, 'action="/sponsor"'), 'sponsor: EN form action degismedi');
t_eq(true, str_contains($spEn, 'Join the list'),     'sponsor: EN dugmesi degismedi');

putenv('WAISI_ROUTES_FILE');
unset($GLOBALS['__routes']);
@unlink($spPrev);

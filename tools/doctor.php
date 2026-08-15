<?php
/**
 * Ortam teshisi.
 *   php tools/doctor.php            # lokal uygunluk
 *   php tools/doctor.php --deploy   # deployment uygunlugu (BUILD_KEY vb. katilasir)
 *   /tools/doctor.php?key=BUILD_KEY # web; her zaman deployment katiliginda
 * Cikis kodu: kritik hata varsa 1.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!build_key_ok($_GET['key'] ?? null)) {
        http_response_code(403);
        exit("forbidden — set BUILD_KEY in inc/config.local.php first\n");
    }
}

// Web'e girebilmis olmak anahtarin gecerli oldugunu KANITLAR (build_key_ok).
// O yuzden web her zaman katidir; CLI'de katilik --deploy ile acilir.
$deploy = $cli ? in_array('--deploy', $argv, true) : true;

$fail = 0;
$warn = 0;

$check = static function (bool $ok, string $label, string $detail = '', string $level = 'HATA') use (&$fail, &$warn): void {
    if ($ok) {
        echo "  ok   $label\n";
        return;
    }
    echo "  $level $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    if ($level === 'HATA') {
        $fail++;
    } else {
        $warn++;
    }
};

echo ($deploy ? "Mod: deployment uygunlugu\n" : "Mod: lokal uygunluk (--deploy ile katilastir)\n");

echo "\nPHP\n";
$check(PHP_VERSION_ID >= 80300, 'PHP >= 8.3', 'bulunan: ' . PHP_VERSION);

echo "\nEklentiler\n";
$check(extension_loaded('gd'), 'gd', 'OG kartlari uretilemez');
$check(extension_loaded('mbstring'), 'mbstring', 'cok dilli metin islenemez');
// intl kritik DEGIL: data/locale/*.php icindeki ay tablolari fallback (spec 4.1).
$check(extension_loaded('intl'), 'intl', 'ay adlari fallback tablosundan gelecek', 'UYARI');
// curl gizli bagimlilik olmasin: tools/smoke.php bunu kullaniyor.
// Lokalde smoke kosulacagi icin KRITIK; sunucuda kosulmayacagi icin uyari.
$check(extension_loaded('curl'), 'curl', 'tools/smoke.php calismaz', $deploy ? 'UYARI' : 'HATA');

echo "\nAyarlar\n";
$check(build_key_ok(BUILD_KEY), 'BUILD_KEY degistirilmis',
       'tools/ web den calismaz', $deploy ? 'HATA' : 'UYARI');
$check(CONTACT_EMAIL !== '', 'CONTACT_EMAIL dolu', 'iletisim baglantilari gizlenir', 'UYARI');

echo "\nCache dizinleri\n";
// Dizinin repoda bulunmasi yazilabilir oldugunu GARANTI ETMEZ (spec 9).
// Yoksa olustur, gercek yazma testi yap, birakma.
foreach ([CACHE_DIR, PAGES_DIR, OG_DIR] as $dir) {
    $short = str_replace(ROOT . '/', '', $dir);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        $check(false, $short, 'olusturulamadi');
        continue;
    }
    $probe = $dir . '/.doctor-' . bin2hex(random_bytes(4));
    $wrote = @file_put_contents($probe, 'x') !== false;
    $check($wrote, "$short yazilabilir", 'izinleri kontrol et');
    if ($wrote) {
        @unlink($probe);
        $check(!is_file($probe), "$short temizlendi", 'gecici dosya silinemedi', 'UYARI');
    }
}

echo "\n";
if ($fail > 0) {
    echo "$fail kritik hata, $warn uyari\n";
    if ($cli) {
        exit(1);
    }
    http_response_code(500);
    exit;
}
echo "Kritik hata yok" . ($warn > 0 ? ", $warn uyari" : '') . "\n";

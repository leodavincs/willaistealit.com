# Çok Dilli Mimari — Faz 0 & Faz 1 Uygulama Planı

> **Agentic worker'lar için:** GEREKLİ ALT-SKILL: `superpowers:executing-plans`
> (bu plan için taze subagent kullanılmayacak — görevler arasında güçlü ardışık
> bağımlılık var). Adımlar `- [ ]` kutucuk sözdizimi kullanıyor.

**Amaç:** Üç dilli mimarinin altyapısını kurmak — ortam doğrulaması (Faz 0) ve tek
front controller'a geçiş (Faz 1) — **siteye tek bir dil eklemeden ve İngilizce
çıktıyı değiştirmeden**.

**Mimari:** URL kuralları bugün `.htaccess` (üretim) ve `router.php` (lokal) arasında
ikiye bölünmüş durumda. Bu plan kuralları saf ve test edilebilir tek bir çözümleyiciye
(`resolve_path()`) taşır, üstüne bir front controller (`route.php`) kurar ve iki giriş
yolunu da o controller'a bağlar. Veri şeması, locale sistemi ve TR/ES içeriği bu planın
kapsamı dışındadır (Faz 2+).

**Teknoloji:** PHP 8.3, framework yok, bağımlılık yok, build adımı yok. Testler
bağımlılıksız bir assert koşucusuyla (`tests/run.php`) çalışır.

**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md`

## Global kısıtlar

Her görevin gereksinimlerine örtük olarak dahildir:

- **PHP 8.3**, `declare(strict_types=1);` her yeni dosyada.
- **Bağımlılık eklenmez.** Composer yok, autoloader yok, test framework'ü yok.
- **Kod yorumları Türkçe ve ASCII** (mevcut kod tabanının konvansiyonu:
  `inc/functions.php` "Slug'i dogrula", "Bulunamazsa / bozuksa null"). Dokümantasyon
  ve markdown tam Türkçe.
- **Fonksiyon adları `snake_case`**, mevcut prosedürel stile uyar. Sınıf eklenmez
  (locale sınıfları Faz 3'te gelir).
- **Her commit temiz çalışma ağacından tek başına uygulanabilir ve doğrulanabilir
  olmalı.** Bir görevin adımı başka bir görevin adımını "önceden uygula" diyemez.
- **Her commit sonunda mevcut İngilizce site çalışır kalır.** İstisna yok.
- **Front controller aynı commit içinde hem eklenip hem üretim routing'ine bağlanmaz**
  (Görev 1D ekler, Görev 1F bağlar).
- **Kritik güvenlik sınırı, açılmadan önce test edilir.** `path_is_forbidden()`
  fixture'larının tamamı, `.htaccess`/`router.php` geçişinden **önceki** commit'lerde
  yeşil olmalı.
- **Faz 1 boyunca veri şeması değişmez** — `data/jobs/<slug>.json` düz dosya kalır.
- Güvenlik kontrolü **her zaman** gerçek-dosya kontrolünden önce koşar (spec §1.8).
- Cache yazımı **atomik**: geçici dosya + `rename()`.
- **Testler gerçek cache dosyalarına dokunmaz.** Yazan her test kendi geçici dizinini
  kullanır.
- **Üretim renderer'ı (`inc/ogcard.php`) bu fazda değiştirilmez.**

## Dosya haritası

| Dosya | Sorumluluk | Görev |
|---|---|---|
| `tests/lib.php` | Bağımlılıksız assert yardımcıları | 0A |
| `tests/run.php` | Test koşucusu | 0A |
| `tests/harness.test.php` | Koşucunun kendi testi | 0A |
| `tools/doctor.php` | Ortam teşhisi: eklentiler, izinler, font, BUILD_KEY | 0B, 0C |
| `inc/ttf.php` | TTF cmap tablosu okuma — code point kapsama sorgusu | 0C |
| `tools/og-samples.php` | Bağımsız örnek tuval — font/wrap/yerleşim sınaması | 0D |
| `inc/routing.php` | **Saf** URL çözümleyici — dosya sistemi yok, global yok | 1A |
| `tests/routing.test.php` | Çözümleyici + tam `path_is_forbidden()` fixture'ları | 1A, 1E |
| `inc/urls.php` | `url_for()`, `alternates_for()` | 1B |
| `inc/routes_cache.php` | `build_routes()`, `load_routes()`, `routes_valid()`, `atomic_write()` | 1C |
| `inc/dispatch.php` | Çözüm sonucunu HTTP eylemine çeviren **saf** eşleyici | 1D |
| `route.php` | Front controller — tek giriş noktası | 1D |
| `unavailable.php` | Yayınlanmamış çeviri sayfası (404 + noindex) | 1D |
| `router.php` | Lokal sunucu köprüsü (kural içermez) | 1F |
| `.htaccess` | Üretim köprüsü (güvenlik + devir) | 1F |
| `tools/smoke.php` | HTTP matris testi | 1G |
| `tools/smoke.sh` | Sunucu ömrünü PID ile yöneten koşucu | 1G |

---

# FAZ 0 — Ortam doğrulaması

---

### Görev 0A — Test koşucusu

**Amaç**
Sonraki her görevin dayanacağı bağımlılıksız assert koşucusunu kurmak. Kendi başına
commit edilir ve kendi başına doğrulanır — sonraki görevlerin hiçbiri "koşucuyu
önceden yaz" demek zorunda kalmaz.

**Değiştirilecek dosyalar**
- Oluştur: `tests/lib.php`
- Oluştur: `tests/run.php`
- Oluştur: `tests/harness.test.php`

**Arayüzler**
- Tüketir: yok (ilk görev).
- Üretir: `t_eq(mixed $expected, mixed $actual, string $label): void`,
  `t_json(mixed $v): string`, `t_done(): void`. Koşucu `tests/*.test.php`
  dosyalarını alfabetik yükler; her test dosyası `t_eq()` çağırır, `t_done()`
  çağırmaz — onu `run.php` yapar.

- [ ] **Adım 1: Assert yardımcılarını yaz**

`tests/lib.php`:
```php
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
```

`tests/run.php`:
```php
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
```

- [ ] **Adım 2: Koşucunun kendi testini yaz**

`tests/harness.test.php`:
```php
<?php
declare(strict_types=1);

t_eq('a', 'a', 'esit dizeler gecer');
t_eq([1, 2, 3], [1, 2, 3], 'dizi karsilastirmasi');
t_eq(['a' => 1], ['a' => 1], 'iliskisel dizi karsilastirmasi');
t_eq('{"a":1}', t_json(['a' => 1]), 't_json JSON uretir');
t_eq('"ğüş"', t_json('ğüş'), 't_json Turkce harfleri kacirmaz');
```

- [ ] **Adım 3: Koşucuyu çalıştır**

Run: `php tests/run.php; echo "cikis: $?"`
Beklenen: `5 gecti, 0 kaldi`, `cikis: 0`.

- [ ] **Adım 4: Kırmızı yolu doğrula**

Run: `php -r 'require "tests/lib.php"; t_eq(1, 2, "kasitli hata"); t_done();'; echo "cikis: $?"`
Beklenen: `x kasitli hata` satırı, `0 gecti, 1 kaldi`, `cikis: 1`.
**Koşucunun kırmızı verebildiğini görmeden ilerlenmez** — hiçbir zaman kırmızı
veremeyen bir koşucu, her testi yeşil gösterir.

- [ ] **Adım 5: Commit**

```bash
git add tests/lib.php tests/run.php tests/harness.test.php
git commit -m "test: add lightweight PHP test harness"
```

**Risk**
Yok. Site kodu etkilenmiyor, hiçbir mevcut dosya değişmiyor.

**Doğrulama komutu**
```bash
php tests/run.php && echo "--- kirmizi yolu ---" && \
php -r 'require "tests/lib.php"; t_eq(1, 2, "kasitli"); t_done();'; echo "cikis: $?"
```

**Beklenen sonuç**
Önce `5 gecti, 0 kaldi`; sonra kasıtlı hata satırı ve `cikis: 1`.

**Rollback sınırı**
`rm -rf tests/`. Başka hiçbir dosya etkilenmiyor.

**Commit sınırı**
Yalnızca `tests/` altındaki üç dosya.

---

### Görev 0B — `doctor.php` ortam kontrolleri

**Amaç**
Ortamın üç dilli çalışmaya hazır olduğunu tek komutla doğrulamak: eklentiler, PHP
sürümü, `BUILD_KEY` ve cache dizinlerinin **gerçekten yazılabilir** olması (spec §9).
Araç iki katılık düzeyinde çalışır: lokal uygunluk ve deployment uygunluğu.

**Değiştirilecek dosyalar**
- Oluştur: `tools/doctor.php`

**Arayüzler**
- Tüketir: `build_key_ok()`, `CACHE_DIR`, `PAGES_DIR`, `OG_DIR`, `CONTACT_EMAIL`
  (`inc/functions.php`, `inc/config.php`).
- Üretir: CLI/web aracı. `--deploy` bayrağı deployment katılığını açar.

**Uygulama ayrıntısı**

Katılık kuralı, `build_key_ok()`'in mevcut davranışıyla tutarlı olacak şekilde:

- **Web modu:** `build_key_ok()` zaten varsayılan anahtarda `false` döndürür, yani
  varsayılan anahtarla sayfaya hiç girilemez (403). Buraya ulaşıldıysa anahtar
  geçerlidir. Web modu bu yüzden **her zaman** deployment katılığındadır.
- **CLI, bayraksız:** yerelde çalışılıyor demektir; varsayılan `BUILD_KEY` **uyarıdır**.
  Faz 1 kapanış kontrolü bu modu kullanır ve "kritik hata yok" beklemesi tutarlıdır.
- **CLI `--deploy`:** deployment uygunluğu; varsayılan `BUILD_KEY` **kritik hatadır**.

```php
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
```

> **`curl` seviyesi neden ters:** lokalde `smoke.php` koşulacağı için `curl`
> yokluğu **kritik**tir; sunucuda smoke koşulmayacağı için **uyarı**dır. `$deploy`
> koşulu bu yüzden `'UYARI' : 'HATA'` sırasındadır — yazım hatası değildir.

**Risk**
Düşük. Yeni dosya, mevcut hiçbir kod yolunu değiştirmiyor. Tek gerçek risk sonda
bırakılan sonda dosyası; `unlink` sonrası `is_file()` ile ayrıca doğrulanıyor.

**Doğrulama komutu**
```bash
php tools/doctor.php; echo "lokal cikis: $?"
php tools/doctor.php --deploy > /dev/null; echo "deploy cikis: $?"
```

**Beklenen sonuç**
Lokal modda `Kritik hata yok` (muhtemelen `BUILD_KEY` ve `CONTACT_EMAIL` uyarılarıyla),
`lokal cikis: 0`. `--deploy` modunda `inc/config.local.php` gerçek anahtarı
içermiyorsa `deploy cikis: 1` — **bu beklenen davranıştır** ve deployment
uygunluğunun ölçüldüğünü kanıtlar.

**Rollback sınırı**
`rm tools/doctor.php`.

**Commit sınırı**
```bash
git add tools/doctor.php
git commit -m "tools: add environment doctor"
```

---

### Görev 0C — Font cmap doğrulaması

**Amaç**
`Fraunces.ttf` ve `Newsreader.ttf`'in Türkçe ve İspanyolca code point'lerini gerçekten
içerdiğini **otomatik** doğrulamak. `imagettfbbox()` eksik glifte `.notdef` kutusunu
ölçüp başarılı görünür — tofu kutusunu yakalayamaz (spec §9.1). Bu yüzden cmap
tablosu doğrudan okunur.

**Değiştirilecek dosyalar**
- Oluştur: `inc/ttf.php`
- Oluştur: `tests/ttf.test.php`
- Değiştir: `tools/doctor.php` (font bölümü eklenir)

**Arayüzler**
- Tüketir: `t_eq()` (Görev 0A), `FONT_BOLD`/`FONT_REG` (`inc/config.php`).
- Üretir: `ttf_missing_codepoints(string $file, array $codepoints): array` (eksik
  code point'ler) ve `ttf_required_codepoints(): array` (spec §9.1 minimum seti).
  Yalnızca `tools/` tarafından kullanılır; site çalışma yolunda yer almaz.

**Risk değerlendirmesi — bu okuyucu gereksiz risk üretiyor mu?**

Hayır, üç sebeple: (a) site çalışma yolunda **hiç yüklenmiyor**, yalnızca
`tools/doctor.php` ve testler çağırıyor; (b) tamamen salt okunur ve her erişim
sınır kontrollü — bozuk font en kötü ihtimalle "hepsi eksik" der, hata fırlatmaz;
(c) alternatifi olan `imagettfbbox` **yalancı olumlu** verir, yani risksiz görünüp
gerçek riski (tofu kutulu paylaşım kartları) gizler. Karmaşıklık bir kez yazılıp
bir daha dokunulmayacak 120 satırda toplanmıştır.

- [ ] **Adım 1: Başarısız testleri yaz**

`tests/ttf.test.php`:
```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/ttf.php';

$fonts = [
    'Fraunces'   => __DIR__ . '/../fonts/Fraunces.ttf',
    'Newsreader' => __DIR__ . '/../fonts/Newsreader.ttf',
];

foreach ($fonts as $name => $file) {
    t_eq([], ttf_missing_codepoints($file, [0x0041]), "$name: A (U+0041) bulunmali");
    // Okuyucunun EKSIGI TESPIT EDEBILDIGININ kaniti. Bu test yesilse okuyucu
    // her seye "var" demiyor demektir; kirmizi olursa okuyucu bozuktur.
    t_eq([0x4E00], ttf_missing_codepoints($file, [0x4E00]), "$name: U+4E00 bulunmamali");
    t_eq([], ttf_missing_codepoints($file, ttf_required_codepoints()), "$name: TR+ES seti tam");
}

// Okunamayan dosya: hepsi eksik sayilir, hata firlatilmaz.
t_eq([0x0041], ttf_missing_codepoints(__DIR__ . '/yok.ttf', [0x0041]), 'olmayan dosya');
```

- [ ] **Adım 2: Başarısız olduğunu doğrula**

Run: `php tests/run.php`
Beklenen: `PHP Fatal error: ... Call to undefined function ttf_missing_codepoints()`

- [ ] **Adım 3: Okuyucuyu yaz**

`inc/ttf.php`:
```php
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
    return $o + 1 < strlen($s) ? (ord($s[$o]) << 8) | ord($s[$o + 1]) : 0;
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
        $rec  = $cmap + 4 + $i * 8;
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
```

- [ ] **Adım 4: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **7 assert** geçer, toplamda **`0 kaldi`**.

- [ ] **Adım 5: `doctor.php`'ye font bölümünü ekle**

`tools/doctor.php` içinde son `echo "\n";` satırından **ÖNCE**:

```php
echo "\nFontlar\n";
require_once ROOT . '/inc/ttf.php';
$required = ttf_required_codepoints();
foreach (['Fraunces' => FONT_BOLD, 'Newsreader' => FONT_REG] as $name => $file) {
    if (!is_file($file)) {
        $check(false, "$name bulundu", $file);
        continue;
    }
    $missing = ttf_missing_codepoints($file, $required);
    // Eksik code point'ler TEK TEK listelenir (spec 9.1).
    $detail = '';
    foreach ($missing as $cp) {
        $detail .= sprintf('U+%04X (%s) ', $cp, mb_chr($cp, 'UTF-8'));
    }
    $check($missing === [], "$name TR+ES kapsami", 'eksik: ' . trim($detail));
}
```

- [ ] **Adım 6: Doctor'ı çalıştır**

Run: `php tools/doctor.php | sed -n '/^Fontlar/,$p'`
Beklenen:
```
Fontlar
  ok   Fraunces TR+ES kapsami
  ok   Newsreader TR+ES kapsami
```

**Eksik çıkarsa** çıktı şuna benzer ve bu bir başarısızlık değil, Faz 0'ın
**bulgusudur**:
```
  HATA Fraunces TR+ES kapsami — eksik: U+0130 (İ) U+011F (ğ)
```
Bu durumda plan durur ve font kararı alınır. **Cmap testi geçmeden Faz 0 başarılı
sayılmaz.**

- [ ] **Adım 7: Commit**

```bash
git add inc/ttf.php tests/ttf.test.php tools/doctor.php
git commit -m "tools: verify font glyph coverage from cmap table"
```

**Risk**
Orta. Cmap format 4 adres aritmetiği yanlış yazıldığında **sessizce "hepsi var"**
diyebilir. `U+4E00` testi tam bu yüzden var. İkinci risk fontun gerçekten eksik glif
taşıması — bu bir plan hatası değil, planın bulması gereken sonuçtur.

**Doğrulama komutu**
```bash
php tests/run.php && php tools/doctor.php | sed -n '/^Fontlar/,$p'
```

**Beklenen sonuç**
Testlerde `0 kaldi`; doctor font bölümünde iki `ok`.

**Rollback sınırı**
`rm inc/ttf.php tests/ttf.test.php` + `doctor.php`'deki font bloğunu geri al.
Site kodu etkilenmiyor.

**Commit sınırı**
Üç dosya. Başka hiçbir şey.

---

### Görev 0D — Örnek OG tuvali (görsel inceleme)

**Amaç**
Cmap testi glifin **var** olduğunu söyler, **iyi durduğunu** söylemez. Bu görev üç
dilin gerçek metinlerini üretim primitifleriyle (`og_wrap`, `og_text`,
`og_text_width`) çizer ve göze sunar. Spec §9.1: bu inceleme yapılmadan TR/ES OG
üretimi onaylanmaz.

**Neden üretim renderer'ı kullanılmıyor**

`og_render(?array $job, string $slug)` incelendiğinde üç engel var:

1. İkinci parametre **slug**'dır, verdict etiketi değil.
2. Verdict metnini `verdict_meta()` üzerinden **İngilizce** `VERDICTS`'ten üretir —
   `DARALIYOR` veya `EN EL MENÚ` hiçbir zaman karta basılmaz. Locale sistemi Faz 3'te
   gelecek.
3. "What survives" alanı `resistanceTags` doluysa onu, boşsa `oneLiner`'ı çizer —
   yani sınamak istediğimiz aksanlı cümle çoğu durumda hiç çizilmez.

Bu yüzden araç **bağımsız bir tuval** üretir ve `inc/ogcard.php`'ye **dokunmaz**.
Sınadığı şey renderer'ın mantığı değil, fontun ve sarma/ölçme primitiflerinin
Türkçe ve İspanyolca karakterlerle davranışıdır.

**Değiştirilecek dosyalar**
- Oluştur: `tools/og-samples.php`
- Değiştir: `.gitignore`

**Arayüzler**
- Tüketir: `og_wrap()`, `og_text()`, `og_text_width()`, `og_ready()`, `OG_W`, `OG_H`,
  `PAD`, `FONT_BOLD`, `FONT_REG` (`inc/ogcard.php` — salt okunur kullanım).
- Üretir: `cache/og-samples/{en,tr,es}.png`.

**Uygulama ayrıntısı**

```php
<?php
/**
 * EN/TR/ES ornek tuvalleri — GOZLE incelenmek uzere.
 * Cmap testi glifin VAR oldugunu soyler; bu script IYI DURDUGUNU gosterir.
 * inc/ogcard.php'ye DOKUNMAZ: yalnizca primitiflerini (og_wrap/og_text/og_text_width)
 * tuketir. Uretim renderer'i verdict etiketini Ingilizce VERDICTS'ten uretiyor,
 * o yuzden yerel etiketler ancak boyle bir tuvalde sinanabiliyor (locale Faz 3).
 *   php tools/og-samples.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/ogcard.php';

if (!og_ready()) {
    exit("GD + FreeType ve fonts/ gerekli.\n");
}

$dir = CACHE_DIR . '/og-samples';
if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
    exit("cache/og-samples olusturulamadi.\n");
}

$sheets = [
    'en' => [
        'title'    => 'SOFTWARE DEVELOPER',
        'verdicts' => ['SAFE', 'SHRINKING', 'ON THE MENU'],
        'charset'  => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789',
        'sentence' => 'The boilerplate is gone. The 3am pager is not, and it never will be.',
        'until'    => 'safe until ~2032',
    ],
    'tr' => [
        'title'    => 'İŞE ALIM UZMANI',
        'verdicts' => ['GÜVENDE', 'DARALIYOR', 'MENÜDE'],
        'charset'  => 'Ç Ğ İ Ö Ş Ü ç ğ ı i ö ş ü',
        'sentence' => 'Özgeçmiş taraması gitti; işe alma kararı ve şirketin itibarı insanda kalıyor.',
        'until'    => '~2030 yılına kadar güvende',
    ],
    'es' => [
        'title'    => '¿DESARROLLADOR?',
        'verdicts' => ['A SALVO', 'SE REDUCE', 'EN EL MENÚ'],
        'charset'  => 'Á É Í Ó Ú Ü Ñ ¿ ¡ á é í ó ú ü ñ',
        'sentence' => 'La programación básica ya desapareció; el diseño y la responsabilidad resisten.',
        'until'    => 'a salvo hasta ~2029',
    ],
];

$warnings = [];

foreach ($sheets as $lang => $s) {
    $img  = imagecreatetruecolor(OG_W, OG_H);
    imageantialias($img, true);
    $bg   = imagecolorallocate($img, 246, 243, 238);
    $ink  = imagecolorallocate($img, 28, 26, 23);
    $ink2 = imagecolorallocate($img, 85, 80, 74);
    $ink3 = imagecolorallocate($img, 138, 130, 121);
    imagefilledrectangle($img, 0, 0, OG_W, OG_H, $bg);

    // 1) Baslik — uretimdeki gibi harf araligi acilmis 26pt
    $spaced = implode(' ', preg_split('//u', $s['title'], -1, PREG_SPLIT_NO_EMPTY) ?: []);
    if (og_text_width($spaced, FONT_BOLD, 26) > OG_W - PAD * 2) {
        $spaced = $s['title'];
    }
    og_text($img, 26, PAD, 70, $ink3, FONT_BOLD, $spaced);

    // 2) Verdict etiketleri — uretimdeki 96pt en kotu durum
    $y = 170;
    foreach ($s['verdicts'] as $label) {
        og_text($img, 96, PAD, $y, $ink, FONT_BOLD, $label);
        // Uretimde 96pt sigmayinca 52pt'e kadar kuculuyor; hangisinin kuculecegini
        // simdiden bilelim.
        if (og_text_width($label, FONT_BOLD, 96) > OG_W - PAD * 2) {
            $warnings[] = sprintf('%s: "%s" 96pt te tasiyor, uretimde kuculecek', $lang, $label);
        }
        $y += 104;
    }

    // 3) Tam karakter seti — iki fontta da
    og_text($img, 30, PAD, $y + 16, $ink, FONT_BOLD, $s['charset']);
    og_text($img, 30, PAD, $y + 58, $ink2, FONT_REG, $s['charset']);

    // 4) Sarma davranisi — og_wrap aksanli metinde satiri dogru kiriyor mu
    $ly = $y + 112;
    foreach (og_wrap($s['sentence'], FONT_REG, 30, OG_W - PAD * 2, 2) as $line) {
        og_text($img, 30, PAD, $ly, $ink, FONT_REG, $line);
        $ly += 42;
    }

    og_text($img, 32, PAD, OG_H - 40, $ink2, FONT_REG, $s['until']);

    imagepng($img, $dir . '/' . $lang . '.png', 6);
    imagedestroy($img);
    echo "yazildi: cache/og-samples/$lang.png\n";
}

foreach ($warnings as $w) {
    echo "UYARI: $w\n";
}

echo "\nUCUNU DE ACIP GOZLE INCELE. Aranacaklar:\n";
echo "  - tofu kutusu (box) YOK\n";
echo "  - GUVENDE / DARALIYOR / MENUDE ve A SALVO / SE REDUCE / EN EL MENU okunuyor\n";
echo "  - C G I O S U c g i o s u ve A E I O U N ters noktalama dogru ciziliyor\n";
echo "  - satir kirilmasi kelime ortasindan gecmiyor, kerning bozuk degil\n";
```

`.gitignore`'a eklenecek satır (mevcut `cache/` bloğunun altına):
```
cache/og-samples/
```

- [ ] **Adım 1: Aracı yaz ve `.gitignore`'u güncelle**
- [ ] **Adım 2: Çalıştır ve üç görseli aç**

```bash
php tools/og-samples.php
open cache/og-samples/en.png cache/og-samples/tr.png cache/og-samples/es.png
```

- [ ] **Adım 3: Cache'in commit'e sızmadığını doğrula**

Run: `git status --short cache/`
Beklenen: boş çıktı.

- [ ] **Adım 4: Commit**

```bash
git add tools/og-samples.php .gitignore
git commit -m "tools: generate EN/TR/ES sample OG canvases for visual review"
```

**Risk**
Düşük. Üretilen dosyalar cache içinde ve ignore'da; üretim renderer'ına
dokunulmuyor.

**Doğrulama komutu**
```bash
php tools/og-samples.php && git status --short cache/
```

**Beklenen sonuç**
Üç PNG (1200×630) üretilir, `git status` boş döner. Sığmayan verdict etiketi varsa
`UYARI:` satırı basılır — bu bilgi, hata değil.

**Bu bir insan onayıdır.** Otomatik geçme koşulu yoktur; kullanıcı üç görseli
onaylamadan Faz 4/5'te TR/ES OG üretimi açılmaz.

**Rollback sınırı**
`rm tools/og-samples.php && rm -rf cache/og-samples` + `.gitignore` satırını geri al.

**Commit sınırı**
İki dosya.

---

# FAZ 1 — Front controller

---

### Görev 1A — Saf URL çözümleyici ve güvenlik fixture'ları

**Amaç**
Tüm URL kurallarını, dosya sistemine ve global duruma **dokunmayan** tek bir
fonksiyonda toplamak. Aynı commit'te `path_is_forbidden()`'ın **tam** fixture setini
yeşile almak — kritik güvenlik sınırı, açılmadan çok önce test edilmiş olur.

**Değiştirilecek dosyalar**
- Oluştur: `inc/routing.php`
- Oluştur: `tests/routing.test.php`

**Arayüzler**
- Tüketir: `t_eq()` (Görev 0A). Test koşucusu **zaten mevcuttur**, bu görev onu
  oluşturmaz.
- Üretir:
  - `resolve_path(string $path, array $routes): array` — her zaman `type` taşır:
    `forbidden`, `og`, `sitemap`, `llms`, `home`, `page`, `job`, `unavailable`,
    `notfound`, `redirect`. `redirect` ayrıca `status` (int) ve `location` (string).
  - `path_is_forbidden(string $path): bool`
  - `lang_prefix(string $lang): string`
  - `LANGS`, `DEFAULT_LANG` sabitleri.
  Görev 1B, 1C, 1D bu sözleşmeye dayanır.

- [ ] **Adım 1: Başarısız testleri yaz**

`tests/routing.test.php`:
```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/routing.php';

// Faz 1 gercegi: yalnizca EN aktif olacak. Fixture'da TR de aktif —
// cozumleyici dilden BAGIMSIZ olmali, Faz 4'te yeniden yazilmamali.
$R = [
    'activeLangs' => ['en', 'tr'],
    'ids' => [
        'software-developer' => ['en' => 'software-developer', 'tr' => 'yazilim-gelistirici'],
        'accountant'         => ['en' => 'accountant'],
    ],
    'slugs' => [
        'en' => ['software-developer' => 'software-developer', 'accountant' => 'accountant'],
        'tr' => ['yazilim-gelistirici' => 'software-developer', 'yazilimci' => 'software-developer'],
        'es' => [],
    ],
    'published' => [
        'software-developer' => ['en', 'tr'],
        'accountant'         => ['en'],
    ],
    'pages'     => ['en' => ['methodology' => 'methodology'],
                    'tr' => ['metodoloji' => 'methodology'], 'es' => []],
    'pageSlugs' => ['en' => ['methodology' => 'methodology'],
                    'tr' => ['methodology' => 'metodoloji'], 'es' => []],
];

$r     = static fn (string $p): array => resolve_path($p, $R);
$redir = static fn (string $to): array => ['type' => 'redirect', 'status' => 301, 'location' => $to];

// --- Ana sayfalar ve normalizasyon (spec 1.1, 1.4) ---
t_eq(['type' => 'home', 'lang' => 'en'], $r('/'),    '/ EN ana sayfa');
t_eq($redir('/'),                        $r('/en'),  '/en -> /');
t_eq($redir('/'),                        $r('/en/'), '/en/ -> /');
t_eq($redir('/tr/'),                     $r('/tr'),  '/tr -> /tr/');
t_eq(['type' => 'home', 'lang' => 'tr'], $r('/tr/'), '/tr/ ana sayfa');

// --- Meslek sayfalari ---
t_eq(['type' => 'job', 'lang' => 'en', 'id' => 'software-developer'],
     $r('/software-developer'), 'EN entry');
t_eq(['type' => 'job', 'lang' => 'tr', 'id' => 'software-developer'],
     $r('/tr/yazilim-gelistirici'), 'TR entry');
t_eq($redir('/software-developer'),     $r('/software-developer/'),      'sondaki egik cizgi');
t_eq($redir('/tr/yazilim-gelistirici'), $r('/tr/yazilim-gelistirici/'),  'TR sondaki egik cizgi');

// --- Bilinen slug -> yerel canonical, TEK adim (spec 1.3, 1.4) ---
t_eq($redir('/tr/yazilim-gelistirici'), $r('/tr/software-developer'),  'id -> TR canonical');
t_eq($redir('/tr/yazilim-gelistirici'), $r('/tr/yazilimci'),           'formerSlug -> TR canonical');
t_eq($redir('/software-developer'),     $r('/en/yazilim-gelistirici'), '/en/<tr-slug> -> EN canonical');
t_eq($redir('/software-developer'),     $r('/en/software-developer/'), '/en/<en-slug>/ tek adimda');

// --- Bilinmeyen: KIRPMA YOK (spec 1.4) ---
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/en/not-a-real-job'), '/en/bilinmeyen -> 404');
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/unknown'),           'bilinmeyen -> 404');
t_eq(['type' => 'notfound', 'lang' => 'tr'], $r('/tr/unknown'),        'TR bilinmeyen -> 404');
// aka YONLENDIRME KAYNAGI DEGIL (spec 1.3): routes tablosunda hic yer almaz.
t_eq(['type' => 'notfound', 'lang' => 'tr'], $r('/tr/developer'),      'aka yonlendirmez');

// --- Yayinlanmamis dil (spec 5.4): 301 URETILMEZ ---
t_eq(['type' => 'unavailable', 'lang' => 'tr', 'id' => 'accountant'],
     $r('/tr/accountant'), 'TR de yayinlanmamis -> unavailable');

// --- Aktif olmayan dil ---
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/es/'),          'ES aktif degil');
t_eq(['type' => 'notfound', 'lang' => 'en'], $r('/es/cualquier'), 'ES aktif degil, entry');

// --- Sabit sayfalar ---
t_eq(['type' => 'page', 'lang' => 'en', 'key' => 'methodology'], $r('/methodology'),   'EN sabit sayfa');
t_eq(['type' => 'page', 'lang' => 'tr', 'key' => 'methodology'], $r('/tr/metodoloji'), 'TR sabit sayfa');
t_eq($redir('/tr/metodoloji'), $r('/tr/methodology'), 'sabit sayfa capraz slug');

// --- Uretilen dosyalar ---
t_eq(['type' => 'sitemap'], $r('/sitemap.xml'), 'sitemap');
t_eq(['type' => 'llms'],    $r('/llms.txt'),    'llms');

// --- OG: aktif dil ve yayin kontrolunden KACAMAZ ---
t_eq(['type' => 'og', 'lang' => 'en', 'slug' => 'accountant'],
     $r('/og/accountant.png'), 'EN OG');
t_eq(['type' => 'og', 'lang' => 'en', 'slug' => 'home'],
     $r('/og/home.png'), 'site geneli OG korunuyor');
t_eq(['type' => 'og', 'lang' => 'tr', 'slug' => 'yazilim-gelistirici'],
     $r('/og/tr/yazilim-gelistirici.png'), 'TR OG');
t_eq($redir('/og/tr/yazilim-gelistirici.png'),
     $r('/og/tr/software-developer.png'), 'OG: bilinen slug -> canonical, tek 301');
t_eq(['type' => 'notfound', 'lang' => 'tr'],
     $r('/og/tr/accountant.png'), 'OG: TR de yayinlanmamis -> 404');
t_eq(['type' => 'notfound', 'lang' => 'en'],
     $r('/og/es/yazilim-gelistirici.png'), 'OG: ES aktif degil -> 404');
t_eq(['type' => 'notfound', 'lang' => 'tr'],
     $r('/og/tr/hayali-meslek.png'), 'OG: bilinmeyen slug -> 404');

// --- GUVENLIK: tam fixture seti (spec 1.8) ---
// Bu blok, .htaccess/router.php gecisinden ONCE yesil olmak ZORUNDA.
$forbidden = [
    '/data/jobs/accountant.json',
    '/data/jobs/accountant/en.json',
    '/data/changelog.json',
    '/inc/config.local.php',
    '/inc/functions.php',
    '/inc/ttf.php',
    '/cache/routes.json',
    '/cache/index.json',
    '/cache/pages/en/accountant.html',
    '/docs/architecture/2026-08-15-cok-dilli-mimari.md',
    '/research/sources.json',
    '/tests/routing.test.php',
    '/.git/config',
    '/.env',
    '/.gitignore',
    '/README.md',
    '/CLAUDE.md',
    '/inc/config.local.php.example',
];
foreach ($forbidden as $p) {
    t_eq(['type' => 'forbidden'], $r($p), "yasak: $p");
    t_eq(true, path_is_forbidden($p), "path_is_forbidden: $p");
}

// Acik kalmasi GEREKENLER — asiri kisitlama da bir hatadir.
$open = [
    '/.well-known/security.txt',   // SSL yenileme ve security.txt buraya bakiyor
    '/assets/style.css',
    '/assets/search.js',
    '/fonts/Fraunces.ttf',
    '/og/accountant.png',
    '/tools/doctor.php',           // BUILD_KEY ile korunuyor, kapatilmiyor
    '/sitemap.xml',
    '/accountant',
];
foreach ($open as $p) {
    t_eq(false, path_is_forbidden($p), "acik: $p");
}
```

- [ ] **Adım 2: Testlerin başarısız olduğunu doğrula**

Run: `php tests/run.php`
Beklenen: `PHP Fatal error: ... Call to undefined function resolve_path()`

- [ ] **Adım 3: Çözümleyiciyi yaz**

`inc/routing.php`:
```php
<?php
/**
 * SAF URL cozumleyici. Dosya sistemine, $_SERVER'a ve global duruma DOKUNMAZ —
 * girdisi (path, routes), ciktisi bir karar dizisi. Boyle oldugu icin
 * .htaccess ve router.php ayni kurallari paylasabiliyor ve hepsi test edilebiliyor.
 */
declare(strict_types=1);

const LANGS        = ['en', 'tr', 'es'];
const DEFAULT_LANG = 'en';

/** Dil prefix'i. Ingilizce prefix'sizdir — kabul edilmis SEO istisnasi (spec 1.1). */
function lang_prefix(string $lang): string
{
    return $lang === DEFAULT_LANG ? '/' : '/' . $lang . '/';
}

/**
 * Guvenlik siniri. GERCEK DOSYA KONTROLUNDEN ONCE calisir (spec 1.8) —
 * ters sirada data/jobs/*.json gercek dosya oldugu icin ham servis edilir.
 */
function path_is_forbidden(string $path): bool
{
    if (preg_match('#^/(data|inc|cache|docs|research|tests)(/|$)#', $path) === 1) {
        return true;
    }
    // Nokta ile baslayan her yol segmenti; .well-known disarida.
    if (preg_match('#(^|/)\.(?!well-known(/|$))#', $path) === 1) {
        return true;
    }
    if (preg_match('#\.(md|example)$#i', $path) === 1) {
        return true;
    }
    return false;
}

function route_redirect(string $to): array
{
    return ['type' => 'redirect', 'status' => 301, 'location' => $to];
}

/** Bir slug'i meslek kimligine cozer: bu dilin tablosu, id'nin kendisi, baska dil. */
function resolve_job_id(string $lang, string $slug, array $routes): ?string
{
    $id = $routes['slugs'][$lang][$slug] ?? null;
    if ($id !== null) {
        return (string)$id;
    }
    if (isset($routes['ids'][$slug])) {
        return $slug;
    }
    foreach (LANGS as $l) {
        if (isset($routes['slugs'][$l][$slug])) {
            return (string)$routes['slugs'][$l][$slug];
        }
    }
    return null;
}

/** OG kart yolu. EN prefix'siz, digerleri /og/<lang>/ altinda (spec 5.6). */
function og_path(string $lang, string $slug): string
{
    return $lang === DEFAULT_LANG
        ? '/og/' . $slug . '.png'
        : '/og/' . $lang . '/' . $slug . '.png';
}

/**
 * OG cozumlemesi. Sayfa cozumlemesiyle AYNI kurallara tabi: aktif dil, kayitli
 * slug, yayin durumu. Gorsel istegi oldugu icin yayinlanmamis durum HTML
 * unavailable sayfasi degil 404 doner.
 */
function resolve_og(string $lang, string $slug, array $routes): array
{
    // Site geneli kart: entry degil, mevcut yol korunuyor (og.php 'home' destekliyor).
    if ($slug === 'home' && $lang === DEFAULT_LANG) {
        return ['type' => 'og', 'lang' => DEFAULT_LANG, 'slug' => 'home'];
    }

    if (!in_array($lang, (array)($routes['activeLangs'] ?? [DEFAULT_LANG]), true)) {
        return ['type' => 'notfound', 'lang' => DEFAULT_LANG];
    }

    $id = resolve_job_id($lang, $slug, $routes);
    if ($id === null) {
        return ['type' => 'notfound', 'lang' => $lang];
    }
    if (!in_array($lang, (array)($routes['published'][$id] ?? []), true)) {
        return ['type' => 'notfound', 'lang' => $lang];
    }

    $canon = $routes['ids'][$id][$lang] ?? null;
    if ($canon === null) {
        return ['type' => 'notfound', 'lang' => $lang];
    }
    return $slug === $canon
        ? ['type' => 'og', 'lang' => $lang, 'slug' => $canon]
        : route_redirect(og_path($lang, (string)$canon));
}

/**
 * @param string $path Sorgu dizesi AYIKLANMIS yol (/tr/yazilim-gelistirici)
 * @param array  $routes cache/routes.json icerigi (bkz. build_routes())
 */
function resolve_path(string $path, array $routes): array
{
    if ($path === '') {
        $path = '/';
    }

    // 1. Guvenlik her seyden once.
    if (path_is_forbidden($path)) {
        return ['type' => 'forbidden'];
    }

    // 2. OG kartlari — dil alt klasoru opsiyonel (spec 5.6).
    if (preg_match('#^/og/(?:(tr|es)/)?([a-z0-9-]+)\.png$#', $path, $m) === 1) {
        return resolve_og($m[1] !== '' ? $m[1] : DEFAULT_LANG, $m[2], $routes);
    }

    // 3. Uretilen statik dosyalar.
    if ($path === '/sitemap.xml') {
        return ['type' => 'sitemap'];
    }
    if ($path === '/llms.txt') {
        return ['type' => 'llms'];
    }

    $trailing = $path !== '/' && str_ends_with($path, '/');
    $clean    = trim($path, '/');
    $seg      = $clean === '' ? [] : explode('/', $clean);

    if ($seg === []) {
        return ['type' => 'home', 'lang' => DEFAULT_LANG];
    }

    // 4. Dil prefix'i.
    $lang = null;
    if (in_array($seg[0], LANGS, true)) {
        $lang = (string)array_shift($seg);
    }

    // 4a. /en ve /en/ her zaman koke iner (spec 1.4).
    if ($lang === DEFAULT_LANG && $seg === []) {
        return route_redirect('/');
    }

    // 4b. Aktif olmayan dil hic yokmus gibi davranir.
    if ($lang !== null && !in_array($lang, (array)($routes['activeLangs'] ?? [DEFAULT_LANG]), true)) {
        return ['type' => 'notfound', 'lang' => DEFAULT_LANG];
    }

    // 4c. Dil ana sayfasi — egik cizgi ZORUNLU (spec 1.1).
    if ($lang !== null && $seg === []) {
        return $trailing
            ? ['type' => 'home', 'lang' => $lang]
            : route_redirect('/' . $lang . '/');
    }

    $lang = $lang ?? DEFAULT_LANG;

    // 5. Tek segment disinda hicbir sey yok.
    if (count($seg) !== 1) {
        return ['type' => 'notfound', 'lang' => $lang];
    }
    $slug = $seg[0];

    // 6. Sabit sayfa mi?
    $key = $routes['pages'][$lang][$slug] ?? null;
    if ($key !== null) {
        $canon = lang_prefix($lang) . $slug;
        return $path === $canon
            ? ['type' => 'page', 'lang' => $lang, 'key' => (string)$key]
            : route_redirect($canon);
    }
    // Baska dilin sabit sayfa slug'i mi? (/tr/methodology -> /tr/metodoloji)
    foreach (LANGS as $l) {
        $k = $routes['pages'][$l][$slug] ?? null;
        if ($k !== null && isset($routes['pageSlugs'][$lang][$k])) {
            return route_redirect(lang_prefix($lang) . $routes['pageSlugs'][$lang][$k]);
        }
    }

    // 7. Meslek.
    $id = resolve_job_id($lang, $slug, $routes);
    if ($id === null) {
        return ['type' => 'notfound', 'lang' => $lang];   // aka buraya duser: 404
    }

    // 8. Hedef dilde yayinlanmis mi? Degilse 301 URETILMEZ (spec 1.3).
    if (!in_array($lang, (array)($routes['published'][$id] ?? []), true)) {
        return ['type' => 'unavailable', 'lang' => $lang, 'id' => $id];
    }

    $canonSlug = $routes['ids'][$id][$lang] ?? null;
    if ($canonSlug === null) {
        return ['type' => 'unavailable', 'lang' => $lang, 'id' => $id];
    }
    $canon = lang_prefix($lang) . $canonSlug;

    // 9. Tek adimda kanonik bicime in — egik cizgi ve alias ayni 301'de cozulur.
    return $path === $canon
        ? ['type' => 'job', 'lang' => $lang, 'id' => $id]
        : route_redirect($canon);
}
```

- [ ] **Adım 4: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **76 assert** geçer — 25 çözümleme, 7 OG,
36 yasak (18 yol × 2 assert), 8 açık kalması gereken yol — toplamda **`0 kaldi`**.

- [ ] **Adım 5: Sitenin hâlâ eski yoldan çalıştığını doğrula**

```bash
php -S 127.0.0.1:8000 router.php > /dev/null 2>&1 &
SRV=$!; sleep 1
curl -sI 127.0.0.1:8000/accountant | head -1
kill $SRV
```
Beklenen: `HTTP/1.1 200 OK` — bu commit çalışan siteye dokunmadı.

- [ ] **Adım 6: Commit**

```bash
git add inc/routing.php tests/routing.test.php
git commit -m "feat: add pure URL resolver with security fixtures"
```

**Risk**
Orta-yüksek: çözümleyici tüm trafiğin geçeceği yer. Ama bu commit'te **hiçbir şeye
bağlı değil** — `route.php` yok, `.htaccess` dokunulmadı. Yanlışsa site etkilenmez,
yalnızca testler kırmızı olur.

**Doğrulama komutu**
```bash
php tests/run.php
```

**Beklenen sonuç**
`0 kaldi`, çıkış kodu 0. Güvenlik bloğu kırmızı verirse **hiçbir sonraki göreve
geçilmez**.

**Rollback sınırı**
`git revert` tek commit. Site zaten bu koda bağlı değil.

**Commit sınırı**
`inc/routing.php` + testi. `.htaccess`, `router.php`, `job.php` **girmez**.

---

### Görev 1B — `url_for()` ve `alternates_for()`

**Amaç**
URL üretimini tek yere toplamak. İngilizcenin prefix'siz olması bu iki fonksiyonun
içinde tek koşul olarak yaşar; şablonlara dağılmaz (spec §1.7).

**Değiştirilecek dosyalar**
- Oluştur: `inc/urls.php`
- Oluştur: `tests/urls.test.php`

**Arayüzler**
- Tüketir: `lang_prefix()`, `og_path()`, `LANGS`, `DEFAULT_LANG` (Görev 1A);
  `SITE_URL` (`inc/config.php`).
- Üretir: `url_for(string $lang, string $type, string $key, array $routes): string`
  ve `alternates_for(string $type, string $key, array $routes): array`
  (`['en' => 'https://...', 'x-default' => 'https://...']`). Faz 4 hreflang bloğu
  ve sitemap bunu tüketir.

- [ ] **Adım 1: Başarısız testleri yaz**

`tests/urls.test.php`:
```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/urls.php';

$U = [
    'activeLangs' => ['en', 'tr'],
    'ids' => ['software-developer' => ['en' => 'software-developer', 'tr' => 'yazilim-gelistirici'],
              'accountant'         => ['en' => 'accountant']],
    'published' => ['software-developer' => ['en', 'tr'], 'accountant' => ['en']],
    'pageSlugs' => ['en' => ['methodology' => 'methodology'],
                    'tr' => ['methodology' => 'metodoloji']],
];

t_eq('https://willaistealit.com/',    url_for('en', 'home', '', $U), 'EN ana sayfa prefix siz');
t_eq('https://willaistealit.com/tr/', url_for('tr', 'home', '', $U), 'TR ana sayfa');
t_eq('https://willaistealit.com/software-developer',
     url_for('en', 'job', 'software-developer', $U), 'EN entry');
t_eq('https://willaistealit.com/tr/yazilim-gelistirici',
     url_for('tr', 'job', 'software-developer', $U), 'TR entry');
t_eq('https://willaistealit.com/tr/metodoloji',
     url_for('tr', 'page', 'methodology', $U), 'TR sabit sayfa');
t_eq('https://willaistealit.com/og/accountant.png',
     url_for('en', 'og', 'accountant', $U), 'EN OG dil klasorsuz');
t_eq('https://willaistealit.com/og/tr/yazilim-gelistirici.png',
     url_for('tr', 'og', 'software-developer', $U), 'TR OG');

// Alternates YALNIZCA yayinlanan dillerden kurulur (spec 5.1).
t_eq(['en' => 'https://willaistealit.com/software-developer',
      'tr' => 'https://willaistealit.com/tr/yazilim-gelistirici',
      'x-default' => 'https://willaistealit.com/software-developer'],
     alternates_for('job', 'software-developer', $U), 'iki dilde yayinli');

t_eq(['en' => 'https://willaistealit.com/accountant',
      'x-default' => 'https://willaistealit.com/accountant'],
     alternates_for('job', 'accountant', $U), 'TR satiri HIC basilmaz');

t_eq(['en' => 'https://willaistealit.com/methodology',
      'tr' => 'https://willaistealit.com/tr/metodoloji',
      'x-default' => 'https://willaistealit.com/methodology'],
     alternates_for('page', 'methodology', $U), 'sabit sayfa alternates');
```

- [ ] **Adım 2: Başarısız olduğunu doğrula**

Run: `php tests/run.php`
Beklenen: `Call to undefined function url_for()`

- [ ] **Adım 3: Uygulamayı yaz**

`inc/urls.php`:
```php
<?php
/**
 * URL uretiminin TEK kaynagi. Sablonlarda elle href kurulmaz.
 * Ingilizcenin prefix'siz olmasi burada tek bir kosul olarak yasar (spec 1.7).
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/routing.php';

/**
 * @param string $type 'home' | 'job' | 'page' | 'og'
 * @param string $key  job/og icin meslek ID'si, page icin sayfa anahtari
 */
function url_for(string $lang, string $type, string $key, array $routes): string
{
    $base = rtrim(SITE_URL, '/');

    if ($type === 'home') {
        return $base . lang_prefix($lang);
    }
    if ($type === 'og') {
        return $base . og_path($lang, (string)($routes['ids'][$key][$lang] ?? $key));
    }

    $slug = $type === 'page'
        ? (string)($routes['pageSlugs'][$lang][$key] ?? $key)
        : (string)($routes['ids'][$key][$lang] ?? $key);

    return $base . lang_prefix($lang) . $slug;
}

/**
 * hreflang kumesi. YALNIZCA yayinlanan dillerden kurulur — karsilikliligi
 * yapisal olarak garanti eden sey bu (spec 5.1).
 * @return array<string,string> 'en'|'tr'|'es'|'x-default' => mutlak URL
 */
function alternates_for(string $type, string $key, array $routes): array
{
    $out    = [];
    $active = (array)($routes['activeLangs'] ?? [DEFAULT_LANG]);

    foreach (LANGS as $lang) {
        if (!in_array($lang, $active, true)) {
            continue;
        }
        if ($type === 'job' && !in_array($lang, (array)($routes['published'][$key] ?? []), true)) {
            continue;
        }
        if ($type === 'page' && !isset($routes['pageSlugs'][$lang][$key])) {
            continue;
        }
        $out[$lang] = url_for($lang, $type, $key, $routes);
    }

    // x-default her zaman Ingilizce (spec 5.1).
    if (isset($out[DEFAULT_LANG])) {
        $out['x-default'] = $out[DEFAULT_LANG];
    }
    return $out;
}
```

- [ ] **Adım 4: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **10 assert** geçer, toplamda **`0 kaldi`**.

- [ ] **Adım 5: Commit**

```bash
git add inc/urls.php tests/urls.test.php
git commit -m "feat: add url_for and alternates_for helpers"
```

**Risk**
Düşük. Hiçbir mevcut şablon henüz bunu çağırmıyor; saf ek.

**Doğrulama komutu**
```bash
php tests/run.php
```

**Beklenen sonuç**
`0 kaldi`.

**Rollback sınırı**
`git revert` tek commit.

**Commit sınırı**
İki dosya. Şablonlar (`job.php`, `inc/header.php`) bu fazda **değiştirilmez** —
onlar Faz 3'ün işi.

---

### Görev 1C — Route cache: üretim, semantik doğrulama, atomik yazım

**Amaç**
`routes.json`'ı üretmek, **yokluğunda ve bozukluğunda sitenin çökmemesini** garanti
etmek, ve şeklen doğru ama semantik olarak bozuk bir cache'in tüm siteyi yanlış
404'e düşürmesini engellemek (spec §1.5).

**Değiştirilecek dosyalar**
- Oluştur: `inc/routes_cache.php`
- Oluştur: `tests/routes_cache.test.php`
- Değiştir: `inc/config.php` (`ROUTES_FILE` sabiti)
- Değiştir: `.gitignore`
- Değiştir: `tools/build-index.php`

**Arayüzler**
- Tüketir: `load_all_jobs()`, `valid_slug()` (`inc/functions.php`); `LANGS`,
  `DEFAULT_LANG` (Görev 1A).
- Üretir:
  - `atomic_write(string $file, string $data): bool`
  - `routes_valid(mixed $d): bool` — **semantik** doğrulama
  - `build_routes(?array &$conflicts = null): array`
  - `load_routes(?string $file = null): array` — **dosya yolu enjekte edilebilir**
  - `routes_cache_reset(): void`
  Görev 1D `load_routes()` çağırır.

- [ ] **Adım 1: `ROUTES_FILE` sabitini ekle**

`inc/config.php` içinde `INDEX_FILE` satırının hemen altına:
```php
const ROUTES_FILE = CACHE_DIR . '/routes.json';
```

- [ ] **Adım 2: Başarısız testleri yaz**

`tests/routes_cache.test.php` — **gerçek `ROUTES_FILE`'a asla dokunmaz**, kendi
geçici dizinini kullanır:
```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/routes_cache.php';

/** Bu testin kendi gecici alani. Gercek cache/routes.json'a DOKUNULMAZ. */
$tmpDir = sys_get_temp_dir() . '/wais-routes-' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0775, true);

$good = [
    'activeLangs' => ['en'],
    'ids'         => ['accountant' => ['en' => 'accountant']],
    'slugs'       => ['en' => ['accountant' => 'accountant'], 'tr' => [], 'es' => []],
    'published'   => ['accountant' => ['en']],
    'pages'       => ['en' => ['methodology' => 'methodology'], 'tr' => [], 'es' => []],
    'pageSlugs'   => ['en' => ['methodology' => 'methodology'], 'tr' => [], 'es' => []],
];

// --- routes_valid: sekil ---
t_eq(false, routes_valid(null),            'null gecersiz');
t_eq(false, routes_valid([]),              'bos dizi gecersiz');
t_eq(false, routes_valid('{"ids":1}'),     'dize gecersiz');
t_eq(false, routes_valid(['ids' => []]),   'eksik anahtar gecersiz');
t_eq(true,  routes_valid($good),           'tam tablo gecerli');

// --- routes_valid: semantik (spec 1.5) ---
$bad = $good; $bad['activeLangs'] = ['en', 'de'];
t_eq(false, routes_valid($bad), 'bilinmeyen dil kodu reddedilir');

$bad = $good; $bad['activeLangs'] = ['tr'];
t_eq(false, routes_valid($bad), 'varsayilan dil aktif degilse reddedilir');

$bad = $good; $bad['published']['hayali'] = ['en'];
t_eq(false, routes_valid($bad), 'published kimligi ids icinde yoksa reddedilir');

$bad = $good; $bad['published']['accountant'] = ['en', 'tr'];
t_eq(false, routes_valid($bad), 'yayinlanan dilde canonical slug yoksa reddedilir');

$bad = $good; $bad['slugs']['en']['hayali-slug'] = 'hayali-kimlik';
t_eq(false, routes_valid($bad), 'slug bilinmeyen kimlige gidiyorsa reddedilir');

$bad = $good; $bad['pageSlugs']['en']['methodology'] = 'baska-slug';
t_eq(false, routes_valid($bad), 'pages/pageSlugs karsilikli degilse reddedilir');

// --- atomik yazim ---
$f = $tmpDir . '/alt/a.json';
t_eq(true, atomic_write($f, '{"x":1}'), 'dizin yoksa olusturur ve yazar');
t_eq('{"x":1}', (string)file_get_contents($f), 'icerik dogru');
t_eq([], glob(dirname($f) . '/*.tmp') ?: [], 'gecici dosya birakilmadi');

// --- build_routes: gercek veriden uretir, cakismasiz olmali ---
$conflicts = null;
$routes = build_routes($conflicts);
t_eq(true,   routes_valid($routes),                       'uretilen tablo gecerli');
t_eq([],     $conflicts,                                  'mevcut veride slug cakismasi yok');
t_eq(['en'], $routes['activeLangs'],                      'Faz 1 de yalnizca EN aktif');
t_eq('accountant', $routes['slugs']['en']['accountant'] ?? null, 'accountant tabloda');
t_eq('accountant', $routes['ids']['accountant']['en'] ?? null,   'id -> EN slug');
t_eq(['en'], $routes['published']['accountant'] ?? null,  'EN de yayinli');
t_eq('methodology', $routes['pages']['en']['methodology'] ?? null, 'sabit sayfa tabloda');

// --- load_routes: bozuk cache -> kaynaktan uretir, COKMEZ (spec 1.5) ---
$broken = $tmpDir . '/broken.json';
file_put_contents($broken, '{ bozuk json');
routes_cache_reset();
t_eq(true, routes_valid(load_routes($broken)), 'bozuk cache -> kaynaktan uretildi');

// Bozuk cache uzerine gecerli tablo YAZILMIS olmali.
routes_cache_reset();
t_eq(true, routes_valid(json_decode((string)file_get_contents($broken), true)),
     'bozuk cache duzeltilerek yazildi');

// --- load_routes: yazilamayan yol -> yine de calisir ---
routes_cache_reset();
t_eq(true, routes_valid(load_routes('/kesinlikle/olmayan/dizin/routes.json')),
     'yazilamayan cache -> bellekte uretildi');

// Temizlik
foreach (glob($tmpDir . '/*') ?: [] as $p) {
    is_dir($p) ? array_map('unlink', glob($p . '/*') ?: []) && rmdir($p) : unlink($p);
}
@rmdir($tmpDir);
routes_cache_reset();
```

- [ ] **Adım 3: Başarısız olduğunu doğrula**

Run: `php tests/run.php`
Beklenen: `Call to undefined function routes_valid()`

- [ ] **Adım 4: Uygulamayı yaz**

`inc/routes_cache.php`:
```php
<?php
/**
 * Route tablosunun uretimi ve onbellegi.
 * routes.json bir PERFORMANS optimizasyonudur, zorunlu deploy artefakti DEGILDIR:
 * yoksa, bossa, bozuksa ya da SEMANTIK olarak tutarsizsa tablo bellekte uretilir
 * ve site calismaya devam eder.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/routing.php';

/** Sabit sayfalarin dil basina slug'lari. Faz 1'de yalnizca EN dolu. */
const PAGE_SLUGS = [
    'en' => ['methodology' => 'methodology', 'landscape' => 'landscape',
             'changelog'   => 'changelog',   'sponsor'   => 'sponsor'],
    'tr' => [],
    'es' => [],
];

/** Gecici dosyaya yaz, sonra rename — yarim yazilmis dosya kimseye gorunmez. */
function atomic_write(string $file, string $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $data, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * SEMANTIK dogrulama. Yalnizca anahtar varligi yetmez: sekil olarak dogru ama
 * icerigi tutarsiz bir cache butun siteyi yanlis 404'e dusurur.
 */
function routes_valid(mixed $d): bool
{
    if (!is_array($d)) {
        return false;
    }
    foreach (['activeLangs', 'ids', 'slugs', 'published', 'pages', 'pageSlugs'] as $k) {
        if (!isset($d[$k]) || !is_array($d[$k])) {
            return false;
        }
    }
    if ($d['ids'] === []) {
        return false;
    }

    // activeLangs yalnizca bilinen dilleri tasir ve varsayilan dil aktif olmali.
    foreach ($d['activeLangs'] as $l) {
        if (!in_array($l, LANGS, true)) {
            return false;
        }
    }
    if (!in_array(DEFAULT_LANG, $d['activeLangs'], true)) {
        return false;
    }

    // Her published kimligi ids icinde bulunmali; yayinlanan her dilde canonical slug olmali.
    foreach ($d['published'] as $id => $langs) {
        if (!isset($d['ids'][$id]) || !is_array($langs)) {
            return false;
        }
        foreach ($langs as $l) {
            if (!in_array($l, LANGS, true)) {
                return false;
            }
            $slug = $d['ids'][$id][$l] ?? null;
            if (!is_string($slug) || $slug === '') {
                return false;
            }
        }
    }

    // slugs degerleri gecerli bir meslek kimligine gitmeli.
    foreach ($d['slugs'] as $lang => $map) {
        if (!in_array($lang, LANGS, true) || !is_array($map)) {
            return false;
        }
        foreach ($map as $id) {
            if (!isset($d['ids'][$id])) {
                return false;
            }
        }
    }

    // pages ve pageSlugs karsilikli olmali (slug -> anahtar -> ayni slug).
    foreach ($d['pages'] as $lang => $map) {
        if (!is_array($map)) {
            return false;
        }
        foreach ($map as $slug => $key) {
            if (($d['pageSlugs'][$lang][$key] ?? null) !== $slug) {
                return false;
            }
        }
    }
    foreach ($d['pageSlugs'] as $lang => $map) {
        if (!is_array($map)) {
            return false;
        }
        foreach ($map as $key => $slug) {
            if (($d['pages'][$lang][$slug] ?? null) !== $key) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Kaynak JSON'lardan route tablosu. Faz 1: veri hala duz dosya, yalnizca EN.
 * @param array|null $conflicts Slug cakismalari buraya yazilir (istek aninda
 *                              sessiz kalinir; build-index.php hata verir).
 */
function build_routes(?array &$conflicts = null): array
{
    $conflicts = [];
    $ids = $published = [];
    $slugs = ['en' => [], 'tr' => [], 'es' => []];

    $claim = static function (string $lang, string $slug, string $id) use (&$slugs, &$conflicts): void {
        if (in_array($slug, LANGS, true)) {
            $conflicts[] = "$lang: '$slug' rezerve dil kodu";
            return;
        }
        if (isset(PAGE_SLUGS[$lang][$slug]) || in_array($slug, PAGE_SLUGS[$lang], true)) {
            $conflicts[] = "$lang: '$slug' sabit sayfa slug'iyla cakisiyor";
            return;
        }
        if (isset($slugs[$lang][$slug]) && $slugs[$lang][$slug] !== $id) {
            $conflicts[] = "$lang: '$slug' hem '{$slugs[$lang][$slug]}' hem '$id' tarafindan isteniyor";
            return;
        }
        $slugs[$lang][$slug] = $id;
    };

    foreach (load_all_jobs() as $slug => $job) {
        $ids[$slug]       = ['en' => $slug];
        $published[$slug] = ['en'];
        $claim('en', (string)$slug, (string)$slug);
        foreach ((array)($job['formerSlugs'] ?? []) as $former) {
            if (valid_slug((string)$former)) {
                $claim('en', (string)$former, (string)$slug);
            }
        }
    }

    $pages = [];
    foreach (PAGE_SLUGS as $lang => $map) {
        $pages[$lang] = [];
        foreach ($map as $key => $slug) {
            $pages[$lang][$slug] = $key;      // slug -> anahtar
        }
    }

    return [
        'activeLangs' => ['en'],
        'ids'         => $ids,
        'slugs'       => $slugs,
        'published'   => $published,
        'pages'       => $pages,
        'pageSlugs'   => PAGE_SLUGS,          // anahtar -> slug
    ];
}

/** Test icin bellek onbellegini bosaltir. */
function routes_cache_reset(): void
{
    $GLOBALS['__routes'] = [];
}

/**
 * Once cache; gecersizse kaynaktan uret ve YAZABILIYORSAN yaz.
 * Yazamamak hata degildir — istek bellekteki tabloyla tamamlanir.
 * @param string|null $file Test icin enjekte edilebilir; null ise ROUTES_FILE.
 */
function load_routes(?string $file = null): array
{
    $file = $file ?? ROUTES_FILE;
    if (isset($GLOBALS['__routes'][$file]) && is_array($GLOBALS['__routes'][$file])) {
        return $GLOBALS['__routes'][$file];
    }

    if (is_file($file)) {
        $raw  = @file_get_contents($file);
        $data = $raw === false ? null : json_decode($raw, true);
        if (routes_valid($data)) {
            return $GLOBALS['__routes'][$file] = $data;
        }
    }

    $data = build_routes();
    atomic_write($file, (string)json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $GLOBALS['__routes'][$file] = $data;
}
```

- [ ] **Adım 5: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **24 assert** geçer, toplamda **`0 kaldi`**.

- [ ] **Adım 6: Gerçek cache dosyasına dokunulmadığını doğrula**

```bash
md5 cache/routes.json 2>/dev/null || echo "routes.json yok (beklenen)"
php tests/run.php > /dev/null
md5 cache/routes.json 2>/dev/null || echo "routes.json hala yok (beklenen)"
```
Beklenen: testler öncesi ve sonrası aynı durum. **Testler gerçek cache'i
değiştirmez.**

- [ ] **Adım 7: `build-index.php` route tablosunu da üretsin**

`tools/build-index.php` içinde `$cleared = clear_cache();` satırından **önce**:
```php
require_once __DIR__ . '/../inc/routes_cache.php';
$conflicts = null;
$routes    = build_routes($conflicts);
if ($conflicts !== []) {
    echo "HATA: slug cakismasi\n";
    foreach ($conflicts as $c) {
        echo "  x $c\n";
    }
    exit($cli ? 1 : 0);
}
$routesOk = atomic_write(
    ROUTES_FILE,
    (string)json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);
echo $routesOk
    ? "route tablosu -> cache/routes.json\n"
    : "UYARI: routes.json yazilamadi (istek aninda uretilecek)\n";
```

- [ ] **Adım 8: `.gitignore`'a üretilen dosyaları ekle**

`cache/index.json` satırının altına (spec §9):
```
cache/routes.json
cache/index-*.json
cache/content-version.json
```

- [ ] **Adım 9: Uçtan uca doğrula**

```bash
rm -f cache/routes.json
php tools/build-index.php
php -r 'require "inc/routes_cache.php"; echo count(load_routes()["ids"]), " meslek\n";'
git status --short cache/
```
Beklenen: `route tablosu -> cache/routes.json`, meslek sayısı, ve `git status`
cache'ten **hiçbir şey göstermez**.

- [ ] **Adım 10: Commit**

```bash
git add inc/routes_cache.php tests/routes_cache.test.php inc/config.php .gitignore tools/build-index.php
git commit -m "feat: build and cache the route table with atomic writes"
```

**Risk**
Orta. `build-index.php` değişiyor. Eklenen blok arama indeksinden **sonra** ve
`clear_cache()`'ten önce koşuyor; çakışma varsa açıkça hata verip duruyor, sessizce
bozuk tablo yazmıyor.

**Doğrulama komutu**
```bash
php tests/run.php && php tools/build-index.php && php tools/validate.php
```

**Beklenen sonuç**
Testlerde `0 kaldi`, `build-index` route satırını basar, `validate.php` "Hata yok"
der.

**Rollback sınırı**
`git revert` tek commit + `rm -f cache/routes.json`. Site hâlâ eski `.htaccess`
üzerinden çalışıyor; bu dosyaları henüz kimse okumuyor.

**Commit sınırı**
Beş dosya. `.htaccess` ve `router.php` **girmez**.

---

### Görev 1D — Front controller (bağlamadan)

**Amaç**
Çözüm sonucunu HTTP eylemine çeviren saf bir eşleyici ve onu uygulayan `route.php`
yazmak. **Bu commit üretim routing'ine bağlamaz** — bağlama Görev 1F'nin işidir.

**Değiştirilecek dosyalar**
- Oluştur: `inc/dispatch.php`
- Oluştur: `route.php`
- Oluştur: `unavailable.php`
- Oluştur: `tests/dispatch.test.php`

**Arayüzler**
- Tüketir: `resolve_path()` (1A), `load_routes()` (1C).
- Üretir: `dispatch_for(array $route): array` —
  `['status' => int, 'headers' => array<string,string>, 'include' => string|null,
  'get' => array<string,string>]`. Saf: hiçbir çıktı basmaz, header göndermez.

- [ ] **Adım 1: Başarısız testleri yaz**

`tests/dispatch.test.php`:
```php
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
```

- [ ] **Adım 2: Başarısız olduğunu doğrula**

Run: `php tests/run.php`
Beklenen: `Call to undefined function dispatch_for()`

- [ ] **Adım 3: Eşleyiciyi yaz**

`inc/dispatch.php`:
```php
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
```

- [ ] **Adım 4: Front controller'ı yaz**

`route.php`:
```php
<?php
/**
 * Front controller — sitenin TEK giris noktasi.
 * .htaccess (uretim) ve router.php (lokal) ikisi de buraya devreder;
 * URL kurali baska hicbir yerde yasamaz.
 */
declare(strict_types=1);

require_once __DIR__ . '/inc/routes_cache.php';
require_once __DIR__ . '/inc/dispatch.php';

$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$action = dispatch_for(resolve_path($path, load_routes()));

http_response_code($action['status']);
foreach ($action['headers'] as $name => $value) {
    header($name . ': ' . $value);
}

if ($action['include'] === null) {
    exit;
}

foreach ($action['get'] as $k => $v) {
    $_GET[$k] = $v;
}

require __DIR__ . '/' . $action['include'];
```

- [ ] **Adım 5: `unavailable.php` iskeletini yaz**

Faz 1'de hiçbir yol buraya düşmüyor (yalnızca EN aktif), ama `dispatch_for()`
çağırdığı için dosyanın var olması gerekiyor.

```php
<?php
/**
 * Bir entry'nin bu dilde henuz yayinlanmadigi durum.
 * 404 + noindex; canonical YAZILMAZ (spec 5.4). Ingilizceye SESSIZ yonlendirme yok.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$pageTitle = 'Not available in this language yet — ' . SITE_NAME;
$pageDesc  = 'This entry has not been published in this language yet.';
require __DIR__ . '/inc/header.php';
?>
<div class="wrap">
  <h1>Not available in this language yet</h1>
  <p class="prose">This entry exists, but it has not been written in this language.
  Read it in English, or open a PR to add the translation.</p>
  <p class="prose"><a href="/">All jobs</a></p>
</div>
<?php require __DIR__ . '/inc/footer.php';
```

- [ ] **Adım 6: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **11 assert** geçer, toplamda **`0 kaldi`**.

- [ ] **Adım 7: Front controller'ın hâlâ BAĞLI OLMADIĞINI doğrula**

```bash
grep -n "route\.php" .htaccess router.php; echo "eslesme cikisi: $?"
```
Beklenen: hiçbir çıktı, `eslesme cikisi: 1`. **Bu adım global kısıtın kanıtıdır.**

- [ ] **Adım 8: Commit**

```bash
git add inc/dispatch.php route.php unavailable.php tests/dispatch.test.php
git commit -m "feat: add front controller, not yet wired to routing"
```

**Risk**
Düşük — `route.php` var ama hiçbir istek oraya ulaşmıyor.

**Doğrulama komutu**
```bash
php tests/run.php && grep -c "route\.php" .htaccess router.php
```

**Beklenen sonuç**
`0 kaldi`; grep her iki dosya için `0` sayar.

**Rollback sınırı**
`git revert` tek commit. Hiçbir istek yolu değişmediği için etkisi sıfırdır.

**Commit sınırı**
Dört dosya. **`.htaccess` ve `router.php` bu commit'e kesinlikle girmez.**

---

### Görev 1E — Genişletilmiş güvenlik fixture'ları

**Amaç**
`path_is_forbidden()` fixture'ları Görev 1A'da yeşile alındı. Bu görev, **routes
tablosu ve dispatcher birlikte devredeyken** güvenlik davranışının bozulmadığını
sabitler — ve bunu bağlantıdan **önce** yapar.

**Değiştirilecek dosyalar**
- Oluştur: `tests/security.test.php`

**Arayüzler**
- Tüketir: `resolve_path()` (1A), `dispatch_for()` (1D), `load_routes()` (1C).

- [ ] **Adım 1: Testleri yaz**

`tests/security.test.php`:
```php
<?php
/**
 * Guvenlik sinirinin UCTAN UCA davranisi: cozumleyici + dispatcher birlikte.
 * Bu dosya, .htaccess/router.php gecisinden ONCEKI commit'te yesil olmak zorunda.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/dispatch.php';
require_once __DIR__ . '/../inc/routes_cache.php';

$live = load_routes();   // gercek tablo, salt okunur

$forbidden = [
    '/data/jobs/accountant.json',
    '/inc/config.local.php',
    '/cache/routes.json',
    '/docs/architecture/2026-08-15-cok-dilli-mimari.md',
    '/research/sources.json',
    '/tests/security.test.php',
    '/.git/config',
    '/README.md',
];

foreach ($forbidden as $p) {
    $action = dispatch_for(resolve_path($p, $live));
    // 403 DEGIL 404: 403 "burada bir sey var" bilgisi sizdirir.
    t_eq(404,       $action['status'],  "uctan uca 404: $p");
    t_eq('404.php', $action['include'], "404 sablonu: $p");
    // Yasak yol, cozumleyiciden slug/id TASIMADAN cikmali.
    t_eq(['lang' => 'en'], $action['get'], "yasak yol slug/id tasimaz: $p");
}

// Yasak yol ASLA yonlendirmeye donusmez — acik yonlendirme yuzeyine kapali.
foreach ($forbidden as $p) {
    t_eq(null, dispatch_for(resolve_path($p, $live))['headers']['Location'] ?? null,
         "yasak yol yonlendirmez: $p");
}

// .well-known cozumleyici tarafindan KAPATILMAZ; varsa gercek dosya servis edilir,
// yoksa normal 404 doner (ikisi de dogru davranis).
t_eq(false, path_is_forbidden('/.well-known/security.txt'), '.well-known kapatilmaz');
t_eq(false, path_is_forbidden('/.well-known/acme-challenge/abc123'), 'acme challenge kapatilmaz');

// tools/ BUILD_KEY ile korunuyor, cozumleyici tarafindan kapatilmiyor (mevcut karar).
t_eq(false, path_is_forbidden('/tools/build-index.php'), 'tools kapatilmaz');
```

- [ ] **Adım 2: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **35 assert** geçer, toplamda **`0 kaldi`**.

- [ ] **Adım 3: Commit**

```bash
git add tests/security.test.php
git commit -m "test: pin the end-to-end security boundary"
```

**Risk**
Çok düşük — yalnızca test eklemesi. Bir fixture kırmızıysa bu **gerçek bir güvenlik
bulgusudur** ve düzeltilene kadar Görev 1F'ye geçilmez.

**Doğrulama komutu**
```bash
php tests/run.php
```

**Beklenen sonuç**
`0 kaldi`.

**Rollback sınırı**
`git revert` tek commit; yalnızca test dosyası.

**Commit sınırı**
Tek dosya.

---

### Görev 1F — `.htaccess` ve `router.php` geçişi

**Amaç**
İki giriş yolunu front controller'a bağlamak ve URL kurallarını o iki dosyadan
tamamen kaldırmak. **Planın tek davranış değiştiren commit'i budur.**

**Ön koşul:** Görev 1A ve 1E'nin güvenlik fixture'ları yeşil olmadan bu göreve
başlanmaz.

**Değiştirilecek dosyalar**
- Değiştir: `router.php` (tamamen yeniden yazılır)
- Değiştir: `.htaccess` (mod_rewrite bloğu + `RedirectMatch` listesi)

**Uygulama ayrıntısı**

`router.php` — yeni tam içerik:
```php
<?php
// Sadece LOKAL gelistirme icin: php -S localhost:8000 router.php
// Kural ICERMEZ — route.php'ye devreder. Uretimde .htaccess ayni seyi yapar.
declare(strict_types=1);

require_once __DIR__ . '/inc/routing.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Guvenlik GERCEK DOSYA KONTROLUNDEN ONCE (spec 1.8).
if (path_is_forbidden($path)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return true;
}

// Gercek dosya varsa built-in server servis etsin (assets, fonts, .well-known).
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/route.php';
return true;
```

`.htaccess` — mod_rewrite bloğu tamamen değişir. **`Options`, `DirectoryIndex`,
`mod_expires`, `mod_headers` ve `ErrorDocument` blokları DOKUNULMADAN kalır.**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # www -> apex, tek kanonik host.
    RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
    RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301]

    # GUVENLIK, gercek dosya kontrolunden ONCE (spec 1.8).
    # Ters sirada data/jobs/*.json gercek dosya oldugu icin ham servis edilir.
    RewriteRule ^(data|inc|cache|docs|research|tests)(/|$) - [R=404,L]
    RewriteRule (^|/)\.(?!well-known(/|$)) - [R=404,L]
    RewriteRule \.(md|example)$ - [R=404,L]

    # tools/ BILEREK acik: shared hosting'de SSH olmayabiliyor, build'i
    # tarayicidan tetiklemek gerekiyor. Korumasi BUILD_KEY (build_key_ok()).
    RewriteRule ^tools/ - [L]

    # Gercek dosya/klasor varsa dokunma (assets, fonts, .well-known).
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Kalan her sey tek giris noktasina. URL kurali route.php'de yasar.
    RewriteRule ^ route.php [L,QSA]
</IfModule>
```

Ayrıca mevcut `RedirectMatch` satırı `tests` klasörünü de kapsayacak şekilde
genişletilir (satır zaten var, yalnızca liste büyür):

```apache
RedirectMatch 404 ^/(data|inc|cache|docs|research|tests)(/|$)
```

> **Uygulayıcıya not:** `RedirectMatch` satırları `.htaccess`'te **kalır**.
> mod_rewrite kuralları onları tekrarlıyor gibi görünse de ikisi farklı modüllerde;
> kuşak-kemer koruması bilinçli: mod_rewrite devre dışıysa `RedirectMatch` hâlâ
> koruyor.

- [ ] **Adım 1: Tam test setini çalıştır (bağlamadan ÖNCE)**

```bash
php tests/run.php && php tools/validate.php && php tools/doctor.php
```
Beklenen: `0 kaldi`, `Hata yok`, `Kritik hata yok`. **Üçü de temiz değilse
bağlanmaz.**

- [ ] **Adım 2: `router.php` ve `.htaccess`'i yaz**

- [ ] **Adım 3: Lokal davranışı doğrula**

```bash
php -S 127.0.0.1:8000 router.php > /dev/null 2>&1 &
SRV=$!; sleep 1
for u in / /accountant /accountant/ /en/accountant /methodology /unknown \
         /data/jobs/accountant.json /inc/config.php /README.md /assets/style.css; do
  printf '%-32s %s\n' "$u" "$(curl -so /dev/null -w '%{http_code} %{redirect_url}' 127.0.0.1:8000$u)"
done
kill $SRV
```

Beklenen:
```
/                                200
/accountant                      200
/accountant/                     301 http://127.0.0.1:8000/accountant
/en/accountant                   301 http://127.0.0.1:8000/accountant
/methodology                     200
/unknown                         404
/data/jobs/accountant.json       404
/inc/config.php                  404
/README.md                       404
/assets/style.css                200
```

- [ ] **Adım 4: Commit ve hash'i kaydet**

```bash
git add .htaccess router.php
git commit -m "refactor: route all requests through the front controller"
git rev-parse HEAD    # ---> BU HASH'I KAYDET, rollback icin gerekli
```

Hash: `WIRING_COMMIT = 2f2b4c8b902556600b0f380360058fa2cc8b7d60`
(kaydedildi 15 Ağustos 2026 — `git revert` ve `git restore --source=` bu hash'e dayanır)

**Risk**
**Planın en yüksek riskli adımı.** `.htaccess` hatası tüm siteyi 500'e düşürür ve
etkisi anında canlıdır. Azaltıcılar: (a) mod_rewrite bloğu dışındaki her şey
dokunulmuyor, (b) güvenlik fixture'ları bu commit'ten önce yeşil, (c) rollback tek
komut, (d) Görev 1G hemen ardından koşuyor.

**Doğrulama komutu**
Adım 3'teki döngü, ve ardından Görev 1G.

**Beklenen sonuç**
Adım 3'teki tablo birebir.

**Rollback sınırı**
Branch durumundan **bağımsız**, kaydedilmiş hash'e dayanır:

```bash
# Tercih edilen: commit'i tersine cevir
git revert <WIRING_COMMIT>

# Acil dosya geri yukleme (commit olusturmadan):
git restore --source=<WIRING_COMMIT>^ -- .htaccess router.php
```

`HEAD~1` **kullanılmaz** — bağlantı commit'i HEAD'in hemen arkasında olmadığında
yanlış dosyaları getirir. `route.php`, `inc/routing.php` ve testler yerinde kalır
ama kimse onları çağırmaz; Görev 1A–1E'nin ayrı commit'lerde olmasının sebebi budur.

**Commit sınırı**
Yalnızca `.htaccess` ve `router.php`. Rollback'in iki dosyayla sınırlı olmasının
şartı budur.

---

### Görev 1G — HTTP smoke matrisi

**Amaç**
Birim testleri `resolve_path()`'i doğruluyor; bu görev **gerçek HTTP** üzerinden iki
giriş yolunu da doğruluyor. Front controller'a geçmenin gerekçesi lokal/üretim
farkını kapatmaksa, testin bunu ölçmesi gerekiyor (spec §12.1).

**Değiştirilecek dosyalar**
- Oluştur: `tools/smoke.php`
- Oluştur: `tools/smoke.sh`

**Arayüzler**
- Tüketir: çalışan bir HTTP sunucusu; `curl` uzantısı.
- Üretir: CLI araçları. Hata varsa çıkış kodu 1.

**Uygulama ayrıntısı**

`tools/smoke.sh` — sunucu ömrünü **PID ile** yönetir. `%1` iş kontrolü etkileşimli
olmayan kabukta güvenilir değildir, bu yüzden kullanılmaz.

```bash
#!/usr/bin/env bash
# HTTP smoke: sunucuyu ayaga kaldirir, hazir olmasini bekler, matrisi kosar, temizler.
#   ./tools/smoke.sh
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${SMOKE_PORT:-8123}"
BASE="http://127.0.0.1:${PORT}"
LOG="$(mktemp -t wais-smoke.XXXXXX)"
PROBE="${ROOT}/.well-known/smoke-probe.txt"
PROBE_DIR_CREATED=0

cleanup() {
  [ -n "${SRV:-}" ] && kill "$SRV" 2>/dev/null
  rm -f "$PROBE" "$LOG"
  [ "$PROBE_DIR_CREATED" = "1" ] && rmdir "${ROOT}/.well-known" 2>/dev/null
  return 0
}
trap cleanup EXIT INT TERM

# .well-known fixture: gercek dosya servis ediliyor mu (guvenlik kurali onu kapatmamali).
if [ ! -d "${ROOT}/.well-known" ]; then
  mkdir -p "${ROOT}/.well-known" && PROBE_DIR_CREATED=1
fi
echo "smoke-probe" > "$PROBE"

php -S "127.0.0.1:${PORT}" -t "$ROOT" "${ROOT}/router.php" > "$LOG" 2>&1 &
SRV=$!

# Hazir olma kontrolu: sabit sleep degil, gercek yoklama.
READY=0
for _ in $(seq 1 60); do
  if curl -sf -o /dev/null "${BASE}/"; then READY=1; break; fi
  if ! kill -0 "$SRV" 2>/dev/null; then break; fi
  sleep 0.25
done

if [ "$READY" != "1" ]; then
  echo "HATA: sunucu ${PORT} portunda ayaga kalkmadi"
  echo "--- sunucu logu ---"
  cat "$LOG"
  exit 1
fi

php "${ROOT}/tools/smoke.php" "$BASE"
```

`tools/smoke.php`:
```php
<?php
/**
 * HTTP smoke matrisi. Calisan bir sunucuya karsi kosar:
 *   ./tools/smoke.sh                          (onerilen — sunucu omrunu yonetir)
 *   php tools/smoke.php https://willaistealit.com
 * Kontrol: status, Location, yonlendirme ADEDI, canonical'in TAM degeri,
 * <html lang>, ve 404 govdesinde icerik sizintisi olmamasi.
 */
declare(strict_types=1);

if (!function_exists('curl_init')) {
    fwrite(STDERR, "HATA: curl uzantisi yok. php tools/doctor.php ile kontrol et.\n");
    exit(1);
}

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8123', '/');

/** Tek istek — yonlendirmeyi TAKIP ETMEZ; zincir uzunlugunu olcebilmek icin. */
function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hlen = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($raw === false) {
        return ['status' => 0, 'location' => null, 'canonical' => null,
                'lang' => null, 'body' => '', 'error' => $err];
    }

    $raw  = (string)$raw;
    $head = substr($raw, 0, $hlen);
    $body = substr($raw, $hlen);

    preg_match('/^Location:\s*(.+)$/mi', $head, $m);
    preg_match('#<link rel="canonical" href="([^"]+)"#i', $body, $c);
    preg_match('#<html lang="([a-z-]+)"#i', $body, $l);

    return ['status' => $code, 'location' => isset($m[1]) ? trim($m[1]) : null,
            'canonical' => $c[1] ?? null, 'lang' => $l[1] ?? null,
            'body' => $body, 'error' => ''];
}

/** Yonlendirme zincirini takip eder, adet dondurur. */
function hops(string $base, string $path, int $max = 5): int
{
    $n = 0;
    $url = $base . $path;
    while ($n < $max) {
        $r = fetch($url);
        if ($r['location'] === null) {
            return $n;
        }
        $n++;
        $url = str_starts_with($r['location'], 'http') ? $r['location'] : $base . $r['location'];
    }
    return $max;
}

/* [yol, status, hedef yol|null, lang|null, canonical|null] */
$matrix = [
    ['/',                          200, null,                  'en',  'https://willaistealit.com/'],
    ['/en',                        301, '/',                   null,  null],
    ['/en/',                       301, '/',                   null,  null],
    ['/accountant',                200, null,                  'en',  'https://willaistealit.com/accountant'],
    ['/accountant/',               301, '/accountant',         null,  null],
    ['/en/accountant',             301, '/accountant',         null,  null],
    ['/en/accountant/',            301, '/accountant',         null,  null],
    ['/methodology',               200, null,                  'en',  null],
    ['/en/not-a-real-job',         404, null,                  null,  null],
    ['/unknown',                   404, null,                  null,  null],
    ['/sitemap.xml',               200, null,                  null,  null],
    ['/llms.txt',                  200, null,                  null,  null],
    ['/og/accountant.png',         200, null,                  null,  null],
    ['/og/home.png',               200, null,                  null,  null],
    ['/og/tr/accountant.png',      404, null,                  null,  null],
    // Guvenlik (spec 1.8)
    ['/data/jobs/accountant.json', 404, null,                  null,  null],
    ['/inc/config.php',            404, null,                  null,  null],
    ['/cache/index.json',          404, null,                  null,  null],
    ['/research/sources.json',     404, null,                  null,  null],
    ['/tests/run.php',             404, null,                  null,  null],
    ['/.git/config',               404, null,                  null,  null],
    ['/README.md',                 404, null,                  null,  null],
    // Acik kalmasi gerekenler
    ['/assets/style.css',          200, null,                  null,  null],
    ['/.well-known/smoke-probe.txt', 200, null,                null,  null],
];

/** 404 govdesi hassas icerik sizdirmamali. */
$leaks = ['adaptPrompt', 'BUILD_KEY', 'config.local', '<?php', 'resistanceTags'];

$fail = 0;
foreach ($matrix as [$path, $status, $target, $lang, $canonical]) {
    $r    = fetch($base . $path);
    $ok   = true;
    $note = '';

    if ($r['error'] !== '') {
        $ok = false;
        $note .= ' [curl: ' . $r['error'] . ']';
    }
    if ($r['status'] !== $status) {
        $ok = false;
        $note .= sprintf(' [status: %d, beklenen: %d]', $r['status'], $status);
    }
    if ($status === 301) {
        $reached = parse_url((string)$r['location'], PHP_URL_PATH) ?: $r['location'];
        if ($reached !== $target) {
            $ok = false;
            $note .= " [hedef: $reached, beklenen: $target]";
        }
        $n = hops($base, $path);
        if ($n !== 1) {
            $ok = false;
            $note .= " [zincir: $n adim, 1 olmali]";
        }
    }
    if ($lang !== null && $r['lang'] !== $lang) {
        $ok = false;
        $note .= sprintf(' [lang: %s, beklenen: %s]', (string)$r['lang'], $lang);
    }
    if ($canonical !== null && $r['canonical'] !== $canonical) {
        $ok = false;
        $note .= sprintf(' [canonical: %s, beklenen: %s]', (string)$r['canonical'], $canonical);
    }
    if ($status === 404) {
        foreach ($leaks as $needle) {
            if (str_contains($r['body'], $needle)) {
                $ok = false;
                $note .= " [SIZINTI: govdede '$needle' var]";
            }
        }
    }

    printf("%-32s %s %d%s\n", $path, $ok ? 'ok  ' : 'HATA', $r['status'], $note);
    if (!$ok) {
        $fail++;
    }
}

echo "\n" . ($fail === 0 ? "Matris temiz.\n" : "$fail satir basarisiz.\n");
exit($fail === 0 ? 0 : 1);
```

- [ ] **Adım 1: İki aracı da yaz ve `smoke.sh`'i çalıştırılabilir yap**

```bash
chmod +x tools/smoke.sh
```

- [ ] **Adım 2: Matrisi çalıştır**

Run: `./tools/smoke.sh; echo "cikis: $?"`
Beklenen: 24 satırın hepsi `ok`, `Matris temiz.`, `cikis: 0`.

Faz 1'in gerçek kanıtı olan satırlar:
```
/en/accountant                   ok   301
/en/not-a-real-job               ok   404
/data/jobs/accountant.json       ok   404
/og/tr/accountant.png            ok   404
/.well-known/smoke-probe.txt     ok   200
```

- [ ] **Adım 3: Sonda dosyasının temizlendiğini doğrula**

Run: `git status --short && ls .well-known 2>/dev/null`
Beklenen: `.well-known/smoke-probe.txt` **yok**, `git status` temiz.

- [ ] **Adım 4: Commit**

```bash
git add tools/smoke.php tools/smoke.sh
git commit -m "test: add HTTP smoke matrix for both entry paths"
```

**Risk**
Düşük — salt okunur teşhis. Tek yan etki `.well-known/smoke-probe.txt` sondası;
`trap cleanup EXIT INT TERM` her çıkış yolunda siliyor ve dizini yalnızca kendisi
oluşturduysa kaldırıyor.

**Doğrulama komutu**
```bash
./tools/smoke.sh && git status --short
```

**Beklenen sonuç**
`Matris temiz.` ve ardından boş `git status`.

**Rollback sınırı**
`rm tools/smoke.php tools/smoke.sh`. Site kodu etkilenmiyor.

**Commit sınırı**
İki dosya.

---

## Commit haritası

**11 commit.** Bağımsız olarak **geri alınabilirler**, ama **uygulama sırası
bakımından birbirlerine bağımlıdırlar** — sözleşmeler zincirleme: koşucu →
çözümleyici → route cache → dispatcher → bağlantı.

| # | Commit | Görev | Dosyalar | Site etkisi |
|---|---|---|---|---|
| 1 | `test: add lightweight PHP test harness` | 0A | `tests/{lib,run,harness.test}.php` | — |
| 2 | `tools: add environment doctor` | 0B | `tools/doctor.php` | — |
| 3 | `tools: verify font glyph coverage from cmap table` | 0C | `inc/ttf.php`, `tests/ttf.test.php`, `tools/doctor.php` | — |
| 4 | `tools: generate EN/TR/ES sample OG canvases for visual review` | 0D | `tools/og-samples.php`, `.gitignore` | — |
| 5 | `feat: add pure URL resolver with security fixtures` | 1A | `inc/routing.php`, `tests/routing.test.php` | — |
| 6 | `feat: add url_for and alternates_for helpers` | 1B | `inc/urls.php`, testi | — |
| 7 | `feat: build and cache the route table with atomic writes` | 1C | `inc/routes_cache.php`, `inc/config.php`, `.gitignore`, `tools/build-index.php`, testi | — |
| 8 | `feat: add front controller, not yet wired to routing` | 1D | `route.php`, `inc/dispatch.php`, `unavailable.php`, testi | — |
| 9 | `test: pin the end-to-end security boundary` | 1E | `tests/security.test.php` | — |
| 10 | **`refactor: route all requests through the front controller`** | 1F | `.htaccess`, `router.php` | **Tüm trafik** |
| 11 | `test: add HTTP smoke matrix for both entry paths` | 1G | `tools/smoke.php`, `tools/smoke.sh` | — |

Sıradaki iki kural haritanın anlamını taşır:

- **Yalnızca 10 numaralı commit davranış değiştirir.** Rollback'i
  `git revert <WIRING_COMMIT>` veya
  `git restore --source=<WIRING_COMMIT>^ -- .htaccess router.php`.
- **Güvenlik, açılmadan önce test edilmiştir:** commit 5 `path_is_forbidden()`'ın
  tam fixture setini, commit 9 uçtan uca davranışı yeşile alır. İkisi de commit
  10'dan öncedir.

## Faz 1 kapanış kontrolü

```bash
php tests/run.php                    # 0 kaldi (cikis kodu 0)
php tools/validate.php               # Hata yok
php tools/build-index.php            # route tablosu + arama indeksi
php tools/doctor.php                 # lokal modda: Kritik hata yok
./tools/smoke.sh                     # Matris temiz
git status --short                   # cache/ ve .well-known/ temiz
```

Altısı da temizse Faz 2'ye (veri şeması + migration) geçilebilir.

## Faz 1'in DEĞİŞTİRMEDİKLERİ

Kapsam sınırını netleştirmek için:

- `job.php`, `index.php`, `inc/header.php`, `inc/footer.php` — dokunulmadı. URL'ler
  hâlâ elle basılıyor; `url_for()`'a geçiş **Faz 3**.
- `inc/ogcard.php` — dokunulmadı. Üretim renderer'ı olduğu gibi duruyor.
- `data/jobs/*.json` — düz dosya. Dizin şeması **Faz 2**.
- `inc/config.php`'deki `VERDICTS`/`CATEGORIES` — İngilizce etiketleriyle duruyor.
  Locale'e ayrışma **Faz 3**.
- `sitemap.php`, `llms.php` — tek dilli çıktı. Çok dilli hale gelmeleri **Faz 4**.
- TR/ES içeriği — yok. `activeLangs` yalnızca `['en']`.

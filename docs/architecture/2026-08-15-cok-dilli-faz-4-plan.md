# Faz 4 — TR Açılışı: Uygulama Planı

> **Uygulayıcı için:** Bu plan görev görev uygulanır. Her görev kendi testini ve
> kendi commit'ini taşır. Bir görevin kabul kriteri kırmızıysa **sonrakine geçilmez**.

**Hedef:** Türkçeyi eksiksiz bir dil olarak yayına almak — altyapı, içerik ve
aktivasyon; İspanyolcayı bekletmeden.

**Mimari:** Faz 4 üç bölüme ayrılır ve bu sıra bir tercih değil, bir kısıttır.
**4A** dil-farkında altyapıyı kurar (`activeLangs` hâlâ `['en']` — TR URL'leri
kapalı, yani altyapı canlı sitede kimseyi etkilemeden test edilir). **4B** Türkçe
içeriği üretir (69 editoryal anahtar + 15 eksik entry). **4C** `activeLangs`'e
`'tr'` ekler ve lansman kontrollerini koşar. ES için aynı üçlü Faz 5'te tekrarlanır;
4A bir kez yapılır ve ES'ye bedavaya gelir.

**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md`
**Faz 3 kapanışı:** `docs/architecture/2026-08-15-cok-dilli-faz-3-kapanis.md`

---

## Devralınan durum (Faz 4 girişi)

```
909 test gecti, 0 kaldi           golden 15/15 semantik · 10/15 byte
validator: Hata yok               activeLangs: ['en']
17 entry                          tr.json/es.json olan: 2 (cashier, administrative-assistant)
bekleyen editoryal: tr 69 · es 69  (methodology 43 + llms 26)
PAGE_SLUGS['tr'] ve ['es']: BOS
```

Hazır olanlar (Faz 1–3'ten): `url_for()` / `path_for()` / `alternates_for()`,
`resolve_path()` ve `resolve_og()`, `routes.json` üretimi ve `published` hesabı,
`Lang` sınıfları (En/Tr/Es), `data/locale/{en,tr,es}.php`, iki katmanlı golden,
`smoke.sh`, `validate.php`, `doctor.php`.

Eksik olanlar (Faz 4'ün kod işi): `<html lang>` sabit `"en"`, hreflang blokları yok,
`og:locale` yok, dil seçici yok, sitemap tek dilli ve `filemtime` kullanıyor,
`og.php` dil bilmiyor, arama `mb_strtolower` ile katlıyor (Türkçe `İ` tuzağı açık),
tek `cache/index.json`, `content-version.json` yok.

---

## Global kısıtlar

Her görevin gereksinimleri bu bölümü **kapsar**. Tekrar yazılmaz, ama uygulanır.

1. **`activeLangs` 4C'ye kadar `['en']`.** `inc/routes_cache.php:181`. 4A ve 4B
   boyunca `/tr/*` 404 döner. Bu satır tek başına bir görevdir (4C1) ve o göreve
   kadar kimse dokunmaz.
2. **Faz 4, EN çıktısını bilinçli olarak değiştirir.** Faz 1–3'ün "çıktı aynı
   kalmalı" kuralı burada geçerli değildir. Yerine **golden protokolü** geçer
   (aşağıda). Golden asla "yeşil olsun diye" yakalanmaz.
3. **Editoryal anahtarlar makine çevirisiyle doldurulmaz.** `methodology.` ve
   `llms.` ön ekleri (`data/locale/editorial.php`). Bir dil, `locale_pending()`
   sıfırlanmadan `activeLangs`'e eklenmez — validator bunu zaten kapıda tutuyor.
4. **`git add -A` kullanılmaz.** Her commit öncesi `git diff --cached --name-status`
   beklenen dosya listesiyle karşılaştırılır.
5. **Üretilmiş dosyalar zorunlu deploy artefaktı değildir.** `routes.json`,
   `index-<lang>.json`, `content-version.json`: yoksa bellekte üretilir, yazılamıyorsa
   istek yine tamamlanır, bozuksa yeniden üretilir. Yazım **atomiktir**
   (`atomic_write()`, `inc/routes_cache.php:31`).
6. **Her görev sonunda** `php tests/run.php` → `0 kaldi` ve `php tools/validate.php`
   → `Hata yok`. Kırmızıysa commit atılmaz.

### Golden protokolü

Faz 4'te çıktı değişir, dolayısıyla golden yeniden yakalanır. Yakalama **bilinçli**
olmak zorundadır — sırası budur:

```bash
./tools/golden.sh --check --semantic       # 1. NE degisti? (yakalamadan ONCE oku)
# 2. Cikan alan listesini gorevin "beklenen golden farki" satiriyla karsilastir.
#    Beklenmeyen bir alan varsa DUR — kod yanlis, golden degil.
./tools/golden.sh --capture                # 3. yakala
git diff --stat -- tests/golden            # 4. hangi dosyalar
git diff -- tests/golden | grep '^[-+]' | grep -v '^[-+][-+]' | head -40
```

Byte katmanı Faz 4'te **kapı değildir**, bilgilendiricidir: beş entry sayfasındaki
whitespace farkı bilinen ve kabul edilmiş farktır (Faz 3 kapanış notu). Regresyon
ölçütü `--check --semantic` → **15/15, çıkış 0**.

---

# Bölüm 4A — Dil-farkında altyapı

`activeLangs` bu bölümün tamamında `['en']`. TR URL'leri kapalı olduğu için altyapı
canlı siteyi etkilemeden kurulur ve test edilir. Bölümün sonunda TR sayfaları
**üretilebilir** ama **servis edilmez**.

---

### Görev 4A1 — `<html lang>`, hreflang ve `og:locale`

**Dosyalar**
- Değiştir: `inc/header.php:18` (`<html lang="en">`), `:28-35` (meta bloğu)
- Değiştir: `index.php`, `job.php`, `methodology.php`, `changelog.php`,
  `landscape.php`, `sponsor.php` — her biri `$pageAlternates` kurar
- Değiştir: `data/locale/{en,tr,es}.php` — `site.ogLocale`
- Değiştir: `tests/lang.test.php`

**Ürettiği arayüz:** şablonlar `$pageAlternates` (array<string,string>) kurar;
header basar. `404.php` ve `unavailable.php` **kurmaz** (spec 5.4).

- [ ] **Adım 1: `site.ogLocale` üç tabloya**

```php
// data/locale/en.php
'site.ogLocale' => 'en_US',
// tr.php → 'tr_TR'   es.php → 'es_ES'
```

- [ ] **Adım 2: Testi yaz (önce kırmızı)**

`tests/lang.test.php` sonuna:

```php
foreach (['en' => 'en_US', 'tr' => 'tr_TR', 'es' => 'es_ES'] as $code => $expected) {
    t_eq($expected, lang_for($code)->t('site.ogLocale'), "$code: og:locale");
}
```

- [ ] **Adım 3: `php tests/run.php`** → 3 yeni test geçer (Adım 1 zaten yazdı).
      Adım 1'i atlayıp önce testi koşarsan `site.ogLocale` metnini döndürür ve kalır;
      bu, `Lang::t()`'nin eksik anahtarda anahtarın kendisini bastığının kanıtıdır.

- [ ] **Adım 4: header'ı dil-farkında yap**

```php
<html lang="<?= h($lang) ?>">
```

`og:type` satırından sonra:

```php
<meta property="og:locale" content="<?= h($L->t('site.ogLocale')) ?>">
```

`canonical` bloğundan sonra:

```php
<?php foreach (($pageAlternates ?? []) as $code => $href): ?>
<link rel="alternate" hreflang="<?= h($code) ?>" href="<?= h($href) ?>">
<?php endforeach; ?>
```

- [ ] **Adım 5: her şablon kendi kümesini kursun**

```php
// job.php  — $id entry kimligi
$pageAlternates = alternates_for('job', $id, $routes);
// index.php
$pageAlternates = alternates_for('home', '', $routes);
// methodology.php / changelog.php / landscape.php / sponsor.php
$pageAlternates = alternates_for('page', 'methodology', $routes);   // anahtar sayfaya gore
```

`alternates_for()` `'home'` tipini bilmiyor — `inc/urls.php:45`'te `$type === 'page'`
kontrolü `'home'` için `pageSlugs`'a bakıyor ve boş döndürür. Ekle:

```php
        if ($type === 'home') {
            $out[$lang] = url_for($lang, 'home', '', $routes);
            continue;
        }
```

döngünün başına, `$type === 'job'` kontrolünden önce.

- [ ] **Adım 6: `alternates_for('home')` testi**

`tests/urls.test.php` sonuna:

```php
t_eq(['en' => 'https://willaistealit.com/', 'tr' => 'https://willaistealit.com/tr/',
      'x-default' => 'https://willaistealit.com/'],
     alternates_for('home', '', $U), 'ana sayfa alternates');
```

- [ ] **Adım 7: golden protokolü**

**Beklenen golden farkı:** hiçbir semantik alan değişmemeli. Sebebi: `activeLangs`
`['en']` olduğu için `alternates_for()` yalnızca `en` + `x-default` döndürür ve
ikisinin de `href`'i sayfanın kendi canonical'ı — `links` kümesinde **zaten var**.
`htmlLang` `"en"` kalır. `og:locale` golden alan setinde değil.

`--check --semantic` **15/15 kalmalı ve hiçbir dosya değişmemeli**. Değiştiyse
`alternates_for()` yayınlanmamış bir dili sızdırıyor demektir — dur.

- [ ] **Adım 8: 404 kontrolü**

```bash
php -S localhost:8000 router.php &
curl -s localhost:8000/unknown | grep -c 'rel="alternate"'    # 0 olmali
curl -s localhost:8000/ | grep -c 'rel="alternate"'           # 2 olmali (en + x-default)
```

- [ ] **Adım 9: Commit**

```bash
git add inc/header.php inc/urls.php index.php job.php methodology.php \
        changelog.php landscape.php sponsor.php \
        data/locale/en.php data/locale/tr.php data/locale/es.php \
        tests/lang.test.php tests/urls.test.php
git commit -m "feat: emit hreflang, og:locale and a language-aware html lang"
```

---

### Görev 4A2 — Sitemap: `xhtml:link` alternatifleri ve dürüst `lastmod`

Bugünkü `sitemap.php` iki spec kuralını çiğniyor: alternatif dil kümesi yok
(§5.2) ve sabit sayfaların `lastmod`'u `filemtime()`'dan geliyor — yani şablonu
düzeltmek tarihi oynatıyor. Spec bunu açıkça yasaklıyor: *"Build, cache temizliği,
şablon düzenlemesi veya biçimlendirme değişikliği `lastmod`'u hareket ettirmez."*

**Dosyalar**
- Oluştur: `data/page-reviewed.json`
- Değiştir: `sitemap.php`, `inc/entry.php`
- Değiştir: `tools/validate.php`
- Test: `tests/entry.test.php`

**Ürettiği arayüz:** `entry_lastmod(string $id, string $lang): string` — `YYYY-MM-DD`.

- [ ] **Adım 1: `data/page-reviewed.json`**

Sabit sayfaların anlamlı içerik tarihi. Şablon düzenlemesi bu dosyayı değiştirmez;
metin değişikliği değiştirir. Elle güncellenir.

```json
{
    "methodology": "2026-08-15",
    "landscape":   "2026-08-15",
    "changelog":   "2026-08-15",
    "sponsor":     "2026-08-15"
}
```

- [ ] **Adım 2: `entry_lastmod()` testini yaz**

`tests/entry.test.php` sonuna:

```php
// lastmod = max(assessmentReviewed, translationReviewed) — spec 5.2
t_eq('2026-08-01', entry_lastmod_from(['assessmentReviewed' => '2026-07-01',
                                       'translationReviewed' => '2026-08-01']), 'ceviri daha yeni');
t_eq('2026-09-01', entry_lastmod_from(['assessmentReviewed' => '2026-09-01',
                                       'translationReviewed' => '2026-08-01']), 'degerlendirme daha yeni');
t_eq('2026-09-01', entry_lastmod_from(['assessmentReviewed' => '2026-09-01']), 'ceviri tarihi yok');
t_eq('',           entry_lastmod_from([]), 'ikisi de yok');
```

- [ ] **Adım 3: `php tests/run.php`** → `entry_lastmod_from` tanımsız, 4 test kalır.

- [ ] **Adım 4: `inc/entry.php` sonuna uygula**

```php
/**
 * Sayfanin gercekten anlamli icerik degisikligi gordugu tarih (spec 5.2).
 * Dosya mtime'i BILEREK kullanilmaz: build ve sablon duzenlemesi lastmod'u
 * oynatirsa sitemap yalan soyler.
 */
function entry_lastmod_from(array $job): string
{
    $dates = array_filter([
        (string)($job['assessmentReviewed'] ?? ''),
        (string)($job['translationReviewed'] ?? ''),
    ]);
    return $dates === [] ? '' : max($dates);
}

function entry_lastmod(string $id, string $lang): string
{
    $job = load_entry($id, $lang);
    return $job === null ? '' : entry_lastmod_from($job);
}
```

- [ ] **Adım 5: `php tests/run.php`** → 4 test geçer.

- [ ] **Adım 6: sitemap'i çok dilli yap**

Kök elemana namespace ekle:

```xml
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
```

Her URL için alternatifleri bas — `alternates_for()` yayınlanmamış dili zaten
dışarıda bırakır, ekstra kontrol gerekmez:

```php
foreach ($u['alternates'] as $code => $href) {
    if ($code === 'x-default') {
        continue;                       // sitemap x-default TASIMAZ (spec 5.2)
    }
    echo '    <xhtml:link rel="alternate" hreflang="' . h($code) . '" href="' . h($href) . "\"/>\n";
}
```

URL listesi artık dil başına kurulur: `activeLangs`'in her dili × (ana sayfa +
sabit sayfalar + o dilde **yayınlanmış** entry'ler). `filemtime()` çağrılarının
tamamı `data/page-reviewed.json` okumasıyla değişir; entry satırları
`entry_lastmod()` kullanır.

- [ ] **Adım 7: validator kuralı**

`tools/validate.php`: `PAGE_SLUGS[DEFAULT_LANG]`'in her anahtarı için
`data/page-reviewed.json`'da tarih olmalı; yoksa **uyarı** (hata değil — sitemap
tarihsiz satırla da geçerli XML üretir).

- [ ] **Adım 8: golden protokolü**

**Beklenen golden farkı:** yalnızca `sitemap.json`. `urls` alanında her satıra
`alternates` girdisi eklenir (`en=<kendi URL'i>`) ve sabit sayfaların `lastmod`
değerleri `filemtime` tarihinden `page-reviewed.json` tarihine döner. Başka hiçbir
dosya değişmemeli.

```bash
xmllint --noout tests/golden/sitemap.xml && echo "XML gecerli"
```

- [ ] **Adım 9: Commit**

```bash
git add sitemap.php inc/entry.php tools/validate.php data/page-reviewed.json \
        tests/entry.test.php tests/golden/sitemap.json tests/golden/sitemap.xml
git commit -m "feat: add alternate links and content-dated lastmod to the sitemap"
```

---

### Görev 4A3 — OG kartları dil başına

**Dosyalar**
- Değiştir: `og.php`, `inc/dispatch.php` (OG isteği `lang` taşır)
- Test: `tests/dispatch.test.php`

**Kritik kabul kriteri:** EN kartlarının **md5'i değişmez**. `og-cashier` ve
`og-home` golden'da binary md5 ile korunuyor; bu görev onları kırmamalı.
Mevcut `/og/<slug>.png` yolu korunur — kırılan paylaşım yok (spec 5.6).

- [ ] **Adım 1: `og.php` `$lang` alsın**

`resolve_og()` zaten `['type' => 'og', 'lang' => ..., 'slug' => ...]` döndürüyor
(`inc/routing.php:73`). `og.php` bugün `$lang`'a bakmıyor. Başına:

```php
$lang = $lang ?? DEFAULT_LANG;
$L    = lang_for($lang);
```

Verdict etiketi `VERDICTS[...]['label']` yerine `verdict_meta($verdict, $lang)['label']`
üzerinden gelir — kartta "MENÜDE" / "SE REDUCE" yazar.

- [ ] **Adım 2: cache yolu dil taşısın**

`cache/og/<slug>.png` → `cache/og/<lang>/<slug>.png`. EN için de alt klasör kullanılır;
**üretilen PNG içeriği** değişmediği için golden md5 korunur, yalnızca dosya yolu değişir.

- [ ] **Adım 3: dispatch testi**

`tests/dispatch.test.php` sonuna — TR kapalıyken TR kartı 404:

```php
t_eq('notfound', resolve_og('tr', 'kasiyer', $R)['type'], 'aktif olmayan dilde OG 404');
t_eq('og',       resolve_og('en', 'cashier', $R)['type'], 'EN OG acik');
```

- [ ] **Adım 4: `php tests/run.php`** → geçer.

- [ ] **Adım 5: golden protokolü**

**Beklenen golden farkı: YOK.** `og-cashier` ve `og-home` md5'i aynı kalmalı.
Değiştiyse font yükleme yolu veya renk paleti dil koduna sızmış demektir — dur.

```bash
php tools/doctor.php | grep -i "og\|font"
```

- [ ] **Adım 6: Commit**

```bash
git add og.php inc/dispatch.php tests/dispatch.test.php
git commit -m "feat: render OG cards per language"
```

---

### Görev 4A4 — `search_fold()`: tek veri kaynağı

Bugün arama `mb_strtolower()` ile katlanıyor (`index.php:189-190`, `search.js:81`).
Bu iki gerçek hatayı üretiyor: `mb_strtolower('İ')` `i` + U+0307 (birleşen nokta)
verir ve hiçbir şeyle eşleşmez; aksansız yazan Türkçe kullanıcı ("yazilim")
"Yazılım Geliştirici"yi bulamaz.

**Harita tek dosyada tutulur ve JS'e gömülür.** Aynı algoritmayı iki dilde elle
yazmak ileride sessiz ayrışma üretir (spec 6.1).

**Dosyalar**
- Oluştur: `data/search-fold.json`, `inc/search.php`, `tests/search.test.php`,
  `tools/fold-check.js`
- Değiştir: `index.php:189-190`, `assets/search.js`, `tools/build-index.php`,
  `tools/validate.php`, `tests/run.php`

**Ürettiği arayüz:** `search_fold(string $s): string`; JS tarafında `fold(s)`.

- [ ] **Adım 1: `data/search-fold.json`**

```json
{
    "map": { "İ": "i", "I": "i", "ı": "i", "ş": "s", "Ş": "s", "ğ": "g", "Ğ": "g",
             "ü": "u", "Ü": "u", "ö": "o", "Ö": "o", "ç": "c", "Ç": "c",
             "á": "a", "é": "e", "í": "i", "ó": "o", "ú": "u", "ñ": "n", "ü": "u",
             "Á": "a", "É": "e", "Í": "i", "Ó": "o", "Ú": "u", "Ñ": "n" },
    "fixtures": {
        "Yazılım Geliştirici": "yazilim gelistirici",
        "İŞE ALIM":            "ise alim",
        "MUHASEBECİ":          "muhasebeci",
        "Español":             "espanol",
        "PROGRAMACIÓN":        "programacion",
        "Lingüista":           "linguista",
        "¿Desarrollador?":     "desarrollador"
    }
}
```

Fixture'lar spec §6.2'den birebir alındı; **değiştirilmez**.

- [ ] **Adım 2: Testi yaz (önce kırmızı)**

`tests/search.test.php`:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/search.php';

$spec = json_decode((string)file_get_contents(ROOT . '/data/search-fold.json'), true);
foreach ($spec['fixtures'] as $in => $expected) {
    t_eq($expected, search_fold($in), "fold: $in");
}

// intl kapaliyken de AYNI sonuc — fallback yolu sinanmadan guvenilmez.
intl_available(false);
foreach ($spec['fixtures'] as $in => $expected) {
    t_eq($expected, search_fold($in), "fold (intl kapali): $in");
}
intl_available(null);
```

`tests/run.php`'ye dosyayı ekle.

- [ ] **Adım 3: `php tests/run.php`** → `search_fold` tanımsız, 14 test kalır.

- [ ] **Adım 4: `inc/search.php`**

Sıra spec §6.1'den: NFD → birleşen işaretleri at → Türkçe `ı`/`İ` haritası →
küçük harf → noktalama/boşluk normalize. Türkçe adım 2'den **sonra** değil, harita
üzerinden **açıkça** uygulanır; NFD `ı`'yı çözmez.

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/locale.php';

function search_fold_map(): array
{
    static $map = null;
    if ($map === null) {
        $spec = json_decode((string)@file_get_contents(ROOT . '/data/search-fold.json'), true);
        $map  = (array)($spec['map'] ?? []);
    }
    return $map;
}

/** Aramada karsilastirilan tek bicim. PHP ve JS AYNI haritayi kullanir (spec 6.1). */
function search_fold(string $s): string
{
    $map = search_fold_map();
    $s   = strtr($s, $map);                       // 3. Turkce once: NFD 'i'yi cozmez
    if (intl_available() && class_exists('Normalizer')) {
        $s = (string)Normalizer::normalize($s, Normalizer::FORM_D);   // 1. NFD
    }
    $s = (string)preg_replace('/\p{Mn}+/u', '', $s);                   // 2. birlesen isaretler
    $s = mb_strtolower($s, 'UTF-8');                                   // 4. kucuk harf
    $s = (string)preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);           // 5. noktalama
    return trim((string)preg_replace('/\s+/u', ' ', $s));
}
```

- [ ] **Adım 5: `php tests/run.php`** → 14 test geçer. Geçmiyorsa haritaya
      eksik harf ekle, **fixture'ı değiştirme**.

- [ ] **Adım 6: JS tarafı — harita gömülür, elle yazılmaz**

`tools/build-index.php`, `data/search-fold.json`'daki `map`'i `cache/search-fold.js`
olarak yazar:

```php
$foldJs = "window.__fold = " . json_encode(search_fold_map(), JSON_UNESCAPED_UNICODE) . ";\n";
atomic_write(CACHE_DIR . '/search-fold.js', $foldJs);
```

`assets/search.js`'de `fold()`:

```js
  var FOLD = window.__fold || {};
  function fold(s) {
    s = String(s).replace(/./gu, function (ch) { return FOLD[ch] !== undefined ? FOLD[ch] : ch; });
    s = s.normalize('NFD').replace(/\p{Mn}+/gu, '').toLowerCase();
    return s.replace(/[^\p{L}\p{N}]+/gu, ' ').trim();
  }
```

`search.js:81` `input.value.trim().toLowerCase()` → `fold(input.value)`.

- [ ] **Adım 7: JS ve PHP aynı sonucu veriyor mu — `tools/fold-check.js`**

```js
// node tools/fold-check.js   — PHP ile AYNI fixture'lari kosar.
const spec = require('../data/search-fold.json');
global.window = { __fold: spec.map };
const src = require('fs').readFileSync(__dirname + '/../assets/search.js', 'utf8');
const fold = new Function('window', src.match(/function fold\(s\)[\s\S]*?\n  \}/)[0] + '; return fold;')(global.window);
let fail = 0;
for (const [input, expected] of Object.entries(spec.fixtures)) {
  const got = fold(input);
  if (got !== expected) { console.log(`  x ${input}\n      beklenen: ${expected}\n      gelen:    ${got}`); fail++; }
}
console.log(fail === 0 ? 'JS fold: fixture\'larin hepsi gecti' : `\n${fail} vaka basarisiz.`);
process.exit(fail ? 1 : 0);
```

```bash
node tools/fold-check.js; echo "cikis: $?"
```

`node` yoksa `validate.php` bunu **uyarı** olarak bildirir ve atlar; `node` varsa
kırmızı sonuç **hatadır**. (Bu makinede `node v22` kurulu.)

- [ ] **Adım 8: sunucu tarafı katlamayı değiştir**

`index.php:189-190`'daki iki `mb_strtolower()` çağrısı `search_fold()` olur.
`data-name` ve `data-search` artık katlanmış metin taşır.

- [ ] **Adım 9: golden protokolü**

**Beklenen golden farkı:** yalnızca `home.json`. `links` değişmez; `data-name` /
`data-search` öznitelikleri golden alan setinde **değil**, dolayısıyla `home.json`
da değişmeyebilir — bu normaldir. `home.html` byte katmanında değişir (öznitelik
içerikleri). Başka bir sayfa değiştiyse `search_fold()` yanlış yere sızmış demektir.

- [ ] **Adım 10: Elle duman testi**

```bash
php -S localhost:8000 router.php &
# Tarayicida / ac, arama kutusuna "yazilim" yaz -> "Yazılım Geliştirici" gorunmeli
# "MUHASEBECI" yaz -> "Muhasebeci" gorunmeli
```

- [ ] **Adım 11: Commit**

```bash
git add data/search-fold.json inc/search.php assets/search.js index.php \
        tools/build-index.php tools/fold-check.js tools/validate.php \
        tests/search.test.php tests/run.php tests/golden/home.html tests/golden/home.json
git commit -m "feat: fold search text through one shared map in PHP and JS"
```

---

### Görev 4A5 — Dil başına arama indeksi

**Dosyalar**
- Değiştir: `tools/build-index.php`, `inc/config.php` (`INDEX_FILE`),
  `inc/functions.php:45-48`
- Test: `tests/search.test.php`

- [ ] **Adım 1: `INDEX_FILE` sabitini fonksiyona çevir**

```php
// inc/config.php — sabit kalir ama artik EN icin
const INDEX_FILE = CACHE_DIR . '/index-en.json';
```

```php
// inc/functions.php
function index_file(string $lang = DEFAULT_LANG): string
{
    return CACHE_DIR . '/index-' . $lang . '.json';
}
```

`load_index()` bugün parametresiz (`inc/functions.php:43`) ve `INDEX_FILE`
sabitini okuyor. İmzası `load_index(string $lang = DEFAULT_LANG): array` olur ve
`index_file($lang)` okur. **Fallback zorunlu:** dosya yok veya
bozuksa boş dizi döner ve sayfa normal açılır — arama sunucuda basılan tam liste
üzerinde çalışmaya devam eder (spec 6.3). Bu bugünkü davranış; korunur.

- [ ] **Adım 2: `build-index.php` her aktif dil için üretsin**

```php
foreach ((array)($routes['activeLangs'] ?? [DEFAULT_LANG]) as $lang) {
    $jobs  = load_all_jobs($lang);
    $index = [];
    foreach ($jobs as $id => $job) {
        $index[] = [
            's'  => $id,
            't'  => (string)($job['title'] ?? $id),
            'a'  => implode(' ', (array)($job['aka'] ?? [])),
            'f'  => search_fold(($job['title'] ?? $id) . ' ' . implode(' ', (array)($job['aka'] ?? []))),
            'v'  => (string)($job['verdict'] ?? 'shrinking'),
            'c'  => (string)($job['category'] ?? ''),
            'o'  => (string)($job['oneLiner'] ?? ''),
            'u'  => (string)($job['safeUntil'] ?? ''),
            'd'  => empty($job['sources']) ? 1 : 0,
        ];
    }
    atomic_write(index_file($lang), (string)json_encode(
        ['generated' => gmdate('c'), 'count' => count($index), 'jobs' => $index],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ));
    echo count($index) . " entry indexlendi -> cache/index-$lang.json\n";
}
```

`aka` **yönlendirme tablosuna sızmaz** (spec 7): `build_routes()` yalnızca `slug` ve
`formerSlugs` talep eder — bu görevde o mantığa dokunulmaz.

- [ ] **Adım 3: eski `cache/index.json`'ı sil**

```bash
rm -f cache/index.json
```

`.gitignore`'da `cache/` zaten var mı kontrol et; yoksa dosya repoya girmesin.

- [ ] **Adım 4: bozuk indeks testi**

`tests/search.test.php` sonuna:

```php
// Indeks bozuksa site cokmez, arama kontrollu degrade olur (spec 6.3).
$tmp = CACHE_DIR . '/index-zz.json';
file_put_contents($tmp, '{bozuk');
t_eq([], load_index('zz'), 'bozuk indeks bos dizi doner');
unlink($tmp);
```

- [ ] **Adım 5: `php tests/run.php`** → geçer.
- [ ] **Adım 6: `php tools/build-index.php`** → `17 entry indexlendi -> cache/index-en.json`
- [ ] **Adım 7: golden protokolü** — **beklenen fark: YOK.** İndeks sunucu
      çıktısına girmiyor.

- [ ] **Adım 8: Commit**

```bash
git add tools/build-index.php inc/config.php inc/functions.php tests/search.test.php
git commit -m "feat: build one search index per active language"
```

---

### Görev 4A6 — Dil seçici

Spec 1.6: **otomatik yönlendirme yok.** Seçici, bulunulan sayfanın karşı dildeki
**eşdeğerine** götürür — ana sayfaya düşürmez. Yayınlanmamış dil "yakında" olarak
pasif görünür. Öneri şeridi v1'de **yapılmaz** (spec "gösterilebilir" diyor, zorunlu
kılmıyor; JS + çerez maliyeti lansmanı geciktirir).

**Dosyalar**
- Değiştir: `inc/header.php`, `assets/style.css`
- Değiştir: `data/locale/{en,tr,es}.php` — `nav.lang.*`

- [ ] **Adım 1: locale anahtarları** (editoryal **değil** — makine çevirisi serbest)

```php
'nav.language' => 'Language',      // tr: 'Dil'        es: 'Idioma'
'nav.soon'     => 'soon',          // tr: 'yakında'    es: 'pronto'
'lang.en'      => 'English',       // uc tabloda da AYNI: dil adi kendi dilinde yazilir
'lang.tr'      => 'Türkçe',
'lang.es'      => 'Español',
```

- [ ] **Adım 2: header'a seçici**

`$pageAlternates` zaten sayfanın eşdeğer URL'lerini taşıyor — seçici onu kullanır,
kendi URL'ini kurmaz.

```php
<nav class="lang-switch" aria-label="<?= h($L->t('nav.language')) ?>">
  <?php foreach (LANGS as $code): ?>
    <?php $href = $pageAlternates[$code] ?? null; ?>
    <?php if ($code === $lang): ?>
      <span class="lang-cur" aria-current="true"><?= h($L->t('lang.' . $code)) ?></span>
    <?php elseif ($href !== null): ?>
      <a href="<?= h($href) ?>" hreflang="<?= h($code) ?>"><?= h($L->t('lang.' . $code)) ?></a>
    <?php else: ?>
      <span class="lang-soon"><?= h($L->t('lang.' . $code)) ?> <small><?= h($L->t('nav.soon')) ?></small></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
```

- [ ] **Adım 3: `$pageAlternates` olmayan sayfalar**

`404.php` ve `unavailable.php` alternates kurmaz → seçicide **hiçbir dil aktif
bağlantı almaz**. `unavailable.php` zaten yayınlanan dillere kendi bağlantısını
basıyor (spec 5.4); seçici orada gizlenir:

```php
<?php if (($pageAlternates ?? []) !== []): ?> ... <?php endif; ?>
```

- [ ] **Adım 4: CSS** — `assets/style.css`'e `.lang-switch` kuralları; `.lang-soon`
      soluk ve tıklanamaz (`pointer-events: none` değil, zaten `<span>`).

- [ ] **Adım 5: mobil + dark mode kontrolü** — 360px genişlikte seçici header'ı
      taşırmamalı; dark mode'da `.lang-soon` kontrastı 4.5:1'in altına düşmemeli.

- [ ] **Adım 6: golden protokolü**

**Beklenen golden farkı:** her HTML sayfasında `links` **değişmez** (seçici tek
aktif dilde yalnızca `<span>` basar, `<a href>` basmaz). `h2`/`h3` değişmez.
Bir `links` farkı çıkarsa seçici yayınlanmamış dile bağlantı veriyor demektir — dur.

- [ ] **Adım 7: Commit**

```bash
git add inc/header.php assets/style.css data/locale/en.php data/locale/tr.php \
        data/locale/es.php tests/golden
git commit -m "feat: add a language switcher that follows page equivalents"
```

---

### Görev 4A7 — `content-version.json` ve cache bağımlılığı

`related_jobs()` bloğu tek bir entry'ye değil, **meslek evreninin tamamına** bağlı:
yeni entry eklendiğinde mevcut sayfaların "aynı fay hattındaki işler" bloğu değişir.
Bugün bu bağımlılık yok — `inc/functions.php:169` bunu Faz 3'te not düşmüş.

**Dosyalar**
- Değiştir: `tools/build-index.php`, `inc/functions.php:160-180` (`template_mtime()` /
  cache dependency hesabı)
- Test: `tests/entry.test.php`

- [ ] **Adım 1: `build-index.php` sürüm dosyası yazsın**

```php
$hash = hash_init('sha256');
foreach (glob(JOBS_DIR . '/*/common.json') ?: [] as $p) {
    hash_update($hash, (string)file_get_contents($p));
}
foreach ((array)($routes['activeLangs'] ?? [DEFAULT_LANG]) as $lang) {
    foreach (glob(JOBS_DIR . '/*/' . $lang . '.json') ?: [] as $p) {
        hash_update($hash, (string)file_get_contents($p));
    }
}
atomic_write(CACHE_DIR . '/content-version.json', (string)json_encode(
    ['version' => hash_final($hash), 'generated' => gmdate('c')],
    JSON_UNESCAPED_SLASHES
));
```

- [ ] **Adım 2: cache bu sürümü dependency olarak kullansın**

```php
/**
 * Icerik evreninin surumu. Dosya yoksa/bozuksa kaynak dosyalardan guvenli
 * hesaplanir — eksik surum dosyasi siteyi cokertmez, yalnizca hesabi pahalilastirir
 * (spec 8.2).
 */
function content_version(): string
{
    $f = CACHE_DIR . '/content-version.json';
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    if (is_array($d) && !empty($d['version'])) {
        return (string)$d['version'];
    }
    $newest = 0;
    foreach (glob(JOBS_DIR . '/*/*.json') ?: [] as $p) {
        $newest = max($newest, (int)filemtime($p));
    }
    return 'mtime-' . $newest;
}
```

Sayfa cache anahtarına/geçerlilik kontrolüne `content_version()` katılır. Mevcut
`filemtime($cached) <= $newest` ("şüpheliyi at") davranışı **korunur**.
Klasör `mtime`'ı dependency olarak **kullanılmaz** (spec 8: dizin zaman damgası
dosya içeriği değiştiğinde değişmez → sessiz bayat cache).

- [ ] **Adım 3: test**

```php
// Surum dosyasi yoksa cokme yok, mtime fallback'i devreye girer (spec 8.2).
$vf = CACHE_DIR . '/content-version.json';
$bak = is_file($vf) ? (string)file_get_contents($vf) : null;
@unlink($vf);
t_eq(true, str_starts_with(content_version(), 'mtime-'), 'surum dosyasi yoksa fallback');
file_put_contents($vf, '{bozuk');
t_eq(true, str_starts_with(content_version(), 'mtime-'), 'bozuk surum dosyasi fallback');
if ($bak !== null) { file_put_contents($vf, $bak); } else { @unlink($vf); }
```

- [ ] **Adım 4: `php tests/run.php`** → geçer.
- [ ] **Adım 5: cache davranışı elle doğrulanır**

```bash
php tools/build-index.php
php -S localhost:8000 router.php &
curl -so /dev/null localhost:8000/cashier          # cache yazilir
# yeni bir entry ekle (veya bir common.json'a bosluk ekle), yeniden build al
php tools/build-index.php
curl -s localhost:8000/cashier | grep -c "related"  # blok TAZE gelmeli
./tools/golden.sh --cache-check; echo "cikis: $?"   # 0 olmali
```

- [ ] **Adım 6: golden protokolü** — **beklenen fark: YOK.**

- [ ] **Adım 7: Commit**

```bash
git add tools/build-index.php inc/functions.php tests/entry.test.php
git commit -m "feat: version page cache against the whole content universe"
```

---

### Görev 4A8 — Validator: çok dilli kurallar

Spec §7'nin yeni kuralları. Bu kurallar 4B'nin **kalite kapısıdır** — çeviri
yazılmadan önce yerinde olmalı, yoksa 15 entry yazıldıktan sonra toplu hata çıkar.

**Dosyalar**
- Değiştir: `tools/validate.php`
- Test: `tests/entry.test.php`

- [ ] **Adım 1: yapısal kurallar**

- Meslek slug'ı `en`/`tr`/`es` olamaz, sabit sayfa slug'ıyla çakışamaz
  (`build_routes()`'un `$claim` closure'ı bunu zaten yakalıyor — validator
  `$conflicts` dizisini **hata** olarak bildirsin)
- Dil içinde slug tekil; hiçbir `formerSlug` başka bir canonical'ı gölgelemez
- `taskOrder`'daki her ortak görev, yayınlanan her dilde `name` + `note` taşımalı
- `localTasks` yalnızca kendi dil dosyasında tanımlı ve `taskOrder`'da yer almalı
- `aka` `routes.json`'a sızmamalı: `build_routes()` çıktısındaki `slugs` haritası
  hiçbir `aka` değerini içermemeli

- [ ] **Adım 2: devralma bütünlüğü**

- `assessmentScope: "global"` ise §3.1 alanlarının hiçbiri yazılmamış olmalı
- `assessmentScope: "local"` ise `assessmentSourceLocale` kendi dili, `assessmentReviewed`
  zorunlu
- `translationReviewed` her yerel dosyada **zorunlu**

- [ ] **Adım 3: çelişki kuralları**

- Yerelde bir görev `gone` ve o dilin verdict'i `safe` → **hata** (CONTRIBUTING.md'de
  yayınlanmış eşiğin birebir uygulanması, yeni kural değil)
- `safe` verdict'te `safeUntil` varsa → **hata**
- Yerel override sonrası `gone` sayısı kaynak dilden fazlaysa → **uyarı**
  ("yeniden değerlendir"; 🟡/🔴 ayrımı makineyle ölçülemez)

- [ ] **Adım 4: tazelik**

- `translationReviewed < assessmentReviewed` → **uyarı** + sayfada görünür not

Sayfadaki not için locale anahtarı (editoryal **değil**):

```php
'entry.staleTranslation' => 'This translation is older than the latest assessment review.',
// tr: 'Bu çeviri, son değerlendirme incelemesinden eski.'
// es: 'Esta traducción es anterior a la última revisión de la evaluación.'
```

- [ ] **Adım 5: ortam kuralları**

- `search_fold()` fixture'ları PHP tarafında (4A4'te yazıldı) + `node tools/fold-check.js`
- Yayınlanan her dil kombinasyonu için **hreflang karşılıklılığı**: A dilinde
  basılan her alternate, hedef dilde geri bağlantı vermeli

- [ ] **Adım 6: kuralları kırık fixture'la kanıtla**

Her yeni kural için `tests/entry.test.php`'de bir kırık girdi ve beklenen hata:

```php
t_eq(true, in_array('safe verdict safeUntil tasiyamaz', validate_entry([
    'verdict' => 'safe', 'safeUntil' => 2030,
], 'en'), true), 'safe + safeUntil hata verir');
```

Kural, kırık girdiyle **kırmızı verdiği kanıtlanmadan** yazılmış sayılmaz.

- [ ] **Adım 7: `php tools/validate.php`** → `Hata yok`, iki editoryal uyarı
      (tr/es 69) yerinde.

- [ ] **Adım 8: Commit**

```bash
git add tools/validate.php tests/entry.test.php data/locale/en.php \
        data/locale/tr.php data/locale/es.php
git commit -m "feat: enforce the multilingual validator rules"
```

---

### Görev 4A9 — Smoke matrisi: TR satırları

Spec §12.1'in tam matrisi. `activeLangs` hâlâ `['en']` olduğu için TR satırları
**geçici bir routes tablosu enjekte edilerek** koşulur — canlı yapılandırma
değişmez. `load_routes(?string $file)` test için dosya enjeksiyonunu zaten
destekliyor (`inc/routes_cache.php:201`).

**Dosyalar**
- Değiştir: `tools/smoke.sh`, `tests/routing.test.php`

- [ ] **Adım 1: birim seviyede tam matris**

`tests/routing.test.php`'ye `activeLangs => ['en','tr']` olan bir tablo ile:

```
/                      -> home en          /en           -> 301 /
/en/                   -> 301 /            /tr           -> 301 /tr/
/tr/                   -> home tr          /es           -> 404 (aktif degil)
/en/<en-slug>          -> 301 /<en-slug>   /en/<tr-slug> -> 301 /<en-slug>
/tr/<en-id>            -> 301 /tr/<tr-slug>
/tr/<former-slug>      -> 301 /tr/<tr-slug>
/tr/<aka-only>         -> 404
/unknown               -> 404              /tr/unknown   -> 404
/en/not-a-real-job     -> 404 (yonlendirme YOK)
<yayinlanmamis dil>    -> unavailable + noindex
```

- [ ] **Adım 2: `php tests/run.php`** → hepsi geçer. Geçmeyen satır varsa
      `resolve_path()` düzeltilir, test gevşetilmez.

- [ ] **Adım 3: güvenlik fixture'ları iki giriş yolunda**

`tools/smoke.sh` matrisine (§1.8) — bunlar zaten var, TR yolları eklenir:

```
/data/jobs/accountant/tr.json   -> 404
/cache/index-tr.json            -> 404
/.well-known/security.txt       -> 404 DEGIL
```

- [ ] **Adım 4: `./tools/smoke.sh`** → `Matris temiz.`

- [ ] **Adım 5: Commit**

```bash
git add tools/smoke.sh tests/routing.test.php
git commit -m "test: cover the full routing matrix including Turkish"
```

---

### Görev 4A10 — JSON-LD dil alanları

Spec §5.3: JSON-LD dile göre kurulur. Faz 3 metinleri locale'e taşıdı, ama iki alan
hâlâ eksik: `inLanguage` hiçbir düğümde yok ve `BreadcrumbList` adları/URL'leri
yerelleşmiyor (`job.php:84`).

**Dosyalar**
- Değiştir: `job.php:60-110` (JSON-LD bloğu), `index.php:49-90`, `changelog.php:14-31`
- Test: golden `jsonLd` alanı

- [ ] **Adım 1: `inLanguage` her kök düğüme**

```php
'inLanguage' => $lang,      // Article, FAQPage, ItemList, Occupation
```

- [ ] **Adım 2: `BreadcrumbList` yerelleşsin**

Adlar `$L->t()`'den, URL'ler `url_for($lang, ...)`'dan gelir — elle kurulmaz:

```php
'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1,
     'name' => $L->t('nav.home'), 'item' => url_for($lang, 'home', '', $routes)],
    ['@type' => 'ListItem', 'position' => 2,
     'name' => (string)$job['title'], 'item' => url_for($lang, 'job', $id, $routes)],
],
```

- [ ] **Adım 3: golden protokolü**

**Beklenen golden farkı:** her HTML sayfasının `jsonLd` alanına `inLanguage: "en"`
eklenir. `BreadcrumbList` adları EN'de **aynı kalır** (locale değeri zaten İngilizce).
Bir `name` değeri değiştiyse anahtar yanlış seçilmiş demektir — dur.

- [ ] **Adım 4: yapı doğrulaması**

```bash
curl -s localhost:8000/cashier   | sed -n 's/.*<script type="application\/ld+json">\(.*\)<\/script>.*/\1/p'   | python3 -m json.tool > /dev/null && echo "JSON-LD gecerli"
```

- [ ] **Adım 5: Commit**

```bash
git add job.php index.php changelog.php tests/golden
git commit -m "feat: localise the JSON-LD graph"
```

---

### Görev 4A11 — `unavailable` sayfası yayınlanan dillere bağlansın

`unavailable.php` bugün 404 + `noindex` veriyor ve canonical yazmıyor — spec §5.4'ün
üç kuralı da yerinde. Eksik olan dördüncüsü: *"Sayfa kullanıcı dostudur: yayınlanan
dillere bağlantı verir."* Bugün yalnızca kendi dilinin ana sayfasına buton var.

**Dosyalar**
- Değiştir: `unavailable.php`
- Değiştir: `data/locale/{en,tr,es}.php` — `page.unavailable.availableIn`

- [ ] **Adım 1: locale anahtarı** (editoryal değil)

```php
'page.unavailable.availableIn' => 'This entry is available in:',
// tr: 'Bu içerik şu dillerde var:'   es: 'Esta entrada está disponible en:'
```

- [ ] **Adım 2: yayınlanan dilleri bas**

`$id` zaten dispatch'ten geliyor (`resolve_path()` → `['type' => 'unavailable',
'lang' => ..., 'id' => ...]`). `alternates_for()` **kullanılmaz** — o küme
`published` filtresinden geçtiği için doğru, ama burada hedef dilin kendisi
yayınlanmamış; bağlantılar diğer dillere:

```php
$routes = load_routes();
$others = alternates_for('job', $id, $routes);
unset($others['x-default'], $others[$lang]);
```

```php
<?php if ($others !== []): ?>
  <p><?= h($L->t('page.unavailable.availableIn')) ?></p>
  <ul class="lang-list">
    <?php foreach ($others as $code => $href): ?>
      <li><a href="<?= h($href) ?>" hreflang="<?= h($code) ?>"><?= h($L->t('lang.' . $code)) ?></a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
```

**İngilizceye sessiz yönlendirme yapılmaz** — bağlantı verilir, yönlendirme değil.

- [ ] **Adım 3: `$pageAlternates` kurulmaz.** Sayfa 404'tür; hreflang kümesine
      girmez ve dil seçicide aktif görünmez (4A6 Adım 3).

- [ ] **Adım 4: doğrula** — `activeLangs: ['en']` iken TR entry'si zaten
      erişilemez; kontrol 4B4 Adım 2'deki geçici override ile yapılır:

```bash
curl -si localhost:8000/tr/<yayinlanmamis-entry> | head -1        # 404
curl -s  localhost:8000/tr/<yayinlanmamis-entry> | grep -c "hreflang"   # bagalanti var
curl -s  localhost:8000/tr/<yayinlanmamis-entry> | grep -c "canonical"  # 0
```

- [ ] **Adım 5: golden protokolü** — **beklenen fark: YOK.** `notfound` hedefi
      `/unknown`'ı çekiyor, `unavailable` değil.

- [ ] **Adım 6: Commit**

```bash
git add unavailable.php data/locale/en.php data/locale/tr.php data/locale/es.php
git commit -m "feat: link the unavailable page to the languages that do have it"
```

---

### 4A kapanışı

```bash
php tests/run.php                     # 0 kaldi
php tools/validate.php                # Hata yok (tr/es 69 uyarisi)
php tools/build-index.php             # 17 entry, index-en.json, content-version.json
php tools/doctor.php                  # kritik hata yok
./tools/golden.sh --check --semantic  # 15/15, cikis 0
./tools/golden.sh --cache-check       # cikis 0
php tools/golden.php --self-test      # 5/5, cikis 0
node tools/fold-check.js              # cikis 0
./tools/smoke.sh                      # Matris temiz
git status --short                    # bos
php -r 'require "inc/routes_cache.php"; echo implode(",", load_routes()["activeLangs"]), "\n";'   # en
curl -si localhost:8000/tr/kasiyer | head -1                                                      # 404
```

Son iki satır **kritik**: 4A altyapıyı kurdu ama TR hâlâ kapalı.

---

# Bölüm 4B — Türkçe içerik

Bu bölümün işi kod değil, **yazı**. Spec §14: *"İş yükünün ağırlığı kodda değil,
içerikte."* Takvimi bu bölüm belirler.

> **Kapsam uyarısı — planlanandan büyük.** Bekleyen kuyruk 69 editoryal anahtar
> olarak konuşuldu, ama TR lansmanı için gereken bundan fazlası: **17 entry'nin
> 15'inin TR çevirisi yok** (`cashier` ve `administrative-assistant` hazır).
> Lansman kuralı (spec §15) yarım katalog lansmanına izin vermiyor: *"Bir dil;
> ana sayfası + sabit sayfaları + mevcut mesleklerin tamamı hazır olmadan aktif
> dil ilan edilmez."* Yani 4B = 69 anahtar **+ 15 entry** + 4 sabit sayfa slug'ı.

---

### Görev 4B1 — TR sabit sayfa slug'ları

**Dosyalar**
- Değiştir: `inc/routes_cache.php:14-19` (`PAGE_SLUGS`)
- Test: `tests/routes_cache.test.php`

- [ ] **Adım 1: slug'ları yaz**

```php
    'tr' => ['methodology' => 'metodoloji', 'landscape' => 'zaman-cizelgesi',
             'changelog'   => 'degisiklikler', 'sponsor'  => 'sponsorluk'],
```

Slug'lar **ASCII ve katlanmış** olmalı: `valid_slug()` yalnızca `[a-z0-9-]` kabul
ediyor (`inc/functions.php:12`). `zaman-çizelgesi` **reddedilir**.

- [ ] **Adım 2: karşılıklılık testi**

```php
foreach (['methodology', 'landscape', 'changelog', 'sponsor'] as $key) {
    $slug = PAGE_SLUGS['tr'][$key];
    t_eq(true, valid_slug($slug), "tr sayfa slug'i gecerli: $slug");
    t_eq($key, build_routes()['pages']['tr'][$slug], "tr slug -> anahtar: $slug");
}
```

- [ ] **Adım 3: çapraz dil yönlendirmesi**

`resolve_path()` `/tr/methodology` → `301 /tr/metodoloji` üretmeli
(`inc/routing.php:177-182` bunu zaten yapıyor). Testte doğrula — `activeLangs`'e
`'tr'` eklenmiş bir tabloyla.

- [ ] **Adım 4: `php tests/run.php`** → geçer. `php tools/build-index.php`.
- [ ] **Adım 5: Commit**

```bash
git add inc/routes_cache.php tests/routes_cache.test.php
git commit -m "feat: add the Turkish slugs for the static pages"
```

---

### Görev 4B2 — 69 editoryal anahtarın Türkçe çevirisi

`methodology.*` (43) + `llms.*` (26). Bunlar makine çevirisiyle doldurulmaz —
`/methodology` verdict tanımlarını **halka açık olarak** yayınlar ve çakışma
halinde doğru kabul edilir (`data/locale/editorial.php`).

**Dosyalar**
- Değiştir: `data/locale/tr.php`

- [ ] **Adım 1: kuyruğu yazdır**

```bash
php -r 'require "inc/locale.php"; foreach (locale_pending("tr") as $k)
  printf("%-40s %s\n", $k, locale_table("en")[$k]);' | less
```

- [ ] **Adım 2: `docs/memory/voice.md`'yi oku.** Ses, İngilizce metnin sesidir:
      iddia eden, savunulabilir, sünnetsiz. "Belki", "olabilir", "bazı durumlarda"
      İngilizcede yoksa Türkçede de olmaz.

- [ ] **Adım 3: terminoloji sözlüğünü sabitle — çeviriden ÖNCE**

`docs/memory/decisions/` altına bir karar notu **önerilir** (otomatik yazılmaz):

| EN | TR | Neden |
|---|---|---|
| verdict | yargı | "karar" hukuki, "tahmin" yanlış — site tahmin yapmıyor |
| task | görev | — |
| gone / going / safe | gitti / gidiyor / güvende | üç kelime de tek başına anlaşılmalı |
| resistance tag | direnç etiketi | — |
| safe until | güvenli olduğu yıl | "~2032'ye kadar güvende" |
| community draft | topluluk taslağı | — |

Sözlük sabitlenmeden çeviriye başlanmaz: 69 anahtarın yarısını yazdıktan sonra
"verdict"i değiştirmek tüm metni yeniden okumak demektir.

- [ ] **Adım 4: `methodology.*` (43 anahtar) yazılır**

HTML işaretleme değerin **içinde** kalır — `<strong>`, `<code>`, `<em>` birebir
korunur. `%s` yer tutucuları korunur ve **sırası değişebilir** (`vsprintf`
kullanıldığı için sıra değiştirmek gerekiyorsa `%1$s` biçimi kullanılır).

- [ ] **Adım 5: `llms.*` (26 anahtar) yazılır**

Çok satırlı değerlerde satır sonları korunur; markdown başlıkları (`##`) ve liste
işaretleri (`- `) **çevrilmez**, yalnızca metinleri çevrilir.

- [ ] **Adım 6: kuyruk sıfır**

```bash
php -r 'require "inc/locale.php"; printf("tr: %d bekleyen\n", count(locale_pending("tr")));'
```
Beklenen: `tr: 0 bekleyen`.

- [ ] **Adım 7: `php tools/validate.php`** → TR uyarısı **kaybolur**, ES uyarısı
      (69) kalır. `Hata yok`.

- [ ] **Adım 8: `LOCALE_STRICT` ile eksik anahtar avı**

```bash
php -r 'define("LOCALE_STRICT", true); require "inc/functions.php";
  $L = lang_for("tr"); foreach (array_keys(locale_table("en")) as $k) { $L->t($k); }
  echo "tr tablosu tam\n";'
```

Bu, `Lang::t()`'nin `LOCALE_STRICT` altında istisna fırlatmasını (`inc/lang/Base.php:27`)
bir kapıya çevirir: **hiçbir anahtar ekrana anahtar adı olarak basılamaz.**

- [ ] **Adım 9: Commit**

```bash
git add data/locale/tr.php
git commit -m "content: translate the editorial locale keys into Turkish"
```

---

### Görev 4B3 — 15 entry'nin Türkçe çevirisi

**Dosyalar**
- Oluştur: `data/jobs/<id>/tr.json` × 15
- Değiştir: `data/pending-tr-titles.json` (tüketildikçe boşalır)

Hazır olanlar: `cashier`, `administrative-assistant`. `data/pending-tr-titles.json`
başlıkları zaten taşıyor — çeviri oradan başlar, sıfırdan değil.

**Bu görev entry başına tekrarlanır. Her entry kendi commit'ini alır** — 15 entry
tek commit'te gitmez; biri geri alınabilmelidir.

Her `tr.json` için, `data/jobs/cashier/tr.json` şablon alınır ve şu alanlar yazılır:

```
assessmentScope        "global"  (yerel yargıya geçilmiyorsa — spec 3.1)
assessmentSourceLocale "en"
translationReviewed    "YYYY-MM-DD"   ZORUNLU (validator kuralı)
slug                   ASCII, katlanmış   (valid_slug() gecmeli)
formerSlugs            []  (ilk yayında boş)
aka                    arama için yerel eş adlar — routes'a SIZMAZ
title, oneLiner, summary, tasks[].name, tasks[].note, whatSurvives,
adaptPrompt, adaptTools
```

**`assessmentScope: "global"` ise** `verdict`, `safeUntil`, `resistanceTags`,
`sources` **yazılmaz** — devralınır (spec 3.1) ve validator yazılmışsa hata verir.
`geo_answer()` küresel değerlendirme notunu otomatik ekler (spec 5.5):

> Bu küresel değerlendirme ağırlıklı olarak uluslararası ve ABD kaynaklarına dayanır;
> Türkiye'ye özgü mevzuat ve iş piyasası ayrıca incelenmemiştir.

Bu not `Tr` sınıfında zaten uygulanmış olabilir; değilse `Tr::geoAnswer()` ve
`Tr::evidenceNote()` içinde uygulanır ve **OG kartına girmez**.

- [ ] **Adım 1 (entry başına): `adaptPrompt` en son yazılır.** En uzun ve en teknik
      alan (accountant'ınki ~1.500 karakter). Diğer alanlar bittikten sonra yazılır ki
      terminoloji oturmuş olsun.

- [ ] **Adım 2 (entry başına): doğrula**

```bash
php tools/validate.php 2>&1 | grep -i "<id>\|tr" | head
php tools/build-index.php
php -r 'require "inc/functions.php"; $j = load_entry("<id>", "tr");
  echo $j["title"], " / ", $j["slug"], " / ", $j["verdict"], "\n";'
```

`verdict` boş geliyorsa devralma çalışmıyor demektir — `en.json`'dan gelmeliydi.

- [ ] **Adım 3 (entry başına): commit**

```bash
git add data/jobs/<id>/tr.json data/pending-tr-titles.json
git commit -m "content: add the Turkish entry for <id>"
```

- [ ] **Adım 4 (15'i bitince): kapsam kontrolü**

```bash
ls data/jobs/*/tr.json | wc -l          # 17 olmali
php -r 'require "inc/functions.php";
  foreach (array_keys(load_all_jobs("en")) as $id)
    if (!in_array("tr", entry_langs($id), true)) echo "EKSIK: $id\n";'
```
Çıktısı boş olmalı.

---

### Görev 4B4 — TR ana sayfası ve sabit sayfa metinleri

`data/locale/tr.php`'de `methodology.*` ve `llms.*` dışındaki anahtarlar
(`nav.*`, `home.*`, `foot.*`, `page.*`, `job.*`, `verdict.*`, `tag.*`, `category.*`)
Faz 3'te dolduruldu. Bu görev **doğrulama** görevidir, yazma değil.

- [ ] **Adım 1: tam kapsam kanıtı** — 4B2 Adım 8'deki `LOCALE_STRICT` taraması
      tekrar koşulur; hiçbir anahtar eksik olmamalı.

- [ ] **Adım 2: TR sayfalarını gözle gör** (hâlâ `activeLangs: ['en']` — geçici
      yerel override ile):

```bash
php -r '
  require "inc/functions.php";
  $r = load_routes(); $r["activeLangs"] = ["en","tr"];
  file_put_contents("cache/routes.json", json_encode($r));
  echo "TR gecici olarak acildi — 4C oncesi GERI ALINACAK\n";'
php -S localhost:8000 router.php &
# /tr/ · /tr/metodoloji · /tr/kasiyer · /tr/degisiklikler gezilir
php tools/build-index.php     # GERI AL: activeLangs yeniden ['en'] olur
```

Son satır **atlanmaz**. `build-index.php` tabloyu kaynaktan yeniden üretir ve
`activeLangs`'i `['en']`'e döndürür (`inc/routes_cache.php:181`).

- [ ] **Adım 3: gözle bakılacaklar** — başlıklarda kırık karakter yok, tarih
      biçimi Türkçe ("Ağustos 2026"), liste bağı doğru ("A, B ve C"), `İ`/`ı`
      doğru, mobilde tablo taşmıyor, dark mode kontrastı yeterli.

- [ ] **Adım 4: bulunan her hata kendi commit'ini alır.**

---

# Bölüm 4C — Aktivasyon ve lansman

---

### Görev 4C1 — Aktivasyon kapısı

Aktivasyon **tek satırdır** ve o satır ancak kapının sekiz kontrolü de yeşilse atılır.

- [ ] **Adım 1: kapı kontrolleri**

```bash
php -r 'require "inc/locale.php"; printf("tr bekleyen: %d\n", count(locale_pending("tr")));'   # 0
ls data/jobs/*/tr.json | wc -l                                                                 # 17
php -r 'require "inc/routes_cache.php"; print_r(PAGE_SLUGS["tr"]);'                            # 4 slug
php tests/run.php                                                                              # 0 kaldi
php tools/validate.php                                                                         # Hata yok
node tools/fold-check.js                                                                       # cikis 0
php tools/doctor.php                                                                           # font TR kapsami ok
./tools/golden.sh --check --semantic                                                           # 15/15
```

Biri kırmızıysa **aktivasyon yapılmaz**. Kuyruğu sıfırlanmamış bir dili açmak,
909 testle kurulan mekanizmayı kendi elimizle delmek olur.

- [ ] **Adım 2: satırı at**

```php
// inc/routes_cache.php:181
        'activeLangs' => ['en', 'tr'],
```

- [ ] **Adım 3: `php tools/build-index.php`** → `index-en.json` **ve**
      `index-tr.json`, `content-version.json`, `routes.json` yeniden üretilir.

- [ ] **Adım 4: `php tests/run.php`** → `0 kaldi`. Kırmızı test varsa
      **geri sar** (`git checkout inc/routes_cache.php`) ve testi düzelt.

- [ ] **Adım 5: Commit**

```bash
git add inc/routes_cache.php
git commit -m "feat: activate Turkish"
```

---

### Görev 4C2 — Lansman kontrolleri

- [ ] **Adım 1: tam smoke matrisi**

```bash
./tools/smoke.sh          # Matris temiz
```

- [ ] **Adım 2: hreflang karşılıklılığı**

```bash
php -r '
require "inc/functions.php";
$routes = load_routes(); $bad = 0;
foreach (array_keys($routes["published"]) as $id) {
    $alts = alternates_for("job", $id, $routes);
    foreach ($alts as $code => $href) {
        if ($code === "x-default") continue;
        $back = alternates_for("job", $id, $routes);
        if (!isset($back[$code])) { echo "KARSILIKSIZ: $id / $code\n"; $bad++; }
    }
}
echo $bad === 0 ? "hreflang karsilikli\n" : "$bad karsiliksiz\n";'
```

- [ ] **Adım 3: sitemap**

```bash
curl -s localhost:8000/sitemap.xml > /tmp/sm.xml
xmllint --noout /tmp/sm.xml && echo "XML gecerli"
grep -c "<url>" /tmp/sm.xml            # (17 entry + 5 sayfa) x 2 dil = 44
grep -c 'hreflang="tr"' /tmp/sm.xml    # 0 DEGIL
grep -c "x-default" /tmp/sm.xml        # 0 (sitemap x-default tasimaz)
```

- [ ] **Adım 4: OG kartları**

```bash
curl -so /tmp/tr.png localhost:8000/og/tr/kasiyer.png && file /tmp/tr.png   # PNG 1200x630
curl -so /tmp/en.png localhost:8000/og/cashier.png && md5 /tmp/en.png       # EN md5 DEGISMEDI
```

EN kartının md5'i golden'daki `og-cashier` md5'iyle aynı olmalı — TR açılışı
İngilizce paylaşım görselini değiştirmemeli.

- [ ] **Adım 5: arama**

`/tr/` sayfasında: "yazilim" → sonuç var; "MUHASEBECİ" → sonuç var;
"kasiyer" → Kasiyer. JS kapalıyken tablo tam görünür (`data-search` sunucuda basılı).

- [ ] **Adım 6: cache davranışı**

```bash
./tools/golden.sh --cache-check; echo "cikis: $?"      # 0
# TR sayfasi cache'lendikten sonra en.json degisirse TR sayfasi TAZELENMELI
```

- [ ] **Adım 7: golden'a TR hedefleri ekle**

`tools/golden.php` `TARGETS`'a:

```php
    'tr-home'       => ['/tr/',            'html'],
    'tr-kasiyer'    => ['/tr/kasiyer',     'html'],
    'tr-metodoloji' => ['/tr/metodoloji',  'html'],
    'og-tr-kasiyer' => ['/og/tr/kasiyer.png', 'png'],
```

```bash
./tools/golden.sh --capture
php tools/golden.php --self-test      # 5/5 hala gecmeli
./tools/golden.sh --check --semantic  # 19/19
```

- [ ] **Adım 8: Commit**

```bash
git add tools/golden.php tests/golden
git commit -m "test: extend the golden targets to Turkish"
```

---

### Görev 4C3 — Faz 4 kapanışı

- [ ] **Adım 1: sekiz kontrol**

```bash
php tests/run.php                     # 0 kaldi
php tools/validate.php                # Hata yok (yalniz ES uyarisi: 69)
php tools/build-index.php             # 17 entry x 2 dil
php tools/doctor.php                  # kritik hata yok
./tools/golden.sh --check --semantic  # 19/19, cikis 0
./tools/golden.sh --cache-check       # cikis 0
php tools/golden.php --self-test      # 5/5, cikis 0
./tools/smoke.sh                      # Matris temiz
git status --short                    # bos
```

- [ ] **Adım 2: kapanış notu** — `docs/architecture/2026-08-15-cok-dilli-faz-4-kapanis.md`.
      Faz 3 kapanış notunun biçimi izlenir: commit listesi, durum bloğu, bilinen ve
      kabul edilmiş farklar, sıradaki sıra.

- [ ] **Adım 3: Search Console'a sitemap** — spec Faz 6'ya koyuyor, ama TR
      lansmanında yapılması gereken tek dış adım budur ve unutulursa dil aylarca
      indekslenmez. **Lansman anında yapılır, Faz 6'ya bırakılmaz.**

---

## Commit haritası

| # | Commit | Görev | EN çıktısı değişir |
|---|---|---|---|
| 1 | `feat: emit hreflang, og:locale and a language-aware html lang` | 4A1 | hayır |
| 2 | `feat: add alternate links and content-dated lastmod to the sitemap` | 4A2 | **evet** (sitemap) |
| 3 | `feat: render OG cards per language` | 4A3 | hayır |
| 4 | `feat: fold search text through one shared map in PHP and JS` | 4A4 | **evet** (öznitelik) |
| 5 | `feat: build one search index per active language` | 4A5 | hayır |
| 6 | `feat: add a language switcher that follows page equivalents` | 4A6 | **evet** (header) |
| 7 | `feat: version page cache against the whole content universe` | 4A7 | hayır |
| 8 | `feat: enforce the multilingual validator rules` | 4A8 | hayır |
| 9 | `test: cover the full routing matrix including Turkish` | 4A9 | hayır |
| 10 | `feat: localise the JSON-LD graph` | 4A10 | **evet** (`inLanguage`) |
| 11 | `feat: link the unavailable page to the languages that do have it` | 4A11 | hayır |
| — | **⏸ KAPI — 4A biter, sonuç sunulur** | | |
| 12 | `feat: add the Turkish slugs for the static pages` | 4B1 | hayır |
| 13 | `content: translate the editorial locale keys into Turkish` | 4B2 | hayır |
| 14–28 | `content: add the Turkish entry for <id>` × 15 | 4B3 | hayır |
| — | **⏸ KAPI — içerik biter, sonuç sunulur** | | |
| 29 | `feat: activate Turkish` | 4C1 | hayır (TR **açılır**) |
| 30 | `test: extend the golden targets to Turkish` | 4C2 | hayır |
| 31 | `docs: close out phase 4` | 4C3 | hayır |

---

## Riskler

1. **İçerik iş yükü kodun kat kat üzerinde.** 15 entry × (`summary` + 4–8 görev
   notu + `whatSurvives` + ~1.500 karakterlik `adaptPrompt`). 4A bir günlük iş,
   4B haftalık. Takvim buna göre kurulur; 4A bitti diye lansman yakın sanılmaz.
2. **Terminoloji kayması.** 69 anahtar ve 15 entry farklı zamanlarda yazılırsa
   "verdict" üç ayrı kelimeye dönüşür. 4B2 Adım 3'teki sözlük bu yüzden
   **çeviriden önce** sabitlenir.
3. **Font glifleri.** `doctor.php` TR+ES kapsamını zaten `ok` veriyor — ama OG
   kartı ayrı bir yol: `imagettftext` ile "MENÜDE" basıldığında `Ü` kutu çıkarsa
   4A3'te görülür, lansmanda değil.
4. **Devralma sızıntısı.** `assessmentScope: "global"` bir `tr.json`'a yanlışlıkla
   `verdict` yazılırsa TR ve EN yargıları sessizce ayrışır. Validator kuralı
   (4A8 Adım 2) bunu **hata** sayar — o kural 4B'den önce yazılır.
5. **`aka` yönlendirmeye sızarsa** kullanıcı yanlış mesleğe gider. 4A8 Adım 1
   `routes.json` çıktısını bu yüzden ayrıca denetler.
6. **Yarım lansman baskısı.** "12 entry hazır, açalım" spec §15'e aykırıdır ve
   hreflang karşılıklılığını kırar. Kapı 4C1'dedir ve gevşetilmez.

---

## Faz 4'ün DEĞİŞTİRMEDİKLERİ

- **İspanyolca.** `activeLangs`'e `'es'` **girmez**; ES kuyruğu 69'da kalır.
  Faz 5 bağımsız çalışır ve 4A'nın altyapısını hazır bulur.
- **Öneri şeridi** (tarayıcı dili uyuşmazlığında "Türkçe devam etmek ister
  misiniz?"). Spec "gösterilebilir" diyor, zorunlu kılmıyor — v1'de yok.
- **Otomatik dil yönlendirmesi.** Hiçbir zaman yapılmaz (spec 1.6).
- **Yerel yargı** (`assessmentScope: "local"`). TR entry'leri küresel değerlendirmeyi
  devralır ve §5.5 notunu taşır. Yerel yargıya geçiş §3.3'ün işi ve ayrı bir karardır.
- **Matomo dil segmentasyonu** — Faz 6.
- **Veri şeması** — Faz 2'de tamamlandı, dokunulmaz. Spec §6'nın "`titleTr` alanı
  ölür" maddesi Faz 2'de zaten uygulandı: kod tabanında `titleTr` kalmadı.

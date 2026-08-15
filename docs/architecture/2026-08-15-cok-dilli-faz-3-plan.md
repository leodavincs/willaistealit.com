# Çok Dilli Mimari — Faz 3 Uygulama Planı (locale sistemi)

> **Agentic worker'lar için:** GEREKLİ ALT-SKILL: `superpowers:executing-plans`.

**Amaç:** Dile bağlı her şeyi — etiketler, tanımlar, üretilen cümleler, tarih ve liste
biçimlendirmesi, arayüz metinleri — koddan çıkarıp locale sistemine taşımak.
**İngilizce çıktı değişmeden.** TR/ES sınıfları ve tabloları yazılır ve test edilir ama
**hiçbir URL'e servis edilmez**: `activeLangs` Faz 3 boyunca `['en']` kalır.

**Mimari:** `inc/lang/{Base,En,Tr,Es}.php` dil davranışını (çoğul, artikel, liste
bağlacı, tarih, üretilen cümle) taşır; `data/locale/{en,tr,es}.php` düz metin tablosunu
taşır. `inc/config.php` yalnızca **dilden bağımsız** olanı tutar: anahtarlar, renkler,
`rgb`, `dot`. Mevcut erişimci fonksiyonlar (`verdict_meta()` vb.) imzalarını koruyup
locale'e devreder — şablon değişikliği en aza iner.

**URL üretimi locale'in işi değildir.** `url_for()` / `alternates_for()` (Faz 1B)
tek kaynak olarak kalır; Lang sınıfları URL üretmez, almaz, bilmez.

**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md` (§4, §12.2)
**Önceki:** Faz 0–1 planı · Faz 2 planı (aynı klasör)

## Faz 2'den devralınan durum

- 17 entry `data/jobs/<id>/{common,en[,tr,es]}.json`; `cashier` ve
  `administrative-assistant` üç dilde içerik taşıyor.
- `activeLangs = ['en']`; `/tr/*` ve `/es/*` 404.
- 216 assert, validator, smoke matrisi temiz.
- Sayfa cache'i `cache/pages/<lang>/`, bağımlılıkları `entry_dependency_files()`.

## Global kısıtlar

- **PHP 8.3**, `declare(strict_types=1);`, kod yorumları Türkçe ve ASCII.
- **Bağımlılık eklenmez.**
- **`activeLangs` Faz 3 boyunca `['en']` kalır.** TR/ES URL'leri açılmaz — o Faz 4.
- **İngilizce çıktı korunur:** golden (byte) + semantik, iki katman (spec §12.2).
  Anlamsız whitespace farkı hata değildir; kullanıcıya görünen veya SEO anlamını
  değiştiren fark hatadır.
- **Locale sınıfları yalnızca dil davranışı taşır.** URL üretmez, dosya okumaz,
  `$_GET`/`$_SERVER`'a bakmaz, HTML basmaz. Girdisi veri, çıktısı metin.
- **Dilden bağımsız olan config'de kalır:** verdict/kategori/tag **anahtarları**,
  `color`, `rgb`, `dot`. Locale'e yalnızca `label`, `blurb`, tanım metinleri gider.
- **`intl` kapalıyken TR ve ES aynı sonucu vermeli** — fallback testi zorunlu.
- **Davranış bağlantısından önce ayrı kapı:** Görev 3C commit üretir ama davranış
  değiştirmez; orada durulur ve sonuç sunulur.
- Her commit sonrası `git diff --cached --name-status` doğrulaması ve:
  - **Görev 3C'den önce:** `php tests/run.php` · `php tools/validate.php` ·
    `./tools/smoke.sh` · `git status --short`
  - **Görev 3C'den sonra** bunlara ek: `./tools/golden.sh --check`
    (golden aracı 3C'de doğuyor; ondan önce çağrılamaz)
  **`git add -A` kullanılmaz.**
- **Cache temizliği `clear_cache()` ile yapılır**, `rm -rf cache/pages/*` ile değil —
  hedef kapsamı kod tarafından belirlenir, kabuk glob'u tarafından değil:
  ```bash
  php -r 'require "inc/functions.php"; echo clear_cache(), " dosya temizlendi\n";'
  ```

## Dosya haritası

| Dosya | Sorumluluk | Görev |
|---|---|---|
| `inc/lang/Base.php` | Soyut dil sözleşmesi + dilden bağımsız yardımcılar | 3A |
| `inc/lang/En.php` | Bugünkü İngilizce davranışın birebir taşınmış hali | 3A |
| `inc/lang/Tr.php` · `Es.php` | Türkçe / İspanyolca dil davranışı | 3B |
| `data/locale/en.php` | İngilizce metin tablosu | 3A |
| `data/locale/tr.php` · `es.php` | Türkçe / İspanyolca metin tabloları | 3B |
| `inc/locale.php` | `lang_for()` fabrikası, `intl_available()` | 3A |
| `tests/lang.test.php` | Dil davranışı testleri (üç dil + intl fallback) | 3A, 3B |
| `tools/golden.php` | Golden yakalama ve karşılaştırma | 3C |
| `tests/golden/*` | Yakalanmış İngilizce çıktı | 3C |
| `inc/config.php` | Dile bağlı metinler çıkar, anahtar+renk kalır | 3D |
| `inc/functions.php` | Erişimciler ve cümle üreticiler locale'e devreder | 3D, 3E |
| Şablonlar | Sabit metinler `t()`'ye, elle URL'ler `url_for()`'a | 3F |

---

## Şema kararları

### L1 — Sözleşme: Lang ne yapar, ne yapmaz

**Yapar:** etiket ve tanım döndürür, ay adı biçimlendirir, liste bağlar, artikel
ekler, çoğul yapar, `geo_answer` ve `faq_pairs` cümlelerini kurar, paylaşım metnini
yazar, kanıt notunu üretir.

**Yapmaz:** URL üretmez (o `url_for()`), dosya okumaz (veri parametre olarak gelir),
HTML kaçışı yapmaz (o `h()`), süper globallere bakmaz.

Bu sınır test edilebilir: `tests/lang.test.php` bir Lang örneğine yalnızca dizi
verir ve dize alır; dosya sistemi ya da HTTP'ye hiç dokunmaz.

### L2 — Config/locale bölüşümü

```php
// inc/config.php — DILDEN BAGIMSIZ kalir
const VERDICTS = [
    'safe'        => ['dot' => '🟢', 'color' => '#2b7d52', 'rgb' => [43, 125, 82]],
    'shrinking'   => ['dot' => '🟡', 'color' => '#a8811f', 'rgb' => [168, 129, 31]],
    'on-the-menu' => ['dot' => '🔴', 'color' => '#b34455', 'rgb' => [179, 68, 85]],
];
const TASK_VERDICTS   = ['gone' => ['color' => '#b34455'], /* ... */];
const RESISTANCE_KEYS = ['physical-presence', 'legal-liability', /* ... */];
const CATEGORY_KEYS   = ['tech', 'finance', 'legal', /* ... */];
```

`label`, `blurb` ve tag tanımları `data/locale/<lang>.php`'ye gider. `SITE_TAG` de
locale'e taşınır (`site.tagline`).

**Geri uyumluluk:** `verdict_meta($key, $lang = DEFAULT_LANG)` **birleştirilmiş** dizi
döndürmeye devam eder — `dot`/`color`/`rgb` config'den, `label`/`blurb` locale'den.
Şablonlar `$v['label']` yazmaya devam eder, hiçbiri değişmez. Bu, golden'ın Faz 3D'de
byte-identical kalmasını sağlayan şeydir.

`RESISTANCE_TAGS` ve `CATEGORIES` sabitleri **anahtar listesine** dönüştüğü için
`validate.php`'deki `isset(RESISTANCE_TAGS[$tag])` kontrolleri
`in_array($tag, RESISTANCE_KEYS, true)` olur.

### L3 — Metin tablosu biçimi

`data/locale/en.php` düz `key => string` döndürür; iç içe dizi yok, `sprintf`
yer tutucuları kullanılır.

```php
return [
    'site.tagline'        => 'Task-level verdicts on which jobs AI actually takes.',
    'verdict.safe.label'  => 'SAFE',
    'verdict.safe.blurb'  => 'The core of this job is structurally resistant. ...',
    'task.gone.label'     => 'gone',
    'tag.legal-liability' => 'A human must legally own the outcome and sign for it.',
    'category.tech'       => 'Tech & Engineering',
    'nav.timeline'        => 'Timeline',
    'job.h1'              => 'Will AI replace %ss?',        // %s = meslek adi
    'job.safeUntil'       => 'safe until <strong>~%s</strong>',
    // ...
];
```

**Eksik anahtar sessizce boş dönmez:** `t()` bulunamayan anahtarı olduğu gibi
döndürür ve `LOCALE_STRICT` açıksa (testlerde) `RuntimeException` atar. Böylece
eksik çeviri testte patlar, üretimde sayfayı boş bırakmaz.

### L4 — `intl` ve fallback

`intl` yalnızca ay adı ve sayı biçimlendirmede kullanılır. Kapalıyken
`data/locale/<lang>.php` içindeki `month.1` … `month.12` tablosu devreye girer ve
**aynı sonucu** üretir. Test bunu zorlayabilmek için:

```php
/**
 * intl var mi. Test icin ezilebilir — fallback yolu sinanmadan guvenilmez.
 * intl_available(false) ezer, intl_available(null) ezmeyi kaldirir.
 */
function intl_available(?bool $force = null): bool
{
    static $forced = null;
    if (func_num_args() > 0) {
        $forced = $force;
    }
    return $forced ?? extension_loaded('intl');
}
```

Kural: **`intl` açık ve kapalı sonuçlar birebir aynı olmalı.** Farklıysa fallback
tablosu yanlıştır ve test kırmızı verir. (`intl` daha zengin biçimler üretebilir;
bu yüzden Lang, `intl`'i yalnızca tablo ile aynı biçimi üretecek şekilde kullanır —
"1 de agosto de 2026" değil, "agosto de 2026".)

---

### Görev 3A — Locale iskeleti ve İngilizce (bağlamadan)

**Amaç**
`Base` sözleşmesini, `En` uygulamasını ve `data/locale/en.php` tablosunu yazmak.
İngilizce davranış bugünkü koddan **birebir** taşınır. Hiçbir çağrı yeri değişmez.

**Değiştirilecek dosyalar**
- Oluştur: `inc/lang/Base.php`, `inc/lang/En.php`, `inc/locale.php`,
  `data/locale/en.php`, `tests/lang.test.php`

**Arayüzler**
- Üretir: `lang_for(string $code): Lang`, `intl_available(?bool): bool`, ve
  `Lang` sözleşmesi:

```php
abstract class Lang
{
    /** @param array<string,string> $strings data/locale/<code>.php tablosu */
    public function __construct(protected array $strings) {}

    // --- Base'in kendisi uyguluyor: tablo okumasi her dilde ayni ---
    public function has(string $key): bool
    {
        return isset($this->strings[$key]);
    }

    public function t(string $key, mixed ...$args): string
    {
        if (!isset($this->strings[$key])) {
            if (defined('LOCALE_STRICT') && LOCALE_STRICT) {
                throw new RuntimeException("locale: eksik anahtar '$key'");
            }
            return $key;                       // sessiz bos donmez (L3)
        }
        $s = $this->strings[$key];
        return $args === [] ? $s : vsprintf($s, $args);
    }

    public function verdictLabel(string $key): string     { return $this->t("verdict.$key.label"); }
    public function verdictBlurb(string $key): string     { return $this->t("verdict.$key.blurb"); }
    public function taskVerdictLabel(string $key): string { return $this->t("task.$key.label"); }
    public function categoryLabel(string $key): string
    {
        return $this->has("category.$key") ? $this->t("category.$key") : $this->t('category.unknown');
    }
    public function tagDefinition(string $key): string
    {
        return $this->has("tag.$key") ? $this->t("tag.$key") : '';
    }

    // --- Dile gore DEGISEN davranis: alt siniflar uygular ---
    abstract public function code(): string;
    abstract public function month(string $ym): string;
    abstract public function listPhrase(array $items): string;
    abstract public function withArticle(string $word): string;
    abstract public function lowerFirst(string $text): string;
    abstract public function plural(string $word, int $n = 2): string;

    abstract public function geoAnswer(array $job): string;
    abstract public function faqPairs(array $job): array;
    /** URL PARAMETRE olarak gelir — Lang URL uretmez (L1). */
    abstract public function shareText(array $job, string $url): string;
    abstract public function evidenceNote(array $job): ?array;
}
```

> `shareText()` URL'i **parametre olarak alır**. Lang URL üretmez (L1).

- [ ] **Adım 1: Testleri yaz** (`tests/lang.test.php`, İngilizce bölümü)

Testler bugünkü fonksiyonların çıktısını **referans** alır: aynı girdi, aynı çıktı.

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/locale.php';
require_once __DIR__ . '/../inc/entry.php';

$en  = lang_for('en');
$job = load_entry('accountant', 'en');

// --- Sozlesme: Lang URL uretmez, dosya okumaz ---
t_eq('en', $en->code(), 'dil kodu');
t_eq(true, $en->has('verdict.safe.label'), 'anahtar var');
t_eq(false, $en->has('hicboyle.anahtar'), 'anahtar yok');

// --- Etiketler ve tanimlar ---
t_eq('SAFE',      $en->verdictLabel('safe'),        'verdict etiketi');
t_eq('SHRINKING', $en->verdictLabel('shrinking'),   'verdict etiketi 2');
t_eq('gone',      $en->taskVerdictLabel('gone'),    'gorev verdict etiketi');
t_eq('Tech & Engineering', $en->categoryLabel('tech'), 'kategori etiketi');
t_eq('A human must legally own the outcome and sign for it.',
     $en->tagDefinition('legal-liability'), 'tag tanimi');
t_eq('Uncategorised', $en->categoryLabel('hicboyle'), 'bilinmeyen kategori');

// --- Bicimlendirme: bugunku davranisla BIREBIR ---
t_eq('August 2026', $en->month('2026-08'), 'ay adi');
t_eq('',            $en->month('bozuk'),   'bozuk ay bos doner');
t_eq('a',           $en->listPhrase(['a']),           'tek ogeli liste');
t_eq('a and b',     $en->listPhrase(['a', 'b']),      'iki ogeli liste');
t_eq('a, b and c',  $en->listPhrase(['a', 'b', 'c']), 'uc ogeli liste');
t_eq('',            $en->listPhrase([]),              'bos liste');
t_eq('an accountant', $en->withArticle('accountant'), 'sesli harf artikeli');
t_eq('a lawyer',      $en->withArticle('lawyer'),     'sessiz harf artikeli');
t_eq('data entry',    $en->lowerFirst('Data entry'),  'ilk harf kucultme');
t_eq('CV screening',  $en->lowerFirst('CV screening'), 'kisaltma korunur');

// --- intl kapaliyken AYNI sonuc (L4) ---
intl_available(false);
t_eq('August 2026', $en->month('2026-08'), 'intl kapali: ay adi ayni');
intl_available(null);

// --- Uretilen cumleler ---
$geo = $en->geoAnswer($job);
t_eq(true, str_starts_with($geo, 'As of August 2026,'), 'geo tarihle basliyor');
t_eq(true, str_contains($geo, 'shrinking rather than disappearing'), 'geo verdict cumlesi');
$faq = $en->faqPairs($job);
t_eq('Will AI replace accountants?', $faq[0]['q'], 'ilk FAQ sorusu');
t_eq($geo, $faq[0]['a'], 'ilk FAQ cevabi geo paragrafi');

// --- shareText URL'i PARAMETRE alir (L1) ---
$share = $en->shareText($job, 'https://willaistealit.com/accountant');
t_eq(true, str_contains($share, 'https://willaistealit.com/accountant'), 'paylasim URL i');
t_eq(true, str_contains($share, 'SHRINKING'), 'paylasim verdict i');

// --- Eksik anahtar sessiz kalmaz (L3) ---
t_eq('hicboyle.anahtar', $en->t('hicboyle.anahtar'), 'eksik anahtar kendini doner');
```

- [ ] **Adım 2: Başarısız olduğunu doğrula** — `php tests/run.php` → `inc/locale.php` yok
- [ ] **Adım 3: `data/locale/en.php`, `Base.php`, `En.php`, `locale.php` yaz**

Metinler `inc/config.php` ve şablonlardan **kopyalanır**, yeniden yazılmaz. `En`
sınıfının `geoAnswer()`/`faqPairs()` gövdeleri bugünkü `geo_answer()`/`faq_pairs()`
kodunun taşınmış halidir; tek fark URL'i parametre alması.

- [ ] **Adım 4: Testler geçsin** — bu görevin eklediği **~26 assert**, toplamda `0 kaldi`
- [ ] **Adım 5: Bağlı olmadığını doğrula**

```bash
grep -rln "lang_for\|inc/locale.php" --include="*.php" . | grep -v "inc/lang/\|inc/locale.php\|tests/lang.test.php"
```
Beklenen: **çıktı yok**.

- [ ] **Adım 6: Commit**

```bash
git add inc/lang/Base.php inc/lang/En.php inc/locale.php data/locale/en.php tests/lang.test.php
git diff --cached --name-status
git commit -m "feat: add locale scaffolding and the English language class"
```

**Risk** Düşük — saf ek, hiçbir çağrı yeri değişmiyor.
**Doğrulama** `php tests/run.php && ./tools/smoke.sh`
**Beklenen** `0 kaldi` · `Matris temiz.`
**Rollback** `git revert` tek commit.
**Commit sınırı** Beş yeni dosya. `inc/config.php`, `inc/functions.php`, şablonlar **girmez**.

---

### Görev 3B — Türkçe ve İspanyolca (bağlamadan)

**Amaç**
`Tr` ve `Es` sınıflarını ve tablolarını yazmak. Dilbilgisi farkları burada yaşar.
**Hiçbir URL açılmaz** — `activeLangs` hâlâ `['en']`.

**Değiştirilecek dosyalar**
- Oluştur: `inc/lang/Tr.php`, `inc/lang/Es.php`, `data/locale/tr.php`, `data/locale/es.php`
- Değiştir: `tests/lang.test.php`

**Dilbilgisi farkları — sınıfların var olma sebebi**

| Davranış | EN | TR | ES |
|---|---|---|---|
| Liste bağlacı | `a, b and c` | `a, b ve c` | `a, b y c` — ama **`i`/`hi` ile başlayan kelimeden önce `e`** |
| Artikel | `a` / `an` | **yok** — kelime olduğu gibi döner | `el` / `la` (cinsiyet tabloda) |
| Çoğul | `+s` | **yok** — "yazılım geliştiriciler" gerekmiyor, başlık tekil kurulur | `+s` / `+es` |
| Ay | `August 2026` | `Ağustos 2026` | `agosto de 2026` |
| İlk harf küçültme | `Data entry` → `data entry` | **İ→i tuzağı**: `mb_strtolower('İ')` birleşen nokta üretir | aksanlı büyük harf |

**Terminoloji** (Faz 1'de onaylandı, locale'de merkezî):

| EN | TR | ES |
|---|---|---|
| SAFE / SHRINKING / ON THE MENU | GÜVENDE / DARALIYOR / MENÜDE | A SALVO / SE REDUCE / EN EL MENÚ |
| gone / going / safe | gitti / gidiyor / kalıyor | ya desapareció / está desapareciendo / resiste |

- [ ] **Adım 1: Testleri yaz** (`tests/lang.test.php` sonuna)

```php
// ================= TURKCE =================
$tr = lang_for('tr');
t_eq('tr', $tr->code(), 'TR dil kodu');
t_eq('GÜVENDE',   $tr->verdictLabel('safe'),        'TR verdict etiketi');
t_eq('DARALIYOR', $tr->verdictLabel('shrinking'),   'TR verdict etiketi 2');
t_eq('MENÜDE',    $tr->verdictLabel('on-the-menu'), 'TR verdict etiketi 3');
t_eq('gitti',     $tr->taskVerdictLabel('gone'),    'TR gorev verdict i');
t_eq('kalıyor',   $tr->taskVerdictLabel('safe'),    'TR gorev verdict i 2');

// Liste bagi ve artikel
t_eq('a, b ve c', $tr->listPhrase(['a', 'b', 'c']), 'TR liste bagi');
t_eq('a ve b',    $tr->listPhrase(['a', 'b']),      'TR iki ogeli liste');
t_eq('muhasebeci', $tr->withArticle('muhasebeci'),  'TR de artikel YOK');

// Ay adi + intl kapaliyken AYNI sonuc (L4)
t_eq('Ağustos 2026', $tr->month('2026-08'), 'TR ay adi');
t_eq('Ocak 2027',    $tr->month('2027-01'), 'TR ay adi 2');
intl_available(false);
t_eq('Ağustos 2026', $tr->month('2026-08'), 'TR intl kapali: ayni');
t_eq('Ocak 2027',    $tr->month('2027-01'), 'TR intl kapali: ayni 2');
intl_available(null);

// I/i tuzagi: mb_strtolower('İ') birlesen nokta uretir, eslesmeyi bozar
t_eq('işe alım', $tr->lowerFirst('İşe alım'), 'TR I harfi dogru kuculuyor');
t_eq('KDV beyanı', $tr->lowerFirst('KDV beyanı'), 'TR kisaltma korunur');

// ================= ISPANYOLCA =================
$es = lang_for('es');
t_eq('A SALVO',     $es->verdictLabel('safe'),        'ES verdict etiketi');
t_eq('SE REDUCE',   $es->verdictLabel('shrinking'),   'ES verdict etiketi 2');
t_eq('EN EL MENÚ',  $es->verdictLabel('on-the-menu'), 'ES verdict etiketi 3');
t_eq('resiste',     $es->taskVerdictLabel('safe'),    'ES gorev verdict i');

// y -> e kurali: i/hi ile baslayan kelimeden once
t_eq('a, b y c',            $es->listPhrase(['a', 'b', 'c']),          'ES liste bagi');
t_eq('padres e hijos',      $es->listPhrase(['padres', 'hijos']),      'ES hi- once e');
t_eq('agujas e hilo',       $es->listPhrase(['agujas', 'hilo']),       'ES i- once e');
t_eq('cobre y hierro',      $es->listPhrase(['cobre', 'hierro']),      'ES hie- istisnasi: y kalir');

t_eq('agosto de 2026', $es->month('2026-08'), 'ES ay adi');
intl_available(false);
t_eq('agosto de 2026', $es->month('2026-08'), 'ES intl kapali: ayni');
intl_available(null);

// ================= UC DIL ORTAK =================
foreach ([$en, $tr, $es] as $L) {
    $c = $L->code();
    t_eq('', $L->month('bozuk'), "$c: bozuk ay bos doner");
    t_eq('', $L->listPhrase([]),  "$c: bos liste");
    foreach (['safe', 'shrinking', 'on-the-menu'] as $v) {
        t_eq(true, $L->verdictLabel($v) !== '', "$c: $v etiketi dolu");
        t_eq(true, $L->verdictBlurb($v) !== '', "$c: $v aciklamasi dolu");
    }
    foreach (CATEGORY_KEYS as $k) {
        t_eq(true, $L->has('category.' . $k), "$c: '$k' kategorisi cevrilmis");
    }
    foreach (RESISTANCE_KEYS as $k) {
        t_eq(true, $L->has('tag.' . $k), "$c: '$k' tag i cevrilmis");
    }
}

// TR/ES gercek entry uzerinde cumle uretebiliyor mu (veri Faz 2'de indi)
$trJob = load_entry('cashier', 'tr');
t_eq(true, $trJob !== null, 'TR cashier yuklenebiliyor');
t_eq(true, str_contains($tr->geoAnswer($trJob), 'Ağustos 2026'), 'TR geo tarihi');
t_eq(true, $tr->faqPairs($trJob)[0]['q'] !== '', 'TR FAQ sorusu dolu');
```

> **Kapsam tarama döngüsü kritik:** `CATEGORY_KEYS` ve `RESISTANCE_KEYS` üzerinde
> dönerek her anahtarın üç dilde de çevrildiğini doğruluyor. Eksik çeviri sessiz
> boşluk değil, kırmızı test olur.

- [ ] **Adım 2–4:** kırmızıyı gör, `Tr.php`/`Es.php` + tabloları yaz, yeşile al
  (bu görevin eklediği **~60 assert**)
- [ ] **Adım 5: `activeLangs` değişmediğini doğrula**

```bash
php -r 'require "inc/routes_cache.php"; echo implode(",", load_routes()["activeLangs"]), "\n";'   # en
./tools/smoke.sh | grep -c "HATA" || echo "matris temiz"
```

- [ ] **Adım 6: Commit**

```bash
git add inc/lang/Tr.php inc/lang/Es.php data/locale/tr.php data/locale/es.php tests/lang.test.php
git diff --cached --name-status
git commit -m "feat: add Turkish and Spanish language classes"
```

**Risk** Düşük — hâlâ hiçbir çağrı yeri bunları kullanmıyor.
**Rollback** `git revert` tek commit.

---

### Görev 3C — Golden yakalama · **KAPI NOKTASI**

**Amaç**
İngilizce çıktının bugünkü halini **dondurmak**. Bundan sonraki her görev bu
referansa karşı doğrulanır. Bu commit davranış değiştirmez; **burada durulur ve
sonuç sunulur** (global kısıt).

**Değiştirilecek dosyalar**
- Oluştur: `tools/golden.php`, `tests/golden/` (yakalanmış çıktı)
- Değiştir: `.gitignore` (yok — goldenlar **takip edilir**, referans olmalarının şartı bu)

**Uygulama ayrıntısı**

`tools/golden.php` iki katman uygular (spec §12.2):

```bash
./tools/golden.sh --capture   # sunucuyu kaldirir, yakalar, kapatir
./tools/golden.sh --check     # yeniden uretir, iki katmanda karsilastirir
```

**Katman 1 — byte/golden:** ham gövde `tests/golden/<ad>.<uzanti>`'ye yazılır
(HTML `.html`, metin `.txt`, XML `.xml`). Byte katmanı içerik türünden bağımsızdır.

**Katman 2 — semantik: çıkarıcı içerik türüne göre seçilir.** HTML çıkarıcısını
`llms.txt` ve `sitemap.xml`'e uygulamak yanlış olur — ikisi HTML değil.

| Tür | Hedefler | Semantik olarak karşılaştırılan |
|---|---|---|
| **HTML** | 11 sayfa | status · content-type · canonical · `<title>` · meta description · H1 · `<html lang>` · verdict etiketi · görev sayısı · görev adları+verdict'leri (sıralı) · JSON-LD (anahtarlar sıralanmış, normalize) · tüm `<a href>` hedefleri (sıralı, tekil) |
| **düz metin** | `/llms.txt` | status · content-type · **gövde** (satır sonları normalize, içerik birebir) |
| **XML** | `/sitemap.xml` | status · content-type · XML **parse edilerek**: her `<url>` için `loc`, `lastmod`, `changefreq`, `priority` ve varsa `xhtml:link` alternatifleri (sıralı) |
| **binary** | `/og/cashier.png` `/og/home.png` | status · content-type · **md5** |

```php
/** Icerik turune gore cikarici sec. HTML cikaricisi XML/metne UYGULANMAZ. */
function golden_extract(string $name, array $res): array
{
    $ct = strtolower($res['contentType']);
    if (str_contains($ct, 'text/html')) {
        return golden_extract_html($res);
    }
    if (str_contains($ct, 'xml')) {
        return golden_extract_sitemap($res);
    }
    if (str_contains($ct, 'image/')) {
        return ['status' => $res['status'], 'contentType' => $ct, 'md5' => md5($res['body'])];
    }
    return golden_extract_text($res);
}
```

Whitespace normalizasyonu **yalnızca semantik katmanda** yapılır. Byte katmanı ham kalır.

**Yakalanacak sayfalar** — verdict ve alan çeşitliliğini kapsayacak şekilde seçilmiş:

| Sayfa | Neden |
|---|---|
| `/` | ana sayfa, faset sayaçları, `homeAnswer`, JSON-LD |
| `/cashier` | 🔴 on-the-menu, `geoAnswer` **override'lı**, 3 dilli entry |
| `/accountant` | 🟡 shrinking, `safeUntil` var, üretilen geoAnswer |
| `/nurse` | 🟢 safe, **`safeUntil` yok**, farklı cümle dalı |
| `/translator` | 🔴, `evidenceStrength` farklı |
| `/methodology` `/landscape` `/changelog` `/sponsor` | sabit sayfalar |
| `/llms.txt` `/sitemap.xml` | üretilen metin dosyaları |
| `/unknown` | 404 |
| `/og/cashier.png` `/og/home.png` | **md5** — verdict etiketi karta basılıyor |

OG kartları binary olduğu için md5 yakalanır; Faz 3'te İngilizce etiket değişmediği
için md5 de değişmemeli.

**Sayım:** 11 HTML + 1 düz metin + 1 XML + 2 binary = **15 hedef**. Her biri için bir
byte dosyası ve bir semantik JSON.

- [ ] **Adım 1: `tools/golden.php` ve `tools/golden.sh` yaz**

`golden.sh` dört mod taşır: `--capture`, `--check [--semantic]`, `--cache-check`
(Görev 3D'de kullanılır) ve doğrudan `golden.php --self-test`. Sunucu yönetimi
`smoke.sh` ile aynı desende: PID, `trap cleanup EXIT INT TERM`, gerçek hazır-olma
yoklaması, başarısızlıkta çıkış kodu 1.

- [ ] **Adım 2: Yakala**

```bash
php -r 'require "inc/functions.php"; clear_cache();'   # sablondan uretilsin
./tools/golden.sh --capture
ls tests/golden/ | wc -l                               # 30 (15 govde + 15 semantik)
```

- [ ] **Adım 3: `--check` kendi yakaladığına karşı temiz mi**

```bash
./tools/golden.sh --check; echo "cikis: $?"
```
Beklenen: `15/15 byte-identical · 15/15 semantik ayni`, çıkış 0.

- [ ] **Adım 4: Golden'ın gerçekten koruduğunu KANITLA**

Kasıtlı bir bozma yapılır ve `--check`'in yakaladığı görülür:

```bash
./tools/golden.php --self-test; echo "cikis: $?"
```

`--self-test` **hiçbir kaynak dosyaya dokunmaz.** Yakalanmış golden'ları okur, kopyayı
**bellekte** bozar ve karşılaştırıcının bozulmayı reddettiğini kanıtlar. `sed`/`.bak`/`mv`
dizisi yok; iş yarıda kesilse bile çalışma ağacı bozulmaz.

```php
/**
 * Karsilastiricinin gercekten kirmizi verebildigini kanitlar.
 * Hicbir kaynak dosya DEGISMEZ: golden kopyasi bellekte bozulur.
 * Hicbir zaman kirmizi veremeyen bir golden, her degisikligi onaylar.
 */
function golden_self_test(): int
{
    $cases = [
        // [golden adi, content-type, bozma islevi, beklenen fark alani]
        ['cashier', 'text/html',
         static fn (string $b): string => str_replace('SHRINKING', 'SHRINKING!', $b),
         'verdictLabel'],
        ['sitemap', 'application/xml',
         static fn (string $b): string => preg_replace('#<priority>0\.9</priority>#', '<priority>0.8</priority>', $b, 1) ?? $b,
         'urls'],
        ['llms', 'text/plain',
         static fn (string $b): string => $b . "\nBOZULDU\n",
         'body'],
        ['og-cashier', 'image/png',
         static fn (string $b): string => $b . 'x',
         'md5'],
    ];

    $fail = 0;
    foreach ($cases as [$name, $ct, $corrupt, $field]) {
        $body = (string)file_get_contents(golden_body_path($name));
        $good = golden_extract($name, ['status' => 200, 'contentType' => $ct, 'body' => $body]);
        $bad  = golden_extract($name, ['status' => 200, 'contentType' => $ct, 'body' => $corrupt($body)]);

        // 1) Bozulmamis kopya kendisiyle AYNI olmali (yalanci kirmizi olmasin)
        if (golden_diff($good, $good) !== []) {
            printf("  HATA %-12s bozulmamis kopya farkli gorunuyor\n", $name);
            $fail++;
            continue;
        }
        // 2) Bozulmus kopya REDDEDILMELI, ve fark beklenen alanda olmali
        $diff = golden_diff($good, $bad);
        if ($diff === []) {
            printf("  HATA %-12s bozulma YAKALANMADI (%s)\n", $name, $field);
            $fail++;
        } elseif (!in_array($field, $diff, true)) {
            printf("  HATA %-12s yakalandi ama yanlis alanda: %s\n", $name, implode(',', $diff));
            $fail++;
        } else {
            printf("  ok   %-12s bozulma '%s' alaninda yakalandi\n", $name, $field);
        }
    }

    echo $fail === 0 ? "\nKarsilastirici dort icerik turunde de kirmizi verebiliyor.\n"
                     : "\n$fail vaka basarisiz.\n";
    return $fail === 0 ? 0 : 1;
}
```

Beklenen çıktı:

```
  ok   cashier      bozulma 'verdictLabel' alaninda yakalandi
  ok   sitemap      bozulma 'urls' alaninda yakalandi
  ok   llms         bozulma 'body' alaninda yakalandi
  ok   og-cashier   bozulma 'md5' alaninda yakalandi

Karsilastirici dort icerik turunde de kirmizi verebiliyor.
```

Dört içerik türünün dördü de kapsanıyor — HTML çıkarıcısı yeşil verirken XML
çıkarıcısının sessizce her şeyi onaylaması mümkün değil.

**Hiçbir zaman kırmızı veremeyen bir golden, her değişikliği onaylar.** Bu adım
atlanamaz.

- [ ] **Adım 5: Commit**

```bash
git add tools/golden.php tools/golden.sh tests/golden
git diff --cached --name-status
git commit -m "test: freeze the English output as a golden reference"
```

**Risk** Düşük — araç ve veri, davranış yok.
**Rollback** `git revert` tek commit.

> ### ⏸ KAPI: burada durulur
>
> Sonraki görev İngilizce çıktının üretim yolunu değiştirir. Devam etmeden önce
> şunlar sunulur: yakalanan sayfa listesi, `--check` temiz sonucu, ve **kasıtlı
> bozma denemesinin kırmızı verdiği kanıtı**. Onay alınmadan Görev 3D'ye geçilmez.

---

### Görev 3D — Erişimciler locale'e devreder

**Amaç**
`inc/config.php`'den dile bağlı metinleri çıkarmak ve erişimci fonksiyonları locale'e
devretmek. **Golden byte-identical kalmalı.**

**Değiştirilecek dosyalar**
- Değiştir: `inc/config.php` (metinler çıkar, anahtar+renk kalır)
- Değiştir: `inc/functions.php` (`verdict_meta`, `task_verdict_meta`, `category_label`,
  `tag_definition`, **`template_mtime()`**)
- Değiştir: `tools/validate.php` (`RESISTANCE_TAGS`/`CATEGORIES` → `*_KEYS`)
- Değiştir: `methodology.php` (tag ve verdict tanımlarını basıyor)

```php
function verdict_meta(?string $verdict, string $lang = DEFAULT_LANG): array
{
    $key  = isset(VERDICTS[$verdict]) ? (string)$verdict : 'shrinking';
    $L    = lang_for($lang);
    // Dilden bagimsiz (config) + dile bagli (locale) BIRLESIK doner —
    // sablonlar $v['label'] yazmaya devam eder, hicbiri degismez.
    return VERDICTS[$key] + [
        'key'   => $key,
        'label' => $L->verdictLabel($key),
        'blurb' => $L->verdictBlurb($key),
    ];
}
```

- [ ] **Adım 1: `template_mtime()`'ı genişlet — cache bayatlığının kapandığı yer**

Bugünkü hali yalnızca `glob(ROOT . '/inc/*.php')` bakıyor. `inc/lang/` bir **alt
dizin** olduğu için o glob'a girmiyor, `data/locale/` ise hiç bakılmıyor. Bu commit'ten
itibaren sayfa çıktısı bu iki yere bağımlı: genişletilmezse bir locale metni
düzeltildiğinde **eski sayfa servis edilmeye devam eder** ve kimse fark etmez.

```php
/** Sablon tarafinin dosya listesi — test bunu okuyabilsin diye ayri. */
function template_files(): array
{
    $files = [ROOT . '/job.php'];
    foreach ([ROOT . '/inc/*.php',
              ROOT . '/inc/lang/*.php',        // alt dizin: eski glob'a GIRMIYORDU
              ROOT . '/data/locale/*.php'] as $pattern) {
        foreach (glob($pattern) ?: [] as $f) {
            $files[] = $f;
        }
    }
    return $files;
}

function template_mtime(): int
{
    static $t = null;
    if ($t !== null) {
        return $t;
    }
    return $t = max(array_map('filemtime', template_files()));
}
```

- [ ] **Adım 2: Bağımlılık testi**

```php
// tests/lang.test.php
$files = template_files();
$has = static fn (string $needle): bool => (bool)array_filter(
    $files, static fn ($f) => str_contains($f, $needle));
t_eq(true, $has('/inc/lang/En.php'),      'sablon bagimliligi: dil sinifi');
t_eq(true, $has('/data/locale/en.php'),   'sablon bagimliligi: locale tablosu');
t_eq(true, $has('/inc/functions.php'),    'sablon bagimliligi: functions');
t_eq(true, $has('/job.php'),              'sablon bagimliligi: job.php');
```

- [ ] **Adım 3: Cache gerçekten geçersizleşiyor mu — HTTP seviyesinde**

```bash
./tools/golden.sh --cache-check; echo "cikis: $?"
```

Beklenen: `cache YENIDEN yazildi (dogru)` ve **çıkış kodu 0**. Başarısızlıkta
çıkış kodu **1**'dir; ekrana yazmak yetmez, adım kırmızı verir. Bu adım geçmeden
commit atılmaz — bayat cache sessizdir, testler yeşil kalır ve hata ancak canlıda
görülür.

`tools/golden.sh` bu modu kendi sunucusuyla, `smoke.sh` ile aynı desende çalıştırır:
PID ile süreç yönetimi, gerçek hazır-olma yoklaması, `trap` ile temizlik.

```bash
#!/usr/bin/env bash
# ./tools/golden.sh --capture | --check [--semantic] | --cache-check
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${GOLDEN_PORT:-8124}"
BASE="http://127.0.0.1:${PORT}"
LOG="$(mktemp -t wais-golden.XXXXXX)"
SRV=""

cleanup() {
  [ -n "$SRV" ] && kill "$SRV" 2>/dev/null
  rm -f "$LOG"
  return 0
}
trap cleanup EXIT INT TERM

# macOS ve Linux'ta calisan mtime
mtime() { stat -f %m "$1" 2>/dev/null || stat -c %Y "$1"; }

start_server() {
  php -S "127.0.0.1:${PORT}" -t "$ROOT" "${ROOT}/router.php" > "$LOG" 2>&1 &
  SRV=$!
  for _ in $(seq 1 60); do
    curl -sf -o /dev/null "${BASE}/" && return 0
    kill -0 "$SRV" 2>/dev/null || break
    sleep 0.25
  done
  echo "HATA: sunucu ${PORT} portunda ayaga kalkmadi"
  cat "$LOG"
  return 1
}

clear_page_cache() {
  php -r 'require "'"${ROOT}"'/inc/functions.php"; clear_cache();' > /dev/null
}

case "${1:---check}" in
  --cache-check)
    clear_page_cache
    start_server || exit 1

    CACHE="${ROOT}/cache/pages/en/cashier.html"
    curl -sf -o /dev/null "${BASE}/cashier" || { echo "HATA: /cashier alinamadi"; exit 1; }
    [ -f "$CACHE" ] || { echo "HATA: cache yazilmadi — write_page_cache bozuk"; exit 1; }
    BEFORE="$(mtime "$CACHE")"

    # filemtime saniye hassasiyetinde: dokunmadan once bir saniyeden fazla bekle
    sleep 1.1
    touch "${ROOT}/data/locale/en.php"

    curl -sf -o /dev/null "${BASE}/cashier" || { echo "HATA: ikinci istek basarisiz"; exit 1; }
    AFTER="$(mtime "$CACHE")"

    if [ "$AFTER" -gt "$BEFORE" ]; then
      echo "cache YENIDEN yazildi (dogru): $BEFORE -> $AFTER"
      exit 0
    fi
    echo "HATA: bayat cache servis edildi — template_mtime() locale dosyalarini gormuyor"
    exit 1
    ;;
  --capture|--check)
    clear_page_cache
    start_server || exit 1
    php "${ROOT}/tools/golden.php" "$@" "$BASE"
    exit $?
    ;;
  *)
    echo "kullanim: golden.sh --capture | --check [--semantic] | --cache-check"
    exit 2
    ;;
esac
```

> `--self-test` sunucu gerektirmez (yalnızca yakalanmış dosyaları okur), o yüzden
> doğrudan `./tools/golden.php --self-test` ile çalışır.

- [ ] **Adım 4: Kalan değişiklikleri uygula**
- [ ] **Adım 5: Golden byte-identical mi**

```bash
php -r 'require "inc/functions.php"; clear_cache();' && ./tools/golden.sh --check; echo "cikis: $?"
```
Beklenen: **15/15 byte-identical**, çıkış 0. Tek byte farkı bile çıkarsa metin
kopyalanırken değişmiş demektir; düzeltilmeden ilerlenmez.

- [ ] **Adım 6: Tam kontrol** — `php tests/run.php`, `php tools/validate.php`, `./tools/smoke.sh`
- [ ] **Adım 7: Commit**

```bash
git add inc/config.php inc/functions.php tools/validate.php methodology.php
git diff --cached --name-status
git commit -m "refactor: move verdict and category text into the locale tables"
```

**Risk** Orta — `VERDICTS` yapısı değişiyor ve 6 dosya okuyor. Golden byte katmanı
tam bu yüzden var.
**Rollback** `git revert <hash>`; golden referansı commit'te kaldığı için geri dönüş
doğrulanabilir.

---

### Görev 3E — Cümle üreticiler locale'e taşınır

**Amaç**
`geo_answer()`, `faq_pairs()`, `pretty_month()`, `list_phrase()`, `with_article()`,
`lower_first()`, `share_text()`, `evidence_note()` gövdelerini `Lang` sınıflarına
taşımak. Fonksiyonlar **imzalarını koruyup** devreder. **Golden byte-identical.**

**Değiştirilecek dosyalar**
- Değiştir: `inc/functions.php` (sekiz fonksiyon ince kabuk olur; `job_url()`/`og_url()`
  `url_for()`'a devreder)
- Değiştir: `job.php`, `sitemap.php`, `og.php` (`job_url`/`og_url` çağrıları kimlik alır)
- Değiştir: `tests/lang.test.php` (devretme + üç dilde URL testleri)

> **Döngüsel require uyarısı:** `functions.php` artık `routes_cache.php`'yi
> (`load_routes`) ve `urls.php`'yi (`url_for`) gerektiriyor; ikisi de `functions.php`'yi
> gerektiriyor. `require_once` bunu çözer ama **iki yükleme sırası da sınanmalı** —
> Faz 2'de `entry.php` için yaptığımız gibi:
> ```bash
> php -r 'require "inc/functions.php"; echo share_text(load_entry("cashier","en"),"en"), "\n";' | head -1
> php -r 'require "inc/urls.php"; require "inc/functions.php"; echo "ok\n";'
> ```

```php
function geo_answer(array $job, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->geoAnswer($job);
}

function share_text(array $job, string $lang = DEFAULT_LANG): string
{
    // URL KIMLIK ve DIL uzerinden uretilir. $job['slug'] YERELDIR: TR'de 'kasiyer'
    // olur ve job_url() prefix eklemedigi icin /kasiyer gibi YANLIS bir koke isaret
    // eder. Tek dogru kaynak url_for() (Faz 1B).
    $url = url_for($lang, 'job', (string)$job['id'], load_routes());
    return lang_for($lang)->shareText($job, $url);   // Lang URL'i PARAMETRE alir (L1)
}

/** Geriye uyumluluk: eski job_url()/og_url() de tek kaynaga devreder. */
function job_url(string $id, string $lang = DEFAULT_LANG): string
{
    return url_for($lang, 'job', $id, load_routes());
}

function og_url(string $id, string $lang = DEFAULT_LANG): string
{
    return url_for($lang, 'og', $id, load_routes());
}
```

- [ ] **Adım 1: Taşı ve devret**
- [ ] **Adım 2: Golden byte-identical** — `./tools/golden.sh --check` → 15/15
- [ ] **Adım 3: Üç dilde cümle ve URL testleri** (bu görevin eklediği **~20 assert**)

Paylaşım metnindeki URL'in **yerel slug'ı değil, dilin kanonik URL'ini** taşıdığı
üç dilde de doğrulanır:

```php
$routes = load_routes();
$cash   = load_entry('cashier', 'en');

t_eq(true, str_contains(share_text($cash, 'en'), 'https://willaistealit.com/cashier'),
     'EN paylasim URL i prefix siz');

// TR/ES icerigi Faz 2'de indi; activeLangs hala ['en'] ama URL URETIMI dile gore calisir.
t_eq('https://willaistealit.com/tr/kasiyer',
     url_for('tr', 'job', 'cashier', $routes), 'TR kanonik URL');
t_eq('https://willaistealit.com/es/cajero',
     url_for('es', 'job', 'cashier', $routes), 'ES kanonik URL');

$trShare = share_text(load_entry('cashier', 'tr'), 'tr');
t_eq(true,  str_contains($trShare, 'https://willaistealit.com/tr/kasiyer'), 'TR paylasim URL i');
t_eq(false, str_contains($trShare, 'willaistealit.com/kasiyer'), 'TR yerel slug KOKE yazilmaz');

$esShare = share_text(load_entry('cashier', 'es'), 'es');
t_eq(true,  str_contains($esShare, 'https://willaistealit.com/es/cajero'), 'ES paylasim URL i');

// job_url() artik KIMLIK alir ve dile gore prefix uretir
t_eq('https://willaistealit.com/cashier',        job_url('cashier'),       'job_url EN');
t_eq('https://willaistealit.com/tr/kasiyer',     job_url('cashier', 'tr'), 'job_url TR');
t_eq('https://willaistealit.com/og/cashier.png', og_url('cashier'),        'og_url EN');
t_eq('https://willaistealit.com/og/tr/kasiyer.png', og_url('cashier', 'tr'), 'og_url TR');
```

> `str_contains($trShare, 'willaistealit.com/kasiyer')` **false** olmalı — bu assert
> tam olarak planın ilk sürümündeki hatayı yakalar.
- [ ] **Adım 4: Commit**

```bash
git add inc/functions.php inc/lang/Base.php inc/lang/En.php inc/lang/Tr.php inc/lang/Es.php tests/lang.test.php
git diff --cached --name-status
git commit -m "refactor: move sentence generation into the language classes"
```

**Risk** Orta-yüksek — `geo_answer()` ve `faq_pairs()` GEO/SEO çıktısının kalbi.
Golden'ın semantik katmanı JSON-LD'yi de karşılaştırdığı için sessiz bozulma yakalanır.

---

### Görev 3F — Şablonlar: sabit metinler ve URL üretimi

**Amaç**
Şablonlardaki sabit İngilizce metinleri `t()`'ye, elle kurulan URL'leri `url_for()`'a
taşımak, `$lang`'ı şablonlara geçirmek. **Golden: byte katmanı serbest, semantik
katman zorunlu.**

Bu görev whitespace ve öznitelik sırası değiştirebilir (PHP etiketleri metnin yerini
alıyor). Spec §12.2 gereği ölçüt semantiktir: *"anlamsız whitespace farkı hata
sayılmaz; kullanıcıya görünen veya SEO anlamını değiştiren fark hata sayılır."*

**Değiştirilecek dosyalar**
`inc/header.php`, `inc/footer.php`, `job.php`, `index.php`, `methodology.php`,
`landscape.php`, `changelog.php`, `sponsor.php`, `llms.php`, `404.php`,
`unavailable.php`

**Uygulama sırası** — her dosya ayrı adım, her adımdan sonra `--check --semantic`:

- [ ] `inc/header.php` + `inc/footer.php` (navigasyon, skip link, footer metinleri)
- [ ] `job.php` (blok başlıkları, "Task breakdown", "Receipts", "Share the verdict"…)
- [ ] `index.php` (faset başlıkları, sütun adları, boş durum)
- [ ] `methodology.php` · `landscape.php` · `changelog.php` · `sponsor.php`
- [ ] `404.php` · `unavailable.php` · `llms.php`

**URL'ler:** `href="/<?= $slug ?>"` biçimindeki her yer `url_for($lang, 'job', $id, $routes)`
olur. `job_url()` ve `og_url()` de `url_for()`'a devreder — iki ayrı URL kaynağı kalmaz.

- [ ] **Son adım: Semantik golden**

```bash
php -r 'require "inc/functions.php"; clear_cache();' && ./tools/golden.sh --check --semantic; echo "cikis: $?"
```
Beklenen: **15/15 semantik aynı**. Byte farkları raporlanır ama hata sayılmaz;
rapor gözle incelenir (beklenen: yalnızca boşluk/satır sonu).

- [ ] **Commit** (dosya grubu başına ayrı commit; her biri semantik golden'dan geçmeli)

**Risk** Yüksek — 11 şablon, 1228 satır. Azaltıcı: dosya başına ayrı adım ve her
adımdan sonra semantik kontrol; bir dosya kırmızı verirse yalnızca o geri alınır.

---

### Görev 3G — Faz 3 kapanışı

- [ ] `php tests/run.php` → `0 kaldi`, **uyarı 0**
- [ ] `php tools/validate.php` → `Hata yok`
- [ ] `php tools/build-index.php` → 17 entry
- [ ] `php tools/doctor.php` → kritik hata yok
- [ ] `./tools/golden.sh --check --semantic` → 15/15, çıkış 0
- [ ] `./tools/golden.php --self-test` → dört içerik türünde de kırmızı verebiliyor
- [ ] `./tools/golden.sh --cache-check` → çıkış 0
- [ ] `./tools/smoke.sh` → `Matris temiz.`
- [ ] `git status --short` → boş
- [ ] `activeLangs` hâlâ `['en']`, `/tr/*` 404
- [ ] `inc/config.php`'de dile bağlı metin kalmadı:
      `grep -nE "'(label|blurb)' =>" inc/config.php` → çıktı yok
- [ ] Üç dilde de tam kapsam: `CATEGORY_KEYS` ve `RESISTANCE_KEYS`'in her anahtarı
      üç tabloda da var (test döngüsü zaten kanıtlıyor)

---

## Commit haritası

| # | Commit | Görev | Davranış |
|---|---|---|---|
| 1 | `feat: add locale scaffolding and the English language class` | 3A | — |
| 2 | `feat: add Turkish and Spanish language classes` | 3B | — |
| 3 | `test: freeze the English output as a golden reference` | 3C | — |
| — | **⏸ KAPI — durulur, sonuç sunulur** | | |
| 4 | `refactor: move verdict and category text into the locale tables` | 3D | **evet** |
| 5 | `refactor: move sentence generation into the language classes` | 3E | **evet** |
| 6–9 | `refactor: localise <sablon grubu>` | 3F | **evet** |

Commit 1–3 davranış değiştirmez. 4'ten itibaren her commit golden'dan geçmek
zorundadır; geçmeyen commit **atılmaz**.

## Riskler

1. **Golden yalancı-yeşil olabilir.** Görev 3C Adım 4 kasıtlı bozma ile bunu
   kanıtlamadan devam edilmez.
2. **Metin kopyalarken sessiz değişiklik.** 3D/3E'de byte katmanı tek byte farkını
   bile yakalar; bu yüzden o iki görevde byte katmanı **zorunlu**, 3F'de değil.
3. **`intl` biçim farkı.** `intl` "1 de agosto de 2026" üretebilir, tablo
   "agosto de 2026". Lang, `intl`'i tabloyla **aynı** biçimi üretecek şekilde
   kullanır; test iki yolu karşılaştırır.
4. **Türkçe `İ` tuzağı.** `mb_strtolower('İ')` birleşen nokta üretir. `Tr::lowerFirst()`
   önce harf haritası uygular. Testi var.
5. **Şablon churn'ü.** 3F 11 dosyaya dokunuyor; dosya başına adım ve semantik kontrol
   bunu bölüyor.

## Faz 3'ün DEĞİŞTİRMEDİKLERİ

- **`activeLangs` `['en']`.** TR/ES URL'leri **açılmaz** — Faz 4.
- `sitemap.php`'nin `xhtml:link` alternatifleri, dil seçici, `hreflang` blokları — **Faz 4**.
- Dile özgü OG kartları (`/og/tr/...`) — **Faz 4**.
- Arama harf katlaması (`data/search-fold.json`), dil başına arama indeksi — **Faz 4**.
- `content-version.json` (spec §8.2) — **Faz 4**. Locale dosyalarının cache
  bağımlılığına katılması ise **Faz 3'e ertelenmez**: `template_mtime()` Görev 3D
  Adım 1'de genişletilir ve Adım 3'te HTTP seviyesinde doğrulanır.
- Veri şeması — Faz 2'de tamamlandı, dokunulmaz.

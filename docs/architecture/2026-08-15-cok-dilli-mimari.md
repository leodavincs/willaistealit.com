# Çok Dilli Mimari (EN · TR · ES) — Tasarım

**Tarih:** 15 Ağustos 2026
**Durum:** Onaylandı — uygulama planı bekleniyor
**Kapsam:** Mimari — uygulama planı ayrıca yazılacak

## Problem

Site tek dilde (İngilizce) kurulmuş ve dil, mimarinin her katmanına gömülü:

- **URL'de:** `^([a-z0-9-]+)/?$` tek seviyeli; dil prefix'i kavramı yok.
- **Veride:** `data/jobs/<slug>.json` düz dosya. Tek `titleTr` alanı 15 entry'nin
  hepsinde var ama yalnızca başlık ve arama için kullanılıyor — dil karmaşası yaratıyor,
  Türkçe içerik varmış izlenimi veriyor, oysa yok.
- **Kodda:** `VERDICTS`, `CATEGORIES`, `RESISTANCE_TAGS` etiketleri `inc/config.php`
  sabitlerinde. `geo_answer()`, `faq_pairs()`, `pretty_month()`, `with_article()`,
  `list_phrase()` İngilizce **cümle üretiyor** — bunlar çeviri değil, dil başına
  yeniden yazım gerektirir (Türkçede artikel yok, `-s` çoğul eki yok, ay adları farklı).
- **Routing'de:** kurallar iki yerde — `.htaccess` (üretim) ve `router.php` (lokal).
  Tek dilde idare edilir; üç dil × yerelleştirilmiş sabit sayfa slug'ları ile
  kaçınılmaz olarak ayrışır ve lokalde çalışan bir şey üretimde 404 verir.

Hedef: siteyi üç dile açarken yalın PHP + JSON mimarisini korumak, framework veya
veritabanı eklememek, ve dillerin ileride birbirinden **bağımsız yargı** taşımasına
baştan izin veren bir veri modeli kurmak.

## Kapsam dışı

- **Arapça / RTL.** Bilinçli erteleme: RTL düzeni, lehçe seçimi ve yerel iş piyasası
  araştırması ayrı bir maliyet. Dördüncü dil olarak sonra değerlendirilir.
- **Yönetim paneli / çeviri arayüzü.** İçerik JSON'da kalır, PR ile değişir.
- **Otomatik makine çevirisi hattı.** Entry metni editoryal üründür.
- **Coğrafi konuma göre otomatik yönlendirme.** Açıkça reddedildi (aşağıda).
- **Veritabanı, framework, build adımı.** `docs/memory/decisions/2026-08-15-stack-php-json`
  kararı geçerliliğini koruyor.

---

## 1. URL ve routing

### 1.1 Kanonik biçim

İngilizce prefix'siz kalır. Bu bilinçli bir SEO kararıdır: kök adres mevcut backlink,
Search Console geçmişi ve indeks değerini taşıyor; simetri uğruna en güçlü URL taşınmaz.

```
/                              EN ana sayfa      canonical: /
/tr/                           TR ana sayfa
/es/                           ES ana sayfa
/software-developer            EN entry
/tr/yazilim-gelistirici        TR entry
/es/desarrollador-de-software  ES entry
/methodology                   EN sabit sayfa
/tr/metodoloji                 TR sabit sayfa
```

**Eğik çizgi kuralı, tek cümle:** eğik çizgi yalnızca dil ana sayfasında bulunur,
başka hiçbir yerde bulunmaz.

Sabit sayfa slug'ları:

| id | EN | TR | ES |
|---|---|---|---|
| `methodology` | `/methodology` | `/tr/metodoloji` | `/es/metodologia` |
| `landscape` | `/landscape` | `/tr/zaman-cizelgesi` | `/es/linea-de-tiempo` |
| `changelog` | `/changelog` | `/tr/degisiklikler` | `/es/cambios` |
| `sponsor` | `/sponsor` | `/tr/sponsorluk` | `/es/patrocinio` |

`en`, `tr`, `es` rezerve kelimedir; meslek slug'ı olamazlar.

### 1.2 Tek giriş noktası

`.htaccess` ve `router.php` artık URL kuralı **içermez**. İkisi de gerçek dosya
bulunmadığında isteği tek bir çözücüye devreder:

```
.htaccess   → gerçek dosya değilse → route.php
router.php  → lokalde aynı şeyi yapar (built-in server için)
route.php   → URL'i çözen TEK yer
```

Front controller'a geçmenin gerekçesi tam olarak budur: lokal ile üretim arasındaki
kural ayrışmasını yapısal olarak imkânsız kılmak.

### 1.3 Çözümleme sırası

Bir `<lang>/<slug>` isteği (veya prefix'siz `<slug>` → EN) şu sırayla çözülür:

1. **O dilin canonical slug'ı mı?** → sayfayı bas (200)
2. **Bilinen bir kimlik mi?** Yani şunlardan biri:
   - meslek `id`'si,
   - **başka bir dilin** canonical slug'ı,
   - o mesleğin `formerSlugs` listesinde açıkça tanımlı eski bir slug
   → hedef dildeki canonical URL'e **301** (tam olarak bir adım)
3. **Hiçbiri değil** → **404**

`aka` (arama alias'ları) bu tabloya **girmez**. Gerekçe: "developer", "programcı" gibi
genel terimler birden fazla meslekle çakışır; kontrolsüz bir yönlendirme tablosu
büyütür ve yanlış mesleğe götürebilir. `aka` yalnızca arama indeksine girer.

Hedef dilde entry **yayınlanmamışsa** 301 üretilmez — yayınlanmamış bir adrese
yönlendirme yapılmaz; §5.4'teki unavailable davranışı uygulanır.

### 1.4 `/en/*` de tablodan geçer

`/en/<x>` literal olarak kırpılmaz. Önce `<x>`'in hangi mesleğe ait olduğu çözülür,
sonra İngilizce canonical URL üretilir:

```
/en                            → 301 → /
/en/                           → 301 → /
/en/software-developer/        → 301 → /software-developer
/en/yazilim-gelistirici        → 301 → /software-developer
/en/desarrollador-de-software  → 301 → /software-developer
/en/not-a-real-job             → 404          (/not-a-real-job'a YÖNLENDİRİLMEZ)
/tr                            → 301 → /tr/
/tr/yazilim-gelistirici/       → 301 → /tr/yazilim-gelistirici
```

Yönlendirme zinciri yasak: her giriş en fazla **bir** 301 ile kanonik biçime iner.

### 1.5 Yönlendirme tablosu

`tools/build-index.php` `cache/routes.json` üretir — 15 entry'nin hepsini açmadan
tek dosya okumasıyla çözümleme:

```json
{
  "ids": { "software-developer": { "en": "software-developer",
                                   "tr": "yazilim-gelistirici",
                                   "es": "desarrollador-de-software" } },
  "slugs": { "en": { "software-developer": "software-developer" },
             "tr": { "yazilim-gelistirici": "software-developer",
                     "yazilimci":           "software-developer" } },
  "published": { "software-developer": ["en", "tr"] }
}
```

`slugs` haritası canonical slug'ları **ve** `formerSlugs`'ı içerir; `aka` içermez.

**`routes.json` bir performans optimizasyonudur, zorunlu bir deploy artefaktı değildir.**
Deploy'dan hemen sonra dosya henüz yokken sitenin tamamının çökmesi kabul edilemez.
`route.php` davranışı:

1. Cache yoksa route tablosunu kaynak JSON dosyalarından **bellekte üretir** ve
   yazabiliyorsa cache'e yazar.
2. Cache yazılamıyorsa (izin, dolu disk) aynı istek bellekteki tabloyla tamamlanır.
3. Boş, kesik veya bozuk cache site genelinde 500/404 üretmez — bozuk sayılır ve
   yeniden üretilir.
4. Cache yazımı **atomiktir**: geçici dosyaya yaz, sonra `rename()`. Yarım yazılmış
   bir `routes.json`'ı başka bir isteğin okuması mümkün olmamalı.

Aynı ilke arama indeksleri için de geçerlidir (§6.3).

### 1.6 Dil seçimi ve hatırlama

- **Otomatik yönlendirme yok.** Ne tarayıcı diline ne coğrafyaya göre.
- Kullanıcının seçimi çerez/localStorage'da hatırlanır.
- Tarayıcı dili sayfanın diliyle uyuşmuyorsa **kapatılabilir bir öneri şeridi**
  gösterilebilir ("Türkçe devam etmek ister misiniz?"). Öneri, yönlendirme değil.
- Header'daki seçici, bulunulan sayfanın karşı dildeki **eşdeğerine** götürür;
  ana sayfaya düşürmez. Yayınlanmamış dil "yakında" olarak pasif gösterilir.

### 1.7 URL üretimi

Şablonlarda elle URL kurulmaz. İki fonksiyon:

```php
url_for(string $lang, string $type, string $key = ''): string
alternates_for(string $type, string $key): array   // yalnızca YAYINLANAN diller
```

İngilizcenin prefix'siz olması bu iki fonksiyonun içinde tek bir koşul olarak yaşar.
Bu, kabul edilmiş SEO istisnasının şablonlara dağılmasını engelleyen tek mekanizmadır.

### 1.8 Güvenlik sınırları

Mevcut sistem üretimde `.htaccess`, lokalde `router.php` ile hassas klasörleri
kapatıyor. Front controller dönüşümünde bu koruma **kaybolmamalıdır**.

**Sıra kritiktir: güvenlik kontrolü, "gerçek dosya varsa dokunma" kontrolünden
ÖNCE uygulanır.** Ters sırada `data/jobs/accountant/en.json` gerçek bir dosya
olduğu için servis edilir ve tüm içerik ham JSON olarak dışarı açılır.

Kabul kriteri — hepsi 404:

```
/data/*        /inc/*         /cache/*
/docs/*        /research/*    /.git/*
/.env ve diğer nokta ile başlayan yollar   (.well-known hariç)
*.md  *.example                            (depo dosyaları)
```

`tools/` bilinçli olarak dışarıdadır ve `BUILD_KEY` ile korunur — shared hosting'de
SSH olmayabildiği için build'in tarayıcıdan tetiklenmesi gerekiyor
(`build_key_ok()`, mevcut karar korunuyor).

Bu fixture'lar hem `.htaccess` hem built-in server yolu için çalışır (§12.1).

---

## 2. Veri şeması

```
data/jobs/software-developer/
    common.json
    en.json
    tr.json
    es.json
```

### 2.1 `common.json` — dilden bağımsız olan tek şey

```json
{
  "id": "software-developer",
  "category": "tech",
  "taskOrder": ["requirements", "boilerplate", "debugging", "architecture", "review"]
}
```

### 2.2 Dil dosyası

```json
{
  "assessmentScope": "global",
  "assessmentSourceLocale": "en",
  "translationReviewed": "2026-08-18",

  "slug": "yazilim-gelistirici",
  "formerSlugs": [],
  "title": "Yazılım Geliştirici",
  "aka": ["yazılımcı", "programcı", "developer"],

  "oneLiner": "...",
  "summary": "...",
  "tasks": {
    "boilerplate": { "name": "Kalıp kod yazımı", "note": "..." }
  },
  "whatSurvives": "...",
  "adaptPrompt": "...",
  "adaptTools": ["Claude", "..."]
}
```

Görevler dizi değil, `taskOrder`'daki id'lerle anahtarlanmış nesnedir: sıra ortak,
metin yerel. Validator "tr.json'da `debugging` görevi eksik" diyebilir.

### 2.3 Yerel görevler (v1'de kullanılmaz, şema engellemez)

Bir pazarda gerçekten farklı bir görev varsa:

```json
{
  "taskOrder": ["requirements", "local-kvkk-uyum", "debugging"],
  "localTasks": {
    "local-kvkk-uyum": { "name": "...", "note": "...",
                         "verdict": "safe", "tags": ["regulated"] }
  }
}
```

`taskOrder` dil dosyasında geçersiz kılınabilir. Yerel görev yalnızca kendi dil
dosyasında tanımlanır ve başka dile sızmaz.

---

## 3. Devralma modeli

Kural iki kelimeyle: **yargı devralınır, düzyazı devralınmaz.**

### 3.1 Sahiplik ve devralma

İki ayrı eksen var ve karıştırılmamalıdır:

> **Sahiplik.** Bir dosya, `assessmentSourceLocale` kendi diline eşitse yargının
> **sahibidir**. Kaynak dosya yargı alanlarını **taşır**; kaynak olmayan dosya
> **taşımaz**, devralır.
>
> **Kapsam.** `assessmentScope` yargının *global* mi yoksa *yerel* mi olduğunu
> söyler — pazara özgü olup olmadığını. Yerel kapsam §7.1'deki kaynak standardına tabidir.

Bu ayrım olmadan `en.json` çelişkiye düşerdi: kapsamı `global`'dir ama yargının
kaynağı odur ve alanları yazmak zorundadır. Faz 2 sonrası tablo:

| Dosya | `assessmentScope` | `assessmentSourceLocale` | Sahip mi | Yargı alanları |
|---|---|---|---|---|
| `en.json` | `global` | `en` | evet | **var** |
| `tr.json` (v1) | `global` | `en` | hayır | **yok** |
| `es.json` (v1) | `global` | `en` | hayır | **yok** |
| `tr.json` (yerel araştırma sonrası) | `local` | `tr` | evet | **var** + kendi `sources` |

Kaynak **olmayan** bir dosyada şunlar `assessmentSourceLocale`'deki dosyadan
devralınır ve yerel dosyaya **yazılmaz**:

```
verdict · safeUntil · resistanceTags · sources · evidenceStrength
assessmentReviewed
tasks[].verdict · tasks[].tags
```

Yazılmışlarsa validator hata verir. Gerekçe: sessiz ayrışma — bir dilin farkında
olunmadan farklı bir yargı taşıması — en tehlikeli hata türüdür ve şemada mümkün olmamalı.

### 3.2 Devralınmayanlar

```
title · oneLiner · summary · tasks[].name · tasks[].note
whatSurvives · adaptPrompt · adaptTools · slug
```

Biri eksikse entry o dilde **yayınlanmamış** sayılır (§5.4).

### 3.3 Yerel yargıya geçiş

```json
{ "assessmentScope": "local",
  "assessmentSourceLocale": "tr",
  "assessmentReviewed": "2026-10-03",
  "translationReviewed": "2026-10-03",
  "verdict": "shrinking", "safeUntil": "2033",
  "inheritedSources": true,
  "sources": ["https://www.turmob.org.tr/...", "https://www.mevzuat.gov.tr/..."] }
```

Tek bir görevin yargısı da ayrıca geçersiz kılınabilir:

```json
{ "tasks": { "tax-filing": { "name": "...", "note": "...",
                             "verdict": "safe", "tags": ["regulated"] } } }
```

`inheritedSources: true` ise İngilizce kaynaklar korunur ve yerel kaynaklar
**üstüne eklenir**. Yerel değerlendirme, küresel otomasyon mekanizmasına dair
uluslararası araştırmayı reddetmek zorunda değildir; eklediği şey yerel mevzuat
ve iş piyasasıdır.

### 3.4 Tarih ayrımı

| Alan | Anlamı | Kime görünür |
|---|---|---|
| `assessmentReviewed` | Yargının en son incelendiği tarih | **Kullanıcıya bu gösterilir** |
| `translationReviewed` | Yerel metnin en son yazıldığı/gözden geçirildiği tarih | Editoryal takip + validator |

Kaynak **olmayan** dosyada `assessmentReviewed` yazılmaz, devralınır; kaynak dosyada
zorunludur. `translationReviewed` kaynak olmayan dosyalarda zorunlu, kaynak dosyada
isteğe bağlıdır (kaynakta çeviri yoktur; yazılmazsa `assessmentReviewed`'a eşit sayılır).

**Tarih biçimi:** `YYYY-MM-DD` tercih edilir. Mevcut `YYYY-MM` verileri geriye
uyumluluk için ayın ilk günü sayılır. Yeni girişler tam tarih yazmalıdır.

`translationReviewed < assessmentReviewed` ise yerel metin bayattır: validator uyarır
ve sayfada görünür bir not basılır. Üç dilli bir sitenin en sık ve en sessiz çürüme
biçimi budur; ölçülmezse fark edilmez.

---

## 4. Locale sistemi

```
inc/lang/Base.php     arayüz
inc/lang/En.php       bugünkü davranışın birebir taşınmış hali
inc/lang/Tr.php
inc/lang/Es.php
data/locale/en.php    düz key => string tablosu
data/locale/tr.php
data/locale/es.php
```

Ayrım net:

| Nerede | Ne |
|---|---|
| `data/locale/*.php` | Düz UI metinleri; verdict **isimleri ve açıklamaları**; kategori ve direnç tag'i etiketleri; ay adları; cümle şablonları |
| `inc/lang/*.php` | Çoğul, artikel, liste bağlacı, tarih biçimi ve **üretilen cümle mantığı** (`geo_answer`, `faq_pairs`) |
| `inc/config.php` | Verdict **anahtarları ve renkleri**, kategori ve tag anahtarları — dilden bağımsız olanlar |

`VERDICTS` sabiti ikiye bölünür: `label`/`blurb` locale'e taşınır, `dot`/`color`/`rgb`
config'de kalır. Bu, "`/methodology` yayınlanmış tanımdır, çakışırsa o kazanır"
kuralını üç dilde birden ayakta tutmanın tek yoludur.

### 4.1 `intl` ve fallback

`intl` yalnızca ay adı ve sayı biçimlendirmede kullanılır. Sunucuda **açık** olduğu
doğrulandı (PHP 8.3.31, Hostinger Premium). Kapalıysa fatal verilmez:
`data/locale/*.php` içindeki elle yazılmış ay tabloları devreye girer. Site bugün
zaten böyle çalışıyor (`pretty_month()`'un İngilizce dizisi), yani fallback bir
gerileme değil, mevcut davranışın üç dile genişletilmiş hali.

Arama harf katlaması `intl`'e **bağımlı değildir** (§6) — `intl` kapalıyken de
sonuç birebir aynı olmalıdır.

---

## 5. SEO

### 5.1 hreflang ve canonical

`alternates_for()` kümesi **yalnızca yayınlanan dillerden** üretilir. Bir meslek
TR'de yayınlanmamışsa hiçbir sayfa `hreflang="tr"` basmaz. Tek kaynaktan üretildiği
için karşılıklılık yapısal olarak garantidir.

```html
<link rel="alternate" hreflang="en" href="https://willaistealit.com/software-developer">
<link rel="alternate" hreflang="tr" href="https://willaistealit.com/tr/yazilim-gelistirici">
<link rel="alternate" hreflang="x-default" href="https://willaistealit.com/software-developer">
```

- `x-default` → her zaman İngilizce URL (kökte `/`, mesleklerde `/<slug>`)
- `canonical` → her zaman normalize edilmiş kendi URL'i
- `<html lang="tr">` + `og:locale`

### 5.2 Sitemap

Tek dosya. Her URL kendi alternate kümesini `xhtml:link` ile taşır. Yayınlanmamış
diller girmez.

`lastmod = max(assessmentReviewed, translationReviewed)`.

**Anlamı:** sayfanın gerçekten anlamlı içerik değişikliği gördüğü tarih. Build,
cache temizliği, şablon düzenlemesi veya biçimlendirme değişikliği `lastmod`'u
**hareket ettirmez**. Dosya `mtime`'ı bu yüzden kullanılmaz.

### 5.3 JSON-LD

Dile göre kurulur: `Article.headline`/`description`, `Occupation.name`,
`occupationalCategory` (locale etiketi), `FAQPage` soruları yerel dilde üretilir,
`BreadcrumbList` hem adları hem URL'leri yerelleştirir, `inLanguage` eklenir.

### 5.4 Yayınlanmamış çeviri

```
HTTP 404
<meta name="robots" content="noindex, follow">
canonical: YAZILMAZ (var olmayan URL kendini canonical gösteremez)
sitemap:   dahil değil
hreflang:  dahil değil
```

Sayfa kullanıcı dostudur: yayınlanan dillere bağlantı verir. **İngilizceye sessiz
yönlendirme yapılmaz.** Dil seçicide "yakında" olarak pasif görünür.

### 5.5 Küresel değerlendirme notu

`assessmentScope: "global"` olan her entry'de, dile özgü ve dürüst bir not
otomatik olarak eklenir.

**TR:**
> Bu küresel değerlendirme ağırlıklı olarak uluslararası ve ABD kaynaklarına dayanır;
> Türkiye'ye özgü mevzuat ve iş piyasası ayrıca incelenmemiştir.

**ES:**
> Esta evaluación global se basa principalmente en fuentes internacionales y
> estadounidenses; todavía no incorpora un análisis específico de la regulación y del
> mercado laboral de cada país hispanohablante.

İspanyolca metin "yerel pazar"ı tek bir pazar gibi sunmaz — İspanyolca konuşulan
ülkelerin mevzuatı birbirinden farklıdır ve bunu tek cümlede birleştirmek yanlış olur.

Not şurada bulunur: **görünür sayfa metni**, **alıntılanabilir cevap paragrafı**
(`geo_answer()`), **JSON-LD `description`**. OG kartına **girmez** — kart 1200×630'da
zaten sıkışık ve bu not paylaşım görselinin işi değil.

### 5.6 OG kartları

```
/og/<slug>.png        EN   (mevcut yol korunur — kırılan paylaşım yok)
/og/tr/<slug>.png     TR
/og/es/<slug>.png     ES
```

Verdict etiketi locale'den gelir: kartta "MENÜDE" / "SE REDUCE" yazar.

---

## 6. Arama

Index dil başına: `cache/index-{en,tr,es}.json`. `titleTr` alanı ölür; yerine `aka`
gelir. `aka` hem index'e hem sunucuda basılan `data-search` özniteliğine katlanmış
halde girer.

### 6.1 Harf katlama — tek veri kaynağı

İki gerçek problem var:

1. Türkçe kullanıcı aksansız yazar: "yazilim" yazıp "Yazılım Geliştirici"yi
   bulamamak kabul edilemez.
2. `mb_strtolower('İ')` `i` + U+0307 (birleşen nokta) üretir ve hiçbir şeyle
   eşleşmez; `strtolower('I')` Türkçede `ı` olmalıyken `i` verir.

**Harita tek dosyada tutulur: `data/search-fold.json`.** PHP doğrudan okur;
JavaScript'e `build-index.php` tarafından gömülür. Aynı algoritmayı iki dilde elle
yazmak ileride sessiz ayrışma üretir — bu yüzden yapılmaz.

Algoritma:

1. Unicode normalize (NFD)
2. Birleşen işaretleri kaldır
3. Türkçeye özgü `ı`/`İ` dönüşümünü **açıkça** uygula (2. adım bunları çözmez)
4. Küçük harfe çevir
5. Noktalama ve fazla boşluğu normalize et

### 6.2 Fixture'lar

Validator bunları hem PHP hem JS tarafında doğrular; `intl` kapalıyken de aynı
sonuç alınmalıdır:

```
Yazılım Geliştirici  → yazilim gelistirici
İŞE ALIM             → ise alim
MUHASEBECİ           → muhasebeci
Español              → espanol
PROGRAMACIÓN         → programacion
Lingüista            → linguista
¿Desarrollador?      → desarrollador
```

Mevcut fuzzy eşleşme (sıralı harf) korunur ve katlanmış meslek adına uygulanır.

### 6.3 İndeks yoksa

Arama indeksi de `routes.json` gibi üretilmiş bir artefakttır ve sayfanın açılması
için zorunlu değildir. İndeks eksik veya bozuksa:

- Sayfalar normal açılmaya devam eder.
- Arama **kontrollü biçimde** degrade olur (sunucuda basılan tam liste görünür
  kalır — bugünkü davranış: JS kapalıyken de tablo tam görünüyor), veya indeks
  güvenli biçimde yeniden üretilir.
- Yazım atomiktir: geçici dosya + `rename()`.

---

## 7. Validator kuralları

Mevcut kuralların hepsi dil başına tekrarlanır. Yeni kurallar:

**Yapısal**
- Meslek slug'ı `en`/`tr`/`es` olamaz; o dilin sabit sayfa slug'larıyla çakışamaz
- Bir dil içinde slug tekil; hiçbir `formerSlug` başka bir canonical slug'ı gölgeleyemez
- `taskOrder`'daki her ortak görev, yayınlanan her dilde `name` + `note` taşımalı
- `localTasks` yalnızca kendi dil dosyasında tanımlanır ve `taskOrder`'da yer almalı
- `aka` yönlendirme tablosuna sızmamalı (routes.json üretimi kontrol edilir)

**Devralma bütünlüğü**
- `assessmentScope: "global"` ise §3.1'deki alanların hiçbiri yazılmamış olmalı
- `assessmentScope: "local"` ise `assessmentSourceLocale` kendi dili olmalı,
  `assessmentReviewed` zorunlu, kaynak standardı §7.1'e tabi
- `translationReviewed` her yerel dosyada zorunlu

**Çelişki**
- Yerelde bir görev `gone`'a çekilmişse ve o dilin geçerli verdict'i `safe` ise → **hata**.
  Bu, `CONTRIBUTING.md`'de zaten yayınlanmış sert eşiğin ("bir tek `gone` task varsa
  verdict `safe` olamaz") birebir uygulanmasıdır, yeni bir kural değildir
- `safe` verdict'te `safeUntil` varsa → hata (rubrik: `safe` entry'lerde yıl yok)
- Yerel override sonrası `gone` sayısı kaynak dilden fazlaysa → **uyarı**:
  🟡/🔴 ayrımı task sayısıyla değil, hayatta kalan işin istihdam kapasitesiyle
  belirlenir ve bu makine ile ölçülemez; validator yalnızca "yeniden değerlendir" der

**Tazelik**
- `translationReviewed < assessmentReviewed` → uyarı + sayfada görünür not

**Ortam**
- `search_fold()` fixture'ları (§6.2), PHP ve JS için ayrı ayrı
- Yayınlanan her dil kombinasyonu için hreflang karşılıklılığı

### 7.1 Yerel kaynak standardı — ONAYLANDI

> **Temel ilke:** Yerel verdict, en az bir iddiaya uygun yerel yetkili kaynak
> taşımalıdır. Küresel teknoloji kanıtları miras alınabilir; yerel mevzuat,
> istihdam veya benimseme iddiasını ilgili yerel kaynak desteklemelidir.

Bu kural, uygulama sırasında `docs/memory/verdict-rubric.md`'ye kısa ve damıtılmış
biçimde **önerilir**. Otomatik yazılmaz — değişiklik diff olarak sunulur ve ayrıca
onaylanır.

`assessmentScope: "local"` bir değerlendirmede validator şunları arar:

1. En az bir **doğrulanmış ve yetkili yerel kaynak**
2. İddiaları taşıyacak **yeterli toplam kaynak** (devralınanlar dahil)
3. Ortada bir **regülasyon iddiası** varsa: birincil mevzuat metni veya yetkili kurum

"TÜİK/İŞKUR varsa otomatik olarak Tier 1'dir ve yeterlidir" gibi mekanik bir kural
kurulmaz. Kaynağın yetkisi iddianın türüne göre değerlendirilir: mevzuat iddiasını
mevzuat metni taşır, istihdam iddiasını istatistik kurumu taşır, benimseme iddiasını
sektör verisi taşır. Mevcut rubriğin `bls.gov` tabanı küresel değerlendirmelerde
geçerliliğini korur.

---

## 8. Cache

Anahtar `cache/pages/<lang>/<slug>.html` olur.

**Kritik incelik:** TR/ES sayfası `en.json`'a bağımlıdır (devralma). Klasör
`mtime`'ı **dependency olarak kullanılmaz** — dizin zaman damgası dosya içeriği
değiştiğinde değişmez ve sessiz bayat cache üretir.

### 8.1 Entry'ye özel bağımlılıklar

```
data/jobs/<id>/common.json
data/jobs/<id>/en.json          (devralma kaynağı — TR/ES sayfası buna bağlı)
data/jobs/<id>/<lang>.json
data/locale/<lang>.php
inc/lang/<Lang>.php + inc/lang/Base.php
inc/config.php                  (verdict tanımları)
job.php + inc/*.php             (şablonlar)
```

Bu kümenin kesin maksimum `mtime`'ı hesaplanır. Unutulursa EN verdict'i
güncellendiğinde TR sayfası eski verdict'i göstermeye devam eder ve kimse fark etmez.

### 8.2 Evrensel bağımlılık: `content-version.json`

`related_jobs()` bloğu tek bir entry'ye değil, **meslek evreninin tamamına** bağlıdır:
yeni bir entry eklendiğinde veya bir entry'nin kategorisi/tag'i değiştiğinde mevcut
sayfaların "aynı fay hattındaki işler" bloğu değişir.

Çözüm: `tools/build-index.php` içerik değiştiğinde tek bir atomik sürüm dosyası üretir.

```json
{ "version": "<hash>", "generated": "2026-08-15T12:00:00Z" }
```

`version`, yayınlanan tüm `common.json` dosyalarının ve ilgili dildeki tüm yayınlanmış
dil dosyalarının **içerik hash'lerinden** türetilir. Sayfa cache'i bunu dependency
olarak kullanır.

**Fallback zorunludur:** `content-version.json` yoksa veya bozuksa sayfa cache'i
kaynak dosyalardan güvenli biçimde hesaplar (ilgili dosyaların maksimum `mtime`'ı).
Eksik sürüm dosyası siteyi çökertmez; yalnızca hesabı pahalılaştırır.

Yazım atomiktir: geçici dosya + `rename()`.

Mevcut `filemtime($cached) <= $newest` ("şüpheliyi at") davranışı korunur.

---

## 9. Ortam ve dağıtım

**Barındırma:** Hostinger Premium (`hostinger_premium_v3`), PHP 8.3.31.
`gd`, `imagick`, `mbstring`, `intl` açık. Depolama üç dil için fazlasıyla yeterli:
mevcut repo 2.6 MB, 15 entry toplam 76 KB; üç dilde ~230 KB JSON ve ~6 MB sayfa+OG
cache'i bekleniyor. Gerçek kısıtlar depolama değil, **inode sayısı** ve **40 PHP
worker**'dır; ikisi de rahat. Aynı hesapta duran Matomo, siteden önce sıkışacak taraftır.

**`tools/doctor.php` (yeni):**

- `intl` / `gd` / `mbstring` var mı, PHP sürümü
- `BUILD_KEY` değiştirilmiş mi
- **Cache dizinleri:** yoksa güvenli biçimde oluştur → **gerçek yazma testi** yap →
  yazılan geçici dosyayı temizle. Dizinin repoda bulunması yazılabilir olduğunu
  garanti etmez
- **Font kontrolü** (§9.1)

**`.gitignore` eklemeleri.** Üretilen cache artefaktları kaynak dosya gibi
yanlışlıkla commit edilmemeli. Mevcut `.gitignore` yalnızca `cache/index.json`'ı
kapsıyor; yeni dosyalar için eklenir:

```
cache/routes.json
cache/index-*.json
cache/content-version.json
```

**Deploy:** Git deployment `.gitignore`'u filtre olarak kullanıyor
(`docs/memory/decisions/2026-08-15-hosting-hostinger`). `cache/pages/{en,tr,es}/.gitkeep`
ve `cache/og/{tr,es}/.gitkeep` takip edilir ki klasörler sunucuda oluşsun; yazılabilirlik
yine `doctor.php` ile doğrulanır.

### 9.1 Font kontrolü

`imagettfbbox()` **tek başına yeterli değildir**: eksik glif için `.notdef` kutusunu
ölçer ve başarılı görünebilir, yani tofu kutusunu yakalayamaz.

İki adım vardır ve **birbirinin alternatifi değildir**:

**Otomatik (Faz 0 geçme koşulu).** TTF **cmap tablosu** okunur; aşağıdaki code
point'lerin **tamamı** her iki fontta bulunmalıdır. Cmap testi geçmeden Faz 0
başarılı sayılmaz.

**Manuel (TR/ES OG üretiminin onay koşulu).** EN/TR/ES için örnek OG kartları
üretilir; taşma, kerning, satır kırılması ve genel görsel kalite göze incelenir.
Cmap testi geçse bile bu inceleme yapılmadan TR/ES OG üretimi onaylanmaz —
cmap glifin *var* olduğunu söyler, *iyi durduğunu* söylemez.

Minimum karakter seti:

```
Türkçe:      Ç Ğ İ Ö Ş Ü ç ğ ı i ö ş ü
İspanyolca:  Á É Í Ó Ú Ü Ñ ¿ ¡ á é í ó ú ü ñ
```

Faz 0 çıktısı **eksik code point'leri tek tek listeler**. Eksik varsa lansmandan
önce karar alınır: glif kapsamı olan bir kesim eklenir ya da OG kartında ikinci bir
yazı tipi kullanılır.

---

## 10. Migration

```bash
php tools/migrate-jobs.php                    # dry-run raporu; hiçbir şey yazmaz
php tools/migrate-jobs.php --out=data/jobs2   # ayrı hedefe yazar
php tools/migrate-jobs.php --verify           # semantik eşitlik raporu
```

- Varsayılan davranış **dry-run**'dır.
- Mevcut JSON dosyaları silinmez, üzerine yazılmaz.
- `--verify` her entry için eski düz dosyadan ve yeni dizinden yüklenen nesneyi
  alan alan karşılaştırır; fark varsa listeler.
- `titleTr` yalnızca `tr.json`'a `title` **tohumu** olarak geçer. `oneLiner`,
  `summary`, görev notları ve `adaptPrompt` boş kalır — yani TR entry'si o haliyle
  **yayınlanmamış** sayılır. 15 Türkçe başlık, 15 Türkçe entry'ye benzemez ve şema
  bu yanılsamayı üretmez.
- Geçiş bitince `load_job()`'ın eski düz dosya okuma yolu **tamamen kaldırılır**.
  İki formatın aynı anda belirsiz biçimde okunduğu bir ara durum bırakılmaz.

---

## 11. Terminoloji

| EN | TR | ES |
|---|---|---|
| SAFE | **GÜVENDE** | **A SALVO** |
| SHRINKING | **DARALIYOR** | **SE REDUCE** |
| ON THE MENU | **MENÜDE** | **EN EL MENÚ** |
| gone | gitti | ya desapareció |
| going | gidiyor | está desapareciendo |
| safe | kalıyor | resiste |

Bunlar **v1 terminolojisidir ve locale dosyalarında merkezî durur.** İlk 2–3 gerçek
TR/ES entry görsel olarak incelendikten sonra metin içinde doğal durmuyorsa, kod
değişikliği veya veri migration'ı gerektirmeden değiştirilebilir.

`/methodology`'nin TR ve ES sürümlerinde "MENÜDE" / "EN EL MENÚ" ifadesinin anlamı
açıkça tanımlanır: *çekirdek görevler gidiyor ve geriye kalan iş aynı sayıda insanı
taşımayacak.* Üç dil de `inc/config.php`'deki 🔴 tanımının çevirisini taşır, yeni bir
tanım üretmez.

---

## 12. Test stratejisi

Proje şu an testsiz. Üç dilde 60+ URL'i elle tıklamanın alternatifi yok.

### 12.1 `tools/smoke.php` (yeni)

Front controller'a geçmenin gerekçesi lokal/üretim farkını kapatmaksa, test yalnızca
route fonksiyonunu değil **iki giriş yolunu da** doğrulamalıdır:

1. `route.php` doğrudan çözümleme testleri (birim)
2. PHP built-in server + `router.php` üzerinden gerçek HTTP
3. Apache rewrite mantığının mümkün olan statik doğrulaması (`.htaccess` kurallarının
   beklenen yönlendirmeyi ürettiğinin kontrolü)

Zorunlu matris — her satır için **status, `Location`, canonical, `<html lang>` ve
yönlendirme adedi** kontrol edilir:

```
/                      /en                  /en/
/tr                    /tr/                 /es                 /es/
/en/<en-slug>          /en/<tr-slug>        /en/<es-slug>
/tr/<en-id>            /tr/<es-slug>        /tr/<former-slug>
/tr/<aka-only>         → 404
/unknown               → 404
/tr/unknown            → 404
/en/not-a-real-job     → 404 (yönlendirme YOK)
<yayınlanmamış dil>    → 404 + noindex
```

Güvenlik fixture'ları (§1.8), aynı iki giriş yolunda ayrıca çalışır:

```
/data/jobs/accountant/en.json   → 404
/inc/config.local.php           → 404
/cache/routes.json              → 404
/docs/architecture/...md        → 404
/research/sources.json          → 404
/.git/config                    → 404
/.well-known/security.txt       → 404 DEĞİL (açık kalmalı)
```

### 12.2 Faz 3 regresyon ölçüsü

"EN byte-identical" iyi bir hedef ama JSON-LD anahtar sırası, whitespace veya asset
query parametresi gibi anlamsız farklar fazı gereksiz kilitler. İki katmanlı test:

1. **Golden/byte karşılaştırması** — mümkün olan sayfalarda
2. **Semantik karşılaştırma** — her sayfada:
   HTTP status · canonical · `<title>` · meta description · H1 · verdict ·
   task sayısı ve içeriği · JSON-LD **anlamı** · link hedefleri

Anlamsız whitespace farkı hata sayılmaz. Kullanıcıya görünen veya SEO anlamını
değiştiren fark hata sayılır.

---

## 13. Fazlar

Her fazın sonunda site çalışır ve doğrulanabilir durumdadır.

| Faz | İş | Doğrulama |
|---|---|---|
| **0** | `doctor.php` + font cmap kontrolü | Eksik code point listesi. Eksik varsa font kararı lansmandan önce alınır |
| **1** | Front controller, `url_for()`/`alternates_for()`, normalizasyon + 301'ler, `routes.json`. Hâlâ tek dil | `smoke.php` matrisi (TR/ES satırları hariç); `.htaccess`/`router.php` ikiliği bitti |
| **2** | Veri şeması + migrate (dry-run → verify → apply); eski okuma yolu kaldırılır. Hâlâ tek dil | `--verify` temiz, sayfa çıktıları değişmemiş, `validate.php` temiz |
| **3** | Locale sistemi; EN metinleri `data/locale/en.php` + `inc/lang/En.php`'ye çıkar | §12.2 iki katmanlı regresyon |
| **4** | TR açılışı: 15 çeviri entry, dil seçici, hreflang, sitemap, OG, arama, unavailable sayfası | `smoke.php` tam matris, hreflang karşılıklılığı, fold fixture'ları |
| **5** | ES açılışı | Aynı kontrol listesi |
| **6** | Kapanış: validator üç dilde, mobil + dark mode + erişilebilirlik, cache davranışı, Search Console'a sitemap | Kontrol listesi |

Faz 1–3 **davranış değiştirmez**; üçü de "çıktı aynı kalmalı" testine bağlıdır.

---

## 14. Riskler

1. **Font glifleri lansman blocker'ı olabilir.** Faz 0'da ölçülür. `imagettfbbox`
   tek başına yalancı olumlu verdiği için cmap kontrolü şart (§9.1).
2. **İş yükünün ağırlığı kodda değil, içerikte.** Faz 1–3 mekanik ve doğrulanabilir.
   Faz 4–5, `adaptPrompt`'lar dahil (accountant'ınki ~1.500 karakter, teknik
   terminoloji dolu) 30 entry'nin çevirisidir. Takvimi bu belirler.
3. **Çeviri bayatlaması ölçülmezse görünmez.** `translationReviewed` uyarısı bu
   yüzden validator'da sert kuraldır.
4. **Matomo segmentasyonu.** Üç dil ayrı ölçülmezse hangi dilin işe yaradığı bilinemez.
   Faz 6'da yapılandırılır.
5. **`aka` ile `formerSlugs` karışırsa** yönlendirme tablosu kontrolsüz büyür ve
   kullanıcı yanlış mesleğe gider. Validator bunu ayrıca denetler (§7).

## 15. Lansman kuralları

**Kısmi lansman kabul edilir ve tercih edilir.** Sıra:

1. EN mimari geçişi (Faz 1–3) — dil sayısı değişmez, davranış değişmez
2. 15 meslek tamamlanınca **TR lansmanı**
3. ES içerikleri tamamlanınca **ES lansmanı**

TR hazırken ES'yi beklemek gereksiz; mimari yayınlanmamış dili zaten sitemap,
hreflang ve indeks dışında tutuyor (§5.1, §5.2, §5.4).

Kurallar:

- Bir dil; **ana sayfası + sabit sayfaları + mevcut 15 mesleğin tamamı** hazır
  olmadan aktif dil ilan edilmez.
- Hazır olmayan dil header'da aktif seçenek gibi gösterilmez — tercihen hiç
  gösterilmez, ya da pasif "yakında" görünür.
- Hazır olmayan dilin ana sayfası (`/es/`) **indekslenebilir boş sayfa üretmez**.
- Dil bazında yarım meslek kataloğu lansmanı yapılmaz.
- Bir dil açıldıktan sonra yeni meslekler entry bazında kademeli eklenebilir;
  o dilde yayınlanmayan karşılıklar hreflang kümesi dışında kalır.

Buna göre Faz 4 ve Faz 5 birbirinden bağımsız yayınlanabilir; Faz 6 her dil
lansmanında ayrı ayrı koşulur.

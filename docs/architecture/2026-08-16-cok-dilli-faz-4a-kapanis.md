# Faz 4A Kapanış — dil-farkında altyapı bitti, TR hâlâ kapalı

> Bu belge **bağlamsız** bir oturumun 4B'yi devralabilmesi için yazıldı.
> 4A'nın on bir görevi ve üç bakım commit'i tamamlandı.

**Plan:** `docs/architecture/2026-08-15-cok-dilli-faz-4-plan.md`
**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md`
**Önceki kapanış:** `docs/architecture/2026-08-15-cok-dilli-faz-3-kapanis.md`

## Devralınan durum (16 Ağustos 2026)

Son commit: `864f8bf`. Çalışma alanı temiz, `main` origin'den ileride, **push yok**.

```
1094 test geçti, 0 kaldı         golden 15/15 semantik · 15/15 byte
validator: Hata yok              cache-check: çıkış 0
smoke: Matris temiz              self-test: 4 içerik türünde kırmızı verebiliyor
fold-check: çıkış 0              doctor: kritik hata yok, 1 uyarı
activeLangs: ['en']              /tr/* ve /es/* 404
bekleyen editoryal: tr 69 · es 69   (methodology 43 + llms 26)
TR entry'si olan: 2 (cashier, administrative-assistant) · 17 entry'nin 15'i eksik
```

Faz 3'ten devralınan **byte golden açığı kapandı**: 15/15 byte-identical.

## Tamamlanan commit'ler

| Görev | Hash | Mesaj |
|---|---|---|
| 4A1 | `58e1720` | `feat: emit hreflang, og:locale and a language-aware html lang` |
| 4A2 | `40d9f2e` | `feat: add alternate links and content-dated lastmod to the sitemap` |
| 4A3 | `1b12418` | `feat: render OG cards per language` |
| 4A4 | `8d7abc4` | `feat: fold search text through one shared map in PHP and JS` |
| 4A5 | `c80296c` | `feat: build one search index per active language` |
| 4A6 | `faa6ed0` | `feat: add a language switcher that follows page equivalents` |
| — | `663fb32` | `fix: prevent the header navigation from overflowing on mobile` |
| — | `659eba1` | `test: refresh golden fixtures for phase 4A output` |
| 4A7 | `64d868a` | `feat: version page cache against the whole content universe` |
| 4A8 | `6038502` | `feat: enforce the multilingual validator rules` |
| — | `952435b` | `fix: read the request language in the page templates` |
| 4A9 | `a5928af` | `test: cover the full routing matrix including Turkish` |
| 4A10 | `c058334` | `feat: localise the JSON-LD graph` |
| 4A11 | `62da352` | `feat: link the unavailable page to the languages that do have it` |
| — | `864f8bf` | `test: refresh golden fixtures after the stylesheet change` |

## 4B'nin devraldığı arayüzler

Bunlar 4A'da kuruldu ve 4B ile 4C'nin üzerine bastığı zemin:

- **`request_lang()`** (`inc/functions.php`) — istek dilini `$_GET['lang']`'den okur,
  bilinmeyen kodu varsayılana düşürür. Her şablon bunu kullanır.
- **`routes_file()` / `WAISI_ROUTES_FILE`** (`inc/routes_cache.php`) — TR'yi canlı
  `cache/routes.json`'a **dokunmadan** önizlemenin tek yolu. Canlı hostta
  (`is_live_host()`) ortam değişkeni **yok sayılır**; iş yarıda kesilse bile TR açık
  kalamaz.
- **`content_hash()` / `content_version()` / `page_cache_file()`** — sayfa cache'i
  içerik evreninin sürümüne bağlı. Sürüm dosya **adında** yaşar
  (`cashier.<ver12>.html`).
- **`search_fold()`** (`inc/search.php`) + `data/search-fold.json` — PHP ve JS aynı
  haritayı paylaşır; harita `index.php`'de `window.__fold` olarak gömülür.
- **`entry_lastmod()` / `page_reviewed()`** — sitemap `lastmod`'u içerik tarihinden
  gelir, `filemtime`'dan değil.
- **`entry_translation_stale()`** (`inc/entry.php`) — validator uyarısı ve sayfadaki
  görünür not aynı hesabı kullanır.
- **`routes_leak_errors()` / `hreflang_reciprocity_errors()`** (`inc/routes_audit.php`)
  — saf fonksiyonlar, sentetik bozuk tablolarla test edilebilir.

## 4B'ye taşınan bilinen durumlar

- **`data/page-reviewed.json`'daki EN tarihleri boş.** Dört sabit sayfa sitemap'te
  `lastmod`'suz yayınlanıyor. Karar: **doğrulanmış editoryal inceleme tarihi gelene
  kadar boş kalır** — uydurulmuş tarih, tarihsizlikten kötüdür. Validator bunu uyarı
  olarak listeler. TR satırları 4B2'de doldurulur.
- **`locale/tr` ve `locale/es`: 69 editoryal anahtar.** 4B2'nin işi; ES Faz 5'e kalır.
- **`doctor`: `CONTACT_EMAIL dolu`** uyarısı — 4A ile ilgisiz, mevcut durum.

## Ertelenen backlog

**`asset()`'i filemtime yerine içerik hash'ine geçirmek.** Faz 4A'da iki kez golden'ı
kirletti (4A6 ve 4A11): CSS dosyasına *dokunmak* ile içeriğini *değiştirmek* arasında
ayrım yapmıyor, ve `?v=` her HTML sayfasının `links` kümesinde. İki seferinde de
farkın tamamının cache-buster olduğu kanıtlandı ve ayrı bakım commit'iyle yakalandı.
Kendi görevi olarak ele alınacak; 4B'nin kapsamında değil.

## Plan sapmaları (hepsi kabul edildi)

1. **4A1** — `alternates_for()` `'home'` tipini zaten destekliyordu; planın önerdiği
   `inc/urls.php` bloğu no-op olurdu, eklenmedi.
2. **4A2** — `entry_lastmod_from()` ham `max()` yerine `YYYY-MM` değerini ayın ilkine
   açar. Veride `assessmentReviewed` `YYYY-MM`, `translationReviewed` `YYYY-MM-DD`;
   ham `max()` planın kendi `YYYY-MM-DD` sözleşmesini bozardı.
3. **4A3** — kapsam plandan geniş: kartın diğer üç hardcoded İngilizce metni de
   locale'e taşındı, `Lang::upper()` eklendi (Türkçe `i` → `İ`), `tools/build-og.php`
   aynı cache yoluna geçirildi. EN kart md5'leri **değişmedi**.
4. **4A4** — harita `cache/search-fold.js` olarak servis edilemez (`.htaccess` `cache/`
   altını 404'lüyor); `index.php`'de inline gömülür. Tek kaynak ilkesi korundu.
5. **4A7** — cache sürümü dosya **adında** yaşar. İçine yazmak servis edilen HTML'i
   kirletirdi, ayrı damga dosyası yarış durumu açardı. `golden.sh --cache-check`
   kalıba çevrildi.
6. **4A8** — `inc/routes_audit.php` yeni dosya: iki kural gerçek katalogla kırılamıyor,
   saf fonksiyon olmadan "kırık fixture ile kanıtla" kapısı karşılanamazdı.
   `hreflang_reciprocity_errors()`'ün iki dalı üretimde tetiklenemez (alternates hep
   canonical'dan kurulur) — savunma katmanı, enjekte edilen kümeyle kanıtlandı.
7. **4A10** — `inLanguage` yalnız CreativeWork türevlerine yazıldı (Article, FAQPage,
   WebSite, Dataset). `ItemList`, `BreadcrumbList` ve `Occupation` schema.org'da
   `Intangible` altındadır; oraya yazmak geçersiz olurdu. `changelog.php`'ye
   dokunulmadı.

## 4A'da bulunan ve düzeltilen iki hata

- **`952435b`** — `route.php` dili `$_GET['lang']` ile geçiriyordu ama `index.php`,
  `methodology.php`, `changelog.php` ve `404.php` onu **hiç okumuyordu**;
  `unavailable.php` `$lang`'i hiç tanımlamıyordu. 4C1'de TR açıldığında ana sayfa ve
  bütün sabit sayfalar İngilizce render edecekti. TR routing matrisi yazılırken çıktı.
- **`663fb32`** — header 360 px'de yatay taşıyordu (scrollWidth 600 > 360), dil
  seçicisi olmadan da. Mobilde sarma düzenine geçirildi; dil adları kısaltılmadı.

## Sıradaki: 4B

`activeLangs` **4C1'e kadar `['en']` kalır.** 4B'nin işi kod değil yazı:

1. **4B1** — TR sabit sayfa slug'ları (`metodoloji`, `zaman-cizelgesi`,
   `degisiklikler`, `sponsorluk`)
2. **4B2** — 69 editoryal anahtarın Türkçesi; **önce terminoloji sözlüğü sabitlenir**
3. **4B3** — TR'si olmayan entry'ler; sayı değil `entry_langs()` hesabı ölçüttür
4. **4B4** — TR ana sayfası ve sabit sayfa doğrulaması (preview override ile)

Katalog kapsamının kapısı:

```bash
php -r 'require "inc/functions.php"; $eksik = [];
  foreach (array_keys(load_all_jobs("en")) as $id)
    if (!in_array("tr", entry_langs($id), true)) $eksik[] = $id;
  echo $eksik ? count($eksik) . " EKSIK: " . implode(", ", $eksik) . "\n" : "TR katalogu tam\n";
  exit($eksik ? 1 : 0);'
```

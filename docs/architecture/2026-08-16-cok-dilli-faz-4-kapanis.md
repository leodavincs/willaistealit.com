# Faz 4 Kapanış — Türkçe yayında

> Faz 4 tamamlandı. Türkçe `activeLangs`'e eklendi, `/tr/*` açık ve tam.
> **Dış yayın adımı (Search Console sitemap gönderimi) YAPILMADI** — ayrı ve açık
> onay bekliyor.

**Plan:** `docs/architecture/2026-08-15-cok-dilli-faz-4-plan.md`
**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md`
**4A ara kapanışı:** `docs/architecture/2026-08-16-cok-dilli-faz-4a-kapanis.md`

## Durum (16 Ağustos 2026)

Son commit: `01a09f9`. Çalışma alanı temiz, `main` origin'den ileride, **push yok**.

```
1141 test geçti, 0 kaldı         golden 19/19 semantik · 19/19 byte-identical
validator: Hata yok (9 uyarı)    cache-check: çıkış 0
smoke: Matris temiz              self-test: 4 içerik türünde kırmızı verebiliyor
fold-check: çıkış 0              doctor: kritik hata yok, 1 uyarı
activeLangs: ['en', 'tr']        /es/* hâlâ 404
17 entry × 2 dil                 TR editoryal kuyruk: 0 · ES: 121
sitemap: 44 URL (22 × 2 dil)     hreflang: 22 hedef karşılıklı
```

## Faz 4'ün üç bölümü

**4A — dil-farkında altyapı (11 görev + 3 bakım commit'i).** Ayrıntı 4A kapanış
notunda. `activeLangs` bu bölüm boyunca `['en']` kaldı.

**4B — Türkçe içerik.** 69 editoryal anahtar, 15 yeni TR entry, 4 sabit sayfa
slug'ı, terminoloji sözlüğü. Planın öngörmediği iki sayfa (`landscape`, `sponsor`)
burada baştan yerelleştirildi.

**4C — aktivasyon.**

| Görev | Hash | Mesaj |
|---|---|---|
| 4B1 | `6395f79` | `feat: add the Turkish slugs for the static pages` |
| — | `4595201` | `docs: fix the Turkish terminology glossary before translating` |
| 4B2 | `e1ea06b` | `content: translate the editorial locale keys into Turkish` |
| 4B3 | `8e7977f` … `ab2306a` | `content: add the Turkish entry for <id>` × 15 |
| — | `9e50dd5` | `feat: localise the landscape page` |
| — | `8e57fea` | `feat: localise the sponsor page` |
| — | `56b5b05` | `test: cover the Turkish static pages in the smoke matrix` |
| 4C1 | `4a50b42` | `feat: activate Turkish` |
| 4C2 | `01a09f9` | `test: extend the golden targets to Turkish` |
| 4C3 | (bu belge) | `docs: close out phase 4` |

Faz 4 toplamı: **45 commit**.

## 4B/4C'de bulunan ve düzeltilen altı hata

Hepsi TR açılmadan önce yakalandı; hiçbiri yayına çıkmadı.

| Commit | Hata |
|---|---|
| `33a8a1c` | `job.php` entry'yi dilsiz yüklüyordu — TR adresinde bütün entry içeriği İngilizceydi |
| `5f26f67` | Üretilen özet, direnç etiketi **tanımlarını** basıyordu; tam cümleler virgülle birleşince paragraf okunamaz hâle geliyordu |
| `62b5696` | Arama animasyonu hardcoded İngilizce meslek adları yazıyordu (`plumber` katalogda bile yok) |
| `ae04c5a` | `mb_strtolower('İdari Asistan')` → `i̇dari asistan`; birleşen nokta sayfa başlığında görünüyordu |
| `17c848d` | `/tr/degisiklikler` kendini `willaistealit.com/changelog` olarak canonical gösteriyordu |
| `952435b` | (4A9'da) `route.php` dili geçiriyordu ama dört şablon onu hiç okumuyordu |

## Kabul edilmiş durumlar

**`data/page-reviewed.json` tarihleri boş — sekiz satır (EN + TR × dört sayfa).**
Sitemap o satırları `lastmod`'suz yayınlıyor; XML geçerli. Karar: doğrulanmış
editoryal inceleme tarihi gelene kadar boş kalır — uydurulmuş tarih, tarihsizlikten
kötüdür. Validator bunları uyarı olarak listeler ve listelemeye devam etmelidir.

**ES kuyruğu 121 anahtar** (69 editoryal + 52 `landscape`/`sponsor`). Faz 5'in işi.
`activeLangs`'e `'es'` girmedi; `/es/*` 404.

**`data/pending-tr-titles.json` tamamen tüketildi** ama dosya duruyor ve kodda hiçbir
referansı yok. Aktivasyon öncesi gereksiz temizlik yapılmadı (kullanıcı kararı).

**`doctor`: `CONTACT_EMAIL dolu`** uyarısı — Faz 4 ile ilgisiz, mevcut durum.

## Ertelenen backlog

**`asset()`'i filemtime yerine içerik hash'ine geçirmek.** Faz 4 boyunca üç kez
golden tazelemesi gerektirdi: CSS/JS dosyasına *dokunmak* ile içeriğini *değiştirmek*
arasında ayrım yapmıyor ve `?v=` her sayfanın `links` kümesinde. Her seferinde farkın
tamamının cache-buster olduğu kanıtlandı ve ayrı bakım commit'iyle yakalandı. Kendi
görevi olarak ele alınacak.

## Yapılmayan dış işlem

**Search Console'a sitemap gönderimi.** Plan bunu 4C3 Adım 3'e koyuyor ve
"lansman anında yapılır, Faz 6'ya bırakılmaz" diyor — ama açıkça dış işlem sayıyor
ve ayrı onay istiyor. **Yapılmadı.** TR aylarca indekslenmeden kalmaması için bu
adımın ayrıca onaylanması gerekiyor.

Deploy de yapılmadı: `main` origin'den ileride, push yok.

## Sıradaki

**Faz 5 — İspanyolca.** 4A'nın altyapısı hazır bekliyor; ES için tekrar edilmesi
gereken tek şey 4B (içerik) ve 4C (aktivasyon). ES kuyruğu 121 anahtar artı 17
entry'nin `es.json`'ı.

Faz 4'te kurulan kapılar ES'de de geçerli: `locale_pending('es')` sıfırlanmadan
aktivasyon yapılamaz, ve **sabit sayfaların gerçekten yerelleştiğini render ederek
doğrulayan test** (`tests/request_lang.test.php`) `landscape`/`sponsor` boşluğunun
ES'de tekrar etmesini engeller — o boşluk `locale_pending()` tarafından görülemiyordu.

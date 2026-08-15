# Faz 3 Kapanış Görevleri — devralma brifingi

> Bu belge **bağlamsız** bir oturumun devralabilmesi için yazıldı. Faz 3'ün son altı
> işini içerir. Bitince Faz 3 tamamlanmış sayılır ve Faz 4 planına geçilir.

**Plan:** `docs/architecture/2026-08-15-cok-dilli-faz-3-plan.md`
**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md`

## Devralınan durum

Son commit: `ac7118e`. Çalışma alanı temiz, `main` origin'den ileride, **push yok**.

```
753 test geçti, 0 kaldı          golden 15/15 semantik · 10/15 byte
validator: Hata yok              cache-check: çıkış 0
smoke: Matris temiz              self-test: 4 içerik türünde kırmızı verebiliyor
activeLangs: ['en']              /tr/* ve /es/* 404
bekleyen editoryal: tr 43 · es 43   (hepsi methodology.*)
```

Yapılmış olanlar: locale iskeleti (`inc/lang/{Base,En,Tr,Es}.php`, `data/locale/*.php`),
golden altyapısı (`tools/golden.php` + `golden.sh`), `config.php`'den dile bağlı
metinlerin çıkarılması, cümle üreticilerin `Lang` sınıflarına taşınması, ve şu
şablonların yerelleştirilmesi: `inc/header.php`, `inc/footer.php`, `job.php`,
`index.php`, `404.php`, `unavailable.php`, `changelog.php`, `methodology.php`.

**Değişmez kısıt:** `activeLangs` Faz 3 boyunca `['en']` kalır. TR/ES URL'leri açılmaz.

---

## Görev 1 — `llms.php` metinlerini locale'e çıkar

`llms.php` çıktısını `$L('...')` kapanışıyla satır satır basıyor; metin bazen
birleştirilmiş ifadelerin içinde (`$L('- ' . $key . ': ' . $def)`). **Dosyayı satır
satır oku**, grep ile tarama yeterli değil.

- Anahtar ön eki: **`llms.`** (editoryal manifestoda tanımlı — `data/locale/editorial.php`).
- İngilizce değerler **birebir** taşınır; tek karakter değişmez.
- Değer içinde markdown/işaretleme varsa değerin içinde kalır.
- `VERDICTS` / `RESISTANCE_KEYS` üzerinde dönen bloklar zaten `verdict_meta()` ve
  `tag_definition()` kullanıyor — onlara dokunma.
- Şablon `$lang`, `$L`, `$routes` kurulumunu diğer sayfalardaki desenle alır:
  ```php
  $lang   = $lang ?? DEFAULT_LANG;
  $L      = lang_for($lang);
  $routes = load_routes();
  ```
- Elle kurulan URL'ler `url_for()` / `path_for()`'a geçer (`llms.txt` mutlak URL
  istiyor → `url_for`). İş bağlantıları **entry id** ile üretilir.

**Kabul:** `./tools/golden.sh --check` → `llms` satırı semantik **aynı**.

## Görev 2 — `llms.*` için TR/ES yazılmaz

`data/locale/tr.php` ve `es.php`'ye **hiçbir `llms.` anahtarı eklenmez**. Çeviri yok,
fallback yok, ekrana anahtar basma yok.

Doğrulama:
```bash
php -r 'require "inc/locale.php"; foreach(["tr","es"] as $l)
  printf("%s: %d bekleyen\n", $l, count(locale_pending($l)));'
```
Beklenen: 43'ten **43 + (eklenen llms anahtarı sayısı)**'na çıkmış olmalı.
`php tools/validate.php` iki dil için de uyarı basmalı, **hata değil** (diller aktif değil).

## Görev 3 — Golden semantik çıkarıcısına sıralı `h2`/`h3` ekle

`tools/golden.php` → `golden_extract_html()` şu iki alanı kazanır:

```php
preg_match_all('#<h2[^>]*>(.*?)</h2>#s', $b, $h2);
preg_match_all('#<h3[^>]*>(.*?)</h3>#s', $b, $h3);
// SIRA anlamlidir: baslik sirasi degisirse sayfa yapisi degismistir.
'h2' => array_map('golden_norm_ws', $h2[1]),
'h3' => array_map('golden_norm_ws', $h3[1]),
```

Gerekçe: Faz 3F'de `<h2>The "safe until" year</h2>` başlığında `"` → `&quot;`
kaçışı oluştu ve semantik golden bunu **görmedi** — ara başlıklar alan setinde yoktu.
Bu kör nokta Faz 4'e bırakılmıyor.

## Görev 4 — Semantik JSON'ları bilinçli olarak yeniden yakala

Alan seti genişlediği için `tests/golden/*.json` yeniden üretilmeli. **Üretim gövdeleri
değişmemeli.**

```bash
./tools/golden.sh --capture
git status --short tests/golden | sort | uniq -c
```

**Kabul kriteri — ikisi birden:**
1. Değişen dosyaların **tamamı `.json`**. Hiçbir `.html`, `.txt`, `.xml`, `.png`
   değişmemiş olmalı:
   ```bash
   git diff --name-only tests/golden | grep -v '\.json$' && echo "HATA: govde degisti" || echo "ok: yalnizca semantik JSON"
   ```
2. `.json` farkları **yalnızca yeni `h2`/`h3` alanlarının eklenmesi** olmalı; mevcut
   alanların değerleri değişmemeli:
   ```bash
   git diff tests/golden | grep '^[-+]' | grep -v '^[-+][-+]' | grep -v '"h2"\|"h3"\|^\+ *"' | head
   ```
   Çıktı boşsa temiz.

Bir gövde dosyası değiştiyse **dur** — o, Görev 1'in çıktıyı bozduğu anlamına gelir.

## Görev 5 — Self-test yeni alanı kanıtlasın

`golden_self_test()`'e beşinci vaka eklenir: bir `<h2>` içeriği bozulur ve
karşılaştırıcının **`h2` alanında** kırmızı verdiği gösterilir.

```php
['methodology', 'text/html; charset=UTF-8',
 static fn (string $b): string => (string)preg_replace('#(<h2[^>]*>)([^<]*)#', '$1BOZUK', $b, 1),
 'h2'],
```

```bash
php tools/golden.php --self-test; echo "cikis: $?"
```
Beklenen: **beş** vakanın hepsi `ok`, çıkış 0. `h2` vakası "yanlış alanda" derse
çıkarıcı yanlış yazılmıştır.

## Görev 6 — Faz 3 kapanış kontrolü (sekizi de)

```bash
php tests/run.php                     # 0 kaldi, uyari 0
php tools/validate.php                # Hata yok (tr/es editoryal UYARISI beklenir)
php tools/build-index.php             # 17 entry
php tools/doctor.php                  # kritik hata yok
./tools/golden.sh --check             # 15/15 semantik
./tools/golden.sh --cache-check       # cikis 0
php tools/golden.php --self-test      # 5/5, cikis 0
./tools/smoke.sh                      # Matris temiz
git status --short                    # bos
```

Ek olarak:
```bash
php -r 'require "inc/routes_cache.php"; echo implode(",", load_routes()["activeLangs"]), "\n";'   # en
grep -rn "SITE_TAG" --include="*.php" .                                                          # ciktisi yok
grep -nE "'(label|blurb)' *=>" inc/config.php                                                    # ciktisi yok
```

---

## Commit sınırları

| Commit | Dosyalar |
|---|---|
| `refactor: extract the llms.txt source into locale keys (English only)` | `llms.php`, `data/locale/en.php` |
| `test: add ordered headings to the golden semantic extractor` | `tools/golden.php` |
| `test: recapture golden semantics after widening the field set` | `tests/golden/*.json` **yalnızca** |

`git add -A` **kullanılmaz**; her commit öncesi `git diff --cached --name-status`
beklenen listeyle karşılaştırılır. Kontrollerden biri kırmızıysa **commit atılmaz**.

## Bittiğinde

Faz 3 tamamlanmış sayılır. Faz 4'ün **ilk işi**, kuyruktaki TR ve ES editoryal
anahtarlarını (methodology + llms) doldurmak ve validator blocker'ını yeşile
çevirmektir. Bir dil, kuyruğu sıfırlanmadan `activeLangs`'e eklenmez — mekanizma
tam olarak bunu engellemek için var.

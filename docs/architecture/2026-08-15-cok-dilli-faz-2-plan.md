# Çok Dilli Mimari — Faz 2 Uygulama Planı (veri şeması + migration)

> **Agentic worker'lar için:** GEREKLİ ALT-SKILL: `superpowers:executing-plans`.
> Adımlar `- [ ]` kutucuk sözdizimi kullanıyor.

**Amaç:** 17 düz İngilizce entry'yi `data/jobs/<id>/{common,en}.json` yapısına **kayıpsız**
taşımak, hazır bekleyen TR/ES içeriğini aynı dizinlere almak, ve eski okuma yolunu tek
commit'te kaldırmak — **sitenin İngilizce çıktısını değiştirmeden**.

**Mimari:** Yeni yükleyici `common.json + <kaynak dil>.json + <istenen dil>.json` üçlüsünü
birleştirip **bugünkü şablonların beklediği düz diziyi** üretir. Böylece `job.php`,
`index.php`, `sitemap.php`, `og.php` ve `related_jobs()` Faz 2'de **yerelleştirilmez** —
içerik üretimleri aynı kalır; yalnızca dosya yolunu elle kuran ve cache anahtarı üreten
yerler teknik olarak uyarlanır. Locale sistemi ve TR/ES yayını Faz 3–4'tür.

**Teknoloji:** PHP 8.3, bağımlılık yok, build adımı yok. Testler `tests/run.php`.

**Spec:** `docs/architecture/2026-08-15-cok-dilli-mimari.md` (§2, §3, §8)
**Önceki plan:** `docs/architecture/2026-08-15-cok-dilli-faz-0-1-plan.md`

## Faz 1'den devralınan durum

- Front controller bağlı, 168 assert yeşil, smoke matrisi temiz.
- `activeLangs` **`['en']`** — Faz 2 bunu değiştirmez. TR/ES dosyaları diske iner ama
  hiçbir URL'e servis edilmez; `/tr/*` 404 döner. Yayın Faz 4'tür.
- `build_routes()` bugün `load_all_jobs()` üzerinden EN slug tablosu üretiyor.

## Global kısıtlar

- **PHP 8.3**, `declare(strict_types=1);`, kod yorumları Türkçe ve ASCII.
- **Bağımlılık eklenmez.**
- **Migration aracı hiçbir dosyayı silmez ve hiçbir dosyanın üzerine yazmaz.**
  Çıktısı her zaman ayrı bir hedef dizindir.
- **Yarıda kesilen migration canlı ağacı bozmaz** — araç yalnızca hedef dizine yazar.
- **Tek commit'te:** yeni yükleyiciye geçiş + eski okuma yolunun kaldırılması + veri
  taşınması. İki formatın aynı anda okunduğu belirsiz bir ara durum bırakılmaz.
- **İngilizce çıktı değişmez** — tek bilinçli istisna `titleTr` (aşağıda §"Tek çıktı farkı").
- Her commit sonrası: diff incelemesi, `php tests/run.php`, `php tools/validate.php`,
  `php tools/build-index.php`, `git status`.

## Dosya haritası

| Dosya | Sorumluluk | Görev |
|---|---|---|
| `inc/entry.php` | Yeni yükleyici: `load_entry()`, `entry_langs()`, `entry_files()` | 2A |
| `tests/entry.test.php` | Yükleyici testleri (gerçek fixture'lar + geçici ağaç) | 2A |
| `tools/migrate-jobs.php` | dry-run → ayrı çıktı → verify | 2B |
| `data/jobs/<id>/common.json` | `id`, `category`, `taskOrder` | 2D |
| `data/jobs/<id>/en.json` | İngilizce kaynak: yargı + düzyazı | 2D |
| `data/jobs/<id>/{tr,es}.json` | `data/i18n/`'den taşınır (2 entry) | 2D |
| `inc/functions.php` | `load_job()`/`load_all_jobs()` yeni yükleyiciye devreder | 2D |
| `og.php`, `sitemap.php`, `job.php` | Düz dosya yolu → dizin | 2D |
| `tools/validate.php` | Dil başına doğrulama + devralma bütünlüğü | 2D |
| `tools/sync-evidence.php` | `evidenceStrength`'i `en.json`'a yazar | 2D |
| `inc/routes_cache.php` | `published` gerçek dil dosyalarından hesaplanır | 2D |
| `docs/memory/decisions/…` | `data/i18n/README.md`'deki editoryal kararlar | 2E |

---

## Şema kararları (uygulamadan önce netleşmesi gerekenler)

### K1 — Sahiplik kuralı (ana spec §3.1'e işlendi)

> Bir dil dosyası yargının **sahibidir** ancak ve ancak `assessmentSourceLocale` kendi
> diline eşitse. Kaynak dosyalar yargı alanlarını **taşımak zorundadır**; kaynak
> olmayanlar **taşıyamaz**, devralır. `assessmentScope` ayrı bir eksendir ve yargının
> *kapsamını* söyler: `global` (pazara özgü değil) veya `local` (§7.1'e tabi).

Bu kural artık yalnızca bu planda değil, **ana spec §3.1 ve §3.4'te** yazılı
(`docs: clarify assessment source ownership`). Faz 2 sonrası durum:

| Dosya | scope | sourceLocale | Kaynak mı | Yargı alanları |
|---|---|---|---|---|
| `en.json` | `global` | `en` | evet | **var** |
| `tr.json` (v1) | `global` | `en` | hayır | **yok** — devralınır |
| `es.json` (v1) | `global` | `en` | hayır | **yok** — devralınır |
| `tr.json` (ileride) | `local` | `tr` | evet | **var** + kendi `sources` |

`translationReviewed` kaynak olmayan dosyalarda **zorunlu**, kaynak dosyada
**isteğe bağlıdır** (kaynakta çeviri yoktur; yazılmazsa `assessmentReviewed`'a eşit sayılır).

### K2 — Görev ID'si üretimi ve kararlılık

İki fixture entry'nin ID'leri **elle yazılmış** ve İngilizce görev adından türetilemez
(`"Scanning items and taking payment"` → `scan-payment`). Kural:

1. **`data/i18n/<id>/common.json` varsa onun `taskOrder`'ı kazanır.** Düz dosyadaki
   görev sırasıyla **konum konum** eşleştirilir. Ön koşul: görev sayıları eşit olmalı;
   değilse migration o entry'de **durur ve hata verir** (sessiz kayma yasak).
2. Yoksa ID İngilizce görev adından **deterministik** üretilir:

```php
/**
 * Gorev adindan kararli ID. Deterministik olmasi sart: ayni ad her zaman ayni ID.
 * ASCII katlama + durak kelime atma + ilk 3 anlamli kelime.
 */
function task_id_from_name(string $name, array $taken): string
{
    $stop = ['and','or','the','a','an','of','for','to','in','on','with','between','at','into','its','their'];
    $s = mb_strtolower($name, 'UTF-8');
    $s = strtr($s, ['ı'=>'i','ğ'=>'g','ş'=>'s','ö'=>'o','ü'=>'u','ç'=>'c',
                    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','&'=>' and ']);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? '';
    $words = array_values(array_filter(preg_split('/\s+/', trim($s)) ?: [],
                          static fn ($w) => $w !== '' && !in_array($w, $stop, true)));
    $id = implode('-', array_slice($words, 0, 3));
    $id = substr($id, 0, 40);
    $id = rtrim($id, '-');
    if ($id === '') {
        $id = 'task';
    }
    $base = $id;
    $n = 2;
    while (in_array($id, $taken, true)) {   // ayni entry icinde cakisma
        $id = $base . '-' . $n++;
    }
    return $id;
}
```

**Kararlılık kuralı:** ID'ler migration sırasında **bir kez** üretilir ve
`common.json`'a yazıldıktan sonra **dondurulur**. Görev adı sonradan düzenlenirse ID
değişmez — çünkü TR/ES dosyaları o ID'ye bağlıdır. `migrate-jobs.php` mevcut bir
`common.json`'ın **üzerine yazmaz**; yeniden üretmesi için dosyanın elle silinmesi gerekir.

### K3 — Yükleyicinin ürettiği şekil

Şablonlar bugün `$job['tasks']`'ı **sıralı liste** olarak dolaşıyor ve her görevde
`name`/`verdict`/`note`/`tags` bekliyor. Yükleyici `tasks{}` nesnesini `taskOrder`'a göre
**listeye düzleştirir**. Böylece `job.php`, `index.php`, `landscape.php`, `llms.php`,
`inc/functions.php`'deki `geo_answer()`/`faq_pairs()` Faz 2'de hiç değişmez.

`lastReviewed` de **uyumluluk takma adı** olarak üretilir (`assessmentReviewed`'dan). 14
çağrı yeri Faz 2'de dokunulmadan kalır; yeniden adlandırma Faz 3'ün işidir.

### K4 — Tek çıktı farkı: `titleTr` ölür

`titleTr` bugün 4 yerde okunuyor: entry sayfasındaki `İşe Alım Uzmanı ·` satırı,
`build-index.php`'nin `tr` alanı ve `index.php`'nin `data-search` özniteliği.

Bu alanın kaldırılması bir gerileme değil, projenin **açık hedefi**: İngilizce sayfada
Türkçe başlık dil karmaşası yaratıyor. Faz 2'de kaldırılır ve:

- 17 Türkçe başlığın hepsi **`data/pending-tr-titles.json`** dosyasına taşınır (2D) —
  hiçbiri kaybolmaz, TR entry'leri yazılırken tohum olur.
- Entry sayfasındaki satır Türkçe adı kaybeder, `Last reviewed: …` kısmı kalır.
- Arama `data-search`'ten Türkçe adı kaybeder. **Faz 4'te** `aka` ile ve dile özgü
  index'le geri gelir; Faz 2'de İngilizce sitede Türkçe arama zaten anlamsızdı.

Bu, Faz 2'nin **tek** kasıtlı çıktı değişikliğidir ve öncesi/sonrası kanıtla gösterilir.

### K5 — Anlamsal eşitliğin tanımı

Eski ve yeni yükleyici çıktıları şu **18 anahtar** üzerinden karşılaştırılır (kod
tabanından `grep` ile çıkarıldı, tahmin değil):

```
slug · title · category · verdict · safeUntil · oneLiner · summary · tasks
resistanceTags · whatSurvives · adaptPrompt · adaptTools · sources
lastReviewed · evidenceStrength · geoAnswer · formerSlugs · reviewed
```

`tasks` içinde: `name · verdict · note · tags` (sıra dahil).

**`titleTr` karşılaştırmadan bilinçli olarak dışarıdadır** (K4). Yeni alanlar
(`assessmentScope`, `assessmentSourceLocale`, `assessmentReviewed`,
`translationReviewed`, `aka`) karşılaştırmaya girmez — eskide karşılıkları yoktur.

---

### Görev 2A — Yeni yükleyici (bağlamadan)

**Amaç**
`common + kaynak dil + istenen dil` üçlüsünü birleştirip şablonların beklediği düz
diziyi üreten yükleyiciyi yazmak. Bu commit'te **hiçbir veri taşınmaz ve hiçbir çağrı
yeri değişmez** — site hâlâ düz dosyaları okur.

**Değiştirilecek dosyalar**
- Oluştur: `inc/entry.php`
- Oluştur: `tests/entry.test.php`

**Arayüzler**
- Tüketir: `JOBS_DIR` (`inc/config.php`), `LANGS`/`DEFAULT_LANG` (`inc/routing.php`).
- Üretir:
  - `entry_dir(string $id, ?string $root = null): string`
  - `entry_langs(string $id, ?string $root = null): array` — o entry'nin **yayınlanmış**
    dilleri (zorunlu düzyazı alanlarının tamamı dolu olanlar)
  - `load_entry(string $id, string $lang = DEFAULT_LANG, ?string $root = null): ?array`
  - `entry_dependency_files(string $id, string $lang, ?string $root = null): array` —
    cache geçerliliği için (spec §8.1)
  `$root` **test icin enjekte edilebilir**; null ise `JOBS_DIR`.

**Uygulama ayrıntısı**

```php
<?php
/**
 * Cok dilli entry yukleyici.
 * common.json + <kaynak dil>.json + <istenen dil>.json -> sablonlarin bekledigi duz dizi.
 * "Yargi devralinir, duzyazi devralinmaz" (spec 3.1) burada uygulanir.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/routing.php';

/** Devralinabilir yargi alanlari (spec 3.1). */
const INHERITED_FIELDS = ['verdict', 'safeUntil', 'resistanceTags', 'sources',
                          'evidenceStrength', 'assessmentReviewed'];

/** Devralinamayan duzyazi alanlari — eksikse o dil YAYINLANMAMIS sayilir (spec 3.2). */
const REQUIRED_PROSE = ['slug', 'title', 'oneLiner', 'summary', 'tasks',
                        'whatSurvives', 'adaptPrompt'];

function entry_dir(string $id, ?string $root = null): string
{
    return ($root ?? JOBS_DIR) . '/' . $id;
}

/** Tek dosyayi oku; yoksa ya da bozuksa null. */
function entry_read(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/**
 * Bir dil dosyasi tek basina yayinlanabilir mi.
 * Ust duzey duzyazinin varligi YETMEZ: gorev metinleri devralinmaz (spec 3.2),
 * bu yuzden siradaki HER gorev icin bu dilde name ve note bulunmali.
 * Kontrol burada olmak zorunda — route cache validator kosmadan da uretilebiliyor,
 * yani eksik gorev metni tasiyan bir dil aksi halde "published" sayilabilirdi.
 */
function entry_lang_publishable(?array $doc, ?array $common = null): bool
{
    if ($doc === null) {
        return false;
    }
    foreach (REQUIRED_PROSE as $f) {
        if (!isset($doc[$f]) || $doc[$f] === '' || $doc[$f] === []) {
            return false;
        }
    }
    // Sira dil dosyasinda ezilebilir; localTasks da gecerli bir kaynaktir (spec 2.3).
    $order = (array)($doc['taskOrder'] ?? $common['taskOrder'] ?? []);
    if ($order === []) {
        return false;
    }
    foreach ($order as $tid) {
        $tid = (string)$tid;
        $task = $doc['tasks'][$tid] ?? $doc['localTasks'][$tid] ?? null;
        if (!is_array($task)
            || (string)($task['name'] ?? '') === ''
            || (string)($task['note'] ?? '') === '') {
            return false;
        }
    }
    return true;
}

/** @return string[] Bu entry'nin yayinlanmis dilleri, LANGS sirasinda. */
function entry_langs(string $id, ?string $root = null): array
{
    $dir    = entry_dir($id, $root);
    $common = entry_read($dir . '/common.json');
    if ($common === null) {
        return [];
    }
    $out = [];
    foreach (LANGS as $lang) {
        if (entry_lang_publishable(entry_read($dir . '/' . $lang . '.json'), $common)) {
            $out[] = $lang;
        }
    }
    return $out;
}

/**
 * Cache gecerliligi icin bagimlilik dosyalari (spec 8.1).
 * TR sayfasi en.json'a BAGLIDIR — devralma yuzunden.
 */
function entry_dependency_files(string $id, string $lang, ?string $root = null): array
{
    $dir = entry_dir($id, $root);
    $files = [$dir . '/common.json', $dir . '/' . $lang . '.json'];
    $doc   = entry_read($dir . '/' . $lang . '.json');
    $src   = (string)($doc['assessmentSourceLocale'] ?? DEFAULT_LANG);
    $files[] = $dir . '/' . $src . '.json';   // TR sayfasi en.json'a BAGLI
    // inheritedSources ise global kaynak dosyasi da bagimliliktir.
    if (!empty($doc['inheritedSources'])) {
        $files[] = $dir . '/' . DEFAULT_LANG . '.json';
    }
    return array_values(array_unique(array_filter($files, 'is_file')));
}

/**
 * Bir entry'yi istenen dilde yukle.
 * @return array|null Sablonlarin bekledigi duz dizi; yayinlanmamissa null.
 */
function load_entry(string $id, string $lang = DEFAULT_LANG, ?string $root = null): ?array
{
    if (!valid_slug($id) || !in_array($lang, LANGS, true)) {
        return null;
    }
    $dir    = entry_dir($id, $root);
    $common = entry_read($dir . '/common.json');
    $doc    = entry_read($dir . '/' . $lang . '.json');
    if ($common === null || !entry_lang_publishable($doc, $common)) {
        return null;
    }

    // Yargi kaynagi: kendisi kaynaksa kendisi, degilse sourceLocale'in dosyasi.
    $srcLang = (string)($doc['assessmentSourceLocale'] ?? DEFAULT_LANG);
    $src     = $srcLang === $lang ? $doc : entry_read($dir . '/' . $srcLang . '.json');
    if ($src === null) {
        return null;
    }

    $out = [
        'id'       => $id,
        'slug'     => (string)$doc['slug'],
        'lang'     => $lang,
        'category' => (string)($common['category'] ?? ''),
        'title'    => (string)$doc['title'],
        'oneLiner' => (string)$doc['oneLiner'],
        'summary'  => (string)$doc['summary'],
        'whatSurvives' => (string)$doc['whatSurvives'],
        'adaptPrompt'  => (string)$doc['adaptPrompt'],
        'adaptTools'   => (array)($doc['adaptTools'] ?? []),
        'aka'          => (array)($doc['aka'] ?? []),
        'formerSlugs'  => (array)($doc['formerSlugs'] ?? []),
        'assessmentScope'        => (string)($doc['assessmentScope'] ?? 'global'),
        'assessmentSourceLocale' => $srcLang,
        'translationReviewed'    => (string)($doc['translationReviewed'] ?? ''),
    ];

    // Yargi alanlari: kaynak dosyadan (spec 3.1, K1).
    foreach (INHERITED_FIELDS as $f) {
        if (isset($src[$f])) {
            $out[$f] = $src[$f];
        }
    }

    // inheritedSources: yerel kaynaklar global kaynaklarin YERINE degil USTUNE gelir
    // (spec 3.3). Yalnizca kaynak dosyanin kendisi bunu talep edebilir.
    if ($srcLang === $lang && !empty($doc['inheritedSources'])) {
        $global = entry_read($dir . '/' . DEFAULT_LANG . '.json');
        $out['sources'] = array_values(array_unique(array_merge(
            (array)($global['sources'] ?? []),
            (array)($doc['sources'] ?? [])
        )));
    }
    // Uyumluluk takma adi: 14 cagri yeri Faz 2'de dokunulmadan kalsin (K3).
    $out['lastReviewed'] = (string)($out['assessmentReviewed'] ?? '');
    if (isset($doc['geoAnswer'])) {
        $out['geoAnswer'] = (string)$doc['geoAnswer'];
    }
    if (isset($src['reviewed'])) {
        $out['reviewed'] = $src['reviewed'];
    }

    // Gorevler: taskOrder sirasinda LISTEYE duzlestirilir (K3).
    // Sira dil dosyasinda ezilebilir; localTasks yalnizca o dilde bulunur (spec 2.3).
    $out['tasks'] = [];
    $srcTasks   = (array)($src['tasks'] ?? []);
    $localTasks = (array)($doc['localTasks'] ?? []);
    $order      = (array)($doc['taskOrder'] ?? $common['taskOrder'] ?? []);
    foreach ($order as $tid) {
        $tid  = (string)$tid;
        $mine = (array)($doc['tasks'][$tid] ?? $localTasks[$tid] ?? []);
        $from = (array)($srcTasks[$tid] ?? []);
        if ($mine === [] && $from === []) {
            continue;
        }
        $task = ['name' => (string)($mine['name'] ?? $from['name'] ?? '')];
        // Gorev yargisi da devralinir; yerel dosya ACIKCA ezebilir (spec 3.3).
        $task['verdict'] = (string)($mine['verdict'] ?? $from['verdict'] ?? '');
        $note = (string)($mine['note'] ?? '');
        if ($note !== '') {
            $task['note'] = $note;
        } elseif (isset($from['note']) && $srcLang === $lang) {
            $task['note'] = (string)$from['note'];
        }
        $tags = $mine['tags'] ?? $from['tags'] ?? null;
        if ($tags !== null && $tags !== []) {
            $task['tags'] = array_values((array)$tags);
        }
        $out['tasks'][] = $task;
    }

    return $out;
}
```

> **Dikkat — `note` devralınmaz.** `note` düzyazıdır (spec §3.2). Kaynak dilin notu
> yalnızca istenen dil kaynak dilin kendisiyse kullanılır; aksi halde yerel dosya
> kendi notunu taşımak zorundadır. Tam kapsam kontrolü **`entry_lang_publishable()`
> içindedir** — `validate.php`'ye bırakılmaz, çünkü route cache validator koşmadan da
> üretilebiliyor ve yarım bir dil "published" görünürdü. Doğrulanan veri: mevcut 17
> entry'nin 122 görevinin tamamında `name` ve `note` var, iki i18n dili de tam —
> kural hiçbir entry'yi yayınlanamaz hale getirmiyor.

**Testler** gerçek fixture'ları kullanır. `administrative-assistant` ve `cashier`
üç dilde de içerik taşıyan **ilk gerçek fixture'lardır**; test bunları geçici bir ağaca
kurar (canlı `data/jobs/` değişmeden).

- [ ] **Adım 1: Testleri yaz**

`tests/entry.test.php` — geçici ağaç kurar, gerçek `data/i18n/` dosyalarını ve düz EN
dosyasını oraya kopyalar, yükleyiciyi sınar:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/entry.php';

/* Gecici agac: canli data/jobs/ DEGISMEZ. */
$root = sys_get_temp_dir() . '/wais-entry-' . bin2hex(random_bytes(4));
$id   = 'cashier';
@mkdir($root . '/' . $id, 0775, true);

$flat = json_decode((string)file_get_contents(ROOT . '/data/jobs/cashier.json'), true);
$common = json_decode((string)file_get_contents(ROOT . '/data/i18n/cashier/common.json'), true);
file_put_contents($root . '/' . $id . '/common.json', (string)json_encode($common));
copy(ROOT . '/data/i18n/cashier/tr.json', $root . '/' . $id . '/tr.json');
copy(ROOT . '/data/i18n/cashier/es.json', $root . '/' . $id . '/es.json');

/* en.json'i duz dosyadan elle kur — migration araci Gorev 2B'de gelecek. */
$en = [
    'assessmentScope' => 'global', 'assessmentSourceLocale' => 'en',
    'assessmentReviewed' => $flat['lastReviewed'], 'slug' => 'cashier',
    'title' => $flat['title'], 'oneLiner' => $flat['oneLiner'], 'summary' => $flat['summary'],
    'whatSurvives' => $flat['whatSurvives'], 'adaptPrompt' => $flat['adaptPrompt'],
    'adaptTools' => $flat['adaptTools'], 'verdict' => $flat['verdict'],
    'safeUntil' => $flat['safeUntil'], 'resistanceTags' => $flat['resistanceTags'],
    'sources' => $flat['sources'], 'evidenceStrength' => $flat['evidenceStrength'],
    'geoAnswer' => $flat['geoAnswer'], 'reviewed' => $flat['reviewed'], 'tasks' => [],
];
foreach ($common['taskOrder'] as $i => $tid) {
    $t = $flat['tasks'][$i];
    $en['tasks'][$tid] = ['name' => $t['name'], 'verdict' => $t['verdict'], 'note' => $t['note']]
                       + (isset($t['tags']) ? ['tags' => $t['tags']] : []);
}
file_put_contents($root . '/' . $id . '/en.json', (string)json_encode($en));

// --- yayinlanmis diller ---
t_eq(['en', 'tr', 'es'], entry_langs($id, $root), 'uc dil de yayinlanmis');
t_eq([], entry_langs('hayali-meslek', $root), 'olmayan entry: dil yok');

// --- EN yukleme: duz dosyayla ayni yargi ---
$en_out = load_entry($id, 'en', $root);
t_eq($flat['verdict'],        $en_out['verdict'],        'EN verdict');
t_eq($flat['safeUntil'],      $en_out['safeUntil'],      'EN safeUntil');
t_eq($flat['resistanceTags'], $en_out['resistanceTags'], 'EN resistanceTags');
t_eq($flat['lastReviewed'],   $en_out['lastReviewed'],   'lastReviewed uyumluluk takma adi');
t_eq($flat['geoAnswer'],      $en_out['geoAnswer'],      'geoAnswer korunur');
t_eq('service',               $en_out['category'],       'kategori common.json dan');
t_eq(6,                       count($en_out['tasks']),   'gorev sayisi');
t_eq($flat['tasks'][0]['name'], $en_out['tasks'][0]['name'], 'gorev sirasi taskOrder a uyar');
t_eq($flat['tasks'][3]['tags'], $en_out['tasks'][3]['tags'], 'gorev tag leri');

// --- TR yukleme: DUZYAZI yerel, YARGI devralinmis (spec 3.1) ---
$tr = load_entry($id, 'tr', $root);
t_eq('Kasiyer', $tr['title'],  'TR baslik yerel');
t_eq('kasiyer', $tr['slug'],   'TR slug yerel');
t_eq($flat['verdict'],   $tr['verdict'],   'TR verdict EN den devralindi');
t_eq($flat['safeUntil'], $tr['safeUntil'], 'TR safeUntil devralindi');
t_eq($flat['sources'],   $tr['sources'],   'TR sources devralindi');
t_eq('global',           $tr['assessmentScope'],        'TR scope global');
t_eq('en',               $tr['assessmentSourceLocale'], 'TR kaynak dili en');
t_eq('2026-08-15',       $tr['translationReviewed'],    'TR ceviri tarihi');
t_eq(6, count($tr['tasks']), 'TR gorev sayisi');
t_eq('Ürün okutma ve ödeme alma', $tr['tasks'][0]['name'], 'TR gorev adi yerel');
t_eq($flat['tasks'][0]['verdict'], $tr['tasks'][0]['verdict'], 'TR gorev yargisi devralindi');
t_eq($flat['tasks'][3]['tags'],    $tr['tasks'][3]['tags'],    'TR gorev tag leri devralindi');
// Duzyazi ASLA devralinmaz: TR notu Ingilizce olamaz.
t_eq(true, str_contains($tr['tasks'][0]['note'], 'Self-servis'), 'TR notu yerel');

// --- ES yukleme ---
$es = load_entry($id, 'es', $root);
t_eq('Cajero', $es['title'], 'ES baslik');
t_eq($flat['verdict'], $es['verdict'], 'ES verdict devralindi');

// --- Eksik/bozuk durumlar ---
t_eq(null, load_entry($id, 'de', $root),          'bilinmeyen dil');
t_eq(null, load_entry('hayali', 'en', $root),     'olmayan entry');
t_eq(null, load_entry('../etc', 'en', $root),     'path traversal reddedilir');

// Zorunlu ust duzey duzyazi eksikse o dil yayinlanmamis sayilir.
$half = json_decode((string)file_get_contents($root . '/' . $id . '/es.json'), true);
$orig = $half;
unset($half['summary']);
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null,          load_entry($id, 'es', $root), 'eksik duzyazi -> yayinlanmamis');
t_eq(['en', 'tr'],  entry_langs($id, $root),      'eksik dil listeden duser');

// GOREV METNI eksikse de yayinlanmamis sayilir — ust duzey alanlar tam olsa bile.
$half = $orig;
unset($half['tasks']['floor-service']['note']);
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null,         load_entry($id, 'es', $root), 'eksik gorev notu -> yayinlanmamis');
t_eq(['en', 'tr'], entry_langs($id, $root),      'eksik gorev notu dili listeden dusurur');

$half = $orig;
$half['tasks']['floor-service']['name'] = '';
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null, load_entry($id, 'es', $root), 'bos gorev adi -> yayinlanmamis');

// Bir gorev tamamen eksikse de.
$half = $orig;
unset($half['tasks']['age-restricted']);
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(null, load_entry($id, 'es', $root), 'eksik gorev -> yayinlanmamis');

// localTasks siradaki gorevi karsilayabilir.
$half = $orig;
$half['taskOrder']  = ['scan-payment', 'local-x'];
$half['localTasks'] = ['local-x' => ['name' => 'Tarea local', 'note' => 'Nota local.',
                                     'verdict' => 'safe', 'tags' => ['regulated']]];
file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($half));
t_eq(['en', 'tr', 'es'], entry_langs($id, $root), 'localTasks siradaki gorevi karsilar');
t_eq(2, count(load_entry($id, 'es', $root)['tasks']), 'ezilmis taskOrder uygulanir');

file_put_contents($root . '/' . $id . '/es.json', (string)json_encode($orig));

// --- Yerel kapsam: sahiplik, inheritedSources, taskOrder ezme, localTasks ---
$local = json_decode((string)file_get_contents($root . '/' . $id . '/tr.json'), true);
$local['assessmentScope']        = 'local';
$local['assessmentSourceLocale'] = 'tr';
$local['assessmentReviewed']     = '2026-10-03';
$local['verdict']                = 'shrinking';
$local['safeUntil']              = '2033';
$local['resistanceTags']         = ['regulated'];
$local['evidenceStrength']       = 'strong';
$local['inheritedSources']       = true;
$local['sources']                = ['https://www.turmob.org.tr/ornek'];
$local['taskOrder']              = ['scan-payment', 'local-kdv-uyum', 'floor-service'];
$local['localTasks']             = ['local-kdv-uyum' => [
    'name' => 'KDV ve fis mevzuati uyumu', 'note' => 'Yerel gorev.',
    'verdict' => 'safe', 'tags' => ['regulated'],
]];
$local['tasks']['scan-payment']['verdict'] = 'gone';
file_put_contents($root . '/' . $id . '/tr.json', (string)json_encode($local));

$lt = load_entry($id, 'tr', $root);
t_eq('shrinking', $lt['verdict'],   'yerel kapsam kendi verdict ini tasir');
t_eq('2033',      $lt['safeUntil'], 'yerel safeUntil');
t_eq(array_values(array_unique(array_merge($flat['sources'], ['https://www.turmob.org.tr/ornek']))),
     $lt['sources'], 'inheritedSources: global + yerel birlesir, tekrarsiz');
t_eq(3, count($lt['tasks']), 'taskOrder dil dosyasinda ezilebilir');
t_eq('KDV ve fis mevzuati uyumu', $lt['tasks'][1]['name'], 'localTasks yukleniyor');
t_eq(['regulated'], $lt['tasks'][1]['tags'], 'localTasks tag leri');
t_eq('gone', $lt['tasks'][0]['verdict'], 'yerel gorev yargisi ezilebilir');

// inheritedSources kapaliyken YALNIZCA yerel kaynaklar
$local['inheritedSources'] = false;
file_put_contents($root . '/' . $id . '/tr.json', (string)json_encode($local));
t_eq(['https://www.turmob.org.tr/ornek'], load_entry($id, 'tr', $root)['sources'],
     'inheritedSources kapali: yalnizca yerel kaynaklar');

// Bagimlilik listesi inheritedSources acikken en.json'i icermeli
$local['inheritedSources'] = true;
file_put_contents($root . '/' . $id . '/tr.json', (string)json_encode($local));
t_eq(true, in_array($root . '/' . $id . '/en.json',
     entry_dependency_files($id, 'tr', $root), true), 'inheritedSources bagimliligi');

/* tr.json'i orijinal haline dondur — sonraki testler global kapsam bekliyor */
copy(ROOT . '/data/i18n/cashier/tr.json', $root . '/' . $id . '/tr.json');

// --- Cache bagimliliklari: TR, en.json'a BAGLI (spec 8.1) ---
$deps = entry_dependency_files($id, 'tr', $root);
t_eq(true, in_array($root . '/' . $id . '/en.json', $deps, true), 'TR bagimliligi en.json icerir');
t_eq(true, in_array($root . '/' . $id . '/common.json', $deps, true), 'common.json bagimlilikta');

/* Temizlik */
$rm = static function (string $d) use (&$rm): void {
    foreach (glob($d . '/*') ?: [] as $p) { is_dir($p) ? $rm($p) : @unlink($p); }
    @rmdir($d);
};
$rm($root);
```

- [ ] **Adım 2: Testlerin başarısız olduğunu doğrula**

Run: `php tests/run.php`
Beklenen: `Failed to open stream: .../inc/entry.php`

- [ ] **Adım 3: `inc/entry.php`'yi yaz** (yukarıdaki kod)

- [ ] **Adım 4: Testlerin geçtiğini doğrula**

Run: `php tests/run.php`
Beklenen: bu görevin eklediği **48 assert** geçer, toplamda **`0 kaldi`**.

- [ ] **Adım 5: Sitenin dokunulmadığını doğrula**

```bash
./tools/smoke.sh
grep -rn "entry.php" --include="*.php" . | grep -v "tests/\|inc/entry.php"
```
Beklenen: `Matris temiz.` ve ikinci komuttan **çıktı yok** — yükleyici henüz kimse
tarafından çağrılmıyor.

- [ ] **Adım 6: Commit**

```bash
git add inc/entry.php tests/entry.test.php
git commit -m "feat: add multilingual entry loader, not yet wired"
```

**Risk**
Düşük. Yeni dosya, hiçbir çağrı yeri değişmiyor, site düz dosyaları okumaya devam
ediyor. Testler geçici ağaçta çalışıyor, `data/jobs/` değişmiyor.

**Doğrulama komutu**
```bash
php tests/run.php && php tools/validate.php && ./tools/smoke.sh
```

**Beklenen sonuç**
`0 kaldi`, `Hata yok`, `Matris temiz.`

**Rollback sınırı**
`git revert` tek commit.

**Commit sınırı**
İki dosya. `inc/functions.php` ve şablonlar **girmez**.

---

### Görev 2B — `migrate-jobs.php`: dry-run, ayrı çıktı, verify

**Amaç**
Düz dosyaları yeni yapıya çeviren aracı yazmak. Araç **hiçbir şeyi silmez, hiçbir şeyin
üzerine yazmaz**; varsayılanı rapor üretmektir.

**Değiştirilecek dosyalar**
- Oluştur: `tools/migrate-jobs.php`

**Arayüzler**
- Tüketir: `load_all_jobs()` (eski yükleyici), `load_entry()` (yeni yükleyici, 2A).
- Üretir: CLI aracı. Üç mod:

```bash
php tools/migrate-jobs.php                       # dry-run raporu, HICBIR SEY yazmaz
php tools/migrate-jobs.php --out="$OUT"          # tek kok altina paketler
php tools/migrate-jobs.php --verify --out="$OUT" # semantik esitlik raporu
```

**Çıktı tek kök altında paketlenir. Hiçbir mod canlı `data/` ağacına yazmaz** —
`data/jobs/` de, `data/pending-tr-titles.json` de dokunulmaz:

```
<out>/
  jobs/
    accountant/{common,en}.json
    cashier/{common,en,tr,es}.json
    ...
  pending-tr-titles.json
  migration-report.json      # entry id listesi + gorev sayilari + ID kaynagi
```

`migration-report.json` yalnızca insan raporu değil, **rollback girdisidir**: ürettiği
entry kimliklerinin kesin listesini taşır, böylece geri alma `data/jobs/*/` gibi geniş
bir glob'a değil bilinen id'lere dayanır.

**Hedef dizin doğrulaması** — araç şunları reddeder ve çıkış kodu 1 verir:

```php
/** --out hedefi guvenli mi. Genis silme hedefleri ve canli agac reddedilir. */
function migrate_target_ok(string $out, string &$why): bool
{
    $real = realpath($out) ?: $out;
    $repo = realpath(ROOT) ?: ROOT;
    $home = getenv('HOME') ?: '/root';
    if ($out === '' || $real === '/' || $real === $home) {
        $why = 'kok, home ya da bos yol olamaz';
        return false;
    }
    if ($real === (realpath(JOBS_DIR) ?: JOBS_DIR)) {
        $why = 'canli JOBS_DIR olamaz';
        return false;
    }
    if ($real === $repo || str_starts_with($real . '/', $repo . '/')) {
        $why = 'repo kokunun icinde olamaz (git add kazasi riski)';
        return false;
    }
    if (is_dir($real) && (glob($real . '/*') ?: []) !== []) {
        $why = 'hedef dizin bos degil';
        return false;
    }
    return true;
}
```

`--force` **yoktur**: dolu hedefi kullanıcı elle siler.

**Uygulama ayrıntısı**

Alan bölüşümü (K1, K5):

| Kaynak (düz) | Hedef |
|---|---|
| `slug` | `common.json.id` **ve** `en.json.slug` |
| `category` | `common.json.category` |
| `tasks[i].name/verdict/note/tags` | `en.json.tasks[<id>]`, id'ler `common.json.taskOrder` |
| `lastReviewed` | `en.json.assessmentReviewed` |
| `title`, `oneLiner`, `summary`, `whatSurvives`, `adaptPrompt`, `adaptTools`, `geoAnswer` | `en.json` (düzyazı) |
| `verdict`, `safeUntil`, `resistanceTags`, `sources`, `evidenceStrength`, `reviewed` | `en.json` (yargı — EN kaynaktır) |
| `formerSlugs` | `en.json` |
| `titleTr` | **`<out>/pending-tr-titles.json`** → 2D'de `data/pending-tr-titles.json` (K4) |

`en.json` sabit başlık bloğuyla başlar:
```json
{ "assessmentScope": "global", "assessmentSourceLocale": "en",
  "assessmentReviewed": "2026-08", "slug": "cashier", ... }
```

Araç sırayla:

1. **`common.json` kaynağı.** `data/i18n/<id>/common.json` varsa **aynen kopyalanır** ve
   `taskOrder` konum konum eşleştirilir. Görev sayıları eşit değilse o entry **hata**
   verir ve hedefe yazılmaz. Yoksa `task_id_from_name()` ile üretilir (K2).
2. **`en.json` üretimi**, yukarıdaki bölüşümle.
3. **TR/ES kopyalama.** `data/i18n/<id>/{tr,es}.json` varsa hedef dizine **kopyalanır**
   (taşınmaz — kaynak yerinde kalır).
4. **`<out>/pending-tr-titles.json`** — bütün `titleTr` değerleri, id ile.
   Canlı ağaca **Görev 2D'de**, kapı geçildikten sonra taşınır.
5. **`<out>/migration-report.json`** — entry id listesi, görev sayıları, ID kaynağı.
5. **Rapor**: entry başına satır — kaç görev, ID'ler nereden geldi (`i18n` / `uretildi`),
   hangi diller kopyalandı, `titleTr` yakalandı mı.

`--verify` her entry için:
- eski `load_job($id)` çıktısını,
- yeni `load_entry($id, 'en', $out)` çıktısını
alır, **K5'teki 18 anahtar** üzerinde `===` karşılaştırır ve farkları listeler.
`titleTr` karşılaştırma dışıdır ve raporda ayrıca "kasıtlı fark" olarak yazılır.

```php
/** K5: anlamsal esitlik yalnizca bu anahtarlar uzerinde tanimlidir. */
const COMPARE_KEYS = ['slug','title','category','verdict','safeUntil','oneLiner','summary',
                      'tasks','resistanceTags','whatSurvives','adaptPrompt','adaptTools',
                      'sources','lastReviewed','evidenceStrength','geoAnswer',
                      'formerSlugs','reviewed'];

/**
 * Eksik alan ile bos alan ayni semantiktedir: eski entry'de hic olmayan bir liste
 * ile yeni entry'deki bos liste sahte fark uretmemeli. Normalizasyon TIP bazlidir —
 * genel gevsek karsilastirma DEGILDIR: dolu bir listenin bosalmasi ya da bir dizenin
 * degismesi hala fark olarak raporlanir.
 */
const LIST_KEYS   = ['resistanceTags', 'sources', 'adaptTools', 'formerSlugs'];
const STRING_KEYS = ['slug', 'title', 'category', 'verdict', 'safeUntil', 'oneLiner',
                     'summary', 'whatSurvives', 'adaptPrompt', 'lastReviewed',
                     'evidenceStrength', 'geoAnswer'];
const BOOL_KEYS   = ['reviewed'];

function norm_field(string $k, mixed $v): mixed
{
    if (in_array($k, LIST_KEYS, true)) {
        return array_values((array)($v ?? []));
    }
    if (in_array($k, STRING_KEYS, true)) {
        return (string)($v ?? '');      // eksik safeUntil ile '' ayni sayilir
    }
    if (in_array($k, BOOL_KEYS, true)) {
        return (bool)($v ?? false);
    }
    return $v;
}

function norm_tasks(mixed $tasks): array
{
    return array_map(static fn ($t) => [
        'name'    => (string)($t['name'] ?? ''),
        'verdict' => (string)($t['verdict'] ?? ''),
        'note'    => (string)($t['note'] ?? ''),
        'tags'    => array_values((array)($t['tags'] ?? [])),
    ], (array)$tasks);
}

function compare_entry(array $old, array $new): array
{
    $diffs = [];
    foreach (COMPARE_KEYS as $k) {
        $a = norm_field($k, $old[$k] ?? null);
        $b = norm_field($k, $new[$k] ?? null);
        if ($k === 'tasks') {
            $a = norm_tasks($a);
            $b = norm_tasks($b);
        }
        if ($a !== $b) {
            $diffs[] = $k;
        }
    }
    return $diffs;
}
```

**Güvenlik kuralları — araç bunları ihlal edemez:**
- `--out` verilmemişse **hiçbir dosya yazılmaz** (dry-run).
- Hiçbir mod canlı `data/jobs/`, `data/i18n/` veya `data/pending-tr-titles.json`
  üzerine yazmaz. Kaynak dosyalar yalnızca **okuma** modunda açılır.
- `--out` hedefi `migrate_target_ok()` doğrulamasından geçmeden hiçbir şey yazılmaz.
- Hedef repo kökünün **içinde olamaz** — untracked bir dizinin yanlışlıkla
  stage edilmesi riski böyle ortadan kalkar.

- [ ] **Adım 1: Aracı yaz**

Yazılacak fonksiyonlar, imzalarıyla:

```php
task_id_from_name(string $name, array $taken): string      // K2, kodu yukarida
compare_entry(array $old, array $new): array               // K5, kodu yukarida
migrate_common(string $id, array $flat): array             // common.json icerigi
migrate_en(string $id, array $flat, array $common): array  // en.json icerigi
migrate_report(array $rows): void                          // entry basina rapor satiri
```

`migrate_common()` once `data/i18n/<id>/common.json`'a bakar; varsa aynen dondurur ve
gorev sayisi esitligini kontrol eder (esit degilse `RuntimeException`). Yoksa
`task_id_from_name()` ile uretir.

`migrate_en()` yukaridaki alan bolusum tablosunu birebir uygular; `tasks` nesnesini
`common['taskOrder']` sirasinda kurar ve `titleTr`'yi **almaz** (K4).

- [ ] **Adım 2: Dry-run**

Run: `php tools/migrate-jobs.php`
Beklenen: 17 satırlık rapor, `cashier` ve `administrative-assistant` için
`ID kaynagi: i18n`, diğer 15'i için `uretildi`; hiçbir dosya yazılmamış
(`git status --short` boş).

- [ ] **Adım 3: `--out` reddetme kurallarını sına**

```bash
php tools/migrate-jobs.php --out=data/jobs;   echo "cikis: $?"   # canli JOBS_DIR
php tools/migrate-jobs.php --out=.;           echo "cikis: $?"   # repo koku
php tools/migrate-jobs.php --out=/;           echo "cikis: $?"   # kok
php tools/migrate-jobs.php --out="$HOME";     echo "cikis: $?"   # home
```
Beklenen: dördü de gerekçeli `HATA:` satırı ve çıkış 1; `git status --short` **boş**.

- [ ] **Adım 4: Commit**

```bash
git add tools/migrate-jobs.php
git commit -m "tools: add migrate-jobs with dry-run and verify"
```

**Risk**
Düşük — araç varsayılan olarak hiçbir şey yazmıyor. Asıl risk `taskOrder` konum
eşleşmesinin yanlış olması; Adım 2'nin raporu id ↔ İngilizce görev adı çiftlerini
**tek tek** basar ve göz kontrolüne sunar.

**Doğrulama komutu**
```bash
php tools/migrate-jobs.php && git status --short
```

**Beklenen sonuç**
Rapor basılır, `git status` **boş** — dry-run gerçekten yazmıyor.

**Rollback sınırı**
`git revert` tek commit; araç zaten hiçbir şeye dokunmuyor.

**Commit sınırı**
Tek dosya.

---

### Görev 2C — Migration kapısı (COMMIT YOK)

**Amaç**
Veriyi gerçekten üretmek, doğrulamak ve **göze sunmak**. Bu görev commit üretmez;
çıktısı bir karardır.

- [ ] **Adım 1: Repo dışında güvenli hedefe üret**

```bash
export MIGRATION_OUT="$(mktemp -d /tmp/willaistealit-migration.XXXXXX)"
echo "$MIGRATION_OUT"          # bu yolu bu gorev boyunca sakla
php tools/migrate-jobs.php --out="$MIGRATION_OUT"
git status --short             # BOS olmali — canli agac dokunulmadi
```

Hedef **repo kökünün dışındadır**: `data/jobs2` gibi repo içi bir dizin untracked
kalır ve bir `git add` kazasıyla commit'e girebilir.

- [ ] **Adım 2: Semantik eşitliği doğrula**

```bash
php tools/migrate-jobs.php --verify --out="$MIGRATION_OUT"; echo "cikis: $?"
```
Beklenen: **17/17 entry farksız**, çıkış 0. Tek satır fark çıkarsa migration
düzeltilene kadar Görev 2D'ye geçilmez.

- [ ] **Adım 3: Görev ID eşleşmesini göze sun**

```bash
php tools/migrate-jobs.php --verify --out="$MIGRATION_OUT" --report-ids
```
İki fixture entry için id ↔ İngilizce görev adı çiftleri okunur ve `data/i18n/`'deki
elle yazılmış id'lerin doğru görevlere düştüğü **elle** teyit edilir.

- [ ] **Adım 4: Üretilen ağacın şeklini kontrol et**

```bash
find "$MIGRATION_OUT" -type f | sort | head -20
cat "$MIGRATION_OUT/jobs/cashier/common.json"
python3 -m json.tool "$MIGRATION_OUT/jobs/cashier/en.json" > /dev/null && echo "en.json gecerli JSON"
ls "$MIGRATION_OUT/jobs/cashier/"      # common.json en.json tr.json es.json
ls "$MIGRATION_OUT/jobs/accountant/"   # common.json en.json  (TR/ES yok — dogru)
head "$MIGRATION_OUT/pending-tr-titles.json"
python3 -c "import json;d=json.load(open('$MIGRATION_OUT/migration-report.json'));print(len(d['ids']),'entry')"
git status --short                     # hala BOS
```

**Kapı koşulu:** `--verify` 17/17 farksız **ve** ID eşleşmesi göz kontrolünden geçmiş
olmalı. İkisi de sağlanmadan Görev 2D'ye geçilmez.

**Rollback sınırı**
`rm -rf "$MIGRATION_OUT"`. Canlı ağaç bu görevde hiç değişmedi; `git status` boş kaldı.

---

### Görev 2D — Atomik geçiş

**Amaç**
Yeni yapıyı yerine koymak, yükleyiciyi devretmek, eski okuma yolunu kaldırmak ve düz
dosya yolunu elle kuran **her** yeri güncellemek — **tek commit'te**.

**Neden tek commit:** iki formatın aynı anda okunduğu bir ara durum, "hangi dosya
doğru" sorusunu belirsiz bırakır. Spec §10 bunu açıkça yasaklıyor.

**Değiştirilecek dosyalar** (envanterden çıkarıldı, tahmin değil)

| Dosya | Satır | Ne değişecek |
|---|---|---|
| `inc/functions.php` | 23 | `load_job()` → `load_entry()`'ye devreder |
| `inc/functions.php` | 44 | `load_all_jobs()` → dizinleri tarar |
| `inc/functions.php` | 150–156 | `serve_page_cache()` bağımlılıkları → `entry_dependency_files()` |
| `inc/functions.php` | `write_page_cache()` | **Dil klasörüne yazar** — yoksa cache hiç isabet etmez |
| `inc/functions.php` | `clear_cache()` | İç içe dil klasörlerini de temizler |
| `job.php` | 318 | `write_page_cache($slug, $html, $lang)` |
| `sitemap.php` | 21 | `lastmod` dosya yolu → dizin |
| `og.php` | 26 | `$sourceFile` → `entry_dependency_files()` maksimumu |
| `job.php` | 286 | GitHub düzenleme linki → `data/jobs/<id>/en.json` |
| `tools/validate.php` | 27, 143 | Dizin tarama + dil başına kurallar |
| `tools/sync-evidence.php` | 39+ | `evidenceStrength`'i `<id>/en.json`'a yazar; **`--dry-run` ve `--root=` eklenir** |
| `tools/build-index.php` | — | `titleTr` alanı düşer, `aka` gelir |
| `inc/routes_cache.php` | — | `published` → `entry_langs()` |
| `index.php` | 206 | Boş durum metni: `data/jobs/<slug>/` |
| `data/jobs/*.json` | — | **17 dosya silinir** (git rm) |
| `data/jobs/<id>/` | — | 17 dizin eklenir |

**`published` hesabı (önemli):** `build_routes()` artık `entry_langs()` kullanır, yani
`cashier` ve `administrative-assistant` için `['en','tr','es']` döner. **`activeLangs`
`['en']` kaldığı için** `/tr/kasiyer` yine 404 verir ve hreflang'de TR satırı çıkmaz
(`alternates_for()` `activeLangs`'e göre filtreliyor). Faz 4 yalnızca `activeLangs`'i
açacak. Smoke matrisine bunu sabitleyen iki satır eklenir.

- [ ] **Adım 1: Geçiş öncesi tam kapı**

```bash
php tests/run.php && php tools/validate.php && ./tools/smoke.sh
php tools/migrate-jobs.php --verify --out="$MIGRATION_OUT"
```
Dördü de temiz olmadan devam edilmez.

- [ ] **Adım 2: Üretilen ağacı yerine koy, eskisini SİL­ME**

```bash
cp -R "$MIGRATION_OUT/jobs/." data/jobs/                       # dizinler eklenir
cp "$MIGRATION_OUT/pending-tr-titles.json" data/pending-tr-titles.json
ls data/jobs/ | head                   # hem *.json hem <id>/ gorunur
```
Düz dosyalar **yerinde kalır**. Bu ara durum tek başına commit edilmez; sonraki
adımlarla aynı commit'e girer.

- [ ] **Adım 3: `inc/functions.php`'yi devret**

```php
require_once __DIR__ . '/entry.php';

/** Tek bir entry'yi yukle. Bulunamazsa / yayinlanmamissa null. */
function load_job(string $slug, string $lang = DEFAULT_LANG): ?array
{
    return load_entry($slug, $lang);
}

/** Tum entry'leri yukle (build ve listeleme icin). */
function load_all_jobs(string $lang = DEFAULT_LANG): array
{
    $jobs = [];
    foreach (glob(JOBS_DIR . '/*/common.json') ?: [] as $path) {
        $id  = basename(dirname($path));
        $job = load_entry($id, $lang);
        if ($job !== null) {
            $jobs[$id] = $job;
        }
    }
    ksort($jobs);
    return $jobs;
}
```

`serve_page_cache()` bağımlılıkları (spec §8.1):

```php
function serve_page_cache(string $slug, string $lang = DEFAULT_LANG): bool
{
    $cached = PAGES_DIR . '/' . $lang . '/' . $slug . '.html';
    $deps   = entry_dependency_files($slug, $lang);
    if (!is_file($cached) || $deps === []) {
        return false;
    }
    // Klasor mtime'ina GUVENILMEZ (spec 8): dosya icerigi degistiginde dizin
    // zaman damgasi degismez. Kesin maksimum dosya mtime'i hesaplanir.
    $newest = template_mtime();
    foreach ($deps as $f) {
        $newest = max($newest, filemtime($f));
    }
    // related-jobs blogu tum evrene bagli — content-version Faz 2'de henuz yok,
    // guvenli fallback olarak entry dizinlerinin en yenisi kullanilir.
    foreach (glob(JOBS_DIR . '/*/*.json') ?: [] as $f) {
        $newest = max($newest, filemtime($f));
    }
    if (filemtime($cached) <= $newest) {
        return false;
    }
    readfile($cached);
    return true;
}
```

> **Not:** `content-version.json` (spec §8.2) Faz 2'de **yazılmaz**. Buradaki `glob`
> tabanlı hesap onun güvenli fallback'idir ve spec §8.2 bunu açıkça öngörüyor.
> Sürüm dosyası Faz 3'te, locale bağımlılıkları da devreye girdiğinde eklenir.

**Yazma tarafı da taşınmalı — yoksa cache hiç isabet etmez.** Bugün
`write_page_cache()` `PAGES_DIR/<slug>.html`'e yazıyor; okuma `<lang>/` altına
bakacaksa yazma da oraya gitmeli:

```php
function write_page_cache(string $slug, string $html, string $lang = DEFAULT_LANG): void
{
    $dir = PAGES_DIR . '/' . $lang;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return;
    }
    // Atomik: yarim yazilmis HTML'i baska bir istek okumasin.
    $tmp = $dir . '/' . $slug . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $html, LOCK_EX) === false) {
        return;
    }
    if (!@rename($tmp, $dir . '/' . $slug . '.html')) {
        @unlink($tmp);
    }
}

/** Cache klasorlerini bosalt. Dil klasorleri IC ICE — duz glob yetmez. */
function clear_cache(): int
{
    $n = 0;
    $patterns = [PAGES_DIR . '/*.html', PAGES_DIR . '/*/*.html',
                 PAGES_DIR . '/*.tmp',  PAGES_DIR . '/*/*.tmp',
                 OG_DIR . '/*.png',     OG_DIR . '/*/*.png'];
    foreach ($patterns as $pattern) {
        foreach (glob($pattern) ?: [] as $f) {
            if (@unlink($f)) {
                $n++;
            }
        }
    }
    return $n;
}
```

`job.php:318` çağrısı `write_page_cache($slug, $html, $lang)` olur. Eski düz
`cache/pages/*.html` kalıntıları geçiş sırasında `clear_cache()` ile temizlenir —
yukarıdaki ilk iki desen tam bunun için var.

- [ ] **Adım 4: `validate.php`'yi yeni yapıya taşı**

Mevcut kuralların hepsi korunur, üstüne (spec §7):

- Her `<id>/common.json`: `id` dizin adına eşit, `category` geçerli, `taskOrder` tekil
  ve boş değil
- `en.json` **kaynaktır**: yargı alanlarını taşımak zorunda (K1)
- Kaynak olmayan dosyalar yargı alanı **taşıyamaz** → hata
- Yayınlanan her dil `taskOrder`'daki her görev için `name` + `note` taşımalı
- `slug` rezerve kelime (`en`/`tr`/`es`) veya sabit sayfa slug'ı olamaz
- Bir dil içinde slug tekil; `formerSlugs` hiçbir canonical slug'ı gölgeleyemez
- `translationReviewed` kaynak olmayan dosyalarda zorunlu
- `translationReviewed < assessmentReviewed` → **uyarı** (çeviri bayat)
- `safe` verdict'te hiçbir görev `gone` olamaz (yayınlanmış rubrik eşiği)
- `safe` verdict'te `safeUntil` olamaz

- [ ] **Adım 5: Kalan çağrı yerlerini güncelle**

`sitemap.php:21`, `og.php:26`, `job.php:286`, `job.php:318`,
`tools/build-index.php`, `inc/routes_cache.php`, `index.php:206` — envanter tablosuna göre.

**`tools/sync-evidence.php`** ayrıca iki bayrak kazanır:

```php
$dryRun = in_array('--dry-run', $argv, true);
$root   = JOBS_DIR;
foreach ($argv as $a) {
    if (str_starts_with($a, '--root=')) {
        $root = substr($a, 7);
    }
}
// dosya listesi: glob($root . '/*/en.json')
// yazma noktasi:
if ($dryRun) {
    echo "  (dry-run) $id: evidenceStrength -> $strength\n";
} else {
    atomic_write($file, $json);
}
```

Gerekçe: bu araç **içerik yazar**. 17/17 eşitlik kapısından sonra canlı `en.json`
dosyalarını değiştirirse migration commit'ine sessizce içerik değişikliği karışır.
Kapıda yalnızca `--dry-run --root=<gecici agac>` biçiminde koşar; canlı çalıştırma
migration commit'inden **sonra**, ayrı bir işin konusudur.

- [ ] **Adım 6: Düz dosyaları kaldır**

```bash
git rm data/jobs/*.json
```
Araç silmedi; **commit** siliyor. Böylece geri alma `git revert` ile tek adım.

- [ ] **Adım 7: Genişletilmiş kapı — dokuzu da geçmeden commit yok**

```bash
php tests/run.php && php tools/validate.php && php tools/build-index.php && ./tools/smoke.sh
```

Üstüne, veri eşitliğinin ötesinde şu dokuz kapı:

```bash
# 1. 17 EN entry yeni yukleyiciyle yukleniyor
php -r 'require "inc/entry.php"; echo count(load_all_jobs()), " EN entry\n";'   # 17

# 2. Iki fixture uc dil dondurıyor
php -r 'require "inc/entry.php";
foreach (["cashier","administrative-assistant"] as $i)
  printf("%-26s %s\n", $i, implode(",", entry_langs($i)));'                     # en,tr,es

# 3. Gorev sayilari migration oncesiyle ayni (rapor dosyasindan)
php -r 'require "inc/entry.php";
$r = json_decode(file_get_contents(getenv("MIGRATION_OUT")."/migration-report.json"), true);
$bad = 0;
foreach ($r["ids"] as $id => $n) {
  $c = count(load_entry($id, "en")["tasks"] ?? []);
  if ($c !== $n) { printf("FARK %s: %d != %d\n", $id, $c, $n); $bad++; }
}
echo $bad === 0 ? "gorev sayilari ayni\n" : "$bad entry farkli\n";'

# 4. Duz dosya kalmadi
ls data/jobs/*.json 2>/dev/null && echo "HATA: duz dosya kaldi" || echo "duz dosya yok"

# 5. build-index 17 entry uretiyor
php tools/build-index.php | grep "entry indexlendi"                              # 17

# 6. Page cache YENI yolda yazilip okunuyor (ilk istek yazar, ikincisi hit)
rm -rf cache/pages/*
php -S 127.0.0.1:8000 router.php > /dev/null 2>&1 & SRV=$!
for i in $(seq 1 40); do curl -sf -o /dev/null 127.0.0.1:8000/ && break; sleep 0.25; done
curl -s -o /dev/null 127.0.0.1:8000/cashier
ls cache/pages/en/cashier.html && echo "cache YENI yolda yazildi"
ls cache/pages/*.html 2>/dev/null && echo "HATA: duz yola da yazmis"
curl -s 127.0.0.1:8000/cashier | grep -c "Cashier"     # ikinci istek: hit, ayni cikti
kill $SRV

# 7. TR hala servis edilmiyor (activeLangs=['en'])
./tools/smoke.sh | grep -E "^/(tr/|og/tr/)"            # hepsi 404 satiri

# 8. sync-evidence yalnizca en.json'a dokunuyor — CANLI AGACA YAZMADAN sinanir
php tools/sync-evidence.php --dry-run --root="$MIGRATION_OUT/jobs"
git status --short    # BOS: kapi hicbir icerik degisikligi uretmedi

# 9. Migration ciktisi ve raporlar stage listesine GIRMIYOR
git status --short | grep -E "migration|jobs2|\.tmp" && echo "HATA: sizinti" || echo "sizinti yok"
```

- [ ] **Adım 8: Çıktı eşitliğini gözle doğrula (K5 + K4)**

```bash
php -S 127.0.0.1:8000 router.php > /dev/null 2>&1 & SRV=$!
sleep 1
curl -s 127.0.0.1:8000/cashier > /tmp/after.html
kill $SRV

# K4'un kasitli farki: Turkce baslik artik basilmiyor
grep -c "Kasiyer" /tmp/after.html                 # 0 beklenir
# Icerik yerinde mi: verdict, gorev sayisi, kaynaklar
grep -c "SHRINKING\|ON THE MENU\|SAFE" /tmp/after.html   # >=1
grep -c 'class="task ' /tmp/after.html                     # 6 beklenir
grep -c "bls.gov" /tmp/after.html                          # >=1 (sources)
```
Beklenen: sayfa açılıyor, verdict ve görevler yerinde, Türkçe başlık **yok** (K4'ün
kasıtlı farkı).

- [ ] **Adım 9: Açık yollarla stage et, listeyi doğrula, commit et**

`git add -A` **kullanılmaz** — aynı repoda başka oturumlar çalışıyor ve ilgisiz
dosyaları commit'e çeker.

```bash
git add data/jobs data/pending-tr-titles.json \
        inc/functions.php inc/entry.php inc/routes_cache.php \
        job.php index.php og.php sitemap.php \
        tools/validate.php tools/sync-evidence.php tools/build-index.php \
        tests/entry.test.php tests/routes_cache.test.php tests/security.test.php

# Stage listesi beklenenle birebir mi — commit'ten ONCE gozle karsilastir
git diff --cached --name-status
```

Beklenen listede **yalnızca** şunlar olmalı: `D data/jobs/*.json` (17 silme),
`A data/jobs/*/{common,en,tr,es}.json`, `A data/pending-tr-titles.json` ve yukarıdaki
PHP dosyaları. Başka bir yol görünüyorsa **commit edilmez**, önce sebebi bulunur.

```bash
git commit -m "refactor: switch to per-language entry directories"
git rev-parse HEAD   # ---> KAYDET
```

Hash buraya yazılır: `MIGRATION_COMMIT = ________________`

**Risk**
**Faz 2'nin en yüksek riskli adımı.** 17 entry'nin tamamı ve 11 çağrı yeri aynı anda
değişiyor. Azaltıcılar: (a) Görev 2C'nin `--verify` kapısı 17/17 farksız olmadan bu
göreve geçilmiyor, (b) düz dosyalar git'te duruyor, revert tek adım, (c) `smoke.sh`
hemen ardından koşuyor.

**Doğrulama komutu**
```bash
php tests/run.php && php tools/validate.php && php tools/build-index.php && ./tools/smoke.sh
```

**Beklenen sonuç**
`0 kaldi` · `Hata yok` · `17 entry indexlendi` · `Matris temiz.`

**Rollback sınırı**
```bash
git revert <MIGRATION_COMMIT>
```
Düz dosyalar takip edildiği için revert onları geri getirir.

**Yarıda kesilen migration için rollback gerekmez** — araç canlı ağaca hiç yazmadı.
Adım 2'deki `cp` sonrası kesilme durumunda temizlik **geniş glob ile değil**,
`migration-report.json`'daki **kesin id listesiyle** yapılır:

```bash
php -r '$r = json_decode(file_get_contents(getenv("MIGRATION_OUT")."/migration-report.json"), true);
foreach (array_keys($r["ids"]) as $id) { echo "data/jobs/$id\n"; }' | xargs rm -rf
rm -f data/pending-tr-titles.json
git status --short    # yalnizca duz dosyalar kalmali, calisma agaci temiz
```

`rm -rf data/jobs/*/` **kullanılmaz**: glob, migration'ın üretmediği bir dizini de
silebilir.

**Commit sınırı**
Tek commit: veri + yükleyici + çağrı yerleri + validator. Bölünmesi yasak —
bölünürse yarı göçmüş bir ağaç ortaya çıkar.

---

### Görev 2E — `data/i18n/` kaldırma ve kararların korunması

**Amaç**
Geçici klasörü kaldırmak — ama içindeki **editoryal kararları kaybetmeden**.

**Ön koşul:** Görev 2D commit'lenmiş, dört doğrulama temiz.

`data/i18n/README.md` üç gerçek karar içeriyor ve bunlar klasörle birlikte silinemez:

1. TR/ES v1'de **global assessment** olarak yayınlanır.
2. **`es` bir pazar değildir** — İspanya, Meksika, Arjantin, Kolombiya tek iş piyasası
   sayılamaz; ülke seçmeden yerel verdict üretilmez.
3. **`cashier` / TR yerel override araştırması bekliyor** — Türkiye'de kasiyer
   istihdamının yüksekliği tek başına yetmez, §7.1 gereği yetkili yerel kaynak şart.

> **Sıra kritiktir: kararlar güvenceye alınmadan README silinmez.** Hafıza commit'i
> `data/i18n/` kaldırma commit'inden **önce** gelir; aksi halde onay beklerken klasör
> silinmiş olur ve kararlar yalnızca git geçmişinde kalır.

- [ ] **Adım 1: Kararları hafızaya taşımayı öner (diff olarak)**

`docs/memory/decisions/2026-08-15-ceviri-kapsami-global-assessment.md` **önerilir** ve
diff olarak sunulur. `docs/memory/` append-only ve otomatik yazılmaz — CLAUDE.md kuralı:
*"Hafızaya yazarken: satırı öner, kullanıcı onaylasın."*

- [ ] **Adım 2: Kullanıcı onayını bekle** — onay gelmeden Adım 3'e geçilmez.

- [ ] **Adım 3: Hafıza commit'i**

```bash
git add docs/memory/decisions/2026-08-15-ceviri-kapsami-global-assessment.md docs/memory/README.md
git diff --cached --name-status     # yalnizca bu iki dosya
git commit -m "memory: record the translation scope decision"
```

- [ ] **Adım 4: Kopyaların birebir taşındığını doğrula**

```bash
for f in cashier administrative-assistant; do
  for l in common tr es; do
    diff -q "data/i18n/$f/$l.json" "data/jobs/$f/$l.json" || echo "FARK: $f/$l"
  done
done
```
Beklenen: hiçbir `FARK:` satırı yok. **Tek bir fark bile varsa klasör silinmez.**

- [ ] **Adım 5: Yükleyicinin gerçekten üç dili gördüğünü doğrula**

```bash
php -r 'require "inc/entry.php";
foreach (["cashier","administrative-assistant"] as $id)
  printf("%-26s %s\n", $id, implode(",", entry_langs($id)));'
```
Beklenen: her ikisi için `en,tr,es`.

- [ ] **Adım 6: `pending-tr-titles.json`'ın 17 başlığı taşıdığını doğrula**

```bash
php -r 'echo count(json_decode(file_get_contents("data/pending-tr-titles.json"), true)), " baslik\n";'
```
Beklenen: `17 baslik`. Dosya `data/` altında, yani `data/i18n/` kaldırılırken
etkilenmez — TR entry'leri yazılana kadar tohum olarak durur.

- [ ] **Adım 7: Klasörü kaldır ve commit et**

```bash
git rm -r data/i18n
git diff --cached --name-status     # yalnizca data/i18n/ silmeleri
git commit -m "chore: remove the i18n staging folder after migration"
```

`git add -A` **kullanılmaz**; `git rm` zaten yalnızca hedeflenen yolları stage eder.

**Kaldırma koşulu — üçü birden, bu sırayla:**
1. README'deki üç karar hafızaya taşınmış ve **onaylanmış** (Adım 1–3)
2. `data/jobs/<id>/{common,tr,es}.json` dosyaları `data/i18n/` kopyalarıyla **birebir aynı**
3. `entry_langs()` her iki fixture için `en,tr,es` döndürüyor

**Risk**
Orta — silinen bir klasör geri gelmez. Ama üç koşul da makine ile doğrulanıyor ve
dosyalar git geçmişinde duruyor (`git show <commit>:data/i18n/...`).

**Doğrulama komutu**
```bash
php tests/run.php && php tools/validate.php && ./tools/smoke.sh && git status --short
```

**Beklenen sonuç**
`0 kaldi` · `Hata yok` · `Matris temiz.` · boş `git status`.

**Rollback sınırı**
`git revert` tek commit; dosyalar geçmişten geri gelir.

**Commit sınırı**
Yalnızca `data/i18n/` kaldırma. Hafıza dosyası **ayrı** commit'te ve ayrı onayla.

---

## Commit haritası

**5 commit + 1 kapı (commit üretmez).** Spec düzeltmesi (`docs: clarify assessment
source ownership`) Faz 2 başlamadan ayrı commit olarak zaten landi.

| # | Commit | Görev | Site etkisi |
|---|---|---|---|
| 1 | `feat: add multilingual entry loader, not yet wired` | 2A | — |
| 2 | `tools: add migrate-jobs with dry-run and verify` | 2B | — |
| — | *(migration kapısı — `--verify` 17/17 + ID göz kontrolü)* | 2C | — |
| 3 | **`refactor: switch to per-language entry directories`** | 2D | **Tüm entry'ler** |
| 4 | `memory: record the translation scope decision` (onaya bağlı) | 2E | — |
| 5 | `chore: remove the i18n staging folder after migration` | 2E | — |

**Sıra bağlayıcı:** hafıza commit'i (4) `data/i18n/` kaldırmadan (5) **önce** gelir —
kararlar güvenceye alınmadan README silinmez.

Yalnızca **3 numaralı** commit davranış değiştirir. Rollback `git revert <MIGRATION_COMMIT>`.

Hiçbir commit'te `git add -A` kullanılmaz; her commit öncesi
`git diff --cached --name-status` çıktısı beklenen dosya listesiyle karşılaştırılır.

## Faz 2 kapanış kontrolü

```bash
php tests/run.php                 # 0 kaldi
php tools/validate.php            # Hata yok (17 entry, dizin yapisinda)
php tools/build-index.php         # route tablosu + arama indeksi
php tools/doctor.php              # Kritik hata yok
./tools/smoke.sh                  # Matris temiz
git status --short                # bos
ls data/jobs/*.json 2>/dev/null   # HICBIR duz dosya kalmamali
php -r 'require "inc/entry.php"; echo implode(",", entry_langs("cashier")), "\n";'  # en,tr,es
```

## Uyumluluk kontrol listesi

| Sistem | Faz 2'de durumu |
|---|---|
| **Changelog** | `data/changelog.json` slug tabanlı; slug'lar değişmedi → dokunulmaz. Validator'ın "entry artık yok" kontrolü dizin yapısına uyarlanır |
| **Arama indeksi** | `build-index.php` `load_all_jobs()` üzerinden çalışır → otomatik uyumlu. `titleTr` alanı düşer, `aka` gelir. Dil başına index **Faz 4** |
| **Related jobs** | `related_jobs()` `load_all_jobs()` kullanıyor → dokunulmaz |
| **Sitemap** | `lastmod` dosya yolu dizine çevrilir. Çok dilli `xhtml:link` **Faz 4** |
| **OG** | `og.php` kaynak mtime'ı `entry_dependency_files()` ile hesaplanır. Dil klasörlü OG **Faz 4** |
| **Sayfa cache** | Anahtar `cache/pages/<lang>/<slug>.html`, bağımlılıklar spec §8.1. `content-version.json` **Faz 3** |
| **Routing** | `build_routes()` `published`'ı `entry_langs()`'ten alır; `activeLangs` `['en']` kaldığı için TR/ES **servis edilmez** |
| **hreflang** | `alternates_for()` `activeLangs`'e göre filtreliyor → TR satırı basılmaz |
| **`sync-evidence.php`** | `evidenceStrength`'i `<id>/en.json`'a yazar; alan sırası korunur |

## Riskler

1. **`taskOrder` konum eşleşmesi.** İki fixture'ın elle yazılmış id'leri düz dosyadaki
   görev sırasına göre eşleşiyor. Sayı eşitliği makine ile, anlam eşleşmesi **gözle**
   doğrulanır (Görev 2C Adım 3). Yanlış eşleşme sessiz bir içerik karışmasıdır.
2. **Tek büyük commit.** 17 entry + 11 çağrı yeri. `--verify` kapısı bunu 17/17
   farksızlığa bağlıyor; yine de Faz 2'nin en riskli anı burasıdır.
3. **`titleTr` kaybı.** Kasıtlı (K4), ama 17 başlık `pending-tr-titles.json`'a
   yazılmadan commit edilirse gerçekten kaybolur. Adım kontrolü var.
4. **`data/i18n/` silme.** Geri dönüşsüz görünür; üç koşul makine ile doğrulanıyor ve
   git geçmişi dosyaları tutuyor.
5. **`note` devralınmıyor.** Yerel dosya her görev için kendi notunu taşımak zorunda.
   İki fixture taşıyor; ileride eksik not yazan bir katkı sessiz boş nota değil,
   validator hatasına düşer.

## Faz 2'nin DEĞİŞTİRMEDİKLERİ

- **`activeLangs` `['en']` kalır.** TR/ES diske iner, hiçbir URL'e servis edilmez.
- `inc/config.php`'deki `VERDICTS`/`CATEGORIES` İngilizce etiketleriyle durur — **Faz 3**.
- `geo_answer()`, `faq_pairs()`, `pretty_month()` İngilizce cümle üretmeye devam eder — **Faz 3**.
- `job.php`, `index.php`, `inc/header.php` şablonları **yerelleştirilmez** — dile göre
  metin üretmezler, İngilizce çıktı verirler; yerelleştirme **Faz 3**'ün işi.
  (Teknik uyarlama görürler: `job.php` cache çağrısına dili ekler ve GitHub düzenleme
  linkini dizine çevirir, `index.php` boş durum metnini günceller — içerik üretimleri
  değişmez.)
- `sitemap.php`'nin `xhtml:link` alternatifleri, dil seçici, dile özgü OG — **Faz 4**.
- Arama harf katlaması (`search-fold.json`) — **Faz 4**.

**Faz 2'de UYGULANAN ama henüz veride kullanılmayan yetenekler** (sessizce
destekleniyormuş gibi bırakılmıyor, açıkça yazılıyor):

- `inheritedSources: true` — yerel kaynakların global kaynakların üstüne eklenmesi.
  Yükleyici destekliyor ve testi var; **hiçbir entry henüz kullanmıyor**.
- Dil dosyasında `taskOrder` ezme ve `localTasks` — yükleyici destekliyor ve testi var;
  **hiçbir entry henüz kullanmıyor**. İlk kullanıcısı `cashier`/TR yerel araştırması
  tamamlandığında olacak.
- Validator bu iki yeteneği Faz 2'de **doğrular** (yanlış kullanım hata verir), ama
  hiçbir entry'yi bunları kullanmaya zorlamaz.

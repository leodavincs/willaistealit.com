<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/entry.php';
require_once __DIR__ . '/locale.php';
require_once __DIR__ . '/urls.php';         // url_for
require_once __DIR__ . '/routes_cache.php'; // load_routes
require_once __DIR__ . '/search.php';       // search_fold

/** Slug'i dogrula: sadece [a-z0-9-], path traversal'a kapali. */
function valid_slug(?string $slug): bool
{
    return is_string($slug) && $slug !== '' && strlen($slug) <= 64 && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1;
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

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

/** Arama index'i: yoksa bos dizi (build-index calistirilmamis). */
function load_index(): array
{
    if (!is_file(INDEX_FILE)) {
        return [];
    }
    $data = json_decode((string)file_get_contents(INDEX_FILE), true);
    return is_array($data) ? $data : [];
}

/** GitHub linki tanimli mi — degilse ilgili bloklar hic basilmaz. */
function has_github(): bool
{
    return GITHUB_URL !== '';
}

function github_url(string $path = ''): string
{
    return rtrim(GITHUB_URL, '/') . $path;
}

function has_contact(): bool
{
    return CONTACT_EMAIL !== '';
}

/**
 * tools/ script'lerinin web'den calisma izni.
 * Anahtar degistirilmemisse hicbir key kabul edilmez — deploy'da unutulan
 * varsayilan anahtar acik kapi birakmasin.
 */
/**
 * Analytics yalnizca canli host'ta basilir. Lokalde `php -S` ile calisirken
 * HTTP_HOST 'localhost:8000' olur ve Matomo'ya kendi gelistirme trafigimizi
 * yollamayiz — aksi halde ilk gunun verisi bizim sayfa yenilemelerimiz olurdu.
 */
function is_live_host(): bool
{
    return ($_SERVER['HTTP_HOST'] ?? '') === parse_url(SITE_URL, PHP_URL_HOST);
}

function build_key_ok(?string $given): bool
{
    if (BUILD_KEY === '' || BUILD_KEY === 'change-me-before-deploy') {
        return false;
    }
    return is_string($given) && hash_equals(BUILD_KEY, $given);
}

/**
 * Dilden bagimsiz (config) + dile bagli (locale) BIRLESIK doner.
 * Sablonlar $v['label'] yazmaya devam eder — cikti degismez.
 */
function verdict_meta(?string $verdict, string $lang = DEFAULT_LANG): array
{
    $key = isset(VERDICTS[$verdict]) ? (string)$verdict : 'shrinking';
    $L   = lang_for($lang);
    return VERDICTS[$key] + [
        'key'   => $key,
        'label' => $L->verdictLabel($key),
        'blurb' => $L->verdictBlurb($key),
    ];
}

function task_verdict_meta(?string $v, string $lang = DEFAULT_LANG): array
{
    $key = isset(TASK_VERDICTS[$v]) ? (string)$v : 'going';
    return TASK_VERDICTS[$key] + [
        'key'   => $key,
        'label' => lang_for($lang)->taskVerdictLabel($key),
    ];
}

function category_label(?string $key, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->categoryLabel((string)$key);
}

function tag_definition(string $tag, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->tagDefinition($tag);
}

/**
 * Entry'nin kanonik adresi. KIMLIK alir, yerel slug DEGIL: TR'de $job['slug']
 * 'kasiyer' olur ve prefix'siz koke yazilirsa /kasiyer gibi YANLIS bir adres cikar.
 * Tek dogru kaynak url_for().
 */
function job_url(string $id, string $lang = DEFAULT_LANG): string
{
    return url_for($lang, 'job', $id, load_routes());
}

function og_url(string $id, string $lang = DEFAULT_LANG): string
{
    return url_for($lang, 'og', $id, load_routes());
}

/** Entry'nin kimligi — yukleyici 'id' veriyor, eski cagrilar 'slug' gecebilir. */
function entry_id(array $job): string
{
    return (string)($job['id'] ?? $job['slug'] ?? '');
}

/** X paylasim metni. URL burada uretilir, Lang'a PARAMETRE olarak gecer (L1). */
function share_text(array $job, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->shareText($job, job_url(entry_id($job), $lang));
}

/**
 * Sayfa cache'i: bagimliliklardan yeniyse dogrudan bas.
 * Klasor mtime'ina GUVENILMEZ (spec 8): dizin zaman damgasi dosya icerigi
 * degistiginde degismez. Kesin maksimum dosya mtime'i hesaplanir.
 */
function serve_page_cache(string $slug, string $lang = DEFAULT_LANG): bool
{
    $cached = PAGES_DIR . '/' . $lang . '/' . $slug . '.html';
    $deps   = entry_dependency_files($slug, $lang);
    if (!is_file($cached) || $deps === []) {
        return false;
    }

    $newest = template_mtime();
    foreach ($deps as $f) {
        $newest = max($newest, filemtime($f));
    }
    // related-jobs blogu tum evrene bagli. content-version.json (spec 8.2) Faz 3'te
    // gelecek; o gelene kadar guvenli fallback: tum entry dosyalarinin en yenisi.
    foreach (glob(JOBS_DIR . '/*/*.json') ?: [] as $f) {
        $newest = max($newest, filemtime($f));
    }

    // <= bilerek: filemtime saniye hassasiyetinde. Ayni saniye icinde yazilan
    // cache ile degisen kaynagi ayirt edemiyoruz, o yuzden supheliyi at.
    if (filemtime($cached) <= $newest) {
        return false;
    }
    readfile($cached);
    return true;
}

/**
 * Sablon tarafinin en son degisme zamani: job.php + inc/*.php.
 * config.php de buna dahil — GITHUB_URL gibi ayarlar sayfa ciktisini degistiriyor,
 * bunu unutmak "degisiklik neden gorunmuyor" hatasina yol aciyor.
 */
/**
 * Sablon tarafinin dosya listesi — test okuyabilsin diye ayri.
 * inc/lang/ bir ALT DIZIN oldugu icin eski glob(inc/*.php) onu hic gormuyordu;
 * data/locale/ ise hic bakilmiyordu. Genisletilmezse bir locale metni
 * duzeltildiginde ESKI SAYFA servis edilmeye devam eder ve kimse fark etmez.
 */
function template_files(): array
{
    $files = [ROOT . '/job.php'];
    foreach ([ROOT . '/inc/*.php',
              ROOT . '/inc/lang/*.php',
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

function write_page_cache(string $slug, string $html, string $lang = DEFAULT_LANG): void
{
    // Dil klasorune yazilir — okuma da oradan (serve_page_cache). Ikisi ayrisirsa
    // site calisir ama HICBIR sayfa cache hit almaz.
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

/** Cache klasorlerini bosalt (build sirasinda cagrilir). */
function clear_cache(): int
{
    $n = 0;
    // Dil klasorleri IC ICE — duz glob yetmez. Eski duz kalintilar da temizlenir.
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

/** Asset URL'ine dosya zamanini ekler — deploy sonrasi tarayici cache'i bayatlamaz. */
function asset(string $path): string
{
    $file = ROOT . $path;
    $v    = is_file($file) ? (string)filemtime($file) : '1';
    return $path . '?v=' . $v;
}

function verdict_counts(array $jobs): array
{
    $counts = array_fill_keys(array_keys(VERDICTS), 0);
    foreach ($jobs as $job) {
        $v = $job['verdict'] ?? null;
        if (isset($counts[$v])) {
            $counts[$v]++;
        }
    }
    return $counts;
}

// ---------- SEO / GEO yardimcilari ----------

/** "2026-08" -> "August 2026". Bozuk girdide bos doner. */
function pretty_month(?string $ym, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->month((string)$ym);
}

/**
 * GEO paragrafi: baglamsiz alintilandiginda bile ayakta duran, tarihli tek paragraf.
 * Cumle kurulumu dile gore degistigi icin govde Lang sinifinda yasar.
 */
function geo_answer(array $job, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->geoAnswer($job);
}

function lower_first(string $text, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->lowerFirst($text);
}

function with_article(string $word, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->withArticle($word);
}

function list_phrase(array $items, string $lang = DEFAULT_LANG): string
{
    return lang_for($lang)->listPhrase($items);
}

/**
 * FAQPage markup'i icin soru-cevap ciftleri.
 * URL DISARIDAN gecer — Lang URL uretmez (L1).
 */
function faq_pairs(array $job, string $lang = DEFAULT_LANG): array
{
    return lang_for($lang)->faqPairs($job, job_url(entry_id($job), $lang));
}

/**
 * Ilgili meslekler: once ayni kategori, sonra ortak direnc tag'i.
 * Ic linkleme hem SEO hem de okuyucunun "peki ya benimki" refleksi icin.
 */
function related_jobs(array $job, int $limit = 4): array
{
    $all  = load_all_jobs();
    $self = (string)($job['slug'] ?? '');
    $tags = (array)($job['resistanceTags'] ?? []);
    $cat  = (string)($job['category'] ?? '');

    $scored = [];
    foreach ($all as $slug => $other) {
        if ($slug === $self) {
            continue;
        }
        $score = 0;
        if ($cat !== '' && ($other['category'] ?? '') === $cat) {
            $score += 3;
        }
        $shared = array_intersect($tags, (array)($other['resistanceTags'] ?? []));
        $score += count($shared) * 2;
        if (($other['verdict'] ?? '') === ($job['verdict'] ?? '')) {
            $score += 1;
        }
        if ($score > 0) {
            $scored[$slug] = $score;
        }
    }

    arsort($scored);
    $out = [];
    foreach (array_slice(array_keys($scored), 0, $limit) as $slug) {
        $out[$slug] = $all[$slug];
    }
    return $out;
}

/**
 * Kanit durumu uyarisi. /methodology'de "kaynagi olmayan entry community draft
 * olarak isaretlenir" sozu verildi — bu onu tutuyor.
 * @return array{level:string,label:string,text:string}|null
 */
function evidence_note(array $job, string $lang = DEFAULT_LANG): ?array
{
    return lang_for($lang)->evidenceNote($job);
}

/** Verdict degisiklikleri — /changelog ve tazelik sinyali icin. Yeniden eskiye. */
function load_changelog(): array
{
    $file = ROOT . '/data/changelog.json';
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    $entries = $data['entries'] ?? [];
    usort($entries, static fn ($a, $b) => strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? '')));
    return $entries;
}

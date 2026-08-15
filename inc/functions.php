<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Slug'i dogrula: sadece [a-z0-9-], path traversal'a kapali. */
function valid_slug(?string $slug): bool
{
    return is_string($slug) && $slug !== '' && strlen($slug) <= 64 && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1;
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Tek bir entry'yi yukle. Bulunamazsa / bozuksa null. */
function load_job(string $slug): ?array
{
    if (!valid_slug($slug)) {
        return null;
    }
    $path = JOBS_DIR . '/' . $slug . '.json';
    if (!is_file($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }
    // Dosya adi her zaman kazanir.
    $data['slug'] = $slug;
    return $data;
}

/** Tum entry'leri yukle (build ve listeleme icin). */
function load_all_jobs(): array
{
    $jobs = [];
    foreach (glob(JOBS_DIR . '/*.json') ?: [] as $path) {
        $slug = basename($path, '.json');
        $job  = load_job($slug);
        if ($job !== null) {
            $jobs[$slug] = $job;
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

function verdict_meta(?string $verdict): array
{
    return VERDICTS[$verdict] ?? VERDICTS['shrinking'];
}

function task_verdict_meta(?string $v): array
{
    return TASK_VERDICTS[$v] ?? TASK_VERDICTS['going'];
}

function category_label(?string $key): string
{
    return CATEGORIES[$key] ?? 'Uncategorised';
}

function tag_definition(string $tag): string
{
    return RESISTANCE_TAGS[$tag] ?? '';
}

function job_url(string $slug): string
{
    return SITE_URL . '/' . $slug;
}

function og_url(string $slug): string
{
    return SITE_URL . '/og/' . $slug . '.png';
}

/** X paylasim metni — paylasim kartinin yanindaki butona gider. */
function share_text(array $job): string
{
    $v = verdict_meta($job['verdict'] ?? '');
    $t = $job['title'] ?? $job['slug'];
    $line = $v['dot'] . ' ' . $v['label'];
    if (!empty($job['safeUntil'])) {
        $line .= ' — safe until ~' . $job['safeUntil'];
    }
    return sprintf("%s: %s\n\n%s\n\n%s", strtoupper($t), $line, (string)($job['oneLiner'] ?? ''), job_url($job['slug']));
}

/** Sayfa cache'i: dosya JSON'dan yeniyse dogrudan bas. */
function serve_page_cache(string $slug): bool
{
    $cached = PAGES_DIR . '/' . $slug . '.html';
    $source = JOBS_DIR . '/' . $slug . '.json';
    if (!is_file($cached) || !is_file($source)) {
        return false;
    }
    // Sablon, kendi verisi ya da entry kumesi degistiyse cache gecersiz.
    // (JOBS_DIR zamani: related-jobs blogu diger entry'lere bagli.)
    $newest = max(
        filemtime($source),
        filemtime(JOBS_DIR),
        filemtime(ROOT . '/job.php'),
        filemtime(ROOT . '/inc/header.php'),
        filemtime(__FILE__)
    );
    if (filemtime($cached) < $newest) {
        return false;
    }
    readfile($cached);
    return true;
}

function write_page_cache(string $slug, string $html): void
{
    if (!is_dir(PAGES_DIR)) {
        @mkdir(PAGES_DIR, 0775, true);
    }
    @file_put_contents(PAGES_DIR . '/' . $slug . '.html', $html, LOCK_EX);
}

/** Cache klasorlerini bosalt (build sirasinda cagrilir). */
function clear_cache(): int
{
    $n = 0;
    foreach ([PAGES_DIR . '/*.html', OG_DIR . '/*.png'] as $pattern) {
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
function pretty_month(?string $ym): string
{
    if (!is_string($ym) || preg_match('/^(\d{4})-(\d{2})$/', $ym, $m) !== 1) {
        return '';
    }
    $months = [1 => 'January', 'February', 'March', 'April', 'May', 'June',
               'July', 'August', 'September', 'October', 'November', 'December'];
    $i = (int)$m[2];
    return isset($months[$i]) ? $months[$i] . ' ' . $m[1] : '';
}

/**
 * GEO paragrafi: baglamsiz alintilandiginda bile ayakta duran, tarihli tek paragraf.
 * Cevap motorlari cevabi bu cumlelerden kuruyor — bu yuzden her sey icinde:
 * tarih, meslek, verdict, ne gitti, ne kaldi, kaynak.
 * Entry kendi metnini `geoAnswer` ile ezebilir.
 */
function geo_answer(array $job): string
{
    if (!empty($job['geoAnswer'])) {
        return (string)$job['geoAnswer'];
    }

    $title = (string)($job['title'] ?? $job['slug'] ?? 'this job');
    $lower = mb_strtolower($title);
    $date  = pretty_month((string)($job['lastReviewed'] ?? '')) ?: 'August 2026';
    $v     = (string)($job['verdict'] ?? 'shrinking');

    // Gorevleri durumuna gore ayir
    $gone = $safe = [];
    foreach (($job['tasks'] ?? []) as $task) {
        $name = lower_first((string)($task['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        if (($task['verdict'] ?? '') === 'gone') {
            $gone[] = $name;
        } elseif (($task['verdict'] ?? '') === 'safe') {
            $safe[] = $name;
        }
    }

    $verdictSentence = match ($v) {
        'safe'        => sprintf('%s is not being replaced by AI.', $title),
        'on-the-menu' => sprintf('the core tasks of %s work are becoming machine-doable%s.', $lower,
                                 !empty($job['safeUntil']) ? ', with the shift expected to land by around ' . (string)$job['safeUntil'] : ''),
        default       => sprintf('the %s role is shrinking rather than disappearing%s.', $lower,
                                 !empty($job['safeUntil']) ? ', with the core narrowing through roughly ' . (string)$job['safeUntil'] : ''),
    };

    $out = sprintf('As of %s, %s', $date, $verdictSentence);

    if ($gone) {
        $out .= sprintf(' AI has already absorbed %s.', list_phrase(array_slice($gone, 0, 3)));
    }
    if ($safe) {
        $out .= sprintf(' What resists is %s.', list_phrase(array_slice($safe, 0, 3)));
    }
    if (!empty($job['resistanceTags'])) {
        $out .= sprintf(' The structural reason is %s.', list_phrase(array_map(
            static fn ($t) => str_replace('-', ' ', (string)$t),
            array_slice((array)$job['resistanceTags'], 0, 3)
        )));
    }

    return $out;
}

/**
 * Cumle icine gomulecek basligi kucultur — ama ilk kelime kisaltmaysa dokunmaz
 * ("CV screening" -> "CV screening", "Data entry" -> "data entry").
 */
function lower_first(string $text): string
{
    $text  = trim($text);
    $first = (string)(preg_split('/\s+/u', $text)[0] ?? '');
    if ($first !== '' && mb_strtoupper($first) === $first && mb_strlen($first) > 1) {
        return $text;
    }
    return mb_strtolower(mb_substr($text, 0, 1)) . mb_substr($text, 1);
}

/** "accountant" -> "an accountant". Sesli harfle baslayanlar icin "an". */
function with_article(string $word): string
{
    $first = mb_strtolower(mb_substr(trim($word), 0, 1));
    return (in_array($first, ['a', 'e', 'i', 'o', 'u'], true) ? 'an ' : 'a ') . $word;
}

/** ["a","b","c"] -> "a, b and c" */
function list_phrase(array $items): string
{
    $items = array_values(array_filter($items));
    $n = count($items);
    if ($n === 0) {
        return '';
    }
    if ($n === 1) {
        return $items[0];
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' and ' . $last;
}

/**
 * FAQPage markup'i icin soru-cevap ciftleri.
 * Sorular insanlarin cevap motoruna yazdigi haliyle yazilir.
 */
function faq_pairs(array $job): array
{
    $title = (string)($job['title'] ?? $job['slug'] ?? '');
    $lower = mb_strtolower($title);
    $v     = verdict_meta($job['verdict'] ?? '');
    $pairs = [];

    $pairs[] = [
        'q' => sprintf('Will AI replace %ss?', $lower),
        'a' => geo_answer($job),
    ];

    if (!empty($job['safeUntil'])) {
        $pairs[] = [
            'q' => sprintf('How long is %s work safe from AI?', $lower),
            'a' => sprintf(
                'Our estimate is roughly %s. That is the year by which the core tasks of this job are expected to be routinely machine-done in ordinary practice — after capability arrives, after employers adopt it, and after regulators allow it. It is not the year the job title disappears. Current verdict: %s.',
                (string)$job['safeUntil'],
                $v['label']
            ),
        ];
    }

    $goneTasks = [];
    foreach (($job['tasks'] ?? []) as $task) {
        if (in_array($task['verdict'] ?? '', ['gone', 'going'], true) && !empty($task['name'])) {
            $goneTasks[] = lower_first((string)$task['name']) . ' (' . (string)$task['verdict'] . ')';
        }
    }
    if ($goneTasks) {
        $pairs[] = [
            'q' => sprintf('Which %s tasks is AI already doing?', $lower),
            'a' => sprintf('%s. Each is judged separately rather than rolling the whole job into one answer.', ucfirst(list_phrase($goneTasks))),
        ];
    }

    if (!empty($job['whatSurvives'])) {
        $pairs[] = [
            'q' => sprintf('What part of being %s is safe from AI?', with_article($lower)),
            'a' => (string)$job['whatSurvives'],
        ];
    }

    if (!empty($job['adaptPrompt'])) {
        $pairs[] = [
            'q' => sprintf('How should %s use AI instead of competing with it?', with_article($lower)),
            'a' => sprintf('Use it on the tasks already marked gone or going, and keep the judgment. There is a copy-ready prompt written for this specific job at %s.', job_url((string)$job['slug'])),
        ];
    }

    return $pairs;
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

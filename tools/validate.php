<?php
/**
 * Sema dogrulayici.
 *   CLI:  php tools/validate.php
 *   Web:  /tools/validate.php?key=BUILD_KEY   (.htaccess bunu 404'ler; lokalde kullan)
 * Cikis kodu: hata varsa 1.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!build_key_ok($_GET['key'] ?? null)) {
        http_response_code(403);
        exit("forbidden — set BUILD_KEY in inc/config.php first\n");
    }
}

const REQUIRED_FIELDS = ['slug', 'title', 'category', 'verdict', 'oneLiner', 'tasks', 'resistanceTags', 'adaptPrompt', 'lastReviewed'];

$errors   = [];
$warnings = [];
$count    = 0;

foreach (glob(JOBS_DIR . '/*.json') ?: [] as $path) {
    $file = basename($path);
    $slug = basename($path, '.json');
    $count++;

    if (!valid_slug($slug)) {
        $errors[] = "$file: dosya adi gecerli bir slug degil (sadece a-z, 0-9, tek tire)";
        continue;
    }

    $raw  = (string)file_get_contents($path);
    $job  = json_decode($raw, true);
    if (!is_array($job)) {
        $errors[] = "$file: gecersiz JSON — " . json_last_error_msg();
        continue;
    }

    foreach (REQUIRED_FIELDS as $field) {
        if (!isset($job[$field]) || $job[$field] === '' || $job[$field] === []) {
            $errors[] = "$file: zorunlu alan eksik veya bos — '$field'";
        }
    }

    if (isset($job['slug']) && $job['slug'] !== $slug) {
        $errors[] = "$file: JSON icindeki slug ('{$job['slug']}') dosya adiyla uyusmuyor";
    }

    if (isset($job['verdict']) && !isset(VERDICTS[$job['verdict']])) {
        $errors[] = "$file: bilinmeyen verdict '{$job['verdict']}' (gecerli: " . implode(', ', array_keys(VERDICTS)) . ')';
    }

    if (isset($job['category']) && !isset(CATEGORIES[$job['category']])) {
        $errors[] = "$file: bilinmeyen kategori '{$job['category']}' (gecerli: " . implode(', ', array_keys(CATEGORIES)) . ')';
    }

    // Gorev kirilimi
    if (isset($job['tasks']) && is_array($job['tasks'])) {
        $n = count($job['tasks']);
        if ($n < 4 || $n > 8) {
            $warnings[] = "$file: $n gorev var — plan 4-8 arasi oneriyor";
        }
        foreach ($job['tasks'] as $i => $task) {
            $where = "$file: tasks[$i]";
            if (!is_array($task)) {
                $errors[] = "$where: nesne olmali";
                continue;
            }
            if (empty($task['name'])) {
                $errors[] = "$where: 'name' eksik";
            }
            if (!isset($task['verdict']) || !isset(TASK_VERDICTS[$task['verdict']])) {
                $errors[] = "$where: gorev verdict'i gone|going|safe olmali";
            }
            foreach ($task['tags'] ?? [] as $tag) {
                if (!isset(RESISTANCE_TAGS[$tag])) {
                    $errors[] = "$where: bilinmeyen direnc tag'i '$tag'";
                }
            }
            if ((($task['verdict'] ?? '') === 'safe') && empty($task['tags'])) {
                $warnings[] = "$where: 'safe' gorevin neden dirençli oldugunu soyleyen tag'i yok";
            }
        }
    } elseif (isset($job['tasks'])) {
        $errors[] = "$file: 'tasks' dizi olmali";
    }

    // Direnc tag'leri
    if (isset($job['resistanceTags']) && is_array($job['resistanceTags'])) {
        $n = count($job['resistanceTags']);
        if ($n < 1 || $n > 3) {
            $warnings[] = "$file: $n direnc tag'i var — 1-3 arasi olmali";
        }
        foreach ($job['resistanceTags'] as $tag) {
            if (!isset(RESISTANCE_TAGS[$tag])) {
                $errors[] = "$file: bilinmeyen direnc tag'i '$tag'";
            }
        }
    }

    // safeUntil
    if (isset($job['safeUntil']) && !preg_match('/^(19|20)\d{2}$/', (string)$job['safeUntil'])) {
        $errors[] = "$file: safeUntil 4 haneli yil olmali ('{$job['safeUntil']}')";
    }
    if (($job['verdict'] ?? '') !== 'safe' && empty($job['safeUntil'])) {
        $warnings[] = "$file: safe olmayan verdict'te safeUntil bekleniyor (paylasim kartinin malzemesi)";
    }

    // lastReviewed
    if (isset($job['lastReviewed']) && !preg_match('/^(19|20)\d{2}-(0[1-9]|1[0-2])$/', (string)$job['lastReviewed'])) {
        $errors[] = "$file: lastReviewed YYYY-MM formatinda olmali ('{$job['lastReviewed']}')";
    }

    // Kopyalanabilir artifact — sitenin varlik sebebi
    if (isset($job['adaptPrompt']) && mb_strlen((string)$job['adaptPrompt']) < 200) {
        $warnings[] = "$file: adaptPrompt cok kisa (" . mb_strlen((string)$job['adaptPrompt']) . " karakter) — kullanilabilir bir prompt olmali";
    }

    if (empty($job['sources'])) {
        $warnings[] = "$file: 'sources' bos — community draft olarak isaretlenecek";
    }

    if (mb_strlen((string)($job['oneLiner'] ?? '')) > 120) {
        $warnings[] = "$file: oneLiner 120 karakteri asiyor — OG kartinda kesilir";
    }
}

// --- changelog tutarliligi ---
$log = load_changelog();
foreach ($log as $i => $e) {
    $where = "changelog.json[$i]";
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($e['date'] ?? ''))) {
        $errors[] = "$where: date YYYY-MM-DD olmali";
    }
    $slug = (string)($e['slug'] ?? '');
    if (!valid_slug($slug)) {
        $errors[] = "$where: gecersiz slug '$slug'";
    } elseif (!is_file(JOBS_DIR . '/' . $slug . '.json')) {
        $warnings[] = "$where: '$slug' entry'si artik yok";
    }
    if (!isset(VERDICTS[(string)($e['to'] ?? '')])) {
        $errors[] = "$where: 'to' gecerli bir verdict olmali";
    }
    $from = $e['from'] ?? null;
    if ($from !== null && $from !== '' && !isset(VERDICTS[(string)$from])) {
        $errors[] = "$where: 'from' null ya da gecerli bir verdict olmali";
    }
    if (empty($e['why'])) {
        $warnings[] = "$where: 'why' bos — degisikligin sebebi yazilmali";
    }
}

// --- yayinda olup changelog'a girmemis entry'ler ---
$logged = array_column($log, 'slug');
foreach (array_keys(load_all_jobs()) as $slug) {
    if (!in_array($slug, $logged, true)) {
        $warnings[] = "$slug: changelog.json'da kaydi yok — tazelik sinyali eksik kalir";
    }
}

$out = static function (string $s) use ($cli): void {
    echo $s . "\n";
};

$out("$count entry tarandi.");
if ($warnings) {
    $out("\n" . count($warnings) . " uyari:");
    foreach ($warnings as $w) {
        $out("  ! $w");
    }
}
if ($errors) {
    $out("\n" . count($errors) . " HATA:");
    foreach ($errors as $e) {
        $out("  x $e");
    }
    if ($cli) {
        exit(1);
    }
    http_response_code(422);
    exit;
}
$out("\nHata yok.");

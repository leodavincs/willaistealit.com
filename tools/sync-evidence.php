<?php
/**
 * research/occupations.json -> data/jobs/*.json icine evidenceStrength yazar.
 *
 * research/ sunucuya deploy EDILMEZ (icerik yol haritasi ve kaynak kutuphanesi).
 * Entry sayfasinin kanit gucunu gosterebilmesi icin bilginin entry'nin kendi
 * dosyasinda olmasi gerekiyor. Kutuphane calisma alani, data/jobs/ ise yayin.
 *
 *   CLI: php tools/sync-evidence.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("cli only\n");
}

$libPath = ROOT . '/research/occupations.json';
if (!is_file($libPath)) {
    echo "research/occupations.json yok — atlaniyor\n";
    exit(0);
}

$lib  = json_decode((string)file_get_contents($libPath), true);
$rows = $lib['occupations'] ?? $lib;

$byslug = [];
foreach ($rows as $r) {
    if (!empty($r['slug'])) {
        $byslug[$r['slug']] = $r;
    }
}

$changed = 0;
$missing = [];

foreach (glob(JOBS_DIR . '/*.json') ?: [] as $path) {
    $slug = basename($path, '.json');
    if (!isset($byslug[$slug])) {
        $missing[] = $slug;
        continue;
    }

    $raw = (string)file_get_contents($path);
    $job = json_decode($raw, true);
    if (!is_array($job)) {
        continue;
    }

    $strength = (string)($byslug[$slug]['evidenceStrength'] ?? '');
    if ($strength === '' || ($job['evidenceStrength'] ?? null) === $strength) {
        continue;
    }

    // lastReviewed'dan hemen sonraya yerlestir ki dosyalar tutarli kalsin
    $out = [];
    foreach ($job as $k => $v) {
        $out[$k] = $v;
        if ($k === 'lastReviewed') {
            $out['evidenceStrength'] = $strength;
        }
    }
    if (!isset($out['evidenceStrength'])) {
        $out['evidenceStrength'] = $strength;
    }

    file_put_contents(
        $path,
        json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
    echo "  $slug -> $strength\n";
    $changed++;
}

echo "\n$changed entry guncellendi.\n";
if ($missing) {
    echo "Kutuphanede karsiligi olmayan: " . implode(', ', $missing) . "\n";
}

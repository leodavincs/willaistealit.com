<?php
/**
 * Ingilizce dil davranisi.
 * Govdeler inc/functions.php'deki mevcut uygulamadan BIREBIR tasindi; tek fark
 * URL'lerin parametre olarak gelmesi (Lang URL uretmez).
 */
declare(strict_types=1);

final class En extends Lang
{
    public function code(): string
    {
        return 'en';
    }

    /** '2026-08' -> 'August 2026'. Bozuk girdide bos doner. */
    public function month(string $ym): string
    {
        $parts = $this->splitYm($ym);
        if ($parts === null) {
            return '';
        }
        [$year, $month] = $parts;
        // intl acikken de tablo ile AYNI bicimi uretmeliyiz (spec 4.1); tablo
        // zaten dogru bicimi tasidigi icin iki yol ayni sonuca varir.
        return $this->t('month.format', $this->t('month.' . $month), (string)$year);
    }

    /** ["a","b","c"] -> "a, b and c" */
    public function listPhrase(array $items): string
    {
        $items = array_values(array_filter($items));
        $n = count($items);
        if ($n === 0) {
            return '';
        }
        if ($n === 1) {
            return (string)$items[0];
        }
        $last = array_pop($items);
        return implode(', ', $items) . ' ' . $this->t('list.and') . ' ' . $last;
    }

    /** "accountant" -> "an accountant". Sesli harfle baslayanlar icin "an". */
    public function withArticle(string $word): string
    {
        $first = mb_strtolower(mb_substr(trim($word), 0, 1));
        return (in_array($first, ['a', 'e', 'i', 'o', 'u'], true) ? 'an ' : 'a ') . $word;
    }

    /**
     * Cumle icine gomulecek basligi kucultur — ama ilk kelime kisaltmaysa dokunmaz
     * ("CV screening" -> "CV screening", "Data entry" -> "data entry").
     */
    public function lowerFirst(string $text): string
    {
        $text  = trim($text);
        $first = (string)(preg_split('/\s+/u', $text)[0] ?? '');
        if ($first !== '' && mb_strtoupper($first) === $first && mb_strlen($first) > 1) {
            return $text;
        }
        return mb_strtolower(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    public function plural(string $word, int $n = 2): string
    {
        return $n === 1 ? $word : $word . 's';
    }

    /** Bilinmeyen verdict 'shrinking'e duser — verdict_meta() ile ayni davranis. */
    private function verdictKey(array $job): string
    {
        $v = (string)($job['verdict'] ?? '');
        return isset(VERDICTS[$v]) ? $v : 'shrinking';
    }

    public function geoAnswer(array $job): string
    {
        if (!empty($job['geoAnswer'])) {
            return (string)$job['geoAnswer'];
        }

        $title = (string)($job['title'] ?? $job['slug'] ?? 'this job');
        $lower = mb_strtolower($title);
        $date  = $this->month((string)($job['lastReviewed'] ?? '')) ?: $this->t('geo.fallbackDate');
        $v     = (string)($job['verdict'] ?? 'shrinking');

        $gone = $safe = [];
        foreach (($job['tasks'] ?? []) as $task) {
            $name = $this->lowerFirst((string)($task['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (($task['verdict'] ?? '') === 'gone') {
                $gone[] = $name;
            } elseif (($task['verdict'] ?? '') === 'safe') {
                $safe[] = $name;
            }
        }

        $until = (string)($job['safeUntil'] ?? '');
        $verdictSentence = match ($v) {
            'safe'        => $this->t('geo.verdict.safe', $title),
            'on-the-menu' => $this->t('geo.verdict.onthemenu', $lower,
                                      $until !== '' ? $this->t('geo.verdict.onthemenu.until', $until) : ''),
            default       => $this->t('geo.verdict.shrinking', $lower,
                                      $until !== '' ? $this->t('geo.verdict.shrinking.until', $until) : ''),
        };

        $out = $this->t('geo.prefix', $date, $verdictSentence);

        if ($gone) {
            $out .= $this->t('geo.gone', $this->listPhrase(array_slice($gone, 0, 3)));
        }
        if ($safe) {
            $out .= $this->t('geo.safe', $this->listPhrase(array_slice($safe, 0, 3)));
        }
        if (!empty($job['resistanceTags'])) {
            $out .= $this->t('geo.resistance', $this->listPhrase(array_map(
                fn ($t) => $this->tagName((string)$t),
                array_slice((array)$job['resistanceTags'], 0, 3)
            )));
        }

        return $out;
    }

    public function faqPairs(array $job, string $url): array
    {
        $title = (string)($job['title'] ?? $job['slug'] ?? '');
        $lower = mb_strtolower($title);
        $pairs = [];

        $pairs[] = [
            'q' => $this->t('faq.replace.q', $lower),
            'a' => $this->geoAnswer($job),
        ];

        if (!empty($job['safeUntil'])) {
            $pairs[] = [
                'q' => $this->t('faq.howLong.q', $lower),
                'a' => $this->t('faq.howLong.a', (string)$job['safeUntil'],
                                $this->verdictLabel($this->verdictKey($job))),
            ];
        }

        $goneTasks = [];
        foreach (($job['tasks'] ?? []) as $task) {
            if (in_array($task['verdict'] ?? '', ['gone', 'going'], true) && !empty($task['name'])) {
                $goneTasks[] = $this->lowerFirst((string)$task['name']) . ' (' . (string)$task['verdict'] . ')';
            }
        }
        if ($goneTasks) {
            $pairs[] = [
                'q' => $this->t('faq.whichTasks.q', $lower),
                'a' => $this->t('faq.whichTasks.a', ucfirst($this->listPhrase($goneTasks))),
            ];
        }

        if (!empty($job['whatSurvives'])) {
            $pairs[] = [
                'q' => $this->t('faq.whatSafe.q', $this->withArticle($lower)),
                'a' => (string)$job['whatSurvives'],
            ];
        }

        if (!empty($job['adaptPrompt'])) {
            $pairs[] = [
                'q' => $this->t('faq.howUse.q', $this->withArticle($lower)),
                'a' => $this->t('faq.howUse.a', $url),
            ];
        }

        return $pairs;
    }

    public function shareText(array $job, string $url): string
    {
        $key  = $this->verdictKey($job);
        $t    = (string)($job['title'] ?? $job['slug'] ?? '');
        $line = VERDICTS[$key]['dot'] . ' ' . $this->verdictLabel($key);
        if (!empty($job['safeUntil'])) {
            $line .= $this->t('share.safeUntil', (string)$job['safeUntil']);
        }
        // strtoupper (mb_ DEGIL) — mevcut davranisla birebir ayni kalmali.
        return sprintf("%s: %s\n\n%s\n\n%s", strtoupper($t), $line,
                       (string)($job['oneLiner'] ?? ''), $url);
    }

    public function evidenceNote(array $job): ?array
    {
        $sources  = (array)($job['sources'] ?? []);
        $strength = (string)($job['evidenceStrength'] ?? '');

        if (count($sources) === 0 || $strength === 'none') {
            return ['level' => 'draft',
                    'label' => $this->t('evidence.draft.label'),
                    'text'  => $this->t('evidence.draft.text')];
        }
        if ($strength === 'thin') {
            return ['level' => 'thin',
                    'label' => $this->t('evidence.thin.label'),
                    'text'  => $this->t('evidence.thin.text')];
        }
        return null;
    }
}

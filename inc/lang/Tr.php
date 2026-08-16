<?php
/**
 * Turkce dil davranisi.
 * En kritik nokta I/i tuzagi: mb_strtolower('İ') 'i' + U+0307 (birlesen nokta)
 * uretir ve hicbir seyle eslesmez; mb_strtoupper('i') ise 'I' verir, Turkcede 'İ'
 * olmali. Bu yuzden harf haritasi ONCE uygulanir.
 */
declare(strict_types=1);

final class Tr extends Lang
{
    /** Turkceye ozgu kucultme: I -> i degil, I -> ı ve İ -> i. */
    public function lower(string $s): string
    {
        return mb_strtolower(strtr($s, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
    }

    /** Turkceye ozgu buyultme: i -> I degil, i -> İ ve ı -> I. */
    public function upper(string $s): string
    {
        return mb_strtoupper(strtr($s, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
    }

    public function code(): string
    {
        return 'tr';
    }

    public function month(string $ym): string
    {
        $parts = $this->splitYm($ym);
        if ($parts === null) {
            return '';
        }
        [$year, $month] = $parts;
        return $this->t('month.format', $this->t('month.' . $month), (string)$year);
    }

    /**
     * ["a","b","c"] -> "a, b ve c"
     *
     * Ogelerden biri kendi icinde 've' tasiyorsa ayirici noktali virgul olur:
     * "denetim yargisi ve onemlilik kararlari ve yasal imza ve temsil" okunamaz,
     * "...kararlari; yasal imza ve temsil" okunur. Ingilizcede bu sorun yok,
     * cunku gorev adlari '&' ile, liste 'and' ile baglaniyor.
     */
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
        $and     = $this->t('list.and');
        $collide = false;
        foreach ($items as $item) {
            if (str_contains(' ' . mb_strtolower((string)$item, 'UTF-8') . ' ', ' ' . $and . ' ')) {
                $collide = true;
                break;
            }
        }
        $last = array_pop($items);
        return $collide
            ? implode('; ', $items) . '; ' . $last
            : implode(', ', $items) . ' ' . $and . ' ' . $last;
    }

    /** Turkcede belirsiz artikel YOK — kelime oldugu gibi doner. */
    public function withArticle(string $word): string
    {
        return $word;
    }

    /** Ilk kelime kisaltmaysa dokunmaz ("KDV beyanı" -> "KDV beyanı"). */
    public function lowerFirst(string $text): string
    {
        $text  = trim($text);
        $first = (string)(preg_split('/\s+/u', $text)[0] ?? '');
        if ($first !== '' && $this->upper($first) === $first && mb_strlen($first) > 1) {
            return $text;
        }
        return $this->lower(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    /** Unlu uyumu: son unlu kalinsa -lar, inceyse -ler. */
    public function plural(string $word, int $n = 2): string
    {
        if ($n === 1 || $word === '') {
            return $word;
        }
        $back = ['a', 'ı', 'o', 'u'];
        $suffix = 'ler';
        foreach (array_reverse(preg_split('//u', $this->lower($word), -1, PREG_SPLIT_NO_EMPTY) ?: []) as $ch) {
            if (in_array($ch, ['a', 'e', 'ı', 'i', 'o', 'ö', 'u', 'ü'], true)) {
                $suffix = in_array($ch, $back, true) ? 'lar' : 'ler';
                break;
            }
        }
        return $word . $suffix;
    }

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

        // Baslik cumlenin ORTASINDA geciyor: "muhasebeci rolu", "Muhasebeci rolu" degil.
        $title = $this->lowerFirst((string)($job['title'] ?? $job['slug'] ?? 'bu meslek'));
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
            'on-the-menu' => $this->t('geo.verdict.onthemenu', $title,
                                      $until !== '' ? $this->t('geo.verdict.onthemenu.until', $until) : ''),
            default       => $this->t('geo.verdict.shrinking', $title,
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
        $pairs = [];

        $pairs[] = ['q' => $this->t('faq.replace.q', $title), 'a' => $this->geoAnswer($job)];

        if (!empty($job['safeUntil'])) {
            $pairs[] = [
                'q' => $this->t('faq.howLong.q', $title),
                'a' => $this->t('faq.howLong.a', (string)$job['safeUntil'],
                                $this->verdictLabel($this->verdictKey($job))),
            ];
        }

        $goneTasks = [];
        foreach (($job['tasks'] ?? []) as $task) {
            if (in_array($task['verdict'] ?? '', ['gone', 'going'], true) && !empty($task['name'])) {
                $goneTasks[] = $this->lowerFirst((string)$task['name'])
                             . ' (' . $this->taskVerdictLabel((string)$task['verdict']) . ')';
            }
        }
        if ($goneTasks) {
            $pairs[] = [
                'q' => $this->t('faq.whichTasks.q', $title),
                'a' => $this->t('faq.whichTasks.a', $this->upperFirst($this->listPhrase($goneTasks))),
            ];
        }

        if (!empty($job['whatSurvives'])) {
            $pairs[] = ['q' => $this->t('faq.whatSafe.q', $title), 'a' => (string)$job['whatSurvives']];
        }
        if (!empty($job['adaptPrompt'])) {
            $pairs[] = ['q' => $this->t('faq.howUse.q', $title), 'a' => $this->t('faq.howUse.a', $url)];
        }

        return $pairs;
    }

    /** ucfirst() cok baytli harfte bozulur; Turkce buyultme ile yapilir. */
    private function upperFirst(string $s): string
    {
        return $s === '' ? '' : $this->upper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    public function shareText(array $job, string $url): string
    {
        $key  = $this->verdictKey($job);
        $t    = (string)($job['title'] ?? $job['slug'] ?? '');
        $line = VERDICTS[$key]['dot'] . ' ' . $this->verdictLabel($key);
        if (!empty($job['safeUntil'])) {
            $line .= $this->t('share.safeUntil', (string)$job['safeUntil']);
        }
        return sprintf("%s: %s\n\n%s\n\n%s", $this->upper($t), $line,
                       (string)($job['oneLiner'] ?? ''), $url);
    }

    public function evidenceNote(array $job): ?array
    {
        $sources  = (array)($job['sources'] ?? []);
        $strength = (string)($job['evidenceStrength'] ?? '');

        if (count($sources) === 0 || $strength === 'none') {
            return ['level' => 'draft', 'label' => $this->t('evidence.draft.label'),
                    'text' => $this->t('evidence.draft.text')];
        }
        if ($strength === 'thin') {
            return ['level' => 'thin', 'label' => $this->t('evidence.thin.label'),
                    'text' => $this->t('evidence.thin.text')];
        }
        return null;
    }
}

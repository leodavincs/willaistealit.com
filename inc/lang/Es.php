<?php
/**
 * Comportamiento del idioma espanol.
 * Regla clave: la conjuncion "y" pasa a "e" ante palabras que empiezan por el
 * sonido /i/ (i- o hi-), SALVO cuando forman diptongo: hie- / hia-.
 *   padres e hijos · agujas e hilo · pero: cobre y hierro
 */
declare(strict_types=1);

final class Es extends Lang
{
    public function code(): string
    {
        return 'es';
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

    /** "y" ya da "e": sonraki kelimenin sesine gore. */
    private function conjunction(string $next): string
    {
        $w = mb_strtolower(trim($next), 'UTF-8');
        // Aksanli i de sayilir: "e íntegro"
        if (preg_match('/^h?(i|í)/u', $w) !== 1) {
            return $this->t('list.and');
        }
        // Diptong istisnasi: hie-/hia- /je/ /ja/ diye okunur, "y" kalir.
        if (preg_match('/^hi(e|a)/u', $w) === 1) {
            return $this->t('list.and');
        }
        return $this->t('list.and.e');
    }

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
        return implode(', ', $items) . ' ' . $this->conjunction((string)$last) . ' ' . $last;
    }

    /** Sezgisel: -a ile bitenler disil sayilir. Sablonlar buna bagimli DEGIL. */
    public function withArticle(string $word): string
    {
        $w = trim($word);
        return (mb_substr(mb_strtolower($w, 'UTF-8'), -1) === 'a' ? 'una ' : 'un ') . $w;
    }

    public function lowerFirst(string $text): string
    {
        $text  = trim($text);
        $first = (string)(preg_split('/\s+/u', $text)[0] ?? '');
        if ($first !== '' && mb_strtoupper($first, 'UTF-8') === $first && mb_strlen($first) > 1) {
            return $text;
        }
        return mb_strtolower(mb_substr($text, 0, 1), 'UTF-8') . mb_substr($text, 1);
    }

    /** Unlu ile biterse +s, 'z' ile biterse -ces, digerlerinde +es. */
    public function plural(string $word, int $n = 2): string
    {
        if ($n === 1 || $word === '') {
            return $word;
        }
        $last = mb_substr(mb_strtolower($word, 'UTF-8'), -1);
        if ($last === 'z') {
            return mb_substr($word, 0, -1) . 'ces';
        }
        return in_array($last, ['a', 'e', 'i', 'o', 'u'], true) ? $word . 's' : $word . 'es';
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

        $title = (string)($job['title'] ?? $job['slug'] ?? 'este trabajo');
        $lower = mb_strtolower($title, 'UTF-8');
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
            'safe'        => $this->t('geo.verdict.safe', $lower),
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
                fn ($t) => $this->tagDefinition((string)$t) !== ''
                    ? rtrim($this->tagDefinition((string)$t), '.')
                    : str_replace('-', ' ', (string)$t),
                array_slice((array)$job['resistanceTags'], 0, 3)
            )));
        }

        return $out;
    }

    public function faqPairs(array $job, string $url): array
    {
        $lower = mb_strtolower((string)($job['title'] ?? $job['slug'] ?? ''), 'UTF-8');
        $pairs = [];

        $pairs[] = ['q' => $this->t('faq.replace.q', $lower), 'a' => $this->geoAnswer($job)];

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
                $goneTasks[] = $this->lowerFirst((string)$task['name'])
                             . ' (' . $this->taskVerdictLabel((string)$task['verdict']) . ')';
            }
        }
        if ($goneTasks) {
            $list = $this->listPhrase($goneTasks);
            $pairs[] = [
                'q' => $this->t('faq.whichTasks.q', $lower),
                'a' => $this->t('faq.whichTasks.a',
                                mb_strtoupper(mb_substr($list, 0, 1), 'UTF-8') . mb_substr($list, 1)),
            ];
        }

        if (!empty($job['whatSurvives'])) {
            $pairs[] = ['q' => $this->t('faq.whatSafe.q', $lower), 'a' => (string)$job['whatSurvives']];
        }
        if (!empty($job['adaptPrompt'])) {
            $pairs[] = ['q' => $this->t('faq.howUse.q', $lower), 'a' => $this->t('faq.howUse.a', $url)];
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
        return sprintf("%s: %s\n\n%s\n\n%s", mb_strtoupper($t, 'UTF-8'), $line,
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

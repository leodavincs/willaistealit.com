<?php
/**
 * Dil sozlesmesi.
 * Base tablo okumasini uygular — her dilde ayni. Alt siniflar yalnizca DILE GORE
 * DEGISEN davranisi uygular: cogul, artikel, liste bagi, tarih, uretilen cumleler.
 *
 * Lang URL URETMEZ, dosya okumaz, super globallere bakmaz, HTML kacisi yapmaz.
 * Girdisi veri, ciktisi metin. URL gerekiyorsa PARAMETRE olarak gelir.
 */
declare(strict_types=1);

abstract class Lang
{
    /** @param array<string,string> $strings data/locale/<code>.php tablosu */
    public function __construct(protected array $strings) {}

    // --- Base'in kendisi uyguluyor: tablo okumasi her dilde ayni ---

    public function has(string $key): bool
    {
        return isset($this->strings[$key]);
    }

    public function t(string $key, mixed ...$args): string
    {
        if (!isset($this->strings[$key])) {
            if (defined('LOCALE_STRICT') && LOCALE_STRICT) {
                throw new RuntimeException("locale: eksik anahtar '$key'");
            }
            return $key;                       // sessiz bos donmez
        }
        $s = $this->strings[$key];
        return $args === [] ? $s : vsprintf($s, $args);
    }

    /**
     * Buyuk harf. Turkce disinda mb_strtoupper yeterli; Tr bunu EZER cunku
     * mb_strtoupper('i') 'I' verir ve Turkcede dogrusu 'İ'dir.
     */
    public function upper(string $s): string
    {
        return mb_strtoupper($s, 'UTF-8');
    }

    public function verdictLabel(string $key): string
    {
        return $this->t("verdict.$key.label");
    }

    public function verdictBlurb(string $key): string
    {
        return $this->t("verdict.$key.blurb");
    }

    public function taskVerdictLabel(string $key): string
    {
        return $this->t("task.$key.label");
    }

    public function categoryLabel(string $key): string
    {
        return $this->has("category.$key") ? $this->t("category.$key") : $this->t('category.unknown');
    }

    /**
     * Direnc etiketinin ADI — uretilen ozet cumlesinde kullanilir. Tanim (tagDefinition)
     * tam bir cumledir; onu listeye dizmek okunamaz metin uretir.
     */
    public function tagName(string $key): string
    {
        $k = 'tagName.' . $key;
        return $this->has($k) ? $this->t($k) : str_replace('-', ' ', $key);
    }

    public function tagDefinition(string $key): string
    {
        return $this->has("tag.$key") ? $this->t("tag.$key") : '';
    }

    /** '2026-08' -> [2026, 8]; bozuk girdide null. */
    protected function splitYm(string $ym): ?array
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $ym, $m) !== 1) {
            return null;
        }
        $month = (int)$m[2];
        return ($month >= 1 && $month <= 12) ? [(int)$m[1], $month] : null;
    }

    // --- Dile gore DEGISEN davranis ---

    abstract public function code(): string;
    abstract public function month(string $ym): string;
    abstract public function listPhrase(array $items): string;
    abstract public function withArticle(string $word): string;
    abstract public function lowerFirst(string $text): string;
    abstract public function plural(string $word, int $n = 2): string;

    abstract public function geoAnswer(array $job): string;
    /** $url: entry'nin kanonik adresi — Lang uretmez, PARAMETRE alir. */
    abstract public function faqPairs(array $job, string $url): array;
    /** URL PARAMETRE olarak gelir — Lang URL uretmez. */
    abstract public function shareText(array $job, string $url): string;
    abstract public function evidenceNote(array $job): ?array;
}

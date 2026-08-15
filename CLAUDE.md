# willaistealit.com

AI'ın hangi mesleklerin hangi görevlerini aldığını task seviyesinde yargılayan directory
sitesi. Her entry bir meslek, her meslek 4–8 task'a bölünür, her task'ın kendi verdict'i var.

Framework yok, veritabanı yok, build adımı yok: PHP 8.3 diskten JSON okuyor. İçerik
`data/jobs/<slug>.json`. Yerelde çalıştırmak için `php -S localhost:8000 router.php`.

## Hangi işte ne okunur

| İş | Önce oku |
|----|----------|
| Entry yazmak veya düzenlemek | `docs/memory/voice.md` + `CONTRIBUTING.md` |
| Verdict veya `safeUntil` belirlemek | `docs/memory/verdict-rubric.md` |
| "Bunu neden böyle yapmıştık?" | `grep -ri "<konu>" docs/memory/decisions/` |
| Alan tanımı, katkı kuralları | `CONTRIBUTING.md` |

## Hafıza hakkında

`docs/memory/` editoryal hafızadır — nasıl yazılır ve nasıl karar verilir. `CONTRIBUTING.md`
katkıcıya yöneliktir ve alan tanımlarını içerir; `/methodology` verdict tanımlarını halka
açık olarak yayınlar. Çakışma olursa yayınlanmış olan doğrudur. Sistemin tamamı:
`docs/memory/README.md`.

Hafızaya yazarken: satırı öner, kullanıcı onaylasın. Otomatik yazma yok.
`docs/memory/decisions/` append-only — not silinmez, `status: superseded` işaretlenir.

Repo public. Sponsor fiyatlaması, gelir modeli ve prospect bilgisi hafızaya yazılmaz.

## Entry değiştirdikten sonra

```bash
php tools/validate.php        # temiz olmalı
php tools/build-index.php     # arama indeksini yenile, cache temizle
```

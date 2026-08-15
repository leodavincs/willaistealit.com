# İçerik Hafızası

Bu klasör, `CONTRIBUTING.md`'de olmayan editoryal bilgiyi tutar: entry'lerin
ses tonu, sınır vakalarda verdict'in nasıl seçildiği, ve geçmiş proje
kararlarının gerekçesi.

## Dosyalar

| Dosya | Ne zaman okunur | Değişim hızı |
|-------|-----------------|--------------|
| `voice.md` | Her içerik yazımında, tamamı | Yavaş |
| `verdict-rubric.md` | Her verdict kararında, tamamı | Yavaş |
| `decisions/` | "Bunu neden böyle yapmıştık?" sorusunda, `grep` ile | Sürekli büyür |

## Sınır: CONTRIBUTING.md

`CONTRIBUTING.md` katkıcıya yöneliktir ve alan tanımlarını, kabul kriterlerini,
`safeUntil`'in ne demek olduğunu, tag'lerin neden yapısal duvar olduğunu anlatır.

Bu klasör üreticiye yöneliktir ve o kuralların **somut vakalara nasıl
uygulandığını** tutar. Hafıza CONTRIBUTING'i tekrar etmez. İki yerde çakışan
bir bilgi varsa CONTRIBUTING doğrudur ve buradaki kopya silinir.

## Kurallar

- `voice.md` + `verdict-rubric.md` toplamı **400 satırı geçemez**. Aşılırsa
  içerik damıtılır, dosya bölünmez. Bu tavan olmadan hafıza kendi ağırlığından
  çöker ve context'te ölü kural birikir.
- `decisions/` **append-only**. Not silinmez; geçersiz kalan not frontmatter'da
  `status: superseded` işaretlenir ve gövdesine `superseded by [[yeni-not]]`
  satırı eklenir. Yanlış çıkmış bir kararın kaydı, kararın kendisi kadar değerli.
- Kural dosyaları yerinde düzenlenir, append edilmez.
- **Bu klasör public.** Repo açık kaynak (MIT) ve Hostinger Git deployment ile
  deploy ediliyor. Sponsor fiyatlaması, gelir modeli, prospect listesi buraya
  **yazılmaz** — onlar `willaisteal-plan.md`'de kalır ve repoya girmez.
  `.htaccess` `docs/`'u siteden 404'e düşürür ama GitHub'dan gizlemez.

## Obsidian

Vault = repo kökü. Ayrı klasör, kopya veya senkron yok. Obsidian'da
`Open folder as vault` ile repo kökünü aç. `.obsidian/` git tarafından
yok sayılır. `decisions/` notları birbirine `[[wikilink]]` ile bağlanır.

## Nasıl güncellenir

Bir entry tamamlandığında veya bir karar alındığında agent ilgili dosyaya
satır **önerir**, sen onaylarsın. Otomatik yazma yok.

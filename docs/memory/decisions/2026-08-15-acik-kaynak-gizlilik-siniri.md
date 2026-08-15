---
date: 2026-08-15
title: Açık kaynak — editoryal kurallar açık, ticari strateji kapalı
status: active
---

# Açık kaynak ve gizlilik sınırı

## Karar
Repo public (MIT). Sınır klasöre göre değil **hassasiyete** göre çizilir:

| Yol | Durum | Gerekçe |
|-----|-------|---------|
| `docs/memory/` | Takipte, public | Katkıcının PR'dan önce okuması gereken şey |
| `docs/superpowers/` | Ignore | Spec ve uygulama planları — çalışma artığı |
| `willaisteal-plan.md` | Ignore, takip dışı | Sponsor fiyatlaması, gelir modeli, prospect listesi |

`.htaccess` içindeki `RedirectMatch 404 ^/(data|inc|cache|fonts|docs)(/|$)` kuralı `docs/`'u
siteden 404'e düşürür. Bu **siteden** gizler, GitHub'dan gizlemez — ayrım önemli.

## Neden
Açık kaynak bu formülde bir yan tercih değil, büyüme mekanizmasının kendisi. Referans proje
canivibecodeit de aynı modelde: MIT, `data/apps/` altında PR ile gelen JSON, 50+ katkıcı,
repoda hiç strateji dosyası yok. Kurucusu açıkça yazmış: açık kaynak olması para
kazanmasına engel olmamış.

Üç kazanç: içerik üretiminin topluluğa dağılması, GitHub'ın kendi başına bir trafik kanalı
olması, ve yargı satan bir site için verdict gerekçesinin denetlenebilir olması.

Editoryal kuralların açık olması özellikle gerekli. Gizlenirse dışarıdan gelen her entry
sese uymaz ve her PR elle düzeltilir — katkı akışı kendi ayağına sıkmış olur.

Deploy Git ile yapıldığı için `.gitignore` aynı zamanda deploy filtresi; bu yüzden ticari
dosyaların takip dışı olması hem GitHub hem `public_html` tarafını birlikte çözüyor.

## Kural
**`docs/memory/decisions/` public'tir. Ticari karar buraya yazılmaz.** Sponsor
fiyatlaması, gelir modeli ve prospect bilgisi `willaisteal-plan.md`'de kalır. Bu ayrım
yazılmazsa bir karar notuna fiyat stratejisi düşer ve fark edilmez.

## Reddedilen alternatif
Repoyu private tutmak. İçerik motorunu ve GitHub trafik kanalını birlikte kapatırdı; 100+
entry hedefi tek kişiyle ulaşılabilir değil.

İkinci reddedilen: `docs/`'un tamamını ignore'a almak. Editoryal kuralları da gizlerdi ve
hafızayı tek makinede, yedeksiz bırakırdı — ayrı vault'tan farkı kalmazdı.

> Bu bölüm karardan geriye doğru çıkarıldı (2026-08-15), o anki tartışmanın
> birebir kaydı değil. Yanlışsa düzeltilir.

## İlgili
[[2026-08-15-hafiza-repo-icinde]] · [[2026-08-15-konumlandirma-adaptasyon]] · [[2026-08-15-hosting-hostinger]]

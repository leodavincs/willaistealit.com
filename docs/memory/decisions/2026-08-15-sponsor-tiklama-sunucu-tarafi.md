---
date: 2026-08-15
title: Dışa tıklama sunucu tarafında sayılır, JS ile değil
status: active
---

# Dışa tıklama sayımı: `/go/` yönlendirmesi

## Karar
Sponsor kutusuna ve dışa açılan bağlantılara giden tıklamalar **JS analytics ile
sayılmaz.** `/go/<slug>` yönlendirmesi kurulur: PHP tıklamayı diske yazar, sonra 302
ile hedefe gönderir.

**Henüz yazılmadı.** Yazılacağı an: tasarım oturumu, sponsor kutusuyla birlikte
(plan 9.4/1).

## Neden
Sponsora verilen "kaç tıklama aldın" rakamı, aracı ne olursa olsun JS ile ölçüldüğünde
reklam engelleyicilerden etkilenir. Sunucu tarafı sayım bu sorunun tamamını ortadan
kaldırıyor:

- JavaScript yok → engellenemez
- Çerez yok → onay gerekmiyor
- Veri bizde → saklama süresi sınırsız, Matomo'nun ham kayıt budamasından etkilenmez

Ayrıca bu repo'nun mimarisine oturuyor: PHP, dosya tabanlı, ek bağımlılık yok.

Genel trafik rakamlarında eksik sayım sorun değil — hatta lehimize. Az gösterip fazla
teslim etmek, abartıp yenileme kaybetmekten iyi. Ama **faturalandırılabilir tek metrik**
kesin olmak zorunda, ve o metrik bu.

## Neden şimdi değil
Sitede şu an izlenecek dış bağlantı yok. `adaptTools` entry'lerde düz metin olarak
render ediliyor (`job.php`, `<span class="tag">`), link değil. Sponsor kutusu da yok
(`SPONSORS_LIVE = false`). İşaret edeceği hiçbir şey yokken yazmak erken.

## Sponsora ne söylenir
Doğruluk kadar **doğrulanabilirlik** önemli. Kendi sunucumuzdaki bir rakam, bizim
düzenleyebileceğimiz bir rakam — ekran görüntüsünün ikna ediciliği sınırlı. Matomo'nun
salt-okunur paylaşılan dashboard'u bu yüzden var: sponsor istediği an kendi bakar.

Söylenecek cümle: tıklama sunucu tarafında sayılıyor, reklam engelleyicilerden
etkilenmiyor; trafik rakamları ise engellenme sebebiyle olduğundan düşük.

## İlgili
[[2026-08-15-analytics-matomo]] · [[2026-08-15-icerik-tavani-93]]

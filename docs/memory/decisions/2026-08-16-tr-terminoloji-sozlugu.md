---
date: 2026-08-16
title: TR terminoloji sözlüğü — çeviriden önce sabitlendi
status: active
---

# TR terminoloji sözlüğü

## Karar

**Sözlük çeviriden önce sabitlenir ve çeviri boyunca değişmez.** 69 editoryal anahtar
ve onlarca entry farklı zamanlarda yazılacak; sözlük olmadan `verdict` üç ayrı kelimeye
dönüşür ve okuyucu her sayfada terimi yeniden öğrenmek zorunda kalır.

Sözlük, Faz 3'te yazılmış TR arayüz metninden **çıkarıldı**, sıfırdan icat edilmedi.
Yerleşmiş kullanım kazanır; çakışma varsa gerekçesi aşağıda yazılı.

### Çekirdek terimler

| İngilizce | Türkçe | Not |
|---|---|---|
| verdict | **yargı** | Meslek ya da görev hakkındaki hüküm. "karar" değil — karar bir eylem, yargı bir hüküm. |
| task | **görev** | |
| entry | **entry** | Çevrilmedi; sitenin kendi birimi ve URL'de de böyle geçiyor. "kayıt" ya da "madde" kavramı daraltırdı. |
| profession / job | **meslek** | `job` teknik bağlamda (dosya, id) çevrilmez. |
| resistance tag | **direnç etiketi** | |
| safe until | **şu yıla kadar güvende** (etiket) · **güvenli olduğu yıl** (tanım) | Aynı kavramın iki yüzü: arayüzde etiket, düzyazıda isim öbeği. |
| community draft | **topluluk taslağı** | |
| source | **kaynak** | |
| evidence | **kanıt** | |
| structural | **yapısal** | Sitenin ana ayrımı: yapısal duvar ≠ geçici yetenek açığı. |
| capability gap | **yetenek açığı** | |
| review / reviewed | **inceleme / incelendi** | |
| changelog | **değişiklikler** | Sayfa slug'ı da `degisiklikler`. |
| horizon | **ufuk** | |

### `safe` — seviyeye göre iki karşılık

Bu bilinçli bir istisnadır, tutarsızlık değil:

| Seviye | İngilizce | Türkçe |
|---|---|---|
| Meslek (verdict) | `safe` | **GÜVENDE** |
| Görev (task) | `safe` | **kalıyor** |

**Neden.** Görev üçlüsü tek bir zamansal eksende durur ve üçü de fiildir:

```
gitti   → görev makineye geçti
gidiyor → geçiş sürüyor
kalıyor → yapısal olarak duruyor
```

`gitti / gidiyor / güvende` bu ekseni kırar: ilk ikisi fiil, üçüncüsü sıfat. Ayrıca
Türkçede güvenlik insana ait bir durumdur — "bu meslek güvende" doğal, "bu görev
güvende" değil. Özne değişince kelime de değişir.

Meslek seviyesinde `GÜVENDE` kalır, çünkü orada özne gerçekten bir insanın mesleğidir.

### Verdict etiketleri (Faz 3'te sabitlendi, değişmez)

| İngilizce | Türkçe |
|---|---|
| SAFE | **GÜVENDE** |
| SHRINKING | **DARALIYOR** |
| ON THE MENU | **SIRADA** |

`ON THE MENU` 16 Ağustos 2026'da `MENÜDE`'den `SIRADA`'ya çevrildi. Gerekçe aşağıda.

## Neden bu sözlük şimdi

4B2 (69 editoryal anahtar) ve 4B3 (entry çevirileri) haftalara yayılacak. Faz 4 planının
risk listesinde bu ikinci sırada: *"69 anahtar ve onlarca entry farklı zamanlarda
yazılırsa 'verdict' üç ayrı kelimeye dönüşür."* Sözlük çeviriden **önce** sabitlenir
kuralı oradan geliyor.

Terim frekansı bu kararı doğruluyor — 69 anahtarın İngilizce metninde: `verdict` 30 kez,
`task` 26, `entry` 12, `job` 12, `review` 10, `profession` 9, `resistance tag` 6,
`structural` 5, `capability` 5, `source` 5, `evidence` 3, `safe until` 3,
`community draft` 2.

## Reddedilen alternatif

**`verdict` → "karar".** Ret sebebi: site bir karar mekanizması değil, bir yargı
yayınlıyor. "Karar" okuyucuya bir eylem çağrısı gibi gelir; `yargı` hükmün arkasında
gerekçe olduğunu ima eder ve sitenin varlık sebebiyle örtüşür.

**`entry` → "madde" / "kayıt".** Ret sebebi: ikisi de ansiklopedi ya da veritabanı
çağrışımı taşıyor. Entry burada bir argümandır, bir kayıt değil.

**`task safe` → "güvende".** Ret sebebi yukarıda: eksen kırılması ve özne uyumsuzluğu.

**`ON THE MENU` → "MENÜDE".** İlk çeviri buydu ve yanlıştı. "On the menu" İngilizcede
bir **tehdit mecazıdır**: yenecekler listesindesin, sıra sende. Türkçede "menüde"
restoran menüsü ya da arayüz menüsü çağrıştırır ve nötr, hatta olumlu okunur —
"menüde var" = sunuluyor, seçilebilir.

Asıl kırılma ölçekteydi. Bu, üç kademenin **en tehlikelisi** (GÜVENDE → DARALIYOR →
?), ama "MENÜDE" tırmanışı hissettirmiyordu; `DARALIYOR`'dan daha hafif duruyordu.
Yani etiket, ölçeğin yönünü tersine çeviriyordu.

`SIRADA` tehdidi ve zaman sırası fikrini birlikte taşıyor, tek kelime, OG kartında
ve tabloda çalışıyor. Ders: **kelime doğru olabilir ama mecaz kayıp olabilir** —
çeviride kontrol edilecek şey kelime değil, okurun ne hissettiği.

## İlgili

Ses kuralları: [[voice]] — ritim, `oneLiner` karşıtlığı ve yasaklı kalıplar Türkçede de
geçerlidir; bu sözlük onların üstüne biner, yerine geçmez.

Çeviri kapsamı: [[2026-08-15-ceviri-kapsami-global-assessment]] — TR entry'leri yargıyı
İngilizceden devralır, düzyazıyı devralmaz.

Plan: `docs/architecture/2026-08-15-cok-dilli-faz-4-plan.md` (Görev 4B2, Adım 3).

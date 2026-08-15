# Verdict Rubriği

İki halka açık otorite var ve bu dosya ikisini de tekrar etmez:

- `CONTRIBUTING.md` — alan tanımları, katkı kuralları
- `/methodology` (`methodology.php`) — `gone`/`going`/`safe` tanımları, tag felsefesi,
  `safeUntil`'in ne iddia ettiği, yayın reddi kuralları

Burada tutulan şey: o kuralların **mevcut 15 entry'de fiilen ürettiği sayılar** —
gözlenen eşikler, gerçek yıl gradyanı, gerçek kaynak barı ve tartışmalı çağrılar.
Rakamsız kural buraya yazılmaz.

## Verdict eşikleri

Mevcut dağılım: 10 `shrinking`, 3 `on-the-menu`, 2 `safe`. Yani `shrinking` varsayılan
kova — bir entry'yi diğer ikisine koymak gerekçe ister.

### `safe` için sert eşik: hiç `gone` task olmayacak

`safe` alan iki entry'de (`nurse`, `teacher`) **sıfır** `gone` task var. Diğer 13'ünün
hepsinde en az bir tane var. İstisnası yok.

Kural: **bir tek `gone` task varsa verdict `safe` olamaz.** Bu, `/methodology`'deki
"task kırılımı kazanır, başlık düzeltilir" ilkesinin sayısal karşılığı.

`safe` "hiçbir şey değişmiyor" demek değil: `nurse` 4, `teacher` 4 task'ı `going`.
Değişen çok şey var, ama hiçbiri henüz tamamen düşmemiş.

### `on-the-menu` ile `shrinking`'i ayıran şey task sayısı DEĞİL

Bu, rubriğin en çok yanlış anlaşılacak yeri. İki entry'nin task dağılımı birebir aynı,
verdict'leri farklı:

| slug | gone | going | safe | verdict |
|------|------|-------|------|---------|
| `copywriter` | 2 | 2 | 3 | 🔴 on-the-menu |
| `graphic-designer` | 2 | 2 | 3 | 🟡 shrinking |

Ayıran şey **hayatta kalanın pazar büyüklüğü**: geriye kalan task'lar aynı sayıda insanı
aynı ücretle çalıştırmaya yetiyor mu?

`on-the-menu` alan üçünün `summary`'si bunu açıkça söylüyor:

> "What is left is smaller and sharper... There are fewer of them." — copywriter

> "The remaining market is real but small and specific." — translator

> "The headcount curve is unambiguous and the entry-level path into service careers is
> closing with it." — customer-support

`graphic-designer`'da ise kalan pazar daralmıyor, ikiye ayrılıyor: "designers with taste
and an argument are more productive than they have ever been."

Kural: **🔴 = kalan işin toplam istihdam kapasitesi düşüyor. 🟡 = iş yeniden dağılıyor
ama kapasite duruyor.** Task saymak bunu vermez; `summary`'yi yazarken cevaplanır.

## `safeUntil` yılı nasıl seçildi

`/methodology` bu sayının ne iddia ettiğini anlatıyor (yetenek + benimseme + regülasyon
gecikmesi). Burada tutulan, o mantığın ürettiği fiili gradyan:

| Yıl | Entry'ler | Ne tür duvar var |
|-----|-----------|------------------|
| 2028 | copywriter, customer-support | Yok — saf metin, dijital teslimat |
| 2029 | graphic-designer, translator | Zayıf: zevk yargısı veya niş sertifikasyon |
| 2030 | data-analyst, photographer, recruiter, video-editor | Kurumsal yargı, kısmi fiziksel |
| 2031 | accountant, marketer | İmza sorumluluğu veya bütçe sahipliği |
| 2032 | software-developer | Operasyonel sorumluluk (3'te uyandırılan kişi) |
| 2033 | lawyer | Lisans + hukuki sorumluluk |
| 2035 | truck-driver | Fiziksel varlık + regülasyon + sigorta |

Kalibrasyon: **her yapısal duvar kabaca +2 yıl.** Duvarsız dijital iş 2028'de başlar;
lisans, fiziksel varlık ve sigorta katmanları üstüne biner.

`safe` entry'lerde yıl yok — `nurse` ve `teacher` boş. Bu bilinçli: yıl vermek "sonunda
düşecek" demektir ve `safe` verdict'iyle çelişir.

Yeni entry için: yılı önce duvar sayısından tahmin et, sonra tabloda komşusuyla karşılaştır.
Komşusundan sapıyorsa gerekçeyi `summary`'de yaz.

## Kanıt standardı

**Taban, istisnasız: `bls.gov` Occupational Outlook Handbook.** 15 entry'nin 15'inde var.
Yeni entry bu bağlantı olmadan gelmez.

İkincil kaynak, gözlenen dört kategori:

| Kategori | Kullanılanlar |
|----------|---------------|
| Çok taraflı kurum | OECD (2), IMF (2), WHO (1) |
| Regülatör | EEOC, FMCSA, ec.europa.eu |
| Meslek örgütü | American Bar Association, AIGA |
| Birincil anket | Stack Overflow Developer Survey, Upwork |

Kabul edilmeyenler: haber makalesi, blog yazısı, satıcı pazarlaması, LinkedIn gönderisi,
"bir rapora göre" diye başlayan ikinci el aktarım.

Sınırda tek örnek: `gartner.com` bir kez geçiyor. Ücretli analist firması, birincil veri
değil — yeni entry'de tercih edilmez, mevcut kullanım korunur.

`/methodology`'nin sözü: kaynağı olmayan entry sayfada **community draft** olarak
etiketlenir. Kaynak eklemek, yapılabilecek en faydalı katkı olarak ilan edilmiş durumda.

## Tartışmalı çağrılar

Sınır vakalarda verilmiş somut kararlar. Yeni bir tartışma çıktığında slug'ıyla buraya eklenir.

**`photographer` neden 🟢 değil?**
`physical-presence` tag'i taşıyor ve `whatSurvives` alanı sitedeki en güçlülerden:
"Images of things that actually happened." Yine de iki task `gone` (stok fotoğrafçılık,
katalog çekimi) — bu, `safe` eşiğini ihlal ediyor. Fiziksel duvar mesleğin *bir kısmını*
koruyor, tamamını değil.

**`translator` neden 🔴, `lawyer` neden 🟡?**
İkisi de `legal-liability` tag'i taşıyor. Fark, duvarın arkasındaki pazarın büyüklüğü:
hukukta korunan iş mesleğin ana gövdesi, çeviride ise azınlığı. `translator` `summary`'si
bunu kabul ediyor: "they were always the minority of the pages." Ayrıca çevirmenlerin
çoğu artık post-edit yapıyor — "a different job wearing the same name."

**`nurse` 4 task'ı `going` olduğu halde neden 🟢?**
Çünkü hiçbiri `gone` değil ve üç duvar birden var (fiziksel, lisans, sorumluluk).
`safe`, "değişim yok" değil, "çekirdek düşmedi" demek.

**`software-developer` neden 2032, `data-analyst` neden 2030?**
İkisi de `human-judgment` + `accountability` taşıyor. Ayıran şey operasyonel sorumluluk:
geliştirici sistem bozulduğunda çağrılan kişi, analist değil.

## Verdict'i değiştirecek şey

Süreç `/methodology`'de yazılı: verdict, haber döngüsü yüksek sesli diye değil, **belirli
bir task durum değiştirdiği için** hareket eder; başlık onu takip eder ve değişiklik
`data/changelog.json`'a kaydedilir.

Bu rubriğe özel olarak, mevcut eşiklerin hangi gözlemle kırılacağı:

- **🟢 → 🟡:** o meslekte ilk task `gone` durumuna geçtiğinde. Sert eşik, tartışmasız.
- **🟡 → 🔴:** kalan task'ların istihdam kapasitesinin düştüğüne dair kanıt geldiğinde —
  ücret verisi, headcount verisi veya meslek örgütü raporu. Bir aracın çıkması yetmez.
- **`safeUntil` erkene çekilir:** duvarın kendisi çöktüğünde (regülasyon değişikliği,
  sigorta modelinin oturması, sertifikasyonun kaldırılması). Model yeteneği yetmez —
  yetenek zaten varsayılan olarak artıyor kabul ediliyor.

## Açık mesele: yayınlanmış tanım bu rubriği üretmiyor

`inc/config.php`'deki verdict blurb'leri iki farklı eksende yazılmış:

- 🟡 `"The role narrows and shifts — it does not vanish."` → **rolün kaderi**
- 🔴 `"The core tasks are becoming machine-doable."` → **task yapılabilirliği**

`copywriter` ve `graphic-designer` birebir aynı task dağılımına sahip (2/2/3) ama farklı
verdict alıyor. Yayınlanmış tanıma göre ikisi aynı olmalıydı; ayıran şey yukarıda yazılı
istihdam kapasitesi kriteri ve o kriter hiçbir yerde yayınlanmamış.

Önerilen çözüm: 🔴 blurb'ünü de role çevirmek — *"The role does not survive at its current
size. A time horizon applies."* Mevcut 15 verdict doğru; kusurlu olan tanım metni.

Aynı durum "tek bir `gone` task varsa `safe` olamaz" eşiği için de geçerli: 15/15 veriyle
doğrulanıyor ama yayınlanmamış. Bir katkıcı bilemez. `CONTRIBUTING.md` veya
`/methodology`'ye taşınmalı.

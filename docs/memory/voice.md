# Ses

Bu dosya entry'lerin **nasıl yazıldığını** tutar. Hangi alanın ne anlama geldiği,
kaç task gerektiği ve `safeUntil`'in tanımı `CONTRIBUTING.md`'de — burada tekrar edilmez.

Buradaki her kural mevcut 15 entry'den çıkarıldı; her birinin altında gerçek bir alıntı ve
slug'ı var. Alıntısız kural eklenmez — alıntısız kural, icat edilmiş kuraldır.

## Tek kural: okuyucu bıraktığı yerde bırakmasın

Bir entry'nin işi bilgi vermek değil, **başlayanı sonuna kadar götürmek**. Aşağıdaki her
kural buna hizmet eder; bir kural momentumu düşürüyorsa yanlıştır ve değiştirilir.

Test: her paragrafın sonunda okuyucunun kapatmamak için bir sebebi var mı? Bir cümle
"bilgi verdim" diyorsa ama merak açmıyorsa, o cümle yerini hak etmiyor.

Bu ilke şunları açıklıyor: `oneLiner`'daki karşıtlık bir kanca, `summary`'nin açılışındaki
yeniden tanımlama merak, kapanışındaki ikinci mertebe sonuç ise bitirmenin ödülü.

## Ritim (ölçüldü)

15 entry'nin `summary` alanı: 79 cümle, ortalama 21 kelime, medyan 22. Asıl sayı bu değil:
**ardışık iki cümle arasındaki ortalama uzunluk farkı 10 kelime.** Ses buradan geliyor.

Kural: **uzun cümleden sonra kısa cümle.** En akılda kalan yerler bu düşüş:

> "Accounting does not disappear. It compresses." — accountant (20 → 4 → 2 kelime)

> "A wedding happened once. A newborn is this size for two weeks. A press photograph is
> worthless if it was generated." — photographer (29 → 9 → 4 → 8 → 9)

**Her `summary` en az bir tane 10 kelimeden kısa cümle taşımalı.** Şu an 15'in 11'inde var.
Taşımayan dördü — `graphic-designer`, `lawyer`, `nurse`, `recruiter` — tamamen 16+ kelimelik
cümlelerden oluşuyor ve okurken düzleşen entry'ler tam olarak bunlar. Sıradaki revizyonda
düzeltilecek.

Üst sınır: 40 kelimeyi geçen cümle yok. Cümlelerin %6'sı 30 kelimenin üstünde; bu tavan.

## Genel ton

**Düz beyan. Hedge yok.** "may", "could", "it is possible that" momentumu kırar; cümle
iddiada bulunur.

**Mekanizmayı adlandır.** Bir şeyin gittiğini söylerken onu ne aldığını yaz: `bank feeds
and OCR` (accountant), `natural-language querying` (data-analyst), `driverless freight
corridors` (truck-driver). "AI is getting better" bir mekanizma değil ve okuyucu orada durur.

**Rakam ve sahne kullan.** `forty options in a minute` (graphic-designer), `three days for
a ticket` (data-analyst), `2am for a few euros a month` (teacher), `ten of them, not a
thousand` (accountant). Soyut paragraf gözle taranır, somut paragraf okunur.

**Rahatsız edici sonucu söyle.** Site adaptasyon rehberi olmak zorunda ama bu kötü haberi
saklamak demek değil — saklanan haber okuyucunun güvenini bitirir:

> "The uncomfortable part is distributional: the job compresses from the bottom."
> — software-developer

**Duygu beyanı entry başına en fazla bir kez, ve hak edilmiş.** 15 entry'de tek örnek var:

> "Translation is the clearest case on this site, and it hurts to write." — translator

Bu bir ritim aracı, süs değil. İkincisini yazarsan ilkini de öldürürsün.

**İmla: İngiliz İngilizcesi.** 15 entry'de istisnasız. `practise` (fiil), `licence` (isim),
`colour`, `-isation` / `-ise`, `artefact`, `favourite`, `rigour`. Amerikan imlası sıfır kez
geçiyor — dışarıdan gelen PR'ın en sık kayacağı yer burası.

## `oneLiner`

Sayfanın kancası. Okuyucunun kalıp kalmayacağı burada belli oluyor.

**14/15'i iki cümlelik karşıtlık.** Birinci cümle kaybedileni ilan eder, ikinci cümle
kalanı olumsuzlama veya karşıtlıkla kurar — açılan boşluk okuyucuyu içeri çeker:

> "The data entry is already gone. The signature and the trust are not." — accountant

> "Writing the code stopped being the hard part. Knowing what to build never was."
> — software-developer

İkinci cümlenin üç bitişi var, üçü de kullanımda:
- **Elips ile olumsuzlama** — fiil düşer: `...are not.` / `...never was.`
- **"is the job" beyanı** — `Knowing which number to ask for is the job.` (data-analyst)
- **Yapamama** — `They cannot attend your wedding.` (photographer)

Birinci cümlenin zamanı bitmişlik taşır: `is already gone`, `is free now`, `stopped being`,
`went to zero`.

Kurallar: asla soru cümlesi (15/15 beyan), asla ünlem, meslek adı geçmez (sayfa zaten o
meslekte), tam iki cümle.

**Tek istisna ve düzeltilecek:** `nurse`. "Every part of this job that a machine could take
was paperwork, and there is a lot of it." Tek cümle, karşıtlık yok, kanca yok — sitedeki en
düz `oneLiner` ve kalıbın neden işe yaradığının kanıtı. Sıradaki revizyonda iki cümleye
çevrilecek.

## `summary`

Üç hamle, 15 entry'de tutarlı:

**1. Kategorik tespitle aç — `oneLiner`'ı tekrar etme.** Tekrar eden açılış, okuyucuya
"bunu okudum" dedirtir ve orada bırakır. En iyileri mesleği yeniden tanımlar:

> "The analyst role was always two jobs pretending to be one: a query-writing service desk,
> and a person who tells the business something it did not know." — data-analyst

**2. Ne düştüğünü mekanizmasıyla anlat.** `graphic-designer`: "A model produces forty
options in a minute, and the client who used to pay for one is now paying for none."

**3. İkinci mertebeden sonuçla kapat.** Bitirmenin ödülü bu — okuyucunun başka yerde
bulamayacağı cümle:

> "A senior who directs three agents is more productive than ever; the junior who used to
> earn their seniority by writing that boilerplate has lost the ladder."
> — software-developer

Uzunluk 4–7 cümle. Em dash serbest ve sık. İkinci tekil şahıs (`you`) seyrek, sahne kurmak
için: "any support page you visit" (customer-support).

## `whatSurvives`

**Tek cümle, ve cümle değil — isim öbeği.** 15/15. "The X will survive" gibi bir yüklem
kurulmaz:

> "Judgment you can be sued for, and a relationship the client will not re-shop every year."
> — accountant

İki kalıp: **gerund ile başlar** (`Deciding what to build...`, `Knowing which question
matters...`) veya **isim listesi** — çoğu üçlü:

> "Hands on a patient, judgment at the bedside, and a registration that a person is
> answerable for." — nurse

Em dash ile kısa açıklama eklenebilir: `Judgment and the argument behind it — generating
options was never the scarce part.` (marketer)

## Task `note` alanları

**İki cümle. Birincisi makinenin ne yaptığı, ikincisi geriye ne kaldığı.**

> "Matching and exception-flagging are largely automated. A human still resolves the
> exceptions, but ten of them, not a thousand." — accountant

İkinci cümle sık sık keskinleştirici bir ters dönüş taşır — task listesini tablo olmaktan
çıkarıp okunur kılan şey bu:

> "Largely automated, and reliably so — right up until the model cheerfully cleans away the
> anomaly that mattered." — data-analyst

`safe` task'ların notu **neden** yapısal olduğunu söyler, "AI henüz iyi değil" demez:

> "A licensed human must sign filings and face the tax authority. No model can hold that
> liability." — accountant

## Yasaklı kalıplar

Mevcut 15 entry'de **hiç görülmeyen** şeyler. Ortak özellikleri: hepsi okuyucuyu düşürür.

- Soru cümlesiyle açılan `oneLiner` veya `summary`
- Ünlem işareti
- "In today's fast-paced world", "As AI continues to evolve", "It's important to note that"
- "revolutionise", "game-changer", "landscape", "leverage" (fiil), "delve" — okuyucu bunları
  gördüğü an metnin makine yazımı olduğuna karar verir ve sekmeyi kapatır
- Madde işaretli `summary` — düz paragraf, liste değil
- "AI is not good enough at this yet" — bu bir tag gerekçesi değil, geri sayımdır
  (bkz. `CONTRIBUTING.md`); task `going` olur ve yıl verilir
- Metin içinde emoji. Verdict emojileri yalnızca arayüzde.
- Amerikan imlası
- Mesleği övmek veya teselli etmek. Entry eli dolu bırakır; bunu `adaptPrompt` ile yapar.

## Türkçe / İngilizce sınırı

Entry içeriğinin tamamı İngilizce yazılır — `titleTr` tek istisna ve yalnızca doğal bir
Türkçe karşılık varsa doldurulur.

Bu klasördeki hafıza dosyaları Türkçe yazılır; alıntılanan entry metni İngilizce
orijinaliyle bırakılır.

---

İlgili: [[docs/memory/README|Hafıza sistemi]] · [[verdict-rubric]]

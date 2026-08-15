---
date: 2026-08-15
title: İçerik tavanı 93 meslek, tempo haftada 5–10
status: active
---

# İçerik hacmi ve tempo

## Karar
Tavan **93 meslek** — `research/occupations.json`'daki evren. Toplu üretim yok.
Tempo haftada 5–10 entry.

Yayın sırası **kanıt gücü × arama hacmi**. Arama hacmi verisi henüz alınmadı
(Keyword Planner, plan satır 370); o iş bitmeden sıralama tahmine dayanır.

Kanıt durumu (2026-08-15): `live` 15 · `queued` 21 · `backlog` 57.
Kanıt gücü: `strong` 25 · `moderate` 31 · `thin` 18 · `none` 19.

**Yayınlanabilir olan 56** (`strong` + `moderate`). `thin` olanlar önce kanıt ister.
`none` olanlar çıkmaz — çıkarsa `/methodology`'de söz verildiği gibi *community draft*
etiketiyle çıkar.

## Neden
Soru "günde kaç sayfa girebiliriz" diye soruldu. LLM ile 1.000 entry teknik olarak
mümkün. Üç ayrı sınır bunu engelliyor ve hiçbiri "günlük kota" değil:

**1. Google'ın scaled content abuse politikası.** Çok sayıda sayfayı esas olarak
sıralama için üretmek yaptırım sebebi — AI yazmış ya da insan yazmış fark etmiyor.
Tetikleyici hacim değil, özgün değer yokluğu. Risk asimetrik: yavaş yayınlamanın
maliyeti zaman, site geneli kalite düşürmesinden çıkmak aylar.

**2. Crawl bütçesi.** Yeni domain yavaş taranır. 1.000 sayfa basmak indekslenmeyi
hızlandırmıyor; darboğaz yayınlama hızı değil, indekslenme hızı.

**3. Asıl sınır: farklılaştırıcının kendisi.** `llms.php` dünyaya şunu söylüyor:
*"Most 'will AI replace X' content judges a job title and stops. This site does not."*
Bu sitenin değeri task seviyesinde yargı, kaynak ve review tarihi. Gerçek yargı olmadan
üretilen 1.000 entry, bizi scaled content abuse'tan ayıran tam o şeyi yok eder. Yani
yaptırım riskini artıran hamle aynı anda ürünü de öldürüyor.

Gerçek tavan Google'ın koyduğu bir sayı değil: **kaç mesleği savunabilecek kadar
biliyoruz.** Şu an 56.

## Yan kural
Mevcut entry'yi güncellemek, zayıf yeni entry eklemekten daha değerli. `lastReviewed`
ve changelog altyapısı tazelik sinyali üretiyor; bu bedavaya gelen bir avantaj ve
rakiplerin çoğunda yok.

## Ertelenen fikir
"AI'ın yarattığı meslekler" sayfası — Faz 2. Naif hâli `llms.php`'deki *"Verdicts are
arguments with visible reasoning, **not predictions**"* sözüyle doğrudan çelişir ve
tahmin listeleri görünür şekilde yanlış çıkar ("prompt engineer", 2023).

İşe yarayan versiyonu: "kanıtı olan yeni roller" — gerçek iş ilanı sayısı, maaş verisi,
headcount. Sitenin geri kalanıyla aynı kanıt standardı. O zaman tahmin listesi değil,
kimsede olmayan bir ölçüm olur.

Daha ucuz bir ikinci versiyon: mevcut entry'ler zaten her meslek için "ne hayatta
kalıyor" diyor. Bunları toplayan bir sayfa yeni kanıt gerektirmez ve hiç tahmin
içermez — `/landscape` yanına oturur.

## İlgili
[[2026-08-15-konumlandirma-adaptasyon]] · [[2026-08-15-sponsor-tiklama-sunucu-tarafi]]

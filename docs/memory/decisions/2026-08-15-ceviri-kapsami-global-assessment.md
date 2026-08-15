---
date: 2026-08-15
title: Çeviri kapsamı — TR/ES v1'de global assessment
status: active
---

# Çeviri kapsamı: TR ve ES v1'de global assessment

## Karar

**1. TR ve ES v1 değerlendirmeleri İngilizceden devralınan global assessment'tır.**
Yargı — `verdict`, `safeUntil`, `resistanceTags`, `sources`, `evidenceStrength` ve
görev yargıları — `en.json`'dan devralınır. Düzyazı devralınmaz: başlık, özet, görev
metinleri ve adapt prompt her dilde ayrıca yazılır. Sayfada bunun küresel bir
değerlendirme olduğunu söyleyen görünür bir not basılır.

*Kaynak durumu:* devralınan kaynaklar İngilizce entry'nin kaynaklarıdır. Global
assessment modundayken bir dil dosyası **kendi yerel kaynaklarını taşımaz**; şema
bunu engeller. Yerel moda geçen dosya kendi kaynaklarını taşır ve taşımak zorundadır.

**2. `es` tek bir ülke ya da pazar varsayımı değildir.**
İspanyolca içerik genel dil kapsamındadır. İspanya, Meksika, Arjantin ve Kolombiya
tek bir iş piyasası olarak ele alınamaz; ülke seçilmeden yerel verdict üretilmez.

*Kaynak durumu:* v1 global ES içeriğinde ülkeye özgü kaynak yok ve beklenmiyor.
Yerel değerlendirmeye geçilecekse önce **hangi ülke** sorusu cevaplanır; o noktadan
sonra kaynak beklentisi o ülkenin kurumlarına göre belirlenir.

**3. `cashier` için Türkiye'ye özgü yerel değerlendirme araştırması beklemektedir.**
Tamamlanana kadar global verdict geçerlidir.

*Kaynak durumu:* **eksik.** Türkiye'de kasiyer istihdamının yüksek olması tek başına
yetmez; otomasyona rağmen kalan işin neden yaklaşık aynı istihdam kapasitesini
koruduğunun gösterilmesi gerekir. En az bir yetkili yerel kaynak şart — mevzuat
iddiasını mevzuat metni, istihdam iddiasını istatistik kurumu taşır.

## Neden

Bir dilin kendi verdict'ini taşıması, o pazar için gerçek araştırma yapıldığı
anlamına gelir. Çeviri bunu üretmez. İngilizce değerlendirmeyi Türkçeye kopyalayıp
"Türkiye değerlendirmesi" gibi sunmak, sitenin tek varlık sebebine — yargının
arkasında görünür bir gerekçe olması — aykırı olurdu.

Şema bu ayrımı zorunlu kılıyor: kaynak olmayan bir dil dosyası yargı alanı
**yazamaz**, validator hata verir. Yani yanlışlıkla yerel görünen bir çeviri
üretmek mümkün değil. Yerel değerlendirmeye geçiş bilinçli bir eylemdir:
`assessmentScope: "local"`, kendi `assessmentSourceLocale`'i ve kendi kaynakları.

## Reddedilen alternatif

**TR/ES'i ülke bazlı yerel verdict ile açmak.** Ret sebebi hız değil, kanıt: 17
mesleğin her biri için Türkiye ve seçilecek bir İspanyolca konuşan ülke adına
mevzuat, istihdam ve benimseme kanıtı toplamak, mevcut İngilizce külliyatı bir kez
daha yazmak demekti. Kanıt olmadan verilen yerel verdict, İngilizce verdict'in
Türkçe kopyasından daha kötüdür: yerel görünür ama yerel değildir.

> Bu not, `data/i18n/README.md` içindeki "V1 kararı" ve "Bekleyen araştırma işi"
> bölümlerinden damıtıldı (15 Ağustos 2026). O klasör geçici bir hazırlık alanıdır ve
> Faz 2 migration tamamlandığında kaldırılacak; kararların kendisi burada yaşamaya
> devam edecek, kaynak metin git geçmişinde korunacak.

## İlgili

Şema ve kurallar: `docs/architecture/2026-08-15-cok-dilli-mimari.md` (§3 sahiplik ve
devralma, §5.5 küresel değerlendirme notu, §7.1 yerel kaynak standardı).

[[2026-08-15-konumlandirma-adaptasyon]] · [[2026-08-15-icerik-tavani-93]]

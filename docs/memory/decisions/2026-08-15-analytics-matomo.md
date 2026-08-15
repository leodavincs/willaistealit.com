---
date: 2026-08-15
title: Analytics — kendi sunucumuzda Matomo
status: active
---

# Analytics aracı: Matomo, self-host

## Karar
`stats.willaistealit.com` altında kendi sunucumuzda Matomo 5. Site ID `1`,
`inc/config.php` içinde — gizli bir değer değil, o yüzden repoda. Lokal geliştirmenin
kendi trafiğini ölçmesini engelleyen şey bu sabit değil, `inc/functions.php`
içindeki `is_live_host()` kontrolü.

Çerezsiz çalışıyor: `disableCookies`, `trackPageView`'den **önce** push ediliyor.
Sıra bozulursa ilk istekte çerez yazılır ve çerez onay banner'ı gerekliliği geri gelir.

## Neden
Karar üç kritere göre verildi: ücretsiz olması, custom event desteği, ve **veri
saklama süresi**.

| Araç | Elenme sebebi |
|------|---------------|
| Google Analytics 4 | Çerez yazar → onay banner'ı zorunlu. ~50 KB script. Reklam engelleyiciler yüksek oranda engelliyor |
| Cloudflare Web Analytics | Ücretsiz ve temiz, ama **custom event yok** — prompt kopyalama sayılamaz |
| Plausible | Teknik olarak tam uyuyor, ama ücretli |
| Umami Cloud | Ücretsiz katmanda **6 ay veri saklama** |

Umami Cloud bir ara önerildi ve geri alındı. Ücretsiz + event + banner yok üçlüsünü
tek başına veren seçenekti, ama saklama sınırı 6 ay. Ölçüm Faz 1'de kuruluyor ki veri
biriksin; 6 ay sonra en eski verinin silinmeye başlaması, biriktirmenin amacını ortadan
kaldırıyor.

Matomo'nun bu planda ayrıca avantajı var: Hostinger Premium zaten PHP 8.3 + MySQL,
yani Matomo'nun istediği yığının aynısı. Umami self-host Node.js + kalıcı süreç +
tercihen PostgreSQL istiyor — paylaşımlı hostingde kırılgan.

Kendi domaininden servis edildiği için reklam engelleyiciler pratikte engelleyemiyor;
rakamlar üçüncü taraf araçlardan daha gerçeğe yakın.

## Bedeli
Kabul edilen takas: `CLAUDE.md`'nin "veritabanı yok" ilkesi kırılıyor. Hesaba bir MySQL
veritabanı (`u359064650_matomo`, plan limiti **3 GB**) ve ikinci bir PHP uygulaması
girdi. Güvenlik güncellemeleri artık bizim sorumluluğumuzda.

3 GB sınırı Matomo'nun kendi ayarıyla yönetiliyor: ham ziyaretçi kayıtları belirli bir
süre sonra silinir, **toplulaştırılmış raporlar sonsuza kadar kalır**. Umami Cloud'un
6 ay kısıtında imkânsız olan şey tam olarak buydu.

## Kurulum tuzağı
Hostinger bu planda subdomain kökünü `public_html` dışına almıyor — API sadece düz
dizin adı kabul ediyor, `../` reddediyor. Matomo `public_html/stats_matomo/` altında
ve apex'ten de görünür durumdaydı. `stats_matomo/.htaccess` içindeki host kontrolü
bunu 404'e düşürüyor. **Bu dosya repoda değil ve Matomo güncellemesinde kaybolabilir**
— kontrolü `DEPLOY.md` taşıyor.

## Reddedilen alternatif
Launch'ta hiç analytics kurmamak, trafik gelince karar vermek. Bu bir ara önerildi ve
geri alındı: ölçümün Faz 1'de kurulmasının sebebi mevcut trafiği görmek değil, veri
biriktirmek. Ertelemek biriken veriyi yok eder.

## İlgili
[[2026-08-15-sponsor-tiklama-sunucu-tarafi]] · [[2026-08-15-hosting-hostinger]] · [[2026-08-15-stack-php-json]]

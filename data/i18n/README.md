# data/i18n/ — hazırlanmış çeviriler, HENÜZ YAYINDA DEĞİL

Site bugün hâlâ `data/jobs/<slug>.json` düz dosyasını ve tek locale'i okuyor.
Bu klasör, `docs/architecture/2026-08-15-cok-dilli-mimari.md` uygulanana kadar TR ve ES
içeriğini **spec'in şemasıyla** bekletir.

`tools/validate.php` ve `tools/build-index.php` yalnızca `data/jobs/*.json` üzerinde
çalışır; buradaki hiçbir dosya doğrulamadan geçmez ve hiçbir sayfaya basılmaz.

## Neden data/jobs/<slug>/ altında değil

Spec'in hedef yerleşimi `data/jobs/<slug>/{common,en,tr,es}.json`. Bu dosyalar oraya
şimdi konsaydı `en.json` henüz taşınmadığı için yarı göçmüş bir dizin oluşur ve
göç aracının `--verify` adımı (eski düz dosya ile yeni dizini karşılaştırıyor)
karşılaştıracak sağlam bir çift bulamazdı. Göç sırasında yapılacak iş tek satır:

```
mv data/i18n/<slug>/{common,tr,es}.json data/jobs/<slug>/
```

## Şemaya uygunluk

Dosyalar spec §2.2 ve §3.1'e göre yazıldı:

- Görevler dizi değil, `common.json`'daki `taskOrder` id'leriyle anahtarlanmış nesne
- `assessmentScope: "global"` olduğu için devralınan alanların **hiçbiri yazılmadı**:
  `verdict`, `safeUntil`, `resistanceTags`, `sources`, `evidenceStrength`,
  `tasks[].verdict`, `tasks[].tags`
- Küresel değerlendirme notu (§5.5) elle yazılmadı — otomatik eklenecek
- Her dilin kendi `slug`'ı var: `idari-asistan`, `kasiyer`, `asistente-administrativo`, `cajero`
- `aliases` değil `aka`

## V1 kararı

TR ve ES **global assessment** olarak yayınlanır: yargı İngilizce değerlendirmeden
devralınır, düzyazı devralınmaz. Yerel verdict yalnızca o pazar için gerçek araştırma
tamamlanırsa açılır (`assessmentScope: "local"`, §3.3 ve §7.1).

`es` bir pazar değildir. İspanya, Meksika, Arjantin ve Kolombiya tek bir iş piyasası
olarak ele alınamaz; ülke seçmeden yerel verdict üretilmez.

## Bekleyen araştırma işi

**cashier / TR yerel override.** Türkiye'de kasiyer istihdamının yüksek olması tek
başına yetmez; otomasyona rağmen kalan işin neden yaklaşık aynı istihdam kapasitesini
koruduğunun gösterilmesi gerekir. §7.1 gereği en az bir yetkili yerel kaynak şart.

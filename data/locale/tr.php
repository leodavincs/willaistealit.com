<?php
/**
 * Turkce metin tablosu.
 * Uretilen cumleler, eklenen metne ek getirmeyecek sekilde kuruldu: "%s'i devraldi"
 * yerine "sunlari devraldi: %s". Boylece unlu uyumu ve hal eki sorunu dogmuyor.
 */
declare(strict_types=1);

return [
    'site.tagline' => 'Yapay zekânın hangi mesleklerin hangi görevlerini aldığına dair görev seviyesinde yargılar.',

    // --- verdict ---
    'verdict.safe.label'        => 'GÜVENDE',
    'verdict.safe.blurb'        => 'Bu işin çekirdeği yapısal olarak dirençli. Yapay zekâ bir araç oluyor, ikame değil.',
    'verdict.shrinking.label'   => 'DARALIYOR',
    'verdict.shrinking.blurb'   => 'Önemli parçalar otomatikleşiyor. Rol daralıyor ve yer değiştiriyor — yok olmuyor.',
    'verdict.on-the-menu.label' => 'MENÜDE',
    'verdict.on-the-menu.blurb' => 'Çekirdek görevler gidiyor ve geriye kalan iş aynı sayıda insanı taşımayacak. Bir zaman ufku geçerli.',

    // --- gorev verdict'leri ---
    'task.gone.label'  => 'gitti',
    'task.going.label' => 'gidiyor',
    'task.safe.label'  => 'kalıyor',

    // --- direnc tag tanimlari ---
    'tag.physical-presence'  => 'Fiziksel dünyada eller ve bir beden gerekiyor.',
    'tag.legal-liability'    => 'Sonucun hukuki sahibi bir insan olmak ve altına imza atmak zorunda.',
    'tag.regulated'          => 'Önünde bir lisans, ruhsat ya da yasal duvar var.',
    'tag.trust-relationship' => 'Değer, çıktının kendisi değil, kişisel güven ilişkisi.',
    'tag.human-judgment'     => 'Belirsizlik altında bağlamsal kararlar — kimse bunu devretmek istemiyor.',
    'tag.creative-taste'     => 'Estetik yargı: yapay zekâ üretebilir, seçemez.',
    'tag.accountability'     => '"Bu yanlış çıkarsa kim sorumlu" sorusu bir insan istiyor.',
    'tag.physical-context'   => 'Sahada, o odada, o anda bulunmak gerekiyor.',
    'tag.emotional-labor'    => 'Duygusal emeğin kendisi işin ta kendisi.',

    // --- kategoriler ---
    'category.tech'      => 'Teknoloji ve Mühendislik',
    'category.finance'   => 'Finans ve Muhasebe',
    'category.legal'     => 'Hukuk',
    'category.health'    => 'Sağlık ve Bakım',
    'category.education' => 'Eğitim',
    'category.creative'  => 'Medya ve Yaratıcı İşler',
    'category.trades'    => 'Zanaat ve Saha İşleri',
    'category.service'   => 'Satış ve Hizmet',
    'category.ops'       => 'Operasyon ve İdari İşler',
    'category.unknown'   => 'Sınıflandırılmamış',

    // --- ay adlari ---
    'month.1'  => 'Ocak',    'month.2'  => 'Şubat',   'month.3'  => 'Mart',
    'month.4'  => 'Nisan',   'month.5'  => 'Mayıs',   'month.6'  => 'Haziran',
    'month.7'  => 'Temmuz',  'month.8'  => 'Ağustos', 'month.9'  => 'Eylül',
    'month.10' => 'Ekim',    'month.11' => 'Kasım',   'month.12' => 'Aralık',
    'month.format' => '%s %s',

    'list.and' => 've',

    // --- kanit notu ---
    'evidence.draft.label' => 'Topluluk taslağı',
    'evidence.draft.text'  => 'Bu entry’ye henüz kanıt eklenmedi. Argüman yine de doğru olabilir ama kimse onu bir kaynakla desteklemedi. Kaynak eklemek, yapabileceğiniz en faydalı katkı.',
    'evidence.thin.label'  => 'Zayıf kanıt',
    'evidence.thin.text'   => 'Bu yargı sınırlı yayınlanmış kanıta dayanıyor. Savunacağımız bir argüman, ama sahip olduğundan daha fazla kaynağı hak ediyor — daha iyi veri biliyorsanız bir PR açın.',

    // --- GEO paragrafi ---
    'geo.prefix'                  => '%s itibarıyla %s',
    'geo.verdict.safe'            => '%s mesleğinin yerini yapay zekâ almıyor.',
    'geo.verdict.onthemenu'       => '%s işinin çekirdek görevleri makineye devroluyor%s.',
    'geo.verdict.onthemenu.until' => ' ve bu geçişin yaklaşık %s yılında tamamlanması bekleniyor',
    'geo.verdict.shrinking'       => '%s rolü yok olmuyor, daralıyor%s.',
    'geo.verdict.shrinking.until' => ' ve çekirdek yaklaşık %s yılına kadar incelmeye devam ediyor',
    'geo.gone'                    => ' Yapay zekâ şu görevleri şimdiden devraldı: %s.',
    'geo.safe'                    => ' Direnen kısım: %s.',
    'geo.resistance'              => ' Yapısal sebep: %s.',
    'geo.fallbackDate'            => 'Ağustos 2026',

    // --- FAQ ---
    'faq.replace.q'    => 'Yapay zekâ %s mesleğinin yerini alacak mı?',
    'faq.howLong.q'    => '%s işi yapay zekâya karşı ne kadar güvende?',
    'faq.howLong.a'    => 'Tahminimiz yaklaşık %s. Bu, işin çekirdek görevlerinin olağan uygulamada rutin olarak makineyle yapılır hâle geleceği yıl — yetenek geldikten, işverenler benimsedikten ve düzenleyiciler izin verdikten sonra. Meslek adının ortadan kalkacağı yıl değil. Güncel yargı: %s.',
    'faq.whichTasks.q' => 'Yapay zekâ %s mesleğinin hangi görevlerini şimdiden yapıyor?',
    'faq.whichTasks.a' => '%s. Her biri ayrı ayrı değerlendiriliyor; tüm meslek tek bir cevaba indirgenmiyor.',
    'faq.whatSafe.q'   => '%s işinin hangi kısmı yapay zekâdan güvende?',
    'faq.howUse.q'     => '%s yapay zekâyla rekabet etmek yerine onu nasıl kullanmalı?',
    'faq.howUse.a'     => 'Zaten gitti ya da gidiyor olarak işaretlenmiş görevlerde kullanın, yargıyı elinizde tutun. Bu mesleğe özel, kopyalayıp kullanabileceğiniz bir prompt burada: %s.',

    'share.safeUntil' => ' — ~%s yılına kadar güvende',
    // --- navigasyon ve footer (Faz 3F) ---
    'nav.skip'               => 'İçeriğe geç',
    'nav.timeline'           => 'Zaman Çizelgesi',
    'nav.methodology'        => 'Metodoloji',
    'nav.changelog'          => 'Değişiklikler',
    'nav.sponsor'            => 'Sponsorluk',
    'nav.github'             => 'GitHub',
    'nav.theme.aria'         => 'Açık ve koyu tema arasında geçiş yap',
    'nav.theme.title'        => 'Açık / koyu',
    'foot.lead'              => 'Buradaki her yargı bir argümandır, kehanet değil.',
    'foot.disagree'          => 'Katılmıyor musunuz?',
    'foot.openPr'            => 'PR açın',
    'foot.allJobs'           => 'Tüm meslekler',
    'foot.howWeDecide'       => 'Nasıl karar veriyoruz',
    'foot.contribute'        => 'Katkıda bulunun',
    'foot.fine'              => 'Sponsorluk hiçbir yargıya dokunmaz.',
    'foot.readRule'          => 'Kuralı okuyun',
];

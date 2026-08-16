<?php
/**
 * Turkce metin tablosu.
 * Uretilen cumleler, eklenen metne ek getirmeyecek sekilde kuruldu: "%s'i devraldi"
 * yerine "sunlari devraldi: %s". Boylece unlu uyumu ve hal eki sorunu dogmuyor.
 */
declare(strict_types=1);

return [
    'site.tagline' => 'Yapay zekânın hangi mesleklerin hangi görevlerini aldığına dair görev seviyesinde yargılar.',
    'site.ogLocale' => 'tr_TR',
    // --- OG karti ---
    // Dogrudan BUYUK harfle yaziliyor: mb_strtoupper'a birakilirsa Turkce 'i' bozulur.
    'og.home.sub'   => 'Yapay zekânın hangi mesleklerin hangi görevlerini aldığına — ve geriye ne kaldığına dair görev seviyesinde yargılar.',
    'og.survives'   => 'NE KALIYOR',

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
    'nav.language'           => 'Dil',
    'nav.soon'               => 'yakında',
    // Dil adlari kendi dilinde — uc tabloda da AYNI.
    'lang.en'                => 'English',
    'lang.tr'                => 'Türkçe',
    'lang.es'                => 'Español',
    'entry.staleTranslation' => 'Bu çeviri, son değerlendirme incelemesinden eski.',
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
    // --- entry sayfasi (job.php, Faz 3F) ---
    'job.pageTitle'              => 'Yapay zekâ %s mesleğinin yerini alacak mı? — %s %s',
    'job.h1'                     => 'Yapay zekâ %s mesleğinin yerini alacak mı?',
    'job.allJobs'                => 'Tüm meslekler',
    'job.lastReviewed'           => 'Son inceleme:',
    'job.safeUntilLabel'         => 'şu yıla kadar güvende',
    'job.longerVersion'          => 'Uzun hâli',
    'job.taskBreakdown'          => 'Görev kırılımı',
    'job.taskBreakdown.note'     => 'Bütün bir mesleğe verilen yargı slogandır. Argüman burada yaşar.',
    'job.resists'                => 'Geri kalan neden direniyor',
    'job.howWeDecide'            => 'Nasıl karar veriyoruz',
    'job.adapt.title'            => 'O sizi kullanmadan siz onu kullanın',
    'job.adapt.note'             => 'Bunu Claude ya da ChatGPT\'ye yapıştırın, bugün başlayın.',
    'job.adapt.label'            => 'adapt prompt',
    'job.adapt.copy'             => 'Prompt\'u kopyala',
    'job.share.title'            => 'Yargıyı paylaş',
    'job.share.until'            => '~%s yılına kadar güvende',
    'job.share.survives'         => 'Ne kalıyor:',
    'job.share.postX'            => 'X\'te paylaş',
    'job.share.copyLink'         => 'Bağlantıyı kopyala',
    'job.share.openImage'        => 'Paylaşım görselini aç',
    'job.share.hint'             => 'Yukarıdaki görsel bu sayfa için üretiliyor ve bağlantıyı yapıştırdığınızda kendiliğinden görünüyor.',
    'job.receipts'               => 'Kanıtlar',
    'job.meta.verdict'           => 'Yargı',
    'job.meta.category'          => 'Kategori',
    'job.meta.safeUntil'         => 'Şu yıla kadar güvende',
    'job.meta.evidence'          => 'Kanıt',
    'job.meta.lastReviewed'      => 'Son inceleme',
    'job.meta.unknown'           => 'bilinmiyor',
    'job.sources'                => 'Kaynaklar',
    'job.faq'                    => 'Doğrudan cevaplar',
    'job.disagree.title'         => 'Bu yargı yanlış mı sizce?',
    'job.disagree.text'          => 'Güzel — mesele tam olarak bu. Her entry tek bir JSON dosyası. Değiştirin, PR\'da savunun; argüman tutarsa yargı değişir.',
    'job.disagree.edit'          => 'Bu entry\'yi düzenle',
    'job.disagree.method'        => 'Metodolojiyi oku',
    'job.related.title'          => 'Aynı fay hattındaki meslekler',
    'job.related.note'           => 'Aynı kategori ya da hayatta kalmak için aynı sebep.',
    // --- sabit sayfalar: 404, unavailable, changelog (Faz 3F) ---
    'page.404.title'               => 'Bulunamadı',
    'page.404.desc'                => 'Bu adreste henüz bir yargı yok.',
    'page.404.h1'                  => '404',
    'page.404.text'                => 'Bu adreste bir yargı yok. Ya meslek henüz burada değil — ya da çoktan çalındı.',
    'page.404.cta'                 => 'Bütün yargılara göz at',
    'page.unavailable.title'       => 'Bu dilde henüz yok',
    'page.unavailable.desc'        => 'Bu entry henüz bu dilde yayınlanmadı.',
    'page.unavailable.text'        => 'Bu entry var, ama bu dilde yazılmadı. İngilizcesini okuyabilir ya da çeviriyi eklemek için bir PR açabilirsiniz.',
    'page.unavailable.availableIn' => 'Bu içerik şu dillerde var:',
    'page.changelog.title'         => 'Yargı değişiklikleri',
    'page.changelog.desc'          => 'willaistealit.com\'daki her yargı değişikliği, tarihiyle ve taşınma sebebiyle. Kendi tahminlerini sessizce yeniden yazan bir site okunmaya değmez.',
    'page.changelog.lede'          => 'Yer değiştiren her yargı, ne zaman ve neden değiştiği. Kendi tahminlerini sessizce yeniden yazan bir site okunmaya değmez.',
    'page.changelog.empty'         => 'Henüz hiçbir şey değişmedi. Bir model çıkışı ya da bir düzenleme gerçekten bir görev kırılımını değiştirdiğinde buraya kaydedilir.',
    'page.changelog.how'           => 'Bir yargı nasıl değişir',
    'page.changelog.howText'       => 'Haber gürültülü olduğu için değil. Bir yargı, kırılımdaki belirli bir görev gerçekten durum değiştirdiğinde değişir — onu yapan bir araç çıkar ya da bir düzenleyici kimin yapabileceğine karar verir. Önce görev hareket eder, başlık onu takip eder.',
    'page.changelog.rules'         => 'Kuralların tamamı burada.',
    // --- ana sayfa (index.php, Faz 3F) ---
    'home.pageTitle'           => 'Yapay zekâ sizin mesleğinizi alacak mı? — %d %s için görev seviyesinde yargılar',
    'home.profession'          => 'meslek',
    'home.professions'         => 'meslek',
    'home.pageDesc'            => '%s Liste yazısı değil: her meslek gerçek görevlerine bölünüyor, her görev ayrı yargılanıyor ve bugün kullanabileceğiniz bir prompt veriliyor.',
    'home.answer'              => '%s itibarıyla willaistealit.com %d %s için görev seviyesinde yapay zekâ yargısı yayınlıyor: %d güvende, %d daralıyor, %d menüde. Her meslek gerçek görevlerine bölünüyor ve her görev ayrı yargılanıyor; çünkü yapay zekâ meslekleri değil, görevleri alıyor.',
    'home.faq.which.q'         => 'Yapay zekâ hangi mesleklerin yerini alacak?',
    'home.faq.how.q'           => 'Bu yapay zekâ meslek yargıları nasıl belirleniyor?',
    'home.faq.how.a'           => 'Her meslek 4-8 somut göreve bölünüyor. Her görev ayrı ayrı gitti, gidiyor ya da kalıyor olarak yargılanıyor ve hayatta kalan her görev direnmesinin yapısal sebebini adlandırmak zorunda — hukuki sorumluluk, fiziksel varlık, regülasyon, güven, insan yargısı, yaratıcı zevk, hesap verebilirlik ya da duygusal emek. Ancak bundan sonra bir başlık yargısı veriliyor ve o yargı asla tek başına durmuyor. Kuralların tamamı: %s',
    'home.faq.until.q'         => 'Bu sitede "şu yıla kadar güvende" ne demek?',
    'home.faq.until.a'         => 'Bir mesleğin çekirdek görevlerinin olağan uygulamada rutin olarak makineyle yapılır hâle gelmesinin beklendiği yıl; yeteneğin gelmesi, kurumların benimsemesi ve düzenleyicilerin izin vermesi hesaba katılarak. Meslek adının ortadan kalkacağı yıl değil.',
    'home.h1'                  => 'Yapay zekâ bunu çalar mı?',
    'home.lede'                => 'Her meslek gerçek görevlerine bölünmüş, her görev kendi başına yargılanmış. Kendinizinkini bulun.',
    'home.searchLabel'         => 'Meslek ara',
    'home.searchHint'          => 'Bir meslek arayın — ',
    'home.searchExample'       => 'muhasebeci',
    'home.distribution'        => 'Yargı dağılımı',
    'home.barAria'             => '%d daralıyor, %d menüde, %d güvende; toplam %d meslek',
    'home.total'               => 'toplam',
    'home.timeline'            => 'Zaman çizelgesini gör',
    'home.filters'             => 'Filtreler',
    'home.verdict'             => 'Yargı',
    'home.all'                 => 'Hepsi',
    'home.category'            => 'Kategori',
    'home.allCategories'       => 'Tüm kategoriler',
    'home.clear'               => 'Filtreleri temizle',
    'home.col.job'             => 'Meslek',
    'home.col.until'           => 'Ne zamana kadar',
    'home.col.survives'        => 'Ne kalıyor',
    'home.noHorizon'           => 'ufuk yok',
    'home.empty'               => 'Bu adda bir meslek henüz yok.',
    'home.addIt'               => 'Ekleyin',
    'home.oneFile'             => '— tek bir JSON dosyası.',
    'home.noEntries'           => 'Henüz entry yok. <code>data/jobs/&lt;id&gt;/</code> altına bir dizin ekleyin ve <code>php tools/build-index.php</code> çalıştırın.',
    // --- /methodology (editoryal: yargi tanimlarini HALKA ACIK yayinlar) ---
    // Terminoloji: docs/memory/decisions/2026-08-16-tr-terminoloji-sozlugu.md
    'methodology.pageTitle'                => 'Nasıl karar veriyoruz',
    'methodology.pageDesc'                 => 'willaistealit.com üzerindeki her yargının arkasındaki kurallar: görev seviyesinde analiz, direnç etiketleri ve fikrimizi değiştirecek şey.',
    'methodology.lede'                     => 'Savunamadığınız bir yargının değeri yoktur. Her birinin tam olarak nasıl kurulduğu ve neyin onu değiştireceği burada.',

    'methodology.oneRule.h'                => 'Tek kural',
    'methodology.oneRule.p'                => '<strong>Meslekler atom değil. Görevler atom.</strong> Kimsenin yerini yapay zekâ almıyor — görevlerin yeri alınıyor. Bu yüzden hiçbir zaman meslek adından başlamıyoruz. İşi 4–8 gerçek göreve bölüyoruz, her birini ayrı ayrı yargılıyoruz, ancak ondan sonra tek bir başlık yargısında topluyoruz. Başlık, altındaki görev dökümüyle çelişirse döküm kazanır ve başlık düzeltilir.',

    'methodology.taskVerdicts.h'           => 'Görev seviyesinde yargılar',
    'methodology.taskVerdicts.gone'        => '<strong>gitti</strong> — bugün işini iyi yapan biri bunu zaten yazılıma devrediyor. "Teoride devredebilir" değil: fiilen devrediyor.',
    'methodology.taskVerdicts.going'       => '<strong>gidiyor</strong> — ilk taslağı makine yapıyor; insan gözden geçiriyor, düzeltiyor ve sahipleniyor. Saatler çöküyor, görev küçülmüş hâliyle kalıyor.',
    'methodology.taskVerdicts.safe'        => '<strong>kalıyor</strong> — geçici bir yetenek açığı değil, yapısal bir sebep engelliyor. <code>kalıyor</code> olan her görev en az bir direnç etiketi göstermek zorunda.',

    'methodology.verdicts.h'               => 'Üç yargı',
    'methodology.verdicts.col1'            => 'Yargı',
    'methodology.verdicts.col2'            => 'Ne demek',
    'methodology.verdicts.note'            => '<strong>Bir yargı asla tek başına durmaz.</strong> Yanında her zaman görev dökümü, direnç etiketleri ve — <code>güvende</code> olmayan her şey için — bir yıl gelir. Bunları yazamıyorsak entry\'yi yayınlamıyoruz.',

    'methodology.tags.h'                   => 'Direnç etiketleri',
    'methodology.tags.col1'                => 'Etiket',
    'methodology.tags.col2'                => 'Duvar',
    'methodology.tags.p'                   => 'Soru hiçbir zaman "yapay zekâ yeterince akıllı mı?" değil — soru şu: <strong>"yeterince akıllı olsa bile, bunu almasını yapısal olarak ne engelliyor?"</strong> Yetenek açıkları kapanır. Yapısal duvarlar kapanmaz, ya da çok daha yavaş kapanır. Tanıdığımız duvarlar şunlar:',
    'methodology.tags.note'                => 'Bir entry 1–3 etiket taşır, en güçlüsü başta. Bir mesleğin ayakta kalması için tek argüman "yapay zekâ henüz yeterince iyi değil" ise bu bir etiket değildir — bu bir geri sayımdır, ve entry etiket yerine bir yıl alır.',

    'methodology.until.h'                  => '"Şu yıla kadar güvende" yılı',
    'methodology.until.p1'                 => 'Sitedeki en kışkırtıcı sayı bu, o yüzden gerçekte ne iddia ettiğini yazalım: <strong>bu mesleğin çekirdek görevlerinin olağan uygulamada rutin olarak makineyle yapılır hâle gelmesini beklediğimiz yıl</strong> — meslek adının ortadan kalkacağı yıl değil, teknolojinin bir demoda ilk kez mümkün olduğu yıl da değil.',
    'methodology.until.p2'                 => 'İnsanların unuttuğu üç gecikmeyi hesaba katıyor: yeteneğin gelmesi, kurumların onu benimsemesi ve düzenleyicilerin izin vermesi. Düzenlemeye tabi mesleklerin, ham görev zorluğunun işaret ettiğinden daha geç tarihler almasının sebebi bu.',
    'methodology.until.p3'                 => 'Bu bir tahmindir ve tartışılmak için yazılmıştır. Zaten amacı da bu.',

    'methodology.sources.h'                => 'Kaynaklar ve taslaklar',
    'methodology.sources.p'                => 'Yayımlanmış işgücü verisi, düzenleyici kurallar ya da sektör kanıtıyla karşılaştırılarak incelenen entry\'ler <strong>Kaynaklar</strong> bölümü taşır. Taşımayanlar sayfada <strong>topluluk taslağı</strong> olarak işaretlenir — argüman yine de iyi olabilir, ama kimse ona henüz kanıt eklememiştir. O kanıtı eklemek, yapabileceğiniz en faydalı katkıdır.',

    'methodology.refuse.h'                 => 'Yapmayı reddettiklerimiz',
    'methodology.refuse.precision'         => '<strong>Sahte kesinlik yok.</strong> Bir mesleğin karşısına yüzde koymuyoruz. O sayı bizde yok — size onu söyleyen kimsede de yok.',
    'methodology.refuse.hedging'           => '<strong>Anlamsızlaşana kadar çekince koymak yok.</strong> "Duruma göre değişir" bir yargı değildir. Karar veriyoruz ve gerekçeyi yayınlıyoruz ki saldırabilesiniz.',
    'methodology.refuse.doom'              => '<strong>Tıklanma için felaket senaryosu yok.</strong> Kırmızı olanlar dahil her entry, kullanabileceğiniz bir şeyle biter. Bir yargı sizi yapacak hiçbir şey olmadan bırakıyorsa o entry eksiktir.',
    'methodology.refuse.sponsor'           => '<strong>Sponsorluk hiçbir yargıya dokunmaz.</strong> Hiçbir sponsor yayından önce bir entry görmedi, hiçbiri de görmeyecek.',
    'methodology.refuse.sponsorLink'       => 'Kuralın tamamı burada.',

    'methodology.change.h'                 => 'Yargılar nasıl değişir',
    'methodology.change.p1'                => 'Her entry sayfanın üstünde gösterilen bir <code>lastReviewed</code> tarihi taşır. Önemli bir model ya da ürün çıktığında etkilenen entry\'leri yeniden açıyoruz ve görev dökümü gerçekten değiştiyse yargıları oynatıyoruz — haber döngüsü gürültülü olduğu için değil. Bir yargı, belirli bir <em>görev</em> durum değiştirdiğinde oynar; başlık onu takip eder.',
    // p2a + [link] + p2b olarak birlesiyor (methodology.php:82). Turkce soz dizimi
    // linki cumlenin ORTASINA aliyor, o yuzden parcalar EN'den farkli bolundu.
    'methodology.change.p2a'               => 'Her değişiklik',
    'methodology.change.link'              => 'yargı değişiklikleri',
    'methodology.change.p2b'               => ' sayfasında kayıtlı ve tarihlidir, çünkü kendi öngörülerini sessizce yeniden yazan bir site okunmayı hak etmez. Bir yargıya atıf veriyorsanız inceleme tarihiyle birlikte verin — o yargı bayatlayacak, ve bu tasarımın parçası.',

    'methodology.disagree.h'               => 'Katılmıyorsanız',
    // p + [repo] + mid + [github|noGithub] olarak birlesiyor (methodology.php:85).
    'methodology.disagree.p'               => 'Her entry tek bir JSON dosyası',
    'methodology.disagree.repo'            => ', açık bir depoda',
    'methodology.disagree.mid'             => '. Yazdığımız işi siz yapıyorsanız ve yanıldığımızı düşünüyorsanız, bizden daha iyi bir kaynaksınız',
    'methodology.disagree.github'          => ': bir pull request açın, yargıyı değiştirin ve gerekçeyi açıklamaya yazın. Sağlamsa yayına girer.',
    'methodology.disagree.noGithub'        => '. Hangi görevde yanıldığımızı yazarak bize söyleyin.',
    'methodology.disagree.contribute'      => 'GitHub\'da katkıda bulunun',
    'methodology.disagree.browse'          => 'Bütün yargılara göz atın',
    // --- /llms.txt (editoryal: makine okurlar icin kaynak metni) ---
    // Markdown yapisi (##, - , >) ve %s yer tutuculari BIREBIR korunur.
    'llms.title'                => '# Will AI Steal It? (willaistealit.com)',
    'llms.intro'                => '> Yapay zekânın hangi mesleklerin hangi görevlerini gerçekten aldığına ve geriye ne kaldığına dair görev
> seviyesinde yargılar. Her meslek gerçek görevlerine bölünür; başlık yargısı verilmeden önce her görev ayrı yargılanır.
> İçeriğin son inceleme tarihi: %s. %d meslek yayında.',

    'llms.different.h'          => '## Bu kaynağı farklı kılan ne',
    'llms.different.p'          => '"Yapay zekâ X mesleğini alacak mı" içeriklerinin çoğu bir meslek adını yargılar ve orada durur. Bu site
durmuyor. Her entry bir mesleği 4-8 somut göreve bölüyor, her göreve kendi yargısını veriyor (gitti /
gidiyor / kalıyor), ayakta kalan görevlerin neden kaldığının yapısal sebebini adlandırıyor ve o mesleğe
özel, kopyalanmaya hazır bir prompt ile bitiyor. Yargılar tahmin değil, gerekçesi görünür argümanlardır
ve her entry bir inceleme tarihi taşır.',

    'llms.verdicts.h'           => '## Yargı ölçeği',
    'llms.distribution'         => 'Mevcut dağılım: %s.',

    'llms.taskVerdicts.h'       => '## Görev seviyesinde yargılar',
    'llms.taskVerdicts.list'    => '- gitti: bugün işini iyi yapan biri bunu zaten yazılıma devrediyor.
- gidiyor: ilk taslağı makine yapıyor; insan gözden geçiriyor, düzeltiyor ve sahipleniyor.
- kalıyor: geçici bir yetenek açığı değil, yapısal bir sebep engelliyor.',

    'llms.tags.h'               => '## Direnç etiketleri (bir görev neden kalır)',

    'llms.until.h'              => '## "Şu yıla kadar güvende" yılı',
    'llms.until.p'              => 'Bir mesleğin çekirdek görevlerinin olağan uygulamada rutin olarak makineyle yapılır hâle gelmesinin
beklendiği yıl. Üç gecikmeyi hesaba katar: yeteneğin gelmesi, kurumların benimsemesi, düzenleyicilerin
izin vermesi. Meslek adının ortadan kalkacağı yıl DEĞİLDİR. Düzenlemeye tabi meslekler, ham görev
zorluğunun ima ettiğinden daha geç yıllar taşır.',

    'llms.entries.h'            => '## Entry\'ler',
    'llms.entry.line'           => '- [%s](%s): %s%s. %s Direnç: %s. İnceleme: %s.',
    'llms.entry.safeUntil'      => ', ~%s yılına kadar güvende',

    'llms.changes.h'            => '## Son yargı değişiklikleri',
    'llms.change.line'          => '- %s — %s: %s -> %s. %s',
    'llms.ongoing'              => 'sürüyor',

    'llms.methodology.h'        => '## Yöntem',
    'llms.methodology.list'     => '- Bir yargı asla tek başına durmaz: yanında her zaman görev dökümü ve direnç etiketleri gelir.
- Sponsorluk hiçbir yargıyı etkilemez. Sponsorlar entry\'leri yayından önce görmez.
- Kaynağı olmayan entry\'ler sayfada "topluluk taslağı" olarak işaretlenir.',
    'llms.methodology.rules'    => '- %s : bir yargıyı neyin değiştireceği dahil kuralların tamamı.',

    'llms.data.h'               => '## Makine okunabilir veri',
    'llms.data.sitemap'         => '- %s : her sayfa ve inceleme tarihi.',
    'llms.data.landscape'       => '- %s : her meslek ve beklenen yılı.',
    'llms.data.repo'            => '- Her entry açık bir depoda tek bir JSON dosyasıdır; şema CONTRIBUTING.md içinde belgelenmiştir.',

    'llms.citation.h'           => '## Atıf',
    'llms.citation.p'           => 'Bir yargıya atıf verirken mesleği, yargıyı, inceleme tarihini ve entry sayfasının bağlantısını ekleyin.
Yargılar değişir; bu sitenin tarihsiz bir atfı bayatlayacaktır. Her mesleğin kopyalanmaya hazır prompt\'u
kendi sayfasında durur ve okuru oraya göndermenin sebebi odur.',
];

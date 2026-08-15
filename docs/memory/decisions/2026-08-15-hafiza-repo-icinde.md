---
date: 2026-08-15
title: Editoryal hafıza repo içinde yaşar, ayrı vault'ta değil
status: active
---

# Editoryal hafıza repo içinde yaşar

## Karar
Ses kuralları, verdict rubriği ve karar kaydı `docs/memory/` altında markdown olarak,
repo içinde tutulur. Obsidian ayrı bir vault kurmaz — repo kökünü `Open folder as vault`
ile açar. `.obsidian/` gitignore'da.

## Neden
Ayrı vault ikinci bir doğruluk kaynağı yaratır: içerik `data/jobs/`'ta, kurallar başka bir
klasörde, ikisi senkrondan çıkar. Repo içinde tutulunca senkron maliyeti sıfır, git
versiyonluyor ve PR ile katkı veren de aynı kuralları görüyor.

Obsidian'ın gerçekten verdiği şey (backlink, graph view, hızlı arama) klasör konumundan
bağımsız — repo kökünü vault olarak açınca hepsi bedava geliyor.

Karar üretim disiplini de buna bağlı: kurallar yavaş değişir ve kalın dosyalarda tutulur
(`voice.md`, `verdict-rubric.md`, toplam 400 satır tavanı); kararlar hızlı büyür ve atomik
append-only notlarda tutulur. İkisi farklı ritimde yaşadığı için aynı dosyada duramaz.

## Reddedilen alternatif
Ayrı Obsidian vault. İkinci doğruluk kaynağı ve senkron maliyeti yaratır, katkıcı kuralları
göremez, hafıza tek makinede ve yedeksiz kalır.

İkinci reddedilen: her şeyi tek bir `CLAUDE.md` dosyasında toplamak. Karar kaydı append-only
büyüdüğü için birkaç ay içinde context'in ciddi bir kısmını yer ve ses kuralları gürültüde
kaybolur.

## İlgili
[[2026-08-15-acik-kaynak-gizlilik-siniri]] · [[2026-08-15-stack-php-json]]

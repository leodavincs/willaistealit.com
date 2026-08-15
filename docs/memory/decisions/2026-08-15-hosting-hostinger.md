---
date: 2026-08-15
title: Hosting — Hostinger shared plan
status: active
---

# Hosting: Hostinger shared plan

## Karar
Hostinger shared plan: 2 GB RAM, 40 PHP worker, sınırsız bant. Deploy kökü `public_html`.
PHP 8.3, `gd` ve `mbstring` uzantıları aktif. Deploy, hPanel üzerinden Git deployment ile
yapılır — repo doğrudan `public_html`'e çekilir.

## Neden
Trafik henüz yok. Sabit maliyeti mümkün olduğunca düşük tutmak, siteyi trafik oturmadan
önce kapatmak zorunda kalmamayı sağlıyor. `gd` OG kartı üretimi, `mbstring` çok dilli
metin için şart; ikisi de bu planda mevcut.

Git deployment'ın önemli bir yan etkisi var: **`.gitignore` aynı zamanda deploy filtresi
haline geliyor.** Takip edilen her dosya `public_html`'e iniyor.

## Reddedilen alternatif
VPS. Daha fazla kontrol verirdi ama trafik oturmadan sabit maliyet yaratırdı ve bu aşamada
çözdüğü bir problem yok.

> Bu bölüm karardan geriye doğru çıkarıldı (2026-08-15), o anki tartışmanın
> birebir kaydı değil. Yanlışsa düzeltilir.

## İlgili
[[2026-08-15-stack-php-json]] · [[2026-08-15-acik-kaynak-gizlilik-siniri]]

---
date: 2026-08-15
title: Stack — framework'süz PHP 8.3 + JSON dosyaları
status: active
---

# Stack: framework'süz PHP 8.3 + JSON dosyaları

## Karar
Site framework kullanmadan, düz PHP 8.3 ile yazılır. İçerik `data/jobs/` altında entry
başına bir JSON dosyasında durur. Veritabanı ve build adımı yok.

## Neden
Hosting Hostinger shared plan — Node çalıştırmıyor. Bu, Next.js ve benzeri her seçeneği
teknik olarak eler. Karar bir tercih değil, kısıtın sonucu.

İkinci sebep: içerik PR ile geliyor. JSON dosyası bir katkıcının doğrudan okuyup
düzenleyebileceği tek format; veritabanı olsaydı katkı akışı ölürdü.

## Reddedilen alternatif
Next.js + headless CMS. Hosting desteklemiyor ve açık kaynak katkı akışını bir admin
paneli arkasına kilitlerdi.

> Bu bölüm karardan geriye doğru çıkarıldı (2026-08-15), o anki tartışmanın
> birebir kaydı değil. Yanlışsa düzeltilir.

## İlgili
[[2026-08-15-hosting-hostinger]]

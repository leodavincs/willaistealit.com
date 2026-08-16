<?php
/**
 * EDITORYAL ICERIK on ekleri — merkezi, makinece okunabilir liste.
 *
 * Bu on eklerle baslayan anahtarlarin cevirisi teknik bir is degil, yayin
 * kalitesinde editoryal istir. /methodology verdict tanimlarini HALKA ACIK olarak
 * yayinlar ve cakisma halinde dogru kabul edilir; llms.txt cevap motorlarina siteyi
 * anlatir. Bunlarin makine cevirisiyle doldurulmasi sitenin otoritesini zedeler.
 *
 * Kural: bu on eklerdeki anahtarlar yalnizca kaynak dilde bulunur. Ceviri gelene
 * kadar TR/ES surumu YAYINLANMAZ — sessiz Ingilizce fallback YOKTUR.
 * Bir dil aktive edilmeden once locale_pending(<dil>) BOS olmalidir (validator).
 */
declare(strict_types=1);

return [
    // page.landscape. ve page.sponsor. Faz 4B'de eklendi: bu iki sayfa Faz 3'te
    // hic yerellestirilmemisti, metinleri PHP'ye gomuluydu. Icerikleri yayin
    // kalitesinde editoryal metin — makine cevirisiyle doldurulmaz.
    'namespaces' => ['methodology.', 'llms.', 'page.landscape.', 'page.sponsor.'],
];

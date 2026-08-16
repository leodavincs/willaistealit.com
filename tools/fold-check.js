// node tools/fold-check.js — JS fold() PHP search_fold() ile ayni sonucu vermeli.
// Fixture'lar da harita da data/search-fold.json'dan gelir: iki taraf tek kaynagi paylasir.
const spec = require('../data/search-fold.json');
global.window = { __fold: spec.map };

const src  = require('fs').readFileSync(__dirname + '/../assets/search.js', 'utf8');
const body = src.match(/function fold\(s\)[\s\S]*?\n  \}/);
if (!body) {
  console.log('HATA: assets/search.js icinde fold() bulunamadi');
  process.exit(1);
}
const fold = new Function('window', 'var FOLD = window.__fold || {}; ' + body[0] + '; return fold;')(global.window);

let fail = 0;
for (const [input, expected] of Object.entries(spec.fixtures)) {
  const got = fold(input);
  if (got !== expected) {
    console.log(`  x ${input}\n      beklenen: ${expected}\n      gelen:    ${got}`);
    fail++;
  }
}
console.log(fail === 0 ? "JS fold: fixture'larin hepsi gecti" : `\n${fail} vaka basarisiz.`);
process.exit(fail ? 1 : 0);

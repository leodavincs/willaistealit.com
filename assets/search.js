// Ana sayfa arama + verdict filtresi.
// Kartlar sunucuda basiliyor; burasi sadece DOM'u filtreliyor.
// Bagimlilik yok (Fuse.js gerekmedi) — JS kapaliysa tam liste yine gorunur.
(function () {
  'use strict';

  var input   = document.getElementById('q');
  var results = document.getElementById('results');
  var empty   = document.getElementById('empty');
  if (!input || !results) return;

  var cards  = Array.prototype.slice.call(results.querySelectorAll('.job-card'));
  var blocks = Array.prototype.slice.call(results.querySelectorAll('.cat-block'));
  var chips  = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));
  var verdictFilter = 'all';

  // Sirali harf eslesmesi: "grphc" -> "graphic designer"
  function fuzzy(haystack, needle) {
    var i = 0;
    for (var j = 0; j < haystack.length && i < needle.length; j++) {
      if (haystack[j] === needle[i]) i++;
    }
    return i === needle.length;
  }

  function matches(card, query) {
    if (verdictFilter !== 'all' && card.dataset.verdict !== verdictFilter) return false;
    if (!query) return true;
    // Tam metinde altdizge; fuzzy SADECE meslek adinda
    // (uzun metinde sirali harf aramasi her seyi esletiyor).
    if ((card.dataset.search || '').indexOf(query) !== -1) return true;
    return query.length >= 3 && fuzzy(card.dataset.name || '', query);
  }

  function apply() {
    var query = input.value.trim().toLowerCase();
    var shown = 0;

    cards.forEach(function (card) {
      var ok = matches(card, query);
      card.hidden = !ok;
      if (ok) shown++;
    });

    blocks.forEach(function (block) {
      var visible = block.querySelectorAll('.job-card:not([hidden])').length;
      block.hidden = visible === 0;
      var count = block.querySelector('.cat-count');
      if (count) count.textContent = String(visible);
    });

    empty.style.display = shown === 0 ? 'block' : 'none';
  }

  var timer;
  input.addEventListener('input', function () {
    window.clearTimeout(timer);
    timer = window.setTimeout(apply, 60);
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      input.value = '';
      apply();
    }
    if (e.key === 'Enter') {
      var first = results.querySelector('.job-card:not([hidden])');
      if (first) window.location.href = first.getAttribute('href');
    }
  });

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      verdictFilter = chip.dataset.filter;
      chips.forEach(function (c) {
        c.setAttribute('aria-pressed', String(c === chip));
      });
      apply();
    });
  });

  // /?q=translator ile gelen aramayi uygula
  var initial = new URLSearchParams(window.location.search).get('q');
  if (initial) {
    input.value = initial;
    apply();
  }

  // "/" tusu aramaya odaklanir
  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && document.activeElement !== input) {
      e.preventDefault();
      input.focus();
    }
  });
})();

// Ana sayfa dizini: arama, fasetli filtreler, sutun siralamasi.
// Satirlar sunucuda basiliyor; burasi DOM'u filtreliyor, siraliyor ve faset
// sayaclarini guncelliyor. Bagimlilik yok — JS kapaliyken tam liste gorunur.
(function () {
  'use strict';

  var input = document.getElementById('q');
  var table = document.getElementById('index-table');
  var empty = document.getElementById('empty');
  if (!input || !table) return;

  var tbody   = table.tBodies[0];
  var rows    = Array.prototype.slice.call(tbody.rows);
  var vFacets = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));
  var cFacets = Array.prototype.slice.call(document.querySelectorAll('[data-cat]'));
  var sorts   = Array.prototype.slice.call(document.querySelectorAll('[data-sort]'));
  var reset   = document.getElementById('reset');

  var state = { verdict: 'all', cat: 'all', sort: 'name', dir: 1 };

  // Sirali harf eslesmesi: "grphc" -> "graphic designer"
  function fuzzy(haystack, needle) {
    var i = 0;
    for (var j = 0; j < haystack.length && i < needle.length; j++) {
      if (haystack[j] === needle[i]) i++;
    }
    return i === needle.length;
  }

  function matchesQuery(row, query) {
    if (!query) return true;
    // Tam metinde altdizge; fuzzy SADECE meslek adinda
    // (uzun metinde sirali harf aramasi her seyi esletiyor).
    if ((row.dataset.search || '').indexOf(query) !== -1) return true;
    return query.length >= 3 && fuzzy(row.dataset.name || '', query);
  }

  function sortValue(row) {
    switch (state.sort) {
      case 'verdict': return Number(row.dataset.verdictRank);
      case 'until':   return Number(row.dataset.until);   // ufku olmayan 9999 -> sona
      default:        return row.dataset.name || '';
    }
  }

  /* Faset sayaclari: bir fasetin sayilari KENDISI disindaki filtrelere gore
     hesaplanir — secili verdict'i degistirince kategori sayilari guncellenir,
     ama verdict sayilari kendi secimlerinden etkilenmez. Standart faset davranisi. */
  function updateCounts(query) {
    var vCounts = {}, cCounts = {}, vTotal = 0, cTotal = 0;

    rows.forEach(function (row) {
      if (!matchesQuery(row, query)) return;
      var v = row.dataset.verdict, c = row.dataset.cat;

      if (state.cat === 'all' || c === state.cat) {
        vCounts[v] = (vCounts[v] || 0) + 1;
        vTotal++;
      }
      if (state.verdict === 'all' || v === state.verdict) {
        cCounts[c] = (cCounts[c] || 0) + 1;
        cTotal++;
      }
    });

    document.querySelectorAll('[data-count-verdict]').forEach(function (el) {
      var k = el.dataset.countVerdict;
      var n = k === 'all' ? vTotal : (vCounts[k] || 0);
      el.textContent = String(n);
      el.closest('.facet').classList.toggle('is-zero', n === 0 && k !== 'all');
    });
    document.querySelectorAll('[data-count-cat]').forEach(function (el) {
      var k = el.dataset.countCat;
      var n = k === 'all' ? cTotal : (cCounts[k] || 0);
      el.textContent = String(n);
      el.closest('.facet').classList.toggle('is-zero', n === 0 && k !== 'all');
    });
  }

  function apply() {
    var query = input.value.trim().toLowerCase();

    var visible = rows.filter(function (row) {
      var ok = matchesQuery(row, query)
        && (state.verdict === 'all' || row.dataset.verdict === state.verdict)
        && (state.cat === 'all' || row.dataset.cat === state.cat);
      row.hidden = !ok;
      return ok;
    });

    visible.sort(function (a, b) {
      var va = sortValue(a), vb = sortValue(b);
      if (va < vb) return -1 * state.dir;
      if (va > vb) return 1 * state.dir;
      // Esitlikte her zaman isme gore — sirali bir liste kararli olmali
      return (a.dataset.name || '').localeCompare(b.dataset.name || '');
    });

    var frag = document.createDocumentFragment();
    visible.forEach(function (r) { frag.appendChild(r); });
    tbody.appendChild(frag);

    updateCounts(query);

    empty.style.display = visible.length === 0 ? 'block' : 'none';
    table.hidden = visible.length === 0;
    if (reset) {
      reset.hidden = state.verdict === 'all' && state.cat === 'all' && query === '';
    }
  }

  function press(list, active, attr) {
    list.forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.dataset[attr] === active));
    });
  }

  var timer;
  input.addEventListener('input', function () {
    window.clearTimeout(timer);
    timer = window.setTimeout(apply, 60);
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { input.value = ''; apply(); }
    if (e.key === 'Enter') {
      var first = tbody.querySelector('tr:not([hidden]) .cell-job a');
      if (first) window.location.href = first.getAttribute('href');
    }
  });

  vFacets.forEach(function (btn) {
    btn.addEventListener('click', function () {
      state.verdict = btn.dataset.filter;
      press(vFacets, state.verdict, 'filter');
      apply();
    });
  });

  cFacets.forEach(function (btn) {
    btn.addEventListener('click', function () {
      state.cat = btn.dataset.cat;
      press(cFacets, state.cat, 'cat');
      apply();
    });
  });

  sorts.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.dataset.sort;
      // Ayni sutuna tekrar basmak yonu cevirir
      state.dir = (state.sort === key) ? -state.dir : 1;
      state.sort = key;
      sorts.forEach(function (b) {
        b.setAttribute('aria-pressed', String(b === btn));
        b.dataset.dir = (b === btn) ? (state.dir === 1 ? 'asc' : 'desc') : '';
      });
      apply();
    });
  });

  if (reset) {
    reset.addEventListener('click', function () {
      state.verdict = 'all';
      state.cat = 'all';
      input.value = '';
      press(vFacets, 'all', 'filter');
      press(cFacets, 'all', 'cat');
      apply();
      input.focus();
    });
  }

  /* --- ipucu: ornek meslekleri daktilo gibi yazip siler ---
     Amaci yazmaya davet etmek. Odaklaninca ya da bir sey yazilinca susuyor,
     ve reduced-motion tercihinde hic calismiyor (sabit metin kaliyor). */
  (function typewriter() {
    var hint = document.getElementById('q-hint');
    var word = hint && hint.querySelector('.q-word');
    if (!hint || !word) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Odakliyken de gizliyoruz: yoksa gercek imlec ile sahte imlec yan yana durur.
    // CSS :focus'a birakmiyoruz, programatik odakta tarayicilar tutarsiz.
    var focused = false;
    function sync() {
      hint.classList.toggle('is-hidden', focused || input.value !== '');
    }
    input.addEventListener('input', sync);
    input.addEventListener('focus', function () { focused = true; sync(); });
    input.addEventListener('blur', function () { focused = false; sync(); });
    sync();

    if (reduced) return;

    var samples = ['accountant', 'translator', 'plumber', 'nurse', 'video editor', 'lawyer'];
    var wi = 0, ci = samples[0].length, deleting = true, stopped = false;

    function stop() {
      stopped = true;
      word.textContent = samples[0];
    }
    input.addEventListener('focus', stop, { once: true });

    function tick() {
      if (stopped) return;
      var full = samples[wi];
      ci += deleting ? -1 : 1;
      word.textContent = full.slice(0, ci);

      var delay = deleting ? 45 : 85;
      if (!deleting && ci === full.length) {
        delay = 1600;              // tam yazilinca bekle
        deleting = true;
      } else if (deleting && ci === 0) {
        deleting = false;
        wi = (wi + 1) % samples.length;
        delay = 260;
      }
      window.setTimeout(tick, delay);
    }
    window.setTimeout(tick, 1800);
  })();

  // /?q=translator ile gelen aramayi uygula
  var initial = new URLSearchParams(window.location.search).get('q');
  if (initial) { input.value = initial; }
  apply();

  // "/" tusu aramaya odaklanir
  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && document.activeElement !== input) {
      e.preventDefault();
      input.focus();
    }
  });
})();

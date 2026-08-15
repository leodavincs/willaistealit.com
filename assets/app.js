// Kopyalama butonlari + hafif event tracking. Bagimlilik yok.
(function () {
  'use strict';

  function track(name, props) {
    // _paq bir dizi: Matomo yuklenmeden once push edilenler kuyruga girer ve
    // tracker gelince gonderilir. Analytics kapaliyken sessizce duser.
    if (!window._paq || typeof window._paq.push !== 'function') return;
    window._paq.push(['trackEvent', 'engagement', name, props && props.slug ? props.slug : undefined]);
  }

  function flash(btn, label) {
    var original = btn.dataset.originalLabel || btn.textContent;
    btn.dataset.originalLabel = original;
    btn.textContent = label;
    btn.dataset.copied = '1';
    window.setTimeout(function () {
      btn.textContent = original;
      delete btn.dataset.copied;
    }, 1600);
  }

  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy') ? resolve() : reject();
      } catch (e) {
        reject(e);
      }
      document.body.removeChild(ta);
    });
  }

  // --- tema gecisi ---
  // Kayitli tercih yoksa sistem tercihi gecerli; buton o anki gorunume gore ters cevirir.
  var toggle = document.getElementById('theme-toggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var root = document.documentElement;
      var current = root.dataset.theme;
      if (!current) {
        current = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }
      var next = current === 'dark' ? 'light' : 'dark';
      root.dataset.theme = next;
      try { localStorage.setItem('theme', next); } catch (e) {}
      track('theme_switch', { to: next });
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy], [data-copy-text]');
    if (btn) {
      var text = btn.dataset.copyText;
      if (!text && btn.dataset.copy) {
        var src = document.querySelector(btn.dataset.copy);
        text = src ? src.textContent : '';
      }
      if (!text) return;
      copy(text).then(function () {
        flash(btn, 'Copied');
        track(btn.dataset.event || 'copy', { slug: location.pathname.replace(/^\//, '') });
      }).catch(function () {
        flash(btn, 'Press Ctrl+C');
      });
      return;
    }

    var link = e.target.closest('[data-event]');
    if (link && link.tagName === 'A') {
      track(link.dataset.event, { slug: location.pathname.replace(/^\//, '') });
    }
  });
})();

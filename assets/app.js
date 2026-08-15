// Kopyalama butonlari + hafif event tracking. Bagimlilik yok.
(function () {
  'use strict';

  function track(name, props) {
    if (typeof window.plausible === 'function') {
      window.plausible(name, props ? { props: props } : undefined);
    }
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

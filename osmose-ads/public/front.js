(function () {
  if (!window.OA_FRONT) return;
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-oa-track]');
    if (!a) return;
    var body = new FormData();
    body.append('action', 'oa_track');
    body.append('post', OA_FRONT.post);
    body.append('kind', a.getAttribute('data-oa-track'));
    if (navigator.sendBeacon) {
      navigator.sendBeacon(OA_FRONT.ajax, body);
    } else {
      fetch(OA_FRONT.ajax, { method: 'POST', body: body, keepalive: true });
    }
  });
})();

/* Visionneuse : toutes les photos de la page s'ouvrent en entier, avec navigation. */
(function () {
  function ready(fn) { document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn); }
  ready(function () {
    var page = document.querySelector('.oa-page');
    if (!page) return;
    // Images des articles : on les rend cliquables (sauf si elles sont déjà dans un lien).
    page.querySelectorAll('.oa-article img').forEach(function (img) {
      if (img.closest('a') || img.naturalWidth && img.naturalWidth < 120) return;
      var a = document.createElement('a');
      a.className = 'oa-zoom';
      a.href = img.currentSrc || img.src;
      img.parentNode.insertBefore(a, img);
      a.appendChild(img);
    });
    var links = Array.prototype.slice.call(page.querySelectorAll('a.oa-zoom'));
    if (!links.length) return;

    var box = document.createElement('div');
    box.className = 'oa-lb';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Photo en grand');
    box.innerHTML = '<button class="oa-lb__close" aria-label="Fermer">×</button>' +
      '<button class="oa-lb__nav oa-lb__prev" aria-label="Photo précédente">‹</button>' +
      '<figure class="oa-lb__fig"><img alt=""><figcaption></figcaption></figure>' +
      '<button class="oa-lb__nav oa-lb__next" aria-label="Photo suivante">›</button>' +
      '<span class="oa-lb__count"></span>';
    document.body.appendChild(box);
    var img = box.querySelector('img'), cap = box.querySelector('figcaption'), count = box.querySelector('.oa-lb__count');
    var cur = 0, lastFocus = null;

    function show(i) {
      cur = (i + links.length) % links.length;
      var a = links[cur], t = a.querySelector('img');
      img.src = a.href;
      img.alt = t ? t.alt : '';
      var fc = a.closest('figure') && a.closest('figure').querySelector('figcaption');
      cap.textContent = fc ? fc.textContent : (t ? t.alt : '');
      count.textContent = (cur + 1) + ' / ' + links.length;
      box.classList.toggle('is-single', links.length < 2);
    }
    function open(i) { lastFocus = document.activeElement; show(i); box.classList.add('is-open'); document.documentElement.classList.add('oa-lb-lock'); box.querySelector('.oa-lb__close').focus(); }
    function close() { box.classList.remove('is-open'); document.documentElement.classList.remove('oa-lb-lock'); img.src = ''; if (lastFocus) lastFocus.focus(); }

    links.forEach(function (a, i) {
      a.addEventListener('click', function (e) { e.preventDefault(); open(i); });
    });
    box.querySelector('.oa-lb__close').addEventListener('click', close);
    box.querySelector('.oa-lb__prev').addEventListener('click', function () { show(cur - 1); });
    box.querySelector('.oa-lb__next').addEventListener('click', function () { show(cur + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', function (e) {
      if (!box.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(cur - 1);
      if (e.key === 'ArrowRight') show(cur + 1);
    });
    var x0 = null;
    box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 50) show(cur + (dx < 0 ? 1 : -1));
      x0 = null;
    });
  });
})();

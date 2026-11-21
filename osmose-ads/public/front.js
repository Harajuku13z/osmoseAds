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

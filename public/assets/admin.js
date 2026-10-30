document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-confirm]');
  if (el && !confirm(el.dataset.confirm)) e.preventDefault();
});

// ---- notifiche push sul telefono e sul computer ----
(function () {
  var csrf = document.body.dataset.csrf;
  if (!csrf) return;
  var pushOk = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  var banner = document.querySelector('[data-push-banner]');
  var later = false;
  try { later = localStorage.getItem('fogolar-push-later') > Date.now(); } catch (e) {}

  function all(sel, fn) { document.querySelectorAll(sel).forEach(fn); }
  function post(path, data) {
    var body = new URLSearchParams(data);
    body.set('_csrf', csrf);
    return fetch('/admin/push/' + path, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r;
    });
  }
  function bytes(b64) {
    var s = atob(b64.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((b64.length + 3) % 4));
    var out = new Uint8Array(s.length);
    for (var i = 0; i < s.length; i++) out[i] = s.charCodeAt(i);
    return out;
  }
  function stateText(t) { all('[data-push-state]', function (el) { el.textContent = t; }); }
  function show(subscribed) {
    all('[data-push-on]', function (b) { b.hidden = subscribed; });
    all('[data-push-off],[data-push-test]', function (b) { b.hidden = !subscribed; });
    if (Notification.permission === 'denied') stateText('Le notifiche sono bloccate per questo sito: riattivale dalle impostazioni del browser.');
    else stateText(subscribed ? 'Attive su questo dispositivo.' : 'Non attive su questo dispositivo.');
    if (banner) banner.hidden = subscribed || later || Notification.permission === 'denied';
  }
  function unsupported() {
    var t = isIOS && !standalone
      ? 'Su iPhone: tocca Condividi, poi «Aggiungi alla schermata Home», apri da lì il backoffice e attiva le notifiche.'
      : 'Questo browser non supporta le notifiche push.';
    stateText(t);
    var bt = document.querySelector('[data-push-text]');
    if (bt) bt.textContent = t;
    all('[data-push-on]', function (b) { b.hidden = true; });
    if (banner) banner.hidden = later || !(isIOS && !standalone);
  }
  function sync(sub) {
    var j = sub.toJSON();
    return post('iscrivi', { endpoint: j.endpoint, p256dh: j.keys.p256dh, auth: j.keys.auth });
  }
  function enable() {
    if (!pushOk) { unsupported(); return; }
    stateText('Attivazione…');
    Notification.requestPermission().then(function (perm) {
      if (perm !== 'granted') { show(false); return; }
      return navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin' })
        .then(function () { return navigator.serviceWorker.ready; })
        .then(function (reg) {
          return fetch('/admin/push/chiave', { credentials: 'same-origin' }).then(function (r) { return r.json(); })
            .then(function (k) { return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: bytes(k.key) }); });
        })
        .then(sync)
        .then(function () { show(true); });
    }).catch(function (e) { stateText('Attivazione non riuscita: ' + e.message); });
  }
  function disable() {
    navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); })
      .then(function (sub) {
        if (!sub) return;
        return post('disiscrivi', { endpoint: sub.endpoint }).then(function () { return sub.unsubscribe(); });
      })
      .then(function () { show(false); })
      .catch(function (e) { stateText('Disattivazione non riuscita: ' + e.message); });
  }

  all('[data-push-on]', function (b) { b.addEventListener('click', enable); });
  all('[data-push-off]', function (b) { b.addEventListener('click', disable); });
  var laterBtn = document.querySelector('[data-push-later]');
  if (laterBtn) laterBtn.addEventListener('click', function () {
    try { localStorage.setItem('fogolar-push-later', Date.now() + 7 * 86400000); } catch (e) {}
    later = true;
    banner.hidden = true;
  });

  if (!pushOk) { unsupported(); return; }
  navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin' })
    .then(function (reg) { return reg.pushManager.getSubscription(); })
    .then(function (sub) {
      show(!!sub);
      if (sub) sync(sub).catch(function () {}); // riallinea il server se l'abbonamento è cambiato
    })
    .catch(function () { show(false); });
})();

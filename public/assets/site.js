(function () {
  var burger = document.querySelector('.burger');
  if (burger) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') !== 'true';
      burger.setAttribute('aria-expanded', open);
      document.body.classList.toggle('nav-open', open);
    });
  }

  var top = document.querySelector('.top--over');
  if (top) {
    var onScroll = function () { top.classList.toggle('is-solid', scrollY > 40); };
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Schede "Perché vieni in Carnia"
  var tabs = [].slice.call(document.querySelectorAll('[role=tab]'));
  function select(tab, focus) {
    tabs.forEach(function (x) {
      var on = x === tab;
      x.setAttribute('aria-selected', on);
      x.tabIndex = on ? 0 : -1;
      document.getElementById(x.getAttribute('aria-controls')).hidden = !on;
    });
    tab.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
    if (focus) tab.focus();
  }
  tabs.forEach(function (tab, i) {
    tab.tabIndex = i ? -1 : 0;
    tab.addEventListener('click', function () { select(tab); });
    tab.addEventListener('keydown', function (e) {
      var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (d) select(tabs[(i + d + tabs.length) % tabs.length], true);
    });
  });
  document.querySelectorAll('[data-later]').forEach(function (p) { p.hidden = true; });

  // Richiesta a passi: senza JS il modulo resta tutto visibile e funzionante
  var form = document.querySelector('form.ask');
  if (!form) return;
  form.classList.add('is-steps');
  var steps = [].slice.call(form.querySelectorAll('.step'));
  var prev = form.querySelector('[data-prev]'), next = form.querySelector('[data-next]'), send = form.querySelector('[data-send]');
  var cur = form.closest('section').querySelector('.alert') ? 2 : new URLSearchParams(location.search).has('tipo') ? 1 : 0;

  function show(i, focus) {
    cur = i;
    steps.forEach(function (s, n) { s.hidden = n !== i; });
    prev.hidden = i === 0;
    next.hidden = i === steps.length - 1;
    send.hidden = i !== steps.length - 1;
    if (focus) {
      var el = steps[i].querySelector('input:not([type=radio]),textarea') || steps[i].querySelector('input');
      if (el && matchMedia('(min-width: 700px)').matches) el.focus({ preventScroll: true });
      form.scrollIntoView({ block: 'nearest' });
    }
  }
  function valid(step) {
    var fields = step.querySelectorAll('input,textarea');
    for (var i = 0; i < fields.length; i++) {
      if (!fields[i].disabled && !fields[i].checkValidity()) { fields[i].reportValidity(); return false; }
    }
    return true;
  }
  next.addEventListener('click', function () { if (valid(steps[cur])) show(cur + 1, true); });
  prev.addEventListener('click', function () { show(cur - 1, true); });
  form.querySelectorAll('.kinds input').forEach(function (r) {
    r.addEventListener('click', function () { sync(); show(1, true); });
  });

  var from = form.elements.date_from, to = form.elements.date_to, people = form.elements.people;
  var closedDays = JSON.parse(form.dataset.closedDays), closedDates = JSON.parse(form.dataset.closedDates);
  var warn = form.querySelector('[data-warn]'), label = form.querySelector('[data-label-from]');

  function kind() { return form.elements.kind.value; }
  function isStay() { return kind() === 'camera' || kind() === 'appartamento'; }
  function sync() {
    form.querySelectorAll('[data-only=stay]').forEach(function (el) { el.hidden = !isStay(); });
    form.querySelectorAll('[data-only=table]').forEach(function (el) { el.hidden = kind() !== 'tavolo'; });
    label.textContent = isStay() ? label.dataset.stay : label.dataset.day;
    if (to) to.disabled = !isStay();
    if (kind() === 'festa' && +people.value < 10) people.value = 20;
    if (kind() !== 'festa' && +people.value >= 20) people.value = 2;
    checkDate();
  }
  function checkDate() {
    var closed = false;
    if (from.value && !isStay()) {
      var day = new Date(from.value + 'T12:00').getDay();
      closed = closedDays.indexOf(day) >= 0 || closedDates.some(function (c) { return from.value >= c[0] && from.value <= c[1]; });
    }
    warn.hidden = !closed;
    if (from.value) to.min = from.value;
  }
  from.addEventListener('change', checkDate);
  form.querySelector('[data-step-down]').addEventListener('click', function () { people.value = Math.max(1, (+people.value || 1) - 1); });
  form.querySelector('[data-step-up]').addEventListener('click', function () { people.value = Math.min(300, (+people.value || 0) + 1); });

  sync();
  show(cur);
})();

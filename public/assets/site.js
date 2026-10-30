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

  // Con "riduci movimento" il fuoco resta fermo
  var fire = document.querySelector('.hero__video');
  if (fire && matchMedia('(prefers-reduced-motion: reduce)').matches) {
    fire.removeAttribute('autoplay');
    fire.pause();
  }

  // Caroselli: lo scorrimento resta quello nativo, frecce e pallini vanno solo dove la pista può arrivare
  var LANG = document.documentElement.lang;
  function say(it, en, de) { return LANG === 'de' ? de : LANG === 'en' ? en : it; }

  document.querySelectorAll('.strip,.reviews__row').forEach(function (track) {
    var slides = [].slice.call(track.children);
    if (slides.length < 2) return;
    var ctl = document.createElement('div');
    ctl.className = 'car__ctl';
    function button(label, text) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'car__btn';
      b.setAttribute('aria-label', label);
      b.textContent = text;
      return b;
    }
    var prev = button(say('Precedente', 'Previous', 'Zurück'), '←');
    var next = button(say('Successiva', 'Next', 'Weiter'), '→');
    var mid = document.createElement('div');
    ctl.appendChild(prev);
    ctl.appendChild(mid);
    ctl.appendChild(next);
    track.parentNode.insertBefore(ctl, track.nextSibling);

    var stops = [], cur = 0, target = null, timer;

    function measure() {
      var end = track.scrollWidth - track.clientWidth, base = slides[0].offsetLeft;
      stops = [];
      slides.forEach(function (s) {
        var x = Math.min(s.offsetLeft - base, end);
        if (!stops.length || x - stops[stops.length - 1] > 4) stops.push(x);
      });
      ctl.hidden = stops.length < 2;
      mid.textContent = '';
      if (stops.length <= 6) {
        mid.className = 'car__dots';
        stops.forEach(function (x, i) {
          var d = document.createElement('button');
          d.type = 'button';
          d.className = 'car__dot';
          d.setAttribute('aria-label', String(i + 1));
          d.addEventListener('click', function () { go(i); });
          mid.appendChild(d);
        });
      } else {
        mid.className = 'car__count';
      }
      show();
    }
    function nearest() {
      var x = track.scrollLeft, best = 0;
      stops.forEach(function (s, i) { if (Math.abs(s - x) < Math.abs(stops[best] - x)) best = i; });
      return best;
    }
    function show() {
      cur = target !== null ? target : nearest();
      prev.disabled = cur === 0;
      next.disabled = cur === stops.length - 1;
      [].forEach.call(mid.children, function (d, i) { d.setAttribute('aria-current', i === cur); });
      if (mid.className === 'car__count') mid.textContent = (cur + 1) + ' / ' + slides.length;
    }
    function go(i) {
      target = Math.max(0, Math.min(stops.length - 1, i));
      var still = matchMedia('(prefers-reduced-motion: reduce)').matches;
      track.scrollTo({ left: stops[target], behavior: still ? 'auto' : 'smooth' });
      show();
      clearTimeout(timer);
      timer = setTimeout(settle, 700);
    }
    function settle() { target = null; show(); }

    prev.addEventListener('click', function () { go(cur - 1); });
    next.addEventListener('click', function () { go(cur + 1); });
    track.addEventListener('scroll', function () {
      if (target === null) requestAnimationFrame(show);
      else { clearTimeout(timer); timer = setTimeout(settle, 140); }
    }, { passive: true });
    // Il dito ha sempre la precedenza su un'animazione in corso
    track.addEventListener('pointerdown', function () { clearTimeout(timer); target = null; }, { passive: true });
    addEventListener('resize', measure, { passive: true });
    addEventListener('load', measure);
    measure();
  });

  // Galleria a schermo intero: sul telefono si sfoglia col dito, su desktop con frecce e tastiera
  document.querySelectorAll('.strip').forEach(function (strip) {
    var figures = [].slice.call(strip.querySelectorAll('figure'));
    if (!figures.length) return;
    var box, rail, count, opened = false, fromHistory = false;

    function build() {
      box = document.createElement('dialog');
      box.className = 'lb';
      box.setAttribute('aria-label', say('Galleria fotografica', 'Photo gallery', 'Fotogalerie'));
      box.innerHTML = '<div class="lb__rail" tabindex="-1"></div><p class="lb__count" aria-live="polite"></p>' +
        '<button type="button" class="lb__x">×</button><button type="button" class="lb__nav lb__nav--prev">←</button><button type="button" class="lb__nav lb__nav--next">→</button>';
      rail = box.querySelector('.lb__rail');
      count = box.querySelector('.lb__count');
      box.querySelector('.lb__x').setAttribute('aria-label', say('Chiudi', 'Close', 'Schließen'));
      box.querySelector('.lb__nav--prev').setAttribute('aria-label', say('Foto precedente', 'Previous photo', 'Vorheriges Foto'));
      box.querySelector('.lb__nav--next').setAttribute('aria-label', say('Foto successiva', 'Next photo', 'Nächstes Foto'));
      figures.forEach(function (f) {
        var src = f.querySelector('img');
        var slide = document.createElement('figure');
        slide.className = 'lb__slide';
        var img = document.createElement('img');
        img.alt = src.alt;
        img.decoding = 'async';
        img.sizes = '100vw';
        img.srcset = src.getAttribute('srcset');
        img.src = src.getAttribute('src');
        slide.appendChild(img);
        if (src.alt) {
          var cap = document.createElement('figcaption');
          cap.textContent = src.alt;
          slide.appendChild(cap);
        }
        rail.appendChild(slide);
      });
      document.body.appendChild(box);
      rail.addEventListener('scroll', function () { requestAnimationFrame(update); }, { passive: true });
      box.querySelector('.lb__x').addEventListener('click', close);
      box.querySelector('.lb__nav--prev').addEventListener('click', function () { move(-1); });
      box.querySelector('.lb__nav--next').addEventListener('click', function () { move(1); });
      box.addEventListener('cancel', function (e) { e.preventDefault(); close(); });
      box.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { e.preventDefault(); move(1); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); move(-1); }
      });
      rail.addEventListener('click', function (e) { if (e.target.tagName !== 'IMG') close(); });
    }
    function index() { return Math.round(rail.scrollLeft / (rail.clientWidth || 1)); }
    function update() {
      var i = index();
      count.textContent = (i + 1) + ' / ' + figures.length;
      box.querySelector('.lb__nav--prev').disabled = i === 0;
      box.querySelector('.lb__nav--next').disabled = i === figures.length - 1;
    }
    function move(d) {
      rail.scrollTo({ left: (index() + d) * rail.clientWidth, behavior: 'smooth' });
    }
    function open(i, back) {
      if (!box) build();
      back = back || figures[i];
      box.showModal();
      document.documentElement.classList.add('lb-open');
      rail.style.scrollBehavior = 'auto';
      rail.scrollLeft = i * rail.clientWidth;
      rail.style.scrollBehavior = '';
      update();
      opened = true;
      // Il tasto indietro del telefono chiude la galleria invece di lasciare la pagina
      history.pushState({ lb: true }, '');
      addEventListener('popstate', onPop);
    }
    function onPop() { fromHistory = true; close(); }
    function close() {
      if (!opened) return;
      opened = false;
      removeEventListener('popstate', onPop);
      var i = index();
      box.close();
      document.documentElement.classList.remove('lb-open');
      if (!fromHistory) history.back();
      fromHistory = false;
      figures[i].scrollIntoView({ inline: 'center', block: 'nearest' });
      figures[i].focus({ preventScroll: true });
    }
    figures.forEach(function (f, i) {
      f.tabIndex = 0;
      f.setAttribute('role', 'button');
      f.setAttribute('aria-label', say('Apri la foto: ', 'Open photo: ', 'Foto öffnen: ') + (f.querySelector('img').alt || (i + 1)));
      f.addEventListener('click', function () { open(i); });
      f.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(i); }
      });
    });
  });

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

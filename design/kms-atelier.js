/* ============================================================================
   KMS — "Atelier" interaction layer

   Purely additive: it tags existing nodes with helper classes and drives a few
   CSS custom properties. It never rewrites markup the app owns, so React stays
   in charge of the DOM and re-renders survive.
   ========================================================================= */
(function () {
  'use strict';

  var reduced =
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* -- Reading progress rail ---------------------------------------------- */

  var rail = document.createElement('div');
  rail.className = 'kms-progress';
  rail.setAttribute('aria-hidden', 'true');
  document.body.appendChild(rail);

  var ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      rail.style.setProperty('--kms-progress', max > 0 ? doc.scrollTop / max : 0);
      ticking = false;
    });
  }
  addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* -- Card tagging -------------------------------------------------------- */

  var CARD_SELECTOR = [
    "main [class*='rounded-lg'][class*='bg-surface']:not([class*='border-dashed'])",
    "main .panel[class*='rounded-lg']",
    "main [class*='aspect-'][class*='border-dashed']",
  ].join(',');

  function tag(root) {
    var nodes = (root || document).querySelectorAll(CARD_SELECTOR);
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      if (el.classList.contains('kms-card')) continue;
      // Skip wrappers that merely contain other cards — only leaf tiles glow.
      if (el.querySelector(CARD_SELECTOR)) continue;
      el.classList.add('kms-card');
      if (el.tagName === 'A' || el.closest('a')) el.classList.add('kms-card--link');
    }
  }

  /* -- Cursor spotlight (delegated: one listener for the whole document) ---- */

  var lastCard = null;
  if (!reduced && matchMedia('(hover: hover) and (pointer: fine)').matches) {
    addEventListener(
      'pointermove',
      function (e) {
        var card = e.target instanceof Element ? e.target.closest('.kms-card') : null;
        if (card !== lastCard) lastCard = card;
        if (!card) return;
        var r = card.getBoundingClientRect();
        card.style.setProperty('--kx', ((e.clientX - r.left) / r.width) * 100 + '%');
        card.style.setProperty('--ky', ((e.clientY - r.top) / r.height) * 100 + '%');
      },
      { passive: true }
    );
  }

  /* -- Scroll reveal ------------------------------------------------------- */

  var io = null;
  if ('IntersectionObserver' in window) {
    io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('reveal-in');
          io.unobserve(entry.target);
        });
      },
      { rootMargin: '0px 0px -12% 0px', threshold: 0.05 }
    );
  }

  function observeReveals(root) {
    if (!io) return;
    var nodes = (root || document).querySelectorAll('.reveal:not(.reveal-in)');
    for (var i = 0; i < nodes.length; i++) {
      // Anything already on screen at load reveals immediately.
      var r = nodes[i].getBoundingClientRect();
      if (r.top < innerHeight * 0.92) nodes[i].classList.add('reveal-in');
      else io.observe(nodes[i]);
    }
  }

  /* -- Stat count-up ------------------------------------------------------- */

  var counted = new WeakSet();
  function countUp(el) {
    if (counted.has(el)) return;
    var raw = el.textContent.trim();
    var m = raw.match(/^(\d+)(\D*)$/);
    if (!m) return;
    counted.add(el);
    var target = parseInt(m[1], 10);
    var suffix = m[2] || '';
    if (target === 0 || target > 100000) return;
    var start = performance.now();
    var dur = 1100;
    function frame(now) {
      var t = Math.min(1, (now - start) / dur);
      var eased = 1 - Math.pow(1 - t, 3);
      el.textContent = Math.round(target * eased) + suffix;
      if (t < 1) requestAnimationFrame(frame);
    }
    el.textContent = '0' + suffix;
    requestAnimationFrame(frame);
  }

  var statIo = null;
  if (!reduced && 'IntersectionObserver' in window) {
    statIo = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          countUp(entry.target);
          statIo.unobserve(entry.target);
        });
      },
      { threshold: 0.6 }
    );
  }

  function observeStats(root) {
    if (!statIo) return;
    var nodes = (root || document).querySelectorAll(
      'main .text-display-lg.font-extrabold, main .text-display-xl.font-extrabold'
    );
    for (var i = 0; i < nodes.length; i++) {
      if (/^\d+\D{0,2}$/.test(nodes[i].textContent.trim())) statIo.observe(nodes[i]);
    }
  }

  /* -- Wire up, and keep up with client-side navigation --------------------- */

  function refresh() {
    tag();
    observeReveals();
    observeStats();
  }

  refresh();

  var pending = null;
  var mo = new MutationObserver(function () {
    if (pending) return;
    pending = requestAnimationFrame(function () {
      pending = null;
      refresh();
    });
  });
  mo.observe(document.body, { childList: true, subtree: true });
})();

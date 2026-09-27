/*
 * Lexus Thăng Long — đo lường tự host, ẩn danh, không cookie.
 *
 *   1. Nguồn khách: lần đầu vào web ghi lại trang giới thiệu (Google,
 *      Facebook, Zalo…), UTM, trang vào đầu tiên → localStorage. Gắn vào mọi
 *      form [data-lead-form] (ô ẩn "attribution") để lead biết tới từ đâu.
 *      Lượt có UTM/gclid/fbclid (chiến dịch) ghi đè lần trước; vào thẳng thì không.
 *   2. Bấm Gọi / Zalo: mỗi lần bấm link tel: hoặc zalo.me gửi 1 sự kiện.
 *   3. Tốc độ thật: LCP, INP, CLS, FCP, TTFB của khách — gửi khi rời trang.
 *
 * Gửi về /api/v1/events bằng sendBeacon (không làm chậm trang). Đồng thời báo
 * generate_lead / click_call / click_zalo cho GA4 (gtag) nếu đã cài mã GA4, còn
 * không thì đẩy vào window.dataLayer cho GTM.
 */
(() => {
  'use strict';

  const KEY = 'ltl.src';
  const ENDPOINT = '/api/v1/events';
  const store = {
    get: (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* bỏ qua */ } },
  };
  window.dataLayer = window.dataLayer || [];
  const track = (name, params) => {
    if (typeof window.gtag === 'function') window.gtag('event', name, params || {});
    else window.dataLayer.push(Object.assign({ event: name }, params));
  };

  /* ── 1. Nguồn khách ─────────────────────────────────────────────────── */

  const params = new URLSearchParams(location.search);
  const utm = {};
  ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((k) => {
    if (params.get(k)) utm[k] = params.get(k).slice(0, 120);
  });
  const campaign = Object.keys(utm).length || params.get('gclid') || params.get('fbclid');
  const external = document.referrer && !document.referrer.startsWith(location.origin);

  let touch = null;
  try { touch = JSON.parse(store.get(KEY) || 'null'); } catch (e) { touch = null; }

  if (!touch || campaign || (external && touch.direct)) {
    touch = {
      ref: external ? document.referrer.slice(0, 300) : '',
      land: location.pathname,
      utm,
      gclid: params.get('gclid') ? 1 : 0,
      fbclid: params.get('fbclid') ? 1 : 0,
      direct: !external && !campaign,
      ts: Date.now(),
    };
    store.set(KEY, JSON.stringify(touch));
  }
  const touchJson = JSON.stringify(touch);

  const attach = (form) => {
    let input = form.querySelector('input[name="attribution"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'attribution';
      form.appendChild(input);
    }
    input.value = touchJson;
  };
  document.querySelectorAll('[data-lead-form]').forEach(attach);
  // Form thêm vào sau (popup) — gắn ngay trước khi gửi.
  document.addEventListener('submit', (e) => {
    const form = e.target.closest && e.target.closest('[data-lead-form]');
    if (form) attach(form);
  }, true);
  document.addEventListener('lead:sent', (e) => {
    const form = e.target.closest ? e.target.closest('[data-lead-form]') : null;
    // form_id = khoá form trong đường dẫn gửi (/gui-form/nhan-bao-gia → nhan-bao-gia).
    const key = form ? (form.getAttribute('action') || '').split('/').filter(Boolean).pop() : '';
    track('generate_lead', { form_id: key || 'lead', page_path: location.pathname });
  });

  /* ── Gửi sự kiện ─────────────────────────────────────────────────────── */

  const send = (events) => {
    if (!events.length) return;
    const body = JSON.stringify({ events, attribution: touch });
    try {
      if (navigator.sendBeacon && navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }))) return;
    } catch (e) { /* lùi về fetch */ }
    try { fetch(ENDPOINT, { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true }); } catch (e) { /* bỏ qua */ }
  };

  /* ── 2. Bấm Gọi / Zalo ──────────────────────────────────────────────── */

  document.addEventListener('click', (e) => {
    const a = e.target.closest && e.target.closest('a[href^="tel:"], a[href*="zalo.me"]');
    if (!a) return;
    const type = a.getAttribute('href').startsWith('tel:') ? 'call' : 'zalo';
    track(type === 'call' ? 'click_call' : 'click_zalo', { page_path: location.pathname });
    send([{ type, path: location.pathname }]);
  }, true);

  /* ── 3. Tốc độ thật (Core Web Vitals) ───────────────────────────────── */

  if (!('PerformanceObserver' in window)) return;
  const types = PerformanceObserver.supportedEntryTypes || [];
  const vitals = {};
  const watch = (type, cb, opts) => {
    if (!types.includes(type)) return;
    try { new PerformanceObserver((list) => cb(list.getEntries())).observe(Object.assign({ type, buffered: true }, opts)); } catch (e) { /* bỏ qua */ }
  };

  watch('largest-contentful-paint', (entries) => {
    const last = entries[entries.length - 1];
    if (last) vitals.LCP = last.renderTime || last.startTime;
  });
  watch('paint', (entries) => {
    entries.forEach((p) => { if (p.name === 'first-contentful-paint') vitals.FCP = p.startTime; });
  });

  // CLS theo "cửa sổ phiên": các dịch chuyển cách nhau < 1 giây, cửa sổ tối đa 5 giây.
  if (types.includes('layout-shift')) {
    vitals.CLS = 0;
    let win = 0, start = 0, prev = 0;
    watch('layout-shift', (entries) => {
      entries.forEach((s) => {
        if (s.hadRecentInput) return;
        if (win && s.startTime - prev < 1000 && s.startTime - start < 5000) win += s.value;
        else { win = s.value; start = s.startTime; }
        prev = s.startTime;
        vitals.CLS = Math.max(vitals.CLS, win);
      });
    });
  }

  // INP (xấp xỉ): tương tác chậm nhất trong trang.
  watch('event', (entries) => {
    entries.forEach((ev) => { if (ev.interactionId) vitals.INP = Math.max(vitals.INP || 0, ev.duration); });
  }, { durationThreshold: 40 });

  const nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
  if (nav && nav.responseStart > 0) vitals.TTFB = nav.responseStart;

  // Gửi một lần khi khách rời/ẩn trang (lúc đó số đo đã chốt). Safari đôi khi
  // chỉ báo pagehide nên nghe cả hai.
  let reported = false;
  const report = (force) => {
    if (reported || (!force && document.visibilityState !== 'hidden')) return;
    reported = true;
    send(Object.keys(vitals).map((metric) => ({
      type: 'vital', metric, value: metric === 'CLS' ? Number(vitals[metric].toFixed(4)) : Math.round(vitals[metric]), path: location.pathname,
    })));
  };
  document.addEventListener('visibilitychange', () => report(false), true);
  window.addEventListener('pagehide', () => report(true), true);
})();

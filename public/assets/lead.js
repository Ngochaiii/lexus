/*
 * Lexus Thăng Long — form thu lead + popup báo giá.
 *
 * File JavaScript DUY NHẤT của site. Mọi thứ ở đây là lớp nâng cấp: tắt JS
 * thì form vẫn POST thường và redirect về kèm lời cảm ơn, nút "Báo giá" vẫn
 * dẫn tới trang /bao-gia. Không có nút chết.
 *
 * Ba việc:
 *   1. Gửi mọi form [data-lead-form] bằng fetch, hiện kết quả tại chỗ.
 *   2. Bấm nút "Báo giá" → mở <dialog id="quote-dialog"> thay vì chuyển trang.
 *   3. Trang chủ: tự mở popup sau N giây — có giới hạn tần suất, không bật trên
 *      điện thoại (xem config('catalog.frontend.popup') để biết vì sao).
 */
(() => {
  'use strict';

  const KEY_SENT = 'ltl.lead.sent';        // lúc gửi form gần nhất
  const KEY_SHOWN = 'ltl.popup.shown_at';  // lúc popup TỰ BẬT gần nhất
  const KEY_SESSION = 'ltl.popup.shown';   // đã tự bật trong phiên này
  const DAY = 86400000;

  // localStorage có thể ném lỗi (Safari riêng tư, bị chặn cookie) — không
  // được để lỗi đó làm hỏng việc gửi form.
  const store = {
    get: (area, k) => { try { return window[area].getItem(k); } catch (e) { return null; } },
    set: (area, k, v) => { try { window[area].setItem(k, v); } catch (e) { /* bỏ qua */ } },
  };
  const within = (key, days) => {
    const t = Number(store.get('localStorage', key));
    return t > 0 && Date.now() - t < days * DAY;
  };

  /* ── 1. Gửi form bằng fetch ─────────────────────────────────────────── */

  const setStatus = (form, text, kind) => {
    const box = form.querySelector('[data-lead-status]');
    if (!box) return;
    box.textContent = text;
    box.hidden = !text;
    box.dataset.kind = kind || '';
  };

  const clearErrors = (form) => {
    form.querySelectorAll('[data-error-for]').forEach((el) => { el.hidden = true; el.textContent = ''; });
    form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
  };

  const showErrors = (form, errors) => {
    let first = null;
    Object.entries(errors || {}).forEach(([field, messages]) => {
      const name = field.split('.')[0];           // consent.0 → consent
      const slot = form.querySelector(`[data-error-for="${name}"]`);
      const input = form.querySelector(`[name="${name}"], [name="${name}[]"]`);
      if (slot) { slot.textContent = [].concat(messages)[0]; slot.hidden = false; }
      if (input) { input.setAttribute('aria-invalid', 'true'); first = first || input; }
    });
    if (first) first.focus();
  };

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-lead-form]');
    if (!form || !window.fetch || !window.FormData) return;

    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;
    clearErrors(form);
    setStatus(form, 'Đang gửi…', 'pending');

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        form.reset();
        const chip = form.querySelector('[data-variant-chip]');
        if (chip && !form.querySelector('[data-variant-input]').value) chip.hidden = true;
        setStatus(form, data.message || form.dataset.success || 'Đã nhận thông tin của bạn.', 'success');
        store.set('localStorage', KEY_SENT, String(Date.now()));
        // Móc cho đo chuyển đổi (GTM/Pixel): document.addEventListener('lead:sent', …)
        form.dispatchEvent(new CustomEvent('lead:sent', { bubbles: true }));
      } else if (res.status === 422) {
        setStatus(form, 'Vui lòng kiểm tra lại các ô được đánh dấu.', 'error');
        showErrors(form, data.errors);
      } else if (res.status === 429) {
        setStatus(form, 'Bạn gửi hơi nhanh. Vui lòng thử lại sau ít phút, hoặc gọi trực tiếp cho chuyên viên.', 'error');
      } else if (res.status === 419) {
        // Phiên hết hạn (để trang mở quá lâu) — gửi lại kiểu thường để lấy token mới.
        form.submit();
      } else {
        throw new Error('HTTP ' + res.status);
      }
    } catch (err) {
      // Mất mạng hay lỗi máy chủ: lùi về POST thường thay vì bỏ rơi khách.
      form.submit();
      return;
    } finally {
      if (button) button.disabled = false;
    }
  });

  /* ── 2. Popup báo giá ───────────────────────────────────────────────── */

  const dialog = document.getElementById('quote-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return; // trình duyệt quá cũ: để link tự dẫn tới /bao-gia

  const form = dialog.querySelector('[data-lead-form]');
  const select = form && form.querySelector('[data-product-select]');
  const quotePath = dialog.dataset.quotePath || '/bao-gia';

  // Phiên bản khách bấm từ thẻ phiên bản: ô ẩn variant_id + dòng "Phiên bản: …".
  const variantInput = form && form.querySelector('[data-variant-input]');
  const variantChip = form && form.querySelector('[data-variant-chip]');
  const setVariant = (id, name) => {
    if (!variantInput) return;
    variantInput.value = id || '';
    if (variantChip) {
      variantChip.hidden = !id;
      const label = variantChip.querySelector('[data-variant-name]');
      if (label) label.textContent = name || '';
    }
  };
  // Đổi dòng xe thì phiên bản cũ không còn đúng — bỏ đi.
  if (select) select.addEventListener('change', () => setVariant('', ''));

  const open = (mode, productId, variantId, variantName) => {
    if (dialog.open || !form) return;
    form.action = mode === 'auto' ? dialog.dataset.autoAction : dialog.dataset.manualAction;
    clearErrors(form);
    setStatus(form, '', '');
    if (select) select.value = productId || '';
    setVariant(variantId, variantName);
    dialog.showModal();
    const first = form.querySelector('input[name="name"]');
    if (first) first.focus();
  };


  // Bấm ra ngoài hộp (vào nền mờ) thì đóng. Sự kiện click trên chính
  // <dialog> chỉ xảy ra khi bấm vào ::backdrop, vì nội dung phủ kín hộp.
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });

  // Mọi link "Báo giá": có data-quote, hoặc trỏ tới /bao-gia (kể cả link
  // biên tập viên gõ tay trong admin).
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link) return;
    // Ctrl/Cmd/Shift/chuột giữa = khách muốn mở tab mới — để trình duyệt lo.
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    let isQuote = link.hasAttribute('data-quote');
    if (!isQuote) {
      try { isQuote = new URL(link.href, location.href).pathname === quotePath; } catch (e) { /* href lạ */ }
    }
    if (!isQuote || location.pathname === quotePath) return; // đang ở /bao-gia thì cứ để form trên trang

    event.preventDefault();

    // Chọn sẵn xe: ưu tiên data-product (id), không có thì đọc ?xe={slug}.
    let productId = link.dataset.product || '';
    if (!productId && select) {
      let slug = '';
      try { slug = new URL(link.href, location.href).searchParams.get('xe') || ''; } catch (e) { /* bỏ qua */ }
      const option = slug && select.querySelector(`option[data-slug="${CSS.escape(slug)}"]`);
      if (option) productId = option.value;
    }
    open('manual', productId, link.dataset.variant || '', link.dataset.variantName || '');
  });

  /* ── 3. Tự bật trên trang chủ ───────────────────────────────────────── */

  const delay = Number(dialog.dataset.autoDelay);
  if (!delay) return; // chỉ trang chủ có data-auto-delay

  const allowMobile = dialog.dataset.autoMobile === '1';
  const isSmall = window.matchMedia('(max-width: 768px)').matches;

  if (
    (isSmall && !allowMobile) ||
    store.get('sessionStorage', KEY_SESSION) ||
    within(KEY_SENT, Number(dialog.dataset.sentDays) || 90) ||
    within(KEY_SHOWN, Number(dialog.dataset.dismissDays) || 7)
  ) return;

  // Đếm thời gian khách THỰC SỰ nhìn trang: tab bị ẩn thì dừng đồng hồ.
  let remaining = delay * 1000;
  let startedAt = null;
  let timer = null;

  const fire = () => {
    // Đang gõ vào một form khác, hoặc đã có hộp thoại mở → không chen ngang.
    const active = document.activeElement;
    const typing = active && /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName);
    if (typing || dialog.open || document.querySelector('dialog[open]')) {
      remaining = 5000; // thử lại sau 5 giây
      start();
      return;
    }
    // Ghi nhớ NGAY LÚC BẬT, không đợi lúc đóng: khách có thể đóng tab luôn
    // mà không bấm ×, và sự kiện `close` của <dialog> cũng có thể bị trình
    // duyệt hoãn ở tab nền. Đã hiện một lần là im dismiss_days ngày.
    store.set('localStorage', KEY_SHOWN, String(Date.now()));
    store.set('sessionStorage', KEY_SESSION, '1');
    open('auto', '');
  };

  const start = () => {
    if (document.hidden || timer) return;
    startedAt = Date.now();
    timer = setTimeout(() => { timer = null; fire(); }, remaining);
  };

  const pause = () => {
    if (!timer) return;
    clearTimeout(timer);
    timer = null;
    remaining = Math.max(0, remaining - (Date.now() - startedAt));
  };

  document.addEventListener('visibilitychange', () => (document.hidden ? pause() : start()));
  start();
})();

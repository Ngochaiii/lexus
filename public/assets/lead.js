/*
 * Lexus Thăng Long — form thu lead + popup báo giá.
 *
 * File JavaScript DUY NHẤT của site. Mọi thứ ở đây là lớp nâng cấp: tắt JS
 * thì form vẫn POST thường và redirect về kèm lời cảm ơn, nút "Báo giá" vẫn
 * dẫn tới trang /bao-gia. Không có nút chết.
 *
 * Năm việc:
 *   1. Gửi mọi form [data-lead-form] bằng fetch, hiện kết quả tại chỗ.
 *   2. Bấm nút "Báo giá" → mở <dialog id="quote-dialog"> thay vì chuyển trang.
 *   3. Trang chủ: tự mở popup sau N giây — có giới hạn tần suất, không bật trên
 *      điện thoại (xem config('catalog.frontend.popup') để biết vì sao).
 *   4. Bấm Zalo → chép sẵn lời nhắn có tên xe, mở khung Zalo dự phòng
 *      (partials/zalo-sheet). Đặt trước mục 2 vì mục 2 thoát sớm ở /bao-gia.
 *   5. Bấm Gọi → vẫn quay số, khách quay lại web có khung "Để lại số"
 *      (partials/call-sheet). Cũng đặt trước mục 2.
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

  // Khung cảm ơn thay chỗ các ô (CSS: .lead-form[data-sent]). Form cũ không có
  // khung (cache HTML) thì lùi về dòng trạng thái.
  const showSent = (form, text) => {
    const panel = form.querySelector('[data-lead-sent]');
    if (!panel) { setStatus(form, text, 'success'); return; }
    setStatus(form, '', '');
    const msg = panel.querySelector('[data-lead-sent-message]');
    if (msg) msg.textContent = text;
    panel.hidden = false;
    form.dataset.sent = '1';
    panel.focus({ preventScroll: true });
    panel.scrollIntoView({ block: 'nearest' });
  };
  // Số khách vừa gửi, dạng 0912 345 678 — hiện trong khung cảm ơn.
  const sentPhone = (form, phone) => {
    const slot = form.querySelector('[data-lead-sent-phone]');
    if (!slot) return;
    const p = normalizePhone(phone);
    slot.hidden = !p;
    if (!p) return;
    slot.textContent = 'Số sẽ được gọi lại: ';
    const b = document.createElement('strong');
    b.textContent = /^\d{10}$/.test(p) ? `${p.slice(0, 4)} ${p.slice(4, 7)} ${p.slice(7)}` : p;
    slot.appendChild(b);
  };
  const resetSent = (form) => {
    const panel = form.querySelector('[data-lead-sent]');
    if (panel) panel.hidden = true;
    delete form.dataset.sent;
  };
  document.addEventListener('click', (event) => {
    const again = event.target.closest && event.target.closest('[data-lead-again]');
    if (!again) return;
    const form = again.closest('[data-lead-form]');
    resetSent(form);
    const first = form.querySelector('input[name="name"]') || form.querySelector('input[name="phone"]');
    if (first) first.focus();
  });

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

  /* Số điện thoại: nhắc ngay khi khách rời ô (không đợi bấm Gửi), chỉ khi
     chắc chắn sai. Cùng luật với App\Support\Phone::PATTERN — máy chủ vẫn
     là nơi quyết định, đây chỉ để khách sửa sớm. */
  const PHONE_OK = /^0(?:2\d{8}|(?:3[2-9]|5[25689]|7[06-9]|8[1-9]|9\d)\d{7})$/;
  const PHONE_MSG = 'Số điện thoại chưa đúng. Bạn kiểm tra lại giúp: 10 số, ví dụ 0912 345 678.';
  function normalizePhone(raw) {
    let d = String(raw || '').trim().replace(/[\s.\-()]+/g, '');
    if (d.startsWith('+84')) d = '0' + d.slice(3);
    else if (d.startsWith('84') && d.length === 11) d = '0' + d.slice(2);
    return d;
  }
  const checkPhone = (input, strict) => {
    const form = input.closest('[data-lead-form]');
    const slot = form && form.querySelector(`[data-error-for="${input.name}"]`);
    const value = normalizePhone(input.value);
    const bad = value !== '' && !PHONE_OK.test(value);
    if (bad && strict) {
      input.setAttribute('aria-invalid', 'true');
      if (slot) { slot.textContent = PHONE_MSG; slot.hidden = false; }
    } else if (!bad && input.getAttribute('aria-invalid')) {
      input.removeAttribute('aria-invalid');
      if (slot) { slot.hidden = true; slot.textContent = ''; }
    }
  };
  // Rời ô: báo nếu sai. Đang gõ lại: chỉ gỡ lỗi khi đã đúng, không la giữa chừng.
  document.addEventListener('focusout', (e) => {
    if (e.target.matches && e.target.matches('[data-lead-form] input[type="tel"]')) checkPhone(e.target, true);
  });
  document.addEventListener('input', (e) => {
    if (e.target.matches && e.target.matches('[data-lead-form] input[type="tel"]')) checkPhone(e.target, false);
  });

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
        const phoneInput = form.querySelector('input[type="tel"]');
        const phone = phoneInput ? phoneInput.value : '';
        form.reset();
        sentPhone(form, phone);
        const chip = form.querySelector('[data-variant-chip]');
        if (chip && !form.querySelector('[data-variant-input]').value) chip.hidden = true;
        showSent(form, data.message || form.dataset.success || 'Đã nhận thông tin của bạn.');
        store.set('localStorage', KEY_SENT, String(Date.now()));
        // Móc cho đo chuyển đổi (GTM/Pixel): document.addEventListener('lead:sent', …)
        // Chỉ lead mới: bản trùng và bot cũng nhận 201 nhưng data.new = false.
        if (data.data && data.data.new) {
          form.dispatchEvent(new CustomEvent('lead:sent', { bubbles: true }));
        }
      } else if (res.status === 422) {
        setStatus(form, 'Vui lòng kiểm tra lại các ô được đánh dấu.', 'error');
        showErrors(form, data.errors);
        // insight.js ghi bước "gửi bị lỗi" của hành trình khách.
        form.dispatchEvent(new CustomEvent('lead:invalid', { bubbles: true }));
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

  /* ── 4. Nút Zalo: chép sẵn lời nhắn + khung dự phòng ────────────────── */

  const zaloSheet = document.getElementById('zalo-sheet');
  if (zaloSheet && typeof zaloSheet.showModal === 'function') {
    const message = zaloSheet.querySelector('[data-zalo-message]');
    const copied = zaloSheet.querySelector('[data-zalo-copied]');
    const status = zaloSheet.querySelector('[data-copy-status]');

    // Lùi về execCommand khi không có Clipboard API (http, trình duyệt trong
    // app cũ). Ô tạm phải nằm ngoài vùng bị khoá: khi khung đang mở thì mọi
    // thứ ngoài khung thành inert, nên gắn vào chính khung.
    const legacyCopy = (text, host) => {
      const area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', '');
      area.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
      host.appendChild(area);
      area.select();
      let ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { /* bỏ qua */ }
      area.remove();
      return ok;
    };
    // Phải gọi ngay trong cú bấm (trình duyệt chỉ cho chép khi khách vừa chạm).
    const copy = (text, host) => {
      try {
        if (navigator.clipboard && window.isSecureContext) {
          return navigator.clipboard.writeText(text).then(() => true, () => legacyCopy(text, host));
        }
      } catch (e) { /* lùi về execCommand */ }
      return Promise.resolve(legacyCopy(text, host));
    };
    const say = (text) => { status.textContent = text; status.hidden = !text; };

    // Mọi link Zalo của trang (thanh đáy, nút nổi, đầu trang xe, bảng giá…).
    // KHÔNG chặn link: Zalo vẫn mở như trước, khung chỉ chờ sẵn khi khách quay lại.
    document.addEventListener('click', (event) => {
      const link = event.target.closest && event.target.closest('a[href*="zalo.me"]');
      if (!link || link.hasAttribute('data-zalo-again')) return;
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

      // data-zalo-tail: đuôi lời nhắn riêng (khối người tư vấn: chỗ điền nơi đăng ký xe).
      const text = `${zaloSheet.dataset.greeting} ${link.dataset.zaloCar || zaloSheet.dataset.car}${link.dataset.zaloTail || ' ạ.'}`;
      message.textContent = text;
      copied.hidden = true;
      say('');
      copy(text, document.body).then((ok) => { copied.hidden = !ok; });
      // Mở khung sau khi trình duyệt đã xử lý link (sang Zalo / tab mới).
      setTimeout(() => { if (!zaloSheet.open) zaloSheet.showModal(); }, 0);
    });

    zaloSheet.addEventListener('click', (event) => {
      if (event.target === zaloSheet) { zaloSheet.close(); return; } // bấm nền mờ
      const target = event.target.closest('button, a');
      if (!target) return;
      if (target.hasAttribute('data-dialog-close')) zaloSheet.close();
      else if (target.hasAttribute('data-copy-message')) {
        copy(message.textContent, zaloSheet).then((ok) => say(ok ? 'Đã chép lời nhắn — mở Zalo, bấm Nhắn tin rồi Dán.' : 'Máy không cho chép — giữ vào lời nhắn ở trên để chép.'));
      } else if (target.dataset.copy) {
        copy(target.dataset.copy, zaloSheet).then((ok) => say(ok ? 'Đã chép số Zalo — mở Zalo, bấm ô tìm kiếm và Dán số.' : `Số Zalo: ${target.dataset.copy}`));
      } else if (target.hasAttribute('data-quote')) {
        zaloSheet.close(); // nhường chỗ cho popup báo giá (mục 2 mở nó)
      }
    });
  }

  /* ── 5. Nút Gọi: khung để lại số khi khách quay lại ─────────────────── */

  const callSheet = document.getElementById('call-sheet');
  if (callSheet && typeof callSheet.showModal === 'function') {
    // KHÔNG chặn link: máy vẫn quay số như trước, khung chỉ chờ sẵn phía sau.
    document.addEventListener('click', (event) => {
      const link = event.target.closest && event.target.closest('a[href^="tel:"]');
      if (!link || link.hasAttribute('data-call-again')) return;
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      if (within(KEY_SENT, 1)) return; // vừa để lại số rồi, không hỏi lại

      const host = link.closest('dialog[open]'); // nút Gọi trong khung Zalo, popup báo giá
      setTimeout(() => {
        if (host) host.close();
        if (!callSheet.open) callSheet.showModal();
      }, 0);
    });

    callSheet.addEventListener('click', (event) => {
      if (event.target === callSheet) { callSheet.close(); return; } // bấm nền mờ
      if (event.target.closest('[data-dialog-close]')) callSheet.close();
    });
  }

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
    resetSent(form);
    if (select) select.value = productId || '';
    setVariant(variantId, variantName);
    // Form rút gọn: đã biết xe của trang thì ẩn ô chọn, chỉ ghi tên xe.
    const productField = form.querySelector('[data-product-field]');
    const productLine = form.querySelector('[data-product-line]');
    if (productLine && productField && select) {
      const known = select.selectedOptions[0] && select.value ? select.selectedOptions[0].textContent : '';
      productField.hidden = Boolean(known);
      productLine.hidden = !known || Boolean(variantId);
      productLine.querySelector('[data-product-name]').textContent = known;
    }
    dialog.showModal();
    const first = form.querySelector('input[name="name"], input[name="phone"]');
    if (first) first.focus();
  };


  // Nút × (button thường, không phải form method="dialog" — xem quote-dialog).
  dialog.querySelectorAll('[data-dialog-close]').forEach((btn) => btn.addEventListener('click', () => dialog.close()));

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

    // Chọn sẵn xe: ưu tiên data-product (id), rồi ?xe={slug}, cuối cùng là
    // dòng xe của trang đang xem (<body data-product> trên trang xe/phiên bản)
    // — nút "Báo giá" ở thanh đáy, header không biết trang đang nói xe nào.
    let productId = link.dataset.product || '';
    if (!productId && select) {
      let slug = '';
      try { slug = new URL(link.href, location.href).searchParams.get('xe') || ''; } catch (e) { /* bỏ qua */ }
      const option = slug && select.querySelector(`option[data-slug="${CSS.escape(slug)}"]`);
      if (option) productId = option.value;
    }
    if (!productId) productId = document.body.dataset.product || '';
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

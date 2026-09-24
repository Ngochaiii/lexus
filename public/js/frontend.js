/*
 * Tương tác frontend — JS thuần, không thư viện, không build.
 *
 * Nguyên tắc: mọi khối đều đọc được khi TẮT JS. Script này chỉ nâng cấp
 * thêm (chuyển slide, lọc tab, đổi màu). Không có script thì carousel thành
 * dải cuộn ngang vuốt được, tab hiện tất cả — không có nút chết.
 */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    /* ── Menu mobile: một hàng gọn, mở thành drawer có trạng thái ARIA ─ */
    function initNavigation() {
        var toggle = document.querySelector('[data-nav-toggle]');
        var nav = document.getElementById('main-nav');
        var backdrop = document.querySelector('[data-nav-close]');
        if (!toggle || !nav) return;

        function setOpen(open) {
            document.body.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
        }

        toggle.addEventListener('click', function () {
            setOpen(!document.body.classList.contains('nav-open'));
        });

        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
    }

    /* ── Carousel hero: các slide chồng lên nhau, đổi bằng opacity ────── */
    function initHero(root) {
        var slides = root.querySelectorAll('[data-hero-slide]');
        if (slides.length < 2) return;

        var dots = root.querySelectorAll('[data-hero-dot]');
        var count = root.querySelector('[data-hero-count]');
        var i = 0;

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function show(next) {
            i = (next + slides.length) % slides.length;
            root.classList.toggle('hero--bare-active', slides[i].classList.contains('hero__slide--bare'));
            for (var k = 0; k < slides.length; k++) {
                slides[k].classList.toggle('is-on', k === i);
                slides[k].setAttribute('aria-hidden', k === i ? 'false' : 'true');
            }
            for (var d = 0; d < dots.length; d++) {
                dots[d].classList.toggle('is-on', d === i);
                dots[d].setAttribute('aria-current', d === i ? 'true' : 'false');
            }
            if (count) count.textContent = pad(i + 1) + ' / ' + pad(slides.length);
        }

        root.addEventListener('click', function (e) {
            var prev = e.target.closest('[data-hero-prev]');
            var next = e.target.closest('[data-hero-next]');
            var dot = e.target.closest('[data-hero-dot]');
            if (prev) { e.preventDefault(); show(i - 1); }
            else if (next) { e.preventDefault(); show(i + 1); }
            else if (dot) { e.preventDefault(); show(+dot.dataset.heroDot); }
        });

        show(0);
    }

    /* ── Coverflow "Khám phá dải sản phẩm" ───────────────────────────── */
    function initDiscovery(root) {
        var items = Array.prototype.slice.call(root.querySelectorAll('[data-disc-item]'));
        if (!items.length) return;

        var tabs = root.querySelectorAll('[data-disc-tab]');
        var stage = root.querySelector('[data-disc-stage]');
        var count = root.querySelector('[data-disc-count]');
        var filter = 'all';
        var i = 0;

        function visible() {
            return items.filter(function (el) {
                return filter === 'all' || el.dataset.discCat === filter;
            });
        }

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function render() {
            var list = visible();
            if (!list.length) return;
            i = Math.min(i, list.length - 1);

            items.forEach(function (el) {
                el.hidden = true;
                el.classList.remove('is-on', 'is-peek');
            });

            var wanted = [{ index: i, rel: 0 }];
            if (list.length > 1) wanted.unshift({ index: (i - 1 + list.length) % list.length, rel: -1 });
            if (list.length > 2) wanted.push({ index: (i + 1) % list.length, rel: 1 });

            wanted.forEach(function (position) {
                var el = list[position.index];
                el.hidden = false;
                el.classList.toggle('is-on', position.rel === 0);
                el.classList.toggle('is-peek', position.rel !== 0);
                el.style.order = String(position.rel);
            });

            if (count) count.textContent = pad(i + 1) + ' / ' + pad(list.length);
            if (stage) stage.dataset.discSingle = list.length < 2 ? 'true' : 'false';
        }

        root.addEventListener('click', function (e) {
            var tab = e.target.closest('[data-disc-tab]');
            var prev = e.target.closest('[data-disc-prev]');
            var next = e.target.closest('[data-disc-next]');
            var peek = e.target.closest('[data-disc-item].is-peek');

            if (tab) {
                e.preventDefault();
                filter = tab.dataset.discTab;
                i = 0;
                for (var t = 0; t < tabs.length; t++) {
                    tabs[t].classList.toggle('is-on', tabs[t] === tab);
                }
                render();
            } else if (prev || next) {
                e.preventDefault();
                var n = visible().length;
                if (n > 1) { i = (i + (next ? 1 : -1) + n) % n; render(); }
            } else if (peek) {
                e.preventDefault();
                var peekIndex = visible().indexOf(peek);
                if (peekIndex >= 0) { i = peekIndex; render(); }
            }
        });

        root.classList.add('is-live');
        render();
    }

    /* ── Bộ chọn màu ở trang chi tiết ────────────────────────────────── */
    function initSwatches(root) {
        var chips = root.querySelectorAll('[data-swatch]');
        if (chips.length < 2) return;

        var panel = document.querySelector('[data-swatch-panel]');
        var label = document.querySelector('[data-swatch-label]');
        if (!panel) return;

        panel.addEventListener('animationend', function () {
            panel.classList.remove('is-color-changing');
        });

        root.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-swatch]');
            if (!chip) return;
            e.preventDefault();

            for (var c = 0; c < chips.length; c++) {
                chips[c].classList.toggle('is-on', chips[c] === chip);
                chips[c].setAttribute('aria-pressed', chips[c] === chip ? 'true' : 'false');
            }

            var hex = chip.dataset.swatch;
            var img = chip.dataset.swatchImage;
            var fallback = panel.dataset.swatchFallback;
            panel.style.background = img
                ? 'center/cover url("' + img + '")'
                : (fallback ? 'center/cover url("' + fallback + '")' : (hex || '#1c1c1a'));
            panel.style.setProperty('--swatch-accent', hex || '#1464f4');
            panel.classList.remove('is-color-changing');
            requestAnimationFrame(function () { panel.classList.add('is-color-changing'); });
            if (label) label.textContent = chip.dataset.swatchName || '';
            panel.setAttribute('aria-label', (panel.dataset.productName || '') + ' màu ' + (chip.dataset.swatchName || ''));
        });

        var first = root.querySelector('[data-swatch]');
        if (first) {
            first.classList.add('is-on');
            first.setAttribute('aria-pressed', 'true');
        }
    }

    /* ── Băng chuyền ảnh (mục layout-carousel, thư viện ở hero) ──────── */
    function initGallery(root) {
        var slides = root.querySelectorAll('[data-gal-slide]');
        if (slides.length < 2) return;

        var dots = root.querySelectorAll('[data-gal-dot]');
        var label = root.querySelector('[data-gal-label]');
        var desc = root.querySelector('[data-gal-desc-out]');
        var count = root.querySelector('[data-gal-count]');
        var i = 0;

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function show(next) {
            i = (next + slides.length) % slides.length;
            for (var k = 0; k < slides.length; k++) {
                slides[k].classList.toggle('is-on', k === i);
                slides[k].setAttribute('aria-hidden', k === i ? 'false' : 'true');
            }
            for (var d = 0; d < dots.length; d++) {
                dots[d].classList.toggle('is-on', d === i);
            }
            if (label) label.textContent = slides[i].dataset.galTitle || '';
            if (desc) {
                desc.textContent = slides[i].dataset.galDesc || '';
                // Ảnh không có mô tả thì thu ô lại, tránh chừa một khoảng trống.
                desc.hidden = ! desc.textContent;
            }
            if (count) count.textContent = pad(i + 1) + ' / ' + pad(slides.length);
        }

        root.addEventListener('click', function (e) {
            var prev = e.target.closest('[data-gal-prev]');
            var next = e.target.closest('[data-gal-next]');
            var dot = e.target.closest('[data-gal-dot]');
            if (prev) { e.preventDefault(); show(i - 1); }
            else if (next) { e.preventDefault(); show(i + 1); }
            else if (dot) { e.preventDefault(); show(+dot.dataset.galDot); }
        });

        root.classList.add('is-live');
        show(0);
    }

    /* ── Tab đánh số (mục layout-tabs) ───────────────────────────────── */
    function initTabs(root) {
        var tabs = root.querySelectorAll('[data-tab]');
        var panels = root.querySelectorAll('[data-tab-panel]');
        if (tabs.length < 2) return;

        function show(i) {
            for (var k = 0; k < tabs.length; k++) {
                tabs[k].classList.toggle('is-on', k === i);
                tabs[k].setAttribute('aria-selected', k === i ? 'true' : 'false');
            }
            for (var k2 = 0; k2 < panels.length; k2++) {
                panels[k2].classList.toggle('is-on', k2 === i);
                panels[k2].hidden = k2 !== i;
            }
        }

        root.addEventListener('click', function (e) {
            var tab = e.target.closest('[data-tab]');
            if (!tab) return;
            e.preventDefault();
            show(+tab.dataset.tab);
        });

        root.classList.add('is-live');
        show(0);
    }

    /* ── Trang đặt cọc: đổi tab hình thức + wizard 2 bước ────────────── */
    /* Không có script thì cả hai bước nằm liền nhau trong cùng một <form>
       và các tab là link thật (?hinh-thuc=) — không có nút chết. */
    /* Trên điện thoại (≤960px) wizard gập thành một màn: cả hai bước hiện
       cùng lúc, nút Tiếp tục/Quay lại ẩn bằng CSS, và mục <details> "Thêm
       thông tin" đóng lại để màn chỉ còn: chọn xe → tên → điện thoại → Gửi.
       Xoay ngang/đổi cỡ thì tính lại — không để khách kẹt ở bước 2 ẩn. */
    function initBooking(root) {
        var panes = root.querySelectorAll('[data-booking-pane]');
        if (!panes.length) return;

        var intros = root.querySelectorAll('[data-booking-intro]');
        var modes = root.querySelectorAll('[data-booking-mode]');
        var steps = root.querySelectorAll('[data-booking-step]');
        var compact = window.matchMedia ? window.matchMedia('(max-width: 960px)') : null;

        function isCompact() { return !!(compact && compact.matches); }

        /* Mục gấp: mobile đóng (trừ khi ô bên trong đang lỗi), desktop luôn mở. */
        function foldMore() {
            var mores = root.querySelectorAll('[data-booking-more]');
            for (var i = 0; i < mores.length; i++) {
                mores[i].open = !isCompact() || mores[i].hasAttribute('data-booking-more-error');
            }
        }

        function activeKey() {
            for (var i = 0; i < panes.length; i++) {
                if (!panes[i].hidden) return panes[i].dataset.bookingPane;
            }
            return panes[0].dataset.bookingPane;
        }

        function pane(key) {
            for (var i = 0; i < panes.length; i++) {
                if (panes[i].dataset.bookingPane === key) return panes[i];
            }
            return null;
        }

        /* Bước 3 (màn cảm ơn) không có fieldset — coi như đã xong cả 3. */
        function showStep(key, n) {
            var host = pane(key);
            if (!host) return;

            var sets = host.querySelectorAll('[data-booking-pane-step]');
            if (!sets.length) { markSteps(3); return; }

            for (var i = 0; i < sets.length; i++) {
                sets[i].hidden = !isCompact() && +sets[i].dataset.bookingPaneStep !== n;
            }
            markSteps(n);

            var kicker = null;
            for (var k = 0; k < intros.length; k++) {
                if (intros[k].dataset.bookingIntro === key) {
                    kicker = intros[k].querySelector('[data-booking-kicker]');
                }
            }
            if (kicker) kicker.textContent = kicker.textContent.replace(/bước \d\/3/, 'bước ' + n + '/3');
        }

        function markSteps(n) {
            for (var i = 0; i < steps.length; i++) {
                var num = +steps[i].dataset.bookingStep;
                steps[i].classList.toggle('is-on', num === n);
                steps[i].classList.toggle('is-done', num < n);
                var badge = steps[i].querySelector('.booking__step-num');
                if (badge) badge.textContent = num < n ? '✓' : String(num);
            }
        }

        function showMode(key) {
            for (var i = 0; i < panes.length; i++) panes[i].hidden = panes[i].dataset.bookingPane !== key;
            for (var j = 0; j < intros.length; j++) intros[j].hidden = intros[j].dataset.bookingIntro !== key;
            for (var m = 0; m < modes.length; m++) {
                modes[m].classList.toggle('chip--on', modes[m].dataset.bookingMode === key);
            }
            showStep(key, 1);
        }

        root.addEventListener('click', function (e) {
            var mode = e.target.closest('[data-booking-mode]');
            var next = e.target.closest('[data-booking-next]');
            var prev = e.target.closest('[data-booking-prev]');

            if (mode) {
                e.preventDefault();
                showMode(mode.dataset.bookingMode);
                history.replaceState(null, '', mode.getAttribute('href'));
            } else if (next) {
                e.preventDefault();
                showStep(activeKey(), 2);
                root.scrollIntoView({ block: 'start', behavior: 'smooth' });
            } else if (prev) {
                e.preventDefault();
                showStep(activeKey(), 1);
                root.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        });

        /* Thẻ chọn: đánh dấu cái đang chọn trong cùng một nhóm radio. */
        root.addEventListener('change', function (e) {
            var input = e.target.closest('.pick input');
            if (!input) return;

            var group = root.querySelectorAll('.pick input[name="' + input.name + '"]');
            for (var i = 0; i < group.length; i++) {
                group[i].closest('.pick').classList.toggle('is-on', group[i].checked);
            }
        });

        /* Ô đang lỗi validate nằm ở bước nào thì mở đúng bước đó. */
        var key = activeKey();
        var bad = pane(key) && pane(key).querySelector('.field__error');
        var set = bad && bad.closest('[data-booking-pane-step]');
        showStep(key, set ? +set.dataset.bookingPaneStep : 1);
        foldMore();

        if (compact) {
            var onChange = function () { showStep(activeKey(), 1); foldMore(); };
            if (compact.addEventListener) compact.addEventListener('change', onChange);
            else if (compact.addListener) compact.addListener(onChange);
        }

        /* Dải chọn xe vuốt ngang: xe đã chọn sẵn (?xe=) kéo vào giữa dải.
           Chỉ cuộn NGANG trong dải — scrollIntoView sẽ kéo cả trang xuống. */
        var picked = root.querySelector('.booking__cars .pick.is-on');
        var strip = picked && picked.closest('.booking__cars');
        if (strip && isCompact()) {
            var cell = picked.parentElement;
            strip.scrollLeft = cell.offsetLeft - (strip.clientWidth - cell.offsetWidth) / 2;
        }
    }

    /* ── Popup thu lead ───────────────────────────────────────────────── */
    /* Mọi điều kiện đọc từ data-* (Cài đặt đổ xuống). Đóng rồi thì ghi mốc vào
       localStorage và im đúng số ngày đã khai — không hỏi lại mỗi lần tải. */
    function initPopup(root) {
        var key = 'popup:' + (root.dataset.popupKey || 'mac-dinh');
        var days = parseInt(root.dataset.popupDays, 10) || 0;
        var delay = (parseInt(root.dataset.popupDelay, 10) || 10) * 1000;
        var successTimer = null;

        try {
            var until = parseInt(localStorage.getItem(key), 10);
            if (until && Date.now() < until) return;
        } catch (e) { /* trình duyệt chặn localStorage thì cứ hiện */ }

        var opener = null;
        var timer = setTimeout(open, delay);

        function remember() {
            if (!days) return;
            try {
                localStorage.setItem(key, String(Date.now() + days * 86400000));
            } catch (e) { /* không ghi được thì thôi */ }
        }

        function focusables() {
            return root.querySelectorAll(
                'a[href], button:not([disabled]), input:not([type=hidden]), select, textarea'
            );
        }

        function open() {
            opener = document.activeElement;
            root.hidden = false;
            var first = root.querySelector('input:not([type=hidden]), button, select, textarea');
            if (first) first.focus();
            document.addEventListener('keydown', onKey);
        }

        function close() {
            clearTimeout(timer);
            clearTimeout(successTimer);
            root.hidden = true;
            remember();
            document.removeEventListener('keydown', onKey);
            if (opener && opener.focus) opener.focus();
        }

        /* Esc để đóng, Tab quẩn trong panel — không để tiêu điểm chạy ra sau
           lớp phủ rồi người dùng bàn phím mắc kẹt. */
        function onKey(e) {
            if (e.key === 'Escape') { e.preventDefault(); close(); return; }
            if (e.key !== 'Tab') return;

            var items = focusables();
            if (!items.length) return;

            var first = items[0];
            var last = items[items.length - 1];

            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-popup-close]')) { e.preventDefault(); close(); }
        });

        /* Chỉ đóng sau khi server xác nhận đã nhận lead. Thông báo nổi vẫn
           còn trên màn hình sau khi popup đóng; lỗi thì popup ở nguyên. */
        var form = root.querySelector('form');
        if (form) {
            form.addEventListener('lead:success', function () {
                remember();
                successTimer = setTimeout(close, 700);
            });
        }
    }

    /* ── Form tư vấn: gửi nền, không tải lại cả trang ───────────────── */
    var leadToastTimer = null;

    function showLeadToast(message) {
        var toast = document.querySelector('[data-lead-toast]');

        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'lead-toast';
            toast.dataset.leadToast = '';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }

        clearTimeout(leadToastTimer);
        toast.textContent = message;
        toast.hidden = false;

        requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });

        leadToastTimer = setTimeout(function () {
            toast.classList.remove('is-visible');
            setTimeout(function () { toast.hidden = true; }, 250);
        }, 3500);
    }

    function initLeadForm(form) {
        if (!window.fetch || !window.FormData) return;

        var status = form.querySelector('[data-lead-status]');
        var button = form.querySelector('button[type="submit"]');
        var buttonText = button ? button.textContent : '';

        function showStatus(message, tone) {
            if (!status) return;
            status.hidden = false;
            status.textContent = message;
            status.classList.toggle('is-success', tone === 'success');
            status.classList.toggle('is-error', tone === 'error');
        }

        function clearErrors() {
            form.querySelectorAll('[data-lead-error]').forEach(function (error) {
                error.remove();
            });
            form.querySelectorAll('[aria-invalid="true"]').forEach(function (field) {
                field.removeAttribute('aria-invalid');
            });
            if (status) {
                status.hidden = true;
                status.textContent = '';
                status.classList.remove('is-success', 'is-error');
            }
        }

        function fieldFor(key) {
            var fields = form.querySelectorAll('[name]');
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].name === key || fields[i].name === key + '[]') return fields[i];
            }
            return null;
        }

        function showValidationErrors(errors) {
            var first = null;
            Object.keys(errors || {}).forEach(function (key) {
                var field = fieldFor(key);
                if (!field) return;
                if (!first) first = field;
                field.setAttribute('aria-invalid', 'true');

                var host = field.closest('.field');
                if (!host) return;
                var error = document.createElement('p');
                error.className = 'field__error';
                error.dataset.leadError = '';
                error.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                host.appendChild(error);
            });
            if (first && first.focus) first.focus();
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (form.dataset.submitting === 'true') return;

            clearErrors();
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            if (button) {
                button.disabled = true;
                button.textContent = 'Đang gửi…';
            }
            showStatus('Đang gửi thông tin…', 'loading');

            fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (json) {
                        if (!response.ok) {
                            throw { status: response.status, json: json };
                        }
                        return json;
                    });
                })
                .then(function (json) {
                    var message = json.message || 'Đã nhận thông tin, chúng tôi sẽ liên hệ sớm.';
                    form.reset();
                    showStatus(message, 'success');
                    showLeadToast(message);
                    form.dispatchEvent(new CustomEvent('lead:success', {
                        bubbles: true,
                        detail: { message: message }
                    }));
                })
                .catch(function (failure) {
                    var json = failure && failure.json ? failure.json : {};
                    if (failure && failure.status === 422 && json.errors) {
                        showValidationErrors(json.errors);
                        showStatus('Vui lòng kiểm tra lại các thông tin được đánh dấu.', 'error');
                    } else if (failure && failure.status === 429) {
                        showStatus('Bạn đã gửi quá nhanh. Vui lòng chờ một phút rồi thử lại.', 'error');
                    } else {
                        showStatus('Chưa gửi được thông tin. Vui lòng kiểm tra kết nối và thử lại.', 'error');
                    }
                })
                .finally(function () {
                    delete form.dataset.submitting;
                    form.removeAttribute('aria-busy');
                    if (button) {
                        button.disabled = false;
                        button.textContent = buttonText;
                    }
                });
        });
    }

    /* ── Trang chủ: reveal theo chương + parallax ảnh có giới hạn ───── */
    function initHomeStory(root) {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var revealNodes = Array.prototype.slice.call(root.querySelectorAll('[data-home-reveal]'));
        var parallaxNodes = Array.prototype.slice.call(root.querySelectorAll('[data-home-parallax]'));
        var hero = root.querySelector('[data-home-hero]');

        revealNodes.forEach(function (el, i) {
            el.style.setProperty('--home-delay', ((i % 5) * 70) + 'ms');
            if (el.matches('.offer__media, .home-charge__media, .split__media, .tiles')) {
                el.classList.add('home-reveal--media');
            }
        });
        root.classList.add('home-motion-ready');

        if (reduceMotion || !('IntersectionObserver' in window)) {
            revealNodes.forEach(function (el) { el.classList.add('is-revealed'); });
        } else {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });

            revealNodes.forEach(function (el) { observer.observe(el); });
        }

        if (reduceMotion) return;

        var ticking = false;
        function updateMotion() {
            ticking = false;

            if (hero) {
                var heroRect = hero.getBoundingClientRect();
                var heroProgress = Math.max(0, Math.min(1, -heroRect.top / Math.max(1, heroRect.height)));
                hero.style.setProperty('--home-hero-scale', (1 + heroProgress * 0.035).toFixed(4));
                hero.style.setProperty('--home-hero-shift', (heroProgress * 14).toFixed(2) + 'px');
            }

            parallaxNodes.forEach(function (node) {
                var rect = node.getBoundingClientRect();
                if (rect.bottom < -100 || rect.top > window.innerHeight + 100) return;
                var center = (rect.top + rect.height / 2 - window.innerHeight / 2) / Math.max(1, window.innerHeight);
                var shift = Math.max(-1, Math.min(1, center)) * -30;
                node.style.setProperty('--home-shift', shift.toFixed(2) + 'px');
            });
        }

        function requestMotion() {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(updateMotion);
        }

        updateMotion();
        window.addEventListener('scroll', requestMotion, { passive: true });
        window.addEventListener('resize', requestMotion);
    }

    /* ── Trang chi tiết xe: nhịp kể chuyện + chuyển động theo cuộn ───── */
    function initProductStory(root) {
        var hero = root.querySelector('[data-story-hero]');
        var nav = root.querySelector('[data-product-nav]');
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var revealSelector = [
            '.intro > *',
            '.highlights > li',
            '[data-story-section] .section__head > *',
            '[data-story-section] .variant',
            '[data-story-section] .item',
            '[data-story-section] .spec-flat > div',
            '[data-story-section] .specs > details',
            '[data-story-section] .fuel-calc > *',
            '[data-story-section] .loan > *',
            '[data-story-section] .lead-form-wrap'
        ].join(',');
        var mediaSelector = [
            '[data-story-section] .config__render',
            '[data-story-section] .gallery__stage',
            '[data-story-section] .tabs__media',
            '[data-story-section] .item__media',
            '[data-story-section] .fuel-calc__media'
        ].join(',');
        var reveals = Array.prototype.slice.call(root.querySelectorAll(revealSelector));
        var mediaReveals = Array.prototype.slice.call(root.querySelectorAll(mediaSelector));

        reveals.forEach(function (el, i) {
            el.classList.add('story-reveal');
            el.style.setProperty('--reveal-delay', ((i % 4) * 70) + 'ms');
        });
        mediaReveals.forEach(function (el) {
            el.classList.add('story-reveal', 'story-reveal--media');
        });

        root.classList.add('story-motion-ready');

        if (reduceMotion || ! ('IntersectionObserver' in window)) {
            reveals.concat(mediaReveals).forEach(function (el) { el.classList.add('is-revealed'); });
        } else {
            var revealObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-revealed');
                    revealObserver.unobserve(entry.target);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -7% 0px' });

            reveals.concat(mediaReveals).forEach(function (el) { revealObserver.observe(el); });
        }

        /* Chuyển động phối cảnh rất nhẹ cho khu vực chọn màu trên thiết bị
           có chuột; màn hình cảm ứng không chạy để tránh giật khi cuộn. */
        var colorPanel = root.querySelector('[data-swatch-panel]');
        if (colorPanel && ! reduceMotion && window.matchMedia('(pointer: fine)').matches) {
            colorPanel.addEventListener('pointermove', function (e) {
                var rect = colorPanel.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width - 0.5;
                var y = (e.clientY - rect.top) / rect.height - 0.5;
                colorPanel.style.setProperty('--tilt-x', (-y * 2.2).toFixed(2) + 'deg');
                colorPanel.style.setProperty('--tilt-y', (x * 2.8).toFixed(2) + 'deg');
            });
            colorPanel.addEventListener('pointerleave', function () {
                colorPanel.style.setProperty('--tilt-x', '0deg');
                colorPanel.style.setProperty('--tilt-y', '0deg');
            });
        }

        var navLinks = nav ? Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]')) : [];
        var navSections = navLinks.map(function (link) {
            var id = link.getAttribute('href');
            return id && id.length > 1 ? root.querySelector(id) : null;
        });
        var ticking = false;

        function updateScrollState() {
            ticking = false;
            var maxScroll = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
            var pageProgress = Math.max(0, Math.min(1, window.scrollY / maxScroll));
            root.style.setProperty('--story-progress', pageProgress.toFixed(4));

            if (nav) nav.style.setProperty('--nav-progress', (pageProgress * 100).toFixed(2) + '%');

            if (hero) {
                var heroRect = hero.getBoundingClientRect();
                var heroProgress = Math.max(0, Math.min(1, -heroRect.top / Math.max(1, heroRect.height)));
                hero.style.setProperty('--hero-progress', heroProgress.toFixed(4));
                root.classList.toggle('story-cta-visible', heroRect.bottom < 180);
            }

            var active = -1;
            for (var i = 0; i < navSections.length; i++) {
                if (navSections[i] && navSections[i].getBoundingClientRect().top <= 210) active = i;
            }
            for (var j = 0; j < navLinks.length; j++) {
                navLinks[j].classList.toggle('is-active', j === active);
                if (j === active) navLinks[j].setAttribute('aria-current', 'location');
                else navLinks[j].removeAttribute('aria-current');
            }
        }

        function requestScrollUpdate() {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(updateScrollState);
        }

        updateScrollState();
        window.addEventListener('scroll', requestScrollUpdate, { passive: true });
        window.addEventListener('resize', requestScrollUpdate);
    }

    /* ── Tiêu đề hiện theo từng dòng ─────────────────────────────────
       Thủ pháp của các trang xe hạng sang: chữ không hiện cả khối mà
       trồi lên từng dòng một, lệch nhau một nhịp ngắn.

       Cách làm: bọc từng TỪ vào span để đo, gom các từ có cùng offsetTop
       thành một dòng, rồi thay bằng .line > span. Phải đợi font tải xong
       mới đo, vì font khác nhau ngắt dòng khác nhau.

       Nội dung chữ không đổi, chỉ thêm thẻ bọc — trình đọc màn hình và
       công cụ tìm kiếm vẫn thấy nguyên câu. */
    function splitIntoLines(el) {
        var text = el.textContent.replace(/\s+/g, ' ').trim();
        if (!text) return false;

        var words = text.split(' ');
        el.textContent = '';

        words.forEach(function (w, i) {
            var span = document.createElement('span');
            span.textContent = w;
            el.appendChild(span);
            if (i < words.length - 1) el.appendChild(document.createTextNode(' '));
        });

        var spans = Array.prototype.slice.call(el.children);
        var lines = [];
        var top = null;

        spans.forEach(function (span) {
            var y = span.offsetTop;
            if (top === null || Math.abs(y - top) > 4) {
                lines.push([]);
                top = y;
            }
            lines[lines.length - 1].push(span.textContent);
        });

        if (!lines.length) return false;

        el.textContent = '';
        lines.forEach(function (words, i) {
            var line = document.createElement('span');
            line.className = 'line';
            var inner = document.createElement('span');
            // Khoảng trắng cuối dòng không hiện ra (span là block) nhưng giữ
            // cho việc bôi đen copy không dính chữ hai dòng vào nhau.
            inner.textContent = words.join(' ') + (i < lines.length - 1 ? ' ' : '');
            inner.style.setProperty('--line-delay', (i * 90) + 'ms');
            line.appendChild(inner);
            el.appendChild(line);
        });

        el.classList.add('line-reveal');
        return true;
    }

    function initLineReveal() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (!('IntersectionObserver' in window)) return;

        var targets = Array.prototype.slice.call(document.querySelectorAll(
            '.home-story .tools__head h2,' +
            '.home-story .disc__head h2,' +
            '.home-story .offer h2,' +
            '.home-story .home-charge__head h2,' +
            '.home-story .home-editorial__head h2,' +
            '.home-story .home-feature .split__body h2,' +
            '.intro h2,' +
            '.product-story .story-section > .wrap > .section__head > h2'
        ));

        if (!targets.length) return;

        var ready = document.fonts && document.fonts.ready
            ? document.fonts.ready
            : Promise.resolve();

        ready.then(function () {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-revealed');
                    obs.unobserve(entry.target);
                });
            }, { threshold: 0.25, rootMargin: '0px 0px -8% 0px' });

            var split = [];

            targets.forEach(function (el) {
                // Tiêu đề đã nằm sẵn trong khung nhìn thì hiện luôn, không
                // bắt khách cuộn đi cuộn lại mới thấy chữ.
                if (!splitIntoLines(el)) return;
                split.push(el);
                var box = el.getBoundingClientRect();
                if (box.top < window.innerHeight * 0.9) {
                    requestAnimationFrame(function () { el.classList.add('is-revealed'); });
                } else {
                    obs.observe(el);
                }
            });

            // Lưới an toàn. Chữ đang bị đẩy ra ngoài khung .line, nên nếu vì
            // bất kỳ lý do gì observer không chạy (tab nền lúc tải, trình
            // duyệt lạ, lỗi ngoài dự đoán) thì tiêu đề sẽ trống vĩnh viễn.
            // Sau 6 giây, hiện hết những gì còn ẩn.
            setTimeout(function () {
                split.forEach(function (el) { el.classList.add('is-revealed'); });
            }, 6000);
        });
    }

    /* ── Ảnh đứng yên, chữ cuộn qua ───────────────────────────────────
       Mỗi đoạn nội dung khi vào giữa màn hình sẽ bật ảnh tương ứng. Dùng
       rootMargin thắt vào dải giữa để đúng một đoạn "đang đọc" tại mỗi thời
       điểm, thay vì mấy đoạn cùng sáng. */
    function initScrolly(root) {
        var shots = Array.prototype.slice.call(root.querySelectorAll('[data-scrolly-shot]'));
        var steps = Array.prototype.slice.call(root.querySelectorAll('[data-scrolly-step]'));

        if (!shots.length || !steps.length) return;

        if (!('IntersectionObserver' in window)) {
            shots.forEach(function (s) { s.classList.add('is-on'); });
            steps.forEach(function (s) { s.classList.add('is-on'); });
            return;
        }

        function show(i) {
            shots.forEach(function (s, k) { s.classList.toggle('is-on', k === i); });
            steps.forEach(function (s, k) { s.classList.toggle('is-on', k === i); });
        }

        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                show(parseInt(entry.target.getAttribute('data-scrolly-step'), 10) || 0);
            });
        }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });

        steps.forEach(function (step) { obs.observe(step); });
    }

    /* ── Chấm tương tác trên ảnh ──────────────────────────────────────
       Rê chuột đã hiện chú thích qua CSS; JS chỉ lo phần bấm (máy tính
       bảng lai) và đóng khi bấm ra ngoài hoặc bấm Esc. */
    function initHotspot(root) {
        var pins = Array.prototype.slice.call(root.querySelectorAll('[data-hotspot-pin]'));
        if (!pins.length) return;

        function closeAll(except) {
            pins.forEach(function (p) {
                if (p !== except) p.setAttribute('aria-expanded', 'false');
            });
        }

        pins.forEach(function (pin) {
            pin.addEventListener('click', function (e) {
                e.stopPropagation();
                var mo = pin.getAttribute('aria-expanded') === 'true';
                closeAll(pin);
                pin.setAttribute('aria-expanded', mo ? 'false' : 'true');
            });
        });

        document.addEventListener('click', function () { closeAll(null); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll(null);
        });
    }

    /* ── Băng ảnh trôi chậm hơn trang ─────────────────────────────────
       Ảnh cao hơn khung 18% (CSS lo phần đó), JS chỉ tính độ trượt theo vị
       trí khung so với giữa màn hình. Một hàm dùng chung cho mọi trang —
       parallax của trang chủ nằm trong initHomeStory và chỉ chạy ở đó. */
    function initBleed() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-bleed]'));
        if (!nodes.length) return;

        var ticking = false;

        function update() {
            ticking = false;
            var vh = window.innerHeight;

            nodes.forEach(function (node) {
                var rect = node.getBoundingClientRect();
                if (rect.bottom < -200 || rect.top > vh + 200) return;

                var frame = node.querySelector('.bleed__frame');
                if (!frame) return;

                // -1 khi khung nằm dưới đáy màn, +1 khi đã trôi lên trên đỉnh.
                var tien = (rect.top + rect.height / 2 - vh / 2) / Math.max(1, vh);
                tien = Math.max(-1, Math.min(1, tien));

                // Biên độ giữ trong 9% chiều cao khung để mép ảnh không hở ra.
                frame.style.setProperty('--bleed-shift', (tien * rect.height * 0.09).toFixed(1) + 'px');
            });
        }

        function request() {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(update);
        }

        update();
        window.addEventListener('scroll', request, { passive: true });
        window.addEventListener('resize', request);
    }

    /* ── Hai nút cho dải cuộn ngang ───────────────────────────────────
       Cuộn và bám mép đã do CSS lo; JS chỉ hiện nút cho người dùng chuột và
       tắt nút khi đã chạm đầu/cuối dải. */
    function initHStrip(root) {
        var rail = root.querySelector('[data-hstrip-rail]');
        var nav = root.querySelector('[data-hstrip-nav]');
        var prev = root.querySelector('[data-hstrip-prev]');
        var next = root.querySelector('[data-hstrip-next]');

        if (!rail || !nav || !prev || !next) return;

        // Dải không tràn thì không cần nút.
        if (rail.scrollWidth <= rail.clientWidth + 4) return;
        nav.hidden = false;

        function buoc() {
            var item = rail.firstElementChild;
            return item ? item.getBoundingClientRect().width + 24 : rail.clientWidth * 0.8;
        }

        function capNhat() {
            prev.disabled = rail.scrollLeft <= 2;
            next.disabled = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 2;
        }

        prev.addEventListener('click', function () { rail.scrollBy({ left: -buoc(), behavior: 'smooth' }); });
        next.addEventListener('click', function () { rail.scrollBy({ left: buoc(), behavior: 'smooth' }); });
        rail.addEventListener('scroll', capNhat, { passive: true });
        window.addEventListener('resize', capNhat);
        capNhat();
    }

    /* ── Tìm trạm sạc: nhập vị trí → sắp theo khoảng cách → chỉ đường ─
       Chưa nối API thì lọc ngay trên danh sách server đã render (đọc lại
       dưới dạng JSON ở [data-finder-seed]); có data-endpoint thì gọi API rồi
       vẽ lại bằng đúng khung thẻ <template> của Blade. Endpoint hỏng thì
       quay về danh sách sẵn có chứ không để khung trắng.

       Tắt JS: danh sách tĩnh vẫn đọc được, nút chỉ đường vẫn mở Google Maps,
       form submit sang trang Trạm sạc & dịch vụ. */
    function initStationFinder(root) {
        var input = root.querySelector('[data-finder-input]');
        var list = root.querySelector('[data-finder-results]');
        var tpl = root.querySelector('[data-finder-template]');
        var seedTag = root.querySelector('[data-finder-seed]');
        var status = root.querySelector('[data-finder-status]');
        var locate = root.querySelector('[data-finder-locate]');
        if (!input || !list || !tpl || !seedTag) return;

        var endpoint = root.dataset.endpoint || '';
        var seed = [];
        var timer = null;
        var luot = 0;

        try { seed = JSON.parse(seedTag.textContent) || []; } catch (e) { seed = []; }

        // Bỏ dấu để "vinh yen" vẫn khớp "Vĩnh Yên".
        function chuanHoa(text) {
            return (text == null ? '' : String(text)).toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
        }

        // Khách gõ "21.27,106.19" (hoặc bấm nút định vị) thì coi là toạ độ,
        // không lọc theo chữ nữa mà sắp theo khoảng cách.
        function docToaDo(text) {
            var m = /^\s*(-?\d{1,3}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/.exec(text || '');
            return m ? { lat: parseFloat(m[1]), lng: parseFloat(m[2]) } : null;
        }

        function khoangCach(a, b) {
            var R = 6371, rad = Math.PI / 180;
            var dLat = (b.lat - a.lat) * rad, dLng = (b.lng - a.lng) * rad;
            var h = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
        }

        function soKm(km) {
            return (km < 10 ? km.toFixed(1) : String(Math.round(km))).replace('.', ',');
        }

        function duongDan(tram, diemDi) {
            var den = (tram.lat != null && tram.lng != null)
                ? tram.lat + ',' + tram.lng
                : ((tram.name || '') + ' ' + (tram.address || '')).trim();
            var url = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(den);
            return diemDi ? url + '&origin=' + encodeURIComponent(diemDi) : url;
        }

        function xepHang(ds, tuKhoa, toaDo) {
            var tu = chuanHoa(tuKhoa).split(/\s+/).filter(Boolean);
            var loc = (toaDo || !tu.length) ? ds.slice() : ds.filter(function (t) {
                var kho = chuanHoa([t.name, t.status, t.info, t.address].join(' '));
                return tu.every(function (x) { return kho.indexOf(x) > -1; });
            });

            loc.forEach(function (t) {
                t.km = typeof t.distance === 'number' ? t.distance
                    : (toaDo && t.lat != null && t.lng != null) ? khoangCach(toaDo, t) : null;
            });

            // Trạm chưa có toạ độ xuống cuối chứ không biến mất.
            if (toaDo) loc.sort(function (a, b) {
                if (a.km == null) return b.km == null ? 0 : 1;
                if (b.km == null) return -1;
                return a.km - b.km;
            });

            return loc;
        }

        function dat(el, chu) {
            if (!el) return;
            el.textContent = chu || '';
            el.hidden = !chu;
        }

        function ve(ds, diemDi) {
            list.textContent = '';

            if (!ds.length) {
                var trong = document.createElement('li');
                trong.className = 'finder__empty';
                trong.textContent = 'Không có trạm nào khớp. Thử tên phường/xã, thành phố hoặc bấm nút định vị.';
                list.appendChild(trong);
                return;
            }

            ds.forEach(function (t) {
                var the = tpl.content.firstElementChild.cloneNode(true);
                var o = {};
                the.querySelectorAll('[data-f]').forEach(function (el) { o[el.dataset.f] = el; });

                dat(o.name, t.name);
                dat(o.status, t.status);
                if (o.status) o.status.classList.toggle('is-warn', t.tone === 'warn');
                dat(o.info, [t.info, t.address].filter(Boolean).join(' · '));
                dat(o.dist, t.km == null ? '' : 'Cách ' + soKm(t.km) + ' km');
                if (o.go) o.go.href = duongDan(t, diemDi);

                list.appendChild(the);
            });
        }

        function bao(chu, canh) {
            if (!status) return;
            status.textContent = chu;
            status.classList.toggle('is-warn', !!canh);
        }

        function hienThi(ds, diemDi, tuKhoa) {
            ve(ds, diemDi);
            if (!ds.length) bao('Không có trạm nào khớp', true);
            else bao(ds.length + (tuKhoa ? ' trạm gần vị trí của bạn' : ' trạm trong khu vực'));
        }

        function chay() {
            var q = input.value.trim();
            var toaDo = docToaDo(q);
            var diemDi = q || null;

            if (!endpoint) {
                hienThi(xepHang(seed, q, toaDo), diemDi, q);
                return;
            }

            var id = ++luot;
            var thamSo = [];
            if (q && !toaDo) thamSo.push('q=' + encodeURIComponent(q));
            if (toaDo) thamSo.push('lat=' + toaDo.lat, 'lng=' + toaDo.lng);
            bao('Đang tìm trạm…');

            fetch(endpoint + (endpoint.indexOf('?') > -1 ? '&' : '?') + thamSo.join('&'), {
                headers: { Accept: 'application/json' }
            })
                .then(function (r) {
                    if (!r.ok) throw new Error(r.status);
                    return r.json();
                })
                .then(function (json) {
                    if (id !== luot) return;   // câu trả lời cũ về muộn thì bỏ
                    var ds = Array.isArray(json) ? json : (json && json.data) || [];
                    hienThi(xepHang(ds, q, toaDo), diemDi, q);
                })
                .catch(function () {
                    if (id !== luot) return;
                    // API lỗi thì hiện toàn bộ danh sách dự phòng; không lọc
                    // bằng địa chỉ vừa nhập vì dữ liệu tĩnh có thể ở tỉnh khác.
                    hienThi(xepHang(seed, '', toaDo), diemDi, null);
                    bao('Chưa gọi được dịch vụ tìm trạm — đang hiện danh sách có sẵn.', true);
                });
        }

        root.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(timer);
            chay();
        });

        input.addEventListener('input', function () {
            // API miễn phí chỉ gọi khi khách bấm Tìm. Khi chưa nối API vẫn
            // giữ trải nghiệm lọc nhanh danh sách tĩnh theo từng ký tự.
            if (endpoint) return;
            clearTimeout(timer);
            timer = setTimeout(chay, 300);
        });

        if (locate && navigator.geolocation) {
            locate.addEventListener('click', function () {
                locate.classList.add('is-on');
                bao('Đang lấy vị trí của bạn…');

                navigator.geolocation.getCurrentPosition(function (pos) {
                    locate.classList.remove('is-on');
                    input.value = pos.coords.latitude.toFixed(5) + ',' + pos.coords.longitude.toFixed(5);
                    clearTimeout(timer);
                    chay();
                }, function () {
                    locate.classList.remove('is-on');
                    bao('Chưa lấy được vị trí — nhập địa chỉ giúp nhé.', true);
                }, { timeout: 8000, maximumAge: 300000 });
            });
        } else if (locate) {
            locate.hidden = true;
        }

        // Vào trang: vẽ lại từ seed cho khoảng cách/điểm đi đi cùng một đường
        // vẽ. Chỉ gọi API ngay khi ô đã có sẵn chữ (quay lại từ trang kết quả).
        if (endpoint && input.value.trim()) chay();
        else hienThi(xepHang(seed, input.value.trim(), docToaDo(input.value)), input.value.trim() || null, input.value.trim());
    }

    function boot() {
        initNavigation();
        document.querySelectorAll('[data-hero]').forEach(initHero);
        document.querySelectorAll('[data-disc]').forEach(initDiscovery);
        document.querySelectorAll('[data-swatches]').forEach(initSwatches);
        document.querySelectorAll('[data-gallery]').forEach(initGallery);
        document.querySelectorAll('[data-tabs]').forEach(initTabs);
        document.querySelectorAll('.booking').forEach(initBooking);
        document.querySelectorAll('[data-popup]').forEach(initPopup);
        document.querySelectorAll('[data-lead-form]').forEach(initLeadForm);
        document.querySelectorAll('[data-home-story]').forEach(initHomeStory);
        document.querySelectorAll('[data-product-story]').forEach(initProductStory);
        document.querySelectorAll('[data-scrolly]').forEach(initScrolly);
        document.querySelectorAll('[data-hotspot]').forEach(initHotspot);
        document.querySelectorAll('[data-hstrip]').forEach(initHStrip);
        document.querySelectorAll('[data-finder]').forEach(initStationFinder);
        initBleed();
        initLineReveal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

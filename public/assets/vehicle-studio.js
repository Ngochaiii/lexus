/* A photographic turntable: no simulated paint or CSS 3D. */
(() => {
    'use strict';
    document.querySelectorAll('[data-vehicle-studio]').forEach(root => {
        const data = JSON.parse(root.querySelector('[data-studio-data]').textContent);
        const $ = selector => root.querySelector(selector);
        const stage = $('[data-stage]'), image = $('[data-studio-image]');
        const status = $('[data-status]'), controls = $('[data-controls]');
        const range = $('[data-angle]'), angleLabel = $('[data-angle-label]');
        const swatches = [...root.querySelectorAll('[data-color]')];
        const cache = new Map();
        let selected = 0, frame = 0, generation = 0, pointer = null, displayedAlt = image.alt;
        const look = () => data.options[selected];
        // ≥ 12 khung chụp bàn xoay = xoay 360° thật (hiện độ). 2–11 khung = các
        // góc nhìn rời (trước, ngang, sau…) — vẫn kéo/bấm để đổi góc, nhưng
        // nhãn ghi "Góc 2/5" chứ không giả làm 360°.
        const canSpin = () => look().frames.length >= 2;
        const isFull = () => look().frames.length >= 12;
        function fetchImage(url) {
            if (cache.has(url)) return cache.get(url);
            const promise = new Promise((resolve, reject) => {
                const img = new Image();
                const timer = setTimeout(() => finish(false), 12000);
                const finish = ok => {
                    clearTimeout(timer); img.onload = img.onerror = null;
                    if (ok) resolve(img); else { cache.delete(url); reject(new Error('image')); }
                };
                img.onload = () => finish(true); img.onerror = () => finish(false); img.src = url;
            });
            cache.set(url, promise);
            if (cache.size > 32) cache.delete(cache.keys().next().value);
            return promise;
        }
        async function show(nextFrame = 0) {
            const version = ++generation, option = look();
            frame = canSpin() ? (nextFrame + option.frames.length) % option.frames.length : 0;
            const url = option.frames[frame] || option.image || data.fallback;
            const label = (option.frames.length || option.image) ? option.name : 'Ảnh tổng quan';
            stage.setAttribute('aria-busy', 'true');
            status.textContent = 'Đang tải hình ảnh…';
            try {
                const loaded = await fetchImage(url);
                if (version !== generation) return;
                image.src = loaded.src;
                displayedAlt = `${data.model} — ${label}${canSpin() ? ` — góc ${frame + 1}/${option.frames.length}` : ''}`;
                image.alt = displayedAlt;
                range.value = String(frame);
                const degrees = Math.round(frame * 360 / Math.max(1, option.frames.length));
                const position = isFull() ? `${degrees}°` : `${frame + 1}/${option.frames.length}`;
                range.setAttribute('aria-valuetext', isFull() ? `${degrees} độ` : `góc ${frame + 1} trên ${option.frames.length}`);
                angleLabel.value = position;
                status.textContent = (option.image || option.frames.length)
                    ? 'Màu sắc hiển thị có thể khác thực tế tùy màn hình và phiên bản.'
                    : 'Chưa có ảnh riêng cho màu này. Đang hiển thị ảnh tổng quan; hãy liên hệ để xem màu thực tế.';
                $('[data-expand]').disabled = false;
                if (canSpin()) fetchImage(option.frames[(frame + 1) % option.frames.length]).catch(() => {});
            } catch (_) {
                if (version !== generation) return;
                // Keep the last valid picture and its original alt text.
                status.textContent = 'Không tải được ảnh đã chọn. Đang giữ ảnh trước đó. Chọn lại màu hoặc góc quay để thử lại.';
            } finally {
                if (version === generation) stage.setAttribute('aria-busy', 'false');
            }
        }
        // Ảnh nhỏ (< 900px) không phóng quá 2 lần cho khỏi vỡ hạt — tính theo
        // màu đang chọn, để một màu ảnh nhỏ không làm co cả sân khấu.
        function fitStage() {
            const w = look().w;
            const small = w && w < 900;
            stage.classList.toggle('is-small', !!small);
            if (small) stage.style.setProperty('--stage-native', `${Math.max(600, w * 2)}px`);
            else stage.style.removeProperty('--stage-native');
        }
        function choose(index) {
            selected = index; pointer = null;
            fitStage();
            swatches.forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
            $('[data-color-name]').textContent = look().name;
            controls.hidden = !canSpin();
            stage.classList.toggle('is-rotatable', canSpin());
            stage.tabIndex = canSpin() ? 0 : -1;
            stage.setAttribute('aria-label', canSpin() ? `Xoay ${data.model}, dùng phím trái và phải hoặc kéo ngang` : `Ngoại thất ${data.model}`);
            range.max = String(Math.max(0, look().frames.length - 1));
            $('[data-mode]').textContent = isFull() ? '360° / NGOẠI THẤT' : canSpin() ? `${look().frames.length} GÓC NHÌN / NGOẠI THẤT` : 'NGOẠI THẤT';
            $('[data-hint]').textContent = canSpin()
                ? (isFull() ? 'Kéo ngang để xoay xe · Hoặc dùng phím ← →' : 'Kéo ngang hoặc bấm ← → để xem xe từ các góc')
                : 'Hình ảnh ngoại thất · Chọn màu để khám phá';
            show(0);
        }
        swatches.forEach((button, index) => {
            button.disabled = false;
            button.addEventListener('click', () => choose(index));
        });
        $('[data-prev]').addEventListener('click', () => show(frame - 1));
        $('[data-next]').addEventListener('click', () => show(frame + 1));
        range.addEventListener('input', () => show(Number(range.value)));
        stage.addEventListener('keydown', event => {
            if (event.target !== stage || !canSpin() || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            show(event.key === 'Home' ? 0 : event.key === 'End' ? look().frames.length - 1 : frame + (event.key === 'ArrowRight' ? 1 : -1));
        });
        stage.addEventListener('pointerdown', event => {
            if (!canSpin() || event.button !== 0 || event.target.closest('button')) return;
            pointer = {id:event.pointerId,x:event.clientX,frame};
            stage.setPointerCapture(event.pointerId);
        });
        stage.addEventListener('pointermove', event => {
            if (!pointer || pointer.id !== event.pointerId) return;
            const step = Math.round((pointer.x - event.clientX) / Math.max(8, stage.clientWidth / look().frames.length));
            const target = (pointer.frame + step % look().frames.length + look().frames.length) % look().frames.length;
            if (target !== frame) show(target);
        });
        ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(type => stage.addEventListener(type, () => {pointer = null;}));
        const dialog = $('[data-studio-dialog]'), expand = $('[data-expand]');
        expand.hidden = typeof dialog.showModal !== 'function';
        expand.addEventListener('click', () => {
            $('[data-large-image]').src = image.src; $('[data-large-image]').alt = displayedAlt;
            dialog.showModal();
        });
        $('[data-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {if (event.target === dialog) dialog.close();});
        dialog.addEventListener('close', () => expand.focus());
        // Load on approach, not alongside the page hero. No automatic rotation.
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting)) { observer.disconnect(); choose(selected); }
            }, {rootMargin:'200px'});
            observer.observe(root);
        } else choose(0);
    });
})();


<?php
    $cfg      = (array) config('catalog.frontend.popup', []);
    $autoOpen = ($autoOpen ?? false) && ($cfg['enabled'] ?? true);
?>
<dialog class="quote-dialog" id="quote-dialog" aria-labelledby="quote-dialog-title"
        data-quote-path="<?php echo e(parse_url(route('quote'), PHP_URL_PATH)); ?>"
        data-manual-action="<?php echo e(route('leads.store', 'nhan-bao-gia')); ?>"
        data-auto-action="<?php echo e(route('leads.store', 'popup-bao-gia')); ?>"
        <?php if($autoOpen): ?>
            data-auto-delay="<?php echo e((int) ($cfg['delay'] ?? 20)); ?>"
            data-auto-mobile="<?php echo e(($cfg['mobile'] ?? false) ? 1 : 0); ?>"
            data-dismiss-days="<?php echo e((int) ($cfg['dismiss_days'] ?? 7)); ?>"
            data-sent-days="<?php echo e((int) ($cfg['sent_days'] ?? 90)); ?>"
        <?php endif; ?>>

    
    <form method="dialog" class="quote-dialog__close-form">
        <button class="quote-dialog__close" aria-label="Đóng">×</button>
    </form>

    
    <?php
        $dealer  = catalog_setting('site_name', 'Lexus Thăng Long');
        $advisor = catalog_setting('advisor_name');
    ?>
    <figure class="quote-dialog__media">
        <img src="<?php echo e(asset('assets/personal/welcome-1200.webp')); ?>"
             srcset="<?php echo e(asset('assets/personal/welcome-640.webp')); ?> 640w,
                     <?php echo e(asset('assets/personal/welcome-1200.webp')); ?> 1200w"
             sizes="(max-width: 700px) 0px, 400px"
             width="1200" height="2191" loading="lazy" decoding="async"
             alt="<?php echo e($advisor ? $advisor.', chuyên viên tư vấn, tại quầy lễ tân '.$dealer : 'Quầy lễ tân '.$dealer); ?>">
        <figcaption>
            <span><?php echo e(mb_strtoupper($dealer)); ?></span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($advisor): ?>
                <p><?php echo e($advisor); ?> — người sẽ gửi báo giá cho bạn.</p>
            <?php else: ?>
                <p>Hân hạnh được đón tiếp bạn tại showroom.</p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </figcaption>
    </figure>

    <div class="quote-dialog__body">
        <div class="eyebrow">BÁO GIÁ LEXUS</div>
        <h2 id="quote-dialog-title">Báo giá lăn bánh,<br>gửi riêng cho bạn.</h2>
        <p class="quote-dialog__lede">Chọn dòng xe bạn quan tâm. Chuyên viên tư vấn sẽ gửi báo giá chi tiết
            từng khoản và phương án sở hữu phù hợp.</p>

        
        <?php echo $__env->make('frontend.partials.lead-form', [
            'formKey'     => 'nhan-bao-gia',
            'submitLabel' => 'Nhận báo giá',
            'instance'    => 'popup',
            'products'    => null,
            'selected'    => null,
            'note'        => 'Không phát sinh chi phí. Thông tin của bạn chỉ dùng cho mục đích tư vấn.',
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</dialog>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/quote-dialog.blade.php ENDPATH**/ ?>

<?php
    $dealer   = catalog_setting('site_name', 'Lexus Thăng Long');
    $advisor  = catalog_setting('advisor_name');
    $phone    = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $phoneFmt = \App\Support\Phone::format($phone);
    $zaloRaw  = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo     = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
    $maps     = catalog_setting('map_url');
    $company  = catalog_setting('company_name', $dealer);

    $footerModels = once(fn () => \App\Support\Catalog::query('product')
        ->published()->orderBy('sort')->take(6)->get());
?>
<footer class="site-footer">
    <div class="footer-top">
        <div class="footer-brand">
            <a class="brand" href="<?php echo e(route('home')); ?>" aria-label="<?php echo e($dealer); ?> — Trang chủ">
                <img class="brand-logo" src="<?php echo e(asset('assets/logo-ltl-white.webp')); ?>"
                     alt="<?php echo e($dealer); ?>" width="800" height="68" loading="lazy" decoding="async">
            </a>
            <p>Tinh hoa trong từng chi tiết.<br>Cảm hứng trên mọi hành trình.</p>
        </div>

        <div>
            <h3>Dòng xe</h3>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $footerModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <a href="<?php echo e(route('products.show', $item->slug)); ?>"><?php echo e($item->name); ?></a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>

        <div>
            <h3>Mua xe</h3>
            <a href="<?php echo e(route('pages.show', 'bang-gia')); ?>">Bảng giá xe</a>
            <a href="<?php echo e(route('pages.show', 'uu-dai')); ?>">Đặc quyền sở hữu</a>
            <a href="<?php echo e(route('pages.show', 'tai-chinh')); ?>">Giải pháp tài chính</a>
            <a href="<?php echo e(route('booking')); ?>">Đăng ký lái thử</a>
        </div>

        <div>
            <h3>Trải nghiệm Lexus</h3>
            <a href="<?php echo e(route('pages.show', 'the-gioi-lexus')); ?>">Tinh hoa Takumi</a>
            <a href="<?php echo e(route('pages.show', 'the-gioi-lexus')); ?>#omotenashi">Omotenashi</a>
            <a href="<?php echo e(route('pages.show', 'dich-vu')); ?>">Dịch vụ &amp; chăm sóc</a>
            <a href="<?php echo e(route('posts.index')); ?>">Câu chuyện Lexus</a>
        </div>

        <div>
            <h3>Kết nối</h3>
            <a href="<?php echo e(route('home')); ?>#chuyen-vien">Người tư vấn của bạn</a>
            <a href="<?php echo e(route('pages.show', 'showroom')); ?>">Showroom</a>
            <a href="<?php echo e(route('pages.show', 'lien-he')); ?>">Liên hệ tư vấn</a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phone): ?><a href="tel:<?php echo e($phone); ?>"><?php echo e($advisor ? $advisor.' · ' : ''); ?><?php echo e($phoneFmt); ?></a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($zalo): ?><a href="<?php echo e($zalo); ?>" rel="noopener">Zalo: <?php echo e($phoneFmt); ?></a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($maps): ?><a href="<?php echo e($maps); ?>" rel="noopener" target="_blank"><?php echo e($dealer); ?></a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <a href="<?php echo e(route('pages.show', 'faq')); ?>">Câu hỏi thường gặp</a>
        </div>
    </div>

    <div class="footer-bottom">
        <div>© <?php echo e(date('Y')); ?> <?php echo e($company); ?>. Website tư vấn cá nhân của chuyên viên bán hàng,
            không phải website chính thức của Lexus Việt Nam.<br>Giá là thông tin tham khảo, vui lòng liên hệ
            để nhận báo giá chính thức. Hình ảnh xe có thể khác phiên bản tại Việt Nam.</div>
        <span>
            <a href="<?php echo e(route('pages.show', 'quyen-rieng-tu')); ?>">Quyền riêng tư</a> &nbsp; / &nbsp;
            <a href="<?php echo e(route('pages.show', 'dieu-khoan')); ?>">Điều khoản</a>
        </span>
    </div>
</footer>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/footer.blade.php ENDPATH**/ ?>
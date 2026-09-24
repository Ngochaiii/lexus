
<?php
    $overlay = $overlay ?? false;

    // Mega menu lấy xe thật. Cache trong request để mọi trang chỉ truy vấn
    // một lần dù header render ở layout.
    $navModels = once(fn () => \App\Support\Catalog::query('product')
        ->published()->with('category')->orderBy('sort')->take(8)->get());

    $navCategories = once(fn () => \App\Support\Catalog::query('category')
        ->orderBy('sort')->get());

    $spotlight = $navModels->first();
?>
<header class="site-header <?php echo e($overlay ? 'over-hero' : ''); ?>">
    <a class="brand" href="<?php echo e(route('home')); ?>" aria-label="<?php echo e(catalog_setting('site_name', 'Lexus Thăng Long')); ?> — Trang chủ">
        <img class="brand-logo"
             src="<?php echo e(asset('assets/logo-ltl-'.($overlay ? 'white' : 'black').'.webp')); ?>"
             alt="<?php echo e(catalog_setting('site_name', 'Lexus Thăng Long')); ?>"
             width="800" height="68" decoding="async">
    </a>

    <nav class="desktop-nav" aria-label="Điều hướng chính">
        <details>
            <summary>Dòng xe</summary>
            <div class="mega">
                <div>
                    <span class="eyebrow">Bộ sưu tập Lexus</span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e(route('categories.show', $c->slug)); ?>"><?php echo e($c->name); ?></a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <a href="<?php echo e(route('products.index')); ?>">Tất cả dòng xe ↗</a>
                </div>
                <div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e(route('products.show', $m->slug)); ?>"><?php echo e($m->name); ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($m->category): ?><small>— <?php echo e($m->category->name); ?></small><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($spotlight): ?>
                    <div>
                        <img src="<?php echo e(catalog_image(data_get($spotlight->hero, 'src')) ?: asset('assets/rx.webp')); ?>"
                             alt="<?php echo e($spotlight->name); ?>"
                             width="1600" height="1067" loading="lazy" decoding="async">
                        <a href="<?php echo e(route('products.show', $spotlight->slug)); ?>">Khám phá <?php echo e($spotlight->name); ?> ↗</a>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </details>
        <a href="<?php echo e(route('pages.show', 'bang-gia')); ?>">Mua xe</a>
        <a href="<?php echo e(route('pages.show', 'the-gioi-lexus')); ?>">Thế giới Lexus</a>
        <a href="<?php echo e(route('pages.show', 'dich-vu')); ?>">Dịch vụ</a>
        <a href="<?php echo e(route('home')); ?>#chuyen-vien">Người đồng hành</a>
    </nav>

    <a class="header-cta" href="<?php echo e(route('booking')); ?>">ĐẶT LỊCH LÁI THỬ ↗</a>

    <details class="mobile-menu">
        <summary><span class="menu-open">MENU ☰</span><span class="menu-close">ĐÓNG ✕</span></summary>
        <nav aria-label="Điều hướng di động">
            <a href="<?php echo e(route('home')); ?>#chuyen-vien">Người đồng hành</a>
            <a href="<?php echo e(route('products.index')); ?>">Dòng xe Lexus</a>
            <a href="<?php echo e(route('pages.show', 'bang-gia')); ?>">Bảng giá &amp; mua xe</a>
            <a href="<?php echo e(route('pages.show', 'the-gioi-lexus')); ?>">Thế giới Lexus</a>
            <a href="<?php echo e(route('pages.show', 'dich-vu')); ?>">Dịch vụ &amp; chăm sóc</a>
            <a href="<?php echo e(route('posts.index')); ?>">Tin tức</a>
            <a href="<?php echo e(route('pages.show', 'showroom')); ?>">Showroom &amp; liên hệ</a>
            <a class="small" href="<?php echo e(route('booking')); ?>">Đặt lịch lái thử ↗</a>
            <a class="small" href="<?php echo e(route('quote')); ?>" data-quote>Nhận báo giá ↗</a>
        </nav>
    </details>
</header>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/header.blade.php ENDPATH**/ ?>

<?php
    $advisor = catalog_setting('advisor_name');
    $phone   = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $zaloRaw = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo    = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
?>
<nav class="sales-bar" aria-label="Tư vấn nhanh">
    <a href="<?php echo e(route('quote')); ?>" data-quote>Nhận báo giá</a>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phone): ?><a href="tel:<?php echo e($phone); ?>">Gọi <?php echo e($advisor ?: 'tư vấn'); ?></a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($zalo): ?><a href="<?php echo e($zalo); ?>" rel="noopener">Zalo</a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</nav>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/sales-bar.blade.php ENDPATH**/ ?>
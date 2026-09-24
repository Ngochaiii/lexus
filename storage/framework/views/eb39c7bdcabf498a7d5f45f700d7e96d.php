
<?php
    $dealer   = catalog_setting('site_name');
    $address  = catalog_setting('address');
    $mapUrl   = catalog_setting('map_url');
    $advisor  = catalog_setting('advisor_name');
    $phone    = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $zalo     = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $hours    = catalog_setting('opening_hours');

    $phoneFmt = \App\Support\Phone::format($phone);
?>
<div class="contact-details">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($address)): ?>
        <div>
            <span>Showroom <?php echo e($dealer); ?></span>
            <p><?php echo nl2br(e($address)); ?></p>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($mapUrl)): ?>
                <a class="small" href="<?php echo e($mapUrl); ?>" rel="noopener" target="_blank">Chỉ đường trên Google Maps ↗</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($phone)): ?>
        <div>
            <span>Chuyên viên tư vấn</span>
            <p><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($advisor): ?><?php echo e($advisor); ?> &nbsp; — &nbsp; <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><a href="tel:<?php echo e($phone); ?>"><?php echo e($phoneFmt); ?></a></p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($zalo)): ?>
        <div>
            <span>Zalo</span>
            <a href="<?php echo e(\Illuminate\Support\Str::startsWith($zalo, 'http') ? $zalo : 'https://zalo.me/'.$zalo); ?>"
               rel="noopener">Nhắn Zalo <?php echo e($phoneFmt); ?> ↗</a>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($hours)): ?>
        <div>
            <span>Giờ đón tiếp</span>
            <p><?php echo e($hours); ?></p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/contact-details.blade.php ENDPATH**/ ?>
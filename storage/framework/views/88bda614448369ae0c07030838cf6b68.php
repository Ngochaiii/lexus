
<div class="container" id="muc-<?php echo e($index); ?>">
    <div class="notice">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($title): ?><span class="notice__label"><?php echo e($title); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php echo catalog_rich_text($section['body'] ?? $intro ?? ''); ?>

    </div>
</div>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/lexus-section/notice.blade.php ENDPATH**/ ?>
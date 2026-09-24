
<?php
    $advisor = catalog_setting('advisor_name', 'Chuyên viên tư vấn');
    $role    = catalog_setting('advisor_role', 'Chuyên viên tư vấn');
    $photo   = catalog_image(catalog_setting('advisor_image'));
?>
<a class="advisor-mini" href="<?php echo e(route('home')); ?>#chuyen-vien">
    <img class="advisor-avatar"
         src="<?php echo e($photo ?: asset('assets/personal/portrait-960.webp')); ?>"
         <?php if (! ($photo)): ?>
             srcset="<?php echo e(asset('assets/personal/portrait-320.webp')); ?> 320w,
                     <?php echo e(asset('assets/personal/portrait-640.webp')); ?> 640w,
                     <?php echo e(asset('assets/personal/portrait-960.webp')); ?> 960w"
         <?php endif; ?>
         sizes="76px" width="960" height="960"
         alt="Chân dung <?php echo e($advisor); ?>"
         loading="lazy" decoding="async">
    <span>
        <span class="eyebrow">NGƯỜI TƯ VẤN CỦA BẠN</span>
        <strong><?php echo e($advisor); ?></strong>
        <span><?php echo e($role); ?></span>
        <span class="advisor-mini-link">Tìm hiểu người đồng hành ↗</span>
    </span>
</a>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/advisor-mini.blade.php ENDPATH**/ ?>
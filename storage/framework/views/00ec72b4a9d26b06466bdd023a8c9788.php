
<?php
    $form = \App\Support\Catalog::query('form')->with('fields')
        ->where('key', $section['form_key'] ?? '')->where('is_active', true)->first();
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($form): ?>
    <section class="section stone" id="muc-<?php echo e($index); ?>">
        <div class="container lead-layout">
            <div class="lead-copy">
                <div class="eyebrow"><?php echo e(mb_strtoupper($title ?? $form->name)); ?></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($intro): ?><h2><?php echo e($intro); ?></h2><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php echo $__env->make('frontend.partials.lead-form', ['formKey' => $form->key, 'submitLabel' => $form->name], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </section>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/lexus-section/form.blade.php ENDPATH**/ ?>
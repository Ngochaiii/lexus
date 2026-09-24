
<?php
    $numbered = $numbered ?? true;
    $anchors  = [];
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
    <?php
        $type   = $section['type'] ?? 'media';
        $layout = $section['layout'] ?? 'cols-3';
        $title  = $section['title'] ?? null;
        $intro  = $section['intro'] ?? null;
        $items  = $section['items'] ?? [];
        $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $anchor = \Illuminate\Support\Str::slug((string) $title);
        $anchor = ($anchor !== '' && ! isset($anchors[$anchor])) ? $anchor : null;
        if ($anchor) {
            $anchors[$anchor] = true;
        }
    ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anchor): ?><span class="section-anchor" id="<?php echo e($anchor); ?>"></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if ($__env->exists('frontend.partials.lexus-section.'.$type, [
        'section' => $section,
        'layout'  => $layout,
        'title'   => $title,
        'intro'   => $intro,
        'items'   => $items,
        'number'   => $number,
        'index'    => $index,
        'numbered' => $numbered,
        'eager'    => ($eagerFirst ?? false) && $loop->first,
    ])) echo $__env->make('frontend.partials.lexus-section.'.$type, [
        'section' => $section,
        'layout'  => $layout,
        'title'   => $title,
        'intro'   => $intro,
        'items'   => $items,
        'number'   => $number,
        'index'    => $index,
        'numbered' => $numbered,
        'eager'    => ($eagerFirst ?? false) && $loop->first,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/sections.blade.php ENDPATH**/ ?>
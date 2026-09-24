
<?php
    $product  = $variant->product;
    $isHybrid = (bool) preg_match('/\d{3}h\b/i', $variant->name);
    $cats     = trim(($product->category?->slug ?? '').($isHybrid ? ' hybrid' : ''));
    $img      = catalog_image($variant->image) ?: catalog_image(data_get($product->hero, 'src'));
    $title    = str_starts_with($variant->name, 'Lexus') ? $variant->name : 'Lexus '.$variant->name;
?>
<article class="model-card variant-card" data-category="<?php echo e($cats); ?>">
    <a class="model-image" href="<?php echo e(route('products.show', $product->slug)); ?>#versions">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($img): ?>
            <?php if (isset($component)) { $__componentOriginalef003b7812b51226fc3e3a60449cc92b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef003b7812b51226fc3e3a60449cc92b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.img','data' => ['src' => $variant->image ?: data_get($product->hero, 'src'),'alt' => $title,'sizes' => '(max-width: 600px) 100vw, (max-width: 1100px) 50vw, 25vw']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('img'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($variant->image ?: data_get($product->hero, 'src')),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'sizes' => '(max-width: 600px) 100vw, (max-width: 1100px) 50vw, 25vw']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalef003b7812b51226fc3e3a60449cc92b)): ?>
<?php $attributes = $__attributesOriginalef003b7812b51226fc3e3a60449cc92b; ?>
<?php unset($__attributesOriginalef003b7812b51226fc3e3a60449cc92b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalef003b7812b51226fc3e3a60449cc92b)): ?>
<?php $component = $__componentOriginalef003b7812b51226fc3e3a60449cc92b; ?>
<?php unset($__componentOriginalef003b7812b51226fc3e3a60449cc92b); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->category): ?>
            <span class="model-label"><?php echo e(mb_strtoupper($product->category->name)); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </a>
    
    <div class="model-info">
        <h3><span class="variant-card__brand">Lexus</span> <span class="variant-card__name"><?php echo e(\Illuminate\Support\Str::after($title, 'Lexus ')); ?></span></h3>
        <p><?php echo e($variant->note); ?></p>
        <div class="model-price"><small>Giá niêm yết</small><strong class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-pending' => ! $variant->price]); ?>"><?php echo e(catalog_money($variant->price) ?: 'Đang cập nhật'); ?></strong></div>
    </div>
    <div class="card-links">
        <a href="<?php echo e(route('products.show', $product->slug)); ?>">Chi tiết <?php echo e($product->name); ?> ↗</a>
        <a href="<?php echo e(route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->getKey()])); ?>"
           data-quote data-product="<?php echo e($product->getKey()); ?>"
           data-variant="<?php echo e($variant->getKey()); ?>" data-variant-name="<?php echo e($variant->name); ?>">Nhận báo giá</a>
    </div>
</article>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/variant-card.blade.php ENDPATH**/ ?>
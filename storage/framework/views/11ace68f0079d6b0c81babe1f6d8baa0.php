
<?php
    $isModel = $product instanceof \Illuminate\Database\Eloquent\Model;

    $slug  = $isModel ? $product->slug : $product['slug'];
    $name  = $isModel ? $product->name : $product['name'];
    $desc  = $isModel ? $product->tagline : ($product['desc'] ?? null);
    $cat   = $isModel ? ($product->category?->slug ?? '') : ($product['cat'] ?? '');
    $label = $isModel ? ($product->category?->name ?? '') : ($product['type'] ?? '');

    $price = $isModel
        ? catalog_money($product->price_from)
        : (filled($product['price'] ?? null) ? $product['price'].' ₫' : null);

    $heroSrc = $isModel ? data_get($product->hero, 'src') : null;
    $img = ($isModel ? catalog_image($heroSrc) : ($product['image'] ?? null))
        ?: asset('assets/'.$slug.'.webp');

    // $eager: thẻ đầu lưới nằm ngay màn hình đầu (ảnh LCP của trang danh sách).
    $eager = $eager ?? false;
?>
<article class="model-card" data-category="<?php echo e($cat); ?>" id="<?php echo e($slug); ?>-card">
    <a class="model-image" href="<?php echo e(route('products.show', $slug)); ?>">
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($heroSrc) && catalog_image($heroSrc)): ?>
            <?php if (isset($component)) { $__componentOriginalef003b7812b51226fc3e3a60449cc92b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef003b7812b51226fc3e3a60449cc92b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.img','data' => ['src' => $heroSrc,'alt' => $name,'eager' => $eager,'sizes' => '(max-width: 600px) 100vw, (max-width: 850px) 50vw, 33vw']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('img'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($heroSrc),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'eager' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($eager),'sizes' => '(max-width: 600px) 100vw, (max-width: 850px) 50vw, 33vw']); ?>
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
        <?php else: ?>
            <img src="<?php echo e($img); ?>" alt="<?php echo e($name); ?>"
                 width="1600" height="1067" loading="<?php echo e($eager ? 'eager' : 'lazy'); ?>" decoding="async">
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($label)): ?>
            <span class="model-label"><?php echo e(mb_strtoupper($label)); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </a>
    <div class="model-info">
        <div>
            <h3><?php echo e($name); ?></h3>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($desc)): ?><p><?php echo e($desc); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($price)): ?>
            <div class="model-price"><small>GIÁ TỪ</small><?php echo e($price); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    <div class="card-links">
        <a href="<?php echo e(route('products.show', $slug)); ?>">Khám phá dòng xe ↗</a>
        <a href="<?php echo e(route('quote', ['xe' => $slug])); ?>" data-quote <?php if($isModel): ?> data-product="<?php echo e($product->getKey()); ?>" <?php endif; ?>>Nhận báo giá</a>
    </div>
</article>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/product-card.blade.php ENDPATH**/ ?>
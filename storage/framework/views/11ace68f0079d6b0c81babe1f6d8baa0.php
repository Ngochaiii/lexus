
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

    $img = ($isModel ? catalog_image(data_get($product->hero, 'src')) : ($product['image'] ?? null))
        ?: asset('assets/'.$slug.'.webp');
?>
<article class="model-card" data-category="<?php echo e($cat); ?>" id="<?php echo e($slug); ?>-card">
    <a class="model-image" href="<?php echo e(route('products.show', $slug)); ?>">
        <img src="<?php echo e($img); ?>" alt="<?php echo e($name); ?>"
             width="1600" height="1067" loading="lazy" decoding="async">
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
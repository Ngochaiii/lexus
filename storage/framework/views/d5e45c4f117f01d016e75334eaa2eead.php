
<?php
    $priceProducts = \App\Support\Catalog::query('product')->published()
        ->with(['variants' => fn ($q) => $q->orderBy('sort'), 'category'])
        ->orderBy('price_from')->get()
        ->filter(fn ($p) => $p->variants->isNotEmpty())
        // Xe có giá lên trước theo giá; xe đang cập nhật giá xuống cuối.
        ->sortBy(fn ($p) => $p->price_from ?? PHP_INT_MAX);

    $updatedAt = $priceProducts->flatMap->variants->max('updated_at');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($priceProducts->isNotEmpty()): ?>
    <section class="container section price-table" id="bang-gia-chi-tiet">
        <div class="section-heading">
            <div>
                <div class="eyebrow">GIÁ NIÊM YẾT <?php echo e($updatedAt ? 'THÁNG '.$updatedAt->format('n/Y') : ''); ?></div>
                <h2>Giá xe Lexus theo từng phiên bản.</h2>
            </div>
            <p class="small">
                <?php echo e($priceProducts->count()); ?> dòng xe · <?php echo e($priceProducts->sum(fn ($p) => $p->variants->count())); ?> phiên bản.
                Giá đã gồm VAT, chưa gồm lệ phí trước bạ và phí đăng ký.
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($updatedAt): ?><br>Cập nhật <time datetime="<?php echo e($updatedAt->toDateString()); ?>"><?php echo e($updatedAt->format('d/m/Y')); ?></time>.<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </p>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <caption class="studio-sr">Bảng giá xe Lexus tại <?php echo e(catalog_setting('site_name', 'Lexus Thăng Long')); ?></caption>
                <thead>
                    <tr>
                        <th scope="col">Dòng xe</th>
                        <th scope="col">Phiên bản</th>
                        <th scope="col">Giá niêm yết</th>
                        <th scope="col"><span class="studio-sr">Báo giá</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $priceProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $product->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($loop->first): ?>
                                    <th scope="rowgroup" rowspan="<?php echo e($product->variants->count()); ?>">
                                        <a href="<?php echo e(route('products.show', $product->slug)); ?>"><?php echo e($product->name); ?></a>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->category): ?><small><?php echo e($product->category->name); ?></small><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </th>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <td><?php echo e($variant->name); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($variant->note)): ?><small><?php echo e($variant->note); ?></small><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                                <td class="price-cell"><?php echo e(catalog_money($variant->price) ?: 'Đang cập nhật'); ?></td>
                                <td>
                                    <a class="text-link" href="<?php echo e(route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->id])); ?>"
                                       data-quote data-product="<?php echo e($product->id); ?>"
                                       data-variant="<?php echo e($variant->id); ?>" data-variant-name="<?php echo e($variant->name); ?>">Giá lăn bánh</a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/price-table.blade.php ENDPATH**/ ?>
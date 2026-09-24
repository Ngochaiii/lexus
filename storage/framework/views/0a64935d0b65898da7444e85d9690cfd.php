<?php
    // Trang 2 trở đi phải tự trỏ canonical về chính nó, kèm rel prev/next.
    $indexCanonical = \App\Support\Url::paginated($canonical ?? request()->url(), $products->currentPage());

    // Tiêu đề/mô tả nêu đích danh các dòng xe + giá thấp nhất: đúng thứ người
    // tìm "xe Lexus SUV giá bao nhiêu" gõ, thay cho câu khẩu hiệu chung chung.
    $listName   = ($category ?? null) ? 'Xe Lexus '.$category->name : 'Các dòng xe Lexus';
    $listModels = collect($products->items())->pluck('name')->map(fn ($n) => trim(str_replace('Lexus', '', $n)))->filter();
    $listFrom   = collect($products->items())->pluck('price_from')->filter()->min();
    $listTitle  = ($category ?? null)
        ? $listName.': '.$listModels->join(', ').' — giá & phiên bản'
        : 'Giá xe Lexus 2026: '.$listModels->count().' dòng xe SUV, Sedan, MPV';
    $listDesc   = $listName.' tại Lexus Thăng Long, Hà Nội: '.$listModels->map(fn ($m) => 'Lexus '.$m)->join(', ')
        .($listFrom ? ' — giá từ '.catalog_money_short($listFrom) : '')
        .'. Xem giá từng phiên bản, màu xe và đăng ký lái thử.';
?>


<?php $__env->startSection('content'); ?>
    <?php
        $category = $category ?? null;

        // Danh mục mà style.css đã có sẵn quy tắc lọc.
        $filters = $categories->whereIn('slug', ['suv', 'sedan', 'mpv', 'hybrid']);

        $showFilters = $filters->isNotEmpty() && ! $category && ! $products->hasPages();
    ?>

    <?php echo $__env->make('frontend.partials.page-intro', [
        'title'   => $category?->name ?? 'Các dòng xe Lexus',
        'eyebrow' => 'THE LEXUS COLLECTION',
        'text'    => 'Mỗi thiết kế là một cá tính. Tìm chiếc Lexus đồng điệu với phong cách và hành trình của bạn.',
        'crumbs'  => $category ? ['Các dòng xe' => route('products.index')] : [],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="container section">
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <span id="<?php echo e($item->slug); ?>-section"></span>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

        <div class="catalog">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showFilters): ?>
                <div class="filters" aria-label="Lọc theo dòng xe">
                    <input class="filter-input" type="radio" name="category" id="all" checked>
                    <label for="all">Tất cả dòng xe</label>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <input class="filter-input" type="radio" name="category" id="<?php echo e($item->slug); ?>">
                        <label for="<?php echo e($item->slug); ?>"><?php echo e($item->name); ?></label>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            <?php elseif($categories->isNotEmpty()): ?>
                
                <div class="filters" aria-label="Danh mục xe">
                    <a class="<?php echo e($category ? '' : 'active'); ?>" href="<?php echo e(route('products.index')); ?>">Tất cả dòng xe</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a class="<?php echo e($category?->is($item) ? 'active' : ''); ?>"
                           href="<?php echo e(route('categories.show', $item->slug)); ?>"><?php echo e($item->name); ?></a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($products->isEmpty()): ?>
                <p>Chưa có dòng xe nào được đăng. Vui lòng quay lại sau.</p>
            <?php else: ?>
                
                <h2 class="studio-sr"><?php echo e($listName); ?> — <?php echo e($products->total()); ?> dòng xe</h2>
                <div class="model-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php echo $__env->make('frontend.partials.product-card', ['product' => $product, 'eager' => $loop->first], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="section-foot">
                <small>Giá tham khảo, không phải báo giá chính thức.</small>
                <a class="text-link" href="<?php echo e(route('booking')); ?>">Đăng ký lái thử</a>
            </div>

            <?php echo $__env->make('frontend.partials.pagination', ['paginator' => $products], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </section>

    <section class="split stone">
        <div class="split-media">
            <img src="<?php echo e(asset('assets/co-so/sanh-trung-bay.webp')); ?>"
                 alt="Sảnh trưng bày xe Lexus hybrid tại Lexus Thăng Long, Cầu Giấy"
                 width="1335" height="893" loading="lazy" decoding="async">
        </div>
        <div class="split-copy">
            <div class="eyebrow">LEXUS ELECTRIFIED</div>
            <h2>Chuyển động hôm nay.<br>Cảm hứng ngày mai.</h2>
            <p>RX, NX, ES, LM và LS đều có bản hybrid — không cần cắm sạc, êm và tiết kiệm trong phố. Ghé showroom để lái thử và so sánh trực tiếp.</p>
            <a class="text-link" href="<?php echo e(route('pages.show', 'the-gioi-lexus')); ?>">Khám phá trải nghiệm</a>
        </div>
    </section>

    <?php echo $__env->make('frontend.partials.conversion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'title'       => $listTitle.' | '.catalog_setting('site_name', config('app.name')),
    'description' => $listDesc,
    'canonical'   => $indexCanonical,
    'prev'        => $products->previousPageUrl(),
    'next'        => $products->nextPageUrl(),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forCollection($listName, $indexCanonical,
            collect($products->items())->map(fn ($p) => ['name' => $p->name, 'url' => \App\Support\Url::absolute('product', $p->slug)])->all(),
            $listDesc),
        \App\Support\JsonLd::forBreadcrumb(array_values(array_filter([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Dòng xe', 'url' => route('products.index')],
            ($category ?? null) ? ['name' => $category->name, 'url' => $indexCanonical] : null,
        ]))),
        \App\Support\JsonLd::organization(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/products.blade.php ENDPATH**/ ?>
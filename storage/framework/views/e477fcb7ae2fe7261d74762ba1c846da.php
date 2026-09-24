<?php
    $indexCanonical = \App\Support\Url::paginated($canonical ?? request()->url(), $posts->currentPage());
    $listName = ($category ?? null)?->name ?? 'Tin tức & tư vấn mua xe Lexus';
    $listDesc = ($category ?? null)
        ? $category->name.' — bài viết của chuyên viên tư vấn Lexus Thăng Long, Hà Nội: giá xe, so sánh phiên bản, kinh nghiệm mua xe Lexus.'
        : 'Bảng giá, giá lăn bánh, so sánh phiên bản và kinh nghiệm chọn xe Lexus tại Hà Nội — cập nhật bởi chuyên viên tư vấn Lexus Thăng Long.';
?>


<?php $__env->startSection('content'); ?>
    <?php
        $category = $category ?? null;
        $items    = collect($posts->items());

        // Chỉ trang đầu mới có bài nổi bật.
        $featured = $posts->currentPage() === 1 ? $items->first() : null;
        $rest     = $featured ? $items->slice(1) : $items;
    ?>

    <?php echo $__env->make('frontend.partials.page-intro', [
        'title'   => $category?->name ?? 'Những câu chuyện đáng dành thời gian.',
        'eyebrow' => 'LEXUS JOURNAL',
        'text'    => 'Thiết kế, con người và những trải nghiệm làm nên một góc nhìn Lexus.',
        'crumbs'  => $category ? ['Tin tức' => route('posts.index')] : [],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($categories->isNotEmpty()): ?>
        <div class="container">
            <div class="filters" aria-label="Chuyên mục">
                <a class="<?php echo e($category ? '' : 'active'); ?>" href="<?php echo e(route('posts.index')); ?>">Tất cả</a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a class="<?php echo e($category?->is($item) ? 'active' : ''); ?>"
                       href="<?php echo e(route('post-categories.show', $item->slug)); ?>"><?php echo e($item->name); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($posts->isEmpty()): ?>
        <section class="container section">
            <p>Chưa có bài viết nào được đăng. Vui lòng quay lại sau.</p>
        </section>
    <?php else: ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($featured): ?>
            <section class="container section">
                <div class="editorial-grid" style="align-items:center">
                    <a href="<?php echo e(route('posts.show', $featured->slug)); ?>">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(catalog_image($featured->cover)): ?>
                            <?php if (isset($component)) { $__componentOriginalef003b7812b51226fc3e3a60449cc92b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef003b7812b51226fc3e3a60449cc92b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.img','data' => ['src' => $featured->cover,'alt' => $featured->title,'eager' => true,'sizes' => '(max-width: 600px) 100vw, 50vw']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('img'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($featured->cover),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($featured->title),'eager' => true,'sizes' => '(max-width: 600px) 100vw, 50vw']); ?>
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
                            <img src="<?php echo e(asset('assets/co-so/mat-tien.webp')); ?>" alt="<?php echo e($featured->title); ?>"
                                 width="1600" height="1067" fetchpriority="high" decoding="async">
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </a>
                    <div>
                        <div class="eyebrow">CÂU CHUYỆN NỔI BẬT</div>
                        <h2><?php echo e($featured->title); ?></h2>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($featured->excerpt)): ?>
                            <p style="margin-top:22px"><?php echo e($featured->excerpt); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <a class="text-link" href="<?php echo e(route('posts.show', $featured->slug)); ?>">Đọc câu chuyện</a>
                    </div>
                </div>
            </section>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rest->isNotEmpty()): ?>
            <section class="container section" style="padding-top:0">
                <div class="news-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $rest; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php echo $__env->make('frontend.partials.post-card', ['post' => $post], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                <?php echo $__env->make('frontend.partials.pagination', ['paginator' => $posts], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </section>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('frontend.partials.conversion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'title'       => $listName.' | '.catalog_setting('site_name', config('app.name')),
    'description' => $listDesc,
    'canonical'   => $indexCanonical,
    'prev'        => $posts->previousPageUrl(),
    'next'        => $posts->nextPageUrl(),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forCollection($listName, $indexCanonical,
            collect($posts->items())->map(fn ($p) => ['name' => $p->title, 'url' => route('posts.show', $p->slug)])->all(),
            $listDesc),
        \App\Support\JsonLd::organization(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/posts.blade.php ENDPATH**/ ?>
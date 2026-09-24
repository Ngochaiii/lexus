<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('frontend.partials.page-intro', [
        'title'   => $post->title,
        'eyebrow' => trim(mb_strtoupper($post->category?->name ?? 'Lexus Journal')
            .($post->published_at ? ' · '.$post->published_at->format('d.m.Y') : '')),
        'text'    => $post->excerpt,
        'crumbs'  => ['Tin tức' => route('posts.index')],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cover = catalog_image($post->cover)): ?>
        <?php
            $coverSize   = \App\Support\Media::dimensions($post->cover);
            $coverSrcset = \App\Support\Media::srcset($post->cover);
        ?>
        <img src="<?php echo e($cover); ?>" alt="<?php echo e($post->title); ?>"
             <?php if($coverSrcset): ?> srcset="<?php echo e($coverSrcset); ?>" sizes="100vw" <?php endif; ?>
             width="<?php echo e($coverSize['w'] ?? 1600); ?>" height="<?php echo e($coverSize['h'] ?? 1067); ?>" fetchpriority="high" decoding="async">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php $author = catalog_setting('advisor_name'); ?>
    <div class="container post-byline">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($author): ?>
            <p>Người viết: <a href="<?php echo e(route('pages.show', 'lien-he')); ?>" rel="author"><?php echo e($author); ?></a>
                — <?php echo e(catalog_setting('advisor_role', 'Chuyên viên tư vấn')); ?></p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <p>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->published_at): ?>Đăng <time datetime="<?php echo e($post->published_at->toAtomString()); ?>"><?php echo e($post->published_at->format('d/m/Y')); ?></time><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at->copy()->addDay())): ?>
                · Cập nhật <time datetime="<?php echo e($post->updated_at->toAtomString()); ?>"><?php echo e($post->updated_at->format('d/m/Y')); ?></time>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </p>
    </div>

    <?php echo $__env->make('frontend.partials.sections', ['sections' => $sections, 'numbered' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($related)): ?>
        <section class="container section">
            <div class="section-heading">
                <h2>Tiếp tục đọc.</h2>
                <a class="text-link" href="<?php echo e(route('posts.index')); ?>">Tất cả câu chuyện</a>
            </div>
            <div class="news-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $related; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php echo $__env->make('frontend.partials.post-card', ['post' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('frontend.partials.conversion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'title'       => data_get($post->seo, 'title', $post->title.' | '.catalog_setting('site_name', config('app.name'))),
    'description' => data_get($post->seo, 'description') ?: $post->excerpt,
    'canonical'   => \App\Support\Url::absolute('post', $post->slug),
    'ogType'      => 'article',
    'ogImage'     => catalog_image($post->cover),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forPost($post),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Tin tức', 'url' => route('posts.index')],
            ['name' => $post->title, 'url' => \App\Support\Url::absolute('post', $post->slug)],
        ]),
        \App\Support\JsonLd::forFaq($sections, \App\Support\Url::absolute('post', $post->slug)),
        \App\Support\JsonLd::organization(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/post.blade.php ENDPATH**/ ?>
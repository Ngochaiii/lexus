<?php $__env->startSection('content'); ?>
    <?php
        $sections = collect($sections);
        $first    = $sections->first();
        $cover    = null;

        if (($first['type'] ?? null) === 'media'
            && count($first['items'] ?? []) === 1
            && blank($first['title'] ?? null)) {
            $cover    = $first['items'][0];
            $sections = $sections->slice(1)->values();
        }
    ?>

    <?php echo $__env->make('frontend.partials.page-intro', [
        'title'   => $page->title,
        'eyebrow' => data_get($page->seo, 'eyebrow'),
        'text'    => data_get($page->seo, 'excerpt'),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cover && ($src = catalog_image($cover['image'] ?? null))): ?>
        <?php
            $coverSize   = \App\Support\Media::dimensions($cover['image']);
            $coverSrcset = \App\Support\Media::srcset($cover['image']);
        ?>
        <img src="<?php echo e($src); ?>" alt="<?php echo e($cover['label'] ?? $page->title); ?>"
             <?php if($coverSrcset): ?> srcset="<?php echo e($coverSrcset); ?>" sizes="100vw" <?php endif; ?>
             width="<?php echo e($coverSize['w'] ?? 1600); ?>" height="<?php echo e($coverSize['h'] ?? 1067); ?>" fetchpriority="high" decoding="async">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($page->slug === 'bang-gia'): ?>
        <?php echo $__env->make('frontend.partials.price-table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->renderWhen($sections->isNotEmpty(), 'frontend.partials.sections', [
        'sections' => $sections,
        'numbered' => false,
        // Ảnh của mục đầu nằm ngay dưới phần mở đầu/ảnh bìa — trên mobile
        // thường là ảnh lớn nhất màn hình đầu (LCP): tải ngay, không lazy.
        // Trừ bảng giá: mục đầu nằm dưới cả bảng.
        'eagerFirst' => $page->slug !== 'bang-gia',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1])); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($page->slug, ['showroom', 'lien-he'], true)): ?>
        <section class="container section">
            <div class="section-heading"><h2>Hẹn gặp bạn tại showroom.</h2></div>
            <?php echo $__env->make('frontend.partials.contact-details', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('frontend.partials.conversion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'title'       => data_get($page->seo, 'title', $page->title.' | '.catalog_setting('site_name', config('app.name'))),
    'description' => data_get($page->seo, 'description'),
    'canonical'   => \App\Support\Url::absolute('page', $page->slug),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forPage($page),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => $page->title, 'url' => \App\Support\Url::absolute('page', $page->slug)],
        ]),
        \App\Support\JsonLd::forFaq($sections, \App\Support\Url::absolute('page', $page->slug)),
        \App\Support\JsonLd::organization(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/page.blade.php ENDPATH**/ ?>
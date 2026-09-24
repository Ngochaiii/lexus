
<?php
    $siteName = catalog_setting('site_name', config('app.name'));

    $pageTitle       = $title ?? $siteName;
    $pageDescription = $description ?? catalog_setting('site_description');
    // Google chỉ hiện ~160 ký tự. Dài hơn thì cắt ở cuối câu gần nhất (hoặc ở
    // ranh giới từ + "…") thay vì để Google tự cắt ngang chữ. og:description
    // vẫn giữ bản đầy đủ — Facebook/Zalo hiện được dài hơn.
    $metaDescription = \App\Support\SeoText::description($pageDescription);
    $pageCanonical   = $canonical ?? request()->url();
    $pageType        = $ogType ?? 'website';
    $pageRobots      = $robots ?? 'index,follow,max-image-preview:large';
    $pageImage       = \App\Support\Url::asset(
        ($ogImage ?? null) ?: catalog_setting('social_image') ?: catalog_setting('logo')
    );

    $overlay  = $overlay  ?? false;
    $salesBar = $salesBar ?? false;
    $popup    = $popup    ?? false;

    // Nhúng ?v=filemtime để cache vĩnh viễn vẫn an toàn khi sửa CSS.
    $cssVersion = @filemtime(public_path('assets/style.css')) ?: null;
    $jsVersion  = @filemtime(public_path('assets/lead.js')) ?: null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo e($pageTitle); ?></title>
    <meta name="theme-color" content="#151617">

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($pageDescription)): ?>
        <meta name="description" content="<?php echo e($metaDescription); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <meta name="robots" content="<?php echo e($pageRobots); ?>">
    <link rel="canonical" href="<?php echo e($pageCanonical); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($prev ?? null)): ?><link rel="prev" href="<?php echo e($prev); ?>"><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($next ?? null)): ?><link rel="next" href="<?php echo e($next); ?>"><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="<?php echo e($pageType); ?>">
    <meta property="og:site_name" content="<?php echo e($siteName); ?>">
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:url" content="<?php echo e($pageCanonical); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($pageDescription)): ?>
        <meta property="og:description" content="<?php echo e($pageDescription); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($pageImage)): ?>
        <meta property="og:image" content="<?php echo e($pageImage); ?>">
        <meta property="og:image:alt" content="<?php echo e($pageTitle); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <meta name="twitter:card" content="<?php echo e(filled($pageImage) ? 'summary_large_image' : 'summary'); ?>">
    <meta name="twitter:title" content="<?php echo e($pageTitle); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($pageDescription)): ?>
        <meta name="twitter:description" content="<?php echo e($pageDescription); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($pageImage)): ?>
        <meta name="twitter:image" content="<?php echo e($pageImage); ?>">
        <meta name="twitter:image:alt" content="<?php echo e($pageTitle); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <link rel="icon" href="<?php echo e(asset('assets/favicon-32.png')); ?>" sizes="32x32" type="image/png">
    <link rel="icon" href="<?php echo e(asset('assets/favicon-192.png')); ?>" sizes="192x192" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo e(asset('assets/apple-touch-icon.png')); ?>">

    
    <link rel="preload" href="<?php echo e(asset('assets/fonts/NobelVnu-Book.woff')); ?>" as="font" type="font/woff" crossorigin>
    <link rel="stylesheet" href="<?php echo e(asset('assets/style.css')); ?><?php if($cssVersion): ?>?v=<?php echo e($cssVersion); ?><?php endif; ?>">
    <?php echo $__env->yieldPushContent('preload'); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($jsonld)): ?>
        
        <script type="application/ld+json"><?php echo json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('frontend.partials.tracking', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="<?php echo e(trim(($bodyClass ?? '').($salesBar ? ' has-sales' : ''))); ?>">
<a class="skip" href="#main">Đến nội dung chính</a>

<?php echo $__env->make('frontend.partials.header', ['overlay' => $overlay], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<main id="main">
    <?php echo $__env->yieldContent('content'); ?>
</main>

<?php echo $__env->make('frontend.partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($salesBar): ?>
    <?php echo $__env->make('frontend.partials.sales-bar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! (request()->routeIs('quote'))): ?>
    <?php echo $__env->make('frontend.partials.quote-dialog', ['autoOpen' => $popup], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<script src="<?php echo e(asset('assets/lead.js')); ?><?php if($jsVersion): ?>?v=<?php echo e($jsVersion); ?><?php endif; ?>" defer></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/layout.blade.php ENDPATH**/ ?>
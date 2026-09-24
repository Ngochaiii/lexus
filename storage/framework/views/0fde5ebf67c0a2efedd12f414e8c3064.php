
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'src'   => null,
    'alt'   => '',
    'sizes' => '100vw',
    'eager' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'src'   => null,
    'alt'   => '',
    'sizes' => '100vw',
    'eager' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $url = \App\Support\Media::url($src);
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($url): ?>
    <?php
        $set = \App\Support\Media::srcset($src);
        $dim = \App\Support\Media::dimensions($src);
    ?>

    <img
        src="<?php echo e($url); ?>"
        <?php if($set): ?> srcset="<?php echo e($set); ?>" sizes="<?php echo e($sizes); ?>" <?php endif; ?>
        <?php if($dim): ?> width="<?php echo e($dim['w']); ?>" height="<?php echo e($dim['h']); ?>" <?php endif; ?>
        alt="<?php echo e($alt); ?>"
        loading="<?php echo e($eager ? 'eager' : 'lazy'); ?>"
        decoding="async"
        <?php if($eager): ?> fetchpriority="high" <?php endif; ?>
        <?php echo e($attributes); ?>

    >
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/components/img.blade.php ENDPATH**/ ?>
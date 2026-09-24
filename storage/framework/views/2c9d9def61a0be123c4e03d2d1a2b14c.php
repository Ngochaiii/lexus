
<?php
    $isModel = $post instanceof \Illuminate\Database\Eloquent\Model;

    $slug  = $isModel ? $post->slug  : $post['slug'];
    $title = $isModel ? $post->title : $post['title'];
    $cat   = $isModel ? ($post->category?->name ?? 'Lexus Journal') : ($post['category'] ?? '');
    $date  = $isModel ? $post->published_at?->format('d.m.Y') : ($post['date'] ?? '');

    $cover = $isModel ? $post->cover : null;
    $img   = ($isModel ? catalog_image($cover) : ($post['image'] ?? null))
        ?: asset('assets/co-so/mat-tien.webp');
?>
<article class="news-card">
    <a href="<?php echo e(route('posts.show', $slug)); ?>">
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($cover) && catalog_image($cover)): ?>
            <?php if (isset($component)) { $__componentOriginalef003b7812b51226fc3e3a60449cc92b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef003b7812b51226fc3e3a60449cc92b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.img','data' => ['src' => $cover,'alt' => $title,'sizes' => '(max-width: 600px) 100vw, (max-width: 1100px) 50vw, 33vw']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('img'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cover),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'sizes' => '(max-width: 600px) 100vw, (max-width: 1100px) 50vw, 33vw']); ?>
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
            <img src="<?php echo e($img); ?>" alt="<?php echo e($title); ?>"
                 width="1600" height="1067" loading="lazy" decoding="async">
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </a>
    <div class="meta"><?php echo e(mb_strtoupper($cat)); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($date): ?> &nbsp; / &nbsp; <?php echo e($date); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>
    <h3><a href="<?php echo e(route('posts.show', $slug)); ?>"><?php echo e($title); ?></a></h3>
    <a class="text-link" href="<?php echo e(route('posts.show', $slug)); ?>">Đọc câu chuyện</a>
</article>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/post-card.blade.php ENDPATH**/ ?>
<?php
    $heroImage = catalog_image(data_get($product->hero, 'src')) ?: asset('assets/co-so/khu-trung-bay.webp');

    // Ảnh hero dưới ~1400px mà kéo tràn màn hình thì vỡ hạt. Khi đó chuyển
    // sang kiểu "studio": nền đá sáng, ảnh giữ gần kích thước gốc, header
    // nền trắng thay vì trong suốt. Upload ảnh lớn hơn là tự về kiểu tràn.
    $heroSize = \App\Support\Media::dimensions(data_get($product->hero, 'src'));
    $studio   = $heroSize && $heroSize['w'] < 1400;

    $heroSrcset = \App\Support\Media::srcset(data_get($product->hero, 'src'));
    $heroSizes  = $studio ? '(max-width: 900px) 100vw, 55vw' : '100vw';
?>

<?php $__env->startPush('preload'); ?>
    
    <link rel="preload" as="image" fetchpriority="high" href="<?php echo e($heroImage); ?>"
          <?php if($heroSrcset): ?> imagesrcset="<?php echo e($heroSrcset); ?>" imagesizes="<?php echo e($heroSizes); ?>" <?php endif; ?>>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $highlights = collect($product->highlights ?? [])->filter(fn ($h) => filled($h['value'] ?? null));
        $variants   = $product->variants;
        $options    = $product->options;

        // Thư viện có mục riêng thì thanh điều hướng mới hiện link "Thư viện".
        $galleryIndex = collect($sections)
            ->search(fn ($s) => ($s['layout'] ?? null) === 'gallery');
    ?>

    

    
    <section class="hero model-hero <?php echo e($studio ? 'is-studio' : ''); ?>"
             <?php if($studio): ?> style="--hero-native: <?php echo e((int) round($heroSize['w'] * 1.25)); ?>px" <?php endif; ?>>
        <img class="hero-media" src="<?php echo e($heroImage); ?>" alt="<?php echo e($product->name); ?><?php echo e(filled($product->tagline) ? ' — '.$product->tagline : ''); ?>"
             <?php if($heroSrcset): ?> srcset="<?php echo e($heroSrcset); ?>" sizes="<?php echo e($heroSizes); ?>" <?php endif; ?>
             width="<?php echo e($heroSize['w'] ?? 1600); ?>" height="<?php echo e($heroSize['h'] ?? 1067); ?>" fetchpriority="high" loading="eager" decoding="async">
        <div class="container">
            <div class="hero-copy">
                <div class="eyebrow">THE <?php echo e(mb_strtoupper($product->name)); ?></div>
                <h1><?php echo e(mb_strtoupper($product->name)); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($product->tagline)): ?><h2><?php echo e($product->tagline); ?></h2><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($price = catalog_money($product->price_from)): ?>
                    <p>Giá từ <?php echo e($price); ?></p>
                <?php elseif($variants->isNotEmpty()): ?>
                    <p>Giá đang cập nhật</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="actions">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variants->isNotEmpty()): ?>
                        <a class="button light" href="#versions">Xem giá <?php echo e($variants->count()); ?> phiên bản</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <a class="text-link" href="<?php echo e(route('quote', ['xe' => $product->slug])); ?>" data-quote data-product="<?php echo e($product->id); ?>">Nhận báo giá</a>
                </div>
            </div>
        </div>
        <div class="hero-bottom">
            <span><?php echo e(mb_strtoupper($product->category?->name ?? '')); ?></span>
            <a href="<?php echo e($variants->isNotEmpty() ? '#versions' : '#overview'); ?>"><?php echo e($variants->isNotEmpty() ? 'XEM GIÁ' : 'KHÁM PHÁ'); ?> &nbsp; ↓</a>
        </div>
    </section>

    
    <nav class="model-nav" aria-label="Nội dung dòng xe">
        <strong><?php echo e(mb_strtoupper($product->name)); ?></strong>
        <div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variants->isNotEmpty()): ?><a href="#versions">Giá & phiên bản</a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(catalog_feature('options') && $options->isNotEmpty()): ?><a href="#vehicle-studio">Màu sắc</a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = collect($sections)->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <a href="#muc-<?php echo e($i); ?>"><?php echo e($section['title'] ?? 'Mục '.($i + 1)); ?></a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($product->specs)): ?><a href="#specs">Thông số</a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <a class="button" href="<?php echo e(route('quote', ['xe' => $product->slug])); ?>" data-quote data-product="<?php echo e($product->id); ?>">Báo giá</a>
    </nav>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variants->isNotEmpty()): ?>
        <?php
            $onRoad = config('catalog.on_road');
            $taxPct = rtrim(rtrim(number_format($onRoad['tax_rate'] * 100, 1, ',', '.'), '0'), ',');
        ?>
        <section class="section versions" id="versions">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <div class="eyebrow">GIÁ NIÊM YẾT · <?php echo e($variants->count()); ?> PHIÊN BẢN</div>
                        <h2>Giá <?php echo e($product->name); ?> theo từng phiên bản.</h2>
                    </div>
                    <p class="small">
                        Giá niêm yết đã gồm VAT. Lăn bánh tạm tính tại <?php echo e($onRoad['region']); ?> = giá niêm yết
                        + lệ phí trước bạ <?php echo e($taxPct); ?>% + biển số <?php echo e(catalog_money_short($onRoad['plate_fee'])); ?>;
                        chưa gồm đăng kiểm, phí đường bộ, bảo hiểm (vài triệu đồng).
                    </p>
                </div>
                <div class="variant-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php $estimate = \App\Support\OnRoadPrice::for($variant); ?>
                        <article class="variant">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($variant->image)): ?>
                                <div class="variant-image">
                                    <?php if (isset($component)) { $__componentOriginalef003b7812b51226fc3e3a60449cc92b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef003b7812b51226fc3e3a60449cc92b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.img','data' => ['src' => $variant->image,'alt' => $product->name.' — '.$variant->name,'sizes' => '(max-width: 600px) 100vw, 33vw']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('img'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($variant->image),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($product->name.' — '.$variant->name),'sizes' => '(max-width: 600px) 100vw, 33vw']); ?>
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
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <h3><?php echo e($variant->name); ?></h3>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($variant->note)): ?><p class="variant-note"><?php echo e($variant->note); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <dl class="variant-prices">
                                <div class="is-list">
                                    <dt>Giá niêm yết</dt>
                                    <dd><?php echo e(catalog_money($variant->price) ?: 'Đang cập nhật'); ?></dd>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($estimate): ?>
                                    <div>
                                        
                                        <dt>Lăn bánh <?php echo e($estimate['region']); ?> (tạm tính)</dt>
                                        <dd><?php echo e(catalog_money_short($estimate['total'])); ?></dd>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </dl>
                            
                            <a class="button" href="<?php echo e(route('quote', ['xe' => $product->slug, 'phien-ban' => $variant->id])); ?>"
                               data-quote data-product="<?php echo e($product->id); ?>"
                               data-variant="<?php echo e($variant->id); ?>" data-variant-name="<?php echo e($variant->name); ?>">Nhận báo giá chi tiết</a>
                        </article>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php echo $__env->make('frontend.partials.vehicle-studio', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($highlights->isNotEmpty()): ?>
        <div class="container spec-strip" id="overview">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $highlights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div>
                    <small><?php echo e(mb_strtoupper($item['label'] ?? '')); ?></small>
                    <strong><?php echo e($item['value']); ?><?php echo e(filled($item['unit'] ?? null) ? ' '.$item['unit'] : ''); ?></strong>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    <?php else: ?>
        <span id="overview"></span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('frontend.partials.sections', ['sections' => $sections], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($product->specs)): ?>
        <section class="container section" id="specs">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">THÔNG TIN SỞ HỮU</div>
                    <h2>Tìm hiểu kỹ hơn.</h2>
                </div>
            </div>
            <div class="accordion">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $product->specs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <details <?php if($loop->first): ?> open <?php endif; ?>>
                        <summary><?php echo e($group['group'] ?? 'Thông số'); ?></summary>
                        <div class="table-wrap">
                            <table class="data-table">
                                <tbody>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group['rows'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($row['label'] ?? ''); ?></td>
                                        <td><?php echo e($row['value'] ?? ''); ?></td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($product->spec_notes)): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = (array) $product->spec_notes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="notice">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($note['label'] ?? null)): ?><strong class="notice__label"><?php echo e($note['label']); ?></strong><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php echo e(is_array($note) ? ($note['body'] ?? '') : $note); ?>

                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <section class="section stone">
            <div class="container lead-layout">
                <div class="lead-copy">
                    <div class="eyebrow">MỘT BƯỚC ĐẾN GẦN HƠN</div>
                    <h2><?php echo e($product->name); ?>.<br>Dành riêng cho bạn.</h2>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($form->description)): ?><p><?php echo e($form->description); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="actions">
                        <a class="text-link" href="<?php echo e(route('booking')); ?>">Đăng ký lái thử</a>
                    </div>
                </div>

                <?php echo $__env->make('frontend.partials.lead-form', [
                    'formKey'     => $form->key,
                    'submitLabel' => $form->name,
                    'selected'    => $product->id,
                    'instance'    => 'cuoi-trang',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </section>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

    
    <?php
        $related = \App\Support\Catalog::query('product')->published()
            ->where('id', '!=', $product->id)->with('category')->orderBy('sort')->take(3)->get();
    ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($related->isNotEmpty()): ?>
        <section class="container section">
            <div class="section-heading">
                <h2>Tiếp tục khám phá.</h2>
                <a class="text-link" href="<?php echo e(route('products.index')); ?>">Tất cả dòng xe</a>
            </div>
            <div class="model-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $related; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php echo $__env->make('frontend.partials.product-card', ['product' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'overlay'     => ! $studio,
    'salesBar'    => true,
    'title'       => data_get($product->seo, 'title', $product->name.' | '.catalog_setting('site_name', config('app.name'))),
    'description' => data_get($product->seo, 'description')
        ?: collect([$product->name, $product->tagline])->filter()->join(' — ')
            .'. Khám phá thiết kế, phiên bản và đăng ký lái thử.',
    'canonical'   => \App\Support\Url::absolute('product', $product->slug),
    'ogType'      => 'product',
    'ogImage'     => catalog_image(data_get($product->hero, 'src')),
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forProduct($product),
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Dòng xe', 'url' => route('products.index')],
            ['name' => $product->name, 'url' => \App\Support\Url::absolute('product', $product->slug)],
        ]),
        \App\Support\JsonLd::forFaq($sections, \App\Support\Url::absolute('product', $product->slug)),
        \App\Support\JsonLd::organization(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/product.blade.php ENDPATH**/ ?>
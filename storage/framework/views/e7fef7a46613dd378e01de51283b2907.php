<?php
    $advisor   = catalog_setting('advisor_name');
    $advisorRo = catalog_setting('advisor_role', 'Chuyên viên tư vấn');
    $portrait  = catalog_image(catalog_setting('advisor_image'));
    $hasAdvisor = filled($advisor) && ! catalog_setting('advisor_off');

    $heroProduct = $products->first();

    // Ảnh banner tính ở đây (không phải trong @section) để preload đúng ảnh
    // đang hiển thị — preload ảnh khác là tải thừa cả trăm KB mỗi lượt vào.
    $banner  = $banners->first();
    $bImage  = catalog_image($banner?->image) ?: asset('assets/co-so/khu-trung-bay.webp');
    $bMobile = catalog_image($banner?->image_mobile);
    $bSrcset = \App\Support\Media::srcset($banner?->image);
?>

<?php $__env->startPush('preload'); ?>
    <link rel="preload" as="image" fetchpriority="high" href="<?php echo e($bImage); ?>"
          <?php if($bSrcset): ?> imagesrcset="<?php echo e($bSrcset); ?>" imagesizes="100vw" <?php endif; ?>>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    
    
    <?php
        $bEyebrow = $banner?->eyebrow  ?: 'THE LEXUS RX · MỘT CHUẨN MỰC MỚI';
        $bTitle   = $banner?->title    ?: "Dấu ấn riêng.\nHành trình khác biệt.";
        $bText    = $banner?->subtitle ?: "Sự tĩnh tại trong từng chuyển động.\nTinh hoa Lexus, dành riêng cho bạn.";


        // Nút chính: lấy từ banner, không có thì trỏ vào xe đầu danh sách.
        $bCtaUrl   = $banner?->cta_url;
        $bCtaLabel = $banner?->cta_label;

        if (blank($bCtaUrl) && $heroProduct) {
            $bCtaUrl   = route('products.show', $heroProduct->slug);
            $bCtaLabel = $bCtaLabel ?: 'Khám phá '.$heroProduct->name;
        }
    ?>
    <section class="hero">
        <picture>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bMobile): ?><source media="(max-width: 600px)" srcset="<?php echo e($bMobile); ?>"><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <img class="hero-media" src="<?php echo e($bImage); ?>" <?php if($bSrcset): ?> srcset="<?php echo e($bSrcset); ?>" sizes="100vw" <?php endif; ?>
                 alt="<?php echo e(catalog_setting('site_name', 'Lexus Thăng Long')); ?> — khu trưng bày xe Lexus tại Cầu Giấy, Hà Nội"
                 width="1600" height="1067" fetchpriority="high" loading="eager" decoding="async">
        </picture>
        <div class="container">
            <div class="hero-copy">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($bEyebrow)): ?><div class="eyebrow"><?php echo e($bEyebrow); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <h1><?php echo nl2br(e($bTitle)); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($bText)): ?><p><?php echo nl2br(e($bText)); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="actions">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($bCtaUrl)): ?>
                        <a class="button light" href="<?php echo e($bCtaUrl); ?>"><?php echo e($bCtaLabel ?: 'Khám phá dòng xe'); ?></a>
                    <?php else: ?>
                        <a class="button light" href="<?php echo e(route('products.index')); ?>">Khám phá dòng xe</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    
                    <a class="text-link" href="<?php echo e(route('booking')); ?>"><?php echo e(catalog_label('cta.test_drive')); ?></a>
                </div>
            </div>
        </div>
        <div class="hero-bottom">
            <?php $modelCount = \App\Support\Catalog::query('product')->published()->count(); ?>
            <span class="hero-line"><?php echo e(mb_strtoupper(catalog_setting('site_name', 'Lexus Thăng Long'))); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modelCount): ?> &nbsp; / &nbsp; <?php echo e($modelCount); ?> DÒNG XE <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></span>
            <span class="hero-tag"><?php echo e(mb_strtoupper((string) catalog_setting('opening_hours', ''))); ?></span>
            <a href="#collection">CUỘN ĐỂ KHÁM PHÁ &nbsp; ↓</a>
        </div>
    </section>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasAdvisor): ?>
        <aside class="advisor-ribbon">
            <div class="container advisor-ribbon-inner">
                <a class="advisor-identity" href="#chuyen-vien">
                    <img class="advisor-avatar" src="<?php echo e($portrait ?: asset('assets/personal/portrait-960.webp')); ?>"
                         <?php if (! ($portrait)): ?>
                             srcset="<?php echo e(asset('assets/personal/portrait-320.webp')); ?> 320w,
                                     <?php echo e(asset('assets/personal/portrait-640.webp')); ?> 640w,
                                     <?php echo e(asset('assets/personal/portrait-960.webp')); ?> 960w"
                         <?php endif; ?>
                         sizes="56px" width="960" height="960"
                         alt="Chân dung <?php echo e($advisor); ?>" loading="lazy" decoding="async">
                    <span><strong><?php echo e($advisor); ?></strong><span><?php echo e($advisorRo); ?></span></span>
                </a>
                <p>Một người đồng hành cho hành trình Lexus của bạn.</p>
                <a class="text-link" href="#chuyen-vien">Gặp người tư vấn</a>
            </div>
        </aside>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    <?php
        $homeVariants = \App\Models\ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->published())
            ->with('product.category')
            ->get()
            // Theo thứ tự dòng xe trong admin, rồi thứ tự phiên bản.
            ->sortBy(fn ($v) => sprintf('%05d-%05d', $v->product->sort, $v->sort))
            ->values();
        $homeFilters = $homeVariants->pluck('product.category')->filter()->unique('id')->sortBy('sort');
        $hasHybrid   = $homeVariants->contains(fn ($v) => preg_match('/\d{3}h\b/i', $v->name));
    ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($homeVariants->isNotEmpty()): ?>
        <section class="container model-discovery" id="collection">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Bộ sưu tập Lexus · <?php echo e($homeVariants->count()); ?> phiên bản</div>
                    <h2>Chọn đúng phiên bản của bạn.</h2>
                </div>
                <a class="text-link" href="<?php echo e(route('pages.show', 'bang-gia')); ?>">Bảng giá chi tiết</a>
            </div>

            <div class="catalog">
                <div class="filters" aria-label="Lọc phiên bản">
                    <input class="filter-input" type="radio" name="category" id="all" checked>
                    <label for="all">Tất cả</label>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $homeFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <input class="filter-input" type="radio" name="category" id="<?php echo e($item->slug); ?>">
                        <label for="<?php echo e($item->slug); ?>"><?php echo e($item->name); ?></label>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasHybrid): ?>
                        <input class="filter-input" type="radio" name="category" id="hybrid">
                        <label for="hybrid">Hybrid</label>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="model-grid variant-home-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $homeVariants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php echo $__env->make('frontend.partials.variant-card', ['variant' => $variant], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>

            <div class="section-foot">
                <small>Giá niêm yết đã gồm VAT, chưa gồm lệ phí trước bạ và phí đăng ký.</small>
                <a class="text-link" href="<?php echo e(route('booking')); ?>">Đăng ký lái thử</a>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasAdvisor): ?>
        <section class="advisor-section stone" id="chuyen-vien" aria-labelledby="advisor-title">
            <div class="container advisor-layout">
                <div class="advisor-copy">
                    <p class="eyebrow">NGƯỜI ĐỒNG HÀNH CỦA BẠN</p>
                    <h2 id="advisor-title">Chọn một chiếc xe.<br>Gặp một người<br> <span>thấu hiểu.</span></h2>

                    <div class="advisor-name">
                        <strong><?php echo e($advisor); ?></strong><span><?php echo e($advisorRo); ?></span>
                    </div>

                    <p class="advisor-intro"><?php echo e(catalog_setting('advisor_text')
                        ?: 'Một chiếc Lexus phù hợp bắt đầu từ việc hiểu điều bạn cần. Tôi ở đây để cùng bạn tìm hiểu từng lựa chọn, chuẩn bị buổi lái thử và chăm chút cho khoảnh khắc nhận xe.'); ?></p>

                    <?php
                        // Cài đặt advisor_points: mỗi dòng "Tiêu đề|Mô tả".
                        // Bỏ trống thì dùng ba cam kết của bản thiết kế.
                        $points = collect(preg_split('/\R/', (string) catalog_setting('advisor_points')))
                            ->filter()
                            ->map(fn ($line) => trim(explode('|', $line)[1] ?? explode('|', $line)[0]))
                            ->values();

                        if ($points->isEmpty()) {
                            $points = collect([
                                'Lắng nghe nhu cầu sử dụng của bạn',
                                'Cùng tìm hiểu phiên bản & chi phí',
                                'Đồng hành từ lái thử đến nhận xe',
                            ]);
                        }
                    ?>
                    <div class="advisor-promises">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $points; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div>
                                <span><?php echo e(str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT)); ?></span>
                                <p><?php echo e($point); ?></p>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>

                    <div class="actions">
                        <a class="button" href="<?php echo e(route('pages.show', 'lien-he')); ?>">Trao đổi cùng tôi</a>
                        <a class="text-link" href="#khoanh-khac">Những lần đồng hành</a>
                    </div>
                </div>

                <figure class="advisor-portrait">
                    <img src="<?php echo e($portrait ?: asset('assets/personal/portrait-960.webp')); ?>"
                         <?php if (! ($portrait)): ?>
                             srcset="<?php echo e(asset('assets/personal/portrait-320.webp')); ?> 320w,
                                     <?php echo e(asset('assets/personal/portrait-640.webp')); ?> 640w,
                                     <?php echo e(asset('assets/personal/portrait-960.webp')); ?> 960w"
                         <?php endif; ?>
                         sizes="(max-width: 600px) 100vw, 50vw" width="960" height="960"
                         alt="Chân dung <?php echo e($advisor); ?> trong không gian <?php echo e(catalog_setting('site_name')); ?>"
                         loading="lazy" decoding="async">
                    <figcaption>
                        <span>PERSONAL CONSULTATION</span>
                        <p>Sự tận tâm bắt đầu<br>từ một cuộc trò chuyện.</p>
                    </figcaption>
                </figure>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    <?php echo $__env->make('frontend.partials.sections', [
        'sections' => $homeSections,
        'numbered' => false,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasAdvisor): ?>
        <?php
            // Ba ảnh của bản thiết kế. Bước sau có thể chuyển sang Cài đặt
            // giống nhóm showroom_image_* của core.
            $moments = [
                ['key' => 'handover',    'w' => 1200, 'h' => 900,  'index' => '01 / NGÀY BÀN GIAO',   'title' => 'Một khởi đầu đáng nhớ.',            'alt' => 'Chuyên viên và khách hàng cùng cầm hộp bàn giao trước xe Lexus', 'featured' => true],
                ['key' => 'celebration', 'w' => 1200, 'h' => 900,  'index' => '02 / NIỀM VUI GẶP GỠ', 'title' => 'Cùng lưu lại niềm vui.',            'alt' => 'Khoảnh khắc cùng khách hàng bên xe Lexus và hoa tại showroom'],
                ['key' => 'delivery',    'w' => 1200, 'h' => 1609, 'index' => '03 / HÀNH TRÌNH MỚI',  'title' => 'Sẵn sàng cho chặng đường mới.',     'alt' => 'Chuyên viên và khách hàng cầm hoa bên chiếc Lexus màu trắng'],
            ];
        ?>
        <section class="container section personal-moments" id="khoanh-khac" aria-labelledby="moments-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">NHỮNG KHOẢNH KHẮC ĐỒNG HÀNH</p>
                    <h2 id="moments-title">Niềm vui ngày nhận xe.<br>Dấu ấn của một hành trình.</h2>
                </div>
                <p>Từ cuộc gặp gỡ đầu tiên đến khoảnh khắc bàn giao. Những hình ảnh lưu lại sự kết nối giữa
                    người tư vấn, khách hàng và chiếc xe được lựa chọn.</p>
            </div>

            <div class="moments-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $moments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $moment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <figure class="moment <?php echo e(($moment['featured'] ?? false) ? 'moment-featured' : ''); ?>">
                        <img src="<?php echo e(asset('assets/personal/'.$moment['key'].'-1200.webp')); ?>"
                             srcset="<?php echo e(asset('assets/personal/'.$moment['key'].'-640.webp')); ?> 640w,
                                     <?php echo e(asset('assets/personal/'.$moment['key'].'-1200.webp')); ?> 1200w"
                             sizes="<?php echo e(($moment['featured'] ?? false)
                                 ? '(max-width: 600px) calc(100vw - 40px), (max-width: 1000px) 90vw, 46vw'
                                 : '(max-width: 600px) calc(100vw - 40px), 30vw'); ?>"
                             width="<?php echo e($moment['w']); ?>" height="<?php echo e($moment['h']); ?>"
                             alt="<?php echo e($moment['alt']); ?>" loading="lazy" decoding="async">
                        <figcaption>
                            <span class="moment-index"><?php echo e($moment['index']); ?></span>
                            <h3><?php echo e($moment['title']); ?></h3>
                        </figcaption>
                    </figure>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <div class="moments-invite">
                <p>Chiếc Lexus tiếp theo sẽ kể câu chuyện của bạn.</p>
                <a class="text-link" href="<?php echo e(route('booking')); ?>">Hẹn một buổi lái thử</a>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($posts->isNotEmpty()): ?>
        <section class="container section" style="padding-top:0">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Góc nhìn Lexus</div>
                    <h2>Những câu chuyện truyền cảm hứng.</h2>
                </div>
                <a class="text-link" href="<?php echo e(route('posts.index')); ?>">Tất cả câu chuyện</a>
            </div>
            <div class="news-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php echo $__env->make('frontend.partials.post-card', ['post' => $post], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php echo $__env->make('frontend.partials.conversion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'overlay' => true,
    'popup'   => true,   // popup báo giá tự bật — cấu hình ở catalog.frontend.popup
    // Tiêu đề trang chủ mang từ khoá địa phương ("đại lý Lexus Hà Nội") —
    // sửa ở Cài đặt → seo_home_title; bỏ trống thì chỉ còn tên đại lý.
    'title'   => catalog_setting('seo_home_title') ?: catalog_setting('site_name', config('app.name')),
    'description' => catalog_setting('site_description'),
    'jsonld'  => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::organization(),
        \App\Support\JsonLd::website(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/home.blade.php ENDPATH**/ ?>
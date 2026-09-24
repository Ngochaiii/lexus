<?php $__env->startSection('content'); ?>
    <?php
        // config('catalog.frontend.booking.forms') chỉ khai một form cho bản
        // này; nếu sau có nhiều hình thức thì lấy cái đang chọn.
        $form  = $mode ?? $forms->first();
        $zalo  = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
        $phone = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    ?>

    <?php echo $__env->make('frontend.partials.page-intro', [
        'title'   => 'Cảm nhận Lexus. Bằng chính bạn.',
        'eyebrow' => 'KẾT NỐI CÙNG LEXUS',
        'text'    => 'Chọn một khoảng thời gian thư thái để khám phá chiếc Lexus bạn yêu thích.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="container section">
        <div class="lead-layout">
            <div class="lead-copy">
                <?php echo $__env->make('frontend.partials.advisor-mini', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <div class="eyebrow">TƯ VẤN CÁ NHÂN</div>
                <h2>Hân hạnh<br>được đón tiếp bạn.</h2>

                <?php echo $__env->make('frontend.partials.contact-details', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($zalo)): ?>
                    <div id="zalo" class="notice">Zalo:
                        <a href="<?php echo e(\Illuminate\Support\Str::startsWith($zalo, 'http') ? $zalo : 'https://zalo.me/'.$zalo); ?>"
                           rel="noopener"><u><?php echo e(\App\Support\Phone::format($phone)); ?></u></a>
                        — nhắn tin trực tiếp cho <?php echo e(catalog_setting('advisor_name', 'chuyên viên tư vấn')); ?>,
                        phản hồi trong giờ làm việc.</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($form): ?>
                <?php echo $__env->make('frontend.partials.lead-form', [
                    'formKey'     => $form->key,
                    'submitLabel' => $form->name,
                    'products'    => $products,
                    'selected'    => $selected,
                    'instance'    => 'trang',
                    'note'        => 'Chuyên viên tư vấn sẽ gọi lại để xác nhận thời gian và địa điểm lái thử.',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <p>Form đăng ký chưa được cấu hình. Vui lòng liên hệ trực tiếp qua hotline.</p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'title'       => 'Đăng ký lái thử xe Lexus tại Hà Nội | '.catalog_setting('site_name', config('app.name')),
    'description' => 'Đặt lịch lái thử xe Lexus (SUV, Sedan, MPV) tại showroom Lexus Thăng Long, Cầu Giấy, Hà Nội. Chuyên viên tư vấn gọi lại xác nhận lịch hẹn.',
    'jsonld'      => \App\Support\JsonLd::graph(
        \App\Support\JsonLd::forBreadcrumb([
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Đăng ký lái thử', 'url' => route('booking')],
        ]),
        \App\Support\JsonLd::organization(),
    ),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/booking.blade.php ENDPATH**/ ?>
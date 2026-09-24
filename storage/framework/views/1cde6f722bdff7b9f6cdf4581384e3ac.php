<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('frontend.partials.page-intro', [
        'title'   => 'Nhận báo giá dành riêng cho bạn.',
        'eyebrow' => 'BÁO GIÁ LEXUS',
        'text'    => 'Chọn dòng xe bạn quan tâm. Chuyên viên tư vấn sẽ gửi báo giá lăn bánh và phương án sở hữu phù hợp.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="container section">
        <div class="lead-layout">
            <div class="lead-copy">
                <?php echo $__env->make('frontend.partials.advisor-mini', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <div class="eyebrow">TƯ VẤN CÁ NHÂN</div>
                <h2>Một báo giá.<br>Rõ ràng từng khoản.</h2>
                <p>Báo giá gồm giá xe, lệ phí trước bạ, biển số, đăng kiểm và bảo hiểm theo nơi bạn đăng ký
                    xe. Không phát sinh chi phí khi yêu cầu báo giá.</p>

                <?php echo $__env->make('frontend.partials.contact-details', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <?php echo $__env->make('frontend.partials.lead-form', [
                'formKey'     => $form->key,
                'submitLabel' => 'Nhận báo giá',
                'products'    => $products,
                'selected'    => $selected,
                'variant'     => $selectedVariant ?? null,
                'instance'    => 'trang',
                'note'        => 'Chuyên viên tư vấn sẽ gửi báo giá trong giờ làm việc. Thông tin của bạn chỉ dùng cho mục đích tư vấn.',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layout', [
    'title'       => 'Nhận báo giá xe Lexus | '.catalog_setting('site_name', config('app.name')),
    'description' => 'Để lại thông tin để nhận báo giá lăn bánh và phương án sở hữu Lexus phù hợp từ chuyên viên tư vấn '.catalog_setting('site_name', 'Lexus Thăng Long').'.',
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/quote.blade.php ENDPATH**/ ?>
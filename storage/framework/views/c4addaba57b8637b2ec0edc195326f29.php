
<?php
    $formKey     = $formKey     ?? 'dang-ky-lai-thu';
    $submitLabel = $submitLabel ?? 'Gửi đăng ký';
    $honeypot    = config('catalog.leads.honeypot', 'website');
    $idp         = \Illuminate\Support\Str::slug($formKey.'-'.($instance ?? 'form'));

    $products = $products ?? once(fn () => \App\Support\Catalog::query('product')
        ->published()->orderBy('sort')->get(['id', 'slug', 'name']));

    // Paginator mà đưa vào collect() sẽ thành mảng current_page/per_page…
    // chứ không phải danh sách xe — lấy đúng phần dữ liệu ra.
    $products = $products instanceof \Illuminate\Contracts\Pagination\Paginator
        ? collect($products->items())
        : collect($products);

    $selected = $selected ?? null;
    $selected = $selected instanceof \Illuminate\Database\Eloquent\Model ? $selected->getKey() : $selected;

    $variant = ($variant ?? null) instanceof \Illuminate\Database\Eloquent\Model ? $variant : null;

    $formModel = once(fn () => \App\Support\Catalog::query('form')->pluck('success_message', 'key'));
    $successMessage = $formModel[$formKey] ?? 'Đã nhận thông tin của bạn. Chuyên viên tư vấn sẽ liên hệ sớm.';

    $note = $note ?? 'Chuyên viên tư vấn sẽ gọi lại trong giờ làm việc. Thông tin của bạn chỉ dùng cho mục đích tư vấn.';

    // Lỗi tách theo bag $formKey (xem StoreLead) — chỉ đọc lại giá trị cũ
    // khi CHÍNH form này vừa lỗi, không thì form khác trên trang bị điền lây.
    $bag        = isset($errors) ? $errors->getBag($formKey) : new \Illuminate\Support\MessageBag;
    $isThisForm = $bag->any();
    $sent       = session('lead_success') && session('lead_form_key') === $formKey;
    $old        = fn (string $key) => $isThisForm ? old($key) : null;
?>

<form class="lead-form" action="<?php echo e(route('leads.store', $formKey)); ?>" method="post"
      data-lead-form data-success="<?php echo e($successMessage); ?>">
    <?php echo csrf_field(); ?>

    
    <div class="notice full" data-lead-status role="status" aria-live="polite" <?php if (! ($sent)): ?> hidden <?php endif; ?>>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sent): ?><?php echo e(session('lead_success')); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <div class="honeypot" hidden aria-hidden="true">
        <label for="<?php echo e($idp); ?>-<?php echo e($honeypot); ?>">Để trống ô này</label>
        <input id="<?php echo e($idp); ?>-<?php echo e($honeypot); ?>" name="<?php echo e($honeypot); ?>" tabindex="-1" autocomplete="off">
    </div>

    <div class="field">
        <label for="<?php echo e($idp); ?>-name">Họ và tên *</label>
        <input id="<?php echo e($idp); ?>-name" name="name" autocomplete="name" placeholder="Nguyễn Minh Anh"
               value="<?php echo e($old('name')); ?>" required maxlength="100">
        <p class="form-note field-error" data-error-for="name" <?php if (! ($bag->has('name'))): ?> hidden <?php endif; ?>>
            <?php echo e($bag->first('name')); ?></p>
    </div>

    <div class="field">
        <label for="<?php echo e($idp); ?>-phone">Số điện thoại *</label>
        <input id="<?php echo e($idp); ?>-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel"
               placeholder="09xx xxx xxx" pattern="[+0-9 ().-]{9,18}"
               value="<?php echo e($old('phone')); ?>" required>
        <p class="form-note field-error" data-error-for="phone" <?php if (! ($bag->has('phone'))): ?> hidden <?php endif; ?>>
            <?php echo e($bag->first('phone')); ?></p>
    </div>

    
    <input type="hidden" name="variant_id" value="<?php echo e($variant?->getKey()); ?>" data-variant-input>
    <p class="variant-chip full" data-variant-chip <?php if (! ($variant)): ?> hidden <?php endif; ?>>
        Phiên bản: <strong data-variant-name><?php echo e($variant?->name); ?></strong>
    </p>

    <div class="field full">
        <label for="<?php echo e($idp); ?>-product">Dòng xe quan tâm *</label>
        <select id="<?php echo e($idp); ?>-product" name="product_id" required data-product-select>
            <option value="">Chọn dòng xe</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php $value = (string) $item->getKey(); ?>
                <option value="<?php echo e($value); ?>" data-slug="<?php echo e($item->slug); ?>"
                        <?php if($isThisForm ? old('product_id') === $value : (string) $selected === $value): echo 'selected'; endif; ?>><?php echo e($item->name); ?></option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </select>
        <p class="form-note field-error" data-error-for="product_id" <?php if (! ($bag->has('product_id'))): ?> hidden <?php endif; ?>>
            <?php echo e($bag->first('product_id')); ?></p>
    </div>

    <label class="consent full">
        
        <input type="checkbox" name="consent[]" value="1" required>
        <span>Tôi đã đọc <a href="<?php echo e(route('pages.show', 'quyen-rieng-tu')); ?>"><u>chính sách quyền
            riêng tư</u></a> và đồng ý với mục đích tư vấn được mô tả.</span>
    </label>
    <p class="form-note field-error full" data-error-for="consent" <?php if (! ($bag->has('consent'))): ?> hidden <?php endif; ?>>
        <?php echo e($bag->first('consent')); ?></p>

    <button class="button full" type="submit"><?php echo e($submitLabel); ?> &nbsp; ↗</button>

    <p class="form-note full"><?php echo e($note); ?></p>
</form>
<?php /**PATH /Users/Shared/dự án /lexus/resources/views/frontend/partials/lead-form.blade.php ENDPATH**/ ?>
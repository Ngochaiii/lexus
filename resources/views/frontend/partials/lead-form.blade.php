{{--
    Form thu lead — dùng cho đăng ký lái thử, báo giá và popup.

    Khách chỉ nhập tên, số điện thoại, chọn xe rồi gửi. Chuyên viên hẹn giờ
    khi gọi lại, nên không hỏi ngày/khung giờ.

    Theo hợp đồng của `StoreLead`:
      · POST route('leads.store', $formKey), kèm @csrf
      · ô bẫy bot tên config('catalog.leads.honeypot')
      · chọn xe gửi `product_id` → StoreLead ghi vào leads.product_id, nên
        cột "Dòng xe" trong danh sách Lead ở admin có dữ liệu
      · lỗi validate nằm trong error bag đặt theo $formKey

    Hai đường gửi, cùng một form:
      · Không JS: POST thường → redirect về trang cũ kèm session('lead_success')
      · Có JS (public/assets/lead.js): gửi bằng fetch, nhận JSON, hiện kết
        quả ngay trong form — dùng cho popup, để popup không bị đóng mất
        khi trang tải lại.

    Biến:
      $formKey      khoá form (bắt buộc có bản ghi trong bảng forms)
      $submitLabel  chữ trên nút gửi
      $products     Collection model Product; bỏ trống thì tự lấy xe đã đăng
      $selected     id xe chọn sẵn
      $variant      phiên bản chọn sẵn (ProductVariant) — gửi kèm variant_id ẩn;
                    popup thì lead.js điền khi khách bấm từ thẻ phiên bản
      $note         dòng ghi chú dưới nút; bỏ trống thì dùng câu mặc định
      $instance     hậu tố id — BẮT BUỘC khác nhau khi một trang có hai form
                    (trang chi tiết xe có form cuối trang + popup trong layout),
                    không thì id="name" trùng và nhãn trỏ nhầm ô
--}}
@php
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
@endphp

<form class="lead-form" action="{{ route('leads.store', $formKey) }}" method="post"
      data-lead-form data-success="{{ $successMessage }}">
    @csrf

    {{-- Kết quả gửi. aria-live để trình đọc màn hình đọc lên khi JS điền vào. --}}
    <div class="notice full" data-lead-status role="status" aria-live="polite" @unless ($sent) hidden @endunless>
        @if ($sent){{ session('lead_success') }}@endif
    </div>

    {{-- Bẫy bot. `hidden` một mình không đủ vì .field có display:flex; class
         .honeypot trong style.css khoá lại bằng !important. --}}
    <div class="honeypot" hidden aria-hidden="true">
        <label for="{{ $idp }}-{{ $honeypot }}">Để trống ô này</label>
        <input id="{{ $idp }}-{{ $honeypot }}" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
    </div>

    <div class="field">
        <label for="{{ $idp }}-name">Họ và tên *</label>
        <input id="{{ $idp }}-name" name="name" autocomplete="name" placeholder="Nguyễn Minh Anh"
               value="{{ $old('name') }}" required maxlength="100">
        <p class="form-note field-error" data-error-for="name" @unless ($bag->has('name')) hidden @endunless>
            {{ $bag->first('name') }}</p>
    </div>

    <div class="field">
        <label for="{{ $idp }}-phone">Số điện thoại *</label>
        <input id="{{ $idp }}-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel"
               placeholder="09xx xxx xxx" pattern="[+0-9 ().-]{9,18}"
               value="{{ $old('phone') }}" required>
        <p class="form-note field-error" data-error-for="phone" @unless ($bag->has('phone')) hidden @endunless>
            {{ $bag->first('phone') }}</p>
    </div>

    {{-- Phiên bản khách bấm chọn (thẻ phiên bản). Đổi dòng xe thì lead.js xoá
         đi; máy chủ cũng tự bỏ nếu phiên bản không thuộc dòng xe đã chọn. --}}
    <input type="hidden" name="variant_id" value="{{ $variant?->getKey() }}" data-variant-input>
    <p class="variant-chip full" data-variant-chip @unless ($variant) hidden @endunless>
        Phiên bản: <strong data-variant-name>{{ $variant?->name }}</strong>
    </p>

    <div class="field full">
        <label for="{{ $idp }}-product">Dòng xe quan tâm *</label>
        <select id="{{ $idp }}-product" name="product_id" required data-product-select>
            <option value="">Chọn dòng xe</option>
            @foreach ($products as $item)
                @php $value = (string) $item->getKey(); @endphp
                <option value="{{ $value }}" data-slug="{{ $item->slug }}"
                        @selected($isThisForm ? old('product_id') === $value : (string) $selected === $value)>{{ $item->name }}</option>
            @endforeach
        </select>
        <p class="form-note field-error" data-error-for="product_id" @unless ($bag->has('product_id')) hidden @endunless>
            {{ $bag->first('product_id') }}</p>
    </div>

    <label class="consent full">
        {{-- name="consent[]": core validate kiểu checkbox bằng rule `array`. --}}
        <input type="checkbox" name="consent[]" value="1" required>
        <span>Tôi đã đọc <a href="{{ route('pages.show', 'quyen-rieng-tu') }}"><u>chính sách quyền
            riêng tư</u></a> và đồng ý với mục đích tư vấn được mô tả.</span>
    </label>
    <p class="form-note field-error full" data-error-for="consent" @unless ($bag->has('consent')) hidden @endunless>
        {{ $bag->first('consent') }}</p>

    <button class="button full" type="submit">{{ $submitLabel }} &nbsp; ↗</button>

    <p class="form-note full">{{ $note }}</p>
</form>

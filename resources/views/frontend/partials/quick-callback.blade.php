{{--
    Form "để lại số" một ô — đặt trên bảng giá /bang-gia (26/10/2026).

    Vì sao: Ads báo 4 lượt bấm Gọi/Zalo trong tuần mà chị Hà không nhận được
    cuộc gọi nào — chạm nút không chắc thành cuộc gọi. Số khách để lại thì nằm
    trên máy chủ (Admin → Liên hệ) và mail báo về, không thất lạc. Chỉ hỏi số
    điện thoại: khách đang xem giá, gõ số là xong.

    Gửi như mọi form lead (lead.js: fetch + khung cảm ơn; không JS thì POST
    thường). Form `goi-lai-nhanh` tạo bằng migration 2026_10_26_100000; chưa
    có bản ghi thì không hiện, tránh nút gửi vào 404.
--}}
@php
    $quickForm = once(fn () => \App\Support\Catalog::query('form')->where('key', 'goi-lai-nhanh')->first());
    $advisor   = catalog_setting('advisor_name') ?: 'chuyên viên';
    $honeypot  = config('catalog.leads.honeypot', 'website');
@endphp
@if ($quickForm)
    <form class="quick-callback" action="{{ route('leads.store', 'goi-lai-nhanh') }}" method="post"
          data-lead-form data-success="{{ $quickForm->success_message }}">
        @csrf

        <div class="quick-callback__fields">
            <label class="quick-callback__label" for="quick-callback-phone">
                Cần giá lăn bánh chi tiết? Để lại số, {{ $advisor }} gọi lại cho bạn.</label>
            <div class="quick-callback__row">
                <input id="quick-callback-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel"
                       placeholder="Số điện thoại của bạn" pattern="[+0-9 .\(\)\-]{9,18}" required>
                <button class="button" type="submit">Gọi lại cho tôi</button>
            </div>
            <p class="form-note field-error" data-error-for="phone" hidden></p>
            <label class="consent">
                <input type="checkbox" name="consent[]" value="1" required>
                <span>Tôi đồng ý với <a href="{{ route('pages.show', 'quyen-rieng-tu') }}"><u>chính sách quyền riêng tư</u></a>.</span>
            </label>
            <p class="form-note field-error" data-error-for="consent" hidden></p>
        </div>

        <div class="honeypot" hidden aria-hidden="true">
            <label for="quick-callback-{{ $honeypot }}">Để trống ô này</label>
            <input id="quick-callback-{{ $honeypot }}" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>

        <div class="notice" data-lead-status role="status" aria-live="polite" hidden></div>
        <div class="lead-sent" data-lead-sent tabindex="-1" hidden>
            <p class="lead-sent__title">Đã gửi thành công</p>
            <p class="lead-sent__message" data-lead-sent-message></p>
        </div>
    </form>
@endif

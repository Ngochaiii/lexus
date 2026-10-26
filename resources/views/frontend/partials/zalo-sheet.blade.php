{{--
    Khung Zalo dự phòng — một <dialog> dùng chung cho mọi nút Zalo của site.

    Vì sao (25/10/2026): 5 lượt bấm Gọi/Zalo từ quảng cáo mà chị Hà không nhận
    được gì; một khách bấm Zalo hai lần cách 9 giây (Zalo không mở). Link
    zalo.me/<số> của Zalo cá nhân chỉ mở trang cá nhân — không có cách mở
    thẳng khung chat kèm lời nhắn soạn sẵn (cái đó cần Zalo OA).

    Nên bấm Zalo bất kỳ (lead.js): link vẫn mở Zalo như cũ, đồng thời chép sẵn
    lời nhắn có tên xe vào bộ nhớ máy và mở khung này. Khách vào Zalo chỉ còn
    "Nhắn tin → Dán → Gửi"; quay lại web (Zalo không mở) thì có sẵn: mở lại
    Zalo, chép số Zalo, gọi, để lại số.

    Lời nhắn = data-greeting + tên xe (data-zalo-car của nút, hoặc data-car
    của trang) + " ạ.". Không JS thì khung không bao giờ mở, link Zalo chạy
    như bình thường.
--}}
@php
    $zaloRaw = catalog_setting('advisor_zalo') ?: catalog_setting('zalo');
    $zalo    = $zaloRaw ? (\Illuminate\Support\Str::startsWith($zaloRaw, 'http') ? $zaloRaw : 'https://zalo.me/'.$zaloRaw) : null;
    $zaloFmt = \App\Support\Phone::zalo($zaloRaw);
    $phone   = catalog_setting('advisor_phone') ?: catalog_setting('hotline');
    $advisor = catalog_setting('advisor_name');
    // "Thu Hà" → "chị Hà": cách khách gọi chị trong tin nhắn đầu tiên.
    $callee  = $advisor ? 'chị '.\Illuminate\Support\Str::afterLast(trim($advisor), ' ') : 'chị';
    $car     = ($product ?? null) instanceof \App\Models\Product ? $product->name : 'xe Lexus';
@endphp
@if ($zalo)
    <dialog class="zalo-sheet" id="zalo-sheet" aria-labelledby="zalo-sheet-title"
            data-greeting="Chào {{ $callee }}, em muốn hỏi giá lăn bánh" data-car="{{ $car }}">
        <button type="button" class="quote-dialog__close" data-dialog-close aria-label="Đóng">×</button>

        <h2 id="zalo-sheet-title">Nhắn Zalo {{ $advisor ?: 'chuyên viên' }}</h2>

        <p class="zalo-sheet__copied" data-zalo-copied hidden>✓ Đã chép sẵn lời nhắn:</p>
        <blockquote class="zalo-sheet__message" data-zalo-message></blockquote>
        <p class="zalo-sheet__steps">Trong Zalo bấm <b>Nhắn tin</b> → giữ vào ô chat → <b>Dán</b> → <b>Gửi</b>.</p>

        <div class="zalo-sheet__actions">
            <a class="button" href="{{ $zalo }}" rel="noopener" target="_blank" data-zalo-again>Mở Zalo</a>
            <button type="button" class="button outline" data-copy-message>Chép lại lời nhắn</button>
        </div>

        <p class="zalo-sheet__fallback">Zalo không mở?</p>
        <ul class="zalo-sheet__list">
            @if ($zaloFmt)
                <li><button type="button" class="text-link" data-copy="{{ preg_replace('/\D/', '', $zaloFmt) }}">Chép số Zalo {{ $zaloFmt }}</button>
                    <small>rồi tìm số trong Zalo</small></li>
            @endif
            @if ($phone)
                <li><a class="text-link" href="tel:{{ $phone }}">Gọi {{ $advisor ?: 'chuyên viên' }} {{ \App\Support\Phone::format($phone) }}</a></li>
            @endif
            <li><a class="text-link" href="{{ route('quote') }}" data-quote>Để lại số, {{ $advisor ?: 'chuyên viên' }} gọi lại</a></li>
        </ul>
        <p class="zalo-sheet__status" data-copy-status role="status" hidden></p>
    </dialog>
@endif

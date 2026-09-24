{{--
    Mục hỏi đáp: accordion <details> (không JS) theo hệ CSS Lexus.

    JSON-LD FAQPage cho mục này do trang cha gom lại (JsonLd::forFaq) chứ
    không in ở đây — một trang chỉ nên có MỘT khối FAQPage, gộp mọi câu hỏi.

    Câu hỏi đầu tiên mở sẵn: khách thấy ngay dạng nội dung, và công cụ tìm
    kiếm đọc được câu trả lời mà không phải "bấm".
--}}
@php $rows = $section['rows'] ?? []; @endphp
@if ($rows)
    <section class="container section" id="muc-{{ $index }}">
        <div class="section-heading">
            <div>
                @if (filled($title))<div class="eyebrow">{{ mb_strtoupper($title) }}</div>@endif
                <h2>{{ $intro ?: 'Câu hỏi thường gặp' }}</h2>
            </div>
        </div>
        <div class="accordion">
            @foreach ($rows as $row)
                <details @if ($loop->first) open @endif>
                    <summary>{{ $row['label'] ?? '' }}</summary>
                    <p>{!! nl2br(e($row['value'] ?? '')) !!}</p>
                </details>
            @endforeach
        </div>
    </section>
@endif

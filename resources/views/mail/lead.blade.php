<x-mail::message>
# {{ $lead->status === 'spam' ? 'Nghi spam (đã để vào mục Spam)' : 'Khách mới để lại số' }}

Web: **{{ $site ?: config('app.url') }}** · form **{{ $lead->form?->name ?? '—' }}**

<x-mail::table>
| | |
| --- | --- |
| **Họ tên** | {{ $lead->name ?: '(không ghi)' }} |
| **Điện thoại** | @if ($lead->phone)[{{ $lead->phone }}](tel:{{ $lead->phone }})@else(không ghi)@endif |
@if ($lead->email)| **Email** | {{ $lead->email }} |
@endif
@if ($lead->product)| **Xe quan tâm** | {{ $lead->product->name }}{{ $variant ? ' · '.$variant : '' }} |
@endif
@foreach ($extra as $label => $value)| **{{ $label }}** | {{ $value }} |
@endforeach
| **Nguồn** | {{ $lead->sourceSummary() }} |
@if ($lead->landing_page)| **Trang vào** | {{ $lead->landing_page }} |
@endif
| **Lúc** | {{ $lead->created_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }} |
</x-mail::table>

@if ($lead->status === 'spam' && $lead->note)
<x-mail::subcopy>
{{ $lead->note }}
</x-mail::subcopy>
@endif

<x-mail::button :url="url('/admin/leads')">
Xem trong Admin
</x-mail::button>
</x-mail::message>

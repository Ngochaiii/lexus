<x-mail::message>
# Liên hệ mới

Có khách để lại thông tin qua form **{{ $lead->form?->name ?? '—' }}**.
@if ($lead->product)
Đang quan tâm: **{{ $lead->product->name }}**.
@endif

<x-mail::table>
| | |
| --- | --- |
@if ($lead->name)| **Họ tên** | {{ $lead->name }} |@endif

@if ($lead->phone)| **Điện thoại** | {{ $lead->phone }} |@endif

@if ($lead->email)| **Email** | {{ $lead->email }} |@endif

</x-mail::table>

@if (filled($lead->utm))
<x-mail::subcopy>
Nguồn: {{ collect($lead->utm)->map(fn ($v, $k) => "$k=$v")->implode(' · ') }}
</x-mail::subcopy>
@endif

<x-mail::button :url="config('app.url').'/admin/leads'">
Xem trong admin
</x-mail::button>
</x-mail::message>

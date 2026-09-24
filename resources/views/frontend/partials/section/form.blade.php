{{--
    Nhúng form theo `form_key`. Form đã tắt (is_active = false) hoặc bị xoá
    thì mục này im lặng không render — không để trang khách gặp lỗi.
--}}
@php
    $form = catalog_feature('forms') && filled($section['form_key'] ?? null)
        ? \App\Support\Catalog::query('form')
            ->with('fields')
            ->where('key', $section['form_key'])
            ->where('is_active', true)
            ->first()
        : null;
@endphp

@if ($form)
    @include('frontend.partials.lead-form', ['form' => $form])
@endif

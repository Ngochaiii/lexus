{{-- Mục form nhúng giữa trang. --}}
@php
    $form = \App\Support\Catalog::query('form')->with('fields')
        ->where('key', $section['form_key'] ?? '')->where('is_active', true)->first();
@endphp
@if ($form)
    <section class="section stone" id="muc-{{ $index }}">
        <div class="container lead-layout">
            <div class="lead-copy">
                <div class="eyebrow">{{ mb_strtoupper($title ?? $form->name) }}</div>
                @if ($intro)<h2>{{ $intro }}</h2>@endif
            </div>
            @include('frontend.partials.lead-form', ['formKey' => $form->key, 'submitLabel' => $form->name])
        </div>
    </section>
@endif

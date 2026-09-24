@php
    $studioOptions = catalog_feature('options') ? $options->map(function ($option) {
        $frames = collect($option->spin_frames ?? [])->filter(fn ($path) => is_string($path) && filled($path))
            ->map(fn ($path) => catalog_image($path))->filter()->values()->all();
        return [
            'name' => $option->name,
            'hex' => preg_match('/^#[0-9a-f]{6}$/i', $option->hex ?? '') ? $option->hex : '#bcb8b0',
            'image' => catalog_image($option->image),
            'frames' => $frames,
            // Ảnh nhỏ nhất thực sự hiển thị — quyết định khung. Có bộ xoay thì
            // chỉ xét ảnh xoay (ảnh đại diện không được hiện nữa).
            'w' => collect(filled($option->spin_frames) ? $option->spin_frames : [$option->image])
                ->map(fn ($p) => \App\Support\Media::dimensions($p)['w'] ?? null)->filter()->min(),
        ];
    })->values()->all() : [];
    $firstLook = $studioOptions[0] ?? null;

    // Ảnh nhỏ thì phóng tối đa 2 lần rồi thôi — thà nhỏ gọn giữa sân khấu còn
    // hơn to mà nhoè. Tính theo màu hiển thị đầu tiên; đổi màu thì
    // vehicle-studio.js tính lại theo màu mới (fitStage).
    $firstW     = $firstLook['w'] ?? null;
    $stageLimit = $firstW && $firstW < 900 ? max(600, (int) round($firstW * 2)) : null;
@endphp
@if ($firstLook)
<section class="vehicle-studio" id="vehicle-studio" data-vehicle-studio aria-labelledby="studio-title">
    <div class="container">
        <div class="studio-heading">
            <div><p class="eyebrow">THIẾT KẾ THEO DẤU ẤN CỦA BẠN</p><h2 id="studio-title">Cuốn hút. Từ mọi góc nhìn.</h2></div>
            <p>{{ $product->name }}<span>Ngoại thất & sắc màu</span></p>
        </div>
        <div class="studio-layout">
            <div class="studio-display">
                <div class="studio-stage {{ $stageLimit ? 'is-small' : '' }}" data-stage @if ($stageLimit) style="--stage-native: {{ $stageLimit }}px" @endif aria-label="Góc nhìn ngoại thất {{ $product->name }}">
                    <img data-studio-image src="{{ $firstLook['frames'][0] ?? $firstLook['image'] ?? $heroImage }}"
                         alt="{{ $product->name }} — {{ ($firstLook['image'] || $firstLook['frames']) ? $firstLook['name'] : 'Ảnh tổng quan' }}"
                         width="1600" height="1000" loading="lazy" decoding="async" draggable="false">
                    <span class="studio-stage-label" data-mode>Ngoại thất</span>
                    <button type="button" class="studio-expand" data-expand hidden aria-label="Phóng lớn ảnh xe">⛶</button>
                </div>
                <div class="studio-controls" data-controls hidden>
                    <button type="button" data-prev aria-label="Xoay xe sang trái">←</button>
                    <label><span class="studio-sr">Góc quay xe</span><input data-angle type="range" min="0" max="17" value="0" aria-label="Góc quay xe"></label>
                    <button type="button" data-next aria-label="Xoay xe sang phải">→</button>
                    <output data-angle-label>0°</output>
                </div>
                <p class="studio-hint" data-hint>Khám phá màu sắc ngoại thất.</p>
            </div>
            <div class="studio-selection">
                <p class="eyebrow">BỘ SƯU TẬP MÀU SẮC</p>
                <h3 data-color-name>{{ $firstLook['name'] }}</h3>
                <div class="studio-swatches" role="group" aria-label="Chọn màu ngoại thất">
                    @foreach ($studioOptions as $look)
                        <button type="button" data-color="{{ $loop->index }}" style="--paint:{{ $look['hex'] }}"
                                aria-label="{{ $look['name'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" disabled>
                            <span aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>
                <p class="studio-status" data-status role="status" aria-live="polite">{{ ($firstLook['image'] || $firstLook['frames']) ? 'Hình ảnh màu sắc có thể thay đổi theo màn hình và phiên bản.' : 'Ảnh tổng quan minh họa. Vui lòng liên hệ để xem màu thực tế.' }}</p>
                <div class="studio-divider"></div>
                <p>Một sắc màu phù hợp.<br>Một dấu ấn rất riêng.</p>
                {{-- Vừa chọn được màu ưng ý = lúc khách sẵn lòng để lại số nhất. --}}
                <div class="studio-actions">
                    <a class="button" href="{{ route('quote', ['xe' => $product->slug]) }}" data-quote data-product="{{ $product->id }}">Nhận báo giá</a>
                    <a class="text-link" href="{{ route('booking', ['xe' => $product->slug]) }}">Lái thử màu này</a>
                </div>
                <noscript><p>Bật JavaScript để chọn màu và xoay xe. Bạn vẫn có thể xem ảnh tổng quan và liên hệ tư vấn.</p></noscript>
            </div>
        </div>
    </div>
    <script type="application/json" data-studio-data>{!! json_encode(['model' => $product->name, 'fallback' => $heroImage, 'options' => $studioOptions], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <dialog class="studio-dialog" data-studio-dialog aria-label="Ảnh ngoại thất phóng lớn">
        <button type="button" data-close aria-label="Đóng ảnh phóng lớn">Đóng ×</button>
        <img data-large-image alt="" width="1600" height="1000">
    </dialog>
</section>
@push('scripts')
    <script src="{{ asset('assets/vehicle-studio.js') }}?v={{ filemtime(public_path('assets/vehicle-studio.js')) }}" defer></script>
@endpush
@endif

{{-- Trang Báo cáo (App\Filament\Pages\Reports). Bảng HTML thuần + style tại chỗ
     để không phụ thuộc class Tailwind của theme admin; hợp cả sáng lẫn tối. --}}
@php
    $money = fn ($v) => $v ? catalog_money($v) : '—';
    $pct = fn ($v) => $v === null ? '—' : number_format($v, 1, ',', '.').'%';
    $ratingColor = ['good' => '#16a34a', 'needs' => '#d97706', 'poor' => '#dc2626'];
    $ratingText = ['good' => 'Tốt', 'needs' => 'Cần cải thiện', 'poor' => 'Kém'];
    $fmtVital = fn ($metric, $v) => $v === null ? '—' : ($metric === 'CLS' ? number_format($v, 3, ',', '.') : number_format($v / 1000, 2, ',', '.').' s');
@endphp
<x-filament-panels::page>
    <style>
        .rp-tabs{display:flex;gap:8px;flex-wrap:wrap}
        .rp-tabs button{padding:6px 14px;border:1px solid rgba(127,127,127,.35);border-radius:999px;font-size:14px}
        .rp-tabs button[aria-pressed=true]{background:rgba(245,158,11,.18);border-color:#f59e0b;font-weight:600}
        .rp-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}
        .rp-kpi{padding:14px 16px;border:1px solid rgba(127,127,127,.25);border-radius:12px}
        .rp-kpi small{display:block;font-size:12px;opacity:.7;text-transform:uppercase;letter-spacing:.06em}
        .rp-kpi strong{display:block;font-size:24px;line-height:1.3;margin-top:4px}
        .rp-table{width:100%;border-collapse:collapse;font-size:14px}
        .rp-table th,.rp-table td{padding:8px 10px;border-bottom:1px solid rgba(127,127,127,.2);text-align:left}
        .rp-table th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;opacity:.7}
        .rp-table td.num,.rp-table th.num{text-align:right;font-variant-numeric:tabular-nums}
        .rp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px}
        .rp-bar{height:8px;border-radius:4px;background:#f59e0b;min-width:2px}
        .rp-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;vertical-align:middle}
        .rp-note{font-size:13px;opacity:.7;margin-top:8px}
    </style>

    <div class="rp-tabs" role="group" aria-label="Khoảng thời gian">
        @foreach ([7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày', 365 => '12 tháng'] as $d => $label)
            <button type="button" wire:click="setDays({{ $d }})" aria-pressed="{{ $days === $d ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    <x-filament::section heading="Tổng quan" description="Lead thật (không tính spam) nhận trong {{ $days }} ngày qua; hợp đồng và hoa hồng tính theo ngày giao xe.">
        <div class="rp-kpis">
            <div class="rp-kpi"><small>Lead mới</small><strong>{{ $overview['leads'] }}</strong></div>
            <div class="rp-kpi"><small>Đang chăm sóc</small><strong>{{ $overview['open'] }}</strong></div>
            <div class="rp-kpi"><small>Đã giao xe</small><strong>{{ $overview['won'] }}</strong></div>
            <div class="rp-kpi"><small>Tỷ lệ chốt</small><strong>{{ $pct($overview['rate']) }}</strong></div>
            <div class="rp-kpi"><small>Giá trị hợp đồng</small><strong>{{ $money($overview['deal_value']) }}</strong></div>
            <div class="rp-kpi"><small>Hoa hồng</small><strong>{{ $money($overview['commission']) }}</strong></div>
            <div class="rp-kpi" style="{{ $overview['due'] ? 'border-color:#dc2626' : '' }}"><small>Cần gọi lại hôm nay</small><strong>{{ $overview['due'] }}</strong></div>
        </div>
    </x-filament::section>

    <div class="rp-grid">
        <x-filament::section heading="Phễu bán hàng" description="Số lead đang ở từng bước.">
            @php $max = max(1, max($funnel)); @endphp
            <table class="rp-table">
                @foreach (\App\Models\Lead::STATUSES as $key => $label)
                    <tr>
                        <td style="width:40%">{{ $label }}</td>
                        <td><div class="rp-bar" style="width:{{ round($funnel[$key] / $max * 100) }}%"></div></td>
                        <td class="num" style="width:50px">{{ $funnel[$key] }}</td>
                    </tr>
                @endforeach
            </table>
        </x-filament::section>

        <x-filament::section heading="Nguồn khách" description="Lần đầu khách vào web từ đâu → bao nhiêu lead, chốt bao nhiêu.">
            @if ($sources)
                <table class="rp-table">
                    <tr><th>Nguồn</th><th class="num">Lead</th><th class="num">Giao xe</th><th class="num">Tỷ lệ chốt</th></tr>
                    @foreach ($sources as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['leads'] }}</td><td class="num">{{ $row['won'] }}</td><td class="num">{{ $pct($row['rate']) }}</td></tr>
                    @endforeach
                </table>
            @else
                <p class="rp-note">Chưa có lead trong khoảng này.</p>
            @endif
        </x-filament::section>

        <x-filament::section heading="Phiên bản được hỏi" description="Khách hỏi giá mẫu nào nhiều nhất, chốt được mẫu nào.">
            @if ($variants)
                <table class="rp-table">
                    <tr><th>Phiên bản</th><th class="num">Lead</th><th class="num">Giao xe</th><th class="num">Tỷ lệ</th></tr>
                    @foreach ($variants as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['leads'] }}</td><td class="num">{{ $row['won'] }}</td><td class="num">{{ $pct($row['rate']) }}</td></tr>
                    @endforeach
                </table>
            @else
                <p class="rp-note">Chưa có lead trong khoảng này.</p>
            @endif
        </x-filament::section>

        <x-filament::section heading="Lý do không mua">
            @if ($lost)
                <table class="rp-table">
                    @foreach ($lost as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['count'] }}</td></tr>
                    @endforeach
                </table>
            @else
                <p class="rp-note">Chưa có lead "Không mua" — nhớ chọn lý do khi đóng lead để biết mất khách vì đâu.</p>
            @endif
        </x-filament::section>
    </div>

    @php
        $stepLabels = ['view' => 'Xem trang', 'see_price' => 'Thấy bảng giá', 'see_form' => 'Thấy form', 'form_start' => 'Bắt đầu điền form',
            'form_error' => 'Gửi bị báo lỗi', 'lead' => 'Gửi form (trừ Spam)', 'call' => 'Bấm Gọi', 'zalo' => 'Bấm Zalo'];
        $pct = fn ($n, $of) => $of > 0 ? round($n / $of * 100).'%' : '—';
        $secsText = fn ($s) => $s === null ? '—' : ($s >= 60 ? intdiv($s, 60).' phút '.($s % 60).' giây' : $s.' giây');
    @endphp
    <x-filament::section heading="Khách quảng cáo đi tới đâu trên trang" description="Mỗi lượt xem trang được ghi: thấy bảng giá, thấy form, bắt đầu điền, gửi, bấm Gọi/Zalo. Đếm theo lượt xem, không theo người. Tỷ lệ % so với số lượt xem. Ghi từ 09/11/2026. {{ $days }} ngày qua.">
        <table class="rp-table">
            <tr><th>Bước</th><th class="num">Khách quảng cáo</th><th class="num">%</th><th class="num">Khách khác</th><th class="num">%</th></tr>
            @foreach ($stepLabels as $step => $label)
                <tr><td>{{ $label }}</td>
                    <td class="num">{{ $journey['ads']['steps'][$step] }}</td><td class="num">{{ $step === 'view' ? '' : $pct($journey['ads']['steps'][$step], $journey['ads']['steps']['view']) }}</td>
                    <td class="num">{{ $journey['other']['steps'][$step] }}</td><td class="num">{{ $step === 'view' ? '' : $pct($journey['other']['steps'][$step], $journey['other']['steps']['view']) }}</td></tr>
            @endforeach
            <tr><td>Ở lại trang (trung vị)</td><td class="num" colspan="2">{{ $secsText($journey['ads']['secs']) }}</td><td class="num" colspan="2">{{ $secsText($journey['other']['secs']) }}</td></tr>
            <tr><td>Kéo tới (trung vị, % chiều dài trang)</td><td class="num" colspan="2">{{ $journey['ads']['scroll'] === null ? '—' : $journey['ads']['scroll'].'%' }}</td><td class="num" colspan="2">{{ $journey['other']['scroll'] === null ? '—' : $journey['other']['scroll'].'%' }}</td></tr>
        </table>
        @if ($adPages)
            <p class="rp-note" style="margin-top:16px">Trang khách quảng cáo vào nhiều nhất:</p>
            <table class="rp-table">
                <tr><th>Trang</th><th class="num">Xem</th><th class="num">Thấy giá</th><th class="num">Thấy form</th><th class="num">Bắt đầu điền</th><th class="num">Gọi/Zalo</th><th class="num">Ở lại</th><th class="num">Kéo tới</th></tr>
                @foreach ($adPages as $row)
                    <tr><td><a href="{{ url($row['path']) }}" target="_blank" style="text-decoration:underline">{{ $row['path'] }}</a></td>
                        <td class="num">{{ $row['view'] }}</td><td class="num">{{ $row['see_price'] }}</td><td class="num">{{ $row['see_form'] }}</td>
                        <td class="num">{{ $row['form_start'] }}</td><td class="num">{{ $row['contact'] }}</td>
                        <td class="num">{{ $secsText($row['secs']) }}</td><td class="num">{{ $row['scroll'] === null ? '—' : $row['scroll'].'%' }}</td></tr>
                @endforeach
            </table>
        @endif
    </x-filament::section>

    <x-filament::section heading="Bấm Gọi / Zalo theo trang" description="Khách bấm nút gọi hoặc Zalo ở trang nào — kể cả khi họ gọi thẳng mà không để lại form. {{ $days }} ngày qua.">
        @if ($clicks)
            <table class="rp-table">
                <tr><th>Trang</th><th class="num">Gọi</th><th class="num">Zalo</th><th class="num">Tổng</th></tr>
                @foreach ($clicks as $row)
                    <tr><td><a href="{{ url($row['path']) }}" target="_blank" style="text-decoration:underline">{{ $row['path'] }}</a></td>
                        <td class="num">{{ $row['call'] }}</td><td class="num">{{ $row['zalo'] }}</td><td class="num"><strong>{{ $row['total'] }}</strong></td></tr>
                @endforeach
            </table>
        @else
            <p class="rp-note">Chưa có lượt bấm nào được ghi.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Sau khi bấm Gọi / Zalo" description="Khách dừng ở bước nào. Chỉ là dấu hiệu: web không biết cuộc gọi có nối máy hay tin Zalo có gửi không, số thật phải hỏi chị Hà. {{ $days }} ngày qua.">
        <table class="rp-table">
            <tr><th>Kênh</th><th class="num">Không rời trang</th><th class="num">Quay lại trong 30 giây</th><th class="num">Đi lâu rồi quay lại</th><th class="num">Không thấy quay lại</th></tr>
            @foreach (['call' => 'Gọi', 'zalo' => 'Zalo'] as $channel => $label)
                <tr><td>{{ $label }}</td>
                    @foreach (['stay', 'quick', 'long', 'gone'] as $step)
                        <td class="num">{{ $afterTap[$channel][$step] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </x-filament::section>

    <x-filament::section heading="Tốc độ thật của khách (28 ngày)" description="Số đo từ trình duyệt của khách thật, p75 — cách Google chấm Core Web Vitals. Xanh = Tốt, cam = Cần cải thiện, đỏ = Kém.">
        <table class="rp-table">
            <tr><th>Chỉ số</th><th class="num">Tất cả</th><th class="num">Điện thoại</th><th class="num">Máy tính</th><th class="num">Số lượt đo</th></tr>
            @foreach (['LCP' => 'LCP — hiện nội dung chính', 'INP' => 'INP — phản hồi khi bấm', 'CLS' => 'CLS — nhảy bố cục', 'FCP' => 'FCP — hiện chữ đầu tiên', 'TTFB' => 'TTFB — máy chủ phản hồi'] as $metric => $label)
                <tr>
                    <td>{{ $label }}</td>
                    @foreach (['all', 'mobile', 'desktop'] as $d)
                        @php $cell = $vitals[$metric][$d]; @endphp
                        <td class="num">
                            @if ($cell['rating'])<span class="rp-dot" style="background:{{ $ratingColor[$cell['rating']] }}" title="{{ $ratingText[$cell['rating']] }}"></span>@endif
                            {{ $fmtVital($metric, $cell['value']) }}
                        </td>
                    @endforeach
                    <td class="num">{{ $vitals[$metric]['all']['n'] }}</td>
                </tr>
            @endforeach
        </table>
        @if ($slowest)
            <p class="rp-note" style="margin-top:16px">Trang chậm nhất (LCP p75, từ 5 lượt đo):</p>
            <table class="rp-table">
                @foreach ($slowest as $row)
                    <tr><td>{{ $row['path'] }}</td><td class="num"><span class="rp-dot" style="background:{{ $ratingColor[$row['rating']] }}"></span>{{ $fmtVital('LCP', $row['lcp']) }}</td><td class="num">{{ $row['n'] }} lượt</td></tr>
                @endforeach
            </table>
        @else
            <p class="rp-note">Số đo tích luỹ dần theo lượt khách thật (bot và công cụ đo không được tính).</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>

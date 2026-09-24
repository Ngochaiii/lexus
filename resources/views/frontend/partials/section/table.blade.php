{{--
    Bảng phụ (VD bảng tỷ lệ mua lại). Dòng nhập tay hoặc dán từ HTML —
    cùng parser với `specs`, chỉ khác là phẳng, không chia nhóm.

    Layout `stats`: mỗi dòng thành một ô chỉ số (số to, nhãn nhỏ) thay vì
    hàng bảng — dùng cho dải "2022 · 8 khoang · 4,9/5" ở trang Về chúng tôi.
    Cột `value` là số, cột `label` là chú thích bên dưới.
--}}
@if (($section['layout'] ?? null) === 'stats')
    <div class="stat-strip">
        @foreach ($section['rows'] ?? [] as $row)
            <div>
                <b>{{ $row['value'] ?? '' }}</b>
                <span>{{ $row['label'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
@else
<div class="table-scroll">
    <table class="data-table">
        <tbody>
            @foreach ($section['rows'] ?? [] as $row)
                <tr>
                    <th scope="row">{{ $row['label'] ?? '' }}</th>
                    <td>{{ $row['value'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Mục bảng: .data-table đã có sẵn trong style.css của Lexus. --}}
<section class="container section" id="muc-{{ $index }}">
    <div class="section-heading">
        <div>
            @if (filled($title))<div class="eyebrow">{{ ($numbered ?? true) ? $number.' / ' : '' }}{{ mb_strtoupper($title) }}</div>@endif
            @if ($intro)<h2>{{ $intro }}</h2>@endif
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            @if (filled($section['body'] ?? null))<caption class="studio-sr">{{ strip_tags($section['body']) }}</caption>@endif
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
</section>

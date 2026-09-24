<?php

use App\Models\MenuItem;
use App\Support\Catalog;
use App\Support\Media;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

if (! function_exists('catalog_model')) {
    /**
     * Core không hardcode Product::class — model tra qua config để dự án
     * nào cần thêm hành vi thì extend rồi trỏ lại.
     *
     * @return class-string<Model>
     */
    function catalog_model(string $key): string
    {
        return Catalog::model($key);
    }
}

if (! function_exists('catalog_label')) {
    /** catalog_label('product.plural') → "Dòng xe" */
    function catalog_label(string $key, ?string $default = null): string
    {
        return Catalog::label($key, $default);
    }
}

if (! function_exists('catalog_feature')) {
    /** catalog_feature('dealers') → true|false */
    function catalog_feature(string $key): bool
    {
        return Catalog::feature($key);
    }
}

if (! function_exists('catalog_setting')) {
    /** catalog_setting('hotline') — giá trị màn hình Cài đặt, có cache. */
    function catalog_setting(string $key, mixed $default = null): mixed
    {
        return Catalog::model('setting')::get($key, $default);
    }
}

if (! function_exists('catalog_menu')) {
    /**
     * catalog_menu('header') → các mục gốc kèm con cháu, đã sắp thứ tự.
     * Menu không có thì trả collection rỗng — layout không cần @if.
     *
     * @return Collection<int, MenuItem>
     */
    function catalog_menu(string $key): Collection
    {
        return Catalog::menu($key);
    }
}

if (! function_exists('catalog_image')) {
    /** catalog_image('catalog/sections/a.webp') → URL đầy đủ. Link ngoài trả nguyên. */
    function catalog_image(mixed $path): ?string
    {
        return Media::url($path);
    }
}

if (! function_exists('catalog_money')) {
    /** catalog_money(5990000000) → "5.990.000.000 đ" */
    function catalog_money(mixed $amount): ?string
    {
        return Money::format($amount);
    }
}

if (! function_exists('catalog_rows')) {
    /**
     * Đọc một khoá Cài đặt dạng "bảng nhỏ" thành các dòng đã tách cột.
     *
     * Vài khối của thiết kế (chỉ số chăm sóc chủ xe, danh sách trạm sạc,
     * thẻ dịch vụ) là bảng 2–4 cột nhưng quá nhỏ để đẻ ra một bảng DB.
     * Người nhập gõ mỗi dòng một mục, cột ngăn bằng `|`:
     *
     *     10 năm|Bảo hành xe và pin
     *     24/7|Cứu hộ lưu động toàn tỉnh
     *
     * Xuống dòng hoặc `;` đều tính là hết một dòng — ô textarea và ô một
     * dòng trong admin dùng chung được một khoá.
     *
     * @return Collection<int, array<int, string>>
     */
    function catalog_rows(?string $value, int $columns = 2): Collection
    {
        return collect(preg_split('/[\r\n;]+/', (string) $value))
            ->map(fn (string $row) => array_pad(
                array_map('trim', explode('|', trim($row), $columns)),
                $columns,
                '',
            ))
            ->filter(fn (array $row) => filled($row[0]))
            ->values();
    }
}

if (! function_exists('catalog_money_short')) {
    /**
     * Giá rút gọn kiểu bản thiết kế: 799.000.000 → "799 triệu",
     * 1.090.000.000 → "1,09 tỷ".
     *
     * Dùng ở hero trang chi tiết và thẻ chọn xe — chỗ cần đọc lướt. Bảng giá
     * và form vẫn dùng catalog_money() đầy đủ, không rút gọn tiền người ta
     * sắp trả.
     */
    function catalog_money_short(mixed $amount): ?string
    {
        if (blank($amount) || ! is_numeric($amount)) {
            return null;
        }

        $amount = (float) $amount;

        [$value, $unit] = $amount >= 1_000_000_000
            ? [$amount / 1_000_000_000, 'tỷ']
            : [$amount / 1_000_000, 'triệu'];

        // Bỏ số 0 thừa sau dấu phẩy: 1,00 → 1 · 1,09 → 1,09 · 799,0 → 799
        $text = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');

        return $text.' '.$unit;
    }
}

if (! function_exists('catalog_field_label')) {
    /**
     * Nhãn ô nhập, cho phép ĐÚNG MỘT thứ đánh dấu: `[chữ](đường-dẫn)` thành link.
     *
     * Cần cho ô đồng ý xử lý dữ liệu cá nhân — theo Nghị định 13/2023 câu đồng
     * ý phải dẫn được tới chính sách bảo vệ dữ liệu, mà nhãn thì người nhập gõ
     * trong admin nên không thể gõ thẻ HTML.
     *
     * Mọi thứ khác escape hết. Đường dẫn chỉ nhận http(s) hoặc đường dẫn nội bộ
     * bắt đầu bằng "/" — chặn javascript: và data: ngay từ đây thay vì tin vào
     * người nhập.
     */
    function catalog_field_label(?string $label): HtmlString
    {
        $safe = e((string) $label);

        $html = preg_replace_callback(
            '/\[([^\]]{1,120})\]\(([^)\s]{1,300})\)/',
            function (array $m): string {
                $text = $m[1];
                $url = html_entity_decode($m[2], ENT_QUOTES);

                if (! preg_match('#^(https?://|/)#i', $url)) {
                    return $m[0];   // đường dẫn lạ thì để nguyên chữ, không dựng link
                }

                $external = str_starts_with(strtolower($url), 'http');

                return '<a href="'.e($url).'"'
                    .($external ? ' rel="noopener" target="_blank"' : '')
                    .'>'.$text.'</a>';
            },
            $safe,
        );

        return new HtmlString($html);
    }
}

if (! function_exists('catalog_rich_text')) {
    /**
     * Nội dung dài của bài viết / mục văn bản.
     *
     * Ô soạn thảo lưu HTML, nhưng nội dung nhập từ trước khi có ô đó là văn bản
     * thuần — in thẳng là mất hết dấu xuống dòng. Nên chia hai đường: có thẻ thì
     * lọc qua danh sách cho phép, không có thẻ thì escape rồi nl2br như cũ.
     */
    function catalog_rich_text(?string $body): HtmlString
    {
        $body = (string) $body;

        if (! preg_match('/<[a-z][^>]*>/i', $body)) {
            return new HtmlString(nl2br(e($body)));
        }

        return new HtmlString(\App\Support\RichText::clean($body));
    }
}

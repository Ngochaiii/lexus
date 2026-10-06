<?php

namespace App\Support;

/** Chuẩn hoá chữ cho thẻ meta. */
class SeoText
{
    /**
     * Đuôi tiêu đề trang: " | Thu Hà tư vấn Lexus" khi site có chuyên viên
     * đứng tên — "| Lexus Thăng Long" đọc như website của đại lý (Google Ads
     * tạm ngưng vì ngụ ý là đại lý). Không có chuyên viên thì dùng tên site.
     */
    public static function titleSuffix(): string
    {
        $advisor = catalog_setting('advisor_name');

        return ' | '.(filled($advisor) ? $advisor.' tư vấn Lexus' : catalog_setting('site_name', config('app.name')));
    }

    /**
     * Mô tả meta ≤ $max ký tự: ưu tiên cắt ở cuối câu (". ", "! ", "? ")
     * nếu còn giữ được ít nhất 60% độ dài; không thì cắt ở ranh giới từ và
     * thêm "…". Chuỗi ngắn hơn giới hạn giữ nguyên.
     */
    public static function description(?string $text, int $max = 160): ?string
    {
        if (blank($text)) {
            return $text;
        }

        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $head = mb_substr($text, 0, $max);
        if (preg_match('/^(.*[.!?])\s/us', $head, $m) && mb_strlen($m[1]) >= $max * .6) {
            return $m[1];
        }

        $cut = mb_substr($head, 0, $max - 1);
        $cut = preg_replace('/\s+\S*$/u', '', $cut);

        return rtrim($cut, " ,;:—–-").'…';
    }
}

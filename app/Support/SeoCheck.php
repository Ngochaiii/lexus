<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Chấm nhanh bài Gemini vừa viết theo các tiêu chí SEO on-page, hiện ngay
 * trong thông báo ở admin để biên tập viên biết cần sửa gì trước khi đăng.
 */
class SeoCheck
{
    /**
     * @param  array{article_html:string,seo_title:string,meta_description:string,primary_keyword?:string,faq?:array}  $article
     * @return array<int, array{ok:bool,label:string}>
     */
    public static function article(array $article): array
    {
        $html = $article['article_html'];
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text));
        $kw = Str::lower(trim($article['primary_keyword'] ?? ''));

        preg_match('/<p\b[^>]*>(.*?)<\/p>/isu', $html, $first);
        $firstPara = Str::lower(strip_tags($first[1] ?? ''));
        $h2 = preg_match_all('/<h2\b/i', $html);
        $tables = preg_match_all('/<table\b/i', $html);
        preg_match_all('/<a\b[^>]*href="([^"]+)"/i', $html, $links);
        $internal = collect($links[1])->filter(fn ($href) => str_starts_with($href, '/') || str_contains($href, (string) parse_url(config('app.url'), PHP_URL_HOST)))->count();
        $kwCount = $kw === '' ? 0 : substr_count(Str::lower($text), $kw);
        $titleLen = mb_strlen($article['seo_title']);
        $descLen = mb_strlen($article['meta_description']);
        $faq = count($article['faq'] ?? []);
        $firstHasNumber = (bool) preg_match('/\d/u', $firstPara);
        // Hứa hẹn không có trong dữ liệu: hay bị AI tự thêm, dễ gây hiểu lầm cho khách.
        $all = Str::lower($text.' '.$article['meta_description'].' '.collect($article['faq'] ?? [])->map(fn ($q) => is_array($q) ? implode(' ', $q) : '')->implode(' '));
        $promises = collect(['ưu đãi', 'khuyến mại', 'khuyến mãi', 'giảm giá', 'quà tặng', 'lãi suất'])->filter(fn ($w) => str_contains($all, $w))->values();

        return [
            ['ok' => $words >= 1000, 'label' => "Độ dài {$words} từ (nên ≥ 1.000)"],
            ['ok' => $kw !== '' && str_contains($firstPara, $kw), 'label' => 'Từ khoá chính "'.$kw.'" có trong đoạn mở đầu'],
            ['ok' => $firstHasNumber, 'label' => 'Đoạn mở đầu trả lời ngay bằng con số'],
            ['ok' => $promises->isEmpty(), 'label' => $promises->isEmpty() ? 'Không hứa ưu đãi/lãi suất ngoài dữ liệu' : 'Có nhắc "'.$promises->implode('", "').'" — kiểm tra lại có chương trình thật không'],
            ['ok' => $kwCount >= 2 && $kwCount <= 8, 'label' => "Từ khoá chính xuất hiện {$kwCount} lần (nên 2–8)"],
            ['ok' => $h2 >= 3, 'label' => "{$h2} đề mục H2 (nên ≥ 3)"],
            ['ok' => $tables >= 1, 'label' => "{$tables} bảng số liệu"],
            ['ok' => $internal >= 3, 'label' => "{$internal} link nội bộ (nên ≥ 3)"],
            ['ok' => $titleLen >= 30 && $titleLen <= 65, 'label' => "SEO title {$titleLen} ký tự (30–65)"],
            ['ok' => $descLen >= 120 && $descLen <= 165, 'label' => "Meta description {$descLen} ký tự (120–165)"],
            ['ok' => $faq >= 3, 'label' => "{$faq} câu hỏi FAQ (sinh schema FAQPage)"],
        ];
    }

    /** @param array<int, array{ok:bool,label:string}> $checks */
    public static function summary(array $checks): string
    {
        return collect($checks)->map(fn ($c) => ($c['ok'] ? '✅ ' : '⚠️ ').$c['label'])->implode("\n");
    }
}

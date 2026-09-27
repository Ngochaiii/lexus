<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mục lục bài viết: tên từng mục (bài chia mục trong admin) và từng <h2> trong
 * thân bài (bài Gemini — gắn neo id="muc-…"); trả danh sách để hiện ở cột phải. Neo giúp khách nhảy tới đúng đoạn cần đọc
 * và giúp Google/AI trích dẫn đúng đoạn (link "Chuyển đến" trên kết quả tìm).
 */
class PostToc
{
    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{id: string, title: string}>}
     */
    public static function apply(array $sections): array
    {
        $toc = [];
        $used = [];

        foreach ($sections as $i => $section) {
            // Mục có tên: partials/sections gắn neo theo slug tên mục (neo trùng thì
            // chỉ mục đầu nhận) — tính y hệt để link mục lục trỏ đúng chỗ.
            $anchor = Str::slug((string) ($section['title'] ?? ''));
            if ($anchor !== '' && ! isset($used[$anchor])) {
                $used[$anchor] = true;
                $toc[] = ['id' => $anchor, 'title' => trim((string) (($section['intro'] ?? null) ?: $section['title']))];
            }

            if (($section['type'] ?? null) !== 'text' || blank($section['body'] ?? null)) {
                continue;
            }

            $sections[$i]['body'] = preg_replace_callback('#<h2(\s[^>]*)?>(.*?)</h2>#is', function (array $m) use (&$toc, &$used) {
                $title = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($title === '' || preg_match('/\bid\s*=/i', $m[1] ?? '')) {
                    return $m[0];
                }

                $base = 'muc-'.(Str::slug($title) ?: count($toc) + 1);
                $id = $base;
                for ($n = 2; isset($used[$id]); $n++) {
                    $id = $base.'-'.$n;
                }
                $used[$id] = true;
                $toc[] = ['id' => $id, 'title' => $title];

                return '<h2 id="'.$id.'"'.($m[1] ?? '').'>'.$m[2].'</h2>';
            }, (string) $section['body']) ?? $section['body'];
        }

        return [$sections, $toc];
    }
}

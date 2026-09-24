<?php

namespace App\Support;

/**
 * Chuyển qua lại giữa ô soạn thảo đơn giản của bài viết và cấu trúc sections
 * dùng chung ở frontend/API. Các mục ảnh, video, bảng cũ được giữ nguyên khi
 * biên tập lại bài; chỉ các mục văn bản được gộp vào một ô duy nhất.
 */
class PostContent
{
    /** @param array<int, array<string, mixed>> $sections */
    public static function fromSections(array $sections): string
    {
        return collect($sections)
            ->filter(fn (array $section): bool => ($section['type'] ?? 'media') === 'text')
            ->map(function (array $section): string {
                return implode("\n", array_filter([
                    filled($section['title'] ?? null)
                        ? '<h2>'.e($section['title']).'</h2>'
                        : null,
                    filled($section['intro'] ?? null)
                        ? '<p>'.e($section['intro']).'</p>'
                        : null,
                    filled($section['body'] ?? null)
                        ? (string) $section['body']
                        : null,
                ]));
            })
            ->filter()
            ->implode("\n\n");
    }

    /**
     * @param  array<int, array<string, mixed>>  $existingSections
     * @return array<int, array<string, mixed>>
     */
    public static function intoSections(?string $body, array $existingSections = []): array
    {
        $sections = collect($existingSections)
            ->reject(fn (array $section): bool => ($section['type'] ?? 'media') === 'text')
            ->values()
            ->all();

        if (static::isBlank($body)) {
            return $sections;
        }

        array_unshift($sections, [
            'type' => 'text',
            'body' => trim((string) $body),
        ]);

        return $sections;
    }

    /**
     * Mục hỏi đáp của bài ↔ ô chữ trong admin, dạng:
     *
     *     Hỏi: Giá lăn bánh Lexus RX 350h bao nhiêu?
     *     Đáp: Khoảng 3,77 tỷ đồng tại Hà Nội…
     *
     * Mục `faq` sinh FAQPage JSON-LD (JsonLd::forFaq) — Google và AI trích
     * nguyên câu trả lời. Biên tập viên sửa chữ thường, không phải JSON.
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    public static function faqToText(array $sections): string
    {
        return collect($sections)
            ->filter(fn (array $section): bool => ($section['type'] ?? null) === 'faq')
            ->flatMap(fn (array $section) => $section['rows'] ?? [])
            ->filter(fn ($row) => filled($row['label'] ?? null))
            ->map(fn (array $row): string => 'Hỏi: '.trim((string) $row['label'])."\nĐáp: ".trim(strip_tags((string) ($row['value'] ?? ''))))
            ->implode("\n\n");
    }

    /** @return array<int, array{label:string,value:string}> */
    public static function parseFaq(?string $text): array
    {
        $rows = [];
        $q = null;
        $a = [];
        $flush = function () use (&$rows, &$q, &$a): void {
            if (filled($q) && filled(trim(implode(' ', $a)))) {
                $rows[] = ['label' => trim($q), 'value' => trim(implode(' ', $a))];
            }
            $q = null;
            $a = [];
        };

        foreach (preg_split('/\R/u', (string) $text) as $line) {
            $line = trim($line);
            if (preg_match('/^(hỏi|h|q)\s*[:：]\s*(.+)$/iu', $line, $m)) {
                $flush();
                $q = $m[2];
            } elseif (preg_match('/^(đáp|trả lời|đ|a)\s*[:：]\s*(.*)$/iu', $line, $m)) {
                $a[] = $m[2];
            } elseif ($line !== '' && $q !== null) {
                $a[] = $line;
            }
        }
        $flush();

        return $rows;
    }

    /**
     * Thay mục hỏi đáp của bài bằng nội dung ô chữ. Ô trống → bỏ mục hỏi đáp.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    public static function withFaq(array $sections, ?string $faqText): array
    {
        $sections = collect($sections)
            ->reject(fn (array $section): bool => ($section['type'] ?? null) === 'faq')
            ->values()->all();

        if ($rows = static::parseFaq($faqText)) {
            $sections[] = ['type' => 'faq', 'title' => 'Hỏi đáp', 'intro' => 'Câu hỏi thường gặp', 'rows' => $rows];
        }

        return $sections;
    }

    protected static function isBlank(?string $body): bool
    {
        $text = html_entity_decode(strip_tags((string) $body), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $text)) === '';
    }
}

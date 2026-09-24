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

    protected static function isBlank(?string $body): bool
    {
        $text = html_entity_decode(strip_tags((string) $body), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $text)) === '';
    }
}

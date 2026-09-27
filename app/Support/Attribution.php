<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Khách tới từ đâu — phân loại "lần chạm" mà trình duyệt ghi lại
 * (public/assets/insight.js: nguồn giới thiệu, UTM, trang vào đầu tiên).
 *
 *   ?utm_source=facebook&utm_medium=cpc  → facebook / cpc
 *   ?gclid=…                             → google / cpc
 *   referrer google.com.vn               → google / organic
 *   referrer l.facebook.com              → facebook / social
 *   referrer chatgpt.com                 → ai / ai
 *   không có referrer                    → direct / none
 *
 * Chạy phía máy chủ để luật phân loại nằm một chỗ, sửa là áp cho cả lead lẫn
 * lượt bấm Gọi/Zalo.
 */
class Attribution
{
    /** host chứa chuỗi → [source, medium] */
    private const HOSTS = [
        'google.'        => ['google', 'organic'],
        'coccoc.'        => ['coccoc', 'organic'],
        'bing.'          => ['bing', 'organic'],
        'duckduckgo.'    => ['duckduckgo', 'organic'],
        'yahoo.'         => ['yahoo', 'organic'],
        'facebook.'      => ['facebook', 'social'],
        'fb.me'          => ['facebook', 'social'],
        'messenger.'     => ['facebook', 'social'],
        'instagram.'     => ['instagram', 'social'],
        'zalo.'          => ['zalo', 'social'],
        'zaloapp.'       => ['zalo', 'social'],
        'tiktok.'        => ['tiktok', 'social'],
        'youtube.'       => ['youtube', 'social'],
        'youtu.be'       => ['youtube', 'social'],
        'chatgpt.'       => ['ai', 'ai'],
        'openai.'        => ['ai', 'ai'],
        'perplexity.'    => ['ai', 'ai'],
        'gemini.google'  => ['ai', 'ai'],
        'copilot.'       => ['ai', 'ai'],
        'claude.ai'      => ['ai', 'ai'],
    ];

    /**
     * @param  array<string, mixed>|null  $raw  {ref, land, utm:{…}, gclid, fbclid, ts}
     * @return array{source:string,medium:string,campaign:?string,landing_page:?string}
     */
    public static function classify(?array $raw): array
    {
        $raw = $raw ?? [];
        $utm = is_array($raw['utm'] ?? null) ? $raw['utm'] : [];
        $clean = fn ($v, $n = 120) => is_string($v) && trim($v) !== '' ? Str::limit(trim(strip_tags($v)), $n, '') : null;

        $landing = $clean($raw['land'] ?? null, 255);
        if ($landing !== null && ! str_starts_with($landing, '/')) {
            $landing = parse_url($landing, PHP_URL_PATH) ?: null;
        }
        $campaign = $clean($utm['utm_campaign'] ?? null);

        if ($source = $clean($utm['utm_source'] ?? null, 40)) {
            $source = Str::lower($source);
            $source = collect(self::HOSTS)->first(fn ($pair, $needle) => str_contains($source.'.', rtrim($needle, '.').'.'))[0] ?? Str::slug($source);

            return ['source' => $source, 'medium' => Str::lower($clean($utm['utm_medium'] ?? null, 40) ?? 'utm'), 'campaign' => $campaign, 'landing_page' => $landing];
        }

        if (filled($raw['gclid'] ?? null)) {
            return ['source' => 'google', 'medium' => 'cpc', 'campaign' => $campaign, 'landing_page' => $landing];
        }
        if (filled($raw['fbclid'] ?? null)) {
            return ['source' => 'facebook', 'medium' => 'social', 'campaign' => $campaign, 'landing_page' => $landing];
        }

        $host = Str::lower((string) parse_url((string) ($raw['ref'] ?? ''), PHP_URL_HOST));
        $ownHost = Str::lower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($host === '' || $host === $ownHost || str_ends_with($host, '.'.$ownHost)) {
            return ['source' => 'direct', 'medium' => 'none', 'campaign' => null, 'landing_page' => $landing];
        }

        foreach (self::HOSTS as $needle => [$source, $medium]) {
            if (str_contains($host, $needle)) {
                return ['source' => $source, 'medium' => $medium, 'campaign' => $campaign, 'landing_page' => $landing];
            }
        }

        return ['source' => 'referral', 'medium' => Str::limit(preg_replace('/^www\./', '', $host), 40, ''), 'campaign' => $campaign, 'landing_page' => $landing];
    }

    /** Chuỗi JSON từ trình duyệt → mảng (giới hạn cỡ, bỏ rác). */
    public static function decode(mixed $json): ?array
    {
        if (is_array($json)) {
            return $json;
        }
        if (! is_string($json) || $json === '' || strlen($json) > 3000) {
            return null;
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    public static function device(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }

    /** Bot, Lighthouse, trình duyệt headless — không tính vào số liệu. */
    public static function isBot(?string $userAgent): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|Lighthouse|HeadlessChrome|PageSpeed|facebookexternalhit|curl|python|wget/i', (string) $userAgent);
    }
}

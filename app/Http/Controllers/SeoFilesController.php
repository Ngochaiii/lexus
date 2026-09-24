<?php

namespace App\Http\Controllers;

use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * robots.txt và llms.txt sinh động theo APP_URL + dữ liệu thật.
 *
 * robots.txt từng là file tĩnh copy từ bản VinFast, khai sitemap của
 * domain khác — Google sẽ đi thu thập nhầm site. Sinh động thì đổi domain
 * chỉ cần sửa APP_URL.
 *
 * llms.txt (đề xuất llmstxt.org) là bản tóm tắt Markdown cho các công cụ AI:
 * đại lý là ai, ở đâu, bán xe gì, giá bao nhiêu, trang nào trả lời câu hỏi
 * nào. Mô hình đọc được ngay mà không phải bóc HTML — phục vụ GEO.
 */
class SeoFilesController
{
    /**
     * Bot AI được mời đọc để trích dẫn site trong câu trả lời. Chặn chúng
     * thì site không bao giờ xuất hiện trong ChatGPT Search, Perplexity,
     * Gemini, Claude… — ngược với mục tiêu GEO.
     */
    private const AI_BOTS = [
        'GPTBot', 'OAI-SearchBot', 'ChatGPT-User',
        'PerplexityBot', 'Perplexity-User',
        'ClaudeBot', 'Claude-SearchBot', 'Claude-User',
        'Google-Extended', 'Applebot-Extended', 'CCBot',
    ];

    private const PRIVATE_PATHS = ['/admin/', '/api/', '/gui-form/', '/livewire/'];

    public function robots(): Response
    {
        $rules = collect(self::PRIVATE_PATHS)->map(fn ($p) => 'Disallow: '.$p)->implode("\n");

        $lines = [
            'User-agent: *',
            'Allow: /',
            $rules,
            '',
            '# Công cụ AI: được phép đọc nội dung công khai để trích dẫn.',
            ...array_map(fn ($bot) => 'User-agent: '.$bot, self::AI_BOTS),
            'Allow: /',
            $rules,
            '',
        ];

        if (Route::has('sitemap')) {
            $lines[] = 'Sitemap: '.Url::route('sitemap');
        }

        return $this->text(implode("\n", $lines)."\n");
    }

    public function llms(): Response
    {
        $name     = catalog_setting('site_name', config('app.name'));
        $address  = catalog_setting('address');
        $hours    = catalog_setting('opening_hours');
        $hotline  = \App\Support\Phone::format(catalog_setting('hotline'));
        $advisor  = catalog_setting('advisor_name');

        $out   = [];
        $out[] = '# '.$name;
        $out[] = '';
        $out[] = '> '.trim((string) catalog_setting('site_description'));
        $out[] = '';

        $facts = array_filter([
            $address ? 'Địa chỉ: '.$address : null,
            $hours ? 'Giờ mở cửa: '.$hours : null,
            $hotline ? 'Hotline: '.$hotline.($advisor ? ' ('.$advisor.', chuyên viên tư vấn)' : '') : null,
            'Website: '.rtrim((string) config('app.url'), '/'),
        ]);
        foreach ($facts as $fact) {
            $out[] = '- '.$fact;
        }

        // ── Xe + giá: phần AI hay được hỏi nhất ──
        $products = Catalog::query('product')->published()
            ->with(['variants' => fn ($q) => $q->orderBy('sort'), 'category'])
            ->orderBy('sort')->get();

        if ($products->isNotEmpty()) {
            $out[] = '';
            $out[] = '## Dòng xe và giá niêm yết (VNĐ, đã gồm VAT)';
            $out[] = '';
            foreach ($products as $p) {
                $line = '- ['.$p->name.']('.Url::absolute('product', $p->slug).')';
                $meta = array_filter([$p->category?->name, $p->tagline]);
                if ($meta) {
                    $line .= ': '.implode(' — ', $meta).'.';
                }
                $prices = $p->variants->filter(fn ($v) => filled($v->price))
                    // Kèm ghi chú phiên bản ("giá dự kiến…") để AI không trích nhầm
                    // giá dự kiến thành giá chính thức.
                    ->map(fn ($v) => $v->name.' '.catalog_money($v->price)
                        .(str_contains((string) $v->note, 'dự kiến') ? ' ('.$v->note.')' : ''))->implode('; ');
                if ($prices !== '') {
                    $line .= ' Phiên bản: '.$prices.'.';
                } elseif ($from = catalog_money($p->price_from)) {
                    $line .= ' Giá từ '.$from.'.';
                } elseif ($p->variants->isNotEmpty()) {
                    $line .= ' Phiên bản: '.$p->variants->pluck('name')->implode(', ').' — giá đang cập nhật.';
                }
                $out[] = $line;
            }
        }

        // ── Trang trả lời câu hỏi thường gặp ──
        $pages = Catalog::query('page')->where('status', 'published')
            ->whereNotIn('slug', (array) config('catalog.seo.sitemap_exclude_pages', []))
            ->get()
            ->filter(fn ($page) => filled($page->sections) || $page->slug === 'bang-gia');

        if ($pages->isNotEmpty()) {
            $out[] = '';
            $out[] = '## Trang thông tin';
            $out[] = '';
            foreach ($pages as $page) {
                $desc = data_get($page->seo, 'description') ?: data_get($page->seo, 'excerpt');
                $out[] = '- ['.$page->title.']('.Url::absolute('page', $page->slug).')'.($desc ? ': '.$desc : '');
            }
        }

        if (Route::has('booking')) {
            $out[] = '- [Đăng ký lái thử]('.Url::route('booking').'): để lại tên, số điện thoại và dòng xe; chuyên viên gọi lại xác nhận lịch.';
        }
        if (Route::has('quote')) {
            $out[] = '- [Nhận báo giá lăn bánh]('.Url::route('quote').'): báo giá chi tiết theo phiên bản, gửi riêng cho khách.';
        }

        // ── Bài viết mới ──
        if (catalog_feature('posts')) {
            $posts = Catalog::query('post')->published()->latest('published_at')->take(20)->get();
            if ($posts->isNotEmpty()) {
                $out[] = '';
                $out[] = '## Bài viết';
                $out[] = '';
                foreach ($posts as $post) {
                    $out[] = '- ['.$post->title.']('.Url::absolute('post', $post->slug).')'
                        .(filled($post->excerpt) ? ': '.$post->excerpt : '');
                }
            }
        }

        return $this->text(implode("\n", $out)."\n", 'text/markdown');
    }

    private function text(string $body, string $type = 'text/plain'): Response
    {
        return response($body, 200, [
            'Content-Type' => $type.'; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300, s-maxage=3600',
        ]);
    }
}

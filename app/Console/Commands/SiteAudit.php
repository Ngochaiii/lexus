<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Quét toàn bộ website như Googlebot: mọi trang trong sitemap + link nội bộ
 * + ảnh/CSS/JS, báo lỗi SEO kỹ thuật.
 *
 *     php artisan site:audit                          # quét APP_URL
 *     php artisan site:audit --url=http://127.0.0.1:8010
 *     php artisan site:audit --no-assets              # bỏ kiểm tra ảnh/CSS/JS
 *
 * LỖI (thoát mã 1): trang/link/ảnh không trả 200, JSON-LD hỏng, noindex trên
 * trang có trong sitemap, thiếu tiêu đề/H1/canonical sai.
 * CẢNH BÁO: tiêu đề/mô tả quá ngắn hoặc dài, trùng tiêu đề/mô tả, ảnh thiếu alt.
 */
class SiteAudit extends Command
{
    protected $signature = 'site:audit {--url= : Gốc website, mặc định APP_URL} {--no-assets : Không kiểm tra ảnh/CSS/JS} {--concurrency=6}';

    protected $description = 'Quét toàn bộ website: trang, link, ảnh, thẻ SEO, dữ liệu cấu trúc';

    private const UA = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html) LexusSiteAudit';

    /** @var array<int, array{0:string,1:string,2:string}> [mức, trang, nội dung] */
    private array $issues = [];

    public function handle(): int
    {
        $base = rtrim($this->option('url') ?: (string) config('app.url'), '/');
        $host = parse_url($base, PHP_URL_HOST);
        $conc = max(1, (int) $this->option('concurrency'));

        $this->info("Quét {$base}");

        $sitemap = Http::withUserAgent(self::UA)->timeout(30)->get($base.'/sitemap.xml');
        if (! $sitemap->successful()) {
            $this->error('Không đọc được sitemap.xml (HTTP '.$sitemap->status().')');

            return self::FAILURE;
        }
        // Sitemap ghi theo APP_URL — đổi sang gốc đang quét (vd quét bản local).
        $appBase = rtrim((string) config('app.url'), '/');
        $pages = collect(preg_match_all('#<url>\s*<loc>([^<]+)</loc>#', $sitemap->body(), $m) ? $m[1] : [])
            ->map(fn ($u) => str_replace($appBase, $base, html_entity_decode($u)))->unique()->values();
        $this->line('Sitemap: '.$pages->count().' trang');

        $titles = $descs = [];
        $links = $assets = [];
        $pageResults = [];

        foreach ($pages->chunk($conc) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => $chunk->map(fn ($u) => $pool->as($u)
                ->withUserAgent(self::UA)->withoutRedirecting()->timeout(30)->get($u))->all());

            foreach ($responses as $url => $res) {
                $path = Str::after($url, $base) ?: '/';
                if (! $res instanceof \Illuminate\Http\Client\Response) {
                    $this->issue('LỖI', $path, 'Không kết nối được');

                    continue;
                }
                $pageResults[$path] = $res->status();
                if ($res->status() !== 200) {
                    $this->issue('LỖI', $path, 'Trang trong sitemap trả HTTP '.$res->status());

                    continue;
                }
                $this->checkPage($url, $path, $res->body(), $base, $host, $titles, $descs, $links, $assets);
            }
        }

        foreach ($titles as $t => $paths) {
            if (count($paths) > 1) {
                $this->issue('CẢNH BÁO', implode(', ', $paths), 'Trùng tiêu đề: '.Str::limit($t, 60));
            }
        }
        foreach ($descs as $d => $paths) {
            if (count($paths) > 1) {
                $this->issue('CẢNH BÁO', implode(', ', $paths), 'Trùng mô tả: '.Str::limit($d, 60));
            }
        }

        // Link nội bộ không có trong sitemap: phải trả 200 hoặc chuyển hướng hợp lệ.
        $extra = collect(array_keys($links))->reject(fn ($u) => $pages->contains($u))->values();
        $this->line('Link nội bộ ngoài sitemap: '.$extra->count());
        $this->checkUrls($extra, $conc * 2, 'Link hỏng', $links, true);

        if (! $this->option('no-assets')) {
            $list = collect(array_keys($assets));
            $this->line('Ảnh/CSS/JS: '.$list->count());
            $this->checkUrls($list, $conc * 2, 'Tài nguyên lỗi', $assets, false);
        }

        return $this->report($pages->count());
    }

    private function checkPage(string $url, string $path, string $html, string $base, ?string $host, array &$titles, array &$descs, array &$links, array &$assets): void
    {
        $title = trim(html_entity_decode(strip_tags((string) (preg_match('#<title>(.*?)</title>#s', $html, $m) ? $m[1] : ''))));
        $desc = html_entity_decode((string) (preg_match('#<meta name="description" content="([^"]*)"#', $html, $m) ? $m[1] : ''));
        $canonical = (string) (preg_match('#<link rel="canonical" href="([^"]+)"#', $html, $m) ? $m[1] : '');
        $robots = (string) (preg_match('#<meta name="robots" content="([^"]+)"#', $html, $m) ? $m[1] : '');
        $h1 = preg_match_all('#<h1[\s>]#', $html);

        $len = mb_strlen($title);
        if ($len === 0) {
            $this->issue('LỖI', $path, 'Thiếu thẻ <title>');
        } elseif ($len < 20 || $len > 70) {
            $this->issue('CẢNH BÁO', $path, "Tiêu đề {$len} ký tự (nên 20–70)");
        }
        $dl = mb_strlen($desc);
        if ($dl === 0) {
            $this->issue('CẢNH BÁO', $path, 'Thiếu meta description');
        } elseif ($dl < 50 || $dl > 165) {
            $this->issue('CẢNH BÁO', $path, "Mô tả {$dl} ký tự (nên 50–165)");
        }
        if ($h1 !== 1) {
            $this->issue($h1 === 0 ? 'LỖI' : 'CẢNH BÁO', $path, "Có {$h1} thẻ H1 (nên đúng 1)");
        }
        $appBase = rtrim((string) config('app.url'), '/');
        if ($canonical === '' || rtrim(str_replace($appBase, $base, $canonical), '/') !== rtrim($url, '/')) {
            $this->issue('LỖI', $path, 'Canonical sai: '.($canonical ?: '(thiếu)'));
        }
        if (str_contains($robots, 'noindex')) {
            $this->issue('LỖI', $path, 'Trang trong sitemap nhưng có noindex');
        }
        if ($noAlt = preg_match_all('#<img(?![^>]*\balt=)[^>]*>#', $html)) {
            $this->issue('CẢNH BÁO', $path, "{$noAlt} ảnh thiếu alt");
        }
        foreach (preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m) ? $m[1] : [] as $json) {
            if (json_decode($json) === null) {
                $this->issue('LỖI', $path, 'JSON-LD không đọc được');
            }
        }
        // Chỉ có nghĩa khi quét domain thật (quét bản local thì link local là đúng).
        if (! in_array($host, ['127.0.0.1', 'localhost'], true)
            && (str_contains($html, '127.0.0.1') || str_contains($html, 'localhost'))) {
            $this->issue('CẢNH BÁO', $path, 'Còn link 127.0.0.1/localhost');
        }

        $titles[$title][] = $path;
        if ($desc !== '') {
            $descs[$desc][] = $path;
        }

        preg_match_all('~(?:href|src)="([^"#]+)"~', $html, $m);
        preg_match_all('#srcset="([^"]+)"#', $html, $s);
        $srcset = collect($s[1])->flatMap(fn ($v) => array_map(fn ($p) => trim(explode(' ', trim($p))[0]), explode(',', $v)));

        foreach ([...$m[1], ...$srcset] as $raw) {
            $u = html_entity_decode(trim($raw));
            if (str_starts_with($u, '//')) {
                continue;
            }
            if (str_starts_with($u, '/')) {
                $u = $base.$u;
            }
            if (parse_url($u, PHP_URL_HOST) !== $host && ! str_starts_with($u, $base)) {
                continue;
            }
            $u = str_replace($appBase, $base, $u);
            if (preg_match('#\.(webp|jpe?g|png|gif|svg|ico|css|js|woff2?)(\?|$)#i', $u)) {
                $assets[$u][] = $path;
            } elseif (! preg_match('#/(admin|livewire|api|gui-form)(/|$)|^mailto:|^tel:#', $u)) {
                $links[strtok($u, '#')][] = $path;
            }
        }
    }

    private function checkUrls($urls, int $conc, string $label, array $foundOn, bool $allowRedirect): void
    {
        foreach ($urls->chunk($conc) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => $chunk->map(fn ($u) => $pool->as($u)
                ->withUserAgent(self::UA)->withoutRedirecting()->timeout(30)->head($u))->all());
            foreach ($responses as $u => $res) {
                $status = $res instanceof \Illuminate\Http\Client\Response ? $res->status() : 0;
                $ok = $status === 200 || ($allowRedirect && in_array($status, [301, 308], true));
                if (! $ok) {
                    $on = collect($foundOn[$u] ?? [])->unique()->take(3)->implode(', ');
                    $this->issue('LỖI', $on, "{$label} (HTTP {$status}): {$u}");
                }
            }
        }
    }

    private function issue(string $level, string $where, string $message): void
    {
        $this->issues[] = [$level, $where, $message];
    }

    private function report(int $pageCount): int
    {
        $errors = collect($this->issues)->where(0, 'LỖI');
        $warnings = collect($this->issues)->where(0, 'CẢNH BÁO');

        if ($this->issues) {
            $this->table(['Mức', 'Trang', 'Vấn đề'], collect($this->issues)
                ->sortBy(fn ($i) => $i[0] === 'LỖI' ? 0 : 1)
                ->map(fn ($i) => [$i[0], Str::limit($i[1], 60), Str::limit($i[2], 110)])->all());
        }

        $this->newLine();
        $this->line("Đã quét {$pageCount} trang · <fg=red>{$errors->count()} lỗi</> · <fg=yellow>{$warnings->count()} cảnh báo</>");

        return $errors->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}

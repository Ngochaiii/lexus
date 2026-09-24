<?php

namespace App\Jobs;

use App\Services\GeminiArticleWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Viết bài bằng Gemini trong queue worker, không trong request của admin.
 *
 * Một bài 1.200–1.800 từ có bước nghiên cứu từ khoá mất 1–3 phút, cộng thời
 * gian thử lại khi model quá tải (503). Cloudflare cắt mọi request quá 100 giây
 * (lỗi 524) nên không thể bắt trình duyệt đứng chờ. Kết quả ghi vào cache theo
 * $key; trang soạn bài hỏi lại vài giây một lần (PollsGeminiArticle).
 */
class GenerateArticleWithGemini implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Đủ cho 4 model × thời gian chờ mỗi lượt; queue.database.retry_after phải lớn hơn. */
    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(
        public string $key,
        public string $title,
        public string $cover,
        public ?string $instructions = null,
    ) {}

    public static function cacheKey(string $key): string
    {
        return 'gemini-article:'.$key;
    }

    public function handle(GeminiArticleWriter $writer): void
    {
        try {
            $article = $writer->generate($this->title, $this->cover, $this->instructions);
            Cache::put(self::cacheKey($this->key), ['status' => 'done', 'article' => $article], now()->addHours(6));
        } catch (Throwable $e) {
            Cache::put(self::cacheKey($this->key), ['status' => 'error', 'message' => $e->getMessage()], now()->addHours(6));
        }
    }

    public function failed(Throwable $e): void
    {
        Cache::put(self::cacheKey($this->key), [
            'status' => 'error',
            'message' => 'Tiến trình viết bài bị dừng giữa chừng ('.class_basename($e).'). Vui lòng bấm tạo lại.',
        ], now()->addHours(6));
    }
}

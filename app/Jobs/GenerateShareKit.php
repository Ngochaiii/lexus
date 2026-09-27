<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\GeminiShareKit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Gemini soạn bài chia sẻ Facebook/Zalo/diễn đàn cho một bài viết (chạy nền). */
class GenerateShareKit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $postId) {}

    public static function cacheKey(int $postId): string
    {
        return 'gemini-share-kit:'.$postId;
    }

    public function handle(GeminiShareKit $writer): void
    {
        $post = Post::find($this->postId);

        try {
            if (! $post) {
                throw new \RuntimeException('Bài viết không còn tồn tại.');
            }
            $post->forceFill(['share_kit' => $writer->generate($post)])->saveQuietly();
            Cache::put(self::cacheKey($this->postId), ['status' => 'done'], now()->addHours(6));
        } catch (Throwable $e) {
            Cache::put(self::cacheKey($this->postId), ['status' => 'error', 'message' => $e->getMessage()], now()->addHours(6));
        }
    }

    public function failed(Throwable $e): void
    {
        Cache::put(self::cacheKey($this->postId), ['status' => 'error', 'message' => 'Tiến trình bị dừng giữa chừng ('.class_basename($e).'). Vui lòng thử lại.'], now()->addHours(6));
    }
}

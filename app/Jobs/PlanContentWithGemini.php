<?php

namespace App\Jobs;

use App\Services\GeminiContentPlanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Gemini đề xuất chủ đề bài viết (chạy nền); trạng thái ghi vào cache cho trang Kế hoạch. */
class PlanContentWithGemini implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CACHE_KEY = 'gemini-content-plan';

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $count = 8, public ?string $focus = null) {}

    public function handle(GeminiContentPlanner $planner): void
    {
        try {
            $ideas = $planner->propose($this->count, $this->focus);
            Cache::put(self::CACHE_KEY, ['status' => 'done', 'count' => $ideas->count()], now()->addHours(6));
        } catch (Throwable $e) {
            Cache::put(self::CACHE_KEY, ['status' => 'error', 'message' => $e->getMessage()], now()->addHours(6));
        }
    }

    public function failed(Throwable $e): void
    {
        Cache::put(self::CACHE_KEY, ['status' => 'error', 'message' => 'Tiến trình bị dừng giữa chừng ('.class_basename($e).'). Vui lòng thử lại.'], now()->addHours(6));
    }
}

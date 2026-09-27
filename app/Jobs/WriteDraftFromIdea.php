<?php

namespace App\Jobs;

use App\Models\ContentIdea;
use App\Services\GeminiContentPlanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/** Gemini viết một chủ đề trong kế hoạch thành bài NHÁP (chạy nền, 1–3 phút). */
class WriteDraftFromIdea implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $ideaId) {}

    public function handle(GeminiContentPlanner $planner): void
    {
        $idea = ContentIdea::find($this->ideaId);
        if (! $idea) {
            return;
        }

        try {
            $post = $planner->writeDraft($idea);
            $idea->update(['status' => 'drafted', 'post_id' => $post->id, 'error' => null]);
        } catch (Throwable $e) {
            $idea->update(['status' => 'idea', 'error' => $e->getMessage()]);
        }
    }

    public function failed(Throwable $e): void
    {
        ContentIdea::whereKey($this->ideaId)->update([
            'status' => 'idea',
            'error' => 'Tiến trình viết bài bị dừng giữa chừng ('.class_basename($e).'). Vui lòng bấm viết lại.',
        ]);
    }
}

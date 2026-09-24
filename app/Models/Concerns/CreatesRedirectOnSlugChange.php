<?php

namespace App\Models\Concerns;

use App\Support\Catalog;
use App\Support\Url;

/**
 * Đổi slug của một bản ĐÃ PUBLISH thì đường dẫn cũ đang được Google index và
 * người ta đã lưu — để rơi vào 404 là mất hạng. Trait này tự tạo một redirect
 * 301 từ đường dẫn cũ sang mới, khỏi phải nhớ làm tay.
 *
 * Model dùng trait phải khai urlType() trả về loại trong config('catalog.routes').
 */
trait CreatesRedirectOnSlugChange
{
    public static function bootCreatesRedirectOnSlugChange(): void
    {
        static::updating(function ($model): void {
            if (! $model->isDirty('slug')) {
                return;
            }

            // Chỉ bận tâm bản đã publish — bản nháp chưa ai biết tới đường dẫn cũ.
            if (($model->getOriginal('status') ?? null) !== 'published') {
                return;
            }

            $type = $model->urlType();
            $from = Url::to($type, $model->getOriginal('slug'));
            $to = Url::to($type, $model->slug);

            if ($from === $to) {
                return;
            }

            Catalog::model('redirect')::updateOrCreate(
                ['from_path' => $from],
                ['to_path' => $to, 'status_code' => 301],
            );

            // Nếu trước đó có luật trỏ TỚI đường dẫn cũ, đổi nó trỏ thẳng đích
            // mới — tránh chuỗi 301 nối tiếp 301.
            Catalog::model('redirect')::query()
                ->where('to_path', $from)
                ->where('from_path', '!=', $to)
                ->update(['to_path' => $to]);
        });
    }
}

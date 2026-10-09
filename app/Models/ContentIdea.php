<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một chủ đề bài viết trong kế hoạch (Gemini đề xuất, người duyệt). */
class ContentIdea extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'idea' => 'Chờ viết',
        'writing' => 'Gemini đang viết',
        'drafted' => 'Đã có bài nháp',
        'dismissed' => 'Bỏ qua',
    ];

    public const PRIORITIES = [1 => 'Cao', 2 => 'Vừa', 3 => 'Thấp'];

    public const CLUSTERS = [
        'gia' => 'Giá & lăn bánh',
        'so-sanh' => 'So sánh',
        'tra-gop' => 'Trả góp & chi phí',
        'kinh-nghiem' => 'Kinh nghiệm mua & dùng xe',
        'dia-phuong' => 'Khu vực / đại lý',
    ];

    /** Giai đoạn hành trình khách — khách hỏi Google/AI khác nhau ở mỗi giai đoạn. */
    public const STAGES = [
        'tim-hieu' => 'Tìm hiểu',
        'lua-chon' => 'Lựa chọn',
        'quyet-dinh' => 'Quyết định',
    ];

    protected function casts(): array
    {
        return [
            'secondary_keywords' => 'array',
            'ai_prompts' => 'array',
            'priority' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

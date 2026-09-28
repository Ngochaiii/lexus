<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data'         => 'array',
            'utm'          => 'array',
            'activities'   => 'array',
            'follow_up_at' => 'datetime',
            'closed_at'    => 'datetime',
            'qualified_at' => 'datetime',
            'deal_value'   => 'decimal:2',
            'commission'   => 'decimal:2',
        ];
    }

    /** Đường ống bán hàng — thứ tự là thứ tự các bước. */
    public const STATUSES = [
        'new'        => 'Mới',
        'called'     => 'Đã gọi',
        'appointment'=> 'Hẹn lái thử',
        'test_drive' => 'Đã lái thử',
        'deposit'    => 'Đặt cọc',
        'won'        => 'Đã giao xe',
        'lost'       => 'Không mua',
        'spam'       => 'Spam',
    ];

    /** Còn đang theo — chưa chốt, chưa mất, không phải spam. */
    public const OPEN = ['new', 'called', 'appointment', 'test_drive', 'deposit'];

    /** Khách thật (đã hẹn lái thử trở lên) — chuyển đổi báo lại Google Ads. */
    public const QUALIFIED = ['appointment', 'test_drive', 'deposit', 'won'];

    public const LOST_REASONS = [
        'price'      => 'Giá / ngân sách',
        'competitor' => 'Mua hãng hoặc đại lý khác',
        'timing'     => 'Chưa tới thời điểm mua',
        'finance'    => 'Không duyệt được vay',
        'no_contact' => 'Không liên lạc được',
        'other'      => 'Lý do khác',
    ];

    public const SOURCES = [
        'google'   => 'Google',
        'coccoc'   => 'Cốc Cốc',
        'bing'     => 'Bing',
        'facebook' => 'Facebook',
        'zalo'     => 'Zalo',
        'tiktok'   => 'TikTok',
        'youtube'  => 'YouTube',
        'ai'       => 'AI (ChatGPT, Gemini…)',
        'direct'   => 'Trực tiếp',
        'referral' => 'Web khác',
    ];

    protected static function booted(): void
    {
        // Ghi lúc chốt (giao xe / không mua) để báo cáo theo tháng chốt.
        static::saving(function (Lead $lead): void {
            if ($lead->isDirty('status')) {
                $lead->closed_at = in_array($lead->status, ['won', 'lost'], true) ? ($lead->closed_at ?? now()) : null;

                // Lần ĐẦU lên "Hẹn lái thử" trở lên — thời điểm chuyển đổi gửi
                // Google Ads. Lùi trạng thái hay lên tiếp cũng không ghi đè.
                if (in_array($lead->status, self::QUALIFIED, true)) {
                    $lead->qualified_at ??= now();
                }
            }
        });
    }

    public static function sourceLabel(?string $source): string
    {
        return self::SOURCES[$source] ?? ($source ?: 'Chưa rõ');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(catalog_model('form'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(catalog_model('product'));
    }

    /** Phiên bản khách chọn (khi gửi từ thẻ phiên bản). */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

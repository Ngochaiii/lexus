<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Banner chỉ có ảnh, không chữ: hiện đúng tấm ảnh, bấm vào là đi tới link.
     *
     * Dùng cho ảnh đã thiết kế sẵn chữ bên trong — đè thêm tiêu đề và nút của
     * site lên là hỏng bố cục nhà thiết kế.
     */
    public function isBare(): bool
    {
        return blank($this->title) && blank($this->subtitle) && blank($this->eyebrow);
    }

    /**
     * Banner đang được phép hiện: đã bật, đang trong khoảng thời gian chạy, VÀ
     * có ít nhất một thứ để hiện.
     *
     * Mốc thời gian để trống nghĩa là không giới hạn phía đó. Không ảnh không
     * chữ thì bỏ qua — slide rỗng còn tệ hơn không có banner nào.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNotNull('image')->orWhereNotNull('title'))
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}

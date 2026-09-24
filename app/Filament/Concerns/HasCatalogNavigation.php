<?php

namespace App\Filament\Concerns;

trait HasCatalogNavigation
{
    /**
     * Tắt viết hoa đầu từ cho nhãn của resource.
     *
     * Filament chạy Str::ucwords() lên nhãn model (Resource/Concerns/HasLabels.php)
     * rồi dùng kết quả cho CẢ menu bên trái LẪN tiêu đề trang: ra "Dòng Xe",
     * "Bài Viết", "Chuyển Hướng", "Banner Trang Chủ". Tiếng Việt không viết hoa
     * giữa câu như vậy, mà nhãn lại do người dùng đặt trong config nên phải giữ
     * nguyên từng chữ họ gõ.
     *
     * Ghi đè METHOD chứ không đặt lại property $hasTitleCaseModelLabel: property
     * đó đã khai ở Filament\Resources\Resource, khai lại trong trait là PHP báo
     * "define the same property... considered incompatible" và chết ngay khi
     * nạp class.
     *
     * Một chỗ sửa cho mọi resource. Trước kia trait này chỉ ghi đè
     * getNavigationLabel() nên chữa được menu mà bỏ sót tiêu đề trang.
     */
    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }
}

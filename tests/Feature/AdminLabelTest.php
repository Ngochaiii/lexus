<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Tests\TestCase;

/**
 * Nhãn trong admin phải giữ nguyên chữ người dùng gõ.
 *
 * Filament chạy Str::ucwords() lên nhãn model rồi dùng cho cả menu lẫn tiêu đề
 * trang, ra "Dòng Xe", "Bài Viết", "Banner Trang Chủ". Tiếng Việt không viết
 * hoa giữa câu như vậy.
 */
class AdminLabelTest extends TestCase
{
    /** @return array<int, class-string> */
    protected function resources(): array
    {
        return Filament::getPanel('admin')->getResources();
    }

    public function test_khong_resource_nao_bi_viet_hoa_dau_tu(): void
    {
        $hong = [];

        foreach ($this->resources() as $r) {
            $goc = $r::getPluralModelLabel();

            if ($r::getNavigationLabel() !== $goc || $r::getTitleCasePluralModelLabel() !== $goc) {
                $hong[] = class_basename($r).': "'.$goc.'" → menu "'.$r::getNavigationLabel()
                    .'", tiêu đề "'.$r::getTitleCasePluralModelLabel().'"';
            }
        }

        $this->assertSame([], $hong, "Nhãn bị đổi chữ:\n".implode("\n", $hong));
    }

    /**
     * Chốt cái sai cụ thể bằng một nhãn thật, không suy luận vòng vo: nhãn của
     * mặt hàng là "Dòng xe" — nếu đâu đó viết hoa lại thì thành "Dòng Xe" và
     * test này đỏ ngay.
     */
    public function test_nhan_nhieu_chu_giu_nguyen_chu_thuong(): void
    {
        $r = ProductResource::class;

        $this->assertSame('Dòng xe', $r::getNavigationLabel());
        $this->assertSame('Dòng xe', $r::getTitleCasePluralModelLabel());
        $this->assertNotSame('Dòng Xe', $r::getNavigationLabel());
    }
}

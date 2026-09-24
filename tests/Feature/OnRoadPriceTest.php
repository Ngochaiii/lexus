<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\OnRoadPrice;
use Tests\TestCase;

/** Giá lăn bánh tạm tính ở thẻ phiên bản trang xe. */
class OnRoadPriceTest extends TestCase
{
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now()]);
    }

    public function test_xe_xang_hybrid_cong_truoc_ba_12_va_bien_so(): void
    {
        $v = $this->product->variants()->create(['name' => 'ES 350h Premium', 'price' => 3_350_000_000]);

        // 3,35 tỷ + 12% (402 triệu) + biển số 14 triệu
        $this->assertSame(3_766_000_000, OnRoadPrice::for($v)['total']);
    }

    public function test_xe_dien_chay_pin_truoc_ba_0(): void
    {
        $v = $this->product->variants()->create(['name' => 'ES 500e', 'price' => 3_000_000_000, 'note' => 'Thuần điện']);

        $this->assertTrue(OnRoadPrice::isBattery($v));
        $this->assertSame(3_014_000_000, OnRoadPrice::for($v)['total']);
    }

    public function test_hybrid_co_chu_dien_trong_ghi_chu_van_tinh_12(): void
    {
        $v = $this->product->variants()->create(['name' => 'NX 350h', 'price' => 1_000_000_000, 'note' => 'Hybrid xăng điện']);

        $this->assertFalse(OnRoadPrice::isBattery($v));
    }

    public function test_chua_co_gia_thi_khong_tinh_va_trang_xe_dat_gia_len_truoc_mau(): void
    {
        $v = $this->product->variants()->create(['name' => 'ES 350h Luxury', 'price' => null]);
        $this->assertNull(OnRoadPrice::for($v));

        $this->product->variants()->create(['name' => 'ES 350h Premium', 'price' => 3_350_000_000]);
        $html = $this->get('/san-pham/es')->assertOk()->getContent();

        $this->assertStringContainsString('Lăn bánh Hà Nội (tạm tính)', $html);
        $this->assertStringContainsString('3,77 tỷ', $html);
        // Giá & phiên bản đứng trước mọi mục nội dung khác.
        $this->assertLessThan(strpos($html, 'id="overview"'), strpos($html, 'id="versions"'));
    }
}

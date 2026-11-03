<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

/**
 * Ghi chú đầu bảng giá (03/11/2026). Tháng 11 chị Hà tập trung RX 350h; nhóm
 * quảng cáo "RX350 tên cũ" dẫn khách gõ "lexus rx350" về /san-pham/rx — trang
 * phải nói ngay bản đang bán là RX 350h. Ô này sửa được trong admin, dùng tiếp
 * cho ưu đãi/xe có sẵn khi chị Hà xác nhận.
 */
class ProductNoticeTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_11_03_090000_product_notice_rx350h.php';

    private const OLD_HIGHLIGHTS = [
        ['value' => '371', 'unit' => 'HP', 'label' => 'Công suất · RX 500h'],
        ['value' => '5,9', 'unit' => 'giây', 'label' => '0–100 km/h · RX 500h'],
        ['value' => 'AWD', 'unit' => '', 'label' => 'Dẫn động bốn bánh'],
        ['value' => '612', 'unit' => 'lít', 'label' => 'Khoang hành lý'],
    ];

    private function rx(array $attributes = []): Product
    {
        $rx = Product::create($attributes + ['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published',
            'published_at' => now(), 'price_from' => 3_350_000_000, 'highlights' => self::OLD_HIGHLIGHTS,
            'tagline' => 'SUV hạng sang cỡ trung, thuần hybrid']);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);

        return $rx;
    }

    private function migrate(): void
    {
        (require database_path(self::MIGRATION))->up();
    }

    public function test_ghi_chu_hien_dau_bang_gia(): void
    {
        $this->rx(['notice' => 'Bản RX đang bán là RX 350h hybrid.']);

        $html = $this->get('/san-pham/rx')->assertOk()->getContent();
        $notice = strpos($html, 'class="versions-notice"');

        $this->assertNotFalse($notice);
        $this->assertStringContainsString('Bản RX đang bán là RX 350h hybrid.', $html);
        $this->assertLessThan(strpos($html, 'class="variant-grid"'), $notice, 'ghi chú nằm trên các phiên bản');
    }

    public function test_khong_co_ghi_chu_thi_khong_hien(): void
    {
        $this->rx();

        $this->assertStringNotContainsString('versions-notice', $this->get('/san-pham/rx')->getContent());
    }

    public function test_migration_dat_ghi_chu_rx350h_va_dua_rx350h_len_truoc(): void
    {
        $rx = $this->rx(['notice' => null]);

        $this->migrate();
        $after = $rx->fresh()->only('notice', 'highlights', 'tagline');
        $this->migrate();
        $this->assertSame($after, $rx->fresh()->only('notice', 'highlights', 'tagline'));

        $rx->refresh();
        $this->assertStringContainsString('RX 350h', $rx->notice);
        $this->assertStringContainsString('RX 350', $rx->notice);
        $this->assertStringContainsString('RX 350h', $rx->highlights[0]['label']);
        $this->assertStringNotContainsString('500h', $rx->highlights[0]['label']);
        $this->assertStringContainsString('RX 350h', $rx->tagline, 'khách gõ rx350 thấy RX 350h ngay ở hero');
    }

    public function test_migration_khong_de_ghi_chu_va_noi_bat_da_sua_tay(): void
    {
        $rx = $this->rx(['notice' => 'Chị Hà tự viết.', 'highlights' => [['value' => '1', 'unit' => '', 'label' => 'Sửa tay']],
            'tagline' => 'Tagline sửa tay']);

        $this->migrate();

        $this->assertSame('Tagline sửa tay', $rx->fresh()->tagline);
        $this->assertSame('Chị Hà tự viết.', $rx->fresh()->notice);
        $this->assertSame('Sửa tay', $rx->fresh()->highlights[0]['label']);
    }

    public function test_admin_va_seeder_co_o_ghi_chu(): void
    {
        $this->assertStringContainsString("make('notice')", file_get_contents(app_path('Filament/Resources/Products/Schemas/ProductForm.php')));
        $this->assertStringContainsString("'notice'", file_get_contents(database_path('seeders/Brands/LexusSeeder.php')));
    }
}

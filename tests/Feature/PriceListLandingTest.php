<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * /bang-gia là trang đích quảng cáo "giá xe lexus" (13/10/2026: 106 nhấp,
 * 0 khách). Khách xem xong giá niêm yết là đi, muốn biết lăn bánh phải qua
 * form. Bảng hiện luôn lăn bánh tạm tính, và nút cuối dòng mở Zalo của
 * chị Hà thay vì form.
 */
class PriceListLandingTest extends TestCase
{
    private const OLD_EXCERPT = 'Giá niêm yết từng phiên bản, cập nhật theo bảng giá của đại lý. Bấm "Nhận báo giá" để có giá lăn bánh chi tiết.';

    private const NEW_EXCERPT = 'Giá niêm yết và giá lăn bánh Hà Nội tạm tính của từng phiên bản. Cần báo giá chi tiết theo nơi đăng ký xe, nhắn Zalo cho Thu Hà.';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('hotline', '0934846666');
        Setting::put('advisor_name', 'Thu Hà');

        $rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now(),
            'price_from' => 3_350_000_000]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);

        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(),
            'price_from' => 2_940_000_000]);
        $es->variants()->create(['name' => 'ES 500e', 'note' => 'Thuần điện', 'price' => 2_940_000_000, 'sort' => 1]);

        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published',
            'seo' => ['excerpt' => self::OLD_EXCERPT]]);
    }

    private function table(): string
    {
        $html = $this->get('/bang-gia')->assertOk()->getContent();
        preg_match('#<section class="[^"]*price-table.*?</section>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_bang_gia_hien_lan_banh_tam_tinh_tung_phien_ban(): void
    {
        $table = $this->table();

        // 3,35 tỷ + 12% trước bạ + 14 triệu biển số.
        $this->assertStringContainsString(catalog_money_short(3_766_000_000), $table);
        // Xe điện chạy pin miễn trước bạ: 2,94 tỷ + 14 triệu.
        $this->assertStringContainsString(catalog_money_short(2_954_000_000), $table);
        $this->assertStringContainsString('Lăn bánh Hà Nội', $table);
        $this->assertStringContainsString('tạm tính', $table);
    }

    public function test_nut_cuoi_dong_mo_zalo_chuyen_vien_khong_qua_form(): void
    {
        Setting::put('advisor_zalo', '0989345989');

        $table = $this->table();

        $this->assertStringContainsString('href="https://zalo.me/0989345989"', $table);
        $this->assertStringContainsString('Nhắn Zalo Thu Hà hỏi giá lăn bánh RX 350h Premium', $table);
        $this->assertStringNotContainsString('data-quote', $table);
    }

    public function test_chua_co_zalo_thi_van_dung_form_bao_gia(): void
    {
        $this->assertStringContainsString('data-quote', $this->table());
    }

    public function test_migration_doi_cau_mo_dau_khi_con_ban_cu(): void
    {
        $migration = require database_path('migrations/2026_10_14_100000_price_list_zalo_intro.php');
        $migration->up();
        $migration->up();

        $this->assertSame(self::NEW_EXCERPT, Page::where('slug', 'bang-gia')->first()->seo['excerpt']);
        $this->get('/bang-gia')->assertSee(self::NEW_EXCERPT);
    }

    public function test_cau_mo_dau_tu_sua_trong_admin_thi_giu(): void
    {
        Page::where('slug', 'bang-gia')->first()->update(['seo' => ['excerpt' => 'Câu tự viết']]);

        (require database_path('migrations/2026_10_14_100000_price_list_zalo_intro.php'))->up();

        $this->assertSame('Câu tự viết', Page::where('slug', 'bang-gia')->first()->seo['excerpt']);
    }

    public function test_seeder_dung_cau_moi(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));

        $this->assertStringContainsString(self::NEW_EXCERPT, $seeder);
        $this->assertStringNotContainsString(self::OLD_EXCERPT, $seeder);
    }
}

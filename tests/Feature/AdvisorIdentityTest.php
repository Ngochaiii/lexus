<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Post;
use App\Models\Setting;
use App\Support\JsonLd;
use Tests\TestCase;

/**
 * Website là trang tư vấn cá nhân của chuyên viên, không phải web của đại lý.
 *
 * Tài khoản Google Ads của chị Thu Hà bị tạm ngưng vì "Các phương thức kinh
 * doanh không được chấp nhận" (ngụ ý là đại lý) — người xét kháng nghị chỉ
 * xem được website. Đầu trang, chân trang và dữ liệu cấu trúc phải nói rõ:
 * đây là site của chuyên viên, đại lý cho phép dùng tên và logo.
 */
class AdvisorIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://lexusthanglong.test']);
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('hotline', '0989345989');
        Setting::put('advisor_name', 'Thu Hà', 'home');
        Setting::put('advisor_full_name', 'Nguyễn Thị Thu Hà', 'home');
        Setting::put('advisor_phone', '0989345989', 'home');
    }

    public function test_dau_trang_moi_trang_ghi_ro_website_ca_nhan_cua_chuyen_vien(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<header class="site-header.*?</header>#s', $html, $header);

        $this->assertStringContainsString('Website tư vấn cá nhân', $header[0] ?? '');
        $this->assertStringContainsString('Nguyễn Thị Thu Hà', $header[0] ?? '');
    }

    public function test_chan_trang_noi_ro_duoc_dai_ly_cho_phep_va_khong_phai_web_chinh_thuc(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<footer class="site-footer">.*?</footer>#s', $html, $footer);
        $text = preg_replace('/\s+/', ' ', strip_tags($footer[0] ?? ''));

        $this->assertStringContainsString('Nguyễn Thị Thu Hà – chuyên viên tư vấn bán hàng tại Lexus Thăng Long', $text);
        $this->assertStringContainsString('được đại lý cho phép sử dụng tên và logo', $text);
        $this->assertStringContainsString('không phải website chính thức của Lexus Việt Nam hay của đại lý', $text);
    }

    public function test_du_lieu_cau_truc_website_do_chuyen_vien_dung_ten(): void
    {
        $site = JsonLd::website();
        $this->assertSame('Thu Hà – Tư vấn Lexus Thăng Long', $site['name']);
        $this->assertSame(['@id' => JsonLd::advisorId()], $site['publisher']);

        $advisor = JsonLd::advisor();
        $this->assertSame('Nguyễn Thị Thu Hà', $advisor['name']);
        $this->assertSame('Thu Hà', $advisor['alternateName']);
        $this->assertSame('+84989345989', $advisor['telephone']);

        // Đại lý vẫn là nơi bán xe (Offer.seller) nhưng không nhận website này
        // là của mình, và số máy là của chuyên viên chứ không phải tổng đài đại lý.
        $org = JsonLd::organization();
        $this->assertSame('AutoDealer', $org['@type']);
        $this->assertSame('Lexus Thăng Long', $org['name']);
        $this->assertArrayNotHasKey('url', $org);
        $this->assertArrayNotHasKey('telephone', $org);

        $this->get('/')->assertOk()
            ->assertSee('<meta property="og:site_name" content="Thu Hà – Tư vấn Lexus Thăng Long">', false);
    }

    public function test_tong_dai_rieng_cua_dai_ly_van_giu_trong_du_lieu_dai_ly(): void
    {
        Setting::put('hotline', '02433728888');

        $this->assertSame('+842433728888', JsonLd::organization()['telephone']);
    }

    public function test_migration_dien_ho_ten_day_du_khi_con_trong(): void
    {
        $migration = require database_path('migrations/2026_10_04_100000_advisor_identity_on_site.php');

        Setting::query()->where('key', 'advisor_full_name')->delete();
        $migration->up();
        $this->assertSame('Nguyễn Thị Thu Hà', Setting::get('advisor_full_name'));

        Setting::put('advisor_full_name', 'Thu Hà Nguyễn', 'home');
        $migration->up();
        $this->assertSame('Thu Hà Nguyễn', Setting::get('advisor_full_name'), 'đã sửa trong Cài đặt thì giữ');
    }

    public function test_banner_trang_chu_noi_bang_loi_chuyen_vien(): void
    {
        $migration = require database_path('migrations/2026_10_04_100000_advisor_identity_on_site.php');
        $seeded = Banner::create(['title' => 'A', 'is_active' => true, 'sort' => 1] + $migration::BANNER_OLD);
        $edited = Banner::create(['title' => 'B', 'is_active' => false, 'eyebrow' => 'TỰ VIẾT', 'subtitle' => 'Câu tự viết']);

        $migration->up();

        $this->assertSame($migration::BANNER_NEW['subtitle'], $seeded->fresh()->subtitle);
        $this->assertSame('THU HÀ · TƯ VẤN LEXUS THĂNG LONG', $seeded->fresh()->eyebrow);
        $this->assertSame('Câu tự viết', $edited->fresh()->subtitle, 'đã sửa trong admin thì giữ');
        $this->get('/')->assertOk()->assertDontSee('Đại lý Lexus chính hãng tại ngã tư');
    }

    public function test_bai_viet_do_chuyen_vien_dang(): void
    {
        $post = Post::create(['title' => 'Bảng giá Lexus', 'slug' => 'bang-gia-lexus', 'status' => 'published', 'published_at' => now()]);

        $this->assertSame(['@id' => JsonLd::advisorId()], JsonLd::forPost($post)['publisher']);
    }
}

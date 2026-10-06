<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Setting;
use App\Support\JsonLd;
use Tests\TestCase;

/** Sửa theo kết quả kiểm tra claude-seo (27/09/2026). */
class SeoAuditFixesTest extends TestCase
{
    public function test_dang_bai_trong_ngay_dang_thi_tu_dien_ngay_va_co_date_published(): void
    {
        $post = Post::create(['title' => 'Bài Gemini', 'slug' => 'bai-gemini', 'status' => 'draft']);
        $this->assertNull($post->published_at, 'nháp thì chưa có ngày đăng');

        $this->travelTo(now()->setDate(2026, 9, 28)->setTime(9, 0));
        $post->update(['status' => 'published']);
        $this->assertSame('2026-09-28 09:00', $post->fresh()->published_at->format('Y-m-d H:i'));

        $html = $this->get('/tin-tuc/bai-gemini')->assertOk()->getContent();
        $this->assertStringContainsString('"datePublished":"2026-09-28T09:00:00', $html);
        $this->assertStringContainsString('<time datetime="2026-09-28T09:00:00', $html, 'trang hiện ngày đăng');
    }

    public function test_toa_do_showroom_va_zalo_chuyen_vien_trong_du_lieu_cau_truc(): void
    {
        $this->assertSame(['@type' => 'GeoCoordinates', 'latitude' => 21.0301, 'longitude' => 105.7812], JsonLd::geo(' 21.0301, 105.7812 '));
        $this->assertNull(JsonLd::geo('21,0301 105,7812'), 'sai định dạng thì bỏ');
        $this->assertNull(JsonLd::geo('48.8584, 2.2945'), 'ngoài Việt Nam thì bỏ');

        Setting::put('geo', '21.0301, 105.7812');
        Setting::put('advisor_name', 'Thu Hà', 'home');
        Setting::put('zalo', '0989345989', 'social');

        $org = JsonLd::organization();
        $this->assertSame(21.0301, $org['geo']['latitude']);
        $this->assertSame(['https://zalo.me/0989345989'], $org['employee']['sameAs']);
    }

    public function test_kenh_tiktok_hien_o_trang_chu_chan_trang_va_du_lieu_cau_truc(): void
    {
        $this->get('/')->assertOk()->assertDontSee('tiktok.com', false);

        Setting::put('tiktok', 'https://www.tiktok.com/@thuhalexus28', 'social');
        Setting::put('advisor_name', 'Thu Hà', 'home');

        $this->get('/')->assertOk()
            ->assertSee('TikTok: @thuhalexus28')
            ->assertSee('href="https://www.tiktok.com/@thuhalexus28"', false);
        $this->assertContains('https://www.tiktok.com/@thuhalexus28', JsonLd::advisor()['sameAs']);
    }

    public function test_trang_co_speculation_rules_bo_admin_va_form(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<script type="speculationrules">(.*?)</script>#s', $html, $m);
        $rules = json_decode($m[1] ?? '', true);

        $this->assertSame('moderate', $rules['prefetch'][0]['eagerness']);
        $excluded = $rules['prefetch'][0]['where']['and'][1]['not']['href_matches'];
        $this->assertContains('/admin/*', $excluded);
        $this->assertContains('/gui-form/*', $excluded);
    }

    public function test_mo_ta_trang_chu_mac_dinh_khong_qua_160_ky_tu(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));
        preg_match("/'site_description' => '([^']+)'/u", $seeder, $m);

        $this->assertLessThanOrEqual(160, mb_strlen($m[1]), 'Google cắt mô tả dài hơn ~160 ký tự');
    }

    // Tài khoản Google Ads đứng tên chuyên viên: trang chủ không được tự nhận
    // là đại lý (chính sách "Trình bày sai sự thật"). Nói rõ ai đứng tên site.
    public function test_tieu_de_va_mo_ta_trang_chu_noi_ro_la_site_cua_chuyen_vien(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));
        preg_match("/'seo_home_title'\s*=> '([^']+)'/u", $seeder, $title);
        preg_match("/'site_description' => '([^']+)'/u", $seeder, $desc);

        foreach ([$title[1], $desc[1]] as $text) {
            $this->assertStringStartsWith('Thu Hà', $text);
            $this->assertStringNotContainsString('Lexus Thăng Long — Đại lý', $text);
        }
        $this->assertLessThanOrEqual(60, mb_strlen($title[1]));
    }

    // Liên kết GA4 ↔ Google Ads bật quảng cáo cá nhân hoá: chính sách phải nói rõ
    // tiếp thị lại, mã nhấp quảng cáo và cách từ chối. Migration đưa lên site thật.
    public function test_chinh_sach_rieng_tu_noi_ro_quang_cao_google(): void
    {
        \App\Models\Setting::put('site_name', 'Lexus Thăng Long');
        $old = [['type' => 'text', 'title' => 'Tóm tắt', 'body' => '<p>Cập nhật lần cuối: 24/09/2026. Chính sách…</p>']];
        $page = \App\Models\Page::create(['slug' => 'quyen-rieng-tu', 'title' => 'Quyền riêng tư', 'status' => 'published', 'sections' => $old]);

        (require database_path('migrations/2026_09_30_120000_advisor_experience_and_ads_privacy.php'))->up();

        $html = $this->get('/quyen-rieng-tu')->assertOk()->getContent();
        $this->assertStringContainsString('Quảng cáo, tiếp thị lại và cách từ chối', $html);
        $this->assertStringContainsString('gclid', $html);
        $this->assertStringContainsString('https://myadcenter.google.com/', $html);
        $this->assertStringContainsString('Cập nhật lần cuối: 28/09/2026', $html);
        $this->assertSame('10 năm kinh nghiệm bán ô tô', \App\Models\Setting::get('advisor_experience'));

        (require database_path('migrations/2026_09_30_120000_advisor_experience_and_ads_privacy.php'))->up();
        $this->assertCount(2, $page->fresh()->sections, 'chạy lại không thêm trùng mục 7');

        $legal = file_get_contents(database_path('content/legal-pages.php'));
        $this->assertStringContainsString("'7. Quảng cáo Google'", $legal);
    }
}

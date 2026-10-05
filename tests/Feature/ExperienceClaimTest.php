<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Kinh nghiệm của chuyên viên phải đúng sự thật: giấy xác nhận của đại lý ghi
 * chị Thu Hà làm tại Lexus Thăng Long từ 01/03/2017 — "10 năm kinh nghiệm bán
 * ô tô" là phóng đại (Google 02/10/2026: bịa kinh nghiệm tác giả là tín hiệu
 * trang kém tin cậy). Đổi thành câu khớp giấy đại lý.
 */
class ExperienceClaimTest extends TestCase
{
    private const NEW = 'Tư vấn Lexus tại Lexus Thăng Long từ 2017';

    public function test_doi_cau_kinh_nghiem_trong_cai_dat_va_bai_viet(): void
    {
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('advisor_name', 'Thu Hà', 'home');
        Setting::put('advisor_experience', '10 năm kinh nghiệm bán ô tô', 'home');
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai', 'status' => 'published', 'published_at' => now(),
            'sections' => [['type' => 'text', 'body' => '<p>Thu Hà, 10 năm kinh nghiệm bán ô tô, tư vấn bạn.</p>']]]);

        $migration = require database_path('migrations/2026_10_05_100000_advisor_experience_since_2017.php');
        $migration->up();
        $migration->up();

        $this->assertSame(self::NEW, Setting::get('advisor_experience'));
        $this->assertSame('<p>Thu Hà, tư vấn Lexus tại Lexus Thăng Long từ 2017, tư vấn bạn.</p>', $post->fresh()->sections[0]['body']);
        $this->get('/')->assertOk()->assertDontSee('10 năm kinh nghiệm');
    }

    public function test_cau_tu_viet_trong_cai_dat_thi_giu(): void
    {
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('advisor_experience', 'Câu tự viết', 'home');

        (require database_path('migrations/2026_10_05_100000_advisor_experience_since_2017.php'))->up();

        $this->assertSame('Câu tự viết', Setting::get('advisor_experience'));
    }

    public function test_seeder_dung_cau_moi(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));

        $this->assertStringContainsString("'advisor_experience' => '".self::NEW."'", $seeder);
        $this->assertStringNotContainsString('10 năm kinh nghiệm', $seeder);
    }
}

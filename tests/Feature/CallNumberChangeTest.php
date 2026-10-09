<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Support\ArticleContext;
use App\Support\Phone;
use Tests\TestCase;

/**
 * 09/10/2026: chị Thu Hà nghe điện thoại bằng số 0934 846 666; Zalo vẫn là
 * 0989 345 989. Web hiện số gọi và số Zalo riêng — chỗ ghi "Zalo: …" lấy số
 * từ đường dẫn Zalo, không lấy số gọi.
 */
class CallNumberChangeTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_09_100000_call_number_0934846666.php';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('site_name', 'Lexus Thăng Long');
        Setting::put('advisor_name', 'Thu Hà', 'home');
        Setting::put('advisor_phone', '0934846666', 'home');
        Setting::put('hotline', '0934846666');
        Setting::put('zalo', 'https://zalo.me/0989345989');
    }

    public function test_so_zalo_lay_tu_duong_dan_zalo(): void
    {
        $this->assertSame('0989 345 989', Phone::zalo('https://zalo.me/0989345989'));
        $this->assertSame('0989 345 989', Phone::zalo('0989345989'));
        $this->assertNull(Phone::zalo('https://zalo.me/g/abcxyz'));
        $this->assertNull(Phone::zalo(null));
    }

    public function test_web_hien_so_goi_va_so_zalo_rieng(): void
    {
        $html = $this->get('/tin-tuc')->assertOk()->getContent();
        $this->assertStringContainsString('tel:0934846666', $html);
        $this->assertStringContainsString('Zalo: 0989 345 989', $html);
        $this->assertStringNotContainsString('Zalo: 0934', $html);

        $card = view('frontend.partials.contact-details')->render();
        $this->assertStringContainsString('0934 846 666', $card);
        $this->assertStringContainsString('Nhắn Zalo 0989 345 989', $card);
    }

    public function test_ho_so_gemini_noi_ro_so_zalo(): void
    {
        $context = ArticleContext::build('Bài thử');

        $this->assertStringContainsString('Điện thoại chuyên viên Thu Hà: 0934 846 666', $context);
        $this->assertStringContainsString('Zalo chuyên viên Thu Hà: 0989 345 989', $context);
    }

    public function test_migration_doi_so_goi_giu_so_zalo(): void
    {
        Setting::put('advisor_phone', '0989345989', 'home');
        Setting::put('hotline', '0989345989');
        Setting::put('advisor_zalo', 'https://zalo.me/0989345989', 'home');

        $post = Post::create(['title' => 'Bài', 'slug' => 'bai', 'status' => 'published', 'published_at' => now(),
            'excerpt' => 'Gọi 0989 345 989 để nhận báo giá.',
            'sections' => [
                ['type' => 'text', 'body' => '<p>Gọi hoặc nhắn Zalo 0989 345 989 để thực hiện các quyền trên.</p>'
                    .'<p>Vui lòng gọi hoặc nhắn Zalo chuyên viên Thu Hà: 0989 345 989. Hết.</p>'
                    .'<p>qua số 0989 345 989 (gọi hoặc Zalo).</p>'
                    .'<p>Zalo: 0989 345 989 — nhắn tin trực tiếp. <a href="https://zalo.me/0989345989">Zalo</a></p>'
                    .'<p>Điện thoại chuyên viên Thu Hà: 0989.345.989; <a href="tel:0989345989">gọi</a>, <a href="tel:+84989345989">gọi</a>.</p>'
                    .'<p>Số khác 0912 345 678 giữ nguyên.</p>'],
                ['type' => 'faq', 'rows' => [['label' => 'Hỏi', 'value' => 'Liên hệ số 0989 345 989 (Thu Hà).']]],
            ]]);
        $page = Page::create(['slug' => 'lien-he', 'title' => 'Liên hệ', 'status' => 'published',
            'seo' => ['description' => 'Liên hệ chuyên viên Thu Hà: số 0989 345 989.']]);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $after = $post->fresh()->only('excerpt', 'sections');
        $migration->up();
        $this->assertSame($after, $post->fresh()->only('excerpt', 'sections'));

        $this->assertSame('0934846666', Setting::get('advisor_phone'));
        $this->assertSame('0934846666', Setting::get('hotline'));
        $this->assertSame('https://zalo.me/0989345989', Setting::get('advisor_zalo'));
        $this->assertSame('https://zalo.me/0989345989', Setting::get('zalo'));

        $post->refresh();
        $this->assertSame('Gọi 0934 846 666 để nhận báo giá.', $post->excerpt);
        $this->assertSame(
            '<p>Gọi 0934 846 666 hoặc nhắn Zalo 0989 345 989 để thực hiện các quyền trên.</p>'
            .'<p>Vui lòng gọi chuyên viên Thu Hà: 0934 846 666 hoặc nhắn Zalo 0989 345 989. Hết.</p>'
            .'<p>qua số 0934 846 666 (gọi) hoặc Zalo 0989 345 989.</p>'
            .'<p>Zalo: 0989 345 989 — nhắn tin trực tiếp. <a href="https://zalo.me/0989345989">Zalo</a></p>'
            .'<p>Điện thoại chuyên viên Thu Hà: 0934 846 666; <a href="tel:0934846666">gọi</a>, <a href="tel:+84934846666">gọi</a>.</p>'
            .'<p>Số khác 0912 345 678 giữ nguyên.</p>',
            $post->sections[0]['body'],
        );
        $this->assertSame('Liên hệ số 0934 846 666 (Thu Hà).', $post->sections[1]['rows'][0]['value']);
        $this->assertSame('Liên hệ chuyên viên Thu Hà: số 0934 846 666.', $page->fresh()->seo['description']);
    }

    public function test_so_da_sua_tay_trong_cai_dat_thi_giu(): void
    {
        Setting::put('advisor_phone', '0911111111', 'home');

        (require database_path(self::MIGRATION))->up();

        $this->assertSame('0911111111', Setting::get('advisor_phone'));
    }

    public function test_seeder_dung_so_moi(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));

        $this->assertStringContainsString("'advisor_phone' => '0934846666'", $seeder);
        $this->assertStringContainsString("'zalo' => 'https://zalo.me/0989345989'", $seeder);
        foreach (['seeders/LexusSiteSeeder.php', 'seeders/Brands/LexusSeeder.php', 'content/legal-pages.php'] as $file) {
            $text = file_get_contents(database_path($file));
            $this->assertDoesNotMatchRegularExpression('/(?<!Zalo )(?<!zalo\.me\/)0989 ?345 ?989/u', $text, $file);
        }
    }
}

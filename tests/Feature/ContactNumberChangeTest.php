<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use Tests\TestCase;

/**
 * 30/10/2026: chị Thu Hà không nhận được cuộc gọi/Zalo qua 0934 846 666 và
 * 0989 345 989, chuyển sang một số duy nhất 0977 573 366 cho cả gọi lẫn Zalo.
 */
class ContactNumberChangeTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_30_090000_contact_number_0977573366.php';

    private function migrate(): void
    {
        (require database_path(self::MIGRATION))->up();
    }

    public function test_migration_doi_so_goi_va_so_zalo_trong_cai_dat(): void
    {
        Setting::put('advisor_phone', '0934846666', 'home');
        Setting::put('hotline', '0934846666');
        Setting::put('advisor_zalo', 'https://zalo.me/0989345989', 'home');
        Setting::put('zalo', '0989345989', 'social');
        Setting::put('address', 'Ngã tư Phạm Hùng, Cầu Giấy');

        $this->migrate();
        $this->migrate();

        $this->assertSame('0977573366', Setting::get('advisor_phone'));
        $this->assertSame('0977573366', Setting::get('hotline'));
        $this->assertSame('https://zalo.me/0977573366', Setting::get('advisor_zalo'));
        $this->assertSame('0977573366', Setting::get('zalo'));
        $this->assertSame('Ngã tư Phạm Hùng, Cầu Giấy', Setting::get('address'));
    }

    public function test_so_da_sua_tay_khac_thi_giu(): void
    {
        Setting::put('advisor_phone', '0911111111', 'home');

        $this->migrate();

        $this->assertSame('0911111111', Setting::get('advisor_phone'));
    }

    public function test_migration_doi_so_trong_bai_trang_va_form(): void
    {
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai', 'status' => 'published', 'published_at' => now(),
            'excerpt' => 'Gọi 0934 846 666 để nhận báo giá.',
            'sections' => [
                ['type' => 'text', 'body' => '<p>Gọi 0934 846 666 hoặc nhắn Zalo 0989 345 989 để thực hiện các quyền trên.</p>'
                    .'<p>Vui lòng gọi chuyên viên Thu Hà: 0934 846 666 hoặc nhắn Zalo 0989 345 989. Hết.</p>'
                    .'<p>qua số 0934 846 666 (gọi) hoặc Zalo 0989 345 989.</p>'
                    .'<p>Điện thoại chuyên viên tư vấn: 0934 846 666 (Thu Hà), Zalo 0989 345 989.</p>'
                    .'<p>Zalo: 0989.345.989 — <a href="https://zalo.me/0989345989">Zalo</a></p>'
                    .'<p><a href="tel:0934846666">gọi</a>, <a href="tel:+84934846666">gọi</a>, +84989345989.</p>'
                    .'<p>Số khác 0912 345 678 giữ nguyên.</p>'],
                ['type' => 'faq', 'rows' => [['label' => 'Hỏi', 'value' => 'Điện thoại 0934 846 666 (chuyên viên Thu Hà).']]],
            ]]);
        $page = Page::create(['slug' => 'lien-he', 'title' => 'Liên hệ', 'status' => 'published',
            'seo' => ['description' => 'Liên hệ chuyên viên Thu Hà: số 0934 846 666, Zalo 0989 345 989.']]);
        $form = Form::create(['key' => 'thu', 'name' => 'Thử', 'success_message' => 'Cần gấp, gọi 0934 846 666.']);

        $this->migrate();
        $after = $post->fresh()->only('excerpt', 'sections');
        $this->migrate();
        $this->assertSame($after, $post->fresh()->only('excerpt', 'sections'));

        $post->refresh();
        $this->assertSame('Gọi 0977 573 366 để nhận báo giá.', $post->excerpt);
        $this->assertSame(
            '<p>Gọi hoặc nhắn Zalo 0977 573 366 để thực hiện các quyền trên.</p>'
            .'<p>Vui lòng gọi hoặc nhắn Zalo chuyên viên Thu Hà: 0977 573 366. Hết.</p>'
            .'<p>qua số 0977 573 366 (gọi hoặc Zalo).</p>'
            .'<p>Điện thoại chuyên viên tư vấn: 0977 573 366 (Thu Hà, gọi và Zalo).</p>'
            .'<p>Zalo: 0977 573 366 — <a href="https://zalo.me/0977573366">Zalo</a></p>'
            .'<p><a href="tel:0977573366">gọi</a>, <a href="tel:+84977573366">gọi</a>, +84977573366.</p>'
            .'<p>Số khác 0912 345 678 giữ nguyên.</p>',
            $post->sections[0]['body'],
        );
        $this->assertSame('Điện thoại 0977 573 366 (chuyên viên Thu Hà).', $post->sections[1]['rows'][0]['value']);
        $this->assertSame('Liên hệ chuyên viên Thu Hà: số 0977 573 366 (gọi và Zalo).', $page->fresh()->seo['description']);
        $this->assertSame('Cần gấp, gọi 0977 573 366.', $form->fresh()->success_message);
    }

    public function test_seeder_va_noi_dung_mau_dung_so_moi(): void
    {
        $seeder = file_get_contents(database_path('seeders/LexusSiteSeeder.php'));
        $this->assertStringContainsString("'advisor_phone' => '0977573366'", $seeder);
        $this->assertStringContainsString("'zalo' => 'https://zalo.me/0977573366'", $seeder);

        foreach (['seeders/LexusSiteSeeder.php', 'seeders/Brands/LexusSeeder.php', 'content/legal-pages.php'] as $file) {
            $text = file_get_contents(database_path($file));
            $this->assertDoesNotMatchRegularExpression('/0934[ .]?846[ .]?666|0989[ .]?345[ .]?989/', $text, $file);
        }
    }
}

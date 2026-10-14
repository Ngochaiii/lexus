<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Tests\TestCase;

/**
 * Giá phiên bản trên trang sản phẩm là chuẩn (người dùng 14/10/2026): giá từ
 * của xe và mô tả trang /bang-gia tự đi theo, không ghi tay để rồi lệch như
 * ES (2,36 ≠ 2,34) và GX (6,2 ≠ 6,45).
 */
class PriceFollowsVariantsTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_10_14_120000_gx_prices_and_price_from.php';

    private function car(string $slug, string $name, array $prices): Product
    {
        $product = Product::create(['name' => $name, 'slug' => $slug, 'status' => 'published', 'published_at' => now(),
            'price_from' => 1_000_000_000]);
        foreach ($prices as $i => $price) {
            $product->variants()->create(['name' => $name.' '.$i, 'price' => $price, 'sort' => $i]);
        }

        return $product;
    }

    public function test_sua_gia_phien_ban_thi_gia_tu_cua_xe_doi_theo(): void
    {
        $es = $this->car('es', 'Lexus ES', [2_360_000_000, 2_580_000_000]);
        $this->assertSame(2_360_000_000, (int) $es->fresh()->price_from);

        $es->variants()->orderBy('sort')->first()->update(['price' => 2_340_000_000]);

        $this->assertSame(2_340_000_000, (int) $es->fresh()->price_from);
    }

    public function test_xoa_phien_ban_re_nhat_thi_gia_tu_tang(): void
    {
        $rx = $this->car('rx', 'Lexus RX', [3_350_000_000, 4_140_000_000]);

        $rx->variants()->orderBy('sort')->first()->delete();

        $this->assertSame(4_140_000_000, (int) $rx->fresh()->price_from);
    }

    public function test_mo_ta_trang_bang_gia_sinh_tu_gia_san_pham(): void
    {
        $this->car('es', 'Lexus ES', [2_340_000_000, 2_600_000_000]);
        $this->car('gx', 'Lexus GX', [6_450_000_000]);
        Page::create(['slug' => 'bang-gia', 'title' => 'Bảng giá xe Lexus', 'status' => 'published',
            'seo' => ['description' => 'Bảng giá xe Lexus 2026: ES 350h từ 2,36 tỷ, GX 550 6,2 tỷ.']]);

        $html = $this->get('/bang-gia')->assertOk()->getContent();
        preg_match('#<meta name="description" content="([^"]*)"#', $html, $m);

        $this->assertStringContainsString('ES từ 2,34 tỷ', html_entity_decode($m[1] ?? ''));
        $this->assertStringContainsString('GX 6,45 tỷ', html_entity_decode($m[1] ?? ''));
        $this->assertStringNotContainsString('6,2 tỷ', $html);
    }

    public function test_migration_dong_bo_gia_tu_va_doi_chu_gia_gx_cu(): void
    {
        $gx = $this->car('gx', 'Lexus GX', [6_450_000_000]);
        Product::whereKey($gx->id)->update(['price_from' => 6_200_000_000,
            'seo' => json_encode(['title' => 'Lexus GX 550 2026: giá 6,2 tỷ, thông số',
                'description' => 'Giá Lexus GX 550 2026 tại Hà Nội: 6,2 tỷ đồng, lăn bánh khoảng 6,96 tỷ.'])]);

        $post = Post::create(['title' => 'GX 550 hay LX 600', 'slug' => 'gx-lx', 'status' => 'published', 'published_at' => now(),
            'excerpt' => 'Lexus GX 550 giá 6,2 tỷ, 349 HP.',
            'sections' => [['type' => 'text', 'body' => '<p>Lexus GX 550 (6,2 tỷ đồng) gọn hơn và rẻ hơn LX 600 khoảng 2,4 tỷ đồng. '
                .'Giá niêm yết GX 550: 6,20 tỷ. Lăn bánh khoảng 6,96 tỷ. Bảng: 6.200.000.000 đ. Tiêu thụ 6,24 L/100 km.</p>']]]);

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $gx->refresh();
        $this->assertSame(6_450_000_000, (int) $gx->price_from);
        $this->assertSame('Lexus GX 550 2026: giá 6,45 tỷ, thông số', $gx->seo['title']);
        $this->assertSame('Giá Lexus GX 550 2026 tại Hà Nội: 6,45 tỷ đồng, lăn bánh khoảng 7,24 tỷ.', $gx->seo['description']);

        $post->refresh();
        $this->assertSame('Lexus GX 550 giá 6,45 tỷ, 349 HP.', $post->excerpt);
        $this->assertSame('<p>Lexus GX 550 (6,45 tỷ đồng) gọn hơn và rẻ hơn LX 600 khoảng 2,1 tỷ đồng. '
            .'Giá niêm yết GX 550: 6,45 tỷ. Lăn bánh khoảng 7,24 tỷ. Bảng: 6.450.000.000 đ. Tiêu thụ 6,24 L/100 km.</p>', $post->sections[0]['body']);
    }

    public function test_seeder_khong_con_gia_gx_cu(): void
    {
        foreach (['seeders/LexusSiteSeeder.php', 'seeders/Brands/LexusSeeder.php'] as $file) {
            $code = preg_replace('#/\*.*?\*/#s', '', file_get_contents(database_path($file)));

            foreach (['6,2 tỷ', '6,20 tỷ', '6,96 tỷ', '6.200.000.000', '6_200_000_000'] as $old) {
                $this->assertStringNotContainsString($old, $code, "$file còn $old");
            }
        }
    }
}

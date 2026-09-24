<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    protected function product(array $attributes = []): Product
    {
        return Product::create([
            'name' => 'Lexus GX 550',
            'status' => 'published',
            'published_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_danh_sach_chi_tra_ban_da_dang(): void
    {
        $this->product(['name' => 'Đã đăng']);
        $this->product(['name' => 'Còn nháp', 'status' => 'draft']);
        $this->product(['name' => 'Hẹn giờ', 'published_at' => now()->addDay()]);

        $response = $this->getJson('/api/v1/products')->assertOk();

        $this->assertSame(['Đã đăng'], array_column($response->json('data'), 'name'));
    }

    public function test_danh_sach_khong_keo_theo_sections_va_specs(): void
    {
        $this->product(['sections' => [['title' => 'Thư viện', 'items' => [['image' => 'a.webp']]]]]);

        $row = $this->getJson('/api/v1/products')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('sections', $row);
        $this->assertArrayNotHasKey('specs', $row);
    }

    public function test_chi_tiet_tra_sections_da_bo_field_trong(): void
    {
        $this->product([
            'slug' => 'gx-550',
            'sections' => [[
                'title' => 'Thư viện', 'intro' => '', 'type' => 'media', 'layout' => 'slider',
                'items' => [['image' => 'a.webp', 'label' => '', 'desc' => '']],
            ]],
        ]);

        $section = $this->getJson('/api/v1/products/gx-550')->assertOk()->json('data.sections.0');

        $this->assertArrayNotHasKey('intro', $section);
        $this->assertSame(['image' => 'a.webp'], $section['items'][0]);
    }

    public function test_chi_tiet_kem_phien_ban_va_tuy_chon(): void
    {
        $product = $this->product(['slug' => 'gx-550']);
        $product->variants()->create([
            'name' => 'Luxury',
            'price' => 100,
            'battery_kwh' => 75.3,
            'range_km' => 496,
            'is_default' => true,
        ]);
        $product->options()->create(['name' => 'Caviar Black', 'hex' => '#111111']);

        $data = $this->getJson('/api/v1/products/gx-550')->assertOk()->json('data');

        $this->assertSame('Luxury', $data['variants'][0]['name']);
        $this->assertSame('75.30', $data['variants'][0]['battery_kwh']);
        $this->assertSame(496, $data['variants'][0]['range_km']);
        $this->assertTrue($data['variants'][0]['is_default']);
        $this->assertSame('#111111', $data['options'][0]['hex']);
    }

    public function test_chi_tiet_tra_ghi_chu_thong_so_brochure_va_canonical_da_nhap(): void
    {
        $this->product([
            'slug' => 'vf-7',
            'brochure_url' => 'https://example.com/brochures/vf-7.pdf',
            'spec_notes' => [[
                'label' => 'An toàn & an ninh',
                'body' => 'Camera 360 độ và 8 túi khí.',
            ]],
            'seo' => [
                'canonical' => 'https://cars.example/xe-dien-vf-7',
                'image' => 'catalog/seo/vf-7.webp',
            ],
        ]);

        $data = $this->getJson('/api/v1/products/vf-7')->assertOk()->json('data');

        $this->assertSame('https://example.com/brochures/vf-7.pdf', $data['brochure_url']);
        $this->assertSame('An toàn & an ninh', $data['spec_notes'][0]['label']);
        $this->assertSame('https://cars.example/xe-dien-vf-7', $data['canonical']);
        $this->assertSame('https://cars.example/xe-dien-vf-7', $data['jsonld']['url']);
        $this->assertStringEndsWith('/storage/catalog/seo/vf-7.webp', $data['jsonld']['image']);
    }

    public function test_ban_nhap_tra_404(): void
    {
        $this->product(['slug' => 'con-nhap', 'status' => 'draft']);

        $this->getJson('/api/v1/products/con-nhap')->assertNotFound();
    }

    public function test_loc_theo_danh_muc_va_tim_kiem(): void
    {
        $suv = Category::create(['name' => 'SUV']);
        $this->product(['name' => 'GX 550', 'category_id' => $suv->id]);
        $this->product(['name' => 'ES 300h']);

        $this->getJson('/api/v1/products?category=suv')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'GX 550');

        $this->getJson('/api/v1/products?q=ES')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'ES 300h');
    }

    public function test_slug_tu_sinh_va_khong_trung(): void
    {
        $a = $this->product(['name' => 'Lexus GX 550']);
        $b = $this->product(['name' => 'Lexus GX 550']);

        $this->assertSame('lexus-gx-550', $a->slug);
        $this->assertSame('lexus-gx-550-2', $b->slug);
    }
}

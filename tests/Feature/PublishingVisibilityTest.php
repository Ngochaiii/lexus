<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use Tests\TestCase;

class PublishingVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://cars.example',
            'catalog.seo.sitemap_includes' => ['product', 'post'],
        ]);
    }

    public function test_xe_va_tin_hen_gio_khong_lo_ra_frontend_api_hay_sitemap(): void
    {
        Product::create([
            'name' => 'Xe đang hẹn giờ',
            'slug' => 'xe-dang-hen-gio',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);
        Post::create([
            'title' => 'Tin đang hẹn giờ',
            'slug' => 'tin-dang-hen-gio',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get('/san-pham/xe-dang-hen-gio')->assertNotFound();
        $this->get('/tin-tuc/tin-dang-hen-gio')->assertNotFound();

        $this->get('/san-pham')->assertOk()->assertDontSee('Xe đang hẹn giờ');
        $this->get('/tin-tuc')->assertOk()->assertDontSee('Tin đang hẹn giờ');

        $this->getJson('/api/v1/products/xe-dang-hen-gio')->assertNotFound();
        $this->getJson('/api/v1/posts/tin-dang-hen-gio')->assertNotFound();
        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(0, 'data');

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('xe-dang-hen-gio', $xml);
        $this->assertStringNotContainsString('tin-dang-hen-gio', $xml);
    }

    public function test_xe_va_tin_da_toi_gio_xuat_hien_dong_nhat_o_moi_kenh(): void
    {
        Product::create([
            'name' => 'Xe đã phát hành',
            'slug' => 'xe-da-phat-hanh',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);
        Post::create([
            'title' => 'Tin đã phát hành',
            'slug' => 'tin-da-phat-hanh',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/san-pham/xe-da-phat-hanh')->assertOk();
        $this->get('/tin-tuc/tin-da-phat-hanh')->assertOk();
        $this->getJson('/api/v1/products/xe-da-phat-hanh')->assertOk();
        $this->getJson('/api/v1/posts/tin-da-phat-hanh')->assertOk();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('https://cars.example/san-pham/xe-da-phat-hanh', $xml);
        $this->assertStringContainsString('https://cars.example/tin-tuc/tin-da-phat-hanh', $xml);
    }
}

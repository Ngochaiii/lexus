<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleRedirects;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Support\JsonLd;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Url helper đọc config('app.url') lúc gọi nên đặt runtime là đủ.
        config(['app.url' => 'https://lexus.vn']);
    }

    // --- Redirect 301 tự động khi đổi slug ---

    public function test_doi_slug_ban_da_publish_thi_tao_redirect_301(): void
    {
        $product = Product::create(['name' => 'Lexus GX 460', 'slug' => 'gx-460', 'status' => 'published']);

        $product->update(['slug' => 'gx-550']);

        $redirect = Redirect::sole();
        $this->assertSame('/san-pham/gx-460', $redirect->from_path);
        $this->assertSame('/san-pham/gx-550', $redirect->to_path);
        $this->assertSame(301, $redirect->status_code);
    }

    public function test_doi_slug_ban_nhap_thi_khong_tao_redirect(): void
    {
        $product = Product::create(['name' => 'Lexus GX 460', 'slug' => 'gx-460', 'status' => 'draft']);

        $product->update(['slug' => 'gx-550']);

        $this->assertSame(0, Redirect::count());
    }

    public function test_doi_slug_hai_lan_khong_tao_chuoi_301_noi_tiep(): void
    {
        $product = Product::create(['name' => 'GX', 'slug' => 'v1', 'status' => 'published']);

        $product->update(['slug' => 'v2']);
        $product->update(['slug' => 'v3']);

        // Luật cũ /v1 phải trỏ THẲNG tới /v3, không phải /v2
        $this->assertSame('/san-pham/v3', Redirect::firstWhere('from_path', '/san-pham/v1')->to_path);
        $this->assertSame('/san-pham/v3', Redirect::firstWhere('from_path', '/san-pham/v2')->to_path);
    }

    public function test_slug_bai_viet_dung_tien_to_rieng(): void
    {
        $post = Post::create(['title' => 'Tin cũ', 'slug' => 'tin-cu', 'status' => 'published']);

        $post->update(['slug' => 'tin-moi']);

        $this->assertSame('/tin-tuc/tin-cu', Redirect::sole()->from_path);
    }

    // --- Middleware bắt redirect ---

    public function test_middleware_tra_301_va_dem_hits(): void
    {
        $redirect = Redirect::create(['from_path' => '/cu', 'to_path' => '/moi', 'status_code' => 301]);

        $request = Request::create('/cu', 'GET');
        $response = (new HandleRedirects)->handle($request, fn () => response('không nên tới đây'));

        $this->assertSame(301, $response->getStatusCode());
        // redirect() sinh Location tuyệt đối — đúng chuẩn HTTP
        $this->assertStringEndsWith('/moi', $response->headers->get('Location'));
        $this->assertSame(1, $redirect->refresh()->hits);
    }

    public function test_middleware_giu_query_string(): void
    {
        Redirect::create(['from_path' => '/cu', 'to_path' => '/moi', 'status_code' => 301]);

        $request = Request::create('/cu', 'GET', ['utm_source' => 'fb']);
        $response = (new HandleRedirects)->handle($request, fn () => response('x'));

        $this->assertStringEndsWith('/moi?utm_source=fb', $response->headers->get('Location'));
    }

    public function test_middleware_410_cho_trang_da_go(): void
    {
        Redirect::create(['from_path' => '/da-go', 'to_path' => '/', 'status_code' => 410]);

        $request = Request::create('/da-go', 'GET');
        $response = (new HandleRedirects)->handle($request, fn () => response('x'));

        $this->assertSame(410, $response->getStatusCode());
    }

    public function test_middleware_bo_qua_duong_dan_khong_co_luat(): void
    {
        $request = Request::create('/binh-thuong', 'GET');
        $response = (new HandleRedirects)->handle($request, fn () => response('đi tiếp', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('đi tiếp', $response->getContent());
    }

    // --- Sitemap ---

    public function test_sitemap_liet_ke_ban_da_publish(): void
    {
        Product::create(['name' => 'Đã đăng', 'slug' => 'da-dang', 'status' => 'published']);
        Product::create(['name' => 'Còn nháp', 'slug' => 'con-nhap', 'status' => 'draft']);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=300, public, s-maxage=3600, stale-while-revalidate=86400');

        $this->assertFalse($response->headers->has('Set-Cookie'));
        $xml = $response->getContent();

        $this->assertStringContainsString('<loc>https://lexus.vn/</loc>', $xml);
        $this->assertStringContainsString('<loc>https://lexus.vn/san-pham</loc>', $xml);
        $this->assertStringContainsString('https://lexus.vn/san-pham/da-dang', $xml);
        $this->assertStringNotContainsString('con-nhap', $xml);
    }


    public function test_sitemap_la_xml_hop_le(): void
    {
        Product::create(['name' => 'GX', 'slug' => 'gx', 'status' => 'published']);

        $xml = $this->get('/sitemap.xml')->getContent();

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'sitemap.xml phải parse được');
        $this->assertSame('urlset', $doc->getName());
    }

    // --- JSON-LD ---

    public function test_jsonld_san_pham_co_gia_thanh_offer(): void
    {
        $product = Product::create([
            'name' => 'Lexus GX 550', 'slug' => 'gx-550', 'status' => 'published',
            'price_from' => 5_990_000_000, 'tagline' => 'Bản lĩnh chinh phục',
            'hero' => ['type' => 'image', 'src' => 'hero.webp'],
        ]);

        $ld = JsonLd::forProduct($product);

        $this->assertSame('Product', $ld['@type']);
        $this->assertSame('https://lexus.vn/san-pham/gx-550', $ld['url']);
        $this->assertSame('https://lexus.vn/storage/hero.webp', $ld['image']);
        $this->assertSame('5990000000.00', $ld['offers']['price']);
        $this->assertSame('VND', $ld['offers']['priceCurrency']);
    }

    public function test_jsonld_co_trong_response_chi_tiet_san_pham(): void
    {
        Product::create(['name' => 'GX 550', 'slug' => 'gx-550', 'status' => 'published', 'price_from' => 100]);

        $this->getJson('/api/v1/products/gx-550')
            ->assertOk()
            ->assertJsonPath('data.jsonld.@type', 'Product')
            ->assertJsonPath('data.canonical', 'https://lexus.vn/san-pham/gx-550');
    }

    public function test_metadata_chia_se_trang_xe_lay_dung_noi_dung_san_pham(): void
    {
        Product::create([
            'name' => 'Lexus RZ 450e',
            'slug' => 'rz-450e',
            'status' => 'published',
            'tagline' => 'Thuần điện, thuần cảm xúc',
            'hero' => ['type' => 'image', 'src' => 'catalog/rz-450e.webp'],
            'seo' => [
                'title' => 'Lexus RZ 450e thuần điện',
                'description' => 'Khám phá mẫu SUV điện Lexus RZ 450e.',
            ],
        ]);

        $this->get('/san-pham/rz-450e')
            ->assertOk()
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:title" content="Lexus RZ 450e thuần điện">', false)
            ->assertSee('<meta property="og:description" content="Khám phá mẫu SUV điện Lexus RZ 450e.">', false)
            ->assertSee('<meta property="og:url" content="https://lexus.vn/san-pham/rz-450e">', false)
            ->assertSee('<meta property="og:image" content="https://lexus.vn/storage/catalog/rz-450e.webp">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:title" content="Lexus RZ 450e thuần điện">', false)
            ->assertSee('<meta name="twitter:description" content="Khám phá mẫu SUV điện Lexus RZ 450e.">', false)
            ->assertSee('<meta name="twitter:image" content="https://lexus.vn/storage/catalog/rz-450e.webp">', false);
    }

    public function test_jsonld_organization_lay_tu_settings(): void
    {
        Setting::put('site_name', 'Lexus Việt Nam');
        Setting::put('facebook', 'https://facebook.com/lexus');

        $org = JsonLd::organization();

        $this->assertSame('Lexus Việt Nam', $org['name']);
        $this->assertSame('AutoDealer', $org['@type']);
        $this->assertContains('https://facebook.com/lexus', $org['sameAs']);
    }

    public function test_trang_chu_co_mot_h1_og_image_jsonld_va_robots(): void
    {
        Setting::put('site_name', 'Lexus Việt Nam');
        Setting::put('social_image', 'catalog/seo/social.webp');
        Product::create(['name' => 'Lexus RZ', 'status' => 'published']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('name="robots" content="index,follow,max-image-preview:large"', $html);
        $this->assertStringContainsString('property="og:image" content="https://lexus.vn/storage/catalog/seo/social.webp"', $html);
        $this->assertStringContainsString('"@type":"AutoDealer"', $html);
    }

    public function test_canonical_phan_trang_tu_tro(): void
    {
        config(['catalog.frontend.per_page' => 1]);
        Product::create(['name' => 'Xe A', 'status' => 'published']);
        Product::create(['name' => 'Xe B', 'status' => 'published']);

        $this->get('/san-pham?page=2')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://lexus.vn/san-pham?page=2">', false);

        // Trang tìm kiếm và so sánh đã gỡ khỏi bản này — xem README.
    }
}

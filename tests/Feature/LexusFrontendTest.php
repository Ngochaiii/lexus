<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Form;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Giao diện Lexus — kiểm phần mà FrontendTest cũ không kiểm được nữa vì nó
 * khẳng định markup của bản VinFast.
 *
 * Trọng tâm là HÀNH VI: nội dung nhập trong admin ra đúng trang khách xem,
 * mỗi xe ra đúng nội dung của nó, và form lái thử lưu được lead.
 */
class LexusFrontendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'Lexus Thăng Long']);
    }

    private function product(string $name, string $slug, ?Category $category = null): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category?->id,
            'tagline' => $name.' — dòng mô tả',
        ]);
    }

    public function test_moi_dong_xe_ra_dung_noi_dung_cua_no(): void
    {
        $this->product('Lexus RX', 'rx');
        $this->product('Lexus ES', 'es');

        // Lỗi kinh điển khi view còn dữ liệu tĩnh: mở xe nào cũng ra RX.
        $this->get('/san-pham/es')
            ->assertOk()
            ->assertSee('LEXUS ES')
            ->assertDontSee('LEXUS RX');
    }

    public function test_danh_muc_chi_hien_xe_cua_no_trong_luoi(): void
    {
        $suv = Category::create(['name' => 'SUV', 'slug' => 'suv']);
        $sedan = Category::create(['name' => 'Sedan', 'slug' => 'sedan']);

        $this->product('Lexus LX', 'lx', $suv);
        $this->product('Lexus ES', 'es', $sedan);

        $html = $this->get('/danh-muc/suv')->assertOk()->getContent();

        // Chỉ soi phần lưới: mega menu và footer cố ý liệt kê mọi dòng xe,
        // nên assertDontSee trên cả trang sẽ sai.
        preg_match('/<div class="model-grid">(.*?)<\/div>\s*@?\s*<\/div>/s', $html, $m);
        $grid = $m[1] ?? $html;

        $this->assertStringContainsString('Lexus LX', $grid);
        $this->assertStringNotContainsString('Lexus ES', $grid);
    }

    public function test_danh_sach_xe_phan_trang_va_canonical_tu_tro(): void
    {
        config(['catalog.frontend.per_page' => 1]);

        $this->product('Lexus RX', 'rx');
        $this->product('Lexus ES', 'es');

        $this->get('/san-pham?page=2')
            ->assertOk()
            ->assertSee('page=2')
            ->assertSee('rel="canonical"', false);
    }

    public function test_trang_xe_co_metadata_va_jsonld_tu_ban_ghi(): void
    {
        Product::create([
            'name' => 'Lexus NX',
            'slug' => 'nx',
            'status' => 'published',
            'published_at' => now(),
            'seo' => ['title' => 'Lexus NX — giá và thông số', 'description' => 'Mô tả riêng của NX.'],
        ]);

        $this->get('/san-pham/nx')
            ->assertOk()
            ->assertSee('<title>Lexus NX — giá và thông số</title>', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:description" content="Mô tả riêng của NX.">', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_bai_viet_ra_dung_noi_dung_va_og_article(): void
    {
        Post::create([
            'title' => 'Sự tinh tế trong từng chi tiết',
            'slug' => 'su-tinh-te',
            'status' => 'published',
            'published_at' => now(),
            'excerpt' => 'Một góc nhìn về nghệ thuật chế tác.',
            'sections' => [['type' => 'text', 'title' => 'Mở đầu', 'body' => 'Đoạn thân bài.']],
        ]);

        $this->get('/tin-tuc/su-tinh-te')
            ->assertOk()
            ->assertSee('Sự tinh tế trong từng chi tiết')
            ->assertSee('Đoạn thân bài.')
            ->assertSee('<meta property="og:type" content="article">', false);
    }

    public function test_form_lai_thu_hien_du_o_va_luu_duoc_lead(): void
    {
        $this->product('Lexus RX', 'rx');

        $form = Form::create(['key' => 'dang-ky-lai-thu', 'name' => 'Đăng ký lái thử']);
        $form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
            ['key' => 'product', 'label' => 'Dòng xe quan tâm', 'type' => 'text', 'rules' => ['required'], 'sort' => 3],
        ]);

        $this->get('/dang-ky-lai-thu')
            ->assertOk()
            ->assertSee('Họ và tên')
            ->assertSee('Số điện thoại')
            ->assertSee('Lexus RX')                    // xe đổ vào ô select
            ->assertSee('name="_token"', false)        // CSRF
            ->assertSee('class="honeypot"', false);    // ô bẫy bot

        $this->post('/gui-form/dang-ky-lai-thu', [
            'name' => 'Nguyễn Thử Nghiệm',
            'phone' => '0912345678',
            'product' => 'Lexus RX',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'form_id' => $form->id,
            'name' => 'Nguyễn Thử Nghiệm',
            'phone' => '0912345678',
        ]);
    }

    public function test_bot_dien_o_bay_thi_khong_tao_lead(): void
    {
        $form = Form::create(['key' => 'dang-ky-lai-thu', 'name' => 'Đăng ký lái thử']);
        $form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
        ]);

        $this->post('/gui-form/dang-ky-lai-thu', [
            'name' => 'Bot',
            'phone' => '0911111111',
            'website' => 'http://spam.example',   // ô bẫy
        ])->assertRedirect();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_header_va_footer_lay_thong_tin_tu_cai_dat(): void
    {
        Setting::updateOrCreate(['key' => 'advisor_name'], ['value' => 'Thu Hà']);
        Setting::updateOrCreate(['key' => 'advisor_phone'], ['value' => '0989345989']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Thu Hà')
            ->assertSee('0989 345 989');   // Phone::format, không phải chuỗi thô
    }

    public function test_muc_cua_xe_render_theo_layout_cua_ban_thiet_ke(): void
    {
        Product::create([
            'name' => 'Lexus LM',
            'slug' => 'lm',
            'status' => 'published',
            'published_at' => now(),
            'sections' => [
                ['type' => 'media', 'title' => 'Ngoại thất', 'layout' => 'split',
                    'items' => [['image' => 'catalog/lm/ngoai-that.jpg', 'label' => 'Góc trước']]],
                ['type' => 'media', 'title' => 'Thư viện', 'layout' => 'gallery',
                    'items' => [['image' => 'catalog/lm/1.jpg'], ['image' => 'catalog/lm/2.jpg']]],
            ],
        ]);

        $this->get('/san-pham/lm')
            ->assertOk()
            ->assertSee('class="split', false)          // layout split
            ->assertSee('class="gallery-grid"', false)  // layout gallery
            ->assertSee('class="lightbox"', false)      // phóng ảnh bằng :target
            ->assertSee('NGOẠI THẤT');                  // mb_strtoupper giữ dấu
    }

    public function test_khoi_giua_trang_chu_lay_tu_trang_tinh_sua_duoc_trong_admin(): void
    {
        Page::create([
            'slug'   => 'trang-chu',
            'title'  => 'Trang chủ — khối nội dung',
            'status' => 'published',
            'sections' => [
                [
                    'type' => 'media', 'layout' => 'bleed',
                    'title' => 'Chữ biên tập viên tự đặt',
                    'intro' => 'Tiêu đề do admin nhập',
                    'body'  => 'Đoạn mô tả do admin nhập.',
                    'cta_label' => 'Bấm vào đây', 'cta_url' => '/the-gioi-lexus',
                    'items' => [['image' => 'catalog/trang-chu/story.webp']],
                ],
                [
                    'type' => 'media', 'layout' => 'shortcuts',
                    'title' => 'Lối tắt', 'intro' => 'Bốn ô bấm',
                    'items' => [
                        ['label' => 'Ô thứ nhất', 'desc' => 'Mô tả ô 1', 'url' => '/bang-gia'],
                        ['label' => 'Ô thứ hai',  'desc' => 'Mô tả ô 2', 'url' => '/tai-chinh'],
                    ],
                ],
            ],
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // Chữ của admin ra đúng trang chủ…
        $this->assertStringContainsString('Tiêu đề do admin nhập', $html);
        $this->assertStringContainsString('Đoạn mô tả do admin nhập.', $html);
        $this->assertStringContainsString('Bấm vào đây', $html);

        // …và lưới ô bấm dùng đúng khối của bản thiết kế, mỗi ô một link.
        $this->assertStringContainsString('class="shortcut-grid"', $html);
        $this->assertStringContainsString('Ô thứ nhất', $html);
        $this->assertStringContainsString('href="/tai-chinh"', $html);

        // Trang chủ KHÔNG đánh số 01/ 02/ trên eyebrow như trang chi tiết xe.
        $this->assertStringNotContainsString('01 / CHỮ BIÊN TẬP VIÊN TỰ ĐẶT', $html);
    }

    public function test_chua_tao_trang_chu_thi_phan_giua_rong_nhung_trang_van_chay(): void
    {
        $this->product('Lexus RX', 'rx');

        $this->get('/')
            ->assertOk()
            ->assertSee('Lexus RX')          // khối bộ sưu tập vẫn chạy
            ->assertSee('Chạm đến trải nghiệm Lexus.');   // băng chuyển đổi vẫn chạy
    }

    public function test_banner_sua_trong_admin_ra_dung_hero_trang_chu(): void
    {
        Banner::create([
            'eyebrow'   => 'Dòng nhỏ do admin nhập',
            'title'     => "Tiêu đề dòng một\nTiêu đề dòng hai",
            'subtitle'  => 'Mô tả banner do admin nhập.',
            'image'     => 'catalog/banners/anh-moi.webp',
            'cta_label' => 'Nút do admin đặt',
            'cta_url'   => '/bang-gia',
            'is_active' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('DÒNG NHỎ DO ADMIN NHẬP', mb_strtoupper($html));
        $this->assertStringContainsString('Tiêu đề dòng một', $html);
        $this->assertStringContainsString('Mô tả banner do admin nhập.', $html);
        $this->assertStringContainsString('Nút do admin đặt', $html);
        $this->assertStringContainsString('catalog/banners/anh-moi.webp', $html);

        // Xuống dòng trong tiêu đề giữ nguyên chỗ ngắt của người nhập.
        $this->assertMatchesRegularExpression('/Tiêu đề dòng một\s*<br\s*\/?>/u', $html);
    }

    public function test_chua_co_banner_thi_hero_lui_ve_ban_thiet_ke(): void
    {
        $this->assertDatabaseCount('banners', 0);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dấu ấn riêng.')                 // chữ của bản thiết kế
            // Ảnh thật khu trưng bày của đại lý, không phải ảnh demo của template.
            ->assertSee('assets/co-so/khu-trung-bay.webp', false)
            ->assertDontSee('assets/hero.webp', false)
            ->assertDontSee('0 DÒNG XE', false);
    }

    public function test_banner_tat_hoac_het_han_thi_khong_hien(): void
    {
        Banner::create([
            'title'     => 'Banner đã tắt',
            'image'     => 'catalog/banners/tat.webp',
            'is_active' => false,
        ]);

        Banner::create([
            'title'     => 'Banner hết hạn',
            'image'     => 'catalog/banners/het-han.webp',
            'is_active' => true,
            'ends_at'   => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Banner đã tắt')
            ->assertDontSee('Banner hết hạn')
            ->assertSee('Dấu ấn riêng.');   // lùi về bản thiết kế
    }
}

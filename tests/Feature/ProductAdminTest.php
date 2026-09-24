<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\Template;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'x']));
    }

    public function test_danh_sach_render_va_hien_ban_ghi(): void
    {
        $product = Product::create(['name' => 'Lexus GX 550', 'status' => 'published']);

        Livewire::test(ListProducts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$product]);
    }

    public function test_cot_so_muc_dem_dung_so_section(): void
    {
        Product::create([
            'name' => 'Lexus GX 550',
            'sections' => [
                ['title' => 'Thư viện', 'items' => []],
                ['title' => 'Mâm xe', 'items' => []],
            ],
        ]);

        // Cột json dễ bị Filament tách thành nhiều badge — chốt lại bằng test
        Livewire::test(ListProducts::class)
            ->assertSuccessful()
            ->assertSee('2 mục');
    }

    public function test_tao_san_pham_kem_sections_va_specs(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Lexus LX 600',
                'slug' => 'lexus-lx-600',
                'status' => 'draft',
                'sections' => [[
                    'title' => 'Thư viện',
                    'type' => 'media',
                    'layout' => 'slider',
                    // state của FileUpload là mảng đường dẫn, không phải chuỗi
                    'items' => [['image' => ['gallery-01.webp'], 'label' => 'Ngoại thất', 'desc' => '']],
                ]],
                'specs' => [['group' => 'Động cơ', 'rows' => [['label' => 'Dung tích', 'value' => '3.4L']]]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::firstWhere('slug', 'lexus-lx-600');

        $this->assertNotNull($product);
        $this->assertSame('Thư viện', $product->sections[0]['title']);
        $this->assertSame('gallery-01.webp', $product->sections[0]['items'][0]['image']);
        $this->assertSame('Động cơ', $product->specs[0]['group']);
    }

    public function test_luu_duoc_khi_khong_them_phien_ban_hay_mau(): void
    {
        // Repeater mặc định tạo sẵn 1 item rỗng sẽ chặn lưu vì `required`
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Bản tối giản', 'slug' => 'ban-toi-gian', 'status' => 'draft'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNotNull(Product::firstWhere('slug', 'ban-toi-gian'));
    }

    public function test_form_sua_nap_dung_du_lieu_da_luu(): void
    {
        $product = Product::create([
            'name' => 'Lexus GX 550',
            'tagline' => 'Bản lĩnh chinh phục',
            'status' => 'published',
            'highlights' => [['value' => '349', 'unit' => 'mã lực', 'label' => 'Công suất']],
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'name' => 'Lexus GX 550',
                'tagline' => 'Bản lĩnh chinh phục',
                'status' => 'published',
            ]);
    }

    public function test_nhan_ban_copy_nguyen_sections_specs_va_phien_ban(): void
    {
        $product = Product::create([
            'name' => 'Lexus GX 550',
            'status' => 'published',
            'sections' => [['title' => 'Mâm xe', 'type' => 'media', 'layout' => 'cols-2', 'items' => []]],
            'specs' => [['group' => 'Động cơ', 'rows' => []]],
        ]);
        $product->variants()->create([
            'name' => 'Luxury',
            'price' => 100,
            'battery_kwh' => 75.3,
            'range_km' => 496,
            'is_default' => true,
        ]);
        $product->options()->create(['name' => 'Caviar Black']);

        $copy = $product->duplicate('Lexus GX 550 (2026)');

        $this->assertSame('lexus-gx-550-2026', $copy->slug);
        $this->assertSame('draft', $copy->status, 'Bản sao phải là nháp, không tự lên sóng.');
        $this->assertNull($copy->published_at);
        $this->assertSame($product->sections, $copy->sections);
        $this->assertSame($product->specs, $copy->specs);
        $this->assertSame(['Luxury'], $copy->variants->pluck('name')->all());
        $this->assertSame('75.30', $copy->variants->first()->battery_kwh);
        $this->assertSame(496, $copy->variants->first()->range_km);
        $this->assertTrue($copy->variants->first()->is_default);
        $this->assertSame(['Caviar Black'], $copy->options->pluck('name')->all());
    }

    public function test_moi_xe_chi_co_mot_phien_ban_mac_dinh(): void
    {
        $product = Product::create([
            'name' => 'VF 7',
            'status' => 'draft',
        ]);

        $base = $product->variants()->create([
            'name' => 'Base',
            'is_default' => true,
        ]);
        $plus = $product->variants()->create([
            'name' => 'Plus',
            'is_default' => true,
        ]);

        $this->assertFalse($base->refresh()->is_default);
        $this->assertTrue($plus->refresh()->is_default);
        $this->assertSame(1, $product->variants()->where('is_default', true)->count());
    }

    public function test_mau_bo_cuc_giu_khung_muc_nhung_xoa_noi_dung(): void
    {
        $template = Template::create([
            'name' => 'Bố cục Lexus',
            'entity_type' => 'product',
            'payload' => [
                'sections' => [[
                    'title' => 'Ngoại thất',
                    'intro' => 'Thiết kế lấy cảm hứng từ du thuyền.',
                    'type' => 'media',
                    'layout' => 'cols-3',
                    'items' => [['image' => 'a.webp', 'label' => 'Zenith Grey']],
                ]],
            ],
        ]);

        $blank = $template->blankSections();

        $this->assertSame('Ngoại thất', $blank[0]['title']);
        $this->assertSame('cols-3', $blank[0]['layout']);
        $this->assertSame('', $blank[0]['intro'], 'Mẫu giữ khung, không giữ chữ của sản phẩm cũ.');
        $this->assertSame([], $blank[0]['items'], 'Mẫu không mang theo ảnh.');
    }

    /* Ảnh banner hero nằm trong cột JSON `hero`, lưu qua đường dẫn chấm của
       Filament — dễ rơi mất khi form đổi. Chốt lại đường đi từ form xuống DB. */
    public function test_luu_duoc_anh_banner_hero_qua_form(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Lexus RX 350',
                'slug' => 'lexus-rx-350',
                'status' => 'draft',
                'hero' => [
                    'type' => 'image',
                    'src' => ['catalog/hero/rx.jpg'],
                    'banners' => [
                        ['image' => ['catalog/hero/banner-1.jpg'], 'label' => 'Ưu đãi tháng 9'],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $hero = Product::where('slug', 'lexus-rx-350')->firstOrFail()->hero;

        $this->assertSame('catalog/hero/banner-1.jpg', data_get($hero, 'banners.0.image'));
        $this->assertSame('Ưu đãi tháng 9', data_get($hero, 'banners.0.label'));
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Forms\Components\NativeMediaUpload;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Media\ImageVariantBuilder;
use App\Media\MediaStore;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMediaAndPriceTest extends TestCase
{
    public function test_cau_hinh_toi_uu_anh_phia_trinh_duyet_duoc_gioi_han_an_toan(): void
    {
        config([
            'media.client_image_max_dimension' => 1920,
            'media.client_image_quality' => 82,
        ]);

        $upload = NativeMediaUpload::make('banner')->image();

        $this->assertSame(1920, $upload->getClientImageMaxDimension());
        $this->assertSame(0.82, $upload->getClientImageQuality());

        config([
            'media.client_image_max_dimension' => 100,
            'media.client_image_quality' => 10,
        ]);

        $this->assertSame(400, $upload->getClientImageMaxDimension());
        $this->assertSame(0.4, $upload->getClientImageQuality());
    }

    public function test_admin_upload_anh_khong_can_fileinfo_va_chi_luu_relative_path(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'media@test.local',
            'password' => 'x',
        ]));

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL4WQAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('anh-nguy-hiem.php', $png);

        $data = $this->post('/admin/media', [
            'file' => $file,
            'directory' => 'catalog/hero',
            'kind' => 'image',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'image/png')
            ->json('data');

        $this->assertMatchesRegularExpression('~^catalog/hero/[0-9A-Z]{26}\.png$~', $data['path']);
        $this->assertSame('/storage/'.$data['path'], $data['url']);
        $this->assertTrue(app(MediaStore::class)->exists($data['path']));
        $this->assertStringNotContainsString('.php', $data['path']);
    }

    public function test_admin_upload_anh_tu_tao_bien_the_responsive_va_manifest(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'responsive-media@test.local',
            'password' => 'x',
        ]));

        $data = $this->post('/admin/media', [
            'file' => UploadedFile::fake()->image('banner.jpg', 1000, 500),
            'directory' => 'catalog/hero',
            'kind' => 'image',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.responsive_widths', [400, 800])
            ->json('data');

        $media = app(MediaStore::class);

        foreach ([400, 800] as $width) {
            $this->assertTrue($media->exists(ImageVariantBuilder::variantPath($data['path'], $width)));
        }

        $manifest = json_decode((string) $media->read(ImageVariantBuilder::MANIFEST), true);

        $this->assertSame([
            'w' => 1000,
            'h' => 500,
            'v' => [400, 800],
        ], $manifest[$data['path']]);

        $second = $this->post('/admin/media', [
            'file' => UploadedFile::fake()->image('banner-2.jpg', 600, 300),
            'directory' => 'catalog/hero',
            'kind' => 'image',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.responsive_widths', [400])
            ->json('data');

        $manifest = json_decode((string) $media->read(ImageVariantBuilder::MANIFEST), true);

        $this->assertArrayHasKey($data['path'], $manifest);
        $this->assertSame([
            'w' => 600,
            'h' => 300,
            'v' => [400],
        ], $manifest[$second['path']]);
    }

    public function test_admin_upload_tu_choi_php_gia_anh_va_thu_muc_la(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'security@test.local',
            'password' => 'x',
        ]));

        $php = UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["x"]);');

        $this->post('/admin/media', [
            'file' => $php,
            'directory' => 'catalog/hero',
            'kind' => 'image',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL4WQAAAABJRU5ErkJggg==');

        $this->post('/admin/media', [
            'file' => UploadedFile::fake()->createWithContent('ok.png', $png),
            'directory' => '../../public',
            'kind' => 'image',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertSame([], app(MediaStore::class)->allFiles());
    }

    public function test_admin_upload_pdf_bang_chu_ky_file_khong_can_fileinfo(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'pdf@test.local',
            'password' => 'x',
        ]));

        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";

        $data = $this->post('/admin/media', [
            'file' => UploadedFile::fake()->createWithContent('bang-gia.exe', $pdf),
            'directory' => 'catalog/tai-lieu',
            'kind' => 'pdf',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'application/pdf')
            ->json('data');

        $this->assertMatchesRegularExpression('~^catalog/tai-lieu/[0-9A-Z]{26}\.pdf$~', $data['path']);
        $this->assertTrue(app(MediaStore::class)->exists($data['path']));
    }

    public function test_khach_chua_dang_nhap_khong_duoc_upload(): void
    {
        $this->post('/admin/media', [], ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_mo_va_luu_lai_xe_khong_lam_gia_nhan_mot_tram(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => 'x',
        ]));

        $product = Product::create([
            'name' => 'VF 8',
            'slug' => 'vf-8',
            'status' => 'draft',
            'price_from' => 853_000_000,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['price_from' => '853.000.000'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('853000000.00', $product->refresh()->price_from);
    }

    public function test_edit_xe_van_giu_duoc_placeholder_svg_cu(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'legacy-svg@test.local',
            'password' => 'x',
        ]));

        $product = Product::create([
            'name' => 'Xe có ảnh cũ',
            'slug' => 'xe-co-anh-cu',
            'status' => 'draft',
            'hero' => ['type' => 'image', 'src' => 'catalog/hero/placeholder.svg'],
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['hero.src' => 'catalog/hero/placeholder.svg'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('catalog/hero/placeholder.svg', data_get($product->refresh()->hero, 'src'));
    }
}

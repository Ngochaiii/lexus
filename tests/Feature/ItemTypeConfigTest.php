<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bước 7 của tài liệu kiến trúc — bài kiểm tra thật:
 *
 *   "nếu dựng được một trang nội thất mà không chạy
 *    `php artisan make:migration` lần nào, kiến trúc đã đạt."
 *
 * Chạy trên CÙNG schema với mảng ô tô. Khác biệt duy nhất là mấy dòng
 * config() bên dưới — đúng thứ mỗi hãng đổi mà không đụng database.
 */
class ItemTypeConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // config/catalog.php của một dự án nội thất — không gì thuộc về ô tô
        config([
            'catalog.labels.product' => ['single' => 'Sản phẩm', 'plural' => 'Sản phẩm'],
            'catalog.labels.variant' => ['single' => 'Kích thước', 'plural' => 'Kích thước'],
            'catalog.labels.option'  => ['single' => 'Chất liệu', 'plural' => 'Chất liệu'],
            'catalog.labels.sections' => 'Chi tiết sản phẩm',
            'catalog.section_presets' => ['Thư viện', 'Chi tiết gỗ', 'Hoàn thiện', 'Lắp đặt'],
        ]);
    }

    public function test_dung_duoc_mat_hang_noi_that_ma_khong_them_migration_nao(): void
    {
        $before = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();

        $ban = Product::create([
            'name'         => 'Bàn ăn gỗ óc chó',
            'status'       => 'published',
            'published_at' => now(),
            'price_from'   => 42_000_000,
            'highlights'   => [['value' => 'Gỗ óc chó', 'unit' => '', 'label' => 'Chất liệu mặt']],
            'sections'     => [[
                'title' => 'Chi tiết gỗ', 'intro' => 'Vân gỗ tự nhiên.',
                'type' => 'media', 'layout' => 'cols-2',
                'items' => [['image' => 'van-go.webp', 'label' => 'Vân dọc', 'desc' => '']],
            ]],
            'specs' => [['group' => 'Kích thước', 'rows' => [['label' => 'Dài', 'value' => '1.600 mm']]]],
        ]);

        // "Phiên bản" giờ là kích thước, "Màu xe" giờ là chất liệu — cùng bảng
        $ban->variants()->create(['name' => 'Bàn 1m6', 'price' => 42_000_000, 'is_default' => true]);
        $ban->options()->create(['name' => 'Gỗ óc chó']);

        $after = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();

        $this->assertSame(
            $before,
            $after,
            'Đổi mặt hàng mà phải chạy thêm migration nghĩa là kiến trúc chưa đạt.',
        );

        $data = $this->getJson('/api/v1/products/ban-an-go-oc-cho')->assertOk()->json('data');
        $this->assertSame(['Bàn 1m6'], array_column($data['variants'], 'name'));
        $this->assertSame(['Gỗ óc chó'], array_column($data['options'], 'name'));
    }

    public function test_admin_doi_chu_theo_config_chu_khong_theo_ten_cot(): void
    {
        $this->assertSame('Sản phẩm', ProductResource::getPluralModelLabel());
        $this->assertSame('Kích thước', Catalog::label('variant.single'));
        $this->assertSame('Chất liệu', Catalog::label('option.plural'));
    }

    public function test_goi_y_ten_muc_la_cua_nganh_noi_that(): void
    {
        $this->assertSame(['Thư viện', 'Chi tiết gỗ', 'Hoàn thiện', 'Lắp đặt'], Catalog::sectionPresets());
    }

    public function test_schema_trung_lap_khong_co_bang_rieng_cho_mat_hang(): void
    {
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->all();

        $this->assertContains('products', $tables);
        $this->assertContains('product_variants', $tables);
        $this->assertNotContains('cars', $tables);
        $this->assertNotContains('furniture', $tables);
    }
}

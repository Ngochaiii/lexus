# Mở rộng CMS bán xe — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cấu trúc lại module thêm xe cho khớp layout trang chi tiết (người không biết code vào admin điền form là ra đúng trang thiết kế), rồi bổ sung 6 module: banner trang chủ, popup thu lead, tính trả góp, trang đại lý, tìm kiếm, so sánh xe.

**Architecture:** Bám đúng kiến trúc sẵn có — dữ liệu nằm ở DB (không hardcode trong view), admin dựng bằng Filament resource, frontend là Blade tĩnh đọc từ `config/catalog.php` + Cài đặt. Mọi khối thiếu dữ liệu thì tự ẩn. Form và bộ tính toán dùng request thật (POST/GET), JS chỉ nâng cấp thêm — trừ popup vốn phải cần JS.

**Tech Stack:** Laravel 12 · Filament 4 · Blade + CSS tĩnh (`public/css/frontend.css`) · JS thuần (`public/js/frontend.js`) · MariaDB · PHPUnit

## Global Constraints

- Mọi truy vấn model đi qua `App\Support\Catalog::query('key')`, không gọi thẳng `Product::query()`. Model tra ở `config('catalog.models')`.
- Không hardcode chữ hiển thị vào view. Nội dung sửa được phải nằm ở DB hoặc Cài đặt (`config('catalog.settings')` → màn hình Cài đặt).
- Khoá Cài đặt nào frontend đọc thì PHẢI khai trong `config('catalog.settings')`, nếu không admin sẽ không sửa được.
- Ô trống thì không render: khoá rỗng → cả khối biến mất, không để chữ mẫu chết trong view.
- Không để nút chết: nút chỉ có tác dụng khi có JS thì phải ẩn dưới `html.js` (xem `[data-booking-next]` trong `public/css/frontend.css`).
- Tiền hiển thị: `catalog_money()` cho số đầy đủ, `catalog_money_short()` cho chỗ đọc lướt (hero, thẻ chọn xe).
- Route mới đặt TRƯỚC route trang tĩnh `/{page:slug}` ở cuối `routes/web.php`.
- View dùng `Route::has('ten')` trước khi gọi `route('ten')` — hãng tắt module thì link tự biến mất.
- Comment trong code viết bằng tiếng Việt, giải thích LÝ DO chứ không mô tả lại code.
- Chạy `vendor/bin/pint --dirty` trước mỗi commit.
- Toàn bộ test phải xanh trước khi commit: `php artisan test`.

---

## File Structure

**Task 1 — cấu trúc lại module thêm xe theo layout trang chi tiết**
- Create: `database/migrations/2026_01_01_000012_add_spec_notes_to_products_table.php`
- Modify: `app/Models/Product.php` (cast `spec_notes`)
- Rewrite: `app/Filament/Resources/Products/Schemas/ProductForm.php` (sắp lại mục theo đúng thứ tự trang, bù 4 ô còn thiếu)
- Modify: `resources/views/frontend/partials/hero.blade.php` (bỏ nguồn dự phòng gây lặp chữ)
- Modify: `resources/views/frontend/product.blade.php` (truyền `$notes`)
- Modify: `resources/views/frontend/partials/specs.blade.php` (nhận `$notes`)
- Modify: `database/seeders/Brands/BrandSeeder.php` (ghi cột thật thay vì nhóm `__notes`)
- Test: `tests/Feature/ProductLayoutParityTest.php` (mới — chốt form admin dựng đủ khối thiết kế)

**Task 2 — banner trang chủ**
- Create: migration `banners`, `app/Models/Banner.php`, `app/Filament/Resources/Banners/BannerResource.php`, `app/Filament/Resources/Banners/Pages/ManageBanners.php`
- Modify: `config/catalog.php`, `app/Http/Controllers/Frontend/HomeController.php`, `resources/views/frontend/home.blade.php`
- Test: `tests/Feature/BannerTest.php`

**Task 3 — popup thu lead**
- Create: `resources/views/frontend/partials/popup.blade.php`
- Modify: `config/catalog.php`, `resources/views/frontend/layout.blade.php`, `public/js/frontend.js`, `public/css/frontend.css`
- Test: `tests/Feature/PopupTest.php`

**Task 4 — tính trả góp**
- Create: `app/Support/Loan.php`, `resources/views/frontend/partials/loan-calculator.blade.php`
- Modify: `config/catalog.php`, `app/Http/Controllers/Frontend/ProductController.php`, `resources/views/frontend/product.blade.php`, `public/css/frontend.css`
- Test: `tests/Unit/LoanTest.php`, `tests/Feature/LoanCalculatorTest.php`

**Task 5 — trang đại lý**
- Create: `app/Filament/Resources/Dealers/DealerResource.php` + `Pages/ManageDealers.php`, `app/Filament/Resources/Provinces/ProvinceResource.php` + `Pages/ManageProvinces.php`, `app/Http/Controllers/Frontend/DealerController.php`, `resources/views/frontend/dealers.blade.php`
- Modify: `config/catalog.php`, `routes/web.php`, `resources/views/frontend/partials/footer.blade.php`, `public/css/frontend.css`
- Test: `tests/Feature/DealerPageTest.php`

**Task 6 — tìm kiếm**
- Create: `app/Http/Controllers/Frontend/SearchController.php`, `resources/views/frontend/search.blade.php`
- Modify: `routes/web.php`, `resources/views/frontend/partials/header.blade.php`, `public/css/frontend.css`
- Test: `tests/Feature/SearchTest.php`

**Task 7 — so sánh xe**
- Create: `app/Http/Controllers/Frontend/CompareController.php`, `resources/views/frontend/compare.blade.php`
- Modify: `routes/web.php`, `resources/views/frontend/partials/product-card.blade.php`, `public/css/frontend.css`
- Test: `tests/Feature/CompareTest.php`

**Ghi chú phạm vi:** bảy task độc lập nhau, mỗi task tự chạy và tự test được. Cố ý BỎ theo yêu cầu: phân quyền, tính lăn bánh (bộ phận khác lo), xe sẵn kho (xe nào cũng có sẵn).

---

### Task 1: Cấu trúc lại module thêm xe theo layout trang chi tiết

**Bối cảnh — đo thật, không phải suy đoán.** Dựng một chiếc xe CHỈ bằng form admin rồi soi trang chi tiết, kết quả:

```
[CÓ ] layout-gallery · layout-split · data-gallery · data-tabs
[CÓ ] split--media-first · spec-flat
[THIẾU] spec-notes
Khối trang: 7 — bản thiết kế có 10
Đoạn dẫn hero và đoạn khối mở đầu IN RA CÙNG MỘT CÂU
```

Ba kết luận:

1. **Xương sống đã khớp.** Cả 5 bố cục của thiết kế đều chọn được từ dropdown trong admin. Không phải dựng lại gì.
2. **Hở đúng 4 ô chữ.** `hero.lede`, `hero.intro_title`, `hero.intro_body` và ghi chú thông số — seeder ghi được, form admin không có ô. Sửa xe rồi Lưu là Filament ghi đè cả cột json và **mất sạch** mấy đoạn đó.
3. **Có một lỗi lặp chữ.** Đoạn dẫn hero và đoạn của khối mở đầu cùng lùi về `seo.description`, nên xe nào dựng từ admin cũng in y hệt một câu hai lần cách nhau vài dòng.

Ngoài bù ô, task này **sắp lại thứ tự các mục trong form cho trùng thứ tự trên trang** và đặt tên mục theo thứ nó đẻ ra ngoài frontend. Hiện form xếp theo cấu trúc dữ liệu (Thông tin cơ bản → Chỉ số → Phiên bản → Bảng màu → Chi tiết → Thông số), người nhập không biết ô mình đang gõ rơi vào đâu trên trang. Xếp lại theo đúng mạch trang thì vừa điền vừa hình dung được trang đang thành hình.

**Files:**
- Create: `database/migrations/2026_01_01_000012_add_spec_notes_to_products_table.php`
- Modify: `app/Models/Product.php`
- Rewrite: `app/Filament/Resources/Products/Schemas/ProductForm.php`
- Modify: `resources/views/frontend/partials/hero.blade.php`
- Modify: `resources/views/frontend/product.blade.php`
- Modify: `resources/views/frontend/partials/specs.blade.php`
- Modify: `database/seeders/Brands/BrandSeeder.php`
- Test: `tests/Feature/ProductLayoutParityTest.php`

**Interfaces:**
- Produces: cột `products.spec_notes` json — mảng `[['label' => string, 'body' => string], ...]`
- Produces: `partials/specs.blade.php` nhận thêm biến `$notes` (mảng, mặc định `[]`)
- Produces: quy ước nguồn chữ đầu trang, mỗi câu MỘT nguồn duy nhất:
  - đoạn dẫn hero ← `hero.lede`, không có thì **ẩn hẳn** (không mượn `seo.description`)
  - tiêu đề khối mở đầu ← `hero.intro_title`, không có thì ẩn tiêu đề
  - đoạn khối mở đầu ← `hero.intro_body`, không có thì mượn `seo.description` (chỗ DUY NHẤT được mượn)
- Consumes: không phụ thuộc task nào

---

- [ ] **Step 1: Viết test đối chiếu — đây là điều kiện nghiệm thu của cả task**

Create `tests/Feature/ProductLayoutParityTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Form;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Chốt lời hứa lớn nhất của module thêm xe: một người KHÔNG BIẾT CODE vào
 * admin điền form là ra được trang chi tiết đủ khối như bản thiết kế, không
 * cần ai sửa seeder hộ.
 *
 * Test này cố ý dựng xe hoàn toàn qua form Filament chứ không Product::create
 * — chỉ đường vòng qua form mới bắt được lỗi cột json bị ghi đè.
 */
class ProductLayoutParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'x',
        ]));

        $form = Form::create(['key' => 'dang-ky-tu-van', 'name' => 'Đăng ký tư vấn']);
        $form->fields()->create([
            'key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel',
            'rules' => ['required'], 'sort' => 1,
        ]);

        config(['catalog.frontend.product_forms' => ['dang-ky-tu-van']]);
    }

    /** Đúng bộ dữ liệu một người nhập sẽ gõ để dựng lại bản thiết kế. */
    protected function formData(): array
    {
        return [
            'name'         => 'Xe Mẫu VF 7',
            'slug'         => 'xe-mau-vf-7',
            'tagline'      => 'Khi phong cách trở thành dấu ấn',
            'status'       => 'published',
            'published_at' => now(),
            'price_from'   => 799_000_000,

            'hero' => [
                'type'        => 'image',
                'lede'        => 'Thiết kế hoàn toàn mới, công nghệ dẫn đầu.',
                'intro_title' => 'Thiết kế phong cách cho thế hệ khách hàng hiện đại',
                'intro_body'  => 'Ngoại hình liền mạch, tỷ lệ cân đối, chi tiết tinh giản.',
            ],

            'highlights' => [
                ['value' => '260', 'unit' => 'kW', 'label' => 'Công suất tối đa'],
                ['value' => '500', 'unit' => 'Nm', 'label' => 'Mô-men xoắn'],
                ['value' => '496', 'unit' => 'km', 'label' => 'Quãng đường'],
                ['value' => '75,3', 'unit' => 'kWh', 'label' => 'Dung lượng pin'],
            ],

            'sections' => [
                ['title' => 'Tech Fluid — dòng chảy công nghệ', 'intro' => 'Đoạn mở đầu.',
                 'type' => 'media', 'layout' => 'gallery',
                 'items' => [['label' => 'Ảnh lớn'], ['label' => 'Ảnh 2'], ['label' => 'Ảnh 3']]],

                ['title' => 'Trải nghiệm thị giác không giới hạn', 'intro' => 'Chữ trái, ảnh phải.',
                 'type' => 'media', 'layout' => 'split', 'items' => [['label' => 'Ảnh 3/4']]],

                ['title' => 'Điểm nhấn công nghệ', 'intro' => 'Băng chuyền ba ảnh.',
                 'type' => 'media', 'layout' => 'carousel',
                 'items' => [['label' => 'A'], ['label' => 'B'], ['label' => 'C']]],

                ['title' => 'Nội thất khoáng đạt', 'intro' => 'Ảnh trái, chữ phải.',
                 'type' => 'media', 'layout' => 'split-alt', 'items' => [['label' => 'Nội thất']]],

                ['title' => 'Nâng cấp trải nghiệm thực tế mỗi ngày',
                 'type' => 'media', 'layout' => 'tabs',
                 'items' => [['label' => 'Tab 1'], ['label' => 'Tab 2'],
                             ['label' => 'Tab 3'], ['label' => 'Tab 4']]],
            ],

            'specs' => [
                ['group' => 'Động cơ', 'rows' => [
                    ['label' => 'Công suất tối đa', 'value' => '260 kW'],
                    ['label' => 'Mô-men xoắn', 'value' => '500 Nm'],
                ]],
            ],

            'spec_notes' => [
                ['label' => 'An toàn & an ninh', 'body' => 'Camera 360 độ · 6 túi khí.'],
                ['label' => 'Hỗ trợ lái nâng cao ADAS', 'body' => 'Ga tự động thích ứng · Giữ làn.'],
            ],

            'seo' => ['description' => 'Mô tả SEO cho công cụ tìm kiếm.'],
        ];
    }

    public function test_dung_xe_bang_form_admin_ra_du_khoi_nhu_ban_thiet_ke(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $html = $this->get('/san-pham/xe-mau-vf-7')->assertOk()->getContent();

        // Đủ 5 bố cục của bản thiết kế
        $this->assertStringContainsString('layout-gallery', $html, 'thiếu thư viện lớn');
        $this->assertStringContainsString('data-gallery', $html, 'thiếu băng chuyền');
        $this->assertStringContainsString('layout-split', $html, 'thiếu bố cục chia đôi');
        $this->assertStringContainsString('split--media-first', $html, 'thiếu chia đôi đảo bên');
        $this->assertStringContainsString('data-tabs', $html, 'thiếu tab đánh số');

        // Thông số phẳng + hai ô ghi chú
        $this->assertStringContainsString('spec-flat', $html, 'thông số không phải lưới phẳng');
        $this->assertStringContainsString('spec-notes', $html, 'thiếu ghi chú dưới bảng thông số');
        $this->assertStringContainsString('An toàn &amp; an ninh', $html);

        // Chữ đầu trang: ba câu KHÁC NHAU, đúng ba chỗ
        $this->assertStringContainsString('Khi phong cách trở thành dấu ấn', $html);
        $this->assertStringContainsString('Thiết kế hoàn toàn mới, công nghệ dẫn đầu.', $html);
        $this->assertStringContainsString('Thiết kế phong cách cho thế hệ khách hàng hiện đại', $html);
        $this->assertStringContainsString('Ngoại hình liền mạch, tỷ lệ cân đối, chi tiết tinh giản.', $html);
    }

    public function test_khong_in_lap_mot_cau_hai_lan(): void
    {
        $data = $this->formData();

        // Bỏ trống hai ô chữ để cả hai chỗ đều phải đi tìm nguồn dự phòng.
        unset($data['hero']['lede'], $data['hero']['intro_body']);

        Livewire::test(CreateProduct::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $html = $this->get('/san-pham/xe-mau-vf-7')->assertOk()->getContent();

        // Mô tả SEO chỉ được xuất hiện trong thẻ meta và ĐÚNG MỘT chỗ trong
        // thân trang. Hai chỗ cùng mượn nó là lỗi in lặp.
        $this->assertSame(
            2,
            substr_count($html, 'Mô tả SEO cho công cụ tìm kiếm.'),
            'mô tả SEO bị in lặp trong thân trang'
        );
    }

    public function test_sua_xe_trong_admin_khong_lam_mat_chu_dau_trang(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'xe-mau-vf-7')->sole();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['name' => 'Xe Mẫu VF 7 bản mới'])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        $this->assertSame('Thiết kế hoàn toàn mới, công nghệ dẫn đầu.', $product->hero['lede']);
        $this->assertSame('Thiết kế phong cách cho thế hệ khách hàng hiện đại', $product->hero['intro_title']);
        $this->assertSame('Ngoại hình liền mạch, tỷ lệ cân đối, chi tiết tinh giản.', $product->hero['intro_body']);
        $this->assertSame('An toàn & an ninh', $product->spec_notes[0]['label']);
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=ProductLayoutParityTest`
Expected: FAIL — `Unknown column 'spec_notes'`

- [ ] **Step 3: Tạo migration cột `spec_notes`**

Create `database/migrations/2026_01_01_000012_add_spec_notes_to_products_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hai đoạn ghi chú xếp cạnh nhau ngay dưới bảng thông số (bản thiết kế:
 * "An toàn & an ninh" và "Hỗ trợ lái nâng cao ADAS").
 *
 * Trước nhét vào `specs` dưới một nhóm tên `__notes` để khỏi thêm cột — nhưng
 * nhóm đó hiện lên repeater thông số trong admin như một nhóm bình thường,
 * người nhập sửa nhầm là hỏng. Tách hẳn ra cột riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('spec_notes')->nullable()->after('specs');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('spec_notes');
        });
    }
};
```

- [ ] **Step 4: Thêm cast vào Product**

Trong `app/Models/Product.php`, hàm `casts()`, thêm ngay sau `'specs' => 'array',`:

```php
'spec_notes'   => 'array',
```

- [ ] **Step 5: Chạy migration, chạy lại test**

Run: `php artisan migrate --force && php artisan test --filter=ProductLayoutParityTest`
Expected: vẫn FAIL — giờ là "thiếu ghi chú dưới bảng thông số" và mất chữ hero. Đúng ba lỗi đang vá.

- [ ] **Step 6: Sắp lại ProductForm theo đúng thứ tự trang**

Đây là phần "cấu trúc cho hợp layout". Trong `app/Filament/Resources/Products/Schemas/ProductForm.php`, đổi hàm `configure()` thành:

```php
public static function configure(Schema $schema): Schema
{
    // Thứ tự các mục dưới đây TRÙNG thứ tự khối trên trang chi tiết. Người
    // nhập cuộn form từ trên xuống là thấy trang thành hình theo đúng mạch
    // đó — trước kia form xếp theo cấu trúc dữ liệu nên gõ xong không biết
    // chữ mình vừa nhập rơi vào chỗ nào ngoài frontend.
    return $schema->components([
        static::basics(),      // nhận diện + trạng thái (không nằm trên trang)
        static::hero(),        // 01 · hero
        static::intro(),       // 02 · khối mở đầu
        static::highlights(),  // 03 · dải chỉ số
        static::options(),     // 04 · bảng màu
        static::sections(),    // 05 · các mục nội dung
        static::specs(),       // 06 · thông số + ghi chú
        static::variants(),    // giá, giá gạch, số liệu cho bộ so sánh chi phí
        static::seo(),
    ]);
}
```

- [ ] **Step 7: Tách mục Hero ra khỏi "Thông tin cơ bản"**

Trong cùng file, rút gọn `basics()` — BỎ bốn ô hero ra khỏi nó (giữ nguyên name, slug, tagline, category_id, price_from, status, published_at), rồi đổi nhãn tagline cho đúng vai trò của nó:

```php
TextInput::make('tagline')
    ->label('Tagline — tiêu đề lớn ở hero')
    ->helperText('Đây là dòng chữ TO NHẤT trên trang, viết như một câu quảng cáo. Tên xe đã nằm ở dòng nhỏ phía trên rồi.')
    ->columnSpanFull(),
```

Thêm hàm mới:

```php
/** 01 · Hero — ảnh nền chiếm trọn màn hình đầu trang. */
protected static function hero(): Section
{
    return Section::make('01 · Hero')
        ->description('Khối đầu trang: ảnh nền, tiêu đề lớn, đoạn dẫn, giá và hai nút.')
        ->columns(2)
        ->collapsible()
        ->schema([
            Select::make('hero.type')
                ->label('Kiểu nền')
                ->options(['image' => 'Ảnh', 'video' => 'Video'])
                ->default('image')
                ->live()
                ->selectablePlaceholder(false),

            FileUpload::make('hero.src')
                ->label('Ảnh nền')
                ->image()
                ->directory('catalog/hero')
                ->disk('public')
                ->visible(fn (Get $get) => $get('hero.type') !== 'video'),

            TextInput::make('hero.src')
                ->label('Link video')
                ->url()
                ->visible(fn (Get $get) => $get('hero.type') === 'video'),

            FileUpload::make('hero.poster')
                ->label('Ảnh poster')
                ->image()
                ->directory('catalog/hero')
                ->disk('public')
                ->visible(fn (Get $get) => $get('hero.type') === 'video'),

            Textarea::make('hero.lede')
                ->label('Đoạn dẫn dưới tiêu đề')
                ->helperText('Bỏ trống thì hero chỉ còn tiêu đề và giá — KHÔNG tự lấy mô tả SEO, vì mô tả đó đã dùng cho khối mở đầu bên dưới.')
                ->rows(3)
                ->columnSpanFull(),
        ]);
}

/** 02 · Khối mở đầu — đoạn căn giữa ngay dưới hero. */
protected static function intro(): Section
{
    return Section::make('02 · Khối mở đầu')
        ->description('Đoạn căn giữa ngay dưới hero, trước dải chỉ số.')
        ->collapsible()
        ->schema([
            TextInput::make('hero.intro_title')
                ->label('Tiêu đề')
                ->helperText('Đừng lặp lại tagline — trên trang hai câu này nằm cách nhau chưa tới một màn hình.')
                ->columnSpanFull(),

            Textarea::make('hero.intro_body')
                ->label('Nội dung')
                ->helperText('Bỏ trống thì lấy tạm mô tả SEO.')
                ->rows(3)
                ->columnSpanFull(),
        ]);
}
```

Thêm `use Filament\Forms\Components\Textarea;` vào đầu file nếu chưa có.

- [ ] **Step 8: Ghi rõ vai trò từng mục còn lại**

Đổi tiêu đề và mô tả của bốn mục còn lại cho khớp vị trí trên trang:

```php
// trong highlights()
Section::make('03 · Dải chỉ số')
    ->description('Bốn ô số lớn ngay dưới khối mở đầu. Bản thiết kế dùng đúng 4 ô — thêm nữa thì lưới gãy.')

// trong options()
Section::make('04 · '.Catalog::label('option.plural'))
    ->description('Ảnh xe đổi màu theo dãy nút tròn. Khối này không có tiêu đề trên trang.')

// trong sections()
Section::make('05 · '.Catalog::label('sections'))
    ->description('Các khối nội dung giữa trang, hiện theo đúng thứ tự bạn sắp ở đây. Bố cục chọn ở từng mục.')

// trong specs()
Section::make('06 · '.Catalog::label('specs'))
    ->description('Lưới thông số phẳng, kèm hai ô ghi chú xếp cạnh nhau bên dưới.')

// trong variants()
Section::make(Catalog::label('variant.plural'))
    ->description('Không dựng thành mục riêng trên trang. Dùng để lấy giá, giá gạch ở hero và số liệu cho bộ so sánh chi phí.')
```

- [ ] **Step 9: Thêm repeater ghi chú vào mục Thông số**

Trong hàm `specs()`, thêm vào cuối mảng `->schema([...])`, sau `SpecsRepeater::make()`:

```php
Repeater::make('spec_notes')
    ->label('Ghi chú dưới bảng')
    ->helperText('Hai ô chữ xếp cạnh nhau ngay dưới lưới thông số, VD "An toàn & an ninh" và "Hỗ trợ lái nâng cao ADAS". Bỏ trống thì không render.')
    ->addActionLabel('+ Thêm ghi chú')
    ->defaultItems(0)
    ->reorderableWithDragAndDrop()
    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
    ->schema([
        TextInput::make('label')->label('Tiêu đề')->required(),
        Textarea::make('body')->label('Nội dung')->rows(3)->required(),
    ])
    ->columnSpanFull(),
```

- [ ] **Step 10: Bỏ nguồn dự phòng gây lặp chữ ở hero**

Trong `resources/views/frontend/partials/hero.blade.php`, sửa dòng gán `$lede`:

```blade
// Mỗi câu chữ đầu trang có ĐÚNG MỘT nguồn. Trước đây câu này cũng lùi về
// mô tả SEO giống khối mở đầu, nên xe nào chưa điền là in y hệt một câu
// hai lần cách nhau vài dòng.
$lede = $hero['lede'] ?? null;
```

- [ ] **Step 11: Đổi specs.blade.php sang nhận `$notes`**

Trong `resources/views/frontend/partials/specs.blade.php`, thay khối `@php` đầu file bằng:

```blade
@php
    $notes = $notes ?? [];

    // Thiết kế xếp mọi thông số vào một lưới phẳng, không chia nhóm gấp mở.
    $rows = collect($specs)
        ->flatMap(fn ($group) => $group['rows'] ?? [])
        ->filter(fn ($row) => filled($row['label'] ?? null) || filled($row['value'] ?? null));
@endphp
```

Và thay khối ghi chú ở cuối file bằng:

```blade
@if (filled($notes))
    <div class="spec-notes">
        @foreach ($notes as $note)
            <div>
                <h3>{{ $note['label'] ?? '' }}</h3>
                <p>{{ $note['body'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
@endif
```

- [ ] **Step 12: Truyền `$notes` từ product.blade.php**

Trong `resources/views/frontend/product.blade.php`, đổi dòng include specs:

```blade
@include('frontend.partials.specs', [
    'specs' => $product->specs,
    'notes' => $product->spec_notes ?? [],
])
```

- [ ] **Step 13: Chạy test — cả 3 phải xanh**

Run: `php artisan test --filter=ProductLayoutParityTest`
Expected: PASS (3 test)

- [ ] **Step 14: Sửa seeder ghi vào cột thật**

Trong `database/seeders/Brands/BrandSeeder.php`, hàm `specs()`: XOÁ hẳn khối đẩy nhóm `__notes` vào `$groups`, trả về `$groups->all()` như cũ.

Trong hàm `seedProduct()`, thêm vào mảng ghi DB, ngay sau `'specs' => $this->specs($data),`:

```php
'spec_notes'   => $data['spec_notes'] ?? null,
```

- [ ] **Step 15: Seed lại và soi bằng mắt**

Run: `php artisan db:seed --class="Database\Seeders\Brands\VinFastSeeder" --force`
Run: `php artisan serve --port=8000`

Mở `/san-pham/vinfast-vf-7`, kiểm bằng mắt:
- Hero có đoạn dẫn riêng, khối mở đầu có tiêu đề riêng, **hai đoạn KHÁC NHAU**
- Dưới bảng thông số có hai ô "An toàn & an ninh" và "Hỗ trợ lái nâng cao ADAS"

Mở `/admin/products/{id}/edit`, kiểm:
- Các mục xếp theo thứ tự 01 Hero → 02 Khối mở đầu → 03 Dải chỉ số → 04 Bảng màu → 05 Chi tiết → 06 Thông số → Phiên bản → SEO
- Bốn ô chữ mới đều có sẵn giá trị của VF 7
- Bấm Lưu, tải lại trang chi tiết: chữ còn nguyên

- [ ] **Step 16: Chạy toàn bộ test**

Run: `vendor/bin/pint --dirty && php artisan test`
Expected: tất cả PASS

Nếu `ProductAdminTest` đỏ vì đổi cấu trúc mục trong form: sửa test theo cấu trúc mới, đừng gộp mục lại chỉ để test xanh.

- [ ] **Step 17: Commit**

```bash
git add database app resources tests
git commit -m "Sắp lại module thêm xe theo đúng layout trang chi tiết

Dựng thử một chiếc xe CHỈ bằng form admin rồi soi trang: cả 5 bố cục của bản
thiết kế đều chọn được từ dropdown, nhưng ra 7 khối trong khi thiết kế có 10,
và đoạn dẫn hero với đoạn khối mở đầu in ra y hệt một câu.

Bù bốn ô chữ form chưa có: đoạn dẫn hero, tiêu đề và nội dung khối mở đầu,
ghi chú dưới bảng thông số. Trước đó seeder ghi được còn admin thì không, nên
sửa xe rồi Lưu là Filament ghi đè cả cột json và mất sạch mấy đoạn đó.

Hết lặp chữ: mỗi câu đầu trang giờ có đúng một nguồn. Đoạn dẫn hero không còn
mượn mô tả SEO nữa — chỉ khối mở đầu được mượn, và chỉ khi bỏ trống.

Ghi chú thông số tách khỏi cột specs sang cột spec_notes riêng: trước nhét
dưới một nhóm tên __notes để khỏi thêm cột, nhưng nhóm đó hiện lên repeater
thông số như nhóm bình thường, người nhập sửa nhầm là hỏng.

Các mục trong form xếp lại trùng thứ tự khối trên trang và đánh số 01-06 theo
đúng vị trí ngoài frontend. Trước kia form xếp theo cấu trúc dữ liệu nên gõ
xong không biết chữ vừa nhập rơi vào chỗ nào trên trang.

Test mới dựng xe qua chính form Filament chứ không Product::create — chỉ
đường vòng qua form mới bắt được lỗi cột json bị ghi đè."
```

---

### Task 2: Module banner trang chủ

**Bối cảnh:** hero trang chủ đang lấy 3 xe đầu theo `sort` và dùng ảnh hero của xe (`home.blade.php:20`). Không chọn được xe nào lên banner, không đặt được tiêu đề/nút riêng, không up được ảnh chiến dịch không gắn với xe. Cài đặt là key–value phẳng nên không nhét banner nhiều slide vào đó được — phải có bảng riêng.

**Files:**
- Create: `database/migrations/2026_01_01_000013_create_banners_table.php`
- Create: `app/Models/Banner.php`
- Create: `app/Filament/Resources/Banners/BannerResource.php`
- Create: `app/Filament/Resources/Banners/Pages/ManageBanners.php`
- Modify: `config/catalog.php` (khai model + feature)
- Modify: `app/Http/Controllers/Frontend/HomeController.php`
- Modify: `resources/views/frontend/home.blade.php`
- Test: `tests/Feature/BannerTest.php`

**Interfaces:**
- Produces: `App\Models\Banner` với `scopeActive(Builder $q): Builder` — lọc `is_active` và khoảng `starts_at`/`ends_at`
- Produces: `config('catalog.models.banner')`, `config('catalog.features.banners')`
- Produces: `HomeController` truyền thêm biến `$banners` (Collection, có thể rỗng)
- Consumes: không phụ thuộc task nào

- [ ] **Step 1: Viết test thất bại**

Create `tests/Feature/BannerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use Tests\TestCase;

/**
 * Banner trang chủ. Chưa khai banner nào thì hero lùi về dùng ảnh xe như cũ —
 * site đang chạy không được vỡ chỉ vì bảng mới còn rỗng.
 */
class BannerTest extends TestCase
{
    public function test_banner_dang_bat_thi_hien_o_trang_chu(): void
    {
        Banner::create([
            'title'     => 'Trả góp 0% lãi suất 24 tháng',
            'subtitle'  => 'Áp dụng đến hết tháng 8',
            'cta_label' => 'Xem chương trình',
            'cta_url'   => '/tin-tuc',
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Trả góp 0% lãi suất 24 tháng')
            ->assertSee('Xem chương trình');
    }

    public function test_banner_tat_hoac_het_han_thi_khong_hien(): void
    {
        Banner::create(['title' => 'Banner đã tắt', 'is_active' => false]);
        Banner::create([
            'title'     => 'Banner hết hạn',
            'is_active' => true,
            'ends_at'   => now()->subDay(),
        ]);
        Banner::create([
            'title'     => 'Banner chưa tới ngày',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Banner đã tắt')
            ->assertDontSee('Banner hết hạn')
            ->assertDontSee('Banner chưa tới ngày');
    }

    public function test_chua_co_banner_thi_hero_lui_ve_dung_xe(): void
    {
        Product::create([
            'name'         => 'Lexus GX 550',
            'status'       => 'published',
            'published_at' => now(),
            'tagline'      => 'Bản lĩnh chinh phục',
        ]);

        $this->get('/')->assertOk()->assertSee('Bản lĩnh chinh phục');
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=BannerTest`
Expected: FAIL — `Class "App\Models\Banner" not found`

- [ ] **Step 3: Tạo migration**

Create `database/migrations/2026_01_01_000013_create_banners_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner hero trang chủ.
 *
 * Không nhét vào bảng `settings`: settings là key–value phẳng nên banner
 * nhiều slide sẽ phải đẻ ra banner_1_title, banner_2_title… Và banner cần
 * thứ tự với lịch chạy — hai thứ key–value không diễn tả được.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('eyebrow')->nullable();       // dòng nhỏ chữ hoa phía trên
            $table->string('image')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);

            // Khuyến mãi theo đợt: để trống là chạy vô thời hạn.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
```

- [ ] **Step 4: Tạo model Banner**

Create `app/Models/Banner.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
        ];
    }

    /**
     * Banner đang được phép hiện: đã bật VÀ đang trong khoảng thời gian chạy.
     * Mốc thời gian để trống nghĩa là không giới hạn phía đó.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
```

- [ ] **Step 5: Khai model và feature trong config**

Trong `config/catalog.php`, mảng `models`, thêm sau `'product' => ...`:

```php
'banner' => \App\Models\Banner::class,
```

Trong mảng `features`, thêm:

```php
// Banner hero trang chủ. Tắt thì hero lùi về dùng ảnh của mặt hàng.
'banners' => true,
```

- [ ] **Step 6: Chạy migration rồi chạy test**

Run: `php artisan migrate --force && php artisan test --filter=BannerTest`
Expected: 2 test đầu vẫn FAIL (frontend chưa dùng banner), test thứ 3 PASS

- [ ] **Step 7: HomeController truyền banner xuống view**

Trong `app/Http/Controllers/Frontend/HomeController.php`, thêm vào mảng `view(...)`:

```php
// Banner hero. Chưa khai banner nào thì view lùi về dùng ảnh mặt hàng —
// site mới dựng chưa kịp nhập banner vẫn có hero tử tế.
'banners' => Catalog::feature('banners')
    ? Catalog::query('banner')->active()->orderBy('sort')->get()
    : collect(),
```

- [ ] **Step 8: home.blade.php dùng banner khi có**

Trong `resources/views/frontend/home.blade.php`, thay dòng `$slides = $products->take(3);` bằng:

```blade
// Banner tự khai được ưu tiên; chưa có thì lùi về 3 mặt hàng đầu như cũ.
$slides = $banners->isNotEmpty() ? $banners : $products->take(3);
$fromBanner = $banners->isNotEmpty();
```

Rồi trong vòng `@foreach ($slides as $i => $slide)`, thay khối `@php $img = ... @endphp` và phần `<div class="hero__body">` bằng:

```blade
@php
    $img = $fromBanner
        ? catalog_image($slide->image)
        : catalog_image(data_get($slide->hero, 'src'));

    $eyebrow = $fromBanner
        ? $slide->eyebrow
        : trim($slide->name.($slide->category ? ' · '.$slide->category->name : ''));

    $heading = $fromBanner ? $slide->title : ($slide->tagline ?: $slide->name);

    $lede = $fromBanner
        ? $slide->subtitle
        : ($slide->price_from ? 'Giá từ '.catalog_money_short($slide->price_from) : null);

    $ctaLabel = $fromBanner ? $slide->cta_label : 'Khám phá '.$slide->name;
    $ctaUrl   = $fromBanner ? $slide->cta_url : route('products.show', $slide->slug);
@endphp
```

và trong `hero__inner`:

```blade
@if (filled($eyebrow))
    <span class="eyebrow">{{ $eyebrow }}</span>
@endif

<h1>{{ $heading }}</h1>

@if (filled($lede))
    <p class="hero__lede">{{ $lede }}</p>
@endif

<div class="hero__actions">
    @if (filled($ctaLabel) && filled($ctaUrl))
        <a class="btn {{ $img ? 'btn--light' : '' }}" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
    @endif
    <a class="btn {{ $img ? 'btn--ghost' : 'btn--outline' }}"
       href="{{ route('products.index') }}">Xem tất cả</a>
</div>
```

Trong khối `hero__dots`, đổi `{{ $slide->name }}` thành `{{ $fromBanner ? $slide->title : $slide->name }}`.

- [ ] **Step 9: Chạy test — phải xanh cả 3**

Run: `php artisan test --filter=BannerTest`
Expected: PASS

- [ ] **Step 10: Tạo BannerResource cho admin**

Create `app/Filament/Resources/Banners/BannerResource.php`:

```php
<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Support\Catalog;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 1;

    public static function getModel(): string
    {
        return Catalog::model('banner');
    }

    public static function getModelLabel(): string
    {
        return 'Banner';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Banner trang chủ';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('Tiêu đề')->required()->columnSpanFull(),

            TextInput::make('eyebrow')
                ->label('Dòng nhỏ phía trên')
                ->helperText('Chữ hoa giãn cách, VD "Ưu đãi mùa hè".'),

            Textarea::make('subtitle')->label('Mô tả')->rows(2)->columnSpanFull(),

            FileUpload::make('image')
                ->label('Ảnh nền')
                ->image()
                ->directory('catalog/banners')
                ->disk('public')
                ->helperText('Bỏ trống thì banner dùng nền tối, chữ vẫn đọc được.')
                ->columnSpanFull(),

            TextInput::make('cta_label')->label('Nhãn nút'),
            TextInput::make('cta_url')->label('Link nút')
                ->helperText('Nhãn không kèm link thì nút không hiện — tránh nút bấm không ra gì.'),

            Toggle::make('is_active')->label('Đang bật')->default(true),
            TextInput::make('sort')->label('Thứ tự')->numeric()->default(0),

            DateTimePicker::make('starts_at')->label('Chạy từ')->seconds(false)
                ->helperText('Bỏ trống = chạy ngay.'),
            DateTimePicker::make('ends_at')->label('Chạy đến')->seconds(false)
                ->helperText('Bỏ trống = không hết hạn.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('public'),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap(),
                IconColumn::make('is_active')->label('Bật')->boolean(),
                TextColumn::make('starts_at')->label('Từ')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('ends_at')->label('Đến')->dateTime('d/m/Y H:i')->placeholder('—'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBanners::route('/')];
    }
}
```

- [ ] **Step 11: Tạo trang quản lý**

Create `app/Filament/Resources/Banners/Pages/ManageBanners.php` — chép đúng khuôn của `app/Filament/Resources/Categories/Pages/ManageCategories.php`, đổi `CategoryResource` thành `BannerResource` và tên class thành `ManageBanners`.

- [ ] **Step 12: Viết test admin**

Thêm vào `tests/Feature/BannerTest.php`:

```php
public function test_admin_tao_va_sap_xep_banner(): void
{
    $this->actingAs(\App\Models\User::create([
        'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'x',
    ]));

    \Livewire\Livewire::test(\App\Filament\Resources\Banners\Pages\ManageBanners::class)
        ->assertSuccessful();
}
```

- [ ] **Step 13: Chạy toàn bộ test**

Run: `vendor/bin/pint --dirty && php artisan test`
Expected: tất cả PASS

- [ ] **Step 14: Commit**

```bash
git add database/migrations app/Models/Banner.php app/Filament config resources routes tests
git commit -m "Thêm module banner trang chủ

Hero trang chủ xưa nay lấy 3 xe đầu theo sort và dùng ảnh hero của xe: không
chọn được xe nào lên banner, không đặt được tiêu đề hay nút riêng, không up
được ảnh chiến dịch không gắn với xe nào.

Bảng banners riêng chứ không nhét vào Cài đặt: settings là key-value phẳng nên
banner nhiều slide sẽ đẻ ra banner_1_title, banner_2_title... và banner cần
thứ tự với lịch chạy, hai thứ key-value không diễn tả được.

Chưa khai banner nào thì hero lùi về dùng ảnh mặt hàng y như cũ — site đang
chạy không vỡ chỉ vì bảng mới còn rỗng."
```

---

### Task 3: Popup thu lead sau khi truy cập

**Bối cảnh:** chưa có gì. Nhưng nền đã sẵn — chỉ cần trỏ vào một form đã khai trong admin là có ô nhập, chống bot, mail báo, chống trùng. Popup là thứ dễ khiến khách đóng tab nhất nên mọi điều kiện (chậm bao lâu, im mấy ngày, hiện ở đâu) phải chỉnh được trong Cài đặt, không sửa code.

**Files:**
- Create: `resources/views/frontend/partials/popup.blade.php`
- Modify: `config/catalog.php` (nhóm cài đặt `popup`)
- Modify: `resources/views/frontend/layout.blade.php` (include)
- Modify: `public/js/frontend.js` (hàm `initPopup`)
- Modify: `public/css/frontend.css`
- Test: `tests/Feature/PopupTest.php`

**Interfaces:**
- Consumes: `partials/lead-form.blade.php` (đã có) để dựng ô nhập
- Produces: khối `<div class="popup" data-popup data-popup-delay="N" data-popup-days="N">`
- Consumes: không phụ thuộc task nào

- [ ] **Step 1: Viết test thất bại**

Create `tests/Feature/PopupTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Setting;
use Tests\TestCase;

/**
 * Popup thu lead. Chỉ dựng khi Cài đặt trỏ tới một form đang bật — khai
 * thiếu thì trang chủ không được dính khối rỗng nào.
 */
class PopupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $form = Form::create(['key' => 'nhan-tu-van', 'name' => 'Nhận tư vấn']);
        $form->fields()->create([
            'key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel',
            'rules' => ['required'], 'sort' => 1,
        ]);
    }

    public function test_khai_du_thi_popup_hien_o_trang_chu(): void
    {
        Setting::put('popup_form', 'nhan-tu-van');
        Setting::put('popup_title', 'Nhận báo giá lăn bánh');
        Setting::put('popup_delay', '8');

        $this->get('/')
            ->assertOk()
            ->assertSee('Nhận báo giá lăn bánh')
            ->assertSee('data-popup-delay="8"', false);
    }

    public function test_chua_khai_form_thi_khong_co_popup(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-popup', false);
    }

    public function test_form_da_tat_thi_khong_co_popup(): void
    {
        Setting::put('popup_form', 'nhan-tu-van');
        Form::where('key', 'nhan-tu-van')->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('data-popup', false);
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=PopupTest`
Expected: FAIL ở test 1 — không thấy `data-popup-delay`

- [ ] **Step 3: Khai nhóm cài đặt popup**

Trong `config/catalog.php`, mảng `settings`, thêm nhóm mới sau nhóm `home`:

```php
// Popup thu lead ở trang chủ. Bỏ trống `popup_form` là tắt hẳn.
'popup' => [
    'label'  => 'Popup thu lead',
    'fields' => [
        'popup_form'  => ['label' => 'Khoá form dùng cho popup (VD nhan-tu-van)', 'type' => 'text'],
        'popup_title' => ['label' => 'Tiêu đề popup', 'type' => 'text'],
        'popup_text'  => ['label' => 'Mô tả ngắn', 'type' => 'textarea'],
        'popup_delay' => ['label' => 'Hiện sau bao nhiêu giây', 'type' => 'number'],
        'popup_days'  => ['label' => 'Đóng rồi thì im bao nhiêu ngày', 'type' => 'number'],
        'popup_everywhere' => ['label' => 'Hiện ở mọi trang (mặc định chỉ trang chủ)', 'type' => 'toggle'],
    ],
],
```

- [ ] **Step 4: Viết partial popup**

Create `resources/views/frontend/partials/popup.blade.php`:

```blade
{{--
    Popup thu lead, hiện sau một khoảng thời gian.

    Chỉ dựng khi Cài đặt trỏ tới một form ĐANG BẬT. Mọi điều kiện (chậm bao
    lâu, im mấy ngày, hiện ở đâu) đều đọc từ Cài đặt — popup là thứ dễ làm
    khách đóng tab nhất, phải chỉnh được mà không sửa code.

    Khối này luôn `hidden` từ server. Chỉ JS mới mở nó ra, nên tắt JS là popup
    không bao giờ xuất hiện — đúng ý: không có JS thì cũng không có gì chặn
    người ta đọc trang.
--}}
@php
    $key  = catalog_setting('popup_form');
    $form = null;

    if (filled($key) && catalog_feature('forms')) {
        $form = \App\Support\Catalog::query('form')
            ->where('key', $key)
            ->where('is_active', true)
            ->with('fields')
            ->first();
    }

    // Chỉ trang chủ, trừ khi Cài đặt bảo hiện mọi trang.
    $onHome = request()->routeIs('home');
    $show   = $form && ($onHome || catalog_setting('popup_everywhere'));

    // Vừa gửi form xong thì đừng chào lại bằng chính cái popup đó.
    $justSent = session('lead_form_key') === $key;
@endphp

@if ($show && ! $justSent)
    <div class="popup" data-popup
         data-popup-key="{{ $key }}"
         data-popup-delay="{{ (int) catalog_setting('popup_delay', 10) }}"
         data-popup-days="{{ (int) catalog_setting('popup_days', 7) }}"
         hidden>
        <div class="popup__backdrop" data-popup-close></div>

        <div class="popup__panel" role="dialog" aria-modal="true"
             aria-labelledby="popup-title">
            <button type="button" class="popup__close" data-popup-close aria-label="Đóng">&times;</button>

            <div class="popup__head">
                <h2 id="popup-title">{{ catalog_setting('popup_title', $form->name) }}</h2>
                @if ($text = catalog_setting('popup_text', $form->description))
                    <p>{{ $text }}</p>
                @endif
            </div>

            @include('frontend.partials.lead-form', ['form' => $form])
        </div>
    </div>
@endif
```

- [ ] **Step 5: Nhúng vào layout**

Trong `resources/views/frontend/layout.blade.php`, thêm ngay TRƯỚC dòng `<script src="{{ asset('js/frontend.js') }}" defer></script>`:

```blade
    @include('frontend.partials.popup')
```

- [ ] **Step 6: Chạy test — phải xanh cả 3**

Run: `php artisan test --filter=PopupTest`
Expected: PASS

- [ ] **Step 7: Viết CSS**

Thêm vào `public/css/frontend.css`, ngay trước mục `RESPONSIVE`:

```css
/* ═══════════ POPUP THU LEAD ═══════════ */
/* Server luôn render kèm `hidden`; chỉ JS mới gỡ ra. Tắt JS là không bao
   giờ thấy popup — không có JS thì cũng không có gì chặn người ta đọc. */
.popup { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center; justify-content: center; padding: 20px; }
.popup[hidden] { display: none; }
.popup__backdrop { position: absolute; inset: 0; background: rgba(17, 17, 17, .55); }
.popup__panel {
    position: relative;
    width: min(520px, 100%);
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    background: #fff;
    border-radius: 6px;
    padding: 40px 36px 32px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
}
.popup__close {
    position: absolute; top: 12px; right: 14px;
    width: 36px; height: 36px;
    background: none; border: 0; border-radius: 999px;
    font-size: 26px; line-height: 1; color: var(--muted-2); cursor: pointer;
}
.popup__close:hover { background: var(--soft); color: var(--ink); }
.popup__head { margin-bottom: 24px; text-align: center; }
.popup__head h2 { font-size: clamp(22px, 3vw, 28px); margin-bottom: 10px; }
.popup__head p { font-size: 14.5px; color: var(--muted); }
.popup .lead-form-wrap { max-width: none; }

@media (max-width: 680px) {
    .popup__panel { padding: 32px 22px 24px; }
}
```

- [ ] **Step 8: Viết JS**

Thêm vào `public/js/frontend.js`, ngay trước `function boot()`:

```js
    /* ── Popup thu lead ───────────────────────────────────────────────── */
    /* Mọi điều kiện đọc từ data-* (Cài đặt đổ xuống). Đóng rồi thì ghi mốc
       vào localStorage và im đúng số ngày đã khai — không hỏi lại mỗi lần
       tải trang. */
    function initPopup(root) {
        var key = 'popup:' + (root.dataset.popupKey || 'mac-dinh');
        var days = parseInt(root.dataset.popupDays, 10) || 0;
        var delay = (parseInt(root.dataset.popupDelay, 10) || 10) * 1000;

        try {
            var until = parseInt(localStorage.getItem(key), 10);
            if (until && Date.now() < until) return;
        } catch (e) { /* trình duyệt chặn localStorage thì cứ hiện */ }

        var opener = null;
        var timer = setTimeout(open, delay);

        function remember() {
            if (!days) return;
            try {
                localStorage.setItem(key, String(Date.now() + days * 86400000));
            } catch (e) { /* không ghi được thì thôi */ }
        }

        function open() {
            opener = document.activeElement;
            root.hidden = false;
            var first = root.querySelector('input:not([type=hidden]), button, select, textarea');
            if (first) first.focus();
            document.addEventListener('keydown', onKey);
        }

        function close() {
            clearTimeout(timer);
            root.hidden = true;
            remember();
            document.removeEventListener('keydown', onKey);
            if (opener && opener.focus) opener.focus();
        }

        /* Esc để đóng, Tab quẩn trong panel — không để tiêu điểm chạy ra
           sau lớp phủ rồi người dùng bàn phím mắc kẹt. */
        function onKey(e) {
            if (e.key === 'Escape') { e.preventDefault(); close(); return; }
            if (e.key !== 'Tab') return;

            var items = root.querySelectorAll(
                'a[href], button:not([disabled]), input:not([type=hidden]), select, textarea'
            );
            if (!items.length) return;

            var first = items[0];
            var last = items[items.length - 1];

            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-popup-close]')) { e.preventDefault(); close(); }
        });

        /* Bấm Gửi thì coi như xong việc: ghi mốc luôn để lần sau khỏi hiện. */
        var form = root.querySelector('form');
        if (form) form.addEventListener('submit', remember);
    }
```

Và trong `boot()`, thêm:

```js
        document.querySelectorAll('[data-popup]').forEach(initPopup);
```

- [ ] **Step 9: Soi bằng mắt**

Run: `php artisan tinker --execute="App\Models\Setting::put('popup_form','dang-ky-nhan-tin'); App\Models\Setting::put('popup_title','Nhận ưu đãi mới nhất'); App\Models\Setting::put('popup_delay','3'); App\Models\Setting::put('popup_days','7');"`
Run: `php artisan serve --port=8000` rồi mở `/`
Expected: sau 3 giây popup hiện; bấm Esc đóng; tải lại trang thì không hiện nữa

- [ ] **Step 10: Chạy toàn bộ test rồi commit**

```bash
vendor/bin/pint --dirty && php artisan test
git add config resources public tests
git commit -m "Thêm popup thu lead ở trang chủ

Dùng lại form đã khai trong admin nên chống bot, chống trùng, mail báo y hệt
mọi form khác. Chậm bao lâu, đóng rồi im mấy ngày, hiện trang chủ hay mọi
trang đều đọc từ Cài đặt — popup là thứ dễ làm khách đóng tab nhất, phải
chỉnh được mà không sửa code.

Server luôn render kèm hidden, chỉ JS mới mở: tắt JS là popup không bao giờ
xuất hiện. Esc đóng được, Tab quẩn trong panel để người dùng bàn phím không
mắc kẹt sau lớp phủ. Gửi form xong thì ghi mốc luôn, khỏi chào lại."
```

---

### Task 4: Bộ tính trả góp

**Bối cảnh:** khách Việt hỏi trả góp trước khi hỏi giá. Phạm vi đã chốt: **chỉ tính khoản vay và lãi**, không đụng lệ phí lăn bánh (bộ phận khác lo).

Cách tính là **dư nợ giảm dần** — chuẩn phổ biến của ngân hàng Việt Nam: gốc chia đều theo kỳ, lãi tính trên dư nợ còn lại, nên tiền trả tháng đầu cao nhất rồi giảm dần.

Theo đúng quy ước của bộ so sánh chi phí nhiên liệu đang có: **form GET, tính bằng PHP**, không cần JS.

**Files:**
- Create: `app/Support/Loan.php`
- Create: `resources/views/frontend/partials/loan-calculator.blade.php`
- Modify: `config/catalog.php` (khối `loan` + feature)
- Modify: `app/Http/Controllers/Frontend/ProductController.php`
- Modify: `resources/views/frontend/product.blade.php`
- Modify: `public/css/frontend.css`
- Test: `tests/Unit/LoanTest.php`, `tests/Feature/LoanCalculatorTest.php`

**Interfaces:**
- Produces: `App\Support\Loan::schedule(float $price, float $downPayment, float $annualRate, int $months): array`
  trả về `['principal' => float, 'months' => int, 'first_payment' => float, 'last_payment' => float, 'total_interest' => float, 'total_paid' => float, 'monthly_principal' => float]`
- Produces: `ProductController` truyền thêm biến `$loan` (mảng như trên, hoặc `null` khi tắt/không có giá)
- Consumes: không phụ thuộc task nào

- [ ] **Step 1: Viết unit test thất bại**

Create `tests/Unit/LoanTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\Loan;
use PHPUnit\Framework\TestCase;

/**
 * Trả góp dư nợ giảm dần — chuẩn phổ biến của ngân hàng Việt Nam:
 * gốc chia đều theo kỳ, lãi tính trên dư nợ CÒN LẠI.
 */
class LoanTest extends TestCase
{
    public function test_tinh_dung_khoan_vay_dau_tien_va_cuoi_cung(): void
    {
        // Vay 800 triệu (xe 1 tỷ, trả trước 200 triệu), 12 tháng, 9%/năm.
        $r = Loan::schedule(1_000_000_000, 200_000_000, 9, 12);

        $this->assertSame(800_000_000.0, $r['principal']);
        $this->assertSame(12, $r['months']);

        // Gốc mỗi tháng = 800tr / 12
        $this->assertEqualsWithDelta(66_666_666.67, $r['monthly_principal'], 1);

        // Tháng đầu: gốc + 800tr × 9% / 12 = 66.666.667 + 6.000.000
        $this->assertEqualsWithDelta(72_666_666.67, $r['first_payment'], 1);

        // Tháng cuối: gốc + (800tr/12) × 9% / 12
        $this->assertEqualsWithDelta(67_166_666.67, $r['last_payment'], 1);
    }

    public function test_tong_lai_bang_cong_thuc_du_no_giam_dan(): void
    {
        $r = Loan::schedule(1_000_000_000, 200_000_000, 9, 12);

        // Tổng lãi = P × r/12 × (n+1)/2 = 800tr × 0.0075 × 6.5
        $this->assertEqualsWithDelta(39_000_000, $r['total_interest'], 1);
        $this->assertEqualsWithDelta(839_000_000, $r['total_paid'], 1);
    }

    public function test_lai_suat_khong_thi_chi_tra_goc(): void
    {
        $r = Loan::schedule(600_000_000, 100_000_000, 0, 10);

        $this->assertSame(0.0, $r['total_interest']);
        $this->assertEqualsWithDelta(50_000_000, $r['first_payment'], 1);
    }

    public function test_tra_truoc_vuot_gia_thi_khong_con_khoan_vay(): void
    {
        $r = Loan::schedule(500_000_000, 900_000_000, 9, 12);

        $this->assertSame(0.0, $r['principal']);
        $this->assertSame(0.0, $r['first_payment']);
        $this->assertSame(0.0, $r['total_interest']);
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=LoanTest`
Expected: FAIL — `Class "App\Support\Loan" not found`

- [ ] **Step 3: Viết App\Support\Loan**

Create `app/Support/Loan.php`:

```php
<?php

namespace App\Support;

/**
 * Trả góp mua xe — dư nợ giảm dần.
 *
 * Gốc chia đều theo kỳ, lãi tính trên dư nợ CÒN LẠI, nên tiền trả tháng đầu
 * cao nhất rồi giảm dần. Đây là cách hầu hết ngân hàng Việt Nam áp dụng cho
 * vay mua ô tô — khác với trả đều hằng tháng (annuity) của thẻ tín dụng.
 *
 * Chỉ tính khoản vay và lãi. Lệ phí lăn bánh KHÔNG thuộc phạm vi ở đây.
 */
class Loan
{
    /**
     * @param  float  $price        giá xe
     * @param  float  $downPayment  số tiền trả trước
     * @param  float  $annualRate   lãi suất năm, đơn vị phần trăm (9 = 9%/năm)
     * @param  int    $months       số kỳ trả, tính theo tháng
     * @return array<string, float|int>
     */
    public static function schedule(float $price, float $downPayment, float $annualRate, int $months): array
    {
        $months = max(1, $months);

        // Trả trước bằng hoặc vượt giá xe thì không còn gì để vay.
        $principal = max(0.0, $price - $downPayment);

        $monthlyRate = $annualRate / 100 / 12;
        $monthlyPrincipal = $principal / $months;

        // Lãi kỳ đầu tính trên toàn bộ dư nợ; kỳ cuối chỉ còn đúng một phần gốc.
        $firstInterest = $principal * $monthlyRate;
        $lastInterest = $monthlyPrincipal * $monthlyRate;

        // Tổng lãi = P × r × (n+1) / 2n × n = P × r × (n+1)/2
        $totalInterest = $principal * $monthlyRate * ($months + 1) / 2;

        return [
            'principal'         => round($principal, 2),
            'months'            => $months,
            'monthly_principal' => round($monthlyPrincipal, 2),
            'first_payment'     => round($monthlyPrincipal + $firstInterest, 2),
            'last_payment'      => round($monthlyPrincipal + $lastInterest, 2),
            'total_interest'    => round($totalInterest, 2),
            'total_paid'        => round($principal + $totalInterest, 2),
        ];
    }
}
```

- [ ] **Step 4: Chạy unit test — phải xanh**

Run: `php artisan test --filter=LoanTest`
Expected: PASS (4 test)

- [ ] **Step 5: Khai config**

Trong `config/catalog.php`, mảng `features`, thêm:

```php
// Bộ tính trả góp ở trang chi tiết xe. Chỉ tính khoản vay và lãi.
'loan_calc' => true,
```

Và thêm một khối mới ở cấp cao nhất, ngay sau khối `fuel_calc`:

```php
/*
|--------------------------------------------------------------------------
| Trả góp (giá trị mặc định của bộ tính)
|--------------------------------------------------------------------------
| Lãi suất tham khảo, không phải cam kết của ngân hàng. Đổi theo thời điểm.
*/
'loan' => [
    'down_payment_percent' => 30,     // % trả trước gợi ý sẵn
    'annual_rate'          => 9.0,    // %/năm
    'months'               => 60,     // kỳ trả mặc định
    'month_options'        => [12, 24, 36, 48, 60, 72, 84],
],
```

- [ ] **Step 6: Viết feature test thất bại**

Create `tests/Feature/LoanCalculatorTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class LoanCalculatorTest extends TestCase
{
    protected function product(): Product
    {
        return Product::create([
            'name'         => 'Lexus GX 550',
            'status'       => 'published',
            'published_at' => now(),
            'price_from'   => 1_000_000_000,
        ]);
    }

    public function test_hien_bo_tinh_tra_gop_voi_gia_tri_mac_dinh(): void
    {
        $this->product();

        $this->get('/san-pham/lexus-gx-550')
            ->assertOk()
            ->assertSee('Trả góp')
            ->assertSee('name="down"', false)
            ->assertSee('name="months"', false);
    }

    public function test_nhap_so_lieu_thi_tinh_ra_ket_qua(): void
    {
        $this->product();

        // Vay 800tr, 12 tháng, 9%/năm → tháng đầu 72.666.667 đ
        $this->get('/san-pham/lexus-gx-550?down=200000000&months=12&rate=9')
            ->assertOk()
            ->assertSee('72.666.667 đ');
    }

    public function test_tat_feature_thi_khoi_do_bien_mat(): void
    {
        $this->product();
        config(['catalog.features.loan_calc' => false]);

        $this->get('/san-pham/lexus-gx-550')->assertOk()->assertDontSee('name="down"', false);
    }

    public function test_xe_chua_co_gia_thi_khong_dung_bo_tinh(): void
    {
        Product::create(['name' => 'Lexus LX 700h', 'status' => 'published', 'published_at' => now()]);

        $this->get('/san-pham/lexus-lx-700h')->assertOk()->assertDontSee('name="down"', false);
    }
}
```

- [ ] **Step 7: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=LoanCalculatorTest`
Expected: FAIL — chưa có ô `down`

- [ ] **Step 8: ProductController tính và truyền `$loan`**

Trong `app/Http/Controllers/Frontend/ProductController.php`, thêm method:

```php
/**
 * Trả góp cho trang chi tiết. Đọc số liệu từ query string như bộ so sánh
 * chi phí nhiên liệu — form GET, tính bằng PHP, không cần JS.
 *
 * Xe chưa có giá thì trả null, view tự ẩn cả khối.
 */
protected function loan(Request $request, Model $product): ?array
{
    if (! catalog_feature('loan_calc')) {
        return null;
    }

    $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    $price   = (float) ($product->price_from ?: $variant?->price);

    if ($price <= 0) {
        return null;
    }

    $defaults = (array) config('catalog.loan', []);

    $down = $request->filled('down')
        ? (float) preg_replace('/\D/', '', (string) $request->query('down'))
        : $price * ((float) ($defaults['down_payment_percent'] ?? 30)) / 100;

    $months = (int) $request->query('months', $defaults['months'] ?? 60);
    $rate   = (float) str_replace(',', '.', (string) $request->query('rate', $defaults['annual_rate'] ?? 9));

    return \App\Support\Loan::schedule($price, $down, $rate, $months) + [
        'price'         => $price,
        'down'          => round($down, 2),
        'rate'          => $rate,
        'month_options' => (array) ($defaults['month_options'] ?? [12, 24, 36, 48, 60]),
    ];
}
```

Thêm `use Illuminate\Http\Request;` và `use Illuminate\Database\Eloquent\Model;` nếu chưa có, đổi chữ ký `__invoke` để nhận `Request $request`, rồi thêm `'loan' => $this->loan($request, $product),` vào mảng truyền cho view.

- [ ] **Step 9: Viết partial bộ tính**

Create `resources/views/frontend/partials/loan-calculator.blade.php`:

```blade
{{--
    Trả góp — form GET thường, tính bằng PHP, không cần JS (giống bộ so sánh
    chi phí nhiên liệu). Bấm Tính là tải lại đúng trang này kèm query string.

    Dư nợ giảm dần: gốc chia đều, lãi tính trên dư nợ còn lại, nên tiền trả
    tháng đầu cao nhất rồi giảm dần.

    Biến: $product · $loan (từ ProductController@loan)
--}}
<div class="loan">
    <form class="loan__panel" method="GET" action="#tra-gop">
        <div class="field">
            <label for="loan-down">Số tiền trả trước</label>
            <input type="text" inputmode="numeric" id="loan-down" name="down"
                   value="{{ number_format($loan['down'], 0, ',', '.') }}">
            <p class="field__hint">Giá xe {{ catalog_money($loan['price']) }}</p>
        </div>

        <div class="field">
            <label for="loan-months">Thời hạn vay</label>
            <select id="loan-months" name="months">
                @foreach ($loan['month_options'] as $m)
                    <option value="{{ $m }}" @selected($loan['months'] === $m)>{{ $m }} tháng</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="loan-rate">Lãi suất (%/năm)</label>
            <input type="text" inputmode="decimal" id="loan-rate" name="rate"
                   value="{{ rtrim(rtrim(number_format($loan['rate'], 2, ',', ''), '0'), ',') }}">
        </div>

        <button class="btn btn--accent" type="submit">Tính trả góp</button>
    </form>

    <div class="loan__result">
        <div class="loan__lead">
            <span>Trả tháng đầu</span>
            <b>{{ catalog_money($loan['first_payment']) }}</b>
            <span class="loan__fall">giảm dần về {{ catalog_money($loan['last_payment']) }} ở tháng cuối</span>
        </div>

        <dl class="loan__rows">
            <div><dt>Số tiền vay</dt><dd>{{ catalog_money($loan['principal']) }}</dd></div>
            <div><dt>Gốc mỗi tháng</dt><dd>{{ catalog_money($loan['monthly_principal']) }}</dd></div>
            <div><dt>Tổng lãi phải trả</dt><dd>{{ catalog_money($loan['total_interest']) }}</dd></div>
            <div><dt>Tổng gốc và lãi</dt><dd>{{ catalog_money($loan['total_paid']) }}</dd></div>
        </dl>

        <p class="loan__note">
            (*) Tính theo dư nợ giảm dần, chỉ gồm khoản vay và lãi. Số liệu tham khảo,
            không phải cam kết cho vay — lãi suất và điều kiện duyệt hồ sơ do ngân hàng quyết định.
        </p>
    </div>
</div>
```

- [ ] **Step 10: Nhúng vào trang chi tiết**

Trong `resources/views/frontend/product.blade.php`, thêm NGAY SAU khối so sánh chi phí nhiên liệu:

```blade
{{-- ── Trả góp ────────────────────────────────────────────────── --}}
@if ($loan)
    <section class="section" id="tra-gop">
        <div class="wrap">
            <div class="section__head">
                <h2>Trả góp {{ $product->name }}</h2>
                <p>Ước tính khoản vay và lãi theo dư nợ giảm dần.</p>
            </div>
            @include('frontend.partials.loan-calculator', ['product' => $product, 'loan' => $loan])
        </div>
    </section>
@endif
```

- [ ] **Step 11: Viết CSS**

Thêm vào `public/css/frontend.css`, trước mục `RESPONSIVE`:

```css
/* ═══════════ TRẢ GÓP ═══════════ */
.loan { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 48px; align-items: start; }
.loan__panel { background: var(--soft); border: 1px solid var(--line); border-radius: 6px; padding: 32px 30px; }
.loan__panel .btn { width: 100%; }

.loan__lead { padding-bottom: 24px; border-bottom: 1px solid var(--line); margin-bottom: 24px; }
.loan__lead > span { display: block; font-size: 13px; color: var(--muted-2); }
.loan__lead b { display: block; font-size: clamp(28px, 4vw, 40px); font-weight: 700; color: var(--accent); margin: 6px 0 8px; }
.loan__fall { font-size: 13px; color: var(--muted); }

.loan__rows { margin: 0; display: flex; flex-direction: column; gap: 14px; }
.loan__rows > div { display: flex; justify-content: space-between; gap: 20px; align-items: baseline; }
.loan__rows dt { font-size: 14.5px; color: var(--muted); }
.loan__rows dd { margin: 0; font-size: 16px; font-weight: 600; color: var(--ink); font-variant-numeric: tabular-nums; }

.loan__note { margin-top: 24px; font-size: 12px; line-height: 1.7; color: var(--muted-2); }

@media (max-width: 960px) {
    .loan { grid-template-columns: minmax(0, 1fr); gap: 32px; }
}
```

- [ ] **Step 12: Chạy test — phải xanh**

Run: `php artisan test --filter=LoanCalculatorTest`
Expected: PASS (4 test)

- [ ] **Step 13: Chạy toàn bộ test rồi commit**

```bash
vendor/bin/pint --dirty && php artisan test
git add app config resources public tests
git commit -m "Thêm bộ tính trả góp ở trang chi tiết xe

Khách hỏi trả góp trước khi hỏi giá. Chỉ tính khoản vay và lãi — lệ phí lăn
bánh do bộ phận khác lo, không thuộc phạm vi web này.

Dư nợ giảm dần chứ không trả đều hằng tháng: gốc chia đều theo kỳ, lãi tính
trên dư nợ còn lại, nên tiền trả tháng đầu cao nhất rồi giảm. Đây là cách hầu
hết ngân hàng Việt Nam áp dụng cho vay mua ô tô.

Form GET tính bằng PHP, không cần JS — cùng quy ước với bộ so sánh chi phí
nhiên liệu đang có. Xe chưa có giá thì cả khối tự ẩn."
```

---

### Task 5: Trang đại lý / showroom

**Bối cảnh:** bảng `dealers` và `provinces` đã có từ đầu, API `/api/v1/dealers` đã trả dữ liệu — nhưng **không có resource admin** (không nhập được) và **không có trang frontend** (không ai xem được). Đây là code đã viết rồi mà chưa dùng, nối vào rẻ hơn làm mới.

Cột sẵn có: `dealers` (name, address, province_id, lat, lng, phone, opening_hours json) · `provinces` (name, code, + các cột phí lăn bánh — KHÔNG đụng tới, bộ phận khác lo).

**Files:**
- Create: `app/Filament/Resources/Dealers/DealerResource.php` + `Pages/ManageDealers.php`
- Create: `app/Filament/Resources/Provinces/ProvinceResource.php` + `Pages/ManageProvinces.php`
- Create: `app/Http/Controllers/Frontend/DealerController.php`
- Create: `resources/views/frontend/dealers.blade.php`
- Modify: `config/catalog.php` (`routes.dealer`)
- Modify: `routes/web.php`
- Modify: `resources/views/frontend/partials/footer.blade.php`
- Modify: `public/css/frontend.css`
- Test: `tests/Feature/DealerPageTest.php`

**Interfaces:**
- Produces: route tên `dealers`, đường dẫn từ `config('catalog.routes.dealer')` = `/he-thong-dai-ly`
- Produces: `DealerController` truyền `$provinces` (Collection tỉnh CÓ đại lý, đã eager-load `dealers`)
- Consumes: `catalog_feature('dealers')` (đã có sẵn trong config, đang bật)

- [ ] **Step 1: Viết test thất bại**

Create `tests/Feature/DealerPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\Province;
use Tests\TestCase;

class DealerPageTest extends TestCase
{
    public function test_trang_dai_ly_hien_theo_tinh(): void
    {
        $bg = Province::create(['name' => 'Bắc Giang']);
        $hn = Province::create(['name' => 'Hà Nội']);

        Dealer::create([
            'name' => 'Showroom Xương Giang', 'province_id' => $bg->id,
            'address' => 'Đường Xương Giang, TP. Bắc Giang', 'phone' => '0204 123 456',
        ]);
        Dealer::create(['name' => 'Showroom Long Biên', 'province_id' => $hn->id]);

        $this->get('/he-thong-dai-ly')
            ->assertOk()
            ->assertSee('Bắc Giang')
            ->assertSee('Showroom Xương Giang')
            ->assertSee('0204 123 456')
            ->assertSee('Showroom Long Biên');
    }

    public function test_tinh_khong_co_dai_ly_thi_khong_hien(): void
    {
        Province::create(['name' => 'Tỉnh trống']);

        $this->get('/he-thong-dai-ly')->assertOk()->assertDontSee('Tỉnh trống');
    }

    public function test_tat_feature_thi_route_bien_mat(): void
    {
        config(['catalog.features.dealers' => false]);

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dealers'));
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=DealerPageTest`
Expected: FAIL — 404 vì chưa có route

- [ ] **Step 3: Khai tiền tố route**

Trong `config/catalog.php`, mảng `routes`, thêm cạnh `booking`/`accessory`/`service`:

```php
'dealer'    => '/he-thong-dai-ly',
```

- [ ] **Step 4: Thêm quan hệ dealers vào Province**

Trong `app/Models/Province.php`, thêm:

```php
public function dealers(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(catalog_model('dealer'))->orderBy('name');
}
```

- [ ] **Step 5: Viết controller**

Create `app/Http/Controllers/Frontend/DealerController.php`:

```php
<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;

/**
 * Hệ thống đại lý — /he-thong-dai-ly (tiền tố lấy từ config).
 *
 * Nhóm theo tỉnh vì khách tìm showroom gần nhà chứ không tìm theo tên. Tỉnh
 * chưa có đại lý nào thì không liệt kê, khỏi để tên tỉnh trống trơ ra.
 */
class DealerController extends Controller
{
    public function __invoke(): View
    {
        return view('frontend.dealers', [
            'provinces' => Catalog::query('province')
                ->has('dealers')
                ->with('dealers')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
```

- [ ] **Step 6: Khai route**

Trong `routes/web.php`, khối "Trang cố định", thêm:

```php
if (catalog_feature('dealers')) {
    Route::get(trim(Url::prefix('dealer'), '/'), DealerController::class)->name('dealers');
}
```

Thêm `use App\Http\Controllers\Frontend\DealerController;` ở đầu file.

- [ ] **Step 7: Viết view**

Create `resources/views/frontend/dealers.blade.php`:

```blade
{{--
    Hệ thống đại lý, nhóm theo tỉnh.

    Giờ mở cửa là json tự do (["T2-T7: 8:00-19:00", "CN: 8:00-17:00"]) nên
    view chỉ việc liệt kê từng dòng, không đoán cấu trúc.

    Biến: $provinces (đã eager-load dealers)
--}}
@extends('frontend.layout', [
    'title'     => 'Hệ thống đại lý — '.catalog_setting('site_name', config('app.name')),
    'canonical' => route('dealers'),
])

@section('content')
    <div class="wrap">
        <ol class="breadcrumb">
            <li><a href="{{ route('home') }}">Trang chủ</a></li>
            <li>Hệ thống đại lý</li>
        </ol>
    </div>

    <section class="block" style="padding-top:32px">
        <div class="wrap">
            <h1>Hệ thống đại lý</h1>

            @if ($provinces->isEmpty())
                <p class="empty">Chưa có đại lý nào được đăng.</p>
            @else
                @foreach ($provinces as $province)
                    <div class="dealer-group">
                        <h2 class="dealer-group__name">{{ $province->name }}</h2>

                        <div class="dealer-grid">
                            @foreach ($province->dealers as $dealer)
                                <article class="dealer">
                                    <h3>{{ $dealer->name }}</h3>

                                    @if (filled($dealer->address))
                                        <p class="dealer__address">{{ $dealer->address }}</p>
                                    @endif

                                    @if (filled($dealer->opening_hours))
                                        <ul class="dealer__hours">
                                            @foreach ((array) $dealer->opening_hours as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="dealer__actions">
                                        @if (filled($dealer->phone))
                                            <a class="btn btn--sm" href="tel:{{ preg_replace('/\s+/', '', $dealer->phone) }}">{{ $dealer->phone }}</a>
                                        @endif

                                        @if ($dealer->lat && $dealer->lng)
                                            <a class="btn btn--sm btn--outline" rel="noopener" target="_blank"
                                               href="https://www.google.com/maps/search/?api=1&query={{ $dealer->lat }},{{ $dealer->lng }}">Chỉ đường</a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>
@endsection
```

- [ ] **Step 8: Chạy test — phải xanh**

Run: `php artisan test --filter=DealerPageTest`
Expected: PASS

- [ ] **Step 9: Viết CSS**

Thêm vào `public/css/frontend.css`, trước mục `RESPONSIVE`:

```css
/* ═══════════ HỆ THỐNG ĐẠI LÝ ═══════════ */
.dealer-group { margin-top: 48px; }
.dealer-group__name {
    font-size: clamp(19px, 2vw, 24px);
    padding-bottom: 14px;
    border-bottom: 1px solid var(--line);
    margin-bottom: 24px;
}
.dealer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.dealer {
    border: 1px solid var(--line);
    border-radius: 4px;
    background: #fff;
    padding: 24px 26px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: border-color .2s ease;
}
.dealer:hover { border-color: #b5b5b0; }
.dealer h3 { font-size: 17px; font-weight: 600; }
.dealer__address { font-size: 14px; color: var(--muted); margin: 0; }
.dealer__hours { font-size: 13px; color: var(--muted-2); line-height: 1.8; }
.dealer__actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: auto; padding-top: 8px; }
```

- [ ] **Step 10: Thêm link vào footer**

Trong `resources/views/frontend/partials/footer.blade.php`, cột "Đại lý", thêm dòng đầu tiên trong `<ul>`:

```blade
@if (Route::has('dealers'))
    <li><a href="{{ route('dealers') }}">Hệ thống đại lý</a></li>
@endif
```

- [ ] **Step 11: Tạo resource admin cho Đại lý**

Create `app/Filament/Resources/Dealers/DealerResource.php`:

```php
<?php

namespace App\Filament\Resources\Dealers;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Dealers\Pages\ManageDealers;
use App\Support\Catalog;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DealerResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 8;

    public static function getModel(): string
    {
        return Catalog::model('dealer');
    }

    public static function getModelLabel(): string
    {
        return 'Đại lý';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Đại lý';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Catalog::feature('dealers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Tên đại lý')->required()->columnSpanFull(),

            Select::make('province_id')
                ->label('Tỉnh / thành')
                ->relationship('province', 'name')
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('phone')->label('Điện thoại')->tel(),

            Textarea::make('address')->label('Địa chỉ')->rows(2)->columnSpanFull(),

            TextInput::make('lat')->label('Vĩ độ')->numeric()
                ->helperText('Điền cả vĩ độ và kinh độ thì trang đại lý mới hiện nút Chỉ đường.'),
            TextInput::make('lng')->label('Kinh độ')->numeric(),

            Repeater::make('opening_hours')
                ->label('Giờ mở cửa')
                ->addActionLabel('+ Thêm dòng')
                ->defaultItems(0)
                ->simple(TextInput::make('line')->placeholder('T2–T7: 8:00–19:00'))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('province.name')->label('Tỉnh / thành')->badge()->color('gray')->sortable(),
                TextColumn::make('address')->label('Địa chỉ')->wrap()->toggleable(),
                TextColumn::make('phone')->label('Điện thoại')->copyable(),
            ])
            ->filters([
                SelectFilter::make('province_id')
                    ->label('Tỉnh / thành')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDealers::route('/')];
    }
}
```

Create `app/Filament/Resources/Dealers/Pages/ManageDealers.php` — chép khuôn `Categories/Pages/ManageCategories.php`, đổi tên class và resource.

- [ ] **Step 12: Tạo resource admin cho Tỉnh**

Create `app/Filament/Resources/Provinces/ProvinceResource.php` theo cùng khuôn, form chỉ hai ô:

```php
TextInput::make('name')->label('Tên tỉnh / thành')->required(),
TextInput::make('code')->label('Mã')->helperText('VD 24 cho Bắc Giang. Không bắt buộc.'),
```

Bảng hiện `name`, `code`, và cột đếm `dealers_count`:

```php
TextColumn::make('dealers_count')->label('Số đại lý')->counts('dealers'),
```

Ghi chú trong đầu file: các cột phí lăn bánh của bảng `provinces` cố ý KHÔNG đưa vào form này — bộ phận khác quản lý, đưa vào đây dễ sửa nhầm.

Create `app/Filament/Resources/Provinces/Pages/ManageProvinces.php` theo khuôn.

- [ ] **Step 13: Thêm test admin**

Thêm vào `tests/Feature/DealerPageTest.php`:

```php
public function test_man_hinh_admin_dai_ly_render_duoc(): void
{
    $this->actingAs(\App\Models\User::create([
        'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'x',
    ]));

    \Livewire\Livewire::test(\App\Filament\Resources\Dealers\Pages\ManageDealers::class)
        ->assertSuccessful();

    \Livewire\Livewire::test(\App\Filament\Resources\Provinces\Pages\ManageProvinces::class)
        ->assertSuccessful();
}
```

- [ ] **Step 14: Chạy toàn bộ test rồi commit**

```bash
vendor/bin/pint --dirty && php artisan test
git add app config resources routes public tests
git commit -m "Nối bảng đại lý sẵn có vào admin và frontend

Bảng dealers/provinces có từ đầu và API đã trả dữ liệu, nhưng không có màn
hình admin để nhập và không có trang cho khách xem — code viết rồi mà chưa ai
dùng được.

Trang /he-thong-dai-ly nhóm theo tỉnh vì khách tìm showroom gần nhà chứ không
tìm theo tên; tỉnh chưa có đại lý thì không liệt kê. Nút Chỉ đường chỉ hiện
khi đã điền cả vĩ độ lẫn kinh độ.

Các cột phí lăn bánh của bảng provinces cố ý không đưa vào form admin — bộ
phận khác quản lý, để đây dễ sửa nhầm."
```

---

### Task 6: Tìm kiếm

**Bối cảnh:** frontend chưa có ô tìm kiếm nào. Với ~6 dòng xe thì chưa gắt, nhưng thêm phụ kiện và tin tức vào là đã đủ nội dung để người ta muốn gõ tìm.

Phạm vi cố ý hẹp: `LIKE` trên tên/tagline/mô tả, gộp mặt hàng và bài viết. **Không** dùng full-text index hay công cụ tìm kiếm ngoài — chưa đủ dữ liệu để đáng.

**Files:**
- Create: `app/Http/Controllers/Frontend/SearchController.php`
- Create: `resources/views/frontend/search.blade.php`
- Modify: `config/catalog.php` (`routes.search`)
- Modify: `routes/web.php`
- Modify: `resources/views/frontend/partials/header.blade.php`
- Modify: `public/css/frontend.css`
- Test: `tests/Feature/SearchTest.php`

**Interfaces:**
- Produces: route tên `search`, đường dẫn `/tim-kiem`, nhận query `?q=`
- Produces: view nhận `$q` (string), `$products` (Collection), `$posts` (Collection)
- Consumes: `Product::scopeNotInCategory()` (đã có, từ module phụ kiện)

- [ ] **Step 1: Viết test thất bại**

Create `tests/Feature/SearchTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use Tests\TestCase;

class SearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::create([
            'name' => 'VinFast VF 7', 'status' => 'published', 'published_at' => now(),
            'tagline' => 'SUV điện cỡ C',
        ]);
        Product::create([
            'name' => 'VinFast VF 9', 'status' => 'published', 'published_at' => now(),
        ]);
        Product::create(['name' => 'Xe nháp', 'status' => 'draft']);

        Post::create([
            'title' => 'So sánh VF 7 và VF 9', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    public function test_tim_thay_xe_va_bai_viet_theo_tu_khoa(): void
    {
        $this->get('/tim-kiem?q=VF+7')
            ->assertOk()
            ->assertSee('VinFast VF 7')
            ->assertSee('So sánh VF 7 và VF 9')
            ->assertDontSee('VinFast VF 9');
    }

    public function test_tim_theo_tagline(): void
    {
        $this->get('/tim-kiem?q=SUV điện')->assertOk()->assertSee('VinFast VF 7');
    }

    public function test_khong_tra_ve_ban_nhap(): void
    {
        $this->get('/tim-kiem?q=nháp')->assertOk()->assertDontSee('Xe nháp');
    }

    public function test_khong_go_gi_thi_khong_bao_khong_tim_thay(): void
    {
        $this->get('/tim-kiem')
            ->assertOk()
            ->assertDontSee('Không tìm thấy');
    }

    public function test_go_tu_khoa_la_thi_bao_khong_tim_thay(): void
    {
        $this->get('/tim-kiem?q=zzzkhongcogi')->assertOk()->assertSee('Không tìm thấy');
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=SearchTest`
Expected: FAIL — 404

- [ ] **Step 3: Khai tiền tố route**

Trong `config/catalog.php`, mảng `routes`:

```php
'search'    => '/tim-kiem',
```

- [ ] **Step 4: Viết controller**

Create `app/Http/Controllers/Frontend/SearchController.php`:

```php
<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Tìm kiếm — /tim-kiem?q=...
 *
 * Cố ý làm mộc: LIKE trên vài cột chữ, gộp mặt hàng với bài viết. Chưa đủ
 * dữ liệu để đáng dựng full-text index hay gắn công cụ tìm kiếm ngoài; khi
 * nào chậm thật thì đổi, đừng đoán trước.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('frontend.search', [
            'q'        => $q,
            'products' => $q === '' ? collect() : $this->products($q),
            'posts'    => $q === '' ? collect() : $this->posts($q),
        ]);
    }

    protected function products(string $q)
    {
        return Catalog::query('product')
            ->published()
            ->with('category')
            ->where(fn (Builder $b) => $b
                ->where('name', 'like', "%{$q}%")
                ->orWhere('tagline', 'like', "%{$q}%"))
            ->orderBy('sort')
            ->limit(24)
            ->get();
    }

    protected function posts(string $q)
    {
        if (! catalog_feature('posts')) {
            return collect();
        }

        return Catalog::query('post')
            ->published()
            ->with('category')
            ->where(fn (Builder $b) => $b
                ->where('title', 'like', "%{$q}%")
                ->orWhere('excerpt', 'like', "%{$q}%"))
            ->latest('published_at')
            ->limit(12)
            ->get();
    }
}
```

- [ ] **Step 5: Khai route**

Trong `routes/web.php`, khối "Trang cố định":

```php
Route::get(trim(Url::prefix('search'), '/'), SearchController::class)->name('search');
```

Thêm `use App\Http\Controllers\Frontend\SearchController;`.

- [ ] **Step 6: Viết view**

Create `resources/views/frontend/search.blade.php`:

```blade
{{--
    Kết quả tìm kiếm. Chưa gõ gì thì chỉ hiện ô nhập, KHÔNG báo "không tìm
    thấy" — vừa vào trang đã bị báo lỗi là vô lý.

    Biến: $q · $products · $posts
--}}
@extends('frontend.layout', [
    'title' => filled($q) ? 'Tìm "'.$q.'"' : 'Tìm kiếm',
])

@section('content')
    <div class="wrap">
        <ol class="breadcrumb">
            <li><a href="{{ route('home') }}">Trang chủ</a></li>
            <li>Tìm kiếm</li>
        </ol>
    </div>

    <section class="block" style="padding-top:32px">
        <div class="wrap">
            <h1>Tìm kiếm</h1>

            <form class="search-box" method="GET" action="{{ route('search') }}" role="search">
                <label class="sr-only" for="search-q">Từ khoá</label>
                <input id="search-q" type="search" name="q" value="{{ $q }}"
                       placeholder="Tên xe, phụ kiện hoặc tin tức…" autofocus>
                <button class="btn" type="submit">Tìm</button>
            </form>

            @if (filled($q))
                @php $total = $products->count() + $posts->count(); @endphp

                <p class="search-count">
                    {{ $total }} kết quả cho “{{ $q }}”
                </p>

                @if ($total === 0)
                    <p class="empty">Không tìm thấy nội dung nào khớp. Thử từ khoá ngắn hơn.</p>
                @endif

                @if ($products->isNotEmpty())
                    <div class="section__head" style="margin-top:40px">
                        <h2>{{ catalog_label('product.plural') }}</h2>
                    </div>
                    <ul class="cards cards--3">
                        @each('frontend.partials.product-card', $products, 'product')
                    </ul>
                @endif

                @if ($posts->isNotEmpty())
                    <div class="section__head" style="margin-top:48px"><h2>Tin tức</h2></div>
                    <ul class="cards cards--3">
                        @each('frontend.partials.post-card', $posts, 'post')
                    </ul>
                @endif
            @endif
        </div>
    </section>
@endsection
```

- [ ] **Step 7: Chạy test — phải xanh**

Run: `php artisan test --filter=SearchTest`
Expected: PASS (5 test)

- [ ] **Step 8: Thêm ô tìm kiếm vào header**

Trong `resources/views/frontend/partials/header.blade.php`, trong `<div class="header__cta">`, thêm TRƯỚC số hotline:

```blade
@if (Route::has('search'))
    <form class="header__search" method="GET" action="{{ route('search') }}" role="search">
        <label class="sr-only" for="header-q">Tìm kiếm</label>
        <input id="header-q" type="search" name="q" placeholder="Tìm xe…">
    </form>
@endif
```

- [ ] **Step 9: Viết CSS**

Thêm vào `public/css/frontend.css`, trước mục `RESPONSIVE`:

```css
/* ═══════════ TÌM KIẾM ═══════════ */
.header__search input {
    width: 150px;
    height: 38px;
    padding: 0 14px;
    border: 1px solid var(--line);
    border-radius: 999px;
    font-family: inherit;
    font-size: 13.5px;
    color: var(--ink);
    background: var(--soft);
    outline: none;
    transition: width .2s ease, border-color .2s ease;
}
.header__search input:focus { width: 210px; border-color: var(--ink); background: #fff; }

.search-box { display: flex; gap: 12px; margin: 28px 0 8px; max-width: 620px; }
.search-box input {
    flex: 1;
    min-width: 0;
    height: 54px;
    padding: 0 20px;
    border: 1px solid var(--line-in);
    border-radius: 4px;
    font-family: inherit;
    font-size: 16px;
    color: var(--ink);
    outline: none;
}
.search-box input:focus { border-color: var(--accent); }
.search-count { margin-top: 20px; font-size: 14px; color: var(--muted-2); }
```

Và trong `@media (max-width: 960px)`, thêm `.header__search { display: none; }` — header trên mobile đã chật, ô tìm kiếm nằm ở trang `/tim-kiem`.

- [ ] **Step 10: Chạy toàn bộ test rồi commit**

```bash
vendor/bin/pint --dirty && php artisan test
git add app config resources routes public tests
git commit -m "Thêm tìm kiếm mặt hàng và bài viết

Frontend chưa từng có ô tìm kiếm nào. Sáu dòng xe thì chưa gắt, nhưng thêm
phụ kiện và tin tức vào là đã đủ nội dung để người ta muốn gõ tìm.

Cố ý làm mộc: LIKE trên tên/tagline/tiêu đề/tóm tắt, gộp hai loại nội dung.
Chưa đủ dữ liệu để đáng dựng full-text index hay gắn công cụ ngoài — khi nào
đo thấy chậm thật thì đổi.

Chưa gõ gì thì chỉ hiện ô nhập chứ không báo 'không tìm thấy' — vừa vào trang
đã bị báo lỗi là vô lý."
```

---

### Task 7: So sánh xe

**Bối cảnh:** khách phân vân giữa hai ba mẫu cần đặt thông số cạnh nhau. Dữ liệu `specs` đã đủ để dựng, chỉ thiếu màn hình.

Chọn xe qua query string (`?xe=vinfast-vf-7,vinfast-vf-9`) chứ không lưu session: link chia sẻ được, và không cần JS để "thêm vào so sánh".

**Files:**
- Create: `app/Http/Controllers/Frontend/CompareController.php`
- Create: `resources/views/frontend/compare.blade.php`
- Modify: `config/catalog.php` (`routes.compare`)
- Modify: `routes/web.php`
- Modify: `resources/views/frontend/partials/product-card.blade.php`
- Modify: `public/css/frontend.css`
- Test: `tests/Feature/CompareTest.php`

**Interfaces:**
- Produces: route tên `compare`, đường dẫn `/so-sanh`, nhận `?xe=slug1,slug2`
- Produces: view nhận `$cars` (Collection ≤ 3 sản phẩm), `$rows` (mảng `['label' => string, 'values' => array]`), `$all` (Collection để chọn thêm)

- [ ] **Step 1: Viết test thất bại**

Create `tests/Feature/CompareTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class CompareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::create([
            'name' => 'VinFast VF 7', 'status' => 'published', 'published_at' => now(),
            'price_from' => 799_000_000,
            'specs' => [['group' => 'Pin', 'rows' => [
                ['label' => 'Dung lượng pin', 'value' => '75,3 kWh'],
                ['label' => 'Quãng đường', 'value' => '496 km'],
            ]]],
        ]);

        Product::create([
            'name' => 'VinFast VF 9', 'status' => 'published', 'published_at' => now(),
            'price_from' => 1_491_000_000,
            'specs' => [['group' => 'Pin', 'rows' => [
                ['label' => 'Dung lượng pin', 'value' => '92 kWh'],
                ['label' => 'Số chỗ ngồi', 'value' => '7'],
            ]]],
        ]);
    }

    public function test_so_sanh_hai_xe_dat_thong_so_canh_nhau(): void
    {
        $this->get('/so-sanh?xe=vinfast-vf-7,vinfast-vf-9')
            ->assertOk()
            ->assertSee('VinFast VF 7')
            ->assertSee('VinFast VF 9')
            ->assertSee('75,3 kWh')
            ->assertSee('92 kWh')
            ->assertSee('Số chỗ ngồi');
    }

    public function test_thong_so_xe_kia_khong_co_thi_de_gach_ngang(): void
    {
        // VF 7 không khai "Số chỗ ngồi" nên ô của nó phải là dấu gạch,
        // không được đẩy giá trị của xe khác sang.
        $html = $this->get('/so-sanh?xe=vinfast-vf-7,vinfast-vf-9')->getContent();

        $this->assertStringContainsString('—', $html);
    }

    public function test_chua_chon_xe_thi_hien_danh_sach_de_chon(): void
    {
        $this->get('/so-sanh')
            ->assertOk()
            ->assertSee('Chọn xe để so sánh')
            ->assertSee('VinFast VF 7');
    }

    public function test_chi_nhan_toi_da_ba_xe(): void
    {
        Product::create(['name' => 'VinFast VF 8', 'status' => 'published', 'published_at' => now()]);
        Product::create(['name' => 'VinFast VF 6', 'status' => 'published', 'published_at' => now()]);

        $this->get('/so-sanh?xe=vinfast-vf-7,vinfast-vf-9,vinfast-vf-8,vinfast-vf-6')
            ->assertOk()
            ->assertDontSee('VinFast VF 6');
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `php artisan test --filter=CompareTest`
Expected: FAIL — 404

- [ ] **Step 3: Khai tiền tố route**

Trong `config/catalog.php`, mảng `routes`:

```php
'compare'   => '/so-sanh',
```

- [ ] **Step 4: Viết controller**

Create `app/Http/Controllers/Frontend/CompareController.php`:

```php
<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * So sánh xe — /so-sanh?xe=slug1,slug2
 *
 * Chọn xe bằng query string chứ không lưu session: link gửi cho khách được,
 * và không cần JS để "thêm vào so sánh".
 *
 * Tối đa 3 xe — quá số đó là bảng tràn ngang trên điện thoại và chẳng ai đọc.
 */
class CompareController extends Controller
{
    /** Nhiều hơn con số này thì bảng không còn đọc được trên điện thoại. */
    protected const MAX = 3;

    public function __invoke(Request $request): View
    {
        // Nhận cả hai dạng: chuỗi ngăn phẩy từ link chia sẻ (?xe=a,b) và mảng
        // từ form chọn xe ở ngay trên trang (?xe[]=a&xe[]=b).
        $raw = $request->query('xe');
        $raw = is_array($raw) ? implode(',', $raw) : (string) $raw;

        $slugs = collect(explode(',', $raw))
            ->map(fn (string $s) => trim($s))
            ->filter()
            ->unique()
            ->take(self::MAX);

        $cars = $slugs->isEmpty()
            ? collect()
            : Catalog::query('product')
                ->published()
                ->whereIn('slug', $slugs->all())
                ->with('category')
                ->get()
                // Giữ đúng thứ tự khách gõ trên URL, không theo thứ tự DB.
                ->sortBy(fn ($p) => $slugs->search($p->slug))
                ->values();

        return view('frontend.compare', [
            'cars' => $cars,
            'rows' => $this->rows($cars),
            'all'  => Catalog::query('product')
                ->published()
                ->notInCategory(config('catalog.frontend.accessory_category'))
                ->orderBy('sort')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Gộp nhãn thông số của mọi xe thành một danh sách dòng, giữ thứ tự xuất
     * hiện. Xe nào không khai nhãn đó thì ô để trống — KHÔNG được đẩy giá trị
     * của xe khác sang, đó là cách bảng so sánh nói dối.
     *
     * @return array<int, array{label: string, values: array<int, ?string>}>
     */
    protected function rows($cars): array
    {
        $labels = [];

        foreach ($cars as $car) {
            foreach ((array) $car->specs as $group) {
                foreach ($group['rows'] ?? [] as $row) {
                    $label = trim((string) ($row['label'] ?? ''));
                    if ($label !== '' && ! in_array($label, $labels, true)) {
                        $labels[] = $label;
                    }
                }
            }
        }

        return array_map(fn (string $label) => [
            'label'  => $label,
            'values' => $cars->map(fn ($car) => $this->valueFor($car, $label))->all(),
        ], $labels);
    }

    protected function valueFor($car, string $label): ?string
    {
        foreach ((array) $car->specs as $group) {
            foreach ($group['rows'] ?? [] as $row) {
                if (trim((string) ($row['label'] ?? '')) === $label) {
                    return $row['value'] ?? null;
                }
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: Khai route**

Trong `routes/web.php`, khối "Trang cố định":

```php
Route::get(trim(Url::prefix('compare'), '/'), CompareController::class)->name('compare');
```

Thêm `use App\Http\Controllers\Frontend\CompareController;`.

- [ ] **Step 6: Viết view**

Create `resources/views/frontend/compare.blade.php`:

```blade
{{--
    So sánh xe. Bảng cuộn ngang trong khung riêng, không đẩy cả trang trôi.

    Ô nào xe không khai thì để dấu gạch — không mượn giá trị của xe bên cạnh.

    Biến: $cars · $rows · $all
--}}
@extends('frontend.layout', ['title' => 'So sánh xe'])

@section('content')
    <div class="wrap">
        <ol class="breadcrumb">
            <li><a href="{{ route('home') }}">Trang chủ</a></li>
            <li>So sánh</li>
        </ol>
    </div>

    <section class="block" style="padding-top:32px">
        <div class="wrap">
            <h1>So sánh xe</h1>

            <form class="compare-pick" method="GET" action="{{ route('compare') }}">
                <span class="field__label">Chọn xe để so sánh (tối đa 3)</span>

                <div class="compare-pick__grid">
                    @foreach ($all as $car)
                        <label class="pick {{ $cars->contains('slug', $car->slug) ? 'is-on' : '' }}">
                            <input type="checkbox" name="xe[]" value="{{ $car->slug }}"
                                   @checked($cars->contains('slug', $car->slug))>
                            <b>{{ $car->name }}</b>
                        </label>
                    @endforeach
                </div>

                <button class="btn btn--sm" type="submit">So sánh</button>
            </form>

            @if ($cars->count() < 2)
                <p class="empty">Chọn ít nhất hai xe để đặt thông số cạnh nhau.</p>
            @else
                <div class="scroll-x compare-wrap">
                    <table class="compare">
                        <thead>
                            <tr>
                                <th scope="col">Thông số</th>
                                @foreach ($cars as $car)
                                    <th scope="col">
                                        <a href="{{ route('products.show', $car->slug) }}">{{ $car->name }}</a>
                                        @if ($car->price_from)
                                            <span>Từ {{ catalog_money_short($car->price_from) }}</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <th scope="row">{{ $row['label'] }}</th>
                                    @foreach ($row['values'] as $value)
                                        <td>{{ filled($value) ? $value : '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
```

- [ ] **Step 7: Chạy test — phải xanh**

Run: `php artisan test --filter=CompareTest`
Expected: PASS (4 test)

- [ ] **Step 8: Viết CSS**

Thêm vào `public/css/frontend.css`, trước mục `RESPONSIVE`:

```css
/* ═══════════ SO SÁNH XE ═══════════ */
.compare-pick { margin: 28px 0 40px; }
.compare-pick__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin: 12px 0 20px;
}

/* Bảng cuộn trong khung của nó, không đẩy cả trang trôi ngang. */
.scroll-x { overflow-x: auto; }
.compare { border-collapse: collapse; width: 100%; min-width: 640px; }
.compare th, .compare td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    font-size: 15px;
    vertical-align: top;
}
.compare thead th { border-bottom: 1px solid var(--ink); vertical-align: bottom; }
.compare thead th a { font-size: 18px; font-weight: 700; color: var(--ink); display: block; }
.compare thead th a:hover { color: var(--accent); }
.compare thead th span { display: block; font-size: 13px; color: var(--muted-2); margin-top: 4px; font-weight: 400; }
.compare tbody th { font-weight: 400; color: var(--muted); width: 30%; }
.compare tbody td { color: var(--ink); font-weight: 600; font-variant-numeric: tabular-nums; }
```

- [ ] **Step 9: Thêm nút so sánh vào thẻ xe**

Trong `resources/views/frontend/partials/product-card.blade.php`, trong `.card__actions`, thêm sau nút "Xem chi tiết":

```blade
@if (Route::has('compare'))
    <a class="btn btn--sm btn--outline" href="{{ route('compare', ['xe' => $product->slug]) }}">So sánh</a>
@endif
```

- [ ] **Step 10: Chạy toàn bộ test rồi commit**

```bash
vendor/bin/pint --dirty && php artisan test
git add app config resources routes public tests
git commit -m "Thêm trang so sánh xe

Khách phân vân giữa hai ba mẫu cần đặt thông số cạnh nhau; dữ liệu specs đã
đủ để dựng, chỉ thiếu màn hình.

Chọn xe qua query string chứ không lưu session: link gửi cho khách được và
không cần JS để 'thêm vào so sánh'. Tối đa 3 xe, quá số đó bảng không còn đọc
được trên điện thoại.

Nhãn thông số gộp từ mọi xe, giữ thứ tự xuất hiện; xe nào không khai nhãn đó
thì ô để dấu gạch chứ không mượn giá trị của xe bên cạnh — đó là cách bảng so
sánh nói dối."
```

---

## Sau khi xong bảy task

- [ ] Chạy `php artisan test` — toàn bộ phải xanh
- [ ] Soi bằng mắt ở 1440px và 390px: `/`, `/san-pham/vinfast-vf-7`, `/he-thong-dai-ly`, `/tim-kiem?q=vf`, `/so-sanh?xe=vinfast-vf-7,vinfast-vf-9`
- [ ] Kiểm không trang nào tràn ngang (`scrollWidth === clientWidth`)
- [ ] Kiểm popup: hiện đúng giờ, Esc đóng được, đóng rồi tải lại không hiện nữa
- [ ] Cập nhật `CatalogDemoSeeder` thêm dữ liệu mẫu cho banner và đại lý, để `php artisan db:seed` ra site đầy đủ
- [ ] `git push origin main`

## Cố ý KHÔNG làm

| Hạng mục | Lý do |
|---|---|
| Phân quyền | Bạn yêu cầu bỏ |
| Tính lăn bánh | Bộ phận nhân sự khác quản lý, không thuộc web bán hàng |
| Xe sẵn kho | Xe nào cũng có sẵn nên không cần đánh dấu còn/hết |
| Đa ngôn ngữ | Chỉ bán trong nước |
| Nhật ký thao tác | Đi kèm phân quyền, mà phân quyền đã bỏ |

<?php

namespace Database\Seeders\Brands;

use App\Media\MediaStore;
use App\Support\Catalog;
use App\Support\Url;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Khung seeder cho một hãng xe.
 *
 * Lớp con chỉ khai DỮ LIỆU (tên xe, giá, thông số) — không viết lại vòng lặp
 * tạo bản ghi, không tự dựng `sections`. Nhờ vậy thêm hãng thứ năm vẫn không
 * loạn: mọi hãng cùng một hình dạng dữ liệu, cùng một thứ tự mục.
 *
 * Xem `database/seeders/Brands/README.md` để biết cách thêm hãng mới, và
 * `MauSeeder.php` là bản mẫu copy về sửa.
 *
 * Chạy lại được nhiều lần: mọi thứ dùng updateOrCreate theo slug.
 */
abstract class BrandSeeder extends Seeder
{
    /** Tên hãng, hiện trong menu và dùng để đặt thư mục ảnh. */
    abstract protected function brand(): string;

    /**
     * Danh mục của hãng: slug => tên.
     *
     * @return array<string, string>
     */
    abstract protected function categories(): array;

    /**
     * Danh sách xe. Hình dạng từng phần tử xem README + MauSeeder.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function products(): array;

    /** Khoá form đặt ở cuối mỗi trang xe. null = không nhúng form. */
    protected function formKey(): ?string
    {
        return 'dat-lich-lai-thu';
    }

    public function run(): void
    {
        $categories = $this->seedCategories();

        foreach ($this->products() as $sort => $data) {
            $product = $this->seedProduct($data, $categories, $sort);

            $this->seedVariants($product, $data);
            $this->seedColors($product, $data);
        }

        $this->seedMenu();
    }

    // ── Danh mục ─────────────────────────────────────────────────────────

    /** @return array<string, int> slug => id */
    protected function seedCategories(): array
    {
        $ids = [];
        $sort = 0;

        foreach ($this->categories() as $slug => $name) {
            $ids[$slug] = Catalog::query('category')->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'sort' => ++$sort],
            )->id;
        }

        return $ids;
    }

    // ── Xe ───────────────────────────────────────────────────────────────

    /** @param array<string, int> $categories */
    protected function seedProduct(array $data, array $categories, int $sort): Model
    {
        return Catalog::query('product')->updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'tagline' => $data['tagline'] ?? null,
                'category_id' => $categories[$data['category'] ?? ''] ?? null,
                'price_from' => $data['price_from'] ?? null,
                'status' => 'published',
                'published_at' => now(),
                'sort' => $sort,
                'hero' => $this->hero($data),
                'highlights' => $data['highlights'] ?? null,
                'sections' => $this->sections($data),
                'specs' => $this->specs($data),
                'spec_notes' => $this->specNotes($data),
                'seo' => $data['seo'] ?? ['title' => $data['name']],
            ],
        );
    }

    protected function seedVariants(Model $product, array $data): void
    {
        $product->variants()->delete();

        foreach (array_values($data['variants'] ?? []) as $i => $variant) {
            $product->variants()->create([
                'name' => $variant['name'],
                'price' => $variant['price'] ?? null,
                'price_original' => $variant['price_original'] ?? null,
                'note' => $variant['note'] ?? null,
                'is_default' => $variant['is_default'] ?? ($i === 0),
                'sort' => $i + 1,
                // Đặc thù xe điện — chỉ MauSeeder/hãng xe điện mới khai, xe
                // xăng dầu bỏ trống thì calculator ở trang chi tiết tự ẩn.
                'battery_kwh' => $variant['battery_kwh'] ?? null,
                'range_km' => $variant['range_km'] ?? null,
            ]);
        }
    }

    protected function seedColors(Model $product, array $data): void
    {
        $product->options()->delete();

        $sort = 0;

        foreach ($data['colors'] ?? [] as $name => $hex) {
            $product->options()->create([
                'name' => $name,
                'hex' => $hex,
                'sort' => ++$sort,
            ]);
        }
    }

    // ── Dựng `sections` theo một thứ tự cố định ──────────────────────────

    /**
     * Thứ tự mục giống nhau cho mọi xe, mọi hãng:
     *   ảnh → chữ → video → bảng → form
     *
     * Mục nào không khai dữ liệu thì không sinh ra — đúng quy tắc "ô trống
     * thì không render" của tài liệu kiến trúc.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function sections(array $data): array
    {
        $sections = [];

        foreach ($data['media'] ?? [] as $title => $block) {
            $sections[] = [
                'title' => $title,
                'intro' => $block['intro'] ?? '',
                'type' => 'media',
                'layout' => $block['layout'] ?? 'cols-3',
                'items' => $this->mediaItems($data['slug'], $title, $block['items'] ?? []),
            ];
        }

        foreach ($data['story'] ?? [] as $title => $body) {
            $sections[] = ['title' => $title, 'type' => 'text', 'body' => $body];
        }

        if (filled($data['video'] ?? null)) {
            $sections[] = [
                'title' => $data['video_title'] ?? 'Phim giới thiệu',
                'type' => 'video',
                'video_url' => $data['video'],
            ];
        }

        foreach ($data['tables'] ?? [] as $title => $rows) {
            $sections[] = [
                'title' => $title,
                'type' => 'table',
                'rows' => collect($rows)
                    ->map(fn ($value, $label) => ['label' => $label, 'value' => $value])
                    ->values()
                    ->all(),
            ];
        }

        if ($this->formKey() && ($data['form'] ?? true)) {
            $sections[] = [
                'title' => 'Đăng ký lái thử',
                'intro' => 'Để lại thông tin, tư vấn viên sẽ liên hệ lại.',
                'type' => 'form',
                'form_key' => $this->formKey(),
            ];
        }

        return $sections;
    }

    /**
     * Mỗi nhãn ảnh trong dữ liệu → một item kèm ảnh placeholder tự sinh.
     * Ảnh thật thì upload đè trong admin, đường dẫn giữ nguyên.
     *
     * @param  array<int, string>  $labels
     * @return array<int, array<string, string>>
     */
    protected function mediaItems(string $slug, string $sectionTitle, array $labels): array
    {
        $items = [];
        $group = Str::slug($sectionTitle);

        foreach (array_values($labels) as $i => $label) {
            $path = "catalog/{$this->brandSlug()}/{$slug}/{$group}-".($i + 1).'.jpg';

            $this->placeholder($path, $label ?: $sectionTitle);

            $items[] = [
                'image' => $path,
                // Mục "Thư viện" cố ý để trống nhãn — chỉ quăng ảnh vào, đúng
                // ví dụ trong tài liệu kiến trúc.
                'label' => $group === 'thu-vien' ? '' : $label,
                'desc' => '',
            ];
        }

        return $items;
    }

    protected function hero(array $data): ?array
    {
        if (blank($data['hero'] ?? null)) {
            return null;
        }

        $path = "catalog/{$this->brandSlug()}/{$data['slug']}/hero.jpg";

        $this->placeholder($path, $data['name'], 1920, 1080);

        // Cột `hero` là json tự do nên gánh luôn phần chữ ĐẦU TRANG chi tiết,
        // khỏi đẻ thêm cột cho ba câu marketing:
        //   lede        — đoạn dưới tiêu đề hero (tagline đã lên làm h1)
        //   intro_title — tiêu đề khối mở đầu căn giữa ngay dưới hero
        //   intro_body  — đoạn của khối đó
        // Thiếu câu nào thì view lùi về mô tả SEO hoặc bỏ hẳn khối.
        return array_filter([
            'type' => 'image',
            'src' => $path,
            'lede' => $data['hero_lede'] ?? null,
            'intro_title' => $data['intro_title'] ?? null,
            'intro_body' => $data['intro_body'] ?? null,
        ]);
    }

    /**
     * `specs` từ mảng lồng: ['Nhóm' => ['nhãn' => 'giá trị']]
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function specs(array $data): ?array
    {
        if (blank($data['specs'] ?? null)) {
            return null;
        }

        $groups = collect($data['specs'])
            ->map(fn (array $rows, string $group) => [
                'group' => $group,
                'rows' => collect($rows)
                    ->map(fn ($value, $label) => ['label' => $label, 'value' => $value])
                    ->values()
                    ->all(),
            ])
            ->values();

        return $groups->all();
    }

    /**
     * Ghi chú dưới bảng thông số: ['Tiêu đề' => 'Nội dung'] → mảng label/body.
     *
     * Nằm ở cột riêng chứ không trộn vào `specs` — trộn vào thì chúng hiện lên
     * repeater thông số trong admin như một nhóm bình thường, người nhập sửa
     * nhầm là hỏng.
     *
     * @return array<int, array<string, string>>|null
     */
    protected function specNotes(array $data): ?array
    {
        if (blank($data['spec_notes'] ?? null)) {
            return null;
        }

        return collect($data['spec_notes'])
            ->map(fn ($body, $title) => ['label' => $title, 'body' => $body])
            ->values()
            ->all();
    }

    // ── Menu ─────────────────────────────────────────────────────────────

    /**
     * Gắn từng xe thành mục con dưới mục menu trỏ tới danh sách mặt hàng.
     * Menu header chưa dựng thì bỏ qua — CatalogDemoSeeder lo phần khung.
     */
    protected function seedMenu(): void
    {
        $menu = Catalog::query('menu')->where('key', 'header')->first();

        if (! $menu) {
            return;
        }

        $parent = $menu->items()
            ->whereNull('parent_id')
            ->where('url', Url::prefix('product'))
            ->first();

        if (! $parent) {
            return;
        }

        $parent->children()->delete();

        foreach ($this->products() as $sort => $data) {
            $product = Catalog::query('product')->where('slug', $data['slug'])->first();

            if ($product) {
                $parent->children()->create([
                    // Gán menu_id thẳng, KHÔNG dựa vào hook `saving` của MenuItem:
                    // DatabaseSeeder dùng WithoutModelEvents nên hook đó không chạy
                    // khi seed cả bộ, và cột này NOT NULL.
                    'menu_id' => $menu->id,
                    'label' => $product->name,
                    'target_type' => 'product',
                    'target_id' => $product->id,
                    'sort' => $sort + 1,
                ]);
            }
        }
    }

    // ── Ảnh placeholder ──────────────────────────────────────────────────

    protected function brandSlug(): string
    {
        return Str::slug($this->brand());
    }

    /**
     * Sinh một ảnh xám có chữ để trang demo không đầy ảnh vỡ.
     *
     * Chỉ tạo khi file CHƯA có — upload ảnh thật trong admin rồi thì chạy lại
     * seeder cũng không đè mất.
     */
    protected function placeholder(string $path, string $label, int $w = 1600, int $h = 900): void
    {
        $media = app(MediaStore::class);

        if ($media->exists($path) || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $image = imagecreatetruecolor($w, $h);

        imagefill($image, 0, 0, imagecolorallocate($image, 226, 229, 233));
        imagefilledrectangle($image, 0, $h - 8, $w, $h, imagecolorallocate($image, 122, 31, 43));

        // Font dựng sẵn của GD không có dấu tiếng Việt → bỏ dấu cho khỏi vỡ chữ.
        $text = Str::upper(Str::ascii($label));
        $font = 5;
        $x = max(20, (int) (($w - imagefontwidth($font) * strlen($text)) / 2));
        $y = (int) (($h - imagefontheight($font)) / 2);

        imagestring($image, $font, $x, $y, $text, imagecolorallocate($image, 90, 96, 104));

        ob_start();
        imagejpeg($image, null, 82);
        $media->write($path, (string) ob_get_clean());
    }
}

<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Người dùng xác nhận 05/11/2026: GX 550 cửa trời đơn 6,4 tỷ, cửa trời toàn
 * cảnh 6,45 tỷ; RX 350h và GX 550 có sẵn nhiều màu, thay đổi theo ngày.
 *
 * Bản cũ thành "cửa trời toàn cảnh" và giữ slug gx-550 (quảng cáo, Google đang
 * trỏ tới). Đổi chữ giá viết sẵn trong xe, trang, bài. Chỉ thay khi còn đúng
 * bản cũ; chạy lại không đổi thêm.
 */
return new class extends Migration
{
    public const STOCK_NOTE = 'nhiều màu, thay đổi theo ngày';

    private const OLD_RX_NOTICE = 'Bạn đang tìm Lexus RX 350? Bản RX đang bán tại Lexus Thăng Long là RX 350h hybrid 2.5L '
        .'(Premium và Luxury) và RX 500h F SPORT Performance. Giá niêm yết và lăn bánh từng bản ngay bên dưới.';

    private const ASK_COLOR = 'có sẵn nhiều màu, thay đổi theo ngày: hỏi Thu Hà màu bạn thích còn xe không.';

    private const GX_NOTICE = 'GX 550 có hai bản: cửa trời đơn và cửa trời toàn cảnh. Có sẵn nhiều màu, thay đổi theo ngày: hỏi Thu Hà màu bạn thích còn xe không.';

    // Câu dài trước, câu ngắn sau: câu ngắn nằm trong câu dài.
    private const TEXT = [
        'Lexus GX 550 giá niêm yết 6,45 tỷ đồng (đã gồm VAT). Lăn bánh tại Hà Nội tạm tính khoảng 7,24 tỷ đồng.'
            => 'Lexus GX 550 giá niêm yết 6,4 tỷ đồng bản cửa trời đơn, 6,45 tỷ đồng bản cửa trời toàn cảnh (đã gồm VAT). Lăn bánh tại Hà Nội tạm tính khoảng 7,18 và 7,24 tỷ đồng.',
        'Giá niêm yết 6,45 tỷ đồng (đã gồm VAT); lăn bánh tại Hà Nội tạm tính khoảng 7,24 tỷ đồng.'
            => 'Bản cửa trời đơn 6,4 tỷ đồng, bản cửa trời toàn cảnh 6,45 tỷ đồng (đã gồm VAT); lăn bánh tại Hà Nội tạm tính khoảng 7,18 và 7,24 tỷ đồng.',
        'Giá Lexus GX 550 2026 tại Hà Nội: 6,45 tỷ đồng, lăn bánh khoảng 7,24 tỷ'
            => 'Giá Lexus GX 550 2026 tại Hà Nội: từ 6,4 tỷ đồng, lăn bánh từ khoảng 7,18 tỷ',
        'Lexus GX 550: Giá niêm yết 6.450.000.000 đ (lăn bánh Hà Nội tạm tính khoảng 7,24 tỷ đồng)'
            => 'Lexus GX 550: Giá niêm yết từ 6.400.000.000 đ (lăn bánh Hà Nội tạm tính từ khoảng 7,18 tỷ đồng)',
        'Lexus GX 550 2026: giá 6,45 tỷ, thông số' => 'Lexus GX 550 2026: giá từ 6,4 tỷ, thông số',
        '6.450.000.000 đ — GX 550'     => 'từ 6.400.000.000 đ — 2 bản GX 550',
        'GX 550 giá 6,45 tỷ'           => 'GX 550 giá từ 6,4 tỷ',
        'GX 550 6,45 tỷ'               => 'GX 550 từ 6,4 tỷ',
        'GX 550 (6,45 tỷ đồng)'        => 'GX 550 (từ 6,4 tỷ đồng)',
        'GX 550 (6,45 tỷ)'             => 'GX 550 (từ 6,4 tỷ)',
        'GX 550: 6,45 tỷ'              => 'GX 550: 6,4 – 6,45 tỷ',
        'GX 550: khoảng 7,24 tỷ'       => 'GX 550: khoảng 7,18 – 7,24 tỷ',
        'Bốn lựa chọn khung gầm rời'   => 'Năm lựa chọn khung gầm rời',
        '12 phiên bản'                 => '13 phiên bản',
        'mát-xa; cửa sổ trời toàn cảnh.' => 'mát-xa; cửa sổ trời đơn hoặc toàn cảnh tuỳ bản.',
    ];

    private const FIELDS = ['title', 'seo_title', 'excerpt', 'tagline', 'keywords', 'sections', 'seo', 'highlights', 'specs', 'interior'];

    public function up(): void
    {
        $gx = Product::query()->where('slug', 'gx')->first();
        $old = $gx?->variants()->where('name', 'GX 550')->first();

        if ($old && (int) $old->price === 6_450_000_000) {
            $old->update(['name' => 'GX 550 cửa trời toàn cảnh', 'sort' => 2]);
            $gx->variants()->create(['name' => 'GX 550 cửa trời đơn', 'slug' => 'gx-550-cua-troi-don', 'image' => $old->image,
                'price' => 6_400_000_000, 'note' => $old->note, 'sort' => 1]);

            foreach ([Product::query(), Page::query(), Post::query()] as $query) {
                $query->get()->each(fn (Model $model) => $this->rewrite($model));
            }
        }

        ProductVariant::query()
            ->whereIn('name', ['GX 550 cửa trời đơn', 'GX 550 cửa trời toàn cảnh', 'RX 350h Premium', 'RX 350h Luxury'])
            ->whereNull('availability')
            ->get()
            ->each(fn (ProductVariant $variant) => $variant->update(['availability' => 'in_stock', 'availability_note' => self::STOCK_NOTE]));

        Product::query()->where('slug', 'rx')->where('notice', self::OLD_RX_NOTICE)->get()
            ->each(fn (Product $rx) => $rx->forceFill(['notice' => self::OLD_RX_NOTICE.' RX 350h '.self::ASK_COLOR])->saveQuietly());

        $gx = Product::query()->where('slug', 'gx')->first();
        if ($gx && blank($gx->notice)) {
            $gx->forceFill(['notice' => self::GX_NOTICE])->saveQuietly();
        }
    }

    public function down(): void
    {
        // Không hoàn tác: giá do người dùng xác nhận.
    }

    private function walk(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->walk($v), $value);
        }
        if ($value === '6,45 tỷ') {
            return '6,4 – 6,45 tỷ'; // nhãn thẻ GX trong bài so sánh
        }

        return is_string($value) ? strtr($value, self::TEXT) : $value;
    }

    private function rewrite(Model $model): void
    {
        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $model->getAttributes())) {
                continue;
            }
            $old = $model->getAttribute($field);
            if ($old !== null && ($new = $this->walk($old)) !== $old) {
                $model->setAttribute($field, $new);
            }
        }

        if ($model->isDirty()) {
            $model->saveQuietly();
        }
    }
};

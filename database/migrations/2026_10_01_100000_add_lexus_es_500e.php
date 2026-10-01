<?php

use App\Media\MediaStore;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Đại lý bán lại ES 500e từ tháng 10/2026 — giá 2,98 tỷ (chủ website xác
 * nhận 01/10/2026). Thêm phiên bản vào dòng ES và sửa các câu "2 phiên bản"
 * mà seeder đã đặt. Câu nào đã sửa tay trong admin (khác bản gốc) thì giữ.
 * Chạy lại không nhân đôi phiên bản hay câu hỏi.
 */
return new class extends Migration
{
    private const VARIANT = 'ES 500e';

    private const IMAGE = 'catalog/lexus/es/360/trang/04.webp';

    private const TAGLINE = ['Sedan hạng sang thế hệ mới, hybrid 350h', 'Sedan hạng sang thế hệ mới: hybrid 350h và thuần điện 500e'];

    private const SEO_DESCRIPTION = [
        'Giá Lexus ES 350h 2026 tại Hà Nội: Premium 2,36 tỷ, Luxury 2,58 tỷ. Sedan hybrid thế hệ mới, '
            .'màn hình lớn, an toàn LSS+. Xem màu và đăng ký lái thử tại Lexus Thăng Long.',
        'Giá Lexus ES 2026 tại Hà Nội: 350h Premium 2,36 tỷ, 350h Luxury 2,58 tỷ, ES 500e thuần điện 2,98 tỷ (miễn trước bạ). Lái thử tại Lexus Thăng Long.',
    ];

    private const PRICE_FAQ = [
        'Lexus ES thế hệ mới có 2 phiên bản hybrid: ES 350h Premium 2,36 tỷ đồng và ES 350h Luxury 2,58 tỷ đồng '
            .'(giá niêm yết, đã gồm VAT). Lăn bánh tại Hà Nội tạm tính khoảng 2,66 tỷ và 2,90 tỷ đồng.',
        'Lexus ES thế hệ mới có 3 phiên bản: ES 350h Premium 2,36 tỷ đồng, ES 350h Luxury 2,58 tỷ đồng (hybrid) '
            .'và ES 500e 2,98 tỷ đồng (thuần điện), giá niêm yết đã gồm VAT. Lăn bánh tại Hà Nội tạm tính khoảng '
            .'2,66 tỷ, 2,90 tỷ và 2,99 tỷ đồng — ES 500e được miễn lệ phí trước bạ.',
    ];

    private const EV_QUESTION = 'Lexus ES 500e có gì khác ES 350h?';

    private const EV_ANSWER = 'ES 500e là bản thuần điện chạy pin: không dùng xăng, sạc điện. ES 350h là hybrid, không cần sạc. '
        .'ES 500e được miễn lệ phí trước bạ đến hết năm 2030, nên dù giá niêm yết cao hơn ES 350h Luxury 400 triệu đồng, '
        .'chi phí lăn bánh tại Hà Nội chỉ chênh khoảng 90 triệu đồng.';

    /**
     * Câu do seeder đặt trong trang tĩnh và bài viết (bảng giá, hỏi đáp…):
     * thay đúng đoạn cũ, đoạn đã sửa tay thì không khớp nên giữ nguyên.
     */
    private const TEXT = [
        '11 phiên bản' => '12 phiên bản',
        'Hai phiên bản hybrid: ES 350h Premium 2,36 tỷ đồng và ES 350h Luxury 2,58 tỷ đồng.' =>
            'Ba phiên bản: hai bản hybrid ES 350h Premium 2,36 tỷ đồng, ES 350h Luxury 2,58 tỷ đồng và bản thuần điện ES 500e 2,98 tỷ đồng.',
        'từ 2.360.000.000 đ — ES 350h Premium và ES 350h Luxury' =>
            'từ 2.360.000.000 đ — ES 350h Premium, ES 350h Luxury và ES 500e thuần điện',
        'khoang sau rộng, êm và tiết kiệm; hợp đi phố và đưa đón gia đình.</p>' =>
            'khoang sau rộng, êm và tiết kiệm; hợp đi phố và đưa đón gia đình.</p>'
            .'<p>ES 500e thuần điện (2,98 tỷ) được miễn lệ phí trước bạ: lăn bánh Hà Nội khoảng 2,99 tỷ, '
            .'chỉ hơn ES 350h Luxury khoảng 90 triệu đồng.</p>',
    ];

    public function up(): void
    {
        $es = Product::query()->where('slug', 'es')->first();
        if (! $es) {
            return;
        }

        if (! $es->variants()->where('name', self::VARIANT)->exists()) {
            $es->variants()->create([
                'name' => self::VARIANT,
                'price' => 2_980_000_000,
                'note' => 'Thuần điện · miễn lệ phí trước bạ',
                'sort' => (int) $es->variants()->max('sort') + 1,
                'is_default' => false,
                'image' => app(MediaStore::class)->exists(self::IMAGE) ? self::IMAGE : null,
            ]);
        }

        $changes = [];

        if ($es->tagline === self::TAGLINE[0]) {
            $changes['tagline'] = self::TAGLINE[1];
        }

        $highlights = $es->highlights ?? [];
        foreach ($highlights as $i => $item) {
            if (($item['value'] ?? null) === '2' && ($item['label'] ?? null) === 'Premium · Luxury') {
                $highlights[$i] = ['value' => '3', 'unit' => 'phiên bản', 'label' => 'Hybrid 350h · thuần điện 500e'];
                $changes['highlights'] = $highlights;
            }
        }

        $seo = $es->seo ?? [];
        if (($seo['description'] ?? null) === self::SEO_DESCRIPTION[0]) {
            $changes['seo'] = ['description' => self::SEO_DESCRIPTION[1]] + $seo;
        }

        $sections = $es->sections ?? [];
        foreach ($sections as $s => $section) {
            if (($section['type'] ?? null) !== 'faq') {
                continue;
            }
            $rows = $section['rows'] ?? [];
            $hasEv = collect($rows)->contains(fn ($r) => ($r['label'] ?? null) === self::EV_QUESTION);

            foreach ($rows as $r => $row) {
                if (($row['value'] ?? null) === self::PRICE_FAQ[0]) {
                    $rows[$r]['value'] = self::PRICE_FAQ[1];
                    if (! $hasEv) {
                        array_splice($rows, $r + 1, 0, [['label' => self::EV_QUESTION, 'value' => self::EV_ANSWER]]);
                        $hasEv = true;
                    }
                    $sections[$s]['rows'] = $rows;
                    $changes['sections'] = $sections;
                    break;
                }
            }
        }

        if ($changes) {
            $es->update($changes);
        }

        foreach ([Post::query(), Page::query()] as $query) {
            $query->get()->each(fn ($model) => $this->replaceText($model));
        }
    }

    public function down(): void
    {
        // Không xoá phiên bản: lead đã gửi có thể trỏ vào ES 500e.
    }

    /** Thay chữ trong tiêu đề, tóm tắt, SEO và mọi khối nội dung (JSON lồng nhau). */
    private function replaceText(Model $model): void
    {
        $walk = function ($value) use (&$walk) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }
            // Đã thay rồi (ES 500e có mặt) thì bỏ qua — chạy lại không nhân đôi đoạn chèn.
            if (! is_string($value) || str_contains($value, 'ES 500e')) {
                return $value;
            }

            return strtr($value, self::TEXT);
        };

        foreach (['title', 'excerpt', 'sections', 'seo'] as $field) {
            $old = $model->getAttribute($field);
            if ($old !== null && ($new = $walk($old)) !== $old) {
                $model->setAttribute($field, $new);
            }
        }

        if ($model->isDirty()) {
            $model->saveQuietly();
        }
    }
};

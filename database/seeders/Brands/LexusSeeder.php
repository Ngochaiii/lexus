<?php

namespace Database\Seeders\Brands;

use App\Media\MediaStore;
use Illuminate\Database\Eloquent\Model;

/**
 * Bảy dòng xe Lexus đang bán tại Lexus Thăng Long: RX · ES · NX · LX · GX · LM · LS.
 *
 *   php artisan db:seed --class="Database\Seeders\Brands\LexusSeeder"
 *   php artisan catalog:images        # sinh srcset + manifest kích thước
 *
 * ẢNH: lấy từ database/seeders/media/lexus/{slug}/ — bộ ảnh đã chọn lọc và
 * nén webp từ dự án Car-project bằng `import_from_car_project.py` (cùng thư
 * mục). Đã bỏ: ảnh trùng nhau, trang brochure có chữ, ảnh của tư vấn viên
 * khác, ảnh có watermark bên thứ ba. Seeder copy vào kho media ở
 * catalog/lexus/{slug}/; chạy lại thì chỉ ghi đè khi nội dung ảnh đổi.
 *
 * GIÁ: theo TRANG CHỦ Car-project (chủ website chốt 24/9/2026 — trang chi
 *   tiết Car-project còn giá cũ): LM 500h 6 chỗ 7,21 tỷ; GX đặt tên GX 550M
 *   (6,40 tỷ) / GX 550 (6,45 tỷ); ES "đang cập nhật" → price null, trang hiện
 *   "Đang cập nhật". NX 350 F SPORT không có ở Car-project → 3,13 tỷ (giá
 *   hãng). Đổi giá: sửa trong admin, hoặc sửa ở đây rồi seed lại.
 * Phiên bản bị comment trong nguồn (NX 350h 2,34 tỷ trùng tên, LS 500 7,03
 * tỷ) không đưa lên.
 *
 * THÔNG SỐ: chỉ đăng phiên bản có số liệu khớp đời xe hiện hành. ES thế hệ
 * mới chưa có bảng thông số tin cậy nên cố ý để trống — trang tự ẩn khối đó.
 *
 * FAQ: mỗi xe một khối hỏi đáp trả lời thẳng câu hỏi hay gặp (giá, phiên
 * bản, lăn bánh, mua ở đâu). Vừa giúp khách, vừa là đoạn văn mà Google và các
 * công cụ AI trích dẫn được nguyên câu (JSON-LD FAQPage sinh từ đây).
 */
class LexusSeeder extends BrandSeeder
{
    private const DEALER = 'Lexus Thăng Long';

    private const ADDRESS = 'ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội';

    protected function brand(): string
    {
        return 'Lexus';
    }

    /**
     * Slug danh mục phải khớp `data-category` của thẻ xe ở trang danh sách,
     * vì bộ lọc chạy bằng CSS `:has()` chứ không phải JS.
     */
    protected function categories(): array
    {
        return [
            'suv'   => 'SUV',
            'sedan' => 'Sedan',
            'mpv'   => 'MPV',
        ];
    }

    /** Form cuối trang do config('catalog.frontend.product_forms') lo. */
    protected function formKey(): ?string
    {
        return null;
    }

    // ── Ảnh ──────────────────────────────────────────────────────────────

    /** Copy một ảnh đã chọn vào kho media, trả đường dẫn trong kho. */
    private function image(string $slug, string $name): ?string
    {
        $source = database_path("seeders/media/lexus/{$slug}/{$name}.webp");

        if (! is_file($source)) {
            return null;
        }

        $path    = "catalog/{$this->brandSlug()}/{$slug}/{$name}.webp";
        $media   = app(MediaStore::class);
        $content = (string) file_get_contents($source);

        if (! $media->exists($path) || md5((string) $media->read($path)) !== md5($content)) {
            $media->write($path, $content);
        }

        return $path;
    }

    protected function hero(array $data): ?array
    {
        $path = $this->image($data['slug'], 'hero');

        return $path ? ['type' => 'image', 'src' => $path] : parent::hero($data);
    }

    /**
     * Màu: [tên, mã màu, ảnh đại diện, khoá bộ góc?]. Khoá bộ góc mặc định
     * suy từ tên ảnh (mau-xanh-duong → xanh-duong), khớp với ANGLES trong
     * database/seeders/media/import_angles.py. Bộ góc ghi vào spin_frames:
     * trình xem cho kéo/bấm để đi vòng quanh xe (≥ 12 khung là xoay 360°).
     */
    /**
     * Phiên bản kèm ảnh riêng — thẻ phiên bản ở trang chủ và trang xe. Ảnh là
     * đường dẫn trong media/lexus/{xe}/ (phien-ban/… từ import_details.py,
     * hoặc một khung 360° khi Car-project không có ảnh riêng).
     */
    protected function seedVariants(Model $product, array $data): void
    {
        parent::seedVariants($product, $data);

        foreach (array_values($data['variants'] ?? []) as $i => $variant) {
            if (filled($variant['image'] ?? null)) {
                $product->variants()->where('sort', $i + 1)
                    ->update(['image' => $this->image($data['slug'], $variant['image'])]);
            }
        }
    }

    protected function seedColors(Model $product, array $data): void
    {
        $product->options()->delete();

        foreach (array_values($data['colors'] ?? []) as $i => $colour) {
            [$name, $hex, $file] = $colour;
            $key = $colour[3] ?? preg_replace('/^mau-/', '', $file);

            $product->options()->create([
                'name'        => $name,
                'hex'         => $hex,
                'image'       => $this->image($data['slug'], $file),
                'spin_frames' => $this->angles($data['slug'], $key) ?: null,
                'sort'        => $i + 1,
            ]);
        }
    }

    /**
     * Ảnh xoay của một màu, theo thứ tự.
     *
     * Ưu tiên bộ 360° thật (≥ 12 khung chụp/render quanh xe) ở
     * media/lexus/{xe}/360/{màu}/ — lexus.com (RX, ES, NX, GX, LX, 18 khung),
     * Lexus Anh (LM, 36 khung), Lexus Nhật (LS, 12 khung); tải bằng
     * import_360.py, xem docs/vehicle-studio.md; NX Xanh dương cũng từ Lexus
     * Nhật. Không có thì lùi về bộ "nhiều góc" từ Car-project (goc-manifest.json).
     *
     * @return array<int, string>
     */
    private function angles(string $slug, string $key): array
    {
        $spin = glob(database_path("seeders/media/lexus/{$slug}/360/{$key}/*.webp")) ?: [];
        sort($spin);

        if (count($spin) >= 12) {
            // Mở ở góc 3/4 trước cho ấn tượng đầu đẹp hơn — vòng xoay giữ
            // nguyên. lexus.com: khung 04 (khung 01 là ảnh từ trên xuống),
            // riêng LX khung 01. LM (Lexus Anh, 36 góc): khung 05 = 40°.
            // Bộ Lexus Nhật (LS, NX Xanh dương — 12 góc × 30°): khung 02 = 30°.
            $start = count($spin) === 12 ? 1 : (['lx' => 0, 'lm' => 4][$slug] ?? 3);
            $spin = [...array_slice($spin, $start), ...array_slice($spin, 0, $start)];

            return collect($spin)
                ->map(fn ($file) => $this->image($slug, "360/{$key}/".basename($file, '.webp')))
                ->filter()->values()->all();
        }

        static $manifest = null;
        $manifest ??= json_decode((string) @file_get_contents(database_path('seeders/media/lexus/goc-manifest.json')), true) ?: [];

        return collect($manifest[$slug][$key] ?? [])
            ->map(fn ($frame) => $this->image($slug, $frame['file']))
            ->filter()->values()->all();
    }

    /**
     * Thứ tự mục cố định cho mọi xe:
     *   thiết kế → nội thất → vận hành → chi tiết → an toàn → thư viện → hỏi đáp
     */
    protected function sections(array $data): array
    {
        $slug = $data['slug'];
        $name = $data['name'];
        $out  = [];

        foreach (['exterior' => 'split', 'interior' => 'split-alt', 'drive' => 'bleed'] as $key => $layout) {
            if (! $block = $data[$key] ?? null) {
                continue;
            }

            $out[] = array_filter([
                'title'     => $block['title'],
                'intro'     => $block['intro'],
                'body'      => $block['body'] ?? null,
                'type'      => 'media',
                'layout'    => $layout,
                'cta_label' => $layout === 'bleed' ? 'Đăng ký lái thử' : null,
                'cta_url'   => $layout === 'bleed' ? route('booking', ['xe' => $slug], false) : null,
                'items'     => [[
                    'image' => $this->image($slug, $block['image']),
                    'label' => $block['alt'],
                ]],
            ]);
        }

        // Chi tiết & tùy chọn theo phiên bản — ảnh bản Việt Nam từ Car-project
        // (import_details.py): mâm xe, màu/chất liệu ghế, ốp, vô-lăng… Mỗi
        // nhóm là một tab (layout `groups`).
        if ($details = $data['details'] ?? null) {
            $out[] = [
                'title'  => 'Chi tiết',
                'intro'  => 'Chi tiết & tùy chọn theo phiên bản.',
                'type'   => 'media',
                'layout' => 'groups',
                'items'  => collect($details)->flatMap(fn ($rows, $group) => collect($rows)->map(fn ($r) => [
                    'image'   => $this->image($slug, 'chi-tiet/'.$r[0]),
                    'eyebrow' => $group,
                    'label'   => $r[1],
                    'desc'    => $r[2],
                ]))->values()->all(),
            ];
        }

        if ($safety = $data['safety'] ?? null) {
            $out[] = [
                'title'  => 'An toàn',
                'intro'  => $safety['intro'],
                'type'   => 'media',
                'layout' => 'cols-3',
                'items'  => collect($safety['items'])->map(fn ($f) => [
                    'image' => $this->image($slug, $f[0]),
                    'label' => $f[1],
                    'desc'  => $f[2],
                ])->all(),
            ];
        }

        if ($gallery = $data['gallery'] ?? null) {
            $out[] = [
                'title'  => 'Thư viện',
                'intro'  => "{$name} qua ống kính.",
                'type'   => 'media',
                'layout' => 'gallery',
                'items'  => collect($gallery)->map(fn ($alt, $file) => [
                    'image' => $this->image($slug, $file),
                    'label' => "{$name} — {$alt}",
                ])->values()->all(),
            ];
        }

        if ($faq = $data['faq'] ?? null) {
            $out[] = [
                'title' => 'Hỏi đáp',
                'intro' => "Câu hỏi thường gặp về {$name}",
                'type'  => 'faq',
                'rows'  => collect($faq)->map(fn ($a, $q) => ['label' => $q, 'value' => $a])->values()->all(),
            ];
        }

        return $out;
    }

    // ── Câu hỏi dùng chung ───────────────────────────────────────────────

    /** Hai câu cuối FAQ của mọi xe: lăn bánh và mua ở đâu. */
    private function commonFaq(string $name): array
    {
        return [
            "Giá lăn bánh {$name} tại Hà Nội gồm những khoản nào?" =>
                'Giá lăn bánh = giá niêm yết + lệ phí trước bạ (Hà Nội 12% với ô tô con chạy xăng/hybrid; '
                .'ô tô điện chạy pin 0% đến hết năm 2030) + phí cấp biển số (Hà Nội 14 triệu đồng từ 1/1/2026) '
                .'+ phí đăng kiểm, phí bảo trì đường bộ và bảo hiểm '
                .'trách nhiệm dân sự bắt buộc. Bảo hiểm vật chất là tùy chọn. Để nhận bảng tính chi tiết theo '
                .'phiên bản và ưu đãi đang áp dụng, bấm "Nhận báo giá" — chuyên viên gửi riêng cho bạn.',
            "Mua {$name} chính hãng ở đâu tại Hà Nội?" =>
                self::DEALER.' là đại lý Lexus chính hãng tại '.self::ADDRESS.'. Showroom trưng bày, '
                .'có xe lái thử và xưởng dịch vụ chính hãng ngay tại đại lý. Đặt lịch lái thử trên website '
                .'hoặc gọi hotline 0989 345 989 (Thu Hà).',
        ];
    }

    // ── Dữ liệu từng xe ──────────────────────────────────────────────────

    protected function products(): array
    {
        return [$this->rx(), $this->es(), $this->nx(), $this->lx(), $this->gx(), $this->lm(), $this->ls()];
    }

    private function rx(): array
    {
        return [
            'slug'       => 'rx',
            'name'       => 'Lexus RX',
            'tagline'    => 'SUV hạng sang cỡ trung, thuần hybrid',
            'category'   => 'suv',
            'price_from' => 3_350_000_000,
            'highlights' => [
                ['value' => '371', 'unit' => 'HP', 'label' => 'Công suất · RX 500h'],
                ['value' => '5,9', 'unit' => 'giây', 'label' => '0–100 km/h · RX 500h'],
                ['value' => 'AWD', 'unit' => '', 'label' => 'Dẫn động bốn bánh'],
                ['value' => '612', 'unit' => 'lít', 'label' => 'Khoang hành lý'],
            ],
            'exterior' => [
                'image' => 'ngoai-that',
                'alt'   => 'Lexus RX 350h màu đỏ tại Lexus Thăng Long',
                'title' => 'Thiết kế',
                'intro' => 'Lưới tản nhiệt con suốt liền khối. Dáng xe thấp và vững.',
                'body'  => 'Thế hệ thứ năm của RX kéo dài trục cơ sở thêm 60 mm và hạ thấp trọng tâm, '
                    .'tạo tỷ lệ gọn gàng hơn khi nhìn nghiêng. Đèn hậu LED dạng dải chạy suốt chiều ngang, '
                    .'mâm hợp kim tới 21 inch trên bản F SPORT Performance.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Ghế da bán aniline nội thất Lexus RX',
                'title' => 'Nội thất',
                'intro' => 'Khoang lái lấy người lái làm trung tâm — Tazuna.',
                'body'  => 'Màn hình cảm ứng 14 inch, bảng đồng hồ kỹ thuật số và màn hình kính lái được '
                    .'sắp theo một đường nhìn, để mắt rời khỏi mặt đường ít nhất có thể. Ghế da bán aniline '
                    .'có sưởi, làm mát; hàng ghế sau chỉnh điện, gập phẳng mở rộng khoang hành lý.',
            ],
            'drive' => [
                'image' => 'van-hanh',
                'alt'   => 'Lexus RX 500h F SPORT Performance',
                'title' => 'Vận hành',
                'intro' => 'Hybrid tăng áp 371 mã lực. Dẫn động DIRECT4.',
                'body'  => 'RX 500h kết hợp máy xăng 2.4L tăng áp với hai mô-tơ điện, tăng tốc 0–100 km/h '
                    .'trong 5,9 giây. Hệ DIRECT4 phân bổ lực kéo giữa hai cầu theo từng mili giây. '
                    .'Bản 350h ưu tiên êm ái và tiết kiệm nhiên liệu cho đô thị.',
            ],
            'details' => [
                'Ngoại thất' => [
                    ['mat-truoc', 'Mặt trước — lưới Spindle không viền', 'Liền khối với cụm đèn LED hình chữ L'],
                    ['duoi-xe', 'Đuôi xe — đèn hậu liên kết', 'Dải LED chạy suốt chiều ngang'],
                    ['than-xe', 'Thân xe — trọng tâm thấp', 'Đường mái dốc, sườn xe gân nổi'],
                ],
                'Mâm xe' => [
                    ['mam-premium', 'Mâm hợp kim 21 inch', 'RX 350h Premium'],
                    ['mam-luxury', 'Mâm hợp kim nhôm', 'RX 350h Luxury'],
                    ['mam-fsport', 'Mâm hợp kim 21 inch', 'RX 500h F SPORT Performance'],
                ],
                'Nội thất' => [
                    ['da-smooth', 'Da Smooth', 'RX 350h Premium'],
                    ['da-semi-aniline', 'Da Semi-aniline', 'RX 350h Luxury'],
                    ['da-smooth-den', 'Da Smooth đen', 'RX 500h F SPORT Performance'],
                    ['ghe-hazel', 'Màu nâu Hazel', 'F SPORT'],
                    ['ghe-trang', 'Màu trắng Solis White', ''],
                    ['ghe-do', 'Màu đỏ Dark Rose', ''],
                ],
                'Ốp trang trí' => [
                    ['op-xuong-ca', 'Hoa văn xương cá', 'RX 350h Premium'],
                    ['op-sumi', 'Vân gỗ Sumi', 'RX 350h Luxury'],
                    ['op-hop-kim', 'Hoa văn hợp kim', 'RX 500h F SPORT Performance'],
                ],
                'Vô-lăng & tiện nghi' => [
                    ['volang-da', 'Vô-lăng bọc da', 'RX 350h Premium'],
                    ['volang-go', 'Vô-lăng gỗ kết hợp da', 'RX 350h Luxury'],
                    ['volang-fsport', 'Vô-lăng da F SPORT', 'RX 500h F SPORT Performance'],
                    ['hud', 'Màn hình HUD trên kính lái', 'Chỉ dẫn hiện ngay trong tầm mắt'],
                    ['man-hinh-14', 'Màn hình cảm ứng 14 inch', 'Apple CarPlay, Android Auto không dây'],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ 3.0 — tiêu chuẩn trên mọi phiên bản.',
                'items' => [
                    ['an-toan-ahs', 'Đèn pha thích ứng AHS', 'Tự che vùng sáng chiếu vào xe đối diện, giữ pha chiếu xa cho phần đường còn lại.'],
                    ['an-toan-bsm', 'Cảnh báo điểm mù BSM', 'Radar hai bên đuôi xe báo khi có phương tiện trong vùng khuất gương.'],
                    ['an-toan-lta', 'Hỗ trợ giữ làn LTA', 'Giữ xe ở giữa làn khi bật ga tự động theo radar trên cao tốc.'],
                    ['an-toan-pksb', 'Phanh hỗ trợ đỗ xe PKSB', 'Phát hiện vật cản và xe cắt ngang khi lùi, tự phanh nếu người lái chưa kịp phản ứng.'],
                    ['an-toan-rrcc', 'Cảnh báo va chạm phía sau', 'Theo dõi xe đang lao tới từ phía sau, bật đèn cảnh báo và chuẩn bị dây đai.'],
                    ['an-toan-sea', 'Hỗ trợ ra khỏi xe SEA', 'Chặn mở cửa khi có xe máy, xe đạp tiến đến từ phía sau — rất hữu ích trong phố.'],
                ],
            ],
            'gallery' => [
                'thu-vien-2' => 'bản Luxury màu đồng',
                'thu-vien-3' => 'khoang lái',
                'thu-vien-6' => 'ghế trước',
                'thu-vien-8' => 'góc trước',
                'thu-vien-4' => 'thân xe nhìn ngang',
            ],
            'colors' => [
                ['Xanh rêu', '#3E4739', 'mau-xanh'],
                ['Trắng ngọc trai', '#ECECE8', 'mau-trang'],
                ['Xám titan', '#8C8B87', 'mau-xam'],
                ['Đen', '#1B1C1E', 'mau-den'],
                ['Đỏ pha lê', '#7C1622', 'mau-do'],
                ['Đồng', '#8F5A43', 'mau-dong'],
                ['Xanh dương', '#28324F', 'goc/xanh-duong-1', 'xanh-duong'],
            ],
            'variants' => [
                ['name' => 'RX 350h Premium', 'image' => 'phien-ban/premium', 'price' => 3_350_000_000, 'note' => 'Hybrid 2.5L · AWD E-Four'],
                ['name' => 'RX 350h Luxury', 'image' => 'phien-ban/luxury', 'price' => 4_140_000_000, 'note' => 'Hybrid 2.5L · trang bị Luxury'],
                ['name' => 'RX 500h F SPORT Performance', 'image' => 'phien-ban/fsport', 'price' => 4_940_000_000, 'note' => 'Hybrid tăng áp 2.4L · 371 HP · DIRECT4'],
            ],
            'specs' => [
                'Động cơ — RX 500h F SPORT Performance' => [
                    'Loại động cơ'        => '2.4L tăng áp + 2 mô-tơ điện (hybrid)',
                    'Công suất tổng hợp'  => '371 HP',
                    'Mô-men xoắn'         => '460 Nm',
                    'Hộp số'              => 'Tự động 6 cấp',
                    'Dẫn động'            => 'AWD DIRECT4',
                    'Tăng tốc 0–100 km/h' => '5,9 giây',
                    'Tiêu thụ nhiên liệu' => '8,1 L/100 km',
                ],
                'Kích thước & không gian' => [
                    'Dài × rộng × cao' => '4.890 × 1.920 × 1.695 mm',
                    'Chiều dài cơ sở'  => '2.850 mm',
                    'Khoảng sáng gầm'  => '200 mm',
                    'Khoang hành lý'   => '612 lít',
                    'Bình nhiên liệu'  => '65 lít',
                    'Số chỗ ngồi'      => '5',
                ],
                'Khung gầm' => [
                    'Treo trước / sau' => 'MacPherson / đa liên kết',
                    'Phanh'            => 'Đĩa thông gió trước và sau',
                    'Lốp'              => '235/50R21',
                ],
            ],
            'faq' => [
                'Giá xe Lexus RX 2026 bao nhiêu?' =>
                    'Lexus RX có 3 phiên bản: RX 350h Premium 3,35 tỷ đồng, RX 350h Luxury 4,14 tỷ đồng '
                    .'và RX 500h F SPORT Performance 4,94 tỷ đồng (giá niêm yết, đã gồm VAT).',
                'Nên chọn RX 350h hay RX 500h?' =>
                    'RX 350h hợp với người đi phố nhiều, ưu tiên êm và tiết kiệm nhiên liệu. RX 500h dùng máy '
                    .'2.4L tăng áp kết hợp mô-tơ điện, 371 HP, 0–100 km/h 5,9 giây — dành cho người muốn cảm giác lái '
                    .'thể thao mà vẫn là xe hybrid.',
                'Lexus RX có mấy chỗ ngồi?' =>
                    'Lexus RX bán tại Việt Nam là bản 5 chỗ, khoang hành lý 612 lít và gập được hàng ghế sau.',
            ] + $this->commonFaq('Lexus RX'),
            'seo' => [
                'title'       => 'Lexus RX 2026: giá từ 3,35 tỷ, thông số, màu | Lexus Thăng Long',
                'description' => 'Giá xe Lexus RX 2026 tại Hà Nội: RX 350h Premium 3,35 tỷ, 350h Luxury 4,14 tỷ, '
                    .'500h F SPORT Performance 4,94 tỷ. Xem màu, thông số và đăng ký lái thử tại Lexus Thăng Long.',
            ],
        ];
    }

    private function es(): array
    {
        return [
            'slug'       => 'es',
            'name'       => 'Lexus ES',
            'tagline'    => 'Sedan thế hệ mới — hybrid hoặc thuần điện',
            'category'   => 'sedan',
            'price_from' => null,   // Car-project: "ĐANG CẬP NHẬT" — ES thế hệ mới chưa công bố giá
            'highlights' => [
                ['value' => '350h / 500e', 'unit' => '', 'label' => 'Hybrid hoặc thuần điện'],
                ['value' => '5', 'unit' => 'chỗ', 'label' => 'Sedan hạng sang'],
                ['value' => '3', 'unit' => 'phiên bản', 'label' => 'Premium · Luxury · 500e'],
                ['value' => 'Mới', 'unit' => '', 'label' => 'Thế hệ thứ 8 · giá đang cập nhật'],
            ],
            'exterior' => [
                'image' => 'ngoai-that',
                'alt'   => 'Lexus ES thế hệ mới màu xanh nhìn từ phía sau',
                'title' => 'Thiết kế',
                'intro' => 'Thế hệ thứ tám. Một đường mái liền mạch.',
                'body'  => 'ES mới chuyển sang dáng fastback, mui xe dài và đuôi vuốt gọn. Mặt trước bỏ lưới '
                    .'tản nhiệt truyền thống, thay bằng khối "con suốt" liền thân xe — ngôn ngữ thiết kế mới '
                    .'của Lexus cho thời kỳ điện hóa.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Khoang lái Lexus ES thế hệ mới',
                'title' => 'Nội thất',
                'intro' => 'Rộng hơn, sáng hơn, ít nút bấm hơn.',
                'body'  => 'Trục cơ sở dài hơn mang lại chỗ để chân hàng ghế sau rộng rãi — điều khách ES '
                    .'hỏi nhiều nhất. Màn hình trung tâm cỡ lớn, gương chiếu hậu kỹ thuật số, ghế da có sưởi '
                    .'và làm mát.',
            ],
            'details' => [
                'Ngoại thất' => [
                    ['dau-xe', 'Đầu xe — Spindle Body', 'Khối đầu xe liền thân thay lưới tản nhiệt'],
                    ['duoi-xe', 'Đuôi xe — đèn hậu liên kết', 'Dải đèn nhấn chiều rộng'],
                    ['hong-xe', 'Hông xe — dáng fastback', 'Trục cơ sở dài, mái vuốt'],
                ],
                'Nội thất' => [
                    ['noi-that-den', 'Nội thất đen', 'Cổ điển'],
                    ['noi-that-trang', 'Nội thất trắng', 'Hiện đại'],
                    ['noi-that-nau', 'Nội thất nâu', 'Ấm áp'],
                    ['hang-ghe-sau', 'Hàng ghế sau', 'Rộng rãi, ghế công thái học'],
                ],
                'Tiện nghi' => [
                    ['man-hinh-14', 'Màn hình trung tâm 14 inch', 'Apple CarPlay, Android Auto không dây'],
                    ['dong-ho-123', 'Đồng hồ kỹ thuật số 12,3 inch', 'Giao diện theo chế độ lái'],
                    ['hud', 'Màn hình HUD trên kính lái', ''],
                    ['guong-ky-thuat-so', 'Gương chiếu hậu kỹ thuật số', 'Không bị che bởi hành khách phía sau'],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ trên mọi phiên bản.',
                'items' => [
                    ['an-toan-pcs', 'An toàn tiền va chạm PCS', 'Nhận diện xe, người đi bộ và xe đạp phía trước; cảnh báo rồi tự phanh khi cần.'],
                    ['an-toan-drcc', 'Ga tự động theo radar DRCC', 'Giữ khoảng cách với xe phía trước trên cả dải tốc độ, kể cả khi dừng – đi trong tắc đường.'],
                    ['an-toan-lta', 'Hỗ trợ giữ làn LTA', 'Đánh lái nhẹ để giữ xe giữa làn khi đi cao tốc.'],
                    ['an-toan-lda', 'Cảnh báo chệch làn LDA', 'Rung vô-lăng và hỗ trợ đưa xe về làn khi xe lệch khỏi vạch kẻ đường.'],
                    ['an-toan-pda', 'Hỗ trợ lái chủ động PDA', 'Giảm tốc nhẹ trước khúc cua và giữ khoảng cách an toàn với người đi bộ bên đường.'],
                    ['an-toan-rsa', 'Nhận diện biển báo RSA', 'Đọc biển tốc độ và hiển thị ngay trên màn hình trước mặt người lái.'],
                ],
            ],
            'gallery' => [
                'thu-vien-7' => 'màn hình trung tâm',
                'thu-vien-1' => 'mặt trước',
                'thu-vien-3' => 'hàng ghế sau',
                'thu-vien-4' => 'Apple CarPlay',
                'thu-vien-8' => 'ghế da',
            ],
            'colors' => [
                ['Trắng', '#ECECEA', 'mau-trang'],
                ['Bạc', '#B9BCC0', 'mau-bac'],
                ['Xám', '#5E6572', 'mau-xam'],
                ['Xanh thép', '#7F94B8', 'mau-xanh-duong'],
                ['Đồng', '#A15F3E', 'mau-dong'],
                ['Đen', '#1C1D20', 'mau-den'],
            ],
            'variants' => [
                ['name' => 'ES 350h Premium', 'image' => 'phien-ban/premium', 'price' => null, 'note' => 'Hybrid'],
                ['name' => 'ES 350h Luxury', 'image' => '360/xam/04', 'price' => null, 'note' => 'Hybrid · trang bị Luxury'],
                ['name' => 'ES 500e', 'image' => '360/xanh-duong/04', 'price' => null, 'note' => 'Thuần điện'],
            ],
            'faq' => [
                'Giá xe Lexus ES 2026 bao nhiêu?' =>
                    'Lexus ES thế hệ mới gồm 3 phiên bản ES 350h Premium, ES 350h Luxury và ES 500e (thuần điện). '
                    .'Giá bán đang được cập nhật — để lại số điện thoại, chuyên viên báo giá ngay khi Lexus Việt Nam công bố.',
                'ES 350h và ES 500e khác nhau thế nào?' =>
                    'ES 350h là hybrid xăng – điện, không cần sạc, phù hợp đi cả phố lẫn đường dài. ES 500e chạy '
                    .'hoàn toàn bằng điện, êm tuyệt đối và không phát thải, cần sạc tại nhà hoặc trạm sạc. '
                    .'Ô tô điện chạy pin được áp lệ phí trước bạ lần đầu 0% đến hết năm 2030 (Nghị định 202/2026/NĐ-CP), '
                    .'nên chênh lệch giá lăn bánh giữa ES 500e và ES 350h hẹp hơn nhiều so với giá niêm yết.',
                'Vì sao Lexus ES được nhiều khách chọn?' =>
                    'ES là mẫu sedan phổ biến của Lexus tại Việt Nam nhờ khoang sau rộng, vận hành êm '
                    .'và chi phí sử dụng hợp lý của hệ truyền động hybrid.',
            ] + $this->commonFaq('Lexus ES'),
            'seo' => [
                'title'       => 'Lexus ES 2026 thế hệ mới: 350h & 500e thuần điện | Lexus Thăng Long',
                'description' => 'Lexus ES 2026 thế hệ mới tại Hà Nội: ES 350h Premium, 350h Luxury và ES 500e thuần điện — '
                    .'giá đang cập nhật. Xem màu, trang bị an toàn và đăng ký lái thử tại Lexus Thăng Long.',
            ],
        ];
    }

    private function nx(): array
    {
        return [
            'slug'       => 'nx',
            'name'       => 'Lexus NX',
            'tagline'    => 'SUV đô thị: hybrid tiết kiệm hoặc F SPORT tăng áp',
            'category'   => 'suv',
            'price_from' => 3_130_000_000,
            'highlights' => [
                ['value' => '240', 'unit' => 'HP', 'label' => 'Hybrid · NX 350h'],
                ['value' => '275', 'unit' => 'HP', 'label' => 'Tăng áp · NX 350 F SPORT'],
                ['value' => 'E-Four', 'unit' => '', 'label' => 'Dẫn động bốn bánh'],
                ['value' => '520', 'unit' => 'lít', 'label' => 'Khoang hành lý'],
            ],
            'exterior' => [
                'image' => 'thu-vien-3',
                'alt'   => 'Lexus NX 350h màu xám nhìn từ phía sau',
                'title' => 'Thiết kế',
                'intro' => 'Nhỏ gọn cho phố. Đủ chất cho cao tốc.',
                'body'  => 'NX dài 4.660 mm — vừa vặn các hầm gửi xe chung cư Hà Nội — nhưng vẫn giữ tỷ lệ '
                    .'bề thế của một chiếc SUV Lexus. Đèn hậu dạng dải chữ L liền mạch, chữ LEXUS thay cho '
                    .'logo ở đuôi xe.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Khoang lái Lexus NX với màn hình 14 inch',
                'title' => 'Nội thất',
                'intro' => 'Màn hình 14 inch hướng về người lái.',
                'body'  => 'Khoang lái theo triết lý Tazuna: các nút cơ bản nằm trong tầm tay, màn hình lớn '
                    .'nghiêng về phía người lái. Cửa mở bằng nút điện tử e-latch, ghế da có sưởi và làm mát.',
            ],
            'details' => [
                'Ngoại thất' => [
                    ['mat-truoc', 'Mặt trước — Spindle không viền', ''],
                    ['den-pha', 'Cụm đèn pha LED', ''],
                    ['den-hau', 'Đèn hậu chữ L liền mạch', ''],
                    ['duoi-xe', 'Đuôi xe với chữ LEXUS', ''],
                ],
                'Mâm xe' => [
                    ['mam-18', 'Mâm hợp kim nhôm', 'NX 350h'],
                    ['mam-20-fsport', 'Mâm 20 inch sơn đen', 'NX 350 F SPORT'],
                ],
                'Nội thất' => [
                    ['ghe-den-kem', 'Đen & kem Rich Cream', ''],
                    ['ghe-nau', 'Nâu Hazel', ''],
                    ['ghe-do-dark-rose', 'Đỏ Dark Rose', ''],
                    ['ghe-trang-fsport', 'Trắng', 'NX 350 F SPORT'],
                    ['ghe-flare-red', 'Đỏ Flare Red', 'NX 350 F SPORT'],
                    ['ghe-den-fsport', 'Đen', 'NX 350 F SPORT'],
                ],
                'Ốp trang trí' => [
                    ['op-go', 'Gỗ Open Pore Ash, Sumi Black', 'NX 350h'],
                    ['op-nhom', 'Nhôm Dark Spin', 'NX 350 F SPORT'],
                ],
                'Vô-lăng & tiện nghi' => [
                    ['volang-go', 'Vô-lăng gỗ kết hợp da', 'NX 350h'],
                    ['volang-fsport', 'Vô-lăng da F SPORT', 'NX 350 F SPORT'],
                    ['man-hinh', 'Màn hình cảm ứng trung tâm', 'Apple CarPlay, Android Auto không dây'],
                    ['dong-ho-fsport', 'Đồng hồ F SPORT', 'Giao diện xe đua ở chế độ SPORT S+'],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ 3.0 và Lexus Teammate.',
                'items' => [
                    ['an-toan-pcs', 'An toàn tiền va chạm PCS', 'Phát hiện xe, người đi bộ, xe máy phía trước và tự phanh khi nguy cơ va chạm cao.'],
                    ['an-toan-lta', 'Hỗ trợ giữ làn LTA', 'Giữ xe giữa làn đường khi bật ga tự động theo radar.'],
                    ['an-toan-bsm', 'Cảnh báo điểm mù BSM', 'Báo phương tiện trong vùng khuất khi chuyển làn.'],
                    ['an-toan-pksb', 'Phanh hỗ trợ đỗ xe PKSB', 'Tự phanh khi lùi gần vật cản hoặc có xe cắt ngang phía sau.'],
                    ['an-toan-sea', 'Hỗ trợ ra khỏi xe SEA', 'Không cho mở cửa khi có xe máy, xe đạp tiến tới từ phía sau.'],
                    ['an-toan-teammate', 'Đỗ xe tự động Advanced Park', 'Xe tự đánh lái, chuyển số và phanh để vào chỗ đỗ song song hoặc lùi chuồng.'],
                ],
            ],
            'gallery' => [
                'thu-vien-2' => 'bản đen tại Hà Nội',
                'thu-vien-6' => 'khoang lái',
                'thu-vien-7' => 'đuôi xe',
                'thu-vien-4' => 'màn hình trung tâm',
                'thu-vien-8' => 'khoang hành lý',
                'fsport-noi-that-do' => 'nội thất đỏ Flare Red bản F SPORT',
                'fsport-ghe' => 'ghế thể thao F SPORT',
                'fsport-cua-so-troi' => 'cửa sổ trời bản F SPORT',
                'thu-vien-1' => 'màu đen',
            ],
            'colors' => [
                ['Xám', '#5A5B5E', 'mau-xam'],
                ['Trắng', '#EDEDEB', 'mau-trang'],
                ['Đỏ', '#A5141D', 'mau-do'],
                ['Xanh dương', '#2440A8', 'mau-xanh-duong'],
                ['Xanh rêu', '#5E5B39', 'mau-xanh-reu'],
                ['Đen', '#1A1A1C', 'mau-den'],
            ],
            'variants' => [
                ['name' => 'NX 350 F SPORT', 'image' => '360/do/04', 'price' => 3_130_000_000, 'note' => 'Xăng tăng áp 2.4L · 275 HP · treo thích ứng AVS'],
                ['name' => 'NX 350h', 'image' => 'phien-ban/350h', 'price' => 3_270_000_000, 'note' => 'Hybrid 2.5L · AWD E-Four'],
            ],
            'specs' => [
                'Động cơ — NX 350h' => [
                    'Mã động cơ'              => 'A25A-FXS, 4 xi-lanh, 2.487 cc (hybrid)',
                    'Công suất động cơ xăng'  => '188 HP tại 6.000 vòng/phút',
                    'Mô-men xoắn'             => '239 Nm tại 4.300–4.500 vòng/phút',
                    'Tổng công suất hệ thống' => '240 HP (179 kW)',
                    'Dẫn động'                => 'AWD E-Four',
                    'Tiêu thụ kết hợp'        => '6,65 L/100 km',
                ],
                'Động cơ — NX 350 F SPORT' => [
                    'Mã động cơ'       => 'T24A-FTS, 4 xi-lanh tăng áp, 2.393 cc',
                    'Công suất cực đại' => '275 HP (205 kW) tại 6.000 vòng/phút',
                    'Mô-men xoắn'      => '430 Nm tại 1.700–3.600 vòng/phút',
                    'Hộp số'           => 'Tự động 8 cấp',
                    'Dẫn động'         => 'AWD',
                    'Tiêu thụ kết hợp' => '10,75 L/100 km',
                    'Treo thích ứng AVS' => 'Có',
                ],
                'Kích thước & không gian' => [
                    'Dài × rộng × cao' => '4.660 × 1.865 × 1.670 mm',
                    'Chiều dài cơ sở'  => '2.690 mm',
                    'Khoảng sáng gầm'  => '195 mm',
                    'Khoang hành lý'   => '520 lít (tối đa 1.411 lít)',
                    'Bình nhiên liệu'  => '55 lít',
                    'Số chỗ ngồi'      => '5',
                ],
                'Khung gầm' => [
                    'Treo trước / sau' => 'MacPherson / tay đòn kép',
                    'Lốp'              => '235/50R20 run-flat',
                ],
            ],
            'faq' => [
                'Giá xe Lexus NX 2026 bao nhiêu?' =>
                    'Lexus NX có 2 phiên bản: NX 350 F SPORT 3,13 tỷ đồng (xăng tăng áp 2.4L) và NX 350h 3,27 tỷ đồng '
                    .'(hybrid 2.5L, AWD E-Four) — giá niêm yết đã gồm VAT.',
                'Nên chọn NX 350h hay NX 350 F SPORT?' =>
                    'NX 350h tiết kiệm hơn hẳn (6,65 so với 10,75 L/100 km đường hỗn hợp), hợp đi phố hằng ngày. '
                    .'NX 350 F SPORT mạnh hơn (275 HP, 430 Nm), có treo thích ứng AVS, ghế thể thao và tùy chọn nội thất đỏ '
                    .'Flare Red — dành cho người thích cảm giác lái; giá lại thấp hơn 140 triệu đồng.',
                'Lexus NX 350h tiêu hao bao nhiêu xăng?' =>
                    'Theo công bố, NX 350h tiêu thụ 6,65 L/100 km đường kết hợp và khoảng 6,44 L/100 km trong đô thị — '
                    .'xe hybrid càng tiết kiệm khi đi phố đông.',
                'Lexus NX và RX khác nhau thế nào?' =>
                    'NX ngắn hơn RX 230 mm, dễ xoay trở và gửi xe trong phố; RX rộng hơn, khoang hành lý lớn hơn và có '
                    .'bản 500h hiệu năng cao. Cả hai đều là SUV 5 chỗ.',
            ] + $this->commonFaq('Lexus NX'),
            'seo' => [
                'title'       => 'Lexus NX 2026: giá từ 3,13 tỷ, 350h & F SPORT | Lexus Thăng Long',
                'description' => 'Lexus NX 2026 tại Hà Nội: NX 350 F SPORT 3,13 tỷ (tăng áp 275 HP), NX 350h 3,27 tỷ (hybrid, 6,65 L/100 km). '
                    .'Xem 6 màu, thông số chi tiết và đăng ký lái thử tại Lexus Thăng Long, Cầu Giấy.',
            ],
        ];
    }

    private function lx(): array
    {
        return [
            'slug'       => 'lx',
            'name'       => 'Lexus LX',
            'tagline'    => 'SUV đầu bảng, bản lĩnh trên mọi địa hình',
            'category'   => 'suv',
            'price_from' => 8_590_000_000,
            'highlights' => [
                ['value' => '409', 'unit' => 'HP', 'label' => 'V6 3.5L tăng áp kép'],
                ['value' => '650', 'unit' => 'Nm', 'label' => 'Mô-men xoắn cực đại'],
                ['value' => '10', 'unit' => 'cấp', 'label' => 'Hộp số tự động · AWD'],
                ['value' => '4 · 5 · 7', 'unit' => 'chỗ', 'label' => 'Theo phiên bản'],
            ],
            'exterior' => [
                'image' => 'ngoai-that',
                'alt'   => 'Lexus LX 600 màu trắng tại Lexus Thăng Long',
                'title' => 'Thiết kế',
                'intro' => 'Hiện diện không cần lên tiếng.',
                'body'  => 'Lưới tản nhiệt con suốt không viền với các nan ngang mạ crôm, thân xe vuông vức '
                    .'dài 5,1 m. LX đứng trên khung gầm rời TNGA-F — cứng hơn, nhẹ hơn thế hệ trước khoảng 200 kg.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Khoang lái da bò Lexus LX 600',
                'title' => 'Nội thất',
                'intro' => 'Hai màn hình, một tinh thần Takumi.',
                'body'  => 'Màn hình trên 12,3 inch cho giải trí và bản đồ, màn hình dưới 7 inch cho điều hòa và '
                    .'địa hình. Bản VIP 4 chỗ có ghế sau ngả 48 độ, đệm chân và màn hình giải trí riêng.',
            ],
            'drive' => [
                'image' => 'van-hanh',
                'alt'   => 'Lexus LX 600 màu xanh rêu',
                'title' => 'Vận hành',
                'intro' => 'V6 tăng áp kép 409 mã lực. Treo chủ động AHC.',
                'body'  => 'Động cơ V35A-FTS kết hợp hộp số 10 cấp và dẫn động bốn bánh toàn thời gian. Hệ treo '
                    .'điều chỉnh độ cao chủ động AHC và giảm chấn thích ứng AVS giữ thân xe êm trên phố, '
                    .'vững trên đường xấu.',
            ],
            'details' => [
                'Mâm xe' => [
                    ['mam-urban', 'Mâm hợp kim 22 inch', 'LX 600 Urban'],
                    ['mam-fsport', 'Mâm 22 inch dập nguyên khối', 'LX 600 F SPORT'],
                    ['mam-vip', 'Mâm hợp kim 22 inch', 'LX 600 VIP'],
                ],
                'Nội thất' => [
                    ['ghe-den', 'Đen Black', ''],
                    ['ghe-sunflare', 'Nâu Sunflare Brown', ''],
                    ['ghe-trang', 'Trắng & Dark Sepia', ''],
                    ['ghe-hazel', 'Nâu Hazel', ''],
                    ['ghe-crimson', 'Đỏ thẫm Crimson', ''],
                    ['ghe-flare-red', 'Đỏ Flare Red', ''],
                ],
                'Ốp trang trí' => [
                    ['op-go', 'Ốp gỗ', 'LX 600 VIP'],
                    ['op-nhom-hadori', 'Nhôm Hadori — hoạ tiết kiếm Nhật', 'LX 600 F SPORT'],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ cho một chiếc SUV 2,6 tấn.',
                'items' => [
                    ['an-toan-pcs', 'An toàn tiền va chạm PCS', 'Nhận diện xe và người đi bộ phía trước, tự phanh khi nguy cơ va chạm cao.'],
                    ['an-toan-lta', 'Hỗ trợ giữ làn LTA', 'Giữ xe giữa làn khi chạy ga tự động theo radar.'],
                    ['an-toan-bsm', 'Cảnh báo điểm mù BSM', 'Radar hai bên báo phương tiện trong vùng khuất gương.'],
                    ['an-toan-pksb', 'Phanh hỗ trợ đỗ xe PKSB', 'Tự phanh khi lùi gần vật cản — quan trọng với thân xe dài 5,1 m.'],
                    ['an-toan-rrcc', 'Cảnh báo va chạm phía sau', 'Theo dõi xe lao tới từ phía sau và cảnh báo sớm.'],
                    ['an-toan-sea', 'Hệ thống túi khí SRS', 'Túi khí trước, bên hông và rèm che bảo vệ cả ba hàng ghế.'],
                ],
            ],
            'gallery' => [
                'thu-vien-3' => 'khoang sau',
                'thu-vien-4' => 'ghế VIP',
                'thu-vien-5' => 'màn hình giải trí hàng sau',
                'thu-vien-7' => 'khoang lái F SPORT',
                'thu-vien-8' => 'khoang lái',
            ],
            'colors' => [
                ['Đen', '#16171A', 'mau-den'],
                ['Trắng ngọc trai', '#EEEEEA', 'mau-trang'],
                ['Xám kim loại', '#8E8A80', 'mau-xam'],
                ['Xanh rêu', '#4B5239', 'mau-xanh'],
            ],
            'variants' => [
                ['name' => 'LX 600 Urban', 'image' => 'phien-ban/urban', 'price' => 8_590_000_000, 'note' => '7 chỗ'],
                ['name' => 'LX 600 F SPORT', 'image' => 'phien-ban/fsport', 'price' => 8_840_000_000, 'note' => '5 chỗ · phong cách thể thao'],
                ['name' => 'LX 600 VIP', 'image' => 'phien-ban/vip', 'price' => 9_700_000_000, 'note' => '4 chỗ · ghế sau thương gia'],
            ],
            'specs' => [
                'Động cơ' => [
                    'Mã động cơ'        => 'V35A-FTS, V6 tăng áp kép',
                    'Dung tích'         => '3.445 cc',
                    'Công suất cực đại' => '409 HP (305 kW) tại 5.200 vòng/phút',
                    'Mô-men xoắn'       => '650 Nm tại 2.000–3.600 vòng/phút',
                    'Hộp số'            => 'Tự động 10 cấp',
                    'Dẫn động'          => 'AWD toàn thời gian',
                ],
                'Kích thước & không gian' => [
                    'Dài × rộng × cao' => '5.100 × 1.990 × 1.865 mm (VIP, Urban) · 5.090 mm (F SPORT)',
                    'Chiều dài cơ sở'  => '2.850 mm',
                    'Khoảng sáng gầm'  => '205 mm',
                    'Số chỗ ngồi'      => '4 (VIP) · 7 (Urban) · 5 (F SPORT)',
                    'Bình nhiên liệu'  => '80 lít + bình phụ 30 lít',
                ],
                'Vận hành' => [
                    'Treo'             => 'Tay đòn kép / đa điểm, AVS, AHC',
                    'Tiêu thụ kết hợp' => '13,94 (VIP) · 14,53 (Urban) · 14,59 (F SPORT) L/100 km',
                    'Lốp'              => '265/50R22',
                ],
            ],
            'faq' => [
                'Giá xe Lexus LX 600 2026 bao nhiêu?' =>
                    'Lexus LX 600 có 3 phiên bản: Urban 7 chỗ 8,59 tỷ đồng, F SPORT 5 chỗ 8,84 tỷ đồng và VIP 4 chỗ '
                    .'9,70 tỷ đồng (giá niêm yết, đã gồm VAT).',
                'Lexus LX 600 VIP khác gì bản Urban?' =>
                    'Bản VIP chỉ có 4 chỗ: hai ghế sau kiểu thương gia ngả tới 48 độ, có đệm chân, màn hình giải trí '
                    .'và bàn điều khiển riêng. Bản Urban 7 chỗ ưu tiên sức chứa cho gia đình.',
                'LX 600 dùng động cơ gì?' =>
                    'LX 600 dùng động cơ V6 3.5L tăng áp kép V35A-FTS, 409 HP và 650 Nm, hộp số tự động 10 cấp, '
                    .'dẫn động bốn bánh toàn thời gian.',
            ] + $this->commonFaq('Lexus LX'),
            'seo' => [
                'title'       => 'Lexus LX 600 2026: giá từ 8,59 tỷ, 3 phiên bản | Lexus Thăng Long',
                'description' => 'Giá Lexus LX 600 2026 tại Hà Nội: Urban 8,59 tỷ, F SPORT 8,84 tỷ, VIP 4 chỗ 9,7 tỷ. '
                    .'V6 tăng áp kép 409 HP. Xem màu, thông số và đặt lịch lái thử tại Lexus Thăng Long.',
            ],
        ];
    }

    private function gx(): array
    {
        return [
            'slug'       => 'gx',
            'name'       => 'Lexus GX',
            'tagline'    => 'SUV khung gầm rời, sinh ra cho đường dài',
            'category'   => 'suv',
            'price_from' => 6_400_000_000,
            'highlights' => [
                ['value' => '349', 'unit' => 'HP', 'label' => 'V6 3.5L tăng áp kép'],
                ['value' => '650', 'unit' => 'Nm', 'label' => 'Mô-men xoắn cực đại'],
                ['value' => '7', 'unit' => 'chỗ', 'label' => 'Ba hàng ghế'],
                ['value' => '220', 'unit' => 'mm', 'label' => 'Khoảng sáng gầm'],
            ],
            'exterior' => [
                'image' => 'ngoai-that',
                'alt'   => 'Lexus GX 550 màu trắng tại Lexus Thăng Long',
                'title' => 'Thiết kế',
                'intro' => 'Vuông vức, có chủ đích.',
                'body'  => 'Thế hệ mới của GX quay về dáng hộp: nắp ca-pô phẳng và thấp cho tầm nhìn xuống '
                    .'địa hình, cửa sổ đứng, cột D dày. Khung gầm rời GA-F dùng chung nền tảng với LX.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Khoang lái Lexus GX 550',
                'title' => 'Nội thất',
                'intro' => 'Nút bấm vật lý, ở đúng nơi cần.',
                'body'  => 'Các nút điều khiển địa hình và điều hòa đặt thành hàng dưới màn hình lớn — thao tác '
                    .'được cả khi đeo găng. Ghế da bán aniline có sưởi, làm mát, mát-xa; cửa sổ trời toàn cảnh.',
            ],
            'drive' => [
                'image' => 'van-hanh',
                'alt'   => 'Lexus GX 550 nhìn từ trên cao',
                'title' => 'Vận hành',
                'intro' => 'V6 3.5L tăng áp kép. 650 Nm từ 2.000 vòng/phút.',
                'body'  => 'GX 550 dùng động cơ V35A-FTS 349 HP, hộp số tự động 10 cấp và dẫn động bốn bánh toàn thời gian. '
                    .'Khung gầm rời GA-F cùng khoảng sáng gầm 220 mm sẵn sàng cho cả phố lẫn đường xấu.',
            ],
            'details' => [
                'Ngoại thất' => [
                    ['mat-truoc', 'Mặt trước — cản tối ưu góc tiếp cận', ''],
                    ['mat-sau', 'Đuôi xe — đèn hậu LED 3 chiều', ''],
                ],
                'Mâm xe' => [
                    ['mam-luxury', 'Mâm hợp kim nhôm', 'GX 550'],
                ],
                'Nội thất' => [
                    ['ghe-flaxen', 'Da nâu Flaxen', 'GX 550'],
                    ['ghe-den', 'Da đen Black', 'Mọi phiên bản'],
                    ['ghe-overtrail', 'Da tổng hợp kháng bẩn', 'GX 550M'],
                    ['cabin-7-cho', 'Cabin 7 chỗ', 'Hàng ghế thứ ba linh hoạt'],
                ],
                'Ốp trang trí' => [
                    ['op-go', 'Ốp gỗ', 'GX 550'],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ 3.0.',
                'items' => [
                    ['an-toan-pcs', 'An toàn tiền va chạm PCS', 'Phát hiện xe và người đi bộ phía trước, cảnh báo và tự phanh.'],
                    ['an-toan-ahs', 'Tự động pha/cốt', 'Tự chuyển giữa đèn pha và đèn cốt khi gặp xe ngược chiều.'],
                    ['an-toan-teammate', 'Hỗ trợ đỗ xe Advanced Park', 'Xe tự đánh lái và phanh để vào chỗ đỗ.'],
                ],
            ],
            'gallery' => [
                'thu-vien-1' => 'tại showroom Lexus Thăng Long',
                'thu-vien-2' => 'màu xanh rêu',
                'thu-vien-3' => 'góc trước',
                'thu-vien-5' => 'khoang lái',
                'thu-vien-6' => 'trên địa hình',
            ],
            'colors' => [
                ['Xám', '#6C6E71', 'mau-xam'],
                ['Đen', '#18191B', 'mau-den'],
                ['Trắng', '#EDEDEA', 'mau-trang'],
                ['Xanh rêu', '#3F4A3A', 'mau-xanh'],
            ],
            'variants' => [
                ['name' => 'GX 550M', 'image' => 'phien-ban/overtrail', 'price' => 6_400_000_000, 'note' => 'Mâm địa hình chuyên dụng · ghế da tổng hợp kháng bẩn'],
                ['name' => 'GX 550', 'image' => 'phien-ban/luxury', 'price' => 6_450_000_000, 'note' => 'Da nâu Flaxen · ốp gỗ nội thất'],
            ],
            'specs' => [
                'Động cơ' => [
                    'Mã động cơ'          => 'V35A-FTS, V6 tăng áp kép',
                    'Dung tích'           => '3.445 cc',
                    'Công suất cực đại'   => '349 HP (260 kW) tại 4.800–5.200 vòng/phút',
                    'Mô-men xoắn'         => '650 Nm tại 2.000–3.600 vòng/phút',
                    'Tăng tốc 0–100 km/h' => '7 giây',
                ],
                'Kích thước & không gian' => [
                    'Dài × rộng × cao' => '4.960 × 1.980 × 1.865 mm',
                    'Chiều dài cơ sở'  => '2.850 mm',
                    'Khoảng sáng gầm'  => '220 mm',
                    'Số chỗ ngồi'      => '7',
                ],
                'Khung gầm' => [
                    'Treo trước / sau' => 'Tay đòn kép / liên kết 4 điểm',
                    'Phanh'            => 'Đĩa thông gió trước và sau',
                ],
            ],
            'faq' => [
                'Giá xe Lexus GX 550 2026 bao nhiêu?' =>
                    'Lexus GX 550 có 2 phiên bản: GX 550M 6,40 tỷ đồng và GX 550 6,45 tỷ đồng (giá niêm yết, đã gồm VAT).',
                'GX 550M khác gì GX 550?' =>
                    'Hai bản cùng động cơ V6 3.5L tăng áp kép 349 HP, 7 chỗ. GX 550M thiên về khám phá: mâm xe địa hình '
                    .'chuyên dụng và ghế da tổng hợp kháng bẩn. GX 550 thiên về sang trọng: ghế da nâu Flaxen và ốp gỗ '
                    .'nội thất. Chênh lệch giá 50 triệu đồng.',
                'GX và LX nên chọn xe nào?' =>
                    'Cả hai chung khung gầm rời và động cơ V6 3.5L tăng áp kép. GX gọn hơn, giá thấp hơn khoảng 2 tỷ; '
                    .'LX lớn hơn, sang trọng hơn và có bản VIP 4 chỗ.',
            ] + $this->commonFaq('Lexus GX'),
            'seo' => [
                'title'       => 'Lexus GX 550 2026: giá từ 6,4 tỷ, thông số | Lexus Thăng Long',
                'description' => 'Giá Lexus GX 550 2026 tại Hà Nội: GX 550M 6,4 tỷ, GX 550 6,45 tỷ. SUV khung gầm rời, '
                    .'V6 tăng áp kép 349 HP, 7 chỗ. Xem ảnh thực tế và lái thử tại Lexus Thăng Long.',
            ],
        ];
    }

    private function lm(): array
    {
        return [
            'slug'       => 'lm',
            'name'       => 'Lexus LM',
            'tagline'    => 'Phòng chờ hạng nhất trên bánh xe',
            'category'   => 'mpv',
            'price_from' => 7_210_000_000,
            'highlights' => [
                ['value' => '366', 'unit' => 'HP', 'label' => 'Hybrid tăng áp 2.4L'],
                ['value' => '6,8', 'unit' => 'giây', 'label' => '0–100 km/h'],
                ['value' => 'AWD', 'unit' => '', 'label' => 'DIRECT4'],
                ['value' => '4 · 6', 'unit' => 'chỗ', 'label' => 'Theo phiên bản'],
            ],
            'exterior' => [
                'image' => 'ngoai-that',
                'alt'   => 'Khoang hành khách Lexus LM 4 chỗ với màn hình 48 inch',
                'title' => 'Khoang hạng nhất',
                'intro' => 'Màn hình 48 inch. Vách ngăn kính mờ.',
                'body'  => 'Bản 4 chỗ tách hẳn khoang lái bằng vách ngăn có kính làm mờ bằng điện. Hai ghế '
                    .'thương gia ngả gần như phẳng, có sưởi, làm mát, mát-xa; tủ lạnh và bàn làm việc gập.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Bố trí ghế Lexus LM 6 chỗ',
                'title' => 'Hai cấu hình',
                'intro' => '4 chỗ cho doanh nhân. 6 chỗ cho gia đình.',
                'body'  => 'Bản 6 chỗ giữ hai ghế thương gia ở hàng giữa và thêm hàng ghế thứ ba gập linh hoạt. '
                    .'Cửa trượt điện bằng nhôm, bậc lên xuống thấp để khách bước vào thoải mái.',
            ],
            'details' => [
                'Ngoại thất' => [
                    ['mat-truoc', 'Mặt trước — cấu trúc liền mạch', ''],
                    ['mat-ben', 'Thân xe khí động học', ''],
                    ['mat-sau', 'Đuôi xe', ''],
                ],
                'Mâm xe' => [
                    ['mam-19', 'Mâm hợp kim 19 inch', 'LM 500h'],
                ],
                'Nội thất' => [
                    ['khoang-lai', 'Khoang lái', ''],
                    ['khoang-sau', 'Khoang sau, màn hình 48 inch', 'LM 500h 4 chỗ VIP'],
                    ['cabin', 'Ghế thương gia hàng giữa', ''],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ 3.0 — cho người lái và cả khoang sau.',
                'items' => [
                    ['an-toan-pcs', 'An toàn tiền va chạm PCS', 'Nhận diện xe, người đi bộ, xe đạp phía trước và tự phanh.'],
                    ['an-toan-drcc', 'Ga tự động theo radar', 'Giữ khoảng cách với xe trước, kể cả khi dừng – đi trong tắc đường.'],
                    ['an-toan-lta', 'Hỗ trợ giữ làn LTA', 'Giữ xe giữa làn khi chạy cao tốc.'],
                    ['an-toan-lda', 'Cảnh báo chệch làn LDA', 'Hỗ trợ đánh lái đưa xe về làn và tránh chướng ngại.'],
                    ['an-toan-sea', 'Hỗ trợ ra khỏi xe SEA', 'Không cho mở cửa trượt khi có xe máy tiến tới từ phía sau.'],
                    ['an-toan-teammate', 'Hỗ trợ đỗ xe', 'Radar quanh thân xe cảnh báo và tự phanh khi đỗ trong không gian hẹp.'],
                ],
            ],
            'gallery' => [
                'thu-vien-2' => 'ghế thương gia',
                'thu-vien-1' => 'khoang 6 chỗ',
                'thu-vien-3' => 'màn hình 48 inch',
                'thu-vien-5' => 'mặt trước',
                'thu-vien-8' => 'bản đen',
            ],
            'colors' => [
                ['Xám bạc', '#A39C8E', 'mau-xam'],
                ['Đen', '#17181A', 'mau-den'],
                ['Trắng ngọc trai', '#ECEBE7', 'mau-trang'],
                ['Đỏ rượu vang', '#5C1320', 'mau-do'],
            ],
            'variants' => [
                ['name' => 'LM 500h 6 chỗ', 'image' => 'phien-ban/6-cho', 'price' => 7_210_000_000, 'note' => 'Doanh nghiệp & gia đình'],
                ['name' => 'LM 500h 4 chỗ VIP', 'image' => 'phien-ban/4-cho', 'price' => 8_950_000_000, 'note' => 'Vách ngăn · màn hình 48 inch'],
            ],
            'specs' => [
                'Động cơ' => [
                    'Hệ truyền động'      => '2.4L tăng áp + 2 mô-tơ điện (hybrid)',
                    'Tổng công suất'      => '366 HP',
                    'Mô-tơ trước / sau'   => '64 kW / 76 kW (eAxle)',
                    'Hộp số'              => 'Tự động 6 cấp',
                    'Dẫn động'            => 'AWD DIRECT4',
                    'Tăng tốc 0–100 km/h' => '6,8 giây',
                ],
                'Khung gầm & tiện nghi' => [
                    'Treo trước / sau'  => 'MacPherson / tay đòn kép, giảm chấn thích ứng AVS',
                    'Chế độ lái'        => 'Normal · Eco · Sport · Rear Comfort · Custom',
                    'Chống ồn chủ động' => 'Có (ANC)',
                    'Lốp'               => '225/55R19',
                    'Số chỗ ngồi'       => '4 (VIP) hoặc 6',
                ],
            ],
            'faq' => [
                'Giá xe Lexus LM 500h 2026 bao nhiêu?' =>
                    'Lexus LM 500h có 2 phiên bản: 6 chỗ 7,21 tỷ đồng và 4 chỗ VIP 8,95 tỷ đồng (giá niêm yết, đã gồm VAT).',
                'Lexus LM 4 chỗ và 6 chỗ khác nhau thế nào?' =>
                    'Bản 4 chỗ có vách ngăn với khoang lái, màn hình 48 inch và hai ghế thương gia — dành cho đưa đón '
                    .'lãnh đạo. Bản 6 chỗ thêm hàng ghế thứ ba, phù hợp gia đình và doanh nghiệp.',
                'Lexus LM có tiết kiệm nhiên liệu không?' =>
                    'LM 500h là xe hybrid: máy xăng 2.4L tăng áp kết hợp hai mô-tơ điện, tắt máy xăng khi đi chậm '
                    .'và tận dụng năng lượng phanh, nên tiết kiệm hơn đáng kể so với MPV cỡ lớn chạy xăng thuần.',
            ] + $this->commonFaq('Lexus LM'),
            'seo' => [
                'title'       => 'Lexus LM 500h 2026: giá từ 7,21 tỷ, 4 & 6 chỗ | Lexus Thăng Long',
                'description' => 'Giá Lexus LM 500h 2026 tại Hà Nội: 6 chỗ 7,21 tỷ, 4 chỗ VIP 8,95 tỷ. MPV hạng sang hybrid 366 HP, '
                    .'màn hình 48 inch. Xem nội thất và đặt lịch trải nghiệm tại Lexus Thăng Long.',
            ],
        ];
    }

    private function ls(): array
    {
        return [
            'slug'       => 'ls',
            'name'       => 'Lexus LS',
            'tagline'    => 'Sedan đầu bảng, tinh hoa Takumi',
            'category'   => 'sedan',
            'price_from' => 8_030_000_000,
            'highlights' => [
                ['value' => '354', 'unit' => 'HP', 'label' => 'V6 3.5L Multi Stage Hybrid'],
                ['value' => '6,24', 'unit' => 'L/100 km', 'label' => 'Tiêu thụ kết hợp'],
                ['value' => '3.125', 'unit' => 'mm', 'label' => 'Chiều dài cơ sở'],
                ['value' => 'Khí nén', 'unit' => '', 'label' => 'Hệ thống treo'],
            ],
            'exterior' => [
                'image' => 'ngoai-that',
                'alt'   => 'Lexus LS 500h màu xanh rêu',
                'title' => 'Thiết kế',
                'intro' => 'Dài 5,2 m. Mái thấp như một chiếc coupe.',
                'body'  => 'LS là mẫu sedan cao cấp nhất của Lexus. Thân xe thấp và dài, đường mái vuốt dần về '
                    .'đuôi, lưới tản nhiệt con suốt dạng lưới 3D gồm hàng nghìn mắt lưới ghép thủ công.',
            ],
            'interior' => [
                'image' => 'noi-that',
                'alt'   => 'Khoang lái Lexus LS 500h',
                'title' => 'Nội thất',
                'intro' => 'Tấm ốp cửa xếp nếp bằng tay.',
                'body'  => 'Tấm ốp cửa vải xếp nếp và kính cắt Kiriko do nghệ nhân Takumi hoàn thiện thủ công. '
                    .'Ghế sau chỉnh điện, có mát-xa và màn hình giải trí riêng cho từng hành khách.',
            ],
            'details' => [
                'Ngoại thất' => [
                    ['dau-xe', 'Đầu xe — lưới Spindle', ''],
                    ['than-xe', 'Thân xe trọng tâm thấp', ''],
                    ['duoi-xe', 'Đuôi xe — đèn hậu liên kết', ''],
                ],
                'Mâm xe' => [
                    ['mam-20', 'Vành nhôm 20 inch', 'LS 500h'],
                ],
                'Nội thất' => [
                    ['noi-that-den', 'Đen Black', ''],
                    ['noi-that-do-den', 'Đỏ & đen', ''],
                    ['noi-that-vang-nau', 'Vàng nâu', ''],
                    ['noi-that-hat-de', 'Hạt dẻ', ''],
                    ['ghe-sau', 'Ghế sau tự ngả', ''],
                ],
                'Ốp trang trí' => [
                    ['op-art-wood-huu-co', 'Art Wood vân hữu cơ', ''],
                    ['op-art-wood-herringbone', 'Art Wood vân herringbone', ''],
                    ['op-cat-laser', 'Gỗ cắt laser', ''],
                    ['kinh-kiriko', 'Kính cắt Kiriko', ''],
                ],
                'Vô-lăng & tiện nghi' => [
                    ['volang', 'Vô-lăng bọc da', ''],
                    ['can-so', 'Cần số & công tắc chế độ lái', ''],
                    ['tapli-cua', 'Táp-li cửa xếp nếp Takumi', ''],
                    ['man-hinh-sau', 'Màn hình giải trí hàng sau', ''],
                ],
            ],
            'safety' => [
                'intro' => 'Lexus Safety System+ A.',
                'items' => [
                    ['an-toan-pcs', 'An toàn tiền va chạm PCS', 'Nhận diện xe và người đi bộ phía trước, cảnh báo và tự phanh.'],
                    ['an-toan-lta', 'Hỗ trợ giữ làn LTA', 'Giữ xe giữa làn khi chạy ga tự động theo radar.'],
                    ['an-toan-bsm', 'Cảnh báo điểm mù BSM', 'Radar hai bên báo phương tiện trong vùng khuất gương.'],
                    ['an-toan-ahb', 'Tự động pha/cốt AHB', 'Tự chuyển giữa đèn pha và đèn cốt khi gặp xe ngược chiều.'],
                    ['an-toan-pksb', 'Phanh hỗ trợ đỗ xe PKSB', 'Tự phanh khi lùi gần vật cản hoặc có xe cắt ngang.'],
                    ['an-toan-sea', 'Hệ thống túi khí SRS', 'Túi khí trước, bên hông, rèm và đệm ghế bảo vệ mọi hành khách.'],
                ],
            ],
            'gallery' => [
                'thu-vien-3' => 'ghế sau thương gia',
                'thu-vien-1' => 'lưới tản nhiệt',
                'thu-vien-2' => 'đuôi xe LS 500h',
                'thu-vien-4' => 'màn hình hàng sau',
                'thu-vien-5' => 'ốp cửa xếp nếp thủ công',
            ],
            'colors' => [
                ['Xanh đậm', '#1F3563', 'mau-xanh'],
                ['Bạc', '#B7B8B8', 'mau-xam', 'bac'],
                ['Trắng', '#EAEAE8', 'mau-trang'],
                ['Đen', '#2A2B2E', 'mau-den'],
                ['Đỏ', '#7A1424', 'mau-do'],
            ],
            'variants' => [
                ['name' => 'LS 500h', 'image' => 'phien-ban/500h', 'price' => 8_030_000_000, 'note' => 'V6 3.5L hybrid · dẫn động cầu sau'],
            ],
            'specs' => [
                'Động cơ — LS 500h' => [
                    'Loại động cơ'            => 'V6 3.456 cc, hybrid',
                    'Công suất động cơ xăng'  => '295 HP tại 5.800 vòng/phút',
                    'Mô-men xoắn'             => '350 Nm tại 5.100 vòng/phút',
                    'Tổng công suất hệ thống' => '354 HP',
                    'Hộp số'                  => 'Multi Stage Hybrid',
                    'Dẫn động'                => 'Cầu sau (RWD)',
                ],
                'Kích thước & không gian' => [
                    'Dài × rộng × cao' => '5.235 × 1.900 × 1.450 mm',
                    'Chiều dài cơ sở'  => '3.125 mm',
                    'Khoảng sáng gầm'  => '147 mm',
                    'Khoang hành lý'   => '440 lít',
                    'Bình nhiên liệu'  => '82 lít',
                ],
                'Vận hành' => [
                    'Treo'             => 'Khí nén trước và sau, giảm chấn thích ứng AVS',
                    'Tiêu thụ kết hợp' => '6,24 L/100 km',
                    'Tiêu thụ đô thị'  => '3,52 L/100 km',
                    'Lốp'              => '245/45R20 run-flat',
                ],
            ],
            'faq' => [
                'Giá xe Lexus LS 500h 2026 bao nhiêu?' =>
                    'Lexus LS 500h có giá niêm yết 8,03 tỷ đồng (đã gồm VAT), động cơ V6 3.5L hybrid 354 HP.',
                'Lexus LS 500h tiêu hao bao nhiêu nhiên liệu?' =>
                    'Theo công bố, LS 500h tiêu thụ 6,24 L/100 km đường kết hợp và chỉ 3,52 L/100 km trong đô thị, '
                    .'nhờ hệ Multi Stage Hybrid chạy điện nhiều ở tốc độ thấp.',
                'Lexus LS và LM, xe nào phù hợp đưa đón lãnh đạo?' =>
                    'LS là sedan: dáng thấp, lịch lãm, lái êm ở tốc độ cao. LM là MPV: khoang sau cao, có vách ngăn và '
                    .'màn hình 48 inch, thuận tiện làm việc trên xe. Nhiều khách chọn LS để tự lái và LM cho tài xế.',
            ] + $this->commonFaq('Lexus LS'),
            'seo' => [
                'title'       => 'Lexus LS 500h 2026: giá 8,03 tỷ, thông số | Lexus Thăng Long',
                'description' => 'Lexus LS 500h 2026 giá 8,03 tỷ tại Hà Nội: sedan đầu bảng V6 hybrid 354 HP, treo khí nén, '
                    .'6,24 L/100 km. Xem màu, nội thất thủ công Takumi và đặt lịch lái thử tại Lexus Thăng Long.',
            ],
        ];
    }
}

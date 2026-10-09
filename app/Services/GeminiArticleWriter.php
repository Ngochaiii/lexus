<?php

namespace App\Services;

use App\Media\MediaStore;
use App\Support\ArticleContext;
use App\Support\RichText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiArticleWriter
{
    public function __construct(
        private readonly MediaStore $media,
        private readonly GeminiClient $gemini,
    ) {}

    /**
     * @return array{excerpt:string,article_html:string,seo_title:string,meta_description:string,keywords:array<int,string>,primary_keyword:string,slug:string,faq:array<int,array{question:string,answer:string}>}
     */
    public function generate(string $title, string $imagePath, ?string $instructions = null): array
    {
        $this->gemini->ensureConfigured();

        $image = $this->imagePart($imagePath);
        $result = $this->gemini->json($this->payload($title, $instructions, $image), 'bài viết');

        try {
            return $this->normalize($result);
        } catch (RuntimeException $exception) {
            Log::warning('Gemini: '.$exception->getMessage(), ['keys' => array_keys($result)]);

            throw $exception;
        }
    }

    /**
     * Câu lệnh viết bài chuẩn SEO/GEO cho website đại lý Lexus.
     *
     * Hai phần: (1) HỒ SƠ DỮ LIỆU THẬT từ database (App\Support\ArticleContext)
     * — giá, lăn bánh tính sẵn, thông số, link nội bộ; Gemini chỉ được lấy số
     * từ đây. (2) Quy trình: nghiên cứu từ khoá trước (từ khoá chính, ý định
     * tìm kiếm, từ khoá phụ, câu hỏi người dùng hay gõ) rồi mới viết theo khung
     * "trả lời trước" để lên đoạn trích nổi bật của Google và được AI trích dẫn.
     *
     * @param  array{mimeType:string,data:string}  $image
     * @return array<string, mixed>
     */
    private function payload(string $title, ?string $instructions, array $image): array
    {
        $siteName = catalog_setting('site_name', config('app.name'));
        $advisor = catalog_setting('advisor_name') ?: 'chuyên viên tư vấn';
        $extra = filled($instructions) ? trim((string) $instructions) : 'Không có yêu cầu bổ sung.';
        $useGoogleSearch = (bool) config('services.gemini.google_search', false);
        $facts = ArticleContext::build($title, $instructions);

        $research = $useGoogleSearch
            ? 'Dùng Google Search để xem các trang đang xếp hạng cho từ khoá chính tại Việt Nam: họ trả lời những ý nào, còn thiếu gì, người dùng hỏi thêm gì (mục "Mọi người cũng hỏi"). Bài của bạn phải trả lời đủ các ý đó và thêm giá trị họ không có. Nguồn ngoài CHỈ để hiểu nhu cầu tìm kiếm — mọi con số giá, phí, thông số vẫn lấy từ HỒ SƠ DỮ LIỆU.'
            : 'Không có công cụ tìm kiếm: suy luận từ khoá và câu hỏi từ hiểu biết về cách người Việt tìm mua xe sang (họ gõ "giá lăn bánh", "bao nhiêu tiền", "trả góp", "so sánh", "có nên mua", tên phiên bản, tên quận/thành phố). Không khẳng định số liệu lượng tìm kiếm.';

        $prompt = <<<PROMPT
        # NHIỆM VỤ
        Viết một bài tiếng Việt chuẩn SEO cho website {$siteName} — website tư vấn của chuyên viên {$advisor} tại đại lý Lexus chính hãng ở Hà Nội. Người đọc là khách đang cân nhắc mua xe Lexus (thu nhập cao, bận rộn, cần con số rõ ràng và lời khuyên thật).

        Tiêu đề biên tập viên đặt: {$title}
        Yêu cầu thêm: {$extra}

        # HỒ SƠ DỮ LIỆU THẬT (nguồn DUY NHẤT cho mọi con số)
        {$facts}

        # QUY TẮC SỰ THẬT — vi phạm là bài bị loại
        1. Giá niêm yết, giá lăn bánh, % trước bạ, phí biển số: CHỈ chép từ hồ sơ trên, đúng từng đồng. Phiên bản ghi "Đang cập nhật" thì viết là đang cập nhật, không đoán.
        2. Không bịa khuyến mại, quà tặng, lãi suất, thời gian giao xe, số lượng xe về, so sánh giá với đại lý khác. KHÔNG dùng các chữ "ưu đãi", "khuyến mại", "giảm giá", "quà tặng", "lãi suất ưu đãi" (kể cả trong meta description và FAQ) trừ khi mục Yêu cầu thêm nêu rõ chương trình đó.
        3. Thông số kỹ thuật chỉ dùng số trong hồ sơ; không có thì nói chung (vd "hệ truyền động hybrid"), không tự điền số.
        4. Phí đăng kiểm, bảo trì đường bộ, bảo hiểm TNDS: chỉ nêu tên khoản và nói "vài triệu đồng, chuyên viên báo chính xác khi làm hồ sơ".
        5. Tiêu đề nhắc một tháng cụ thể (vd tháng 10/2026) thì ghi rõ: giá theo bảng giá hiện hành cập nhật ngày viết bài, có thể điều chỉnh — không dự đoán giá tương lai.

        # BƯỚC 1 — NGHIÊN CỨU TỪ KHOÁ (làm trước khi viết)
        {$research}
        - primary_keyword: MỘT cụm từ khoá chính 3–7 chữ, đúng cách người dùng gõ (vd "giá lăn bánh lexus rx 350h").
        - search_intent: ý định tìm kiếm (tìm giá / so sánh / cân nhắc mua / tìm đại lý…) và người tìm muốn nhận được gì trong 10 giây đầu.
        - secondary_keywords: 6–12 từ khoá phụ, biến thể ngữ nghĩa, từ khoá dài (long-tail) có địa phương (Hà Nội), tên phiên bản, "trả góp", "bao nhiêu"...
        - faq: 4–6 câu hỏi thật người dùng hay hỏi quanh chủ đề, mỗi câu trả lời 40–80 từ, tự đứng được một mình (AI và Google trích nguyên câu trả lời).

        # BƯỚC 2 — VIẾT BÀI (article_html)
        Độ dài 1.200–1.800 từ. HTML sạch, chỉ dùng: p, h2, h3, strong, em, ul, ol, li, blockquote, table, thead, tbody, tr, th, td, a. KHÔNG h1 (tiêu đề trang đã là h1), không Markdown, không script/style/iframe, không code fence, không lặp lại phần FAQ trong thân bài.
        Khung bắt buộc:
        1. Đoạn mở đầu 40–70 từ TRẢ LỜI NGAY câu hỏi chính: câu ĐẦU TIÊN đã chứa từ khoá chính và con số quan trọng nhất (vd "Giá lăn bánh Lexus RX 350h tại Hà Nội khoảng 3,77–4,65 tỷ đồng…"). KHÔNG chào hỏi, không giới thiệu bản thân, không "Trong bài viết này…" ở đoạn này. Đây là đoạn Google/AI trích làm câu trả lời; phần giới thiệu chuyên viên để ở đoạn kết.
        2. 4–7 mục h2 (có h3 khi cần), mỗi h2 chứa từ khoá chính hoặc một từ khoá phụ một cách tự nhiên, sắp theo thứ tự người đọc cần: con số → cách tính/so sánh → lựa chọn phiên bản → chi phí sở hữu/trả góp → mua ở đâu.
        3. Ít nhất MỘT bảng HTML số liệu lấy từ hồ sơ (vd bảng giá niêm yết – trước bạ – biển số – lăn bánh từng phiên bản). Bảng có thead, cột đơn vị rõ ràng.
        4. Một danh sách từng bước hoặc gạch đầu dòng "lưu ý" thực tế.
        5. 3–6 link nội bộ bằng thẻ a, CHỈ dùng đường dẫn trong mục LINK NỘI BỘ, anchor text mô tả nội dung (vd "bảng giá xe Lexus", "xem màu và thông số Lexus RX"), không dùng "tại đây"/"click". Bắt buộc có link tới trang xe liên quan và /bao-gia.
        6. Đoạn kết 2–3 câu: tóm lại lời khuyên + mời nhận báo giá lăn bánh chi tiết hoặc lái thử tại showroom (nêu địa chỉ showroom và số điện thoại chuyên viên từ hồ sơ; không gọi là hotline), giọng nhẹ nhàng, không thúc ép.
        Văn phong: người thật (chuyên viên {$advisor}) chia sẻ kinh nghiệm, câu ngắn, số liệu cụ thể, xưng "bạn"; người viết là chuyên viên, không phải đại lý — không xưng "chúng tôi" thay đại lý. Từ khoá chính xuất hiện tự nhiên 3–5 lần; tuyệt đối không nhồi từ khoá. Khi chèn từ khoá vào câu/đề mục vẫn viết hoa đúng tên riêng (Lexus, RX 350h, Hà Nội) — từ khoá viết thường chỉ để nghiên cứu. Chữ số viết kiểu Việt Nam: đủ số "3.766.000.000 đ" hoặc rút gọn tối đa 2 chữ số thập phân "3,77 tỷ" (không viết "3,766 tỷ"). Viết chữ "khoảng" thay cho ký hiệu ≈ hoặc ~ (font website không hiển thị đúng các ký hiệu này).

        # QUY TẮC GEO — để ChatGPT, Gemini, Google AI trích đúng bài này
        1. Mỗi mục h2 TỰ ĐỨNG ĐƯỢC khi bị tách riêng: 1–2 câu đầu của mục trả lời thẳng câu hỏi của mục, nhắc lại chủ ngữ đầy đủ (tên xe, phiên bản, Hà Nội) thay cho "nó", "xe này", "như trên".
        2. Ghi nguồn của con số ngay trong câu, theo đúng hồ sơ: "theo bảng giá niêm yết Lexus Việt Nam (cập nhật ngày viết bài)", "theo thông số hãng công bố", "lệ phí trước bạ ô tô tại Hà Nội 12%". Không nêu nguồn mà hồ sơ không có.
        3. Thực thể nhất quán: lần đầu gọi tên đầy đủ (vd "Lexus ES 350h Premium", "chuyên viên {$advisor} tại Lexus Thăng Long"), sau đó giữ đúng một cách gọi; tên phiên bản viết đúng như hồ sơ.
        4. Kinh nghiệm (E-E-A-T): chỉ chia sẻ dạng lời khuyên nghề nghiệp chung (khách thường hỏi gì, nên kiểm tra gì khi chọn bản) — không bịa trải nghiệm, câu chuyện khách hàng, số khách đã tư vấn, giải thưởng, chức danh.
        5. Câu khách hỏi AI (nếu có trong Yêu cầu thêm): trả lời đủ từng câu trong thân bài hoặc FAQ, bằng câu trả lời có con số cụ thể.

        # BƯỚC 3 — THẺ SEO
        - seo_title: tối đa 60 ký tự, BẮT ĐẦU bằng từ khoá chính, có yếu tố gợi nhấp (con số, năm, "chi tiết từng phiên bản"); không cần thêm tên website.
        - meta_description: 140–160 ký tự, có từ khoá chính + một con số cụ thể + lời mời hành động (vd "Nhận báo giá lăn bánh chi tiết"), không hứa ưu đãi.
        - excerpt: 2–3 câu, tối đa 300 ký tự, tóm đúng câu trả lời chính.
        - slug: không dấu, chữ thường, nối gạch ngang, 3–7 từ, chứa từ khoá chính (vd gia-lan-banh-lexus-rx-350h-ha-noi).
        - keywords: từ khoá chính + các từ khoá phụ đã thực sự dùng trong bài.
        PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'primary_keyword' => ['type' => 'string', 'description' => 'Từ khoá chính 3–7 chữ.'],
                'search_intent' => ['type' => 'string', 'description' => 'Ý định tìm kiếm, 1–2 câu.'],
                'secondary_keywords' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 5, 'maxItems' => 12],
                'seo_title' => ['type' => 'string', 'description' => 'Tối đa 60 ký tự, bắt đầu bằng từ khoá chính.'],
                'meta_description' => ['type' => 'string', 'description' => '140–160 ký tự.'],
                'slug' => ['type' => 'string', 'description' => 'Slug không dấu chứa từ khoá chính.'],
                'excerpt' => ['type' => 'string', 'description' => 'Tối đa 300 ký tự.'],
                'article_html' => ['type' => 'string', 'description' => 'Thân bài HTML sạch, không h1, không FAQ.'],
                'faq' => [
                    'type' => 'array',
                    'minItems' => 3,
                    'maxItems' => 6,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'answer' => ['type' => 'string'],
                        ],
                        'required' => ['question', 'answer'],
                    ],
                ],
                'keywords' => [
                    'type' => 'array',
                    'description' => 'Từ khoá chính + từ khoá phụ đã dùng trong bài.',
                    'items' => ['type' => 'string'],
                    'minItems' => 5,
                    'maxItems' => 12,
                ],
            ],
            'required' => ['primary_keyword', 'search_intent', 'secondary_keywords', 'seo_title', 'meta_description', 'slug', 'excerpt', 'article_html', 'faq', 'keywords'],
            'additionalProperties' => false,
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [[
                    'text' => 'Bạn là biên tập viên ô tô hạng sang kiêm chuyên gia SEO/GEO tiếng Việt. Nghiên cứu từ khoá trước, viết sau. '
                        .'Độ chính xác số liệu quan trọng hơn mọi thứ: chỉ dùng số trong HỒ SƠ DỮ LIỆU được cung cấp. '
                        .'Viết cho người đọc trước, công cụ tìm kiếm sau; không nhồi từ khoá.',
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['inlineData' => $image],
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => [
                // Thấp hơn mặc định: bài giá xe cần bám số liệu, không cần "bay".
                'temperature' => 0.55,
                'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 16384),
                'responseFormat' => [
                    'text' => [
                        'mimeType' => 'APPLICATION_JSON',
                        'schema' => $schema,
                    ],
                ],
            ],
        ];

        if ($useGoogleSearch) {
            $payload['tools'] = [['googleSearch' => (object) []]];
        }

        return $payload;
    }

    /** @return array{mimeType:string,data:string} */
    private function imagePart(string $path): array
    {
        $bytes = $this->media->read($path);

        if ($bytes === null || $bytes === '') {
            throw new RuntimeException('Không đọc được ảnh bìa. Hãy tải ảnh lên lại rồi thử tiếp.');
        }

        $maxBytes = (int) config('services.gemini.max_inline_image_bytes', 10 * 1024 * 1024);

        if (strlen($bytes) > $maxBytes) {
            throw new RuntimeException('Ảnh bìa quá lớn để gửi cho Gemini. Hãy dùng ảnh dưới 10 MB.');
        }

        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true)) {
            throw new RuntimeException('Định dạng ảnh bìa không được Gemini hỗ trợ.');
        }

        return [
            'mimeType' => $mime,
            'data' => base64_encode($bytes),
        ];
    }

    /**
     * @return array{excerpt:string,article_html:string,seo_title:string,meta_description:string,keywords:array<int,string>,primary_keyword:string,slug:string,faq:array<int,array{question:string,answer:string}>}
     */
    private function normalize(mixed $result): array
    {
        if (! is_array($result)) {
            throw new RuntimeException('Gemini trả về nội dung không đúng cấu trúc. Vui lòng tạo lại bài.');
        }

        $article = RichText::clean((string) ($result['article_html'] ?? ''));
        $article = preg_replace('/<\/?h1\b[^>]*>/i', '', $article) ?? $article;
        // Phòng khi Gemini vẫn dùng ký hiệu: "≈ 3,77 tỷ" → "khoảng 3,77 tỷ".
        $approx = fn (string $t): string => preg_replace(['/\(\s*[≈~]\s*/u', '/\s*[≈~]\s*/u'], ['(khoảng ', ' khoảng '], $t) ?? $t;
        $article = $approx($article);
        foreach (['excerpt', 'meta_description', 'seo_title'] as $field) {
            if (is_string($result[$field] ?? null)) {
                $result[$field] = trim($approx($result[$field]));
            }
        }
        if (is_array($result['faq'] ?? null)) {
            $result['faq'] = array_map(fn ($q) => is_array($q) ? array_map(fn ($v) => is_string($v) ? $approx($v) : $v, $q) : $q, $result['faq']);
        }

        $primary = trim((string) ($result['primary_keyword'] ?? ''));
        $keywords = collect([$primary, ...($result['keywords'] ?? []), ...($result['secondary_keywords'] ?? [])])
            ->filter(fn (mixed $keyword): bool => is_string($keyword) && filled($keyword))
            ->map(fn (string $keyword): string => trim($keyword))
            ->unique(fn (string $keyword): string => Str::lower($keyword))
            ->take(12)
            ->values()
            ->all();

        if (mb_strlen(trim(strip_tags($article))) < 300 || count($keywords) < 3) {
            throw new RuntimeException('Bài Gemini tạo ra quá ngắn hoặc thiếu từ khóa. Vui lòng tạo lại bài.');
        }

        foreach (['excerpt', 'seo_title', 'meta_description'] as $field) {
            if (blank($result[$field] ?? null)) {
                throw new RuntimeException('Gemini trả về thiếu thông tin SEO. Vui lòng tạo lại bài.');
            }
        }

        $faq = collect($result['faq'] ?? [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['question'] ?? null) && filled($row['answer'] ?? null))
            ->map(fn (array $row): array => [
                'question' => trim(strip_tags((string) $row['question'])),
                'answer' => trim(strip_tags((string) $row['answer'])),
            ])
            ->take(6)->values()->all();

        return [
            'excerpt' => Str::limit(trim((string) $result['excerpt']), 400, ''),
            'article_html' => $article,
            'seo_title' => Str::limit(trim((string) $result['seo_title']), 70, ''),
            'meta_description' => Str::limit(trim((string) $result['meta_description']), 170, ''),
            'keywords' => $keywords,
            'primary_keyword' => $primary !== '' ? $primary : ($keywords[0] ?? ''),
            'slug' => Str::limit(Str::slug((string) ($result['slug'] ?? '')), 80, ''),
            'faq' => $faq,
        ];
    }
}

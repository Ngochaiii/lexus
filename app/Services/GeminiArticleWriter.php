<?php

namespace App\Services;

use App\Media\MediaStore;
use App\Support\RichText;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class GeminiArticleWriter
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function __construct(private readonly MediaStore $media) {}

    /**
     * @return array{excerpt:string,article_html:string,seo_title:string,meta_description:string,keywords:array<int,string>}
     */
    public function generate(string $title, string $imagePath, ?string $instructions = null): array
    {
        $key = trim((string) config('services.gemini.key'));
        $model = trim((string) config('services.gemini.model', 'gemini-3.8-flash'));

        if ($key === '') {
            throw new RuntimeException('Gemini chưa được cấu hình. Hãy thêm GEMINI_API_KEY vào file .env.');
        }

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $model)) {
            throw new RuntimeException('Tên model Gemini trong cấu hình không hợp lệ.');
        }

        $image = $this->imagePart($imagePath);
        $payload = $this->payload($title, $instructions, $image);
        $models = $this->models($model);
        $rounds = max(1, (int) config('services.gemini.retry_rounds', 3));
        $response = null;
        $usedModel = $model;
        $exhausted = [];

        // Google trả 503 "high demand" theo từng đợt vài giây trên các model flash
        // mới. Vì vậy thử hết danh sách model một lượt, nếu vẫn quá tải thì nghỉ
        // 2s, 4s… rồi lặp lại toàn bộ danh sách thay vì chỉ gọi mỗi model một lần.
        // Model trả 429 (hết 20 request/ngày của Free Tier) bị loại khỏi các đợt sau.
        for ($round = 1; $round <= $rounds; $round++) {
            $remaining = array_values(array_diff($models, $exhausted));

            if ($remaining === []) {
                break;
            }

            if ($round > 1) {
                Sleep::for(2 ** ($round - 1))->seconds();
            }

            foreach ($remaining as $candidateModel) {
                $usedModel = $candidateModel;

                try {
                    $response = Http::connectTimeout(10)
                        ->timeout((int) config('services.gemini.timeout', 90))
                        ->acceptJson()
                        ->withHeaders(['x-goog-api-key' => $key])
                        ->post(sprintf(self::ENDPOINT, $candidateModel), $payload);
                } catch (ConnectionException $exception) {
                    Log::warning('Gemini: không kết nối được', ['model' => $candidateModel, 'error' => $exception->getMessage()]);

                    throw new RuntimeException('Không kết nối được tới Gemini. Vui lòng thử lại sau.');
                }

                if (! $response->successful()) {
                    Log::warning('Gemini: HTTP lỗi', [
                        'model' => $candidateModel,
                        'round' => $round,
                        'status' => $response->status(),
                        'error' => Str::limit((string) $response->json('error.message'), 300),
                    ]);
                }

                if ($response->status() === 429) {
                    $exhausted[] = $candidateModel;

                    continue;
                }

                if (! in_array($response->status(), [500, 502, 503, 504], true)) {
                    break 2;
                }
            }
        }

        if ($response === null) {
            throw new RuntimeException('Không có model Gemini hợp lệ để tạo bài viết.');
        }

        if ($response->failed()) {
            if ($response->status() === 429) {
                throw new RuntimeException(
                    'Gemini đang hết hạn mức của dự án API (HTTP 429). Gói miễn phí chỉ cho 20 bài/ngày với mỗi model; '
                    .'hãy thử lại sau vài phút hoặc sang ngày mai, hoặc bật Billing trong Google AI Studio để nâng hạn mức.',
                );
            }

            if (in_array($response->status(), [500, 502, 503, 504], true)) {
                throw new RuntimeException(
                    'Gemini đang quá tải hoặc tạm thời gián đoạn (HTTP '.$response->status().'). '
                    .'Hệ thống đã thử lại nhưng chưa thành công; vui lòng thử lại sau ít phút.',
                );
            }

            $detail = Str::limit((string) $response->json('error.message'), 180);

            throw new RuntimeException(
                'Gemini không tạo được bài viết (HTTP '.$response->status().').'
                .($detail !== '' ? ' '.$detail : ''),
            );
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        $context = [
            'model' => $usedModel,
            'finish_reason' => $response->json('candidates.0.finishReason'),
            'usage' => $response->json('usageMetadata'),
            'text' => Str::limit($text, 500),
        ];

        // Gemini 3.x tính cả token "suy nghĩ" (~2.000–2.500) vào trần này,
        // nên trần thấp sẽ cắt cụt JSON giữa chừng và báo lỗi định dạng sai lệch.
        if ($response->json('candidates.0.finishReason') === 'MAX_TOKENS') {
            Log::warning('Gemini: bài bị cắt vì hết trần token', $context);

            throw new RuntimeException(
                'Bài viết bị cắt vì vượt trần token đầu ra của Gemini. '
                .'Hãy tăng GEMINI_MAX_OUTPUT_TOKENS trong file .env (khuyến nghị 8192 trở lên) rồi tạo lại bài.',
            );
        }

        if ($text === '') {
            Log::warning('Gemini: không trả về nội dung', $context + ['body' => Str::limit($response->body(), 800)]);

            throw new RuntimeException('Gemini không trả về nội dung. Vui lòng thử lại.');
        }

        try {
            $result = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            Log::warning('Gemini: JSON không hợp lệ', $context);

            throw new RuntimeException('Gemini trả về dữ liệu không đúng định dạng. Vui lòng tạo lại bài.');
        }

        try {
            return $this->normalize($result);
        } catch (RuntimeException $exception) {
            Log::warning('Gemini: '.$exception->getMessage(), $context + [
                'keys' => is_array($result) ? array_keys($result) : gettype($result),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array{mimeType:string,data:string}  $image
     * @return array<string, mixed>
     */
    private function payload(string $title, ?string $instructions, array $image): array
    {
        $siteName = catalog_setting('site_name', config('app.name'));
        $extra = filled($instructions) ? trim((string) $instructions) : 'Không có yêu cầu bổ sung.';
        $useGoogleSearch = (bool) config('services.gemini.google_search', false);
        $keywordResearch = $useGoogleSearch
            ? 'Hãy phân tích ảnh đi kèm và dùng Google Search để xác định ngữ cảnh, nhu cầu tìm kiếm và các cụm từ đang được quan tâm có liên quan trực tiếp đến tiêu đề.'
            : 'Hãy phân tích ảnh đi kèm, ngữ cảnh và ý định tìm kiếm để chọn các cụm từ khóa sát chủ đề. Không khẳng định chúng là xu hướng thời gian thực khi không có dữ liệu tìm kiếm.';

        $prompt = <<<PROMPT
        Viết một bài tin tức tiếng Việt hoàn chỉnh cho website đại lý {$siteName}.

        Tiêu đề do biên tập viên cung cấp: {$title}
        Yêu cầu bổ sung: {$extra}
        Ngày hiện tại: {$this->today()}

        {$keywordResearch} Chỉ dùng thông tin có thể kiểm chứng; không tự bịa giá bán, thông số, khuyến mại hay thời hạn. Không sao chép câu văn từ nguồn khác.

        Yêu cầu biên tập và SEO:
        - Bài dài khoảng 900–1.400 từ, mở đầu trả lời nhanh đúng ý định tìm kiếm.
        - Văn phong tự nhiên, hữu ích, dễ đọc; ưu tiên thông tin thực tế cho người đang tìm hiểu xe VinFast.
        - Chọn 5–10 từ khóa/cụm từ khóa sát chủ đề và có ý định tìm kiếm rõ ràng. Đưa chúng vào bài tự nhiên cùng các biến thể ngữ nghĩa; tuyệt đối không nhồi từ khóa.
        - Chỉ xuất nội dung thân bài bằng HTML sạch: p, h2, h3, strong, em, ul, ol, li, blockquote, table, thead, tbody, tr, th, td và a. Không dùng h1 vì tiêu đề trang đã là h1. Không dùng Markdown, script, style, iframe hoặc code fence.
        - Có 3–6 đề mục h2/h3, đoạn văn ngắn, danh sách khi phù hợp và kết bài có lời mời liên hệ đại lý nhẹ nhàng.
        - SEO title dài tối đa 70 ký tự; meta description 140–165 ký tự; tóm tắt tối đa 400 ký tự.
        PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'excerpt' => ['type' => 'string', 'description' => 'Tóm tắt hấp dẫn, tối đa 400 ký tự.'],
                'article_html' => ['type' => 'string', 'description' => 'Toàn bộ thân bài dưới dạng HTML sạch, không có h1.'],
                'seo_title' => ['type' => 'string', 'description' => 'SEO title tối đa 70 ký tự.'],
                'meta_description' => ['type' => 'string', 'description' => 'Meta description từ 140 đến 165 ký tự.'],
                'keywords' => [
                    'type' => 'array',
                    'description' => '5 đến 10 cụm từ khóa đã được dùng tự nhiên trong bài.',
                    'items' => ['type' => 'string'],
                    'minItems' => 5,
                    'maxItems' => 10,
                ],
            ],
            'required' => ['excerpt', 'article_html', 'seo_title', 'meta_description', 'keywords'],
            'additionalProperties' => false,
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [[
                    'text' => 'Bạn là biên tập viên ô tô và chuyên gia SEO nội dung. Ưu tiên độ chính xác, tính hữu ích và ngôn ngữ tự nhiên hơn mật độ từ khóa.',
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
                'temperature' => 0.75,
                'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 8192),
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
     * @return array{excerpt:string,article_html:string,seo_title:string,meta_description:string,keywords:array<int,string>}
     */
    private function normalize(mixed $result): array
    {
        if (! is_array($result)) {
            throw new RuntimeException('Gemini trả về nội dung không đúng cấu trúc. Vui lòng tạo lại bài.');
        }

        $article = RichText::clean((string) ($result['article_html'] ?? ''));
        $article = preg_replace('/<\/?h1\b[^>]*>/i', '', $article) ?? $article;
        $keywords = collect($result['keywords'] ?? [])
            ->filter(fn (mixed $keyword): bool => is_string($keyword) && filled($keyword))
            ->map(fn (string $keyword): string => trim($keyword))
            ->unique(fn (string $keyword): string => Str::lower($keyword))
            ->take(10)
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

        return [
            'excerpt' => Str::limit(trim((string) $result['excerpt']), 400, ''),
            'article_html' => $article,
            'seo_title' => Str::limit(trim((string) $result['seo_title']), 70, ''),
            'meta_description' => Str::limit(trim((string) $result['meta_description']), 170, ''),
            'keywords' => $keywords,
        ];
    }

    private function today(): string
    {
        return now()->toDateString();
    }

    /** @return array<int, string> */
    private function models(string $primary): array
    {
        $fallbacks = config('services.gemini.fallback_models', []);
        $fallbacks = is_string($fallbacks) ? explode(',', $fallbacks) : (array) $fallbacks;

        $models = collect([$primary, ...$fallbacks])
            ->map(fn (mixed $model): string => trim((string) $model))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($models as $model) {
            if (! preg_match('/^[a-zA-Z0-9._-]+$/', $model)) {
                throw new RuntimeException('Tên model Gemini dự phòng trong cấu hình không hợp lệ.');
            }
        }

        return $models;
    }
}

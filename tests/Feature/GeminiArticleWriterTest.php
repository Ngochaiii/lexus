<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Media\MediaStore;
use App\Models\User;
use App\Services\GeminiArticleWriter;
use App\Support\RichText;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class GeminiArticleWriterTest extends TestCase
{
    private string $imagePath = 'catalog/posts/gemini-cover.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.model' => 'gemini-3.8-flash',
            'services.gemini.fallback_models' => ['gemini-3.7-flash'],
            'services.gemini.google_search' => true,
        ]);

        app(MediaStore::class)->write(
            $this->imagePath,
            UploadedFile::fake()->image('gemini-cover.jpg', 1200, 675)->getContent(),
        );
    }

    public function test_gui_tieu_de_anh_va_nhan_json_seo_tu_gemini(): void
    {
        $articleHtml = '<p>'.str_repeat('Giá lăn bánh Lexus RX 350h tại Hà Nội. ', 20).'</p>';

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'primary_keyword' => 'giá lăn bánh lexus rx 350h',
                                'search_intent' => 'Tìm tổng chi phí mua RX 350h tại Hà Nội.',
                                'secondary_keywords' => ['lexus rx 350h premium', 'lăn bánh hà nội'],
                                'excerpt' => 'Tóm tắt bài viết hấp dẫn.',
                                'article_html' => $articleHtml,
                                'seo_title' => 'Giá lăn bánh Lexus RX 350h tại Hà Nội',
                                'meta_description' => 'Giá lăn bánh Lexus RX 350h tại Hà Nội từ khoảng 3,77 tỷ: giá niêm yết, trước bạ 12%, biển số và cách nhận báo giá chi tiết.',
                                'slug' => 'Giá lăn bánh Lexus RX 350h Hà Nội',
                                'faq' => [['question' => 'Lăn bánh RX 350h bao nhiêu?', 'answer' => 'Khoảng 3,77 tỷ đồng tại Hà Nội.']],
                                'keywords' => [
                                    'giá lăn bánh lexus rx 350h',
                                    'lexus rx 350h',
                                    'lexus rx 2026',
                                    'lệ phí trước bạ',
                                    'mua lexus hà nội',
                                ],
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $rx = \App\Models\Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now()]);
        $rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000]);

        $result = app(GeminiArticleWriter::class)->generate(
            'Giá lăn bánh Lexus RX 350h tại Hà Nội tháng 10/2026',
            $this->imagePath,
            'Viết cho người mua xe lần đầu.',
        );

        $this->assertSame('Giá lăn bánh Lexus RX 350h tại Hà Nội', $result['seo_title']);
        $this->assertSame('giá lăn bánh lexus rx 350h', $result['primary_keyword']);
        $this->assertSame('gia-lan-banh-lexus-rx-350h-ha-noi', $result['slug']);
        $this->assertCount(1, $result['faq']);
        $this->assertContains('lăn bánh hà nội', $result['keywords'], 'từ khoá phụ được gộp vào danh sách');

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent'
                && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
                && data_get($data, 'contents.0.parts.0.inlineData.mimeType') === 'image/jpeg'
                && filled(data_get($data, 'contents.0.parts.0.inlineData.data'))
                && array_key_exists('googleSearch', data_get($data, 'tools.0', []))
                && data_get($data, 'generationConfig.maxOutputTokens') === 16384
                && data_get($data, 'generationConfig.responseFormat.text.mimeType') === 'APPLICATION_JSON'
                // Giá thật + lăn bánh tính sẵn nằm trong câu lệnh; không còn sót chữ VinFast.
                && str_contains($prompt = (string) data_get($data, 'contents.0.parts.1.text'), 'HỒ SƠ DỮ LIỆU THẬT')
                && str_contains($prompt, '3.350.000.000 đ')
                && str_contains($prompt, '3.766.000.000 đ')
                && str_contains($prompt, '/san-pham/rx')
                && ! str_contains($prompt, 'VinFast');
        });
    }

    public function test_khong_co_api_key_thi_bao_loi_ro_rang_va_khong_goi_mang(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY');

        try {
            app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_free_tier_khong_gui_google_search_nhung_van_yeu_cau_tu_khoa_seo(): void
    {
        config(['services.gemini.google_search' => false]);
        $articleHtml = '<p>'.str_repeat('Nội dung hữu ích về xe Lexus. ', 20).'</p>';

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'excerpt' => 'Tóm tắt bài viết.',
                            'article_html' => $articleHtml,
                            'seo_title' => 'Tiêu đề SEO Lexus',
                            'meta_description' => 'Nội dung mô tả SEO hữu ích về xe Lexus dành cho khách hàng đang tìm hiểu và lựa chọn phiên bản phù hợp tại Hà Nội.',
                            'keywords' => ['xe lexus', 'lexus hà nội', 'giá xe lexus'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ]),
        ]);

        app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return ! array_key_exists('tools', $data)
                && str_contains((string) data_get($data, 'contents.0.parts.1.text'), 'NGHIÊN CỨU TỪ KHOÁ')
                && str_contains((string) data_get($data, 'contents.0.parts.1.text'), 'Không có công cụ tìm kiếm');
        });
    }

    public function test_loi_quota_tu_gemini_duoc_doi_thanh_thong_bao_de_hieu_va_khong_goi_lap(): void
    {
        Sleep::fake();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Quota exceeded'],
            ], 429),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hết hạn mức');

        try {
            app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);
        } finally {
            // Mỗi model chỉ gọi đúng một lần rồi dừng, không nghỉ chờ rồi gọi lại.
            Http::assertSentCount(2);
            Sleep::assertNeverSlept();
        }
    }

    public function test_model_chinh_het_quota_ngay_thi_chuyen_sang_model_du_phong(): void
    {
        Sleep::fake();

        $articleHtml = '<p>'.str_repeat('Nội dung từ model dự phòng khi model chính hết quota ngày. ', 20).'</p>';

        Http::fakeSequence()
            ->push(['error' => ['message' => 'Quota exceeded']], 429)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'excerpt' => 'Tóm tắt từ model dự phòng.',
                            'article_html' => $articleHtml,
                            'seo_title' => 'SEO title từ model dự phòng',
                            'meta_description' => 'Meta description hợp lệ khi model chính đã dùng hết 20 request miễn phí trong ngày và hệ thống chuyển sang model khác.',
                            'keywords' => ['xe lexus', 'lexus hà nội', 'kinh nghiệm mua xe'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ], 200);

        $result = app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);

        $this->assertSame('SEO title từ model dự phòng', $result['seo_title']);
        Http::assertSentCount(2);
        Sleep::assertNeverSlept();
    }

    public function test_model_het_quota_khong_bi_goi_lai_o_dot_sau(): void
    {
        Sleep::fake();

        $articleHtml = '<p>'.str_repeat('Nội dung sau khi model dự phòng hết quá tải. ', 20).'</p>';

        Http::fakeSequence()
            ->push(['error' => ['message' => 'Quota exceeded']], 429)
            ->push(['error' => ['message' => 'High demand']], 503)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'excerpt' => 'Tóm tắt.',
                            'article_html' => $articleHtml,
                            'seo_title' => 'SEO title đợt hai model dự phòng',
                            'meta_description' => 'Meta description hợp lệ khi model chính hết quota còn model dự phòng quá tải ở đợt một và thành công ở đợt hai.',
                            'keywords' => ['xe lexus', 'lexus hà nội', 'kinh nghiệm mua xe'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ], 200);

        $result = app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);

        $this->assertSame('SEO title đợt hai model dự phòng', $result['seo_title']);

        $urls = collect(Http::recorded())
            ->map(fn (array $pair): string => $pair[0]->url())
            ->all();

        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent',
        ], $urls);
        Sleep::assertSequence([Sleep::for(2)->seconds()]);
    }

    public function test_loi_qua_tai_tu_dong_chuyen_sang_model_du_phong(): void
    {
        $articleHtml = '<p>'.str_repeat('Nội dung bài viết sau khi Gemini hết quá tải. ', 20).'</p>';

        Http::fakeSequence()
            ->push(['error' => ['message' => 'High demand']], 503)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'excerpt' => 'Tóm tắt sau retry.',
                            'article_html' => $articleHtml,
                            'seo_title' => 'SEO title sau retry',
                            'meta_description' => 'Meta description hợp lệ sau khi hệ thống tự thử lại request Gemini bị quá tải và nhận đủ dữ liệu thành công.',
                            'keywords' => ['xe lexus', 'lexus hà nội', 'kinh nghiệm mua xe'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ], 200);

        $result = app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);

        $this->assertSame('SEO title sau retry', $result['seo_title']);
        Http::assertSentCount(2);

        $urls = collect(Http::recorded())
            ->map(fn (array $pair): string => $pair[0]->url())
            ->all();

        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent',
        ], $urls);
    }

    public function test_ca_hai_model_qua_tai_thi_nghi_roi_thu_lai_dot_sau(): void
    {
        Sleep::fake();

        $articleHtml = '<p>'.str_repeat('Nội dung bài viết sau khi Gemini hết quá tải. ', 20).'</p>';
        $overloaded = ['error' => ['message' => 'High demand']];

        Http::fakeSequence()
            ->push($overloaded, 503)
            ->push($overloaded, 503)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'excerpt' => 'Tóm tắt sau đợt hai.',
                            'article_html' => $articleHtml,
                            'seo_title' => 'SEO title sau đợt hai',
                            'meta_description' => 'Meta description hợp lệ sau khi hệ thống nghỉ vài giây rồi thử lại toàn bộ danh sách model Gemini một lần nữa.',
                            'keywords' => ['xe lexus', 'lexus hà nội', 'kinh nghiệm mua xe'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ], 200);

        $result = app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);

        $this->assertSame('SEO title sau đợt hai', $result['seo_title']);
        Http::assertSentCount(3);
        Sleep::assertSequence([Sleep::for(2)->seconds()]);

        $urls = collect(Http::recorded())
            ->map(fn (array $pair): string => $pair[0]->url())
            ->all();

        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
        ], $urls);
    }

    public function test_qua_tai_lien_tuc_thi_dung_sau_ba_dot_va_bao_loi_de_hieu(): void
    {
        Sleep::fake();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'High demand']], 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 503');

        try {
            app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);
        } finally {
            Http::assertSentCount(6);
            Sleep::assertSequence([Sleep::for(2)->seconds(), Sleep::for(4)->seconds()]);
        }
    }

    public function test_bai_bi_cat_vi_het_tran_token_thi_bao_ro_thay_vi_loi_dinh_dang(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"excerpt": "Tóm tắt", "article_html": "<p>Bài viết đang dở']]],
                    'finishReason' => 'MAX_TOKENS',
                ]],
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_MAX_OUTPUT_TOKENS');

        app(GeminiArticleWriter::class)->generate('Một tiêu đề', $this->imagePath);
    }

    public function test_nut_gemini_luon_bam_duoc_va_bao_thieu_du_lieu(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'gemini-missing-data@test.local',
            'password' => 'x',
        ]));
        Http::fake();

        $action = TestAction::make('generateArticleWithGemini')
            ->schemaComponent('geminiArticleActions');

        Livewire::test(CreatePost::class)
            ->assertActionEnabled($action)
            ->callAction($action)
            ->assertNotified('Chưa đủ thông tin');

        Http::assertNothingSent();
    }

    public function test_nut_gemini_do_noi_dung_vao_form_nhung_chua_tu_dong_dang_bai(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'gemini-admin@test.local',
            'password' => 'x',
        ]));

        $articleHtml = '<p>'.str_repeat('Thông tin hữu ích cho người đang tìm hiểu xe Lexus. ', 20).'</p>';

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'excerpt' => 'Tóm tắt do Gemini tạo.',
                            'article_html' => $articleHtml,
                            'seo_title' => 'SEO title do Gemini tạo',
                            'meta_description' => 'Meta description do Gemini tạo cho bài viết xe Lexus tại Hà Nội, cung cấp thông tin hữu ích cho khách hàng đang chọn xe.',
                            'primary_keyword' => 'kinh nghiệm chọn xe lexus',
                            'slug' => 'kinh-nghiem-chon-xe-lexus-2026',
                            'faq' => [['question' => 'Nên chọn RX hay NX?', 'answer' => 'Tùy nhu cầu không gian và ngân sách.']],
                            'keywords' => ['xe lexus', 'lexus hà nội', 'giá xe lexus', 'mua xe lexus', 'kinh nghiệm chọn xe'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ]),
        ]);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Kinh nghiệm chọn xe Lexus',
                'slug' => 'kinh-nghiem-chon-xe-lexus',
                'cover' => [$this->imagePath],
                'status' => 'draft',
            ])
            ->callAction(
                TestAction::make('generateArticleWithGemini')
                    ->schemaComponent('geminiArticleActions'),
            )
            ->assertNotified('Gemini bắt đầu viết bài')
            // Queue trong test chạy đồng bộ → kết quả đã nằm trong cache; trang hỏi lại là điền.
            ->call('pollGeminiArticle')
            ->assertFormSet([
                'excerpt' => 'Tóm tắt do Gemini tạo.',
                'article_body' => str_replace(' </p>', '</p>', RichText::clean($articleHtml)),
                'seo.title' => 'SEO title do Gemini tạo',
                'seo.keywords' => 'kinh nghiệm chọn xe lexus, xe lexus, lexus hà nội, giá xe lexus, mua xe lexus, kinh nghiệm chọn xe',
                // slug đang là bản tự sinh từ tiêu đề → đổi sang slug chứa từ khoá chính.
                'slug' => 'kinh-nghiem-chon-xe-lexus-2026',
                'faq_text' => "Hỏi: Nên chọn RX hay NX?\nĐáp: Tùy nhu cầu không gian và ngân sách.",
                'ai_job' => null,
                'status' => 'draft',
            ]);
    }
}

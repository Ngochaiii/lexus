<?php

namespace Tests\Feature;

use App\Filament\Resources\ContentIdeas\Pages\ManageContentIdeas;
use App\Jobs\PlanContentWithGemini;
use App\Jobs\WriteDraftFromIdea;
use App\Media\MediaStore;
use App\Models\ContentIdea;
use App\Models\Product;
use App\Models\User;
use App\Services\GeminiContentPlanner;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Chuẩn GEO cho module tin tức: chủ đề gắn giai đoạn hành trình khách (tìm
 * hiểu / lựa chọn / quyết định) và câu hỏi khách hỏi ChatGPT, Gemini; bài
 * trả lời đúng các câu đó theo từng đoạn tự đứng được để AI trích dẫn.
 * Giai đoạn "lựa chọn" là chọn dòng xe/phiên bản/cách mua — không xếp hạng
 * đại lý, sale khác (lý do Google Ads tạm ngưng tài khoản).
 */
class GeoJourneyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.model' => 'gemini-3.8-flash',
            'services.gemini.fallback_models' => [],
            'services.gemini.retry_rounds' => 1,
        ]);

        $es = Product::create(['name' => 'Lexus ES', 'slug' => 'es', 'status' => 'published', 'published_at' => now(),
            'hero' => ['type' => 'image', 'src' => 'catalog/lexus/es/hero-test.jpg']]);
        $es->variants()->create(['name' => 'ES 350h Premium', 'slug' => 'es-350h-premium', 'price' => 2_360_000_000, 'sort' => 1]);
        app(MediaStore::class)->write('catalog/lexus/es/hero-test.jpg', UploadedFile::fake()->image('hero.jpg', 1200, 675)->getContent());
    }

    private static function gemini(array $json): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode($json, JSON_UNESCAPED_UNICODE)]]]]]];
    }

    public function test_de_xuat_chu_de_co_giai_doan_va_cau_hoi_hoi_ai(): void
    {
        $idea = fn (string $title, string $kw, string $stage, array $prompts) => ['title' => $title, 'primary_keyword' => $kw,
            'secondary_keywords' => [], 'search_intent' => 'x', 'angle' => 'y', 'target_url' => '/san-pham/es', 'cluster' => 'gia',
            'priority' => 1, 'stage' => $stage, 'ai_prompts' => $prompts];

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(self::gemini(['ideas' => [
            $idea('Xe hybrid Lexus có phải cắm sạc không', 'xe hybrid lexus có cần sạc', 'tim-hieu',
                ['Xe hybrid Lexus có phải cắm sạc không?', '  ', 'Hybrid Lexus đi phố tốn bao nhiêu xăng?']),
            $idea('Lexus ES 350h hay RX 350h cho gia đình', 'es 350h hay rx 350h', 'lua-chon', ['a', 'b', 'c', 'd', 'e', 'f', 'g']),
            $idea('Giá lăn bánh ES 350h Premium Hà Nội', 'lăn bánh es 350h premium', 'khong-co', []),
        ]]))]);

        PlanContentWithGemini::dispatchSync(3);

        $hybrid = ContentIdea::where('primary_keyword', 'xe hybrid lexus có cần sạc')->first();
        $this->assertSame('tim-hieu', $hybrid->stage);
        $this->assertSame(['Xe hybrid Lexus có phải cắm sạc không?', 'Hybrid Lexus đi phố tốn bao nhiêu xăng?'], $hybrid->ai_prompts);
        $this->assertCount(5, ContentIdea::where('stage', 'lua-chon')->first()->ai_prompts, 'tối đa 5 câu');
        $this->assertNull(ContentIdea::where('primary_keyword', 'lăn bánh es 350h premium')->value('stage'), 'giai đoạn lạ bỏ trống');

        Http::assertSent(function (Request $r) {
            $prompt = (string) data_get($r->data(), 'contents.0.parts.0.text');
            $item = data_get($r->data(), 'generationConfig.responseFormat.text.schema.properties.ideas.items');

            return str_contains($prompt, 'GIAI ĐOẠN HÀNH TRÌNH KHÁCH')
                && str_contains($prompt, 'không xếp hạng đại lý')
                && in_array('stage', $item['required'], true)
                && in_array('ai_prompts', $item['required'], true)
                && $item['properties']['stage']['enum'] === array_keys(ContentIdea::STAGES);
        });
    }

    public function test_chi_dan_viet_bai_theo_giai_doan_va_cau_hoi_ai(): void
    {
        $idea = new ContentIdea(['title' => 'ES hay RX', 'primary_keyword' => 'es 350h hay rx 350h', 'stage' => 'lua-chon',
            'ai_prompts' => ['Nên mua Lexus ES 350h hay RX 350h cho gia đình 4 người?', 'ES và RX khác nhau thế nào?']]);

        $text = app(GeminiContentPlanner::class)->instructionsFor($idea);

        $this->assertStringContainsString('Giai đoạn: Lựa chọn', $text);
        $this->assertStringContainsString('Nên mua Lexus ES 350h hay RX 350h cho gia đình 4 người?', $text);
        $this->assertStringContainsString('đề mục h2 dạng câu hỏi', $text);
    }

    public function test_cau_lenh_viet_bai_co_quy_tac_geo(): void
    {
        $idea = ContentIdea::create(['title' => 'Lexus ES 350h trả góp', 'primary_keyword' => 'lexus es 350h trả góp',
            'target_url' => '/san-pham/es', 'status' => 'writing']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        WriteDraftFromIdea::dispatchSync($idea->id);

        Http::assertSent(function (Request $r) {
            $p = (string) data_get($r->data(), 'contents.0.parts.1.text');

            return str_contains($p, 'TỰ ĐỨNG ĐƯỢC')
                && str_contains($p, 'nguồn của con số')
                && str_contains($p, 'không bịa trải nghiệm');
        });
    }

    public function test_admin_hien_giai_doan(): void
    {
        $this->actingAs(User::create(['name' => 'A', 'email' => 'geo@test.local', 'password' => 'x']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        ContentIdea::create(['title' => 'Hybrid có cần sạc', 'primary_keyword' => 'hybrid lexus sạc', 'stage' => 'tim-hieu']);

        Livewire::test(ManageContentIdeas::class)
            ->assertOk()
            ->assertTableColumnExists('stage')
            ->assertSee('Tìm hiểu');
    }
}

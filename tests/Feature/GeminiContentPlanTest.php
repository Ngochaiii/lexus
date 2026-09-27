<?php

namespace Tests\Feature;

use App\Filament\Resources\ContentIdeas\Pages\ManageContentIdeas;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Jobs\GenerateShareKit;
use App\Jobs\PlanContentWithGemini;
use App\Jobs\WriteDraftFromIdea;
use App\Media\MediaStore;
use App\Models\ContentIdea;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Gemini lên kế hoạch chủ đề, viết bài nháp theo chủ đề, soạn bài chia sẻ. */
class GeminiContentPlanTest extends TestCase
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

        PostCategory::create(['name' => 'Bảng giá & mua xe', 'slug' => 'bang-gia-mua-xe']);
    }

    private static function gemini(array $json): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode($json, JSON_UNESCAPED_UNICODE)]]]]]];
    }

    public function test_de_xuat_chu_de_bo_trung_va_sua_duong_dan_khong_co_that(): void
    {
        Post::create(['title' => 'Giá lăn bánh Lexus ES tháng 9', 'slug' => 'gia-lan-banh-es', 'status' => 'published',
            'seo' => ['keywords' => 'giá lăn bánh lexus es, lexus es 2026']]);

        $idea = fn (string $title, string $kw, string $url) => ['title' => $title, 'primary_keyword' => $kw, 'secondary_keywords' => ['lexus es hà nội'],
            'search_intent' => 'Tìm giá', 'angle' => 'Bảng giá từng bản', 'target_url' => $url, 'cluster' => 'gia', 'priority' => 1];

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(self::gemini(['ideas' => [
            $idea('ES 350h Premium trả góp 2026: trả trước bao nhiêu', 'ES 350h trả góp', '/san-pham/es/es-350h-premium'),
            $idea('Giá lăn bánh Lexus ES mới nhất', 'Giá Lăn Bánh Lexus ES', '/san-pham/es'),         // trùng bài đã có
            $idea('Lexus ES có nên mua bản Premium', 'có nên mua lexus es premium', '/duong-dan-bia'), // link bịa
        ]]))]);

        PlanContentWithGemini::dispatchSync(3);

        $this->assertSame(2, ContentIdea::count(), 'từ khoá trùng bài đã có bị bỏ');
        $this->assertSame('es 350h trả góp', ContentIdea::first()->primary_keyword);
        $this->assertSame('/san-pham/es', ContentIdea::where('primary_keyword', 'có nên mua lexus es premium')->value('target_url'), 'đường dẫn bịa → trang dòng xe');

        Http::assertSent(fn (Request $r) => str_contains($prompt = (string) data_get($r->data(), 'contents.0.parts.0.text'), 'Giá lăn bánh Lexus ES tháng 9')
            && str_contains($prompt, '/san-pham/es/es-350h-premium')
            && str_contains($prompt, '2.360.000.000 đ'));
    }

    public function test_viet_chu_de_thanh_bai_nhap_co_link_trang_can_day(): void
    {
        $idea = ContentIdea::create(['title' => 'Lexus ES 350h Premium trả góp 2026', 'primary_keyword' => 'lexus es 350h trả góp',
            'secondary_keywords' => ['trả trước lexus es'], 'target_url' => '/san-pham/es/es-350h-premium', 'cluster' => 'tra-gop', 'priority' => 1]);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(self::gemini([
            'primary_keyword' => 'lexus es 350h trả góp', 'search_intent' => 'Tính trả góp',
            'secondary_keywords' => ['trả trước lexus es', 'vay mua lexus'],
            'seo_title' => 'Lexus ES 350h trả góp 2026', 'meta_description' => str_repeat('Mô tả trả góp Lexus ES 350h. ', 5),
            'slug' => 'lexus-es-350h-tra-gop-2026', 'excerpt' => 'Tóm tắt.',
            'article_html' => '<p>'.str_repeat('Trả góp <a href="/san-pham/es/es-350h-premium">Lexus ES 350h Premium</a>. ', 20).'</p>',
            'faq' => [['question' => 'Trả trước bao nhiêu?', 'answer' => 'Khoảng 30% giá xe.']],
            'keywords' => ['lexus es 350h trả góp', 'trả trước lexus es', 'vay mua lexus'],
        ]))]);

        $idea->update(['status' => 'writing']);
        WriteDraftFromIdea::dispatchSync($idea->id);

        $idea->refresh();
        $post = $idea->post;
        $this->assertSame('drafted', $idea->status);
        $this->assertSame('draft', $post->status, 'không tự đăng — người đọc lại rồi mới đăng');
        $this->assertSame('lexus-es-350h-tra-gop-2026', $post->slug);
        $this->assertSame('catalog/lexus/es/hero-test.jpg', $post->cover, 'ảnh bìa lấy từ ảnh hero dòng xe');
        $this->assertSame('bang-gia-mua-xe', $post->category->slug);
        $this->assertSame('Lexus ES 350h trả góp 2026', $post->seo['title']);
        $this->assertNotNull(collect($post->sections)->firstWhere('type', 'faq'));

        Http::assertSent(fn (Request $r) => str_contains($p = (string) data_get($r->data(), 'contents.0.parts.1.text'), 'TRANG CẦN ĐẨY: /san-pham/es/es-350h-premium')
            && str_contains($p, '"lexus es 350h trả góp"'));
    }

    public function test_viet_bai_loi_thi_tra_chu_de_ve_cho_viet_kem_ly_do(): void
    {
        $idea = ContentIdea::create(['title' => 'Lexus ES giá lăn bánh', 'primary_keyword' => 'giá lăn bánh lexus es', 'target_url' => '/san-pham/es', 'status' => 'writing']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        WriteDraftFromIdea::dispatchSync($idea->id);

        $idea->refresh();
        $this->assertSame('idea', $idea->status);
        $this->assertStringContainsString('hết hạn mức', $idea->error);
        $this->assertSame(0, Post::count());
    }

    public function test_soan_bai_chia_se_gan_utm_theo_kenh_va_chi_cho_bai_da_dang(): void
    {
        $post = Post::create(['title' => 'Giá lăn bánh Lexus ES 350h', 'slug' => 'gia-lan-banh-es-350h', 'status' => 'published', 'published_at' => now()->subDay(),
            'excerpt' => 'Khoảng 2,66 tỷ.', 'sections' => [['type' => 'text', 'body' => '<p>Lăn bánh khoảng 2,66 tỷ.</p>']]]);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(self::gemini([
            'facebook' => 'ES 350h lăn bánh bao nhiêu? Khoảng 2,66 tỷ tại Hà Nội. Chi tiết từng khoản ở đây: {LINK} #LexusES350h',
            'zalo' => 'Chào anh/chị, em gửi bảng lăn bánh ES 350h ≈ 2,66 tỷ: {LINK}',
            'forum' => 'Bản Premium lăn bánh Hà Nội khoảng 2,66 tỷ gồm trước bạ 12% và biển 14 triệu. Mình là tư vấn Lexus.',
        ]))]);

        GenerateShareKit::dispatchSync($post->id);

        $kit = $post->fresh()->share_kit;
        $this->assertStringContainsString('/tin-tuc/gia-lan-banh-es-350h?utm_source=facebook&utm_medium=social&utm_campaign=gia-lan-banh-es-350h', $kit['facebook']);
        $this->assertStringContainsString('utm_source=zalo', $kit['zalo']);
        $this->assertStringContainsString('khoảng 2,66', $kit['zalo'], 'ký hiệu ≈ đổi thành chữ');
        $this->assertStringEndsWith('utm_source=forum&utm_medium=referral&utm_campaign=gia-lan-banh-es-350h', $kit['forum'], 'thiếu {LINK} thì gắn cuối');

        $draft = Post::create(['title' => 'Nháp', 'slug' => 'nhap', 'status' => 'draft']);
        GenerateShareKit::dispatchSync($draft->id);
        $this->assertNull($draft->fresh()->share_kit);
        $this->assertSame('error', cache(GenerateShareKit::cacheKey($draft->id))['status']);
    }

    public function test_admin_ke_hoach_bai_viet_va_nut_chia_se(): void
    {
        $this->actingAs(User::create(['name' => 'A', 'email' => 'plan@test.local', 'password' => 'x']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        $idea = ContentIdea::create(['title' => 'Lexus ES trả góp', 'primary_keyword' => 'lexus es trả góp', 'target_url' => '/san-pham/es']);

        Livewire::test(ManageContentIdeas::class)
            ->assertOk()
            ->assertSee('Lexus ES trả góp')
            ->assertActionVisible(TestAction::make('write')->table($idea))
            ->callAction(TestAction::make('dismiss')->table($idea));
        $this->assertSame('dismissed', $idea->fresh()->status);

        $draft = Post::create(['title' => 'Nháp', 'slug' => 'nhap', 'status' => 'draft']);
        Livewire::test(EditPost::class, ['record' => $draft->getRouteKey()])
            ->assertSee('Chia sẻ để kéo khách')
            ->assertActionDisabled(TestAction::make('generateShareKit')->schemaComponent('shareKitActions'));
    }
}

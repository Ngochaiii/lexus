<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\PostCategories\Pages\ManagePostCategories;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Form;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ContentAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'x']));
    }

    public function test_moi_man_hinh_admin_deu_render_duoc(): void
    {
        foreach ([ListPosts::class, ListPages::class, ListMenus::class,
            ManagePostCategories::class, ManageRedirects::class,
            // Màn hình Form đã gỡ khỏi admin (form cố định trong code); kiểm
            // màn hình Lead thay vào — đó là chỗ chủ website xem người gửi.
            ManageLeads::class, ManageSettings::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }

    public function test_tao_bai_viet_chi_can_dan_noi_dung_van_ban(): void
    {
        Livewire::test(CreatePost::class)
            ->assertSee('Nội dung bài viết')
            ->assertSee('Sinh bài viết bằng Gemini')
            ->assertSee('Yêu cầu thêm cho Gemini')
            ->assertSee('Từ khóa SEO Gemini đã dùng')
            ->assertDontSee('Tên mục')
            ->assertDontSee('Bố cục')
            ->assertDontSee('Theo mặc định của trang')
            ->fillForm([
                'title' => 'Lexus GX 550 về đại lý',
                'slug' => 'lexus-gx-550-ve-dai-ly',
                'status' => 'published',
                'excerpt' => 'Lô xe đầu tiên đã cập cảng.',
                'article_body' => 'Nội dung được dán trực tiếp vào trình soạn thảo.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::firstWhere('slug', 'lexus-gx-550-ve-dai-ly');

        $this->assertNotNull($post);
        $this->assertSame('text', $post->sections[0]['type']);
        $this->assertSame('<p>Nội dung được dán trực tiếp vào trình soạn thảo.</p>', $post->sections[0]['body']);
        $this->assertArrayNotHasKey('title', $post->sections[0]);
        $this->assertArrayNotHasKey('layout', $post->sections[0]);
        $this->assertArrayNotHasKey('width', $post->sections[0]);
    }

    public function test_tao_trang_tinh_kem_seo(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Giới thiệu',
                'slug' => 'gioi-thieu',
                'status' => 'published',
                'seo' => ['title' => 'Về chúng tôi', 'description' => 'Trang giới thiệu.'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::firstWhere('slug', 'gioi-thieu');

        $this->assertSame('Về chúng tôi', $page->seo['title']);
    }

    public function test_menu_luu_duoc_cay_hai_cap(): void
    {
        $product = Product::create(['name' => 'Lexus GX 550', 'status' => 'published']);
        $menu = Menu::create(['key' => 'header', 'name' => 'Menu chính']);

        Livewire::test(EditMenu::class, ['record' => $menu->getKey()])
            ->assertSuccessful()
            ->fillForm([
                'rootItems' => [[
                    'label' => 'Dòng xe',
                    'target_type' => 'url',
                    'url' => '/dong-xe',
                    'children' => [[
                        'label' => 'Lexus GX 550',
                        'target_type' => 'product',
                        'target_id' => $product->id,
                    ]],
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $root = $menu->refresh()->rootItems()->first();

        $this->assertSame('Dòng xe', $root->label);
        $this->assertSame('/dong-xe', $root->url);
        $this->assertSame('Lexus GX 550', $root->children->first()->label);
        $this->assertSame($product->id, (int) $root->children->first()->target_id);
    }

    public function test_menu_item_suy_ra_duong_dan_tu_ban_ghi_dich(): void
    {
        $product = Product::create(['name' => 'Lexus GX 550', 'slug' => 'gx-550', 'status' => 'published']);
        $menu = Menu::create(['key' => 'header', 'name' => 'Menu chính']);

        $item = $menu->items()->create([
            'label' => 'GX 550',
            'target_type' => 'product',
            'target_id' => $product->id,
        ]);

        $this->assertSame('/san-pham/gx-550', $item->resolvedUrl());
    }

    public function test_api_menu_tra_ve_cay_dung_thu_tu(): void
    {
        $menu = Menu::create(['key' => 'header', 'name' => 'Menu chính']);
        $b = $menu->items()->create(['label' => 'B', 'url' => '/b', 'sort' => 2]);
        $a = $menu->items()->create(['label' => 'A', 'url' => '/a', 'sort' => 1]);
        $menu->items()->create(['label' => 'A1', 'url' => '/a1', 'parent_id' => $a->id, 'sort' => 1]);

        $data = $this->getJson('/api/v1/menus/header')->assertOk()->json('data');

        $this->assertSame(['A', 'B'], array_column($data['items'], 'label'));
        $this->assertSame('A1', $data['items'][0]['children'][0]['label']);
        $this->assertSame($b->label, $data['items'][1]['label']);
    }

    public function test_cai_dat_luu_theo_khai_bao_trong_config(): void
    {
        Livewire::test(ManageSettings::class)
            ->fillForm(['site_name' => 'Lexus Việt Nam', 'hotline' => '1900 1234'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Lexus Việt Nam', Setting::get('site_name'));
        $this->assertSame('1900 1234', Setting::get('hotline'));
        $this->assertSame('general', Setting::find('site_name')->group);
    }

    public function test_cai_dat_lo_ra_qua_api(): void
    {
        Setting::put('hotline', '1900 1234');

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.hotline', '1900 1234');
    }

    public function test_them_muc_cai_dat_moi_chi_can_sua_config(): void
    {
        config(['catalog.settings.general.fields.tax_code' => ['label' => 'Mã số thuế', 'type' => 'text']]);

        Livewire::test(ManageSettings::class)
            ->fillForm(['tax_code' => '0101234567'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0101234567', Setting::get('tax_code'));
    }

    public function test_form_builder_luu_o_nhap_va_api_doi_dung_luat(): void
    {
        $form = Form::create(['key' => 'lien-he', 'name' => 'Liên hệ']);
        $form->fields()->create([
            'key' => 'ho_ten', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1,
        ]);

        $this->postJson('/api/v1/leads', ['form' => 'lien-he'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ho_ten');
    }

    public function test_tao_redirect(): void
    {
        Livewire::test(ManageRedirects::class)
            ->callAction('create', ['from_path' => '/cu', 'to_path' => '/moi', 'status_code' => 301])
            ->assertHasNoActionErrors();

        $this->assertSame('/moi', Redirect::firstWhere('from_path', '/cu')->to_path);
    }
}

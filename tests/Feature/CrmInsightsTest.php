<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use App\Models\SiteEvent;
use App\Models\User;
use App\Support\Attribution;
use App\Support\Insights;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** CRM, nguồn khách, bấm Gọi/Zalo, tốc độ thật, quét site, tình trạng xe. */
class CrmInsightsTest extends TestCase
{
    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://lexus.test']);

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now()]);
        $this->rx->variants()->create(['name' => 'RX 350h Premium', 'price' => 3_350_000_000, 'sort' => 1]);

        Form::create(['key' => 'nhan-bao-gia', 'name' => 'Nhận báo giá', 'success_message' => 'Đã nhận.'])
            ->fields()->createMany([
                ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
                ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
            ]);
    }

    public function test_phan_loai_nguon_khach(): void
    {
        $this->assertSame(['facebook', 'cpc', 'rx-thang10'], array_values(array_slice(Attribution::classify(['utm' => ['utm_source' => 'Facebook', 'utm_medium' => 'CPC', 'utm_campaign' => 'rx-thang10']]), 0, 3)));
        $this->assertSame(['google', 'cpc'], array_values(array_slice(Attribution::classify(['gclid' => 'Cj0KCQjw_abc-123XYZ']), 0, 2)));
        // insight.js cũ gửi cờ 0/1 thay cho mã — 0 từng bị filled() coi là có
        // gclid, làm MỌI khách thành "Google Ads". Chỉ mã thật mới tính.
        $this->assertSame('direct', Attribution::classify(['ref' => '', 'gclid' => 0, 'fbclid' => 0, 'direct' => true])['source']);
        $this->assertSame(['google', 'organic'], array_values(array_slice(Attribution::classify(['ref' => 'https://www.google.com/', 'gclid' => 0]), 0, 2)));
        $this->assertSame('direct', Attribution::classify(['gclid' => 1])['source'], 'cờ 1 kiểu cũ không phải mã click');
        $this->assertSame(['gclid' => null, 'gbraid' => 'wxyz0123456789ab', 'wbraid' => null],
            Attribution::clickIds(['gclid' => 0, 'gbraid' => 'wxyz0123456789ab', 'wbraid' => '<script>']));
        $this->assertSame(['google', 'organic'], array_values(array_slice(Attribution::classify(['ref' => 'https://www.google.com.vn/']), 0, 2)));
        $this->assertSame('coccoc', Attribution::classify(['ref' => 'https://coccoc.com/search?q=lexus'])['source']);
        $this->assertSame('zalo', Attribution::classify(['ref' => 'https://chat.zalo.me/'])['source']);
        $this->assertSame('ai', Attribution::classify(['ref' => 'https://chatgpt.com/'])['source']);
        $this->assertSame('direct', Attribution::classify(['ref' => 'https://lexus.test/bang-gia'])['source'], 'link nội bộ không phải nguồn');
        $this->assertSame(['referral', 'otofun.net'], array_values(array_slice(Attribution::classify(['ref' => 'https://www.otofun.net/threads/1']), 0, 2)));
        $this->assertSame('direct', Attribution::classify(null)['source']);
        $this->assertSame('mobile', Attribution::device('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148'));
        $this->assertTrue(Attribution::isBot('Mozilla/5.0 (compatible; Googlebot/2.1)'));
    }

    public function test_lead_ghi_nguon_khach_tu_trinh_duyet(): void
    {
        $this->post('/gui-form/nhan-bao-gia', [
            'name' => 'Khách Thử', 'phone' => '0912345678',
            'attribution' => json_encode(['ref' => 'https://www.google.com/', 'land' => '/san-pham/rx/rx-350h-premium', 'utm' => []]),
        ], ['User-Agent' => 'Mozilla/5.0 (iPhone) Mobile'])->assertRedirect();

        $lead = Lead::sole();
        $this->assertSame('google', $lead->source);
        $this->assertSame('organic', $lead->medium);
        $this->assertSame('/san-pham/rx/rx-350h-premium', $lead->landing_page);
        $this->assertSame('mobile', $lead->device);
        $this->assertNull($lead->gclid);
    }

    // gclid lưu nguyên mã để về sau báo Google lead nào thành khách thật
    // (nhập chuyển đổi ngoại tuyến) — mã chỉ lấy được lúc khách vào từ quảng cáo.
    public function test_lead_tu_quang_cao_luu_ma_click_google(): void
    {
        $this->post('/gui-form/nhan-bao-gia', [
            'name' => 'Khách Ads', 'phone' => '0912345679',
            'attribution' => json_encode(['ref' => 'https://www.google.com/', 'land' => '/san-pham/es', 'utm' => [], 'gclid' => 'Cj0KCQjw_abc-123XYZ']),
        ])->assertRedirect();

        $lead = Lead::sole();
        $this->assertSame(['google', 'cpc'], [$lead->source, $lead->medium]);
        $this->assertSame('Cj0KCQjw_abc-123XYZ', $lead->gclid);
    }

    public function test_ghi_lan_dau_lead_dat_hen_lai_thu_tro_len(): void
    {
        $lead = Lead::create(['form_id' => Form::first()->id, 'name' => 'A', 'phone' => '0912345670', 'status' => 'new']);
        $this->assertNull($lead->qualified_at);

        $lead->update(['status' => 'called']);
        $this->assertNull($lead->fresh()->qualified_at);

        $this->travelTo(now()->setDate(2026, 10, 2)->setTime(14, 30));
        $lead->update(['status' => 'appointment']);
        $first = $lead->fresh()->qualified_at;
        $this->assertSame('2026-10-02 14:30', $first->format('Y-m-d H:i'));

        $this->travel(3)->days();
        $lead->update(['status' => 'deposit']);
        $this->assertTrue($first->equalTo($lead->fresh()->qualified_at), 'giữ lần đầu, không ghi đè');
    }

    public function test_xuat_csv_nhap_chuyen_doi_ngoai_tuyen_cho_google_ads(): void
    {
        config(['catalog.leads.ads_conversion_name' => 'CRM Hen lai thu']);
        $form = Form::first()->id;
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(9, 0));

        Lead::create(['form_id' => $form, 'name' => 'Hẹn', 'phone' => '0912000001', 'gclid' => 'GCLID_OK_1234567', 'status' => 'new'])
            ->update(['status' => 'appointment']);
        Lead::create(['form_id' => $form, 'name' => 'Chưa hẹn', 'phone' => '0912000002', 'gclid' => 'GCLID_NEW_123456', 'status' => 'called']);
        Lead::create(['form_id' => $form, 'name' => 'Không từ Ads', 'phone' => '0912000003', 'status' => 'appointment']);

        $lines = explode("\n", trim(\App\Support\GoogleAdsOfflineExport::csv()));

        $this->assertSame('Parameters:TimeZone=Asia/Ho_Chi_Minh', $lines[0]);
        $this->assertSame('Google Click ID,Conversion Name,Conversion Time', $lines[1]);
        $this->assertSame('GCLID_OK_1234567,CRM Hen lai thu,2026-10-20 09:00:00', $lines[2]);
        $this->assertCount(3, $lines, 'chỉ lead có gclid VÀ đã hẹn lái thử trở lên');

        // Nút trong admin tải đúng file đó.
        $this->actingAs(User::create(['name' => 'A', 'email' => 'admin', 'password' => 'x']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(\App\Filament\Resources\Leads\Pages\ManageLeads::class)
            ->callAction('googleAdsExport')
            ->assertFileDownloaded('google-ads-chuyen-doi-2026-10-20.csv');
    }

    public function test_nhan_su_kien_goi_zalo_va_toc_do_bo_qua_bot_va_so_lieu_sai(): void
    {
        $events = ['events' => [
            ['type' => 'call', 'path' => '/san-pham/rx?x=1'],
            ['type' => 'zalo', 'path' => '/bang-gia'],
            ['type' => 'vital', 'metric' => 'LCP', 'value' => 1834.6, 'path' => '/'],
            ['type' => 'vital', 'metric' => 'CLS', 'value' => 0.04321, 'path' => '/'],
            ['type' => 'vital', 'metric' => 'LCP', 'value' => 999999, 'path' => '/'],   // vô lý → bỏ
            ['type' => 'hack', 'path' => '/'],                                         // loại lạ → bỏ
        ], 'attribution' => ['ref' => 'https://l.facebook.com/']];

        $this->postJson('/api/v1/events', $events, ['User-Agent' => 'Mozilla/5.0 (Macintosh) Chrome/140'])->assertNoContent();
        $this->postJson('/api/v1/events', $events, ['User-Agent' => 'Mozilla/5.0 Chrome-Lighthouse'])->assertNoContent();

        $this->assertSame(4, SiteEvent::count(), 'bot/Lighthouse và số liệu sai không được ghi');
        $this->assertSame('/san-pham/rx', SiteEvent::where('type', 'call')->value('path'), 'bỏ query string');
        $this->assertSame('facebook', SiteEvent::where('type', 'zalo')->value('source'));
        $this->assertEquals(0.0432, SiteEvent::where('metric', 'CLS')->value('value'));
    }

    public function test_bao_cao_ty_le_chot_hoa_hong_va_p75(): void
    {
        $form = Form::sole();
        foreach (['won', 'won', 'lost', 'called', 'spam'] as $status) {
            $form->leads()->create(['name' => 'K', 'phone' => '0900000000', 'status' => $status, 'source' => 'google',
                'commission' => $status === 'won' ? 50_000_000 : null]);
        }
        $this->assertNotNull(Lead::where('status', 'won')->first()->closed_at, 'giao xe thì ghi ngày chốt');

        $overview = (new Insights(30))->overview();
        $this->assertSame(4, $overview['leads'], 'không tính spam');
        $this->assertSame(50.0, $overview['rate']);
        $this->assertSame(100_000_000.0, $overview['commission']);
        $this->assertSame(['label' => 'Google', 'leads' => 4, 'won' => 2, 'rate' => 50.0], (new Insights(30))->bySource()[0]);

        $this->assertSame(30.0, Insights::p75(collect([10, 20, 30, 40])), 'nearest-rank: phần tử thứ ceil(0,75×4)=3');
        $this->assertSame('needs', Insights::rating('LCP', 3000));
    }

    public function test_trang_bao_cao_mo_duoc_trong_admin(): void
    {
        $this->actingAs(User::create(['name' => 'A', 'email' => 'admin', 'password' => 'x']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        SiteEvent::insert([['type' => 'call', 'path' => '/bang-gia', 'created_at' => now()]]);

        Livewire::test(Reports::class)->assertOk()->assertSee('Tỷ lệ chốt')->assertSee('/bang-gia')->call('setDays', 7)->assertSet('days', 7);
    }

    public function test_tinh_trang_xe_hien_nhan_va_bao_google(): void
    {
        $this->rx->variants()->first()->update(['availability' => 'pre_order', 'availability_note' => 'giao trong 2–4 tuần']);

        $html = $this->get('/san-pham/rx/rx-350h-premium')->assertOk()->getContent();
        $this->assertStringContainsString('Đặt trước · giao trong 2–4 tuần', $html);
        $this->assertStringContainsString('"availability":"https://schema.org/PreOrder"', $html);
    }

    public function test_site_audit_bao_loi_trang(): void
    {
        Http::fake([
            'https://audit.test/sitemap.xml' => Http::response('<urlset><url><loc>https://audit.test/</loc></url><url><loc>https://audit.test/hong</loc></url></urlset>'),
            'https://audit.test/' => Http::response('<html><head><title>Trang chủ Lexus Thăng Long ở Hà Nội</title><link rel="canonical" href="https://audit.test/"></head><body><img src="/a.webp"></body></html>'),
            'https://audit.test/hong' => Http::response('', 404),
            'https://audit.test/a.webp' => Http::response('', 200),
        ]);

        $this->artisan('site:audit', ['--url' => 'https://audit.test'])
            ->expectsOutputToContain('Trang trong sitemap trả HTTP 404')
            ->expectsOutputToContain('Có 0 thẻ H1')
            ->assertFailed();
    }
}

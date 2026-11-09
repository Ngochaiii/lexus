<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\Form;
use App\Models\Lead;
use App\Models\SiteEvent;
use App\Models\User;
use App\Support\Insights;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Khách đi tới đâu trên trang (09/11/2026). Đến 09/11 quảng cáo đã đưa gần
 * 2.800 lượt vào web mà mới ra 1 khách thật; web chỉ biết lúc khách bấm
 * Gọi/Zalo hoặc gửi form, không biết họ dừng ở bước nào trước đó. Mỗi lượt
 * xem trang ghi: xem trang, thấy bảng giá, thấy form, bắt đầu điền, gửi bị
 * lỗi, và lúc rời trang (ở bao lâu, kéo tới đâu) — tách khách quảng cáo
 * (google/cpc) khỏi khách khác để biết quảng cáo dẫn tới đâu.
 */
class VisitorJourneyTest extends TestCase
{
    private const AD = ['land' => '/san-pham/rx', 'gclid' => 'Cj0KCQiAtest_gclid-123'];

    private function sendEvents(array $events, ?array $attribution = null): void
    {
        $this->postJson('/api/v1/events', ['events' => $events, 'attribution' => $attribution],
            ['User-Agent' => 'Mozilla/5.0 (iPhone) Safari/605'])->assertNoContent();
    }

    public function test_ghi_cac_buoc_cua_khach_va_danh_dau_khach_quang_cao(): void
    {
        $this->sendEvents([
            ['type' => 'view', 'path' => '/san-pham/rx'],
            ['type' => 'see_price', 'path' => '/san-pham/rx', 'secs' => 4],
            ['type' => 'see_form', 'path' => '/san-pham/rx', 'place' => 'popup', 'secs' => 20],
            ['type' => 'form_start', 'path' => '/san-pham/rx', 'place' => 'content', 'secs' => 31],
            ['type' => 'form_error', 'path' => '/san-pham/rx', 'place' => 'content', 'secs' => 40],
            ['type' => 'leave', 'path' => '/san-pham/rx', 'secs' => 55, 'scroll' => 82],
        ], self::AD);

        $this->assertSame(['view', 'see_price', 'see_form', 'form_start', 'form_error', 'leave'],
            SiteEvent::orderBy('id')->pluck('type')->all());
        $this->assertSame(['cpc'], SiteEvent::distinct()->pluck('medium')->all());

        $leave = SiteEvent::where('type', 'leave')->sole();
        $this->assertSame(55, $leave->secs);
        $this->assertSame(82.0, $leave->value);   // kéo tới 82% trang
        $this->assertSame('popup', SiteEvent::where('type', 'see_form')->sole()->place);   // form trong khung bật lên
    }

    public function test_do_sau_keo_trang_vuot_khoang_thi_bo_so_do_khong_bo_su_kien(): void
    {
        $this->sendEvents([['type' => 'leave', 'path' => '/', 'secs' => 10, 'scroll' => 900]]);

        $leave = SiteEvent::sole();
        $this->assertSame(10, $leave->secs);
        $this->assertNull($leave->value);
    }

    public function test_bam_goi_zalo_cung_ghi_nguon_quang_cao(): void
    {
        $this->sendEvents([['type' => 'zalo', 'path' => '/san-pham/rx', 'place' => 'bar', 'secs' => 12]], self::AD);

        $this->assertSame('cpc', SiteEvent::sole()->medium);
    }

    public function test_bao_cao_hanh_trinh_tach_khach_quang_cao_va_khach_khac(): void
    {
        $ad = ['path' => '/san-pham/rx', 'source' => 'google', 'medium' => 'cpc', 'secs' => null, 'value' => null, 'created_at' => now()];
        $other = ['path' => '/', 'source' => 'google', 'medium' => 'organic', 'secs' => null, 'value' => null, 'created_at' => now()];
        SiteEvent::insert([
            ['type' => 'view'] + $ad, ['type' => 'view'] + $ad, ['type' => 'view'] + $ad, ['type' => 'view'] + $ad,
            ['type' => 'see_price'] + $ad, ['type' => 'see_price'] + $ad, ['type' => 'see_price'] + $ad,
            ['type' => 'see_form'] + $ad, ['type' => 'see_form'] + $ad,
            ['type' => 'form_start'] + $ad,
            ['type' => 'zalo'] + $ad,
            ['type' => 'leave', 'secs' => 10, 'value' => 30.0] + $ad,
            ['type' => 'leave', 'secs' => 20, 'value' => 50.0] + $ad,
            ['type' => 'leave', 'secs' => 90, 'value' => 90.0] + $ad,
            ['type' => 'view'] + $other,
            ['type' => 'leave', 'secs' => 300, 'value' => 100.0] + $other,
        ]);
        $form = Form::create(['key' => 'nhan-bao-gia', 'name' => 'Nhận báo giá']);
        $form->leads()->create(['phone' => '0912345678', 'source' => 'google', 'medium' => 'cpc', 'status' => 'new']);
        $form->leads()->create(['phone' => '0867701000', 'source' => 'google', 'medium' => 'cpc', 'status' => 'spam']);

        $journey = (new Insights)->journey();

        $this->assertSame(
            ['view' => 4, 'see_price' => 3, 'see_form' => 2, 'form_start' => 1, 'form_error' => 0, 'lead' => 1, 'call' => 0, 'zalo' => 1],
            $journey['ads']['steps'],
        );
        $this->assertSame(20, $journey['ads']['secs']);     // trung vị thời gian ở trang
        $this->assertSame(50, $journey['ads']['scroll']);   // trung vị độ sâu kéo trang (%)
        $this->assertSame(1, $journey['other']['steps']['view']);
        $this->assertSame(0, $journey['other']['steps']['lead']);

        $pages = (new Insights)->adLandingPages();
        $this->assertSame('/san-pham/rx', $pages[0]['path']);
        $this->assertSame(4, $pages[0]['view']);
        $this->assertSame(2, $pages[0]['see_form']);
        $this->assertSame(20, $pages[0]['secs']);
    }

    public function test_trang_bao_cao_co_muc_hanh_trinh(): void
    {
        $this->actingAs(User::create(['name' => 'A', 'email' => 'admin', 'password' => 'x']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        SiteEvent::insert([['type' => 'view', 'path' => '/san-pham/rx', 'medium' => 'cpc', 'source' => 'google', 'created_at' => now()]]);

        Livewire::test(Reports::class)->assertOk()
            ->assertSee('Khách quảng cáo đi tới đâu')
            ->assertSee('Thấy form');
    }

    public function test_insight_gui_cac_buoc_hanh_trinh(): void
    {
        $js = file_get_contents(public_path('assets/insight.js'));

        foreach (["'view'", "'see_price'", "'see_form'", "'form_start'", "'form_error'", "'leave'", 'IntersectionObserver', 'lead:invalid'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        // Phải nằm trước đoạn thoát sớm của đo tốc độ, máy cũ vẫn đo được.
        $this->assertLessThan(strpos($js, "if (!('PerformanceObserver' in window)) return;"), strpos($js, "'see_form'"));
        $this->assertStringContainsString("'lead:invalid'", file_get_contents(public_path('assets/lead.js')));
    }
}

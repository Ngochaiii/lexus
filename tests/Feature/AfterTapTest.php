<?php

namespace Tests\Feature;

use App\Models\SiteEvent;
use App\Support\Insights;
use Tests\TestCase;

/**
 * Sau khi bấm Gọi/Zalo khách có đi tiếp không (06/11/2026): đến 05/11 có 6 lượt
 * bấm từ quảng cáo mà chị Hà không nhận được khách nào. Web chỉ biết lúc chạm
 * nút, nên ghi thêm khách ở lại trang, rời trang, hay quay lại sau bao lâu để
 * biết họ dừng ở bước nào. Đây chỉ là dấu hiệu, không phải cuộc gọi đã nối máy.
 */
class AfterTapTest extends TestCase
{
    private function sendEvents(array $events): void
    {
        $this->postJson('/api/v1/events', ['events' => $events], ['User-Agent' => 'Mozilla/5.0 (iPhone) Safari/605'])
            ->assertNoContent();
    }

    public function test_ghi_ket_qua_sau_khi_bam_goi_va_zalo(): void
    {
        $this->sendEvents([
            ['type' => 'call_left', 'path' => '/san-pham/gx', 'place' => 'bar', 'secs' => 1],
            ['type' => 'call_back', 'path' => '/san-pham/gx', 'place' => 'bar', 'secs' => 3],
            ['type' => 'zalo_stay', 'path' => '/bang-gia', 'place' => 'strip'],
        ]);

        $this->assertSame(['call_left', 'call_back', 'zalo_stay'], SiteEvent::orderBy('id')->pluck('type')->all());
        $back = SiteEvent::where('type', 'call_back')->sole();
        $this->assertSame('bar', $back->place);
        $this->assertSame(3, $back->secs);
    }

    public function test_ket_qua_sau_khi_bam_khong_tinh_them_luot_bam(): void
    {
        $this->sendEvents([
            ['type' => 'call', 'path' => '/bang-gia', 'place' => 'bar', 'secs' => 20],
            ['type' => 'call_left', 'path' => '/bang-gia', 'place' => 'bar', 'secs' => 1],
            ['type' => 'call_back', 'path' => '/bang-gia', 'place' => 'bar', 'secs' => 4],
        ]);

        $clicks = (new Insights)->contactClicks();
        $this->assertSame(1, $clicks[0]['call']);
        $this->assertSame(1, $clicks[0]['total']);
    }

    public function test_loai_su_kien_la_bi_bo_qua(): void
    {
        $this->sendEvents([['type' => 'call_hack', 'path' => '/', 'secs' => 1]]);

        $this->assertSame(0, SiteEvent::count());
    }

    public function test_bao_cao_chia_khach_theo_buoc_dung(): void
    {
        $rows = [
            // Gọi: 1 ở lại, 1 quay lại ngay, 1 đi lâu, 1 không quay lại.
            ['type' => 'call_stay'],
            ['type' => 'call_left', 'secs' => 1], ['type' => 'call_back', 'secs' => 4],
            ['type' => 'call_left', 'secs' => 1], ['type' => 'call_back', 'secs' => 200],
            ['type' => 'call_left', 'secs' => 2],
            // Zalo: 1 quay lại ngay.
            ['type' => 'zalo_left', 'secs' => 1], ['type' => 'zalo_back', 'secs' => 10],
        ];
        foreach ($rows as $row) {
            SiteEvent::create($row + ['path' => '/', 'created_at' => now()]);
        }

        $report = (new Insights)->afterTap();

        $this->assertSame(['stay' => 1, 'quick' => 1, 'long' => 1, 'gone' => 1], $report['call']);
        $this->assertSame(['stay' => 0, 'quick' => 1, 'long' => 0, 'gone' => 0], $report['zalo']);
    }

    public function test_insight_theo_doi_sau_khi_bam_va_giu_duoc_qua_lan_tai_lai(): void
    {
        $js = file_get_contents(public_path('assets/insight.js'));

        $this->assertStringContainsString('_left', $js);
        $this->assertStringContainsString('_back', $js);
        $this->assertStringContainsString('_stay', $js);
        $this->assertStringContainsString('sessionStorage', $js);
        // Phải nằm trước đoạn thoát sớm của đo tốc độ, máy cũ vẫn đo được.
        $this->assertLessThan(strpos($js, "if (!('PerformanceObserver' in window)) return;"), strpos($js, 'sessionStorage'));
    }
}

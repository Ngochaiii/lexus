<?php

namespace Tests\Feature;

use App\Models\SiteEvent;
use Tests\TestCase;

/**
 * Lượt bấm Gọi/Zalo (26/10/2026): Ads báo 4 chuyển đổi trong tuần mà chị Hà
 * không nhận cuộc gọi nào, kể cả cuộc gọi nhỡ — khách chạm nút rồi không gọi.
 * Ghi thêm nút nằm ở đâu (thanh đáy, đầu trang, bảng giá…) và khách đã ở trang
 * bao nhiêu giây, để tách chạm nhầm (thanh đáy, 2 giây) khỏi khách thật.
 */
class ContactClickContextTest extends TestCase
{
    private function sendEvents(array $events): void
    {
        $this->postJson('/api/v1/events', ['events' => $events], ['User-Agent' => 'Mozilla/5.0 (iPhone) Safari/605'])
            ->assertNoContent();
    }

    public function test_ghi_vi_tri_nut_va_so_giay_tren_trang(): void
    {
        $this->sendEvents([['type' => 'call', 'path' => '/bang-gia', 'place' => 'table', 'secs' => 42]]);

        $event = SiteEvent::sole();
        $this->assertSame('table', $event->place);
        $this->assertSame(42, $event->secs);
    }

    public function test_vi_tri_la_hoac_so_giay_vo_ly_thi_bo_trong_nhung_van_ghi_luot_bam(): void
    {
        $this->sendEvents([
            ['type' => 'zalo', 'path' => '/', 'place' => '<script>', 'secs' => -5],
            ['type' => 'zalo', 'path' => '/', 'place' => 'bar', 'secs' => 999999],
            ['type' => 'call', 'path' => '/'],
        ]);

        $this->assertSame(3, SiteEvent::count());
        $this->assertSame(0, SiteEvent::whereNotNull('secs')->count());
        $this->assertSame(['bar'], SiteEvent::whereNotNull('place')->pluck('place')->all());
    }

    public function test_insight_gui_vi_tri_va_so_giay(): void
    {
        $js = file_get_contents(public_path('assets/insight.js'));

        $this->assertStringContainsString('place', $js);
        $this->assertStringContainsString('secs', $js);
        $this->assertStringContainsString('.sales-bar', $js);
    }
}

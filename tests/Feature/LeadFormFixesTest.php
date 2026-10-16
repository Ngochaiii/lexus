<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use Tests\TestCase;

/**
 * Test form 16/10/2026:
 *  1. Nút × của popup là một <form method="dialog"> → mỗi lần đóng popup GA4
 *     đếm thêm một form_submit (24 "gửi form" nhưng 4 lead thật).
 *  2. Mở popup bằng nút "Báo giá" ở thanh đáy trên trang xe thì ô dòng xe
 *     trống — khách phải chọn lại xe đang xem.
 *  3. pattern ô số điện thoại có ( ) - không thoát, Chrome biên dịch pattern
 *     với cờ v nên coi là sai cú pháp và bỏ qua — số "12345" lọt tới máy chủ.
 */
class LeadFormFixesTest extends TestCase
{
    private Product $rx;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rx = Product::create(['name' => 'Lexus RX', 'slug' => 'rx', 'status' => 'published', 'published_at' => now()]);
        Page::create(['slug' => 'lien-he', 'title' => 'Liên hệ', 'status' => 'published']);
    }

    public function test_nut_dong_popup_khong_phai_form(): void
    {
        $html = $this->get('/lien-he')->assertOk()->getContent();

        $this->assertStringNotContainsString('method="dialog"', $html);
        $this->assertMatchesRegularExpression('#<button type="button" class="quote-dialog__close" data-dialog-close#', $html);
    }

    public function test_trang_xe_gan_dong_xe_dang_xem_de_popup_chon_san(): void
    {
        $html = $this->get('/san-pham/rx')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<body[^>]*data-product="'.$this->rx->id.'"#', $html);
    }

    public function test_trang_khong_phai_xe_thi_khong_gan_dong_xe(): void
    {
        $html = $this->get('/lien-he')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('#<body[^>]*data-product=#', $html);
    }

    public function test_pattern_so_dien_thoai_hop_le_voi_co_v_cua_trinh_duyet(): void
    {
        $html = $this->get('/lien-he')->assertOk()->getContent();
        preg_match('#name="phone"[^>]*pattern="([^"]+)"#', $html, $m);
        $pattern = html_entity_decode($m[1] ?? '');

        // Cờ v: ( ) - trong lớp ký tự [...] phải được thoát bằng \.
        preg_match('/^\[(.*)\]/', $pattern, $class);
        $this->assertNotEmpty($class);
        $literals = preg_replace('/\w-\w/', '', $class[1]); // bỏ khoảng như 0-9
        $this->assertDoesNotMatchRegularExpression('/(?<!\\\\)[()\-]/', $literals);
        $this->assertSame(1, preg_match('/^'.$pattern.'$/u', '0912 345 678'));
        $this->assertSame(1, preg_match('/^'.$pattern.'$/u', '+84 (912) 345-678'));
        $this->assertSame(0, preg_match('/^'.$pattern.'$/u', '12345'));
    }
}

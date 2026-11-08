<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Product;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Khách để lại số thì báo ngay vào kênh Slack (Incoming Webhook) để chị Hà
 * gọi lại sớm — mail có thể nằm trong hộp thư không ai mở.
 */
class LeadSlackTest extends TestCase
{
    private const HOOK = 'https://hooks.slack.com/services/T000/B000/test';

    protected Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = Form::create([
            'key' => 'nhan-bao-gia',
            'name' => 'Nhận báo giá',
            'success_message' => 'Thu Hà sẽ gọi lại.',
        ]);
        $this->form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
            ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'text', 'rules' => [], 'sort' => 3],
            ['key' => 'product_id', 'label' => 'Dòng xe quan tâm', 'type' => 'product', 'rules' => ['nullable'], 'sort' => 4],
        ]);
    }

    private function send(array $extra = []): void
    {
        $this->post('/gui-form/nhan-bao-gia', array_merge([
            'name' => 'Anh <Nam> & co',
            'phone' => '0987 654 321',
            'note' => 'Hỏi màu trắng',
        ], $extra));
    }

    public function test_khach_de_lai_so_thi_bao_vao_slack_du_thong_tin_de_goi_lai(): void
    {
        config(['catalog.leads.slack_webhook' => self::HOOK]);
        Http::fake([self::HOOK => Http::response('ok')]);
        $product = Product::create(['name' => 'Lexus RX 350h', 'slug' => 'rx', 'status' => 'published']);

        $this->send([
            'product_id' => $product->id,
            'attribution' => json_encode(['land' => '/san-pham/rx', 'gclid' => 'Cj0KCQiAtest_gclid-123']),
        ]);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            $text = $request['text'];

            return $request->url() === self::HOOK
                && str_contains($text, 'Khách mới để lại số')
                && str_contains($text, '0987654321')
                && str_contains($text, 'Anh &lt;Nam&gt; &amp; co')   // Slack mrkdwn phải escape
                && str_contains($text, 'Lexus RX 350h')
                && str_contains($text, 'Hỏi màu trắng')
                && str_contains($text, 'Quảng cáo Google')
                && str_contains($text, '/san-pham/rx')
                && str_contains($text, '/admin/leads')
                && ! str_contains($text, 'Dòng xe quan tâm:');   // mã xe đã hiện thành tên ở "Xe quan tâm"
        });
    }

    public function test_khach_tu_tim_kiem_google_ghi_la_tim_kiem_tu_nhien(): void
    {
        config(['catalog.leads.slack_webhook' => self::HOOK]);
        Http::fake([self::HOOK => Http::response('ok')]);

        $this->send(['attribution' => json_encode(['ref' => 'https://www.google.com/', 'land' => '/san-pham/gx'])]);

        Http::assertSent(fn (Request $request) => str_contains($request['text'], 'Google · tìm kiếm tự nhiên'));
    }

    public function test_chua_cau_hinh_webhook_thi_khong_gui_gi(): void
    {
        config(['catalog.leads.slack_webhook' => null]);
        Http::fake();

        $this->send();

        Http::assertNothingSent();
    }

    public function test_gui_trung_so_trong_5_phut_chi_bao_mot_lan(): void
    {
        config(['catalog.leads.slack_webhook' => self::HOOK]);
        Http::fake([self::HOOK => Http::response('ok')]);

        $this->send();
        $this->send();

        Http::assertSentCount(1);
    }
}

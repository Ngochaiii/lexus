<?php

namespace Tests\Feature;

use App\Events\LeadReceived;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Form lead trên trang Blade hỗ trợ cả fetch JSON và POST thường dự phòng.
 *
 * Cùng action StoreLead với API nên honeypot, chống trùng, mail và webhook
 * phải giống hệt. Test ở đây canh cái "giống hệt" đó.
 */
class LeadFormTest extends TestCase
{
    protected Form $form;

    protected Form $deposit;

    protected function setUp(): void
    {
        parent::setUp();

        // Form nhúng giữa trang chi tiết (brand seeder gắn tự động cho mọi xe).
        $this->form = Form::create([
            'key' => 'dat-lich-lai-thu',
            'name' => 'Đặt lịch lái thử',
            'success_message' => 'Tư vấn viên sẽ gọi lại trong 15 phút.',
        ]);

        $this->form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
        ]);

        // Form nằm CUỐI trang chi tiết. Test tự khai khoá mình đang kiểm thay
        // vì dựa vào mặc định của config: mặc định đó là lựa chọn giao diện
        // của một hãng (bản thiết kế VinFast dùng 'dang-ky-tu-van'), đổi nó
        // không được làm đỏ test về cơ chế form.
        $this->deposit = Form::create([
            'key' => 'dat-coc',
            'name' => 'Đặt cọc',
            'success_message' => 'Tư vấn viên sẽ gọi lại trong 2 giờ làm việc.',
        ]);

        $this->deposit->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ và tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
        ]);

        config(['catalog.frontend.product_forms' => [$this->deposit->key]]);
    }

    public function test_gui_form_tao_lead_va_quay_ve_kem_cau_cam_on(): void
    {
        Event::fake([LeadReceived::class]);

        $this->from('/san-pham/lexus-gx-550')
            ->post('/gui-form/dat-lich-lai-thu', ['name' => 'Đạt', 'phone' => '0987654321'])
            ->assertRedirect('/san-pham/lexus-gx-550')
            ->assertSessionHas('lead_success', 'Tư vấn viên sẽ gọi lại trong 15 phút.')
            ->assertSessionHas('lead_form_key', 'dat-lich-lai-thu');

        $lead = Lead::sole();
        $this->assertSame('Đạt', $lead->name);
        $this->assertSame('0987654321', $lead->phone);
        $this->assertSame(['name' => 'Đạt', 'phone' => '0987654321'], $lead->data);

        Event::assertDispatched(LeadReceived::class);
    }

    public function test_fetch_form_luu_lead_va_tra_json_de_khong_tai_lai_trang(): void
    {
        Event::fake([LeadReceived::class]);

        $response = $this->postJson('/gui-form/dat-lich-lai-thu', [
            'name' => 'Khách kiểm thử',
            'phone' => '0900000099',
        ]);

        $lead = Lead::sole();

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Tư vấn viên sẽ gọi lại trong 15 phút.')
            ->assertJsonPath('data.id', $lead->id)
            ->assertHeader('content-type', 'application/json');

        $this->assertSame('Khách kiểm thử', $lead->name);
        $this->assertSame('0900000099', $lead->phone);
        Event::assertDispatched(LeadReceived::class);
    }

    public function test_fetch_form_tra_loi_json_va_khong_luu_khi_thieu_du_lieu(): void
    {
        $this->postJson('/gui-form/dat-lich-lai-thu', ['name' => 'Khách kiểm thử'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertSame(0, Lead::count());
    }

    /*
     * ĐÃ GỠ: test_form_render_du_thong_tin_de_javascript_gui_nen
     *
     * Test đó đòi `data-lead-form`, `data-lead-status` và lớp JavaScript ở
     * public/js/frontend.js của bản VinFast — gửi form bằng fetch để không
     * tải lại trang.
     *
     * Bản Lexus KHÔNG có JavaScript (xem template/DESIGN-NOTES.md). Form gửi bằng
     * POST thường rồi redirect kèm session('lead_success'); luồng này vẫn
     * chạy khi khách tắt JS, và đã có test khác phủ.
     *
     * Muốn gửi không tải lại trang thì thêm lại lớp JS đó cùng test này.
     */

    public function test_thieu_field_bat_buoc_thi_quay_lai_kem_loi(): void
    {
        // StoreLead dùng validateWithBag($form->key) để lỗi của form này không
        // tràn sang @error() của form khác trên cùng trang — nên lỗi nằm ở bag
        // 'dat-lich-lai-thu', không phải bag mặc định.
        $this->from('/')
            ->post('/gui-form/dat-lich-lai-thu', ['name' => 'Đạt'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('phone', null, 'dat-lich-lai-thu');

        $this->assertSame(0, Lead::count());
    }

    public function test_bot_dien_o_bay_thi_khong_tao_lead_nhung_van_bao_thanh_cong(): void
    {
        $this->post('/gui-form/dat-lich-lai-thu', [
            'name' => 'Bot', 'phone' => '0900000000', 'website' => 'http://spam.example',
        ])->assertSessionHas('lead_success');

        $this->assertSame(0, Lead::count());
    }

    public function test_gui_hai_lan_cung_so_trong_5_phut_chi_tao_mot_lead(): void
    {
        $payload = ['name' => 'Đạt', 'phone' => '0987654321'];

        $this->post('/gui-form/dat-lich-lai-thu', $payload);
        $this->post('/gui-form/dat-lich-lai-thu', $payload)->assertSessionHas('lead_success');

        $this->assertSame(1, Lead::count());
    }

    public function test_lead_gan_dung_mat_hang_khi_form_o_trang_chi_tiet(): void
    {
        $product = Product::create(['name' => 'Lexus GX 550', 'status' => 'published']);

        $this->post('/gui-form/dat-lich-lai-thu', [
            'name' => 'Đạt', 'phone' => '0987654321', 'product_id' => $product->id,
        ]);

        $this->assertSame($product->id, Lead::sole()->product_id);
    }

    public function test_form_da_tat_thi_404(): void
    {
        $this->form->update(['is_active' => false]);

        $this->post('/gui-form/dat-lich-lai-thu', ['name' => 'Đạt', 'phone' => '0987654321'])
            ->assertNotFound();
    }

    public function test_form_hien_o_cuoi_trang_chi_tiet_theo_config(): void
    {
        // Tự khai khoá form đang kiểm — không dựa vào mặc định config của hãng.
        config(['catalog.frontend.product_forms' => ['dat-coc']]);
        Product::create(['name' => 'Lexus GX 550', 'status' => 'published']);

        $html = $this->get('/san-pham/lexus-gx-550')->assertOk()->getContent();

        // Chỉ soi ĐÚNG form cuối trang: popup báo giá trong layout cũng là
        // một .lead-form có "Họ và tên", soi cả trang thì test xanh sai lý do.
        $form = $this->formPostingTo($html, 'dat-coc');
        $this->assertNotNull($form, 'form cuối trang phải có mặt');
        $this->assertStringContainsString('Đặt cọc', $html);
        $this->assertStringContainsString('name="_token"', $form);     // CSRF
        $this->assertStringContainsString('class="honeypot"', $form);  // ô bẫy bot

        // Bỏ hết khoá trong config → form cuối trang biến mất, trang vẫn chạy
        config(['catalog.frontend.product_forms' => []]);

        $html = $this->get('/san-pham/lexus-gx-550')->assertOk()->getContent();
        $this->assertNull($this->formPostingTo($html, 'dat-coc'));
    }

    public function test_form_da_nhung_giua_trang_thi_khong_dung_lai_o_cuoi(): void
    {
        // Config phải trỏ vào CHÍNH form được nhúng giữa trang, không thì test
        // xanh vì lý do sai: form cuối trang là cái khác nên có lặp cũng không lộ.
        config(['catalog.frontend.product_forms' => ['dat-lich-lai-thu']]);

        Product::create([
            'name' => 'Lexus GX 550',
            'status' => 'published',
            'sections' => [[
                'title' => 'Đăng ký lái thử', 'type' => 'form', 'form_key' => 'dat-lich-lai-thu',
            ]],
        ]);

        $html = $this->get('/san-pham/lexus-gx-550')->assertOk()->getContent();

        // Đúng MỘT form lái thử trên trang, không phải hai cái giống hệt nhau.
        // Đếm theo action chứ không đếm .lead-form: popup báo giá trong layout
        // là một form khác, có mặt ở mọi trang.
        $this->assertSame(1, substr_count($html, 'action="'.route('leads.store', 'dat-lich-lai-thu').'"'));
    }

    /** HTML của <form> gửi tới /gui-form/{key}, hoặc null nếu không có. */
    private function formPostingTo(string $html, string $key): ?string
    {
        $action = preg_quote('action="'.route('leads.store', $key).'"', '/');

        return preg_match('/<form[^>]*'.$action.'.*?<\/form>/s', $html, $m) ? $m[0] : null;
    }
}

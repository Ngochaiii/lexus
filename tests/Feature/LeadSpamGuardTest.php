<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Chặn số ảo và spam mà không làm khách thật khó gửi form.
 *
 * 07–08/11/2026: hai số để lại qua form, một số ảo, một người nói không hề
 * gửi. Nguyên tắc:
 *   - Chỉ TỪ CHỐI số chắc chắn không gọi được (đầu số không nhà mạng nào
 *     dùng), kèm lời nhắc dễ hiểu. Số đẹp (0989 999 999) là số thật — khách
 *     Lexus hay dùng — nên không chặn theo kiểu "nhiều số giống nhau".
 *   - Trường hợp chỉ ĐÁNG NGỜ (gửi ngay khi vừa mở trang, một máy gửi nhiều
 *     số khác nhau) thì vẫn nhận, khách vẫn thấy "Đã gửi thành công", nhưng
 *     lead vào mục Spam và không tính chuyển đổi Google Ads — chị Hà vẫn
 *     xem được, lỡ nhầm thì đổi trạng thái.
 */
class LeadSpamGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['catalog.leads.slack_webhook' => null]);

        $form = Form::create(['key' => 'nhan-bao-gia', 'name' => 'Nhận báo giá']);
        $form->fields()->createMany([
            ['key' => 'name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => ['required'], 'sort' => 1],
            ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'rules' => ['required'], 'sort' => 2],
        ]);
    }

    private function send(array $data, string $ip = '14.248.85.21')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/gui-form/nhan-bao-gia', array_merge(['name' => 'Lam'], $data));
    }

    #[DataProvider('dauSoKhongCo')]
    public function test_dau_so_khong_nha_mang_nao_dung_bi_tu_choi_voi_loi_de_hieu(string $phone): void
    {
        $this->send(['phone' => $phone])
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Số điện thoại chưa đúng. Bạn kiểm tra lại giúp: 10 số, ví dụ 0912 345 678.');

        $this->assertSame(0, Lead::count());
    }

    public static function dauSoKhongCo(): array
    {
        return [
            '01 (đầu số cũ đã bỏ)' => ['0123456789'],
            '04'                   => ['0412345678'],
            '06'                   => ['0612345678'],
            '030'                  => ['0301234567'],
            '000'                  => ['0000000000'],
        ];
    }

    #[DataProvider('soThat')]
    public function test_so_that_va_so_dep_van_nhan_binh_thuong(string $phone): void
    {
        $this->send(['phone' => $phone, '_rt' => time() - 30])
            ->assertStatus(201)
            ->assertJsonPath('data.new', true);

        $this->assertSame('new', Lead::sole()->status);
    }

    public static function soThat(): array
    {
        return [
            'Viettel 086'     => ['0867701000'],
            'Viettel 032'     => ['0321234567'],
            'Vinaphone 088'   => ['0889159579'],
            'Mobifone 070'    => ['0701234567'],
            'Vietnamobile 05' => ['0521234567'],
            'iTel 087'        => ['0871234567'],
            'số đẹp'          => ['0989999999'],
            'tứ quý'          => ['0911111111'],
        ];
    }

    public function test_gui_ngay_khi_vua_mo_trang_van_bao_thanh_cong_nhung_vao_spam(): void
    {
        $this->send(['phone' => '0912345678', '_rt' => time()])
            ->assertStatus(201)
            ->assertJsonPath('data.new', false);   // lead.js không bắn generate_lead

        $lead = Lead::sole();
        $this->assertSame('spam', $lead->status);
        $this->assertStringContainsString('gửi ngay sau khi mở trang', $lead->note);
    }

    public function test_khong_co_moc_mo_trang_api_hoac_tat_js_van_la_khach_binh_thuong(): void
    {
        $this->send(['phone' => '0912345678'])->assertJsonPath('data.new', true);

        $this->assertSame('new', Lead::sole()->status);
    }

    public function test_mot_may_gui_nhieu_so_khac_nhau_thi_tu_so_thu_ba_vao_spam(): void
    {
        $this->send(['phone' => '0912345601', '_rt' => time() - 60]);
        $this->send(['phone' => '0912345602', '_rt' => time() - 60]);
        $this->send(['phone' => '0912345603', '_rt' => time() - 60])->assertJsonPath('data.new', false);
        $this->send(['phone' => '0912345604', '_rt' => time() - 60], '113.160.1.1')->assertJsonPath('data.new', true);

        $this->assertSame(['new', 'new', 'spam', 'new'], Lead::orderBy('id')->pluck('status')->all());
        $this->assertStringContainsString('cùng một máy đã gửi 2 số khác', Lead::orderBy('id')->skip(2)->first()->note);
    }

    public function test_lead_nghi_spam_bao_slack_nhe_khong_nhac_ca_kenh(): void
    {
        $hook = 'https://hooks.slack.com/services/T000/B000/test';
        config(['catalog.leads.slack_webhook' => $hook]);
        Http::fake([$hook => Http::response('ok')]);

        $this->send(['phone' => '0912345678', '_rt' => time()]);

        Http::assertSent(fn ($request) => $request->url() === $hook
            && ! str_contains($request['text'], '<!channel>')
            && str_contains($request['text'], 'Nghi spam')
            && str_contains($request['text'], '0912345678'));
    }
}

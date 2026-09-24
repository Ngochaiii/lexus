<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

/** Đăng nhập admin bằng tên tài khoản ngắn (không phải email). */
class AdminLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_dang_nhap_bang_ten_tai_khoan_khong_phai_email(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin', 'password' => 'password']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_sai_mat_khau_thi_khong_vao_duoc(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin', 'password' => 'password']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin', 'password' => 'sai'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_trang_dang_nhap_hien_o_tai_khoan(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Tài khoản');
    }
}

<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

/**
 * Đăng nhập admin bằng "tài khoản" — chấp nhận cả tên ngắn (vd `admin`) lẫn
 * email. Vẫn so với cột `users.email`, chỉ bỏ ràng buộc đúng định dạng email
 * của form mặc định. Giới hạn số lần thử sai (rate limit) của Filament giữ nguyên.
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Tài khoản')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }
}

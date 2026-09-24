<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Brands\LexusSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Thứ tự có ý nghĩa:
     *   1. LexusSiteSeeder — khung site: cài đặt, form thu lead, trang tĩnh
     *   2. Brands\*Seeder   — xe của một hãng, gắn vào khung đó
     *
     * Một site chỉ bán một hãng nên bình thường chỉ gọi MỘT brand seeder.
     * Đổi hãng thì đổi đúng dòng cuối — xem database/seeders/Brands/README.md.
     */
    public function run(): void
    {
        $this->admin();

        $this->call(LexusSiteSeeder::class);
        $this->call(LexusSeeder::class);
    }

    /**
     * Tài khoản quản trị lấy từ .env (ADMIN_EMAIL, ADMIN_PASSWORD).
     *
     * Máy dev không khai thì dùng admin@lexusthanglong.test / password cho
     * tiện. Trên VPS (APP_ENV=production) KHÔNG bao giờ dùng mật khẩu mặc
     * định: thiếu ADMIN_PASSWORD thì sinh mật khẩu ngẫu nhiên và in ra một
     * lần duy nhất trên màn hình.
     *
     * firstOrCreate: chạy lại `db:seed` không đổi mật khẩu đã có.
     */
    private function admin(): void
    {
        $email = config('catalog.admin.email') ?: 'admin@lexusthanglong.test';
        $password = config('catalog.admin.password');

        if (blank($password)) {
            $password = app()->isProduction() ? Str::password(20) : 'password';
        }

        $user = User::firstOrCreate(['email' => $email], ['name' => 'Quản trị', 'password' => $password]);

        if ($user->wasRecentlyCreated && blank(config('catalog.admin.password')) && app()->isProduction()) {
            $this->command?->warn("Tài khoản admin: {$email} / {$password}  — LƯU LẠI NGAY, không hiện lại.");
        }
    }
}


<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Link Google Maps (22/10/2026): link cũ mở ra "Thăng Long Cars" (70 Trương
 * Công Giai) — doanh nghiệp khác. Đổi sang địa điểm Lexus Thăng Long ở ngã
 * tư Phạm Hùng. Chỉ thay khi còn đúng link cũ; link đã sửa trong admin thì giữ.
 */
return new class extends Migration
{
    private const OLD = 'https://maps.app.goo.gl/FLWLrDxDsoBDLfvx8';

    private const NEW = 'https://maps.app.goo.gl/TswfzGgp6sKzMGn47';

    public function up(): void
    {
        if (Setting::query()->where('key', 'map_url')->value('value') === self::OLD) {
            Setting::put('map_url', self::NEW);
        }
    }

    public function down(): void
    {
        // Không hoàn tác.
    }
};

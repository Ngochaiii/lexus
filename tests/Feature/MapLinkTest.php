<?php

namespace Tests\Feature;

use App\Models\Setting;
use Tests\TestCase;

/**
 * Link Google Maps (22/10/2026): link cũ trong admin mở ra "Thăng Long Cars"
 * (70 Trương Công Giai) — một doanh nghiệp khác. Đổi sang địa điểm Lexus
 * Thăng Long ở ngã tư Phạm Hùng; link đã sửa tay trong admin thì giữ.
 */
class MapLinkTest extends TestCase
{
    private const OLD = 'https://maps.app.goo.gl/FLWLrDxDsoBDLfvx8';

    private const NEW = 'https://maps.app.goo.gl/TswfzGgp6sKzMGn47';

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_22_100000_map_link_lexus_thang_long.php'))->up();
    }

    public function test_link_mo_ra_thang_long_cars_duoc_doi_sang_lexus_thang_long(): void
    {
        Setting::put('map_url', self::OLD);

        $this->migrate();
        $this->migrate();

        $this->assertSame(self::NEW, Setting::get('map_url'));
    }

    public function test_link_da_sua_tay_trong_admin_thi_giu(): void
    {
        Setting::put('map_url', 'https://maps.app.goo.gl/khac');

        $this->migrate();

        $this->assertSame('https://maps.app.goo.gl/khac', Setting::get('map_url'));
    }

    public function test_seeder_dung_link_lexus_thang_long(): void
    {
        $this->assertStringContainsString(
            "'map_url'          => '".self::NEW."'",
            file_get_contents(database_path('seeders/LexusSiteSeeder.php'))
        );
    }
}

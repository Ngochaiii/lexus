<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Chuẩn bị trang đích cho Google Ads:
 *
 *  - advisor_experience = "10 năm kinh nghiệm bán ô tô": quảng cáo dùng câu
 *    này, nên trang đích phải có (khối chuyên viên). Chỉ điền khi ô trống.
 *  - Chính sách quyền riêng tư: thêm mã nhấp quảng cáo (gclid), từ khoá quảng
 *    cáo, mục "7. Quảng cáo Google" (tiếp thị lại + cách từ chối) — bắt buộc
 *    theo chính sách quảng cáo cá nhân hoá của Google. Chỉ thay câu còn đúng bản
 *    seeder; mục 7 thêm nếu chưa có.
 */
return new class extends Migration
{
    private const EXPERIENCE = '10 năm kinh nghiệm bán ô tô';

    /** [câu cũ, câu mới] trong thân các mục của trang quyen-rieng-tu. */
    private const REPLACE = [
        ['<p>Cập nhật lần cuối: 24/09/2026.', '<p>Cập nhật lần cuối: 28/09/2026.'],
        ['(Google, Facebook, Zalo…) và nguồn chiến dịch quảng cáo (tham số UTM), nếu có; loại thiết bị',
            '(Google, Facebook, Zalo…), nguồn chiến dịch quảng cáo (tham số UTM, từ khoá quảng cáo) và mã nhấp quảng cáo Google (gclid), nếu có; loại thiết bị'],
        ['<li>Thống kê nguồn khách (khách đến từ trang nào, chiến dịch nào) để cải thiện website.</li></ul>',
            '<li>Thống kê nguồn khách (khách đến từ trang nào, chiến dịch nào) để cải thiện website.</li>'
            .'<li>Đo hiệu quả quảng cáo Google: báo cho Google biết lượt bấm quảng cáo nào dẫn tới lịch hẹn xem xe (xem mục 7).</li></ul>'],
    ];

    private const ADS_TITLE = '7. Quảng cáo Google';

    public function up(): void
    {
        // Chỉ website Lexus này (đã có cài đặt site_name) — DB mới/test thì bỏ qua.
        if (! DB::table('settings')->where('key', 'site_name')->exists()) {
            return;
        }

        $row = DB::table('settings')->where('key', 'advisor_experience')->first();
        if (! $row) {
            DB::table('settings')->insert(['key' => 'advisor_experience', 'value' => json_encode(self::EXPERIENCE),
                'group' => 'general', 'created_at' => now(), 'updated_at' => now()]);
        } elseif (blank(json_decode((string) $row->value, true))) {
            DB::table('settings')->where('key', 'advisor_experience')->update(['value' => json_encode(self::EXPERIENCE), 'updated_at' => now()]);
        }
        Cache::forget('catalog.settings');

        $page = DB::table('pages')->where('slug', 'quyen-rieng-tu')->first();
        $sections = $page ? json_decode((string) $page->sections, true) : null;
        if (! is_array($sections)) {
            return;
        }

        foreach ($sections as &$section) {
            if (is_string($section['body'] ?? null)) {
                foreach (self::REPLACE as [$old, $new]) {
                    $section['body'] = str_replace($old, $new, $section['body']);
                }
            }
        }
        unset($section);

        if (! collect($sections)->contains(fn ($s) => ($s['title'] ?? null) === self::ADS_TITLE)) {
            $sections[] = $this->adsSection();
        }

        DB::table('pages')->where('id', $page->id)->update(['sections' => json_encode($sections, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nội dung trang: giữ nguyên (không rút lại thông tin đã công bố).
        DB::table('settings')->where('key', 'advisor_experience')->where('value', json_encode(self::EXPERIENCE))->delete();
        Cache::forget('catalog.settings');
    }

    /** Giống hệt mục 7 trong LexusSiteSeeder. */
    private function adsSection(): array
    {
        return ['type' => 'text', 'title' => self::ADS_TITLE, 'intro' => 'Quảng cáo, tiếp thị lại và cách từ chối',
            'body' => '<p>Website quảng cáo trên Google (Google Ads) và dùng Google Analytics. Khi bạn đến từ một quảng cáo, '
                .'trình duyệt ghi nhớ <strong>mã nhấp quảng cáo</strong> (gclid) và gửi kèm nếu bạn gửi form. '
                .'Khi bạn đã hẹn xem xe, chúng tôi báo lại cho Google <strong>chỉ mã nhấp đó và thời điểm hẹn</strong> — '
                .'không gửi tên hay số điện thoại — để Google biết quảng cáo nào hữu ích.</p>'
                .'<p>Google và các bên thứ ba có thể dùng cookie để hiển thị quảng cáo của website này cho bạn trên các trang khác '
                .'dựa trên việc bạn đã xem website (tiếp thị lại). Bạn có thể tắt quảng cáo được cá nhân hoá tại '
                .'<a href="https://myadcenter.google.com/" rel="noopener" target="_blank">Trung tâm quảng cáo của tôi</a> của Google, '
                .'và chặn Google Analytics bằng '
                .'<a href="https://tools.google.com/dlpage/gaoptout" rel="noopener" target="_blank">tiện ích chọn không tham gia Google Analytics</a>.</p>'];
    }
};

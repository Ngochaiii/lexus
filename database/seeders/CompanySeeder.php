<?php

namespace Database\Seeders;

use App\Support\Catalog;
use Illuminate\Database\Seeder;

/**
 * Thông tin PHÁP NHÂN THẬT của đại lý đang vận hành site này.
 *
 * Tách khỏi CatalogDemoSeeder vì hai thứ khác hẳn nhau: seeder demo đặt chữ
 * mẫu ("Website ô tô demo") để soi thử admin, còn file này đặt dữ liệu thật.
 * Chạy demo seeder sau file này là ghi đè mất — chạy CompanySeeder sau cùng.
 *
 * Nguồn: hoá đơn GTGT do chi nhánh phát hành. CỐ Ý chỉ lấy phần bên BÁN.
 * Thông tin bên mua (họ tên, số CCCD, địa chỉ nhà, số khung/số máy xe) là dữ
 * liệu cá nhân của khách — không có chỗ nào trên website công khai được.
 *
 * Hotline và email do đại lý cung cấp trực tiếp, không lấy từ hoá đơn (hoá đơn
 * bỏ trống hai ô đó). Chưa có số liệu thật thì để trống chứ KHÔNG bịa: số
 * hotline sai còn tệ hơn không có, khách gọi vào số không tồn tại là mất khách.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $setting = Catalog::model('setting');

        // Tên thương mại hiển thị khắp site.
        $setting::put('site_name', 'VinFast Bắc Giang');
        $setting::put('brand_sub', 'Bắc Giang');

        // Tên pháp nhân + MST: chỉ dùng ở dòng bản quyền cuối trang.
        $setting::put('company_name', 'Công ty Cổ phần Tập đoàn Tương Lai Việt — Chi nhánh Bắc Giang');
        $setting::put('tax_code', '0111166593-007');

        $setting::put('address', 'Tổ dân phố Giáp Sau, Phường Bắc Giang, Tỉnh Bắc Ninh');
        $setting::put('hotline', '0889 159 579');
        $setting::put('email', 'ngochoa25.cv@gmail.com');

        // Popup hỏi tối thiểu họ tên, số điện thoại và mẫu xe quan tâm để giảm
        // ma sát nhưng sales vẫn biết cần tư vấn dòng xe nào.
        $setting::put('popup_form', 'dang-ky-tu-van');
        $setting::put('popup_title', 'Nhận báo giá & ưu đãi mới nhất');
        $setting::put('popup_text', 'Đăng ký ngay, tư vấn viên sẽ liên hệ trong thời gian sớm nhất.');
        $setting::put('popup_delay', '12');
        $setting::put('popup_days', '7');

        $this->privacyPage();

        $setting::put('site_description',
            'Đại lý ủy quyền VinFast tại Bắc Giang — báo giá, lái thử và đặt cọc các dòng xe điện VinFast.');
    }

    /**
     * Trang chính sách bảo vệ dữ liệu cá nhân.
     *
     * Ô đồng ý trong form tư vấn dẫn tới đây, nên trang này PHẢI tồn tại —
     * link gãy ở ngay câu xin phép xử lý dữ liệu thì vừa mất uy tín vừa không
     * chứng minh được là đã thông báo theo Nghị định 13/2023.
     *
     * Nội dung dưới đây là khung sườn, đại lý phải rà lại cho khớp thực tế
     * trước khi chạy thật.
     */
    protected function privacyPage(): void
    {
        Catalog::query('page')->updateOrCreate(
            ['slug' => 'chinh-sach-bao-mat'],
            [
                'title' => 'Chính sách bảo vệ dữ liệu cá nhân',
                'status' => 'published',
                'sections' => [
                    [
                        'title' => 'Dữ liệu chúng tôi thu thập',
                        'type' => 'text',
                        'body' => 'Khi bạn để lại thông tin qua các biểu mẫu trên website, chúng tôi thu thập: họ tên, '
                            .'số điện thoại, email, thời gian dự kiến mua xe, phương thức thanh toán dự kiến và nội dung '
                            ."bạn tự điền thêm.\n"
                            .'Hệ thống cũng ghi nhận địa chỉ IP, nguồn truy cập và trang bạn gửi biểu mẫu để chống spam.',
                    ],
                    [
                        'title' => 'Mục đích sử dụng',
                        'type' => 'text',
                        'body' => "Liên hệ tư vấn sản phẩm, báo giá, xếp lịch lái thử và xử lý yêu cầu đặt cọc.\n"
                            .'Chúng tôi không bán hoặc trao đổi dữ liệu của bạn cho bên thứ ba vì mục đích quảng cáo.',
                    ],
                    [
                        'title' => 'Thời gian lưu trữ',
                        'type' => 'text',
                        'body' => 'Dữ liệu được lưu trong thời gian cần thiết để phục vụ yêu cầu của bạn và theo quy '
                            .'định pháp luật hiện hành về lưu trữ chứng từ.',
                    ],
                    [
                        'title' => 'Quyền của bạn',
                        'type' => 'text',
                        'body' => 'Bạn có quyền yêu cầu xem, sửa, hoặc xoá dữ liệu cá nhân của mình, và rút lại sự '
                            ."đồng ý đã cấp.\n"
                            .'Liên hệ hotline hoặc email của đại lý ở chân trang để thực hiện các quyền này.',
                    ],
                ],
            ],
        );
    }
}

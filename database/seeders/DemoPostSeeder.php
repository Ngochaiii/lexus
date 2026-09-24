<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tin tức mẫu cho đại lý — ảnh lấy từ kho ảnh thật đã có trong
 * MEDIA_ROOT (mặc định storage/app/public), thông số xe lấy đúng theo `highlights` của bảng
 * products nên không lệch với trang chi tiết xe.
 *
 * Chạy lại được nhiều lần: khớp theo slug rồi cập nhật, không nhân bản.
 *
 *     php artisan db:seed --class=DemoPostSeeder
 *
 * Gỡ sạch:
 *
 *     php artisan tinker --execute="App\Models\Post::whereIn('slug', App\Models\Post::pluck('slug'))->..."
 *
 * (Danh sách slug nằm ở hằng SLUGS bên dưới.)
 */
class DemoPostSeeder extends Seeder
{
    /** Slug của toàn bộ bài do seeder này tạo — dùng để gỡ khi cần. */
    public const SLUGS = [
        'sac-xe-dien-tai-nha-chon-bo-sac-nao',
        'vinfast-vf-3-mini-suv-cho-pho-nho',
        'lai-thu-vf-9-bay-cho-duong-dai',
        'noi-that-vf-8-man-hinh-lon-khong-gian-rong',
        'vf-6-hay-vf-7-chon-xe-nao',
        'bao-duong-xe-dien-tai-dai-ly-bac-giang',
        'phu-kien-nen-sam-ngay-khi-nhan-xe',
        'vinfast-vf-5-plus-326-km-cho-gia-dinh-nho',
        'an-toan-tren-vf-8-asean-ncap-5-sao',
        'vinfast-vf-6-480-km-cho-duong-truong',
        'noi-that-vf-7-khoang-lai-sang-va-rong',
    ];

    public function run(): void
    {
        $categoryId = DB::table('post_categories')->orderBy('id')->value('id');

        foreach ($this->posts() as $i => $data) {
            Post::updateOrCreate(
                ['slug' => $data['slug']],
                $data + [
                    'post_category_id' => $categoryId,
                    'status'           => 'published',
                    // Giãn ngày đăng để thứ tự "mới nhất" trên trang chủ ổn định.
                    'published_at'     => Carbon::now()->subDays($i * 2),
                ],
            );
        }
    }

    private function posts(): array
    {
        return [
            [
                'slug'  => 'sac-xe-dien-tai-nha-chon-bo-sac-nao',
                'title' => 'Sạc xe điện tại nhà: chọn bộ sạc nào cho đúng',
                'cover' => 'catalog/settings/tram-sac.jpg',
                'excerpt' => 'Sạc treo tường 11 kW hay sạc di động 2,2 kW? Khác biệt nằm ở thời gian '
                    . 'chờ mỗi sáng và ở đường điện sẵn có trong nhà bạn.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Hai lựa chọn, hai kiểu dùng',
                        'body'  => "Gần như khách mua xe điện lần đầu đều hỏi cùng một câu: sạc ở đâu cho tiện. "
                            . "Câu trả lời ngắn là sạc tại nhà qua đêm, còn trạm sạc công cộng để dành cho đường dài.\n\n"
                            . "Đại lý đang có hai thiết bị. Bộ sạc treo tường AC 11 kW gắn cố định trong gara, "
                            . "cần đường điện ba pha hoặc một pha công suất đủ lớn, và cần thợ điện đi dây riêng. "
                            . "Sạc di động 2,2 kW cắm thẳng vào ổ điện dân dụng, bỏ được vào cốp, nhưng bù lại chậm hơn nhiều.\n\n"
                            . "Nếu mỗi ngày bạn chạy dưới 50 km trong nội thị, bản di động cắm qua đêm là đủ. "
                            . "Chạy nhiều hơn, hoặc nhà có hai xe điện, thì nên đầu tư bộ treo tường ngay từ đầu.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split',
                        'title'  => 'Khảo sát điện trước khi lắp',
                        'intro'  => 'Đại lý khảo sát đường điện tại nhà miễn phí công lắp đặt cho khách đặt cọc. '
                            . 'Kỹ thuật viên đo tải của tủ điện, chọn vị trí đặt bộ sạc sao cho dây sạc với tới '
                            . 'cổng nạp mà không phải kéo căng, rồi mới báo phương án đi dây.',
                        'items'  => [
                            ['image' => 'catalog/settings/tram-sac.jpg', 'label' => 'Bộ sạc treo tường tại showroom'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Vài thói quen nên giữ',
                        'body'  => "Sạc tới khoảng 80% cho việc đi lại hằng ngày, chỉ sạc đầy 100% trước những chuyến xa. "
                            . "Tránh để pin cạn kiệt rồi mới cắm.\n\n"
                            . "Trên đường dài, sạc nhanh DC đưa pin từ 10% lên 70% trong khoảng 30 phút — "
                            . "vừa đủ một lần nghỉ chân. Cần tư vấn cụ thể cho căn nhà của bạn, gọi 0889 159 579.",
                    ],
                ],
                'seo' => [
                    'title'       => 'Sạc xe điện tại nhà: chọn bộ sạc nào cho đúng',
                    'description' => 'So sánh bộ sạc treo tường AC 11 kW và sạc di động 2,2 kW cho xe điện VinFast, '
                        . 'kèm lưu ý khảo sát đường điện trước khi lắp đặt.',
                ],
            ],

            [
                'slug'  => 'vinfast-vf-3-mini-suv-cho-pho-nho',
                'title' => 'VinFast VF 3: mini SUV 215 km cho phố nhỏ',
                'cover' => 'catalog/vinfast/vinfast-vf-3/hero.jpg',
                'excerpt' => 'Bốn chỗ, quãng đường 215 km mỗi lần sạc và kích thước lọt được vào những '
                    . 'con ngõ mà xe cỡ C phải lùi ra.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Chiếc xe hợp với ngõ nhỏ',
                        'body'  => "VF 3 là mini SUV bốn chỗ, công suất tối đa 43 mã lực, quãng đường 215 km "
                            . "mỗi lần sạc theo chuẩn NEDC. Con số ấy nghe khiêm tốn nếu đem so với xe cỡ lớn, "
                            . "nhưng đặt cạnh nhu cầu thật thì lại vừa vặn.\n\n"
                            . "Một ngày đi làm trong thành phố hiếm khi vượt 40 km. Với VF 3, đó là ba đến bốn ngày "
                            . "mới phải cắm sạc một lần. Điều khách hay bất ngờ hơn cả lại là chỗ đỗ: xe lọt vào "
                            . "những khoảng trống mà xe cỡ C đành bỏ qua.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split-alt',
                        'title'  => 'Xem xe thật trước khi quyết',
                        'intro'  => 'Xe có sẵn tại showroom để ngồi thử. Ghế sau gập được để chở đồ cồng kềnh, '
                            . 'và bạn nên tự ngồi vào hàng ghế sau xem có vừa với người nhà mình không — '
                            . 'đây là điều thông số không nói hết.',
                        'items'  => [
                            ['image' => 'catalog/settings/cham-soc.jpg', 'label' => 'VF 3 tại showroom'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Thuê pin hay mua kèm pin',
                        'body'  => "VF 3 có hai cách mua: thuê pin, hoặc mua đứt kèm pin. Thuê pin hạ chi phí ban đầu "
                            . "và pin được bảo hành theo chính sách hãng; mua kèm pin thì trả nhiều hơn lúc đầu "
                            . "nhưng về sau không còn phí thuê hằng tháng.\n\n"
                            . "Cách chọn phụ thuộc vào việc bạn giữ xe bao lâu và chạy bao nhiêu km mỗi tháng. "
                            . "Mang số km thực tế của bạn tới đại lý, tư vấn viên tính giúp cho từng phương án.",
                    ],
                ],
                'seo' => [
                    'title'       => 'VinFast VF 3: mini SUV 215 km cho phố nhỏ',
                    'description' => 'VF 3 bốn chỗ, 43 mã lực, quãng đường 215 km mỗi lần sạc — '
                        . 'lựa chọn cho di chuyển nội thị và những con ngõ hẹp.',
                ],
            ],

            [
                'slug'  => 'lai-thu-vf-9-bay-cho-duong-dai',
                'title' => 'Lái thử VF 9 bảy chỗ trên cung đường dài',
                'cover' => 'catalog/sections/urR1hC7Gp0TXloSQMt0hXjqETIoUdyQ5aZr3yO7Z.jpg',
                'excerpt' => '402 mã lực, 438 km mỗi lần sạc và bảy chỗ ngồi. Bài viết ghi lại những gì '
                    . 'khách thường chú ý khi cầm lái VF 9 lần đầu.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Cảm giác lái đầu tiên',
                        'body'  => "VF 9 là SUV điện bảy chỗ, công suất tối đa 402 mã lực, quãng đường 438 km "
                            . "mỗi lần sạc theo NEDC.\n\n"
                            . "Điều khách nhận ra sớm nhất không phải sức mạnh mà là sự yên tĩnh. Không có tiếng "
                            . "động cơ, nên tiếng lốp và tiếng gió trở thành âm thanh chính trong khoang. "
                            . "Mô-men xoắn đến ngay từ vòng tua đầu, vì vậy chân ga cần nhẹ hơn thói quen cũ, "
                            . "nhất là lúc rời đèn đỏ.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'gallery',
                        'title'  => 'VF 9 ngoài đời thực',
                        'items'  => [
                            ['image' => 'catalog/sections/urR1hC7Gp0TXloSQMt0hXjqETIoUdyQ5aZr3yO7Z.jpg', 'label' => 'Góc ba phần tư'],
                            ['image' => 'catalog/sections/OQfftCOwKBa0cCgJFyaJ2AMcBUDpiS0gEQq6VQAK.jpg', 'label' => 'Đầu xe'],
                            ['image' => 'catalog/vinfast/vinfast-vf-9/hero.jpg', 'label' => 'VF 9'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Đăng ký lái thử',
                        'body'  => "Xe cỡ này cần tự cầm lái mới biết có hợp hay không, đặc biệt là tầm quan sát "
                            . "và cảm giác khi lùi vào chỗ đỗ hẹp.\n\n"
                            . "Đại lý xếp lịch lái thử tại showroom hoặc đưa xe tới tận nhà trong địa bàn tỉnh. "
                            . "Nếu nhà có trẻ nhỏ, hãy mang theo ghế trẻ em để thử lắp lên hàng ghế thứ hai. "
                            . "Đặt lịch qua hotline 0889 159 579.",
                    ],
                ],
                'seo' => [
                    'title'       => 'Lái thử VinFast VF 9 bảy chỗ trên cung đường dài',
                    'description' => 'Ghi chép lái thử VF 9: 402 mã lực, 438 km mỗi lần sạc, bảy chỗ ngồi. '
                        . 'Đăng ký lái thử tại đại lý VinFast Bắc Giang.',
                ],
            ],

            [
                'slug'  => 'noi-that-vf-8-man-hinh-lon-khong-gian-rong',
                'title' => 'Nội thất VF 8: màn hình lớn, ít nút bấm',
                'cover' => 'catalog/vinfast/vinfast-vf-8/noi-that-2.jpg',
                'excerpt' => 'Gần như mọi thao tác dồn vào một màn hình cảm ứng. Tiện hay bất tiện còn '
                    . 'tuỳ thói quen của người lái.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Bảng táp-lô gọn tới mức tối giản',
                        'body'  => "VF 8 dùng pin 82 kWh, quãng đường 447 km mỗi lần sạc theo NEDC, "
                            . "bản Plus đạt 402 mã lực.\n\n"
                            . "Bước vào khoang lái, thứ đập vào mắt là màn hình trung tâm cỡ lớn và một bảng "
                            . "táp-lô gần như trống. Điều hoà, âm thanh, cài đặt xe đều nằm trong màn hình. "
                            . "Người quen nút vật lý sẽ cần vài ngày để nhớ vị trí, nhưng bù lại tầm nhìn "
                            . "thoáng và khoang xe trông rộng hơn hẳn.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split',
                        'title'  => 'Ngồi thử trước khi chốt',
                        'intro'  => 'Lời khuyên thật lòng: hãy ngồi vào ghế lái ít nhất mười phút, thử chỉnh '
                            . 'điều hoà và chuyển bài hát qua màn hình. Nếu thấy thao tác tự nhiên thì hợp; '
                            . 'còn thấy vướng thì nên cân nhắc mẫu khác trong dải sản phẩm.',
                        'items'  => [
                            ['image' => 'catalog/vinfast/vinfast-vf-8/noi-that-2.jpg', 'label' => 'Khoang lái VF 8'],
                        ],
                    ],
                ],
                'seo' => [
                    'title'       => 'Nội thất VinFast VF 8: màn hình lớn, ít nút bấm',
                    'description' => 'Khoang lái VF 8 dồn thao tác vào màn hình trung tâm. Pin 82 kWh, '
                        . '447 km mỗi lần sạc, bản Plus 402 mã lực.',
                ],
            ],

            [
                'slug'  => 'vf-6-hay-vf-7-chon-xe-nao',
                'title' => 'VF 6 hay VF 7: chọn xe nào cho gia đình',
                'cover' => 'catalog/vinfast/vinfast-vf-8/hero.jpg',
                'excerpt' => 'Hai mẫu nằm sát nhau trong dải sản phẩm. Khác biệt rơi vào quãng đường, '
                    . 'sức kéo và cỡ xe.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Đặt hai bộ thông số cạnh nhau',
                        'body'  => "VF 6 có pin 59,6 kWh, quãng đường 480 km mỗi lần sạc theo NEDC, "
                            . "bản Plus đạt 201 mã lực.\n\n"
                            . "VF 7 dùng pin khả dụng 75,3 kWh, quãng đường 496 km, công suất tối đa 260 kW "
                            . "và mô-men xoắn cực đại 500 Nm.\n\n"
                            . "Quãng đường hai xe chênh nhau không nhiều. Khác biệt thật nằm ở sức kéo và cỡ xe: "
                            . "VF 7 khoẻ hơn đáng kể và rộng hơn, VF 6 gọn hơn nên xoay trở trong phố dễ hơn.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split-alt',
                        'title'  => 'Cách chọn cho gọn',
                        'intro'  => 'Nhà bốn người, chủ yếu đi trong tỉnh, thỉnh thoảng về quê cuối tuần: VF 6 đủ dùng. '
                            . 'Thường chở đủ năm người kèm hành lý, hay chạy đường trường đều đặn: VF 7 xứng đáng '
                            . 'với phần chi thêm.',
                        'items'  => [
                            ['image' => 'catalog/settings/tram-sac.jpg', 'label' => 'VF 7 tại showroom'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Thử cả hai trong một buổi',
                        'body'  => "Đọc thông số mãi cũng không thay được mười lăm phút ngồi sau vô-lăng. "
                            . "Đại lý xếp được lịch lái thử cả hai xe trong cùng một buổi để bạn so ngay lúc "
                            . "cảm giác còn mới. Gọi 0889 159 579 để giữ chỗ.",
                    ],
                ],
                'seo' => [
                    'title'       => 'VinFast VF 6 hay VF 7: chọn xe nào cho gia đình',
                    'description' => 'So sánh VF 6 (59,6 kWh, 480 km, 201 mã lực) và VF 7 (75,3 kWh, 496 km, '
                        . '260 kW, 500 Nm) để chọn xe phù hợp.',
                ],
            ],

            [
                'slug'  => 'bao-duong-xe-dien-tai-dai-ly-bac-giang',
                'title' => 'Bảo dưỡng xe điện: ít việc hơn bạn nghĩ',
                'cover' => 'catalog/settings/cham-soc.jpg',
                'excerpt' => 'Không thay dầu máy, không lọc gió động cơ, không bugi. Nhưng vẫn có những '
                    . 'hạng mục không được bỏ qua.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Những việc biến mất',
                        'body'  => "Xe điện bỏ hẳn thay dầu động cơ, lọc dầu, lọc gió động cơ và bugi. "
                            . "Phanh cũng mòn chậm hơn, vì phanh tái sinh gánh phần lớn việc giảm tốc — "
                            . "má phanh trên xe điện thường đi được quãng đường dài hơn xe xăng.\n\n"
                            . "Kết quả là mỗi lần vào xưởng nhanh hơn và ít khoản chi hơn.",
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Những việc vẫn phải làm',
                        'body'  => "Lốp vẫn mòn, và mòn nhanh hơn do xe điện nặng hơn — cần đảo lốp đúng hạn "
                            . "và kiểm tra áp suất đều đặn.\n\n"
                            . "Dầu phanh, nước làm mát pin, lọc gió điều hoà và cần gạt mưa vẫn theo lịch bình thường. "
                            . "Phần mềm xe cũng cần cập nhật, việc này xưởng làm trong lúc bạn chờ.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split',
                        'title'  => 'Đặt lịch tại đại lý',
                        'intro'  => 'Xưởng dịch vụ nhận xe từ 8:00 đến 19:00 hằng ngày tại Tổ dân phố Giáp Sau, '
                            . 'Phường Bắc Giang. Gọi trước 0889 159 579 để khỏi phải chờ.',
                        'items'  => [
                            ['image' => 'catalog/settings/cham-soc.jpg', 'label' => 'Khu vực dịch vụ'],
                        ],
                    ],
                ],
                'seo' => [
                    'title'       => 'Bảo dưỡng xe điện VinFast: ít việc hơn bạn nghĩ',
                    'description' => 'Xe điện bỏ thay dầu và bugi, nhưng lốp, dầu phanh và lọc gió điều hoà '
                        . 'vẫn theo lịch. Đặt lịch tại đại lý VinFast Bắc Giang.',
                ],
            ],

            [
                'slug'  => 'phu-kien-nen-sam-ngay-khi-nhan-xe',
                'title' => 'Phụ kiện nên sắm ngay khi nhận xe',
                'cover' => 'catalog/vinfast/vinfast-vf-8/noi-that-1.jpg',
                'excerpt' => 'Bảy món đại lý đang có sẵn. Vài món nên lắp trước khi xe lăn bánh, '
                    . 'số còn lại mua dần cũng kịp.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Ba món nên có từ ngày đầu',
                        'body'  => "Ngày nhận xe thường vội, và phần lớn khách chỉ nhớ ra thiếu gì sau vài tuần chạy. "
                            . "Ba món dưới đây nên chuẩn bị trước.\n\n"
                            . "Thứ nhất là thiết bị sạc. Bộ sạc treo tường AC 11 kW cần thợ đi dây nên phải đặt sớm, "
                            . "còn sạc di động 2,2 kW cắm ổ điện dân dụng là dùng được ngay — ít nhất hãy có một trong hai.\n\n"
                            . "Thứ hai là thảm sàn 3D theo xe. Thảm ôm đúng khuôn sàn nên nước và đất cát bị giữ lại "
                            . "trong lòng thảm thay vì ngấm xuống nỉ, và tháo ra rửa được.\n\n"
                            . "Thứ ba là camera hành trình trước/sau. Món này chỉ có giá trị khi đã lắp sẵn "
                            . "trước lúc cần đến nó.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split',
                        'title'  => 'Lắp tại đại lý khi nhận xe',
                        'intro'  => 'Kỹ thuật viên lắp thảm sàn và đi dây camera hành trình ngay trong lúc bàn giao, '
                            . 'nên bạn không phải quay lại lần hai. Dây camera được giấu theo nẹp trần và trụ A '
                            . 'thay vì để lòng thòng trước mặt người lái.',
                        'items'  => [
                            ['image' => 'catalog/vinfast/vinfast-vf-8/noi-that-1.jpg', 'label' => 'Khoang sau xe mới bàn giao'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Bốn món mua dần cũng được',
                        'body'  => "Áo phủ xe chống nắng dành cho ai phải đỗ ngoài trời cả ngày — mùa hè, "
                            . "tấm phủ giữ cho vô-lăng và ghế không nóng tới mức không chạm được.\n\n"
                            . "Bơm lốp điện mini nhỏ gọn, bỏ cốp, dùng để bù áp suất giữa hai lần bảo dưỡng. "
                            . "Xe điện nặng hơn xe xăng cùng cỡ nên áp suất lốp đáng để kiểm tra thường xuyên.\n\n"
                            . "Ô dù gấp 2 tầng và mô hình xe tỉ lệ 1:24 thì thuần về sở thích. "
                            . "Xem đủ bảy món tại mục Phụ kiện, hoặc hỏi tư vấn viên khi tới showroom.",
                    ],
                ],
                'seo' => [
                    'title'       => 'Phụ kiện nên sắm ngay khi nhận xe VinFast',
                    'description' => 'Thiết bị sạc, thảm sàn 3D và camera hành trình nên có từ ngày đầu; '
                        . 'áo phủ xe, bơm lốp mini và các món còn lại mua dần cũng kịp.',
                ],
            ],

            [
                'slug'  => 'vinfast-vf-5-plus-326-km-cho-gia-dinh-nho',
                'title' => 'VinFast VF 5 Plus: 326 km cho gia đình nhỏ',
                'cover' => 'catalog/vinfast/vinfast-vf-5-plus/noi-that-1.jpg',
                'excerpt' => 'Năm chỗ, 134 mã lực, 326 km mỗi lần sạc. Mẫu xe nằm giữa VF 3 và VF 6 '
                    . 'trong dải sản phẩm.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Bước lên từ VF 3',
                        'body'  => "VF 5 Plus là SUV điện cỡ A+ năm chỗ, công suất tối đa 134 mã lực, "
                            . "quãng đường 326 km mỗi lần sạc theo NEDC.\n\n"
                            . "Đặt cạnh VF 3, khác biệt lớn nhất không nằm ở quãng đường mà ở chỗ ngồi: "
                            . "năm chỗ thay vì bốn, và hàng ghế sau rộng hơn hẳn. Nhà có ông bà đi cùng, "
                            . "hoặc thường chở thêm người, thì đây là bước lên đáng cân nhắc.\n\n"
                            . "So với VF 6, VF 5 Plus gọn hơn và nhẹ nhàng hơn trong phố, đổi lại quãng đường "
                            . "ngắn hơn — 326 km so với 480 km.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split-alt',
                        'title'  => 'Xe có sẵn tại showroom',
                        'intro'  => 'Ghé đại lý ngồi thử cả VF 3, VF 5 Plus và VF 6 trong cùng một buổi — '
                            . 'ba xe đứng cạnh nhau thì khác biệt về không gian hiện ra rõ hơn nhiều '
                            . 'so với đọc bảng thông số.',
                        'items'  => [
                            ['image' => 'catalog/vinfast/vinfast-vf-5-plus/hero.jpg', 'label' => 'VinFast VF 5 Plus'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Hợp với ai',
                        'body'  => "Gia đình bốn đến năm người, đi làm và đưa đón con trong tỉnh, cuối tuần "
                            . "chạy loanh quanh trong bán kính trên dưới trăm cây số: VF 5 Plus vừa vặn.\n\n"
                            . "Nếu tháng nào cũng có vài chuyến đường trường vài trăm km thì nên nhìn sang "
                            . "VF 6 hoặc VF 7. Mang lịch trình thật của bạn tới đại lý hoặc gọi 0889 159 579, "
                            . "tư vấn viên đối chiếu giúp.",
                    ],
                ],
                'seo' => [
                    'title'       => 'VinFast VF 5 Plus: 326 km cho gia đình nhỏ',
                    'description' => 'VF 5 Plus năm chỗ, 134 mã lực, 326 km mỗi lần sạc — mẫu xe nằm giữa '
                        . 'VF 3 và VF 6 trong dải sản phẩm VinFast.',
                ],
            ],

            [
                'slug'  => 'an-toan-tren-vf-8-asean-ncap-5-sao',
                'title' => 'An toàn trên VF 8: ASEAN NCAP 5 sao',
                'cover' => 'catalog/vinfast/vinfast-vf-8/ngoai-that-2.jpg',
                'excerpt' => 'Năm sao ASEAN NCAP, 11 túi khí trên bản Plus và nhóm cảnh báo dành riêng '
                    . 'cho người lái đường dài.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Điểm an toàn và ý nghĩa của nó',
                        'body'  => "VF 8 đạt xếp hạng 5 sao ASEAN NCAP và được vinh danh tại ASEAN NCAP "
                            . "Grand Prix Awards 2024.\n\n"
                            . "Xếp hạng này đo khả năng bảo vệ người lớn, bảo vệ trẻ em và mức trang bị "
                            . "hỗ trợ an toàn. Với người mua xe gia đình, đây là một trong số ít con số "
                            . "do bên thứ ba độc lập chấm, nên đáng tin hơn thông số do hãng tự công bố.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split',
                        'title'  => 'Sáu trang bị đáng chú ý',
                        'intro'  => 'Bản VF 8 Plus có 11 túi khí. Ngoài ra xe còn camera sau, giám sát xung quanh, '
                            . 'đèn pha tự động, cảnh báo người lái buồn ngủ và cảnh báo mất tập trung — '
                            . 'hai cảnh báo cuối là thứ phát huy tác dụng rõ nhất trên đường trường ban đêm.',
                        'items'  => [
                            ['image' => 'catalog/vinfast/vinfast-vf-8/thu-vien-4.jpg', 'label' => 'Trang bị an toàn trên VF 8'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Nên tự kiểm chứng khi lái thử',
                        'body'  => "Trang bị an toàn chỉ có ích khi bạn biết nó nằm ở đâu và kêu như thế nào. "
                            . "Lúc lái thử, hãy nhờ tư vấn viên bật từng cảnh báo lên cho nghe thử, "
                            . "và xem màn hình hiển thị camera 360 lúc lùi vào chỗ hẹp.\n\n"
                            . "Nhà có trẻ nhỏ thì mang theo ghế trẻ em để lắp thử chốt ISOFIX. "
                            . "Đặt lịch qua 0889 159 579.",
                    ],
                ],
                'seo' => [
                    'title'       => 'An toàn trên VinFast VF 8: ASEAN NCAP 5 sao',
                    'description' => 'VF 8 đạt 5 sao ASEAN NCAP, bản Plus có 11 túi khí, camera sau, giám sát '
                        . 'xung quanh và nhóm cảnh báo người lái.',
                ],
            ],

            [
                'slug'  => 'vinfast-vf-6-480-km-cho-duong-truong',
                'title' => 'VinFast VF 6: 480 km cho đường trường',
                'cover' => 'catalog/vinfast/vinfast-vf-6/hero.jpg',
                'excerpt' => 'Quãng đường dài nhất nhì dải sản phẩm trên một chiếc xe vẫn đủ gọn '
                    . 'để đi lại hằng ngày.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Con số đáng chú ý nhất',
                        'body'  => "VF 6 dùng pin 59,6 kWh, quãng đường 480 km mỗi lần sạc theo NEDC, "
                            . "bản Plus đạt 201 mã lực.\n\n"
                            . "Điều thú vị là 480 km này còn nhỉnh hơn VF 8 (447 km) và VF 9 (438 km), "
                            . "dù pin nhỏ hơn đáng kể. Xe nhẹ hơn và nhỏ hơn nên tiêu thụ ít hơn — "
                            . "đó là lý do VF 6 đi xa được như vậy với viên pin khiêm tốn.\n\n"
                            . "Với người chạy đường trường đều đặn, ít lần dừng sạc hơn đồng nghĩa "
                            . "chuyến đi ngắn lại.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'split-alt',
                        'title'  => 'Gọn cho phố, đủ cho đường dài',
                        'intro'  => 'VF 6 nằm ở cỡ B — xoay trở trong ngõ và tìm chỗ đỗ dễ hơn VF 8 hay VF 9 '
                            . 'rất nhiều, nhưng vẫn đi xa được. Đây là lý do mẫu xe này hợp với người '
                            . 'chỉ mua một chiếc xe duy nhất cho mọi nhu cầu.',
                        'items'  => [
                            ['image' => 'catalog/vinfast/vinfast-vf-6/ngoai-that-1.jpg', 'label' => 'VinFast VF 6'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'So với VF 5 Plus và VF 7',
                        'body'  => "Xuống VF 5 Plus: gọn hơn, rẻ hơn, nhưng quãng đường còn 326 km — "
                            . "hợp nếu bạn gần như chỉ chạy trong tỉnh.\n\n"
                            . "Lên VF 7: khoẻ hơn hẳn với 260 kW và 500 Nm, quãng đường 496 km, "
                            . "khoang rộng hơn — hợp nếu thường chở đủ người kèm hành lý.\n\n"
                            . "Cả ba xe đều có tại showroom để so trực tiếp trong một buổi.",
                    ],
                ],
                'seo' => [
                    'title'       => 'VinFast VF 6: 480 km cho đường trường',
                    'description' => 'VF 6 pin 59,6 kWh, quãng đường 480 km mỗi lần sạc, bản Plus 201 mã lực — '
                        . 'đi xa hơn cả VF 8 và VF 9 với viên pin nhỏ hơn.',
                ],
            ],

            [
                'slug'  => 'noi-that-vf-7-khoang-lai-sang-va-rong',
                'title' => 'Nội thất VF 7: khoang lái sáng và rộng',
                'cover' => 'catalog/vinfast/vinfast-vf-7/noi-that-khoang-dat-nang-tam-tien-nghi-1.jpg',
                'excerpt' => 'Tông kem sáng, bệ trung tâm liền mạch và màn hình đặt ngang tầm mắt — '
                    . 'khoang lái VF 7 khác hẳn cảm giác xe điện phổ thông.',
                'sections' => [
                    [
                        'type'  => 'text',
                        'title' => 'Vì sao khoang xe trông rộng hơn thực tế',
                        'body'  => "VF 7 dùng pin khả dụng 75,3 kWh, công suất tối đa 260 kW, "
                            . "mô-men xoắn cực đại 500 Nm và đi được 496 km mỗi lần sạc theo NEDC.\n\n"
                            . "Nhưng thứ gây ấn tượng khi mở cửa lại là khoang lái. Xe điện không có "
                            . "trục các-đăng chạy dọc sàn, nên sàn phẳng và chỗ để chân hàng ghế sau "
                            . "rộng hơn xe xăng cùng cỡ.\n\n"
                            . "Tông kem sáng cùng cửa sổ trời kéo dài khiến khoang xe thoáng hơn nữa. "
                            . "Đổi lại, nội thất sáng màu cần chăm hơn — nhà có trẻ nhỏ nên tính tới điều này.",
                    ],
                    [
                        'type'   => 'media',
                        'layout' => 'gallery',
                        'title'  => 'Khoang lái VF 7',
                        'items'  => [
                            ['image' => 'catalog/vinfast/vinfast-vf-7/noi-that-khoang-dat-nang-tam-tien-nghi-1.jpg', 'label' => 'Bảng táp-lô'],
                            ['image' => 'catalog/vinfast/vinfast-vf-7/trai-nghiem-thi-giac-khong-gioi-han-1.jpg', 'label' => 'Tầm nhìn'],
                            ['image' => 'catalog/vinfast/vinfast-vf-7/diem-nhan-cong-nghe-nang-cap-trai-nghiem-1.jpg', 'label' => 'Điểm nhấn công nghệ'],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'title' => 'Ngồi thử cả hàng ghế sau',
                        'body'  => "Khách đi xem xe thường chỉ ngồi ghế lái rồi quyết. Với VF 7, "
                            . "hàng ghế sau mới là nơi khác biệt lộ ra rõ nhất — hãy ngồi vào đó vài phút, "
                            . "duỗi chân thử.\n\n"
                            . "Xe có tại showroom, và đại lý xếp được lịch lái thử tại nhà trong địa bàn tỉnh. "
                            . "Gọi 0889 159 579.",
                    ],
                ],
                'seo' => [
                    'title'       => 'Nội thất VinFast VF 7: khoang lái sáng và rộng',
                    'description' => 'Khoang lái VF 7 tông kem sáng, sàn phẳng nhờ cấu trúc xe điện. '
                        . 'Pin 75,3 kWh, 260 kW, 500 Nm, 496 km mỗi lần sạc.',
                ],
            ],
        ];
    }
}

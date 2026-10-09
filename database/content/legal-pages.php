<?php

/*
 * Chính sách quyền riêng tư + Điều khoản sử dụng (bản 06/10/2026).
 * Dùng chung cho LexusSiteSeeder (DB mới) và migration 2026_10_06_100000
 * (site đang chạy).
 *
 * Mô tả ĐÚNG những gì hệ thống đang lưu (xem StoreLead: tên, số điện thoại,
 * dòng xe, đồng ý, trang gửi, UTM, gclid, IP) và mã đo lường đang bật
 * (partials/tracking: GA4; GTM/Pixel chỉ khi điền mã). Đổi form, bật mã đo
 * lường hay chạy lại quảng cáo thì sửa trang này theo.
 */

$updated = '<p>Cập nhật lần cuối: 06/10/2026.';

return [
    'quyen-rieng-tu' => [
        'seo' => [
            'title'       => 'Chính sách quyền riêng tư | Thu Hà tư vấn Lexus',
            'description' => 'Website tư vấn của chuyên viên Nguyễn Thị Thu Hà thu thập thông tin gì, dùng để làm gì, lưu bao lâu và quyền của bạn — '
                .'theo Luật Bảo vệ dữ liệu cá nhân 2025.',
            'eyebrow'     => 'QUYỀN RIÊNG TƯ',
            'excerpt'     => 'Thu Hà chỉ hỏi những gì cần để tư vấn cho bạn — và bạn luôn có quyền yêu cầu xoá.',
        ],
        'sections' => [
            ['type' => 'text', 'title' => 'Tóm tắt', 'intro' => 'Chính sách trong ba câu',
                'body' => '<p>Khi bạn gửi form đăng ký lái thử hoặc nhận báo giá, chuyên viên Thu Hà lưu <strong>họ tên, số điện thoại và dòng xe bạn quan tâm</strong> '
                    .'để liên hệ tư vấn. Thông tin <strong>không được bán</strong> và chỉ chia sẻ với đại lý Lexus Thăng Long khi cần để làm báo giá, lái thử hoặc hợp đồng cho bạn. '
                    .'Bạn có thể yêu cầu xem, sửa hoặc xoá dữ liệu bất cứ lúc nào qua số 0934 846 666 (gọi) hoặc Zalo 0989 345 989.</p>'
                    .$updated.' Chính sách áp dụng theo Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 và Nghị định 356/2025/NĐ-CP.</p>'],
            ['type' => 'text', 'title' => '1. Người chịu trách nhiệm', 'intro' => 'Ai nhận và xử lý dữ liệu của bạn',
                'body' => '<p>Website này là <strong>trang tư vấn cá nhân</strong> của bà <strong>Nguyễn Thị Thu Hà</strong>, chuyên viên tư vấn bán hàng '
                    .'tại đại lý Lexus Thăng Long (ngã tư Phạm Hùng – Dương Đình Nghệ, Cầu Giấy, Hà Nội). '
                    .'Thu Hà là người quản lý website và trực tiếp nhận, xử lý thông tin bạn gửi qua website.</p>'
                    .'<p>Website được đại lý cho phép sử dụng tên và logo; <strong>không phải</strong> website chính thức của Lexus Việt Nam hay của đại lý. '
                    .'Mọi yêu cầu về dữ liệu cá nhân, vui lòng gọi 0934 846 666 hoặc nhắn Zalo 0989 345 989.</p>'],
            ['type' => 'text', 'title' => '2. Dữ liệu thu thập', 'intro' => 'Website thu thập những gì',
                'body' => '<h3>Do bạn cung cấp qua form</h3><ul><li>Họ và tên.</li><li>Số điện thoại.</li><li>Dòng xe bạn quan tâm.</li>'
                    .'<li>Xác nhận đồng ý với chính sách này.</li></ul>'
                    .'<h3>Ghi nhận tự động khi gửi form</h3><ul><li>Trang bạn đang xem lúc gửi, trang bạn vào đầu tiên, nguồn đưa bạn tới website '
                    .'(Google, Facebook, Zalo…), tham số chiến dịch (UTM) và mã nhấp quảng cáo Google (gclid), nếu có; '
                    .'loại thiết bị (điện thoại hay máy tính).</li>'
                    .'<li>Địa chỉ IP — dùng để chống gửi tự động (spam).</li></ul>'
                    .'<h3>Thống kê ẩn danh</h3><ul><li>Số lượt bấm nút Gọi / Zalo theo từng trang và số đo tốc độ tải trang — '
                    .'không kèm tên, số điện thoại hay địa chỉ IP, chỉ để biết trang nào hữu ích và cải thiện tốc độ.</li></ul>'
                    .'<h3>Lưu trên trình duyệt của bạn</h3><ul><li>Cookie phiên làm việc, cần để form gửi được an toàn.</li>'
                    .'<li>Ghi nhớ đã xem hộp báo giá tự bật, để không hiện lại liên tục. Dữ liệu này nằm trên máy bạn, không gửi về máy chủ.</li>'
                    .'<li>Ghi nhớ nguồn đưa bạn tới website lần đầu — chỉ gửi kèm khi bạn chủ động gửi form.</li>'
                    .'<li>Cookie của Google Analytics (xem mục 7).</li></ul>'
                    .'<p>Website <strong>không</strong> thu thập số CCCD, thông tin thẻ ngân hàng hay mật khẩu, và không nhận thanh toán.</p>'],
            ['type' => 'text', 'title' => '3. Mục đích', 'intro' => 'Dữ liệu được dùng để làm gì',
                'body' => '<ul><li>Gọi lại để xác nhận lịch lái thử, gửi báo giá và phương án mua xe theo yêu cầu của bạn.</li>'
                    .'<li>Chăm sóc sau bán hàng khi bạn đã mua xe: nhắc lịch bảo dưỡng, thông báo chương trình liên quan đến xe của bạn.</li>'
                    .'<li>Thống kê nguồn khách (khách đến từ trang nào, kênh nào) để cải thiện website.</li></ul>'
                    .'<p>Dữ liệu của bạn không được dùng cho mục đích khác khi chưa hỏi ý kiến bạn.</p>'],
            ['type' => 'text', 'title' => '4. Chia sẻ', 'intro' => 'Dữ liệu được chia sẻ với ai',
                'body' => '<ul><li>Đại lý Lexus Thăng Long — để lập báo giá, hợp đồng, đăng ký lái thử và giao xe theo yêu cầu của bạn.</li>'
                    .'<li>Ngân hàng, công ty bảo hiểm — chỉ khi bạn yêu cầu tư vấn vay mua xe hoặc bảo hiểm, và chỉ những thông tin cần cho hồ sơ đó.</li>'
                    .'<li>Nhà cung cấp máy chủ lưu trữ website và Google (Analytics) — chỉ ở mức cần để website hoạt động và thống kê lượt truy cập.</li>'
                    .'<li>Cơ quan nhà nước có thẩm quyền — khi pháp luật yêu cầu.</li></ul>'
                    .'<p>Dữ liệu cá nhân của bạn <strong>không được bán, không cho thuê</strong>.</p>'],
            ['type' => 'text', 'title' => '5. Lưu trữ', 'intro' => 'Dữ liệu được lưu bao lâu, ở đâu',
                'body' => '<p>Dữ liệu được lưu trên máy chủ của website, chỉ người được phân quyền quản trị mới xem được. '
                    .'Dữ liệu được giữ trong thời gian cần thiết cho việc tư vấn và chăm sóc khách hàng, hoặc đến khi bạn yêu cầu xoá — '
                    .'trừ trường hợp pháp luật yêu cầu lưu giữ lâu hơn (ví dụ hồ sơ hợp đồng mua bán).</p>'],
            ['type' => 'text', 'title' => '6. Quyền của bạn', 'intro' => 'Bạn có quyền gì với dữ liệu của mình',
                'body' => '<ul><li>Được biết dữ liệu nào đang được lưu và dùng vào việc gì.</li><li>Xem và yêu cầu sửa dữ liệu sai.</li>'
                    .'<li>Rút lại sự đồng ý và yêu cầu xoá dữ liệu.</li><li>Yêu cầu ngừng liên hệ tư vấn, chăm sóc.</li>'
                    .'<li>Khiếu nại nếu cho rằng dữ liệu bị xử lý sai quy định.</li></ul>'
                    .'<p>Gọi 0934 846 666 hoặc nhắn Zalo 0989 345 989 để thực hiện các quyền trên. Thu Hà phản hồi trong thời hạn pháp luật quy định.</p>'],
            // Giữ tên mục: SeoAuditFixesTest. Chạy lại Google Ads có tiếp thị lại
            // thì phải nói rõ ở đây trước khi bật.
            ['type' => 'text', 'title' => '7. Quảng cáo Google', 'intro' => 'Google Analytics, quảng cáo và cách từ chối',
                'body' => '<p>Website dùng <strong>Google Analytics</strong> để đếm lượt truy cập và xem trang nào hữu ích. '
                    .'Google Analytics đặt cookie trên trình duyệt của bạn và xử lý dữ liệu theo '
                    .'<a href="https://policies.google.com/privacy?hl=vi" rel="noopener" target="_blank">chính sách quyền riêng tư của Google</a>.</p>'
                    .'<p>Website hiện <strong>không chạy quảng cáo Google</strong> và không bật tiếp thị lại. '
                    .'Nếu bạn đến website từ một quảng cáo Google, mã nhấp quảng cáo (gclid) chỉ được lưu kèm khi bạn chủ động gửi form. '
                    .'Trước khi bật quảng cáo hoặc tiếp thị lại, chính sách này sẽ được cập nhật.</p>'
                    .'<p>Bạn có thể tắt quảng cáo được cá nhân hoá tại '
                    .'<a href="https://myadcenter.google.com/" rel="noopener" target="_blank">Trung tâm quảng cáo của tôi</a> của Google '
                    .'và chặn Google Analytics bằng '
                    .'<a href="https://tools.google.com/dlpage/gaoptout" rel="noopener" target="_blank">tiện ích chọn không tham gia Google Analytics</a>.</p>'],
            ['type' => 'text', 'title' => '8. Thay đổi chính sách', 'intro' => 'Khi chính sách được sửa',
                'body' => '<p>Khi website thu thập thêm loại dữ liệu hoặc dùng thêm công cụ mới, chính sách này được cập nhật và ghi ngày ở mục Tóm tắt. '
                    .'Xem thêm <a href="/dieu-khoan">Điều khoản sử dụng</a>.</p>'],
        ],
    ],

    'dieu-khoan' => [
        'seo' => [
            'title'       => 'Điều khoản sử dụng | Thu Hà tư vấn Lexus',
            'description' => 'Điều khoản sử dụng website tư vấn cá nhân của chuyên viên Nguyễn Thị Thu Hà: tính chất thông tin, giá tham khảo, hình ảnh, nhãn hiệu và trách nhiệm.',
            'eyebrow'     => 'ĐIỀU KHOẢN',
            'excerpt'     => 'Những điều cần biết khi sử dụng thông tin trên website này.',
        ],
        'sections' => [
            ['type' => 'text', 'title' => '1. Về website', 'intro' => 'Website này là gì',
                'body' => '<p>Đây là <strong>website tư vấn cá nhân</strong> của bà <strong>Nguyễn Thị Thu Hà</strong>, chuyên viên tư vấn bán hàng tại đại lý Lexus Thăng Long, Hà Nội. '
                    .'Website được đại lý cho phép sử dụng tên và logo; <strong>không phải</strong> website chính thức của Lexus Việt Nam, '
                    .'Toyota Motor Corporation hay của đại lý. Khi truy cập và sử dụng website, bạn đồng ý với các điều khoản dưới đây.</p>'
                    .$updated.'</p>'],
            ['type' => 'text', 'title' => '2. Website làm gì', 'intro' => 'Tư vấn, không bán hàng trực tuyến',
                'body' => '<ul><li>Website giới thiệu các dòng xe Lexus đại lý Lexus Thăng Long đang bán, để bạn tham khảo và liên hệ chuyên viên Thu Hà.</li>'
                    .'<li>Website <strong>không bán hàng trực tuyến và không nhận thanh toán</strong>: không có giỏ hàng, không thu tiền cọc qua website. '
                    .'Hợp đồng mua bán được ký với đại lý Lexus Thăng Long; tiền cọc và tiền xe thanh toán cho đại lý theo hợp đồng.</li>'
                    .'<li>Tư vấn qua điện thoại, Zalo và tại showroom không mất phí.</li></ul>'],
            ['type' => 'text', 'title' => '3. Giá và thông tin xe', 'intro' => 'Giá và thông số chỉ để tham khảo',
                'body' => '<ul><li>Giá trên website là giá niêm yết tham khảo đã gồm VAT, có thể thay đổi theo chính sách của Lexus Việt Nam mà không báo trước. '
                    .'Giá giao dịch là giá ghi trên hợp đồng mua bán.</li>'
                    .'<li>Thông số kỹ thuật theo công bố của hãng và có thể khác nhau giữa các phiên bản, lô xe.</li>'
                    .'<li>Ví dụ về giá lăn bánh, khoản vay chỉ mang tính minh họa; số liệu chính xác theo quy định và hồ sơ tại thời điểm giao dịch.</li></ul>'],
            ['type' => 'text', 'title' => '4. Hình ảnh', 'intro' => 'Hình ảnh xe và showroom',
                'body' => '<p>Ảnh xe là ảnh chụp xe bán tại Việt Nam; trang bị có thể khác nhau giữa các phiên bản, màu sắc hiển thị phụ thuộc màn hình. '
                    .'Hình ảnh showroom và xưởng dịch vụ là ảnh thực tế tại Lexus Thăng Long.</p>'],
            ['type' => 'text', 'title' => '5. Nhãn hiệu', 'intro' => 'Nhãn hiệu và nội dung',
                'body' => '<p>Tên, logo Lexus và tên các dòng xe là nhãn hiệu thuộc Toyota Motor Corporation; tên và logo Lexus Thăng Long thuộc đại lý. '
                    .'Các nhãn hiệu này được dùng để giới thiệu xe do đại lý phân phối, với sự cho phép của đại lý. '
                    .'Nội dung bài viết trên website không được sao chép cho mục đích thương mại khi chưa được đồng ý.</p>'],
            ['type' => 'text', 'title' => '6. Trách nhiệm', 'intro' => 'Giới hạn trách nhiệm',
                'body' => '<p>Thông tin trên website được cố gắng giữ chính xác và cập nhật, nhưng không bảo đảm không có sai sót. '
                    .'Quyết định mua xe nên dựa trên báo giá, hợp đồng và thông tin xác nhận trực tiếp với đại lý. '
                    .'Liên kết đến website khác (bản đồ, Zalo, ngân hàng…) thuộc trách nhiệm của bên sở hữu website đó.</p>'],
            ['type' => 'text', 'title' => '7. Liên hệ', 'intro' => 'Liên hệ và luật áp dụng',
                'body' => '<p>Mọi câu hỏi về điều khoản, vui lòng gọi chuyên viên Thu Hà: 0934 846 666 hoặc nhắn Zalo 0989 345 989. Điều khoản này được điều chỉnh theo pháp luật Việt Nam. '
                    .'Xem thêm <a href="/quyen-rieng-tu">Chính sách quyền riêng tư</a>.</p>'],
        ],
    ],
];

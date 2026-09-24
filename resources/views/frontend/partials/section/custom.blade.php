{{--
    Khối phải viết code riêng cho dự án.

    Quy ước: tạo view `frontend/sections/{slug-của-tên-mục}.blade.php` trong
    repo của hãng. VD mục tên "Bảng mua lại" → frontend/sections/bang-mua-lai.
    Chưa có view thì mục im lặng — không nổ trang khách.
--}}
@includeIf('frontend.sections.'.Str::slug($section['title'] ?? ''), ['section' => $section])

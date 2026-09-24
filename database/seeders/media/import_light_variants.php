<?php

/**
 * Ảnh PHIÊN BẢN nền sáng cho LM và LS (thẻ trang chủ + thẻ phiên bản trang xe).
 *
 *     php database/seeders/media/import_light_variants.php
 *
 * Ảnh Car-project của LM, LS chụp nền tối, lệch tông với các thẻ khác (ảnh
 * phòng chụp sáng của lexus.com). lexus.com không có LM (không bán ở Mỹ) và
 * không còn trình xem LS, nên lấy ảnh xe NỀN TRONG SUỐT từ nguồn chính thức:
 *   · LM: trình cấu hình Lexus Anh (images.lexus-europe.com), PNG trong suốt
 *   · LS: trình xem 360° Lexus Nhật (lexus.jp), PNG trong suốt 640×480
 * rồi ghép lên nền "phòng chụp" vẽ bằng GD, pha màu theo ảnh lexus.com (tường
 * xám xanh, sàn sáng, tối dần ra hai mép) + bóng mềm dưới xe. Xe hướng đầu
 * sang trái, chiếm ~86% bề ngang — cùng bố cục các thẻ còn lại.
 *
 * Ra: database/seeders/media/lexus/{xe}/phien-ban/{tên}.webp (1120×800, tỉ lệ
 * 1,4 = khung thẻ). Brands\LexusSeeder đã trỏ sẵn tới các tên này.
 * Cần PHP có GD + WebP. Bản quyền ảnh xe thuộc Lexus.
 */
const OUT = __DIR__.'/lexus';
const W = 1120;
const H = 800;
const HORIZON = 0.60;   // đường chân tường, tính theo chiều cao
const CAR_WIDTH = 0.86; // bề ngang xe so với khung
const GROUND = 0.88;    // mép dưới bánh xe

$lmEu = 'https://images.lexus-europe.com/gb/product-token/eec7f472-f2be-43bc-899d-051f6e2bc66a'
    .'/vehicle/867e6a9c-ed55-496a-af55-7e2cd367106f/width/1600/height/900/scale-mode/1/padding/0'
    .'/image-quality/90/day-exterior-33_%s_LA21.png'; // góc 33 × 10° = 330°: đầu xe chếch trái
$jp = 'https://lexus.jp/models/ls/images/features/exterior/tcv/%1$s/%1$s_60.png'; // 60°: đầu xe chếch trái

$jobs = [
    // [xe, tên file, url, ghi chú màu]
    ['lm', '6-cho', sprintf($lmEu, '1J7'), 'Sonic Titanium'],
    ['lm', '4-cho', sprintf($lmEu, '223'), 'Graphite Black'],
    ['ls', '500h', sprintf($jp, '1L2'), 'Sonic Titanium'],
];

function fetch(string $url): GdImage
{
    $ctx = stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (LexusThangLong media import)\r\n", 'timeout' => 30]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || ! ($im = @imagecreatefromstring($data))) {
        fwrite(STDERR, "Không tải được: $url\n");
        exit(1);
    }
    imagepalettetotruecolor($im); // PNG của lexus.jp dạng bảng màu → cần truecolor để giữ alpha
    imagealphablending($im, false);
    imagesavealpha($im, true);

    return $im;
}

/** Cắt sát phần xe: chỉ tính điểm gần như đặc, bỏ cả bóng mờ có sẵn trong ảnh → bánh xe chạm đúng mặt đất. */
function trim_alpha(GdImage $im): GdImage
{
    [$w, $h] = [imagesx($im), imagesy($im)];
    [$x0, $y0, $x1, $y1] = [$w, $h, 0, 0];
    for ($y = 0; $y < $h; $y += 2) {
        for ($x = 0; $x < $w; $x += 2) {
            if (((imagecolorat($im, $x, $y) >> 24) & 0x7F) < 30) {
                $x0 = min($x0, $x); $x1 = max($x1, $x); $y0 = min($y0, $y); $y1 = max($y1, $y);
            }
        }
    }

    return imagecrop($im, ['x' => $x0, 'y' => $y0, 'width' => $x1 - $x0 + 2, 'height' => $y1 - $y0 + 2]);
}

function mix(array $a, array $b, float $t): array
{
    $t = max(0, min(1, $t));

    return [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t, $a[2] + ($b[2] - $a[2]) * $t];
}

/**
 * Nền phòng chụp: tường xám xanh, sàn sáng giữa, tối dần ra mép, cộng bóng
 * mềm dưới xe (elip Gauss tính theo từng điểm ảnh — mịn, không răng cưa).
 * $shadow = [tâm x, tâm y (mặt đất), bán trục x, bán trục y].
 */
function backdrop(array $shadow): GdImage
{
    $im = imagecreatetruecolor(W, H);
    $wallTop = [0xCD, 0xD5, 0xDE]; $wallLow = [0xDC, 0xE2, 0xE8];
    $floorMid = [0xE4, 0xE5, 0xE8]; $floorEdge = [0xBC, 0xC4, 0xD0];
    $hz = H * HORIZON;
    $blend = 70; // chân tường chuyển dần trong 70px
    [$sx, $sy, $rx, $ry] = $shadow;
    for ($y = 0; $y < H; $y++) {
        for ($x = 0; $x < W; $x++) {
            $side = abs($x - W / 2) / (W / 2);
            $wall = mix(mix($wallTop, $wallLow, $y / $hz), [0xC8, 0xD0, 0xD9], $side ** 3 * .5);
            $depth = max(0, ($y - $hz) / (H - $hz));
            $floor = mix($floorMid, $floorEdge, min(1, $side ** 2 * .8 + $depth ** 2 * .3));
            $t = ($y - ($hz - $blend / 2)) / $blend;
            $t = $t <= 0 ? 0 : ($t >= 1 ? 1 : $t * $t * (3 - 2 * $t));
            $c = mix($wall, $floor, $t);

            // Bóng: đậm nhất ngay dưới xe, nhạt dần ra ngoài.
            $d = (($x - $sx) / $rx) ** 2 + (($y - $sy) / $ry) ** 2;
            if ($d < 6) {
                $k = .42 * exp(-$d * 1.6) + .30 * exp(-((($x - $sx) / ($rx * .9)) ** 2 + (($y - $sy) / ($ry * .22)) ** 2) * 3);
                $c = mix($c, [0x2A, 0x30, 0x3A], min(.75, $k));
            }
            imagesetpixel($im, $x, $y, ((int) $c[0] << 16) | ((int) $c[1] << 8) | (int) $c[2]);
        }
    }

    return $im;
}

foreach ($jobs as [$car, $name, $url, $colour]) {
    $src = trim_alpha(fetch($url));
    [$sw, $sh] = [imagesx($src), imagesy($src)];
    $cw = (int) (W * CAR_WIDTH);
    $ch = (int) round($sh * $cw / $sw);
    if ($ch > H * .60) { // xe cao (LM) → giới hạn theo chiều cao
        $ch = (int) (H * .60);
        $cw = (int) round($sw * $ch / $sh);
    }
    $x = intdiv(W - $cw, 2);
    $y = (int) (H * GROUND) - $ch;

    $canvas = backdrop([W / 2, H * GROUND - 4, $cw * .52, 34]);
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $src, $x, $y, 0, 0, $cw, $ch, $sw, $sh);

    @mkdir(OUT."/$car/phien-ban", 0775, true);
    imagewebp($canvas, $file = OUT."/$car/phien-ban/$name.webp", 88);
    echo "✓ $car/phien-ban/$name.webp ($colour, xe {$cw}×{$ch})\n";
}

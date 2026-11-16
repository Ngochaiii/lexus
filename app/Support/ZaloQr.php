<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Cache;

/**
 * Mã QR Zalo cho khách xem web trên máy tính (16/11/2026): quét bằng camera
 * điện thoại là mở Zalo của chính khách tới đúng trang Zalo người tư vấn.
 *
 * Tạo SVG ngay trên máy chủ bằng chillerlan/php-qrcode (Filament đã kéo theo,
 * không thêm gói), không gọi dịch vụ ngoài. Tự vẽ một path gộp các ô đen liền
 * nhau theo hàng (~2 KB, bản mặc định của thư viện ~8 KB) vì mã chèn thẳng
 * vào trang ở vài chỗ. Nhớ đệm theo nội dung, đổi số là mã mới.
 */
class ZaloQr
{
    private const QUIET = 2; // viền trắng (ô) quanh mã — máy quét cần

    public static function svg(string $url): string
    {
        return Cache::rememberForever('zalo-qr:v2:'.md5($url), function () use ($url) {
            $matrix = (new QRCode(new QROptions(['eccLevel' => EccLevel::M, 'addQuietzone' => false])))
                ->addByteSegment($url)
                ->getQRMatrix()
                ->getMatrix(true);

            $size = count($matrix) + 2 * self::QUIET;
            $path = '';
            foreach ($matrix as $y => $row) {
                $x = 0;
                $n = count($row);
                while ($x < $n) {
                    if (! $row[$x]) { $x++; continue; }
                    $start = $x;
                    while ($x < $n && $row[$x]) { $x++; }
                    $path .= 'M'.($start + self::QUIET).' '.($y + self::QUIET).'h'.($x - $start).'v1h-'.($x - $start).'z';
                }
            }

            // Hình trang trí cho người đọc màn hình: chữ bên cạnh đã nói đủ.
            return '<svg aria-hidden="true" focusable="false" class="zalo-qr__code" viewBox="0 0 '.$size.' '.$size.'"'
                .' shape-rendering="crispEdges"><rect width="'.$size.'" height="'.$size.'" fill="#fff"/>'
                .'<path fill="#111" d="'.$path.'"/></svg>';
        });
    }
}

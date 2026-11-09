<?php

namespace App\Http\Controllers\Api;

use App\Models\SiteEvent;
use App\Support\Attribution;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Nhận sự kiện ẩn danh từ insight.js (navigator.sendBeacon):
 *   - call / zalo : khách bấm nút Gọi / Zalo trên trang nào, nút nằm ở đâu
 *                   (place) và sau bao nhiêu giây trên trang (secs) — tách
 *                   chạm nhầm ở thanh đáy khỏi khách đọc giá rồi mới bấm;
 *   - call_stay, call_left, call_back (và zalo_…): sau khi bấm khách ở lại
 *                   trang, rời trang, hay quay lại sau bao lâu (secs);
 *   - view, see_price, see_form, form_start, form_error, leave: hành trình
 *                   một lượt xem trang — thấy bảng giá, thấy form, bắt đầu
 *                   điền, gửi bị lỗi, rời trang sau bao lâu (secs) và kéo tới
 *                   đâu (scroll %, lưu ở value). Mỗi bước tối đa 1 lần/lượt xem;
 *   - vital       : số đo tốc độ của khách thật (LCP, INP, CLS, FCP, TTFB).
 *
 * Mọi sự kiện ghi kèm source + medium (cpc = quảng cáo) để tách khách quảng cáo.
 *
 * Không lưu IP, không cookie. Bot / Lighthouse / trình duyệt headless bị bỏ
 * qua để số liệu chỉ là người thật. Luôn trả 204 — trình duyệt không chờ.
 */
class EventController extends Controller
{
    private const LIMITS = ['LCP' => 60000, 'INP' => 20000, 'FCP' => 60000, 'TTFB' => 60000, 'CLS' => 10];

    /** Vị trí nút insight.js gửi lên; giá trị khác bỏ trống. */
    private const PLACES = ['bar', 'float', 'header', 'hero', 'table', 'sheet', 'thanks', 'advisor', 'strip', 'call_sheet', 'footer', 'content', 'popup'];

    /** Kết quả sau khi bấm Gọi/Zalo. */
    private const AFTER_TAP = ['call_stay', 'call_left', 'call_back', 'zalo_stay', 'zalo_left', 'zalo_back'];

    /** Hành trình một lượt xem trang. */
    public const JOURNEY = ['view', 'see_price', 'see_form', 'form_start', 'form_error', 'leave'];

    public function store(Request $request): Response
    {
        if (Attribution::isBot($request->userAgent())) {
            return response()->noContent();
        }

        $payload = $request->json()->all() ?: $request->all();
        $events = array_slice(is_array($payload['events'] ?? null) ? $payload['events'] : [], 0, 10);
        $touch = Attribution::classify(Attribution::decode($payload['attribution'] ?? null));
        $device = Attribution::device($request->userAgent());

        $rows = [];
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $type = $event['type'] ?? null;
            $path = is_string($event['path'] ?? null) && str_starts_with($event['path'], '/')
                ? mb_substr(strtok($event['path'], '?#'), 0, 255) : null;

            if (in_array($type, ['call', 'zalo', ...self::AFTER_TAP, ...self::JOURNEY], true)) {
                $place = $event['place'] ?? null;
                $secs = $event['secs'] ?? null;
                $scroll = $event['scroll'] ?? null;
                $rows[] = ['type' => $type, 'metric' => null,
                    // Độ sâu kéo trang (%) của sự kiện rời trang.
                    'value' => $type === 'leave' && is_numeric($scroll) && $scroll >= 0 && $scroll <= 100 ? (float) round($scroll) : null,
                    'place' => in_array($place, self::PLACES, true) ? $place : null,
                    'secs' => is_int($secs) && $secs >= 0 && $secs <= 86400 ? $secs : null];
            } elseif ($type === 'vital') {
                $metric = strtoupper((string) ($event['metric'] ?? ''));
                $value = $event['value'] ?? null;
                if (! isset(self::LIMITS[$metric]) || ! is_numeric($value) || $value < 0 || $value > self::LIMITS[$metric]) {
                    continue;
                }
                $rows[] = ['type' => 'vital', 'metric' => $metric, 'value' => round((float) $value, $metric === 'CLS' ? 4 : 0),
                    'place' => null, 'secs' => null];
            } else {
                continue;
            }

            $rows[array_key_last($rows)] += [
                'path' => $path, 'device' => $device, 'source' => $touch['source'], 'medium' => $touch['medium'], 'created_at' => now(),
            ];
        }

        if ($rows) {
            SiteEvent::insert($rows);
        }

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\SiteEvent;
use App\Support\Attribution;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Nhận sự kiện ẩn danh từ insight.js (navigator.sendBeacon):
 *   - call / zalo : khách bấm nút Gọi / Zalo trên trang nào;
 *   - vital       : số đo tốc độ của khách thật (LCP, INP, CLS, FCP, TTFB).
 *
 * Không lưu IP, không cookie. Bot / Lighthouse / trình duyệt headless bị bỏ
 * qua để số liệu chỉ là người thật. Luôn trả 204 — trình duyệt không chờ.
 */
class EventController extends Controller
{
    private const LIMITS = ['LCP' => 60000, 'INP' => 20000, 'FCP' => 60000, 'TTFB' => 60000, 'CLS' => 10];

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

            if (in_array($type, ['call', 'zalo'], true)) {
                $rows[] = ['type' => $type, 'metric' => null, 'value' => null];
            } elseif ($type === 'vital') {
                $metric = strtoupper((string) ($event['metric'] ?? ''));
                $value = $event['value'] ?? null;
                if (! isset(self::LIMITS[$metric]) || ! is_numeric($value) || $value < 0 || $value > self::LIMITS[$metric]) {
                    continue;
                }
                $rows[] = ['type' => 'vital', 'metric' => $metric, 'value' => round((float) $value, $metric === 'CLS' ? 4 : 0)];
            } else {
                continue;
            }

            $rows[array_key_last($rows)] += [
                'path' => $path, 'device' => $device, 'source' => $touch['source'], 'created_at' => now(),
            ];
        }

        if ($rows) {
            SiteEvent::insert($rows);
        }

        return response()->noContent();
    }
}

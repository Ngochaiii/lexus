<?php

namespace App\Http\Controllers\Api;

use App\Services\ChargingStationFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ChargingStationController extends Controller
{
    public function __invoke(Request $request, ChargingStationFinder $finder): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:160', 'required_without_all:lat,lng'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);

        try {
            $lat = array_key_exists('lat', $data) ? (float) $data['lat'] : null;
            $lng = array_key_exists('lng', $data) ? (float) $data['lng'] : null;

            if ($lat === null || $lng === null) {
                $coordinates = $finder->geocode((string) $data['q']);

                if ($coordinates === null) {
                    throw ValidationException::withMessages([
                        'q' => 'Không tìm thấy vị trí này tại Việt Nam. Vui lòng nhập rõ phường/xã và tỉnh/thành.',
                    ]);
                }

                $lat = $coordinates['lat'];
                $lng = $coordinates['lng'];
            }

            $stations = $finder->nearby($lat, $lng);
        } catch (RuntimeException $exception) {
            Log::warning('Không lấy được dữ liệu trạm sạc miễn phí.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Chưa lấy được dữ liệu trạm sạc. Website đang hiển thị danh sách dự phòng.',
                'data' => [],
            ], 503);
        }

        $attribution = $finder->attribution();

        return response()->json([
            'data' => $stations,
            'meta' => [
                ...$attribution,
                'latitude' => $lat,
                'longitude' => $lng,
                'realtime_availability' => false,
            ],
        ]);
    }
}

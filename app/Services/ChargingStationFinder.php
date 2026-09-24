<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

class ChargingStationFinder
{
    /** @return array{lat:float,lng:float}|null */
    public function geocode(string $query): ?array
    {
        $query = Str::squish($query);
        $cacheKey = 'station-geocode:v1:'.sha1(Str::lower($query));
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && array_key_exists('found', $cached)) {
            return $cached['found'] ? ['lat' => $cached['lat'], 'lng' => $cached['lng']] : null;
        }

        // Public Nominatim yêu cầu tối đa một request/giây cho toàn ứng dụng.
        if (RateLimiter::tooManyAttempts('nominatim-public-global', 1)) {
            throw new RuntimeException('Dịch vụ tìm địa chỉ đang bận. Vui lòng thử lại sau một giây.');
        }

        RateLimiter::hit('nominatim-public-global', 1);

        try {
            $coordinates = $this->geocodeWithNominatim($query);
        } catch (RuntimeException) {
            $coordinates = null;
        }

        // Photon là nguồn OSM dự phòng không cần key. Nhờ vậy ô nhập
        // địa chỉ vẫn dùng được khi Nominatim tạm lỗi hoặc bị chặn DNS.
        if ($coordinates === null) {
            $coordinates = $this->geocodeWithPhoton($query);
        }

        Cache::put($cacheKey, $coordinates
            ? ['found' => true, ...$coordinates]
            : ['found' => false], (int) config('services.nominatim.cache_seconds', 2592000));

        return $coordinates;
    }

    /** @return array{lat:float,lng:float}|null */
    private function geocodeWithNominatim(string $query): ?array
    {

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => (string) config('services.nominatim.user_agent'),
                    'Referer' => (string) config('app.url'),
                    'Accept-Language' => 'vi,en;q=0.8',
                ])
                ->connectTimeout(4)
                ->timeout((int) config('services.nominatim.timeout', 8))
                ->get((string) config('services.nominatim.url'), [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'vn',
                    'addressdetails' => 0,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Không kết nối được dịch vụ tìm địa chỉ.');
        }

        if ($response->failed()) {
            throw new RuntimeException('Dịch vụ tìm địa chỉ tạm thời không phản hồi.');
        }

        $first = data_get($response->json(), '0');

        return $this->validCoordinates(data_get($first, 'lat'), data_get($first, 'lon'));
    }

    /** @return array{lat:float,lng:float}|null */
    private function geocodeWithPhoton(string $query): ?array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => (string) config('services.nominatim.user_agent'),
                    'Referer' => (string) config('app.url'),
                    'Accept-Language' => 'vi,en;q=0.8',
                ])
                ->connectTimeout(4)
                ->timeout((int) config('services.photon.timeout', 10))
                ->get((string) config('services.photon.url'), [
                    'q' => $query.', Việt Nam',
                    'limit' => 5,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Không kết nối được dịch vụ tìm địa chỉ dự phòng.');
        }

        $features = data_get($response->json(), 'features');

        if ($response->failed() || ! is_array($features) || ! array_is_list($features)) {
            throw new RuntimeException('Dịch vụ tìm địa chỉ dự phòng tạm thời không phản hồi.');
        }

        $needle = Str::lower(Str::ascii(Str::squish($query)));
        $feature = collect($features)
            ->filter(fn ($item) => is_array($item)
                && Str::upper((string) data_get($item, 'properties.countrycode')) === 'VN'
                && is_array(data_get($item, 'geometry.coordinates')))
            ->sortByDesc(function (array $item) use ($needle): int {
                $name = Str::lower(Str::ascii(Str::squish((string) data_get($item, 'properties.name'))));

                return $name === $needle ? 2 : (str_contains($needle, $name) || str_contains($name, $needle) ? 1 : 0);
            })
            ->first();

        $coordinates = data_get($feature, 'geometry.coordinates');

        if (! is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        return $this->validCoordinates($coordinates[1], $coordinates[0]);
    }

    /** @return array<int, array<string, mixed>> */
    public function nearby(float $lat, float $lng): array
    {
        $apiKey = trim((string) config('services.open_charge_map.key'));

        return $apiKey !== ''
            ? $this->nearbyFromOpenChargeMap($apiKey, $lat, $lng)
            : $this->nearbyFromOpenStreetMap($lat, $lng);
    }

    /** @return array{source:string,attribution_url:string} */
    public function attribution(): array
    {
        if (filled(config('services.open_charge_map.key'))) {
            return [
                'source' => 'Open Charge Map',
                'attribution_url' => 'https://openchargemap.io',
            ];
        }

        return [
            'source' => 'OpenStreetMap',
            'attribution_url' => 'https://www.openstreetmap.org/copyright',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function nearbyFromOpenChargeMap(string $apiKey, float $lat, float $lng): array
    {
        $distance = max(1, min(500, (int) config('services.open_charge_map.distance_km', 80)));
        $limit = max(1, min(50, (int) config('services.open_charge_map.max_results', 12)));
        $cacheKey = sprintf('charging-stations:ocm:v1:%.2f:%.2f:%d:%d', $lat, $lng, $distance, $limit);

        $raw = Cache::remember($cacheKey, (int) config('services.open_charge_map.cache_seconds', 21600), function () use ($apiKey, $lat, $lng, $distance, $limit): array {
            $url = rtrim((string) config('services.open_charge_map.url'), '/').'/poi/';

            try {
                $response = Http::acceptJson()
                    ->withHeaders(['User-Agent' => (string) config('services.nominatim.user_agent')])
                    ->connectTimeout(4)
                    ->timeout((int) config('services.open_charge_map.timeout', 10))
                    ->get($url, [
                        'key' => $apiKey,
                        'output' => 'json',
                        'countrycode' => strtoupper((string) config('services.open_charge_map.country_code', 'VN')),
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'distance' => $distance,
                        'distanceunit' => 'KM',
                        'maxresults' => $limit,
                        'compact' => 'true',
                        'verbose' => 'false',
                        'client' => 'vinfast-bacgiang',
                    ]);
            } catch (ConnectionException) {
                throw new RuntimeException('Không kết nối được Open Charge Map.');
            }

            if ($response->failed() || ! is_array($response->json()) || ! array_is_list($response->json())) {
                throw new RuntimeException('Open Charge Map tạm thời không phản hồi.');
            }

            return $response->json();
        });

        return collect($raw)
            ->map(fn (mixed $station) => is_array($station) ? $this->normalizeOpenChargeMap($station, $lat, $lng) : null)
            ->filter()
            ->sortBy('distance')
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function nearbyFromOpenStreetMap(float $lat, float $lng): array
    {
        $distance = max(1, min(100, (int) config('services.overpass.distance_km', 80)));
        $limit = max(1, min(50, (int) config('services.overpass.max_results', 12)));
        $cacheKey = sprintf('charging-stations:osm:v1:%.2f:%.2f:%d:%d', $lat, $lng, $distance, $limit);

        $raw = Cache::remember($cacheKey, (int) config('services.overpass.cache_seconds', 21600), function () use ($lat, $lng, $distance): array {
            $radius = $distance * 1000;
            $query = sprintf(
                '[out:json][timeout:%d];nwr(around:%d,%.6F,%.6F)["amenity"="charging_station"];out center tags;',
                max(5, min(60, (int) config('services.overpass.query_timeout', 20))),
                $radius,
                $lat,
                $lng,
            );

            try {
                $response = Http::asForm()
                    ->acceptJson()
                    ->withHeaders([
                        'User-Agent' => (string) config('services.nominatim.user_agent'),
                        'Referer' => (string) config('app.url'),
                    ])
                    ->connectTimeout(5)
                    ->timeout((int) config('services.overpass.timeout', 25))
                    ->post((string) config('services.overpass.url'), ['data' => $query]);
            } catch (ConnectionException) {
                throw new RuntimeException('Không kết nối được OpenStreetMap.');
            }

            $elements = data_get($response->json(), 'elements');

            if ($response->failed() || ! is_array($elements) || ! array_is_list($elements)) {
                throw new RuntimeException('OpenStreetMap tạm thời không phản hồi.');
            }

            return $elements;
        });

        return collect($raw)
            ->map(fn (mixed $station) => is_array($station) ? $this->normalizeOpenStreetMap($station, $lat, $lng) : null)
            ->filter()
            ->sortBy('distance')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $station
     * @return array<string, mixed>|null
     */
    private function normalizeOpenChargeMap(array $station, float $originLat, float $originLng): ?array
    {
        $lat = $this->number(data_get($station, 'AddressInfo.Latitude'));
        $lng = $this->number(data_get($station, 'AddressInfo.Longitude'));

        if ($lat === null || $lng === null) {
            return null;
        }

        $name = Str::squish((string) (
            data_get($station, 'AddressInfo.Title')
            ?: data_get($station, 'OperatorInfo.Title')
            ?: 'Trạm sạc'
        ));
        $isOperational = data_get($station, 'StatusType.IsOperational');
        $status = $isOperational === true ? 'Hoạt động' : ($isOperational === false ? 'Tạm ngừng' : 'Thông tin tham khảo');

        return [
            'id' => data_get($station, 'ID'),
            'name' => $name,
            'status' => $status,
            'tone' => $isOperational === false ? 'warn' : 'ok',
            'info' => $this->openChargeMapInfo($station),
            'address' => $this->openChargeMapAddress($station),
            'lat' => $lat,
            'lng' => $lng,
            'distance' => round($this->distance($originLat, $originLng, $lat, $lng), 1),
            'source_url' => filled(data_get($station, 'ID'))
                ? 'https://openchargemap.io/site/poi/details/'.data_get($station, 'ID')
                : null,
        ];
    }

    /** @param array<string, mixed> $station
     * @return array<string, mixed>|null
     */
    private function normalizeOpenStreetMap(array $station, float $originLat, float $originLng): ?array
    {
        $lat = $this->number(data_get($station, 'lat') ?? data_get($station, 'center.lat'));
        $lng = $this->number(data_get($station, 'lon') ?? data_get($station, 'center.lon'));
        $tags = data_get($station, 'tags', []);

        if ($lat === null || $lng === null || ! is_array($tags)) {
            return null;
        }

        $name = Str::squish((string) ($tags['name'] ?? $tags['brand'] ?? $tags['operator'] ?? 'Trạm sạc xe điện'));
        [$status, $tone] = $this->openStreetMapStatus($tags);
        $type = in_array(data_get($station, 'type'), ['node', 'way', 'relation'], true)
            ? data_get($station, 'type')
            : null;
        $id = data_get($station, 'id');

        return [
            'id' => $id,
            'name' => $name,
            'status' => $status,
            'tone' => $tone,
            'info' => $this->openStreetMapInfo($tags),
            'address' => $this->openStreetMapAddress($tags),
            'lat' => $lat,
            'lng' => $lng,
            'distance' => round($this->distance($originLat, $originLng, $lat, $lng), 1),
            'source_url' => $type && filled($id) ? "https://www.openstreetmap.org/{$type}/{$id}" : null,
        ];
    }

    /** @param array<string, mixed> $station */
    private function openChargeMapInfo(array $station): string
    {
        $parts = [];
        $points = (int) data_get($station, 'NumberOfPoints', 0);
        $connections = collect(data_get($station, 'Connections', []))->filter(fn ($item) => is_array($item));

        if ($points > 0) {
            $parts[] = $points.' điểm sạc';
        }

        $maxPower = $connections
            ->map(fn (array $connection) => $this->number(data_get($connection, 'PowerKW')))
            ->filter(fn ($power) => $power !== null && $power > 0)
            ->max();

        if ($maxPower) {
            $parts[] = 'tối đa '.$this->formatNumber($maxPower).' kW';
        }

        $types = $connections
            ->pluck('ConnectionType.Title')
            ->filter()
            ->map(fn ($type) => Str::squish((string) $type))
            ->unique()
            ->take(2)
            ->implode(', ');

        if ($types !== '') {
            $parts[] = $types;
        }

        return implode(' · ', $parts);
    }

    /** @param array<string, mixed> $station */
    private function openChargeMapAddress(array $station): string
    {
        return collect([
            data_get($station, 'AddressInfo.AddressLine1'),
            data_get($station, 'AddressInfo.Town'),
            data_get($station, 'AddressInfo.StateOrProvince'),
        ])->filter()->map(fn ($part) => Str::squish((string) $part))->unique()->implode(', ');
    }

    /** @param array<string, mixed> $tags */
    private function openStreetMapInfo(array $tags): string
    {
        $parts = [];
        $capacity = $this->positiveInteger($tags['capacity'] ?? null);

        if ($capacity !== null) {
            $parts[] = $capacity.' điểm sạc';
        }

        $maxPower = collect($tags)
            ->filter(fn ($value, $key) => str_contains((string) $key, 'output'))
            ->flatMap(fn ($value) => $this->powerValues((string) $value))
            ->max();

        if ($maxPower) {
            $parts[] = 'tối đa '.$this->formatNumber((float) $maxPower).' kW';
        }

        $connectors = collect($tags)
            ->filter(fn ($value, $key) => preg_match('/^socket:[^:]+$/', (string) $key) === 1
                && ! in_array(Str::lower(trim((string) $value)), ['', '0', 'no'], true))
            ->keys()
            ->map(fn ($key) => $this->connectorName(Str::after((string) $key, 'socket:')))
            ->filter()
            ->unique()
            ->take(3)
            ->implode(', ');

        if ($connectors !== '') {
            $parts[] = $connectors;
        }

        if (($tags['opening_hours'] ?? null) === '24/7') {
            $parts[] = 'Mở 24/7';
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Dữ liệu cộng đồng OpenStreetMap';
    }

    /** @param array<string, mixed> $tags */
    private function openStreetMapAddress(array $tags): string
    {
        $street = Str::squish(trim(implode(' ', array_filter([
            $tags['addr:housenumber'] ?? null,
            $tags['addr:street'] ?? null,
        ]))));

        return collect([
            $tags['addr:full'] ?? null,
            $street,
            $tags['addr:suburb'] ?? $tags['addr:ward'] ?? null,
            $tags['addr:district'] ?? null,
            $tags['addr:city'] ?? $tags['addr:town'] ?? null,
            $tags['addr:province'] ?? $tags['addr:state'] ?? null,
        ])->filter()->map(fn ($part) => Str::squish((string) $part))->unique()->implode(', ');
    }

    /** @param array<string, mixed> $tags
     * @return array{0:string,1:string}
     */
    private function openStreetMapStatus(array $tags): array
    {
        $operational = Str::lower((string) ($tags['operational_status'] ?? ''));
        $access = Str::lower((string) ($tags['access'] ?? ''));

        if (in_array($operational, ['broken', 'closed', 'out_of_order', 'temporarily_closed'], true)) {
            return ['Tạm ngừng', 'warn'];
        }

        if (in_array($access, ['no', 'private'], true)) {
            return ['Hạn chế truy cập', 'warn'];
        }

        if ($operational === 'operational') {
            return ['Hoạt động', 'ok'];
        }

        return ['Thông tin tham khảo', 'ok'];
    }

    /** @return array<int, float> */
    private function powerValues(string $value): array
    {
        preg_match_all('/(\d+(?:[.,]\d+)?)\s*(kW|W)?/i', $value, $matches, PREG_SET_ORDER);

        return collect($matches)->map(function (array $match): float {
            $number = (float) str_replace(',', '.', $match[1]);

            return Str::lower($match[2] ?? '') === 'w' ? $number / 1000 : $number;
        })->filter(fn ($power) => $power > 0)->values()->all();
    }

    private function connectorName(string $tag): string
    {
        return [
            'type2_combo' => 'CCS2',
            'type2' => 'Type 2',
            'chademo' => 'CHAdeMO',
            'schuko' => 'Schuko',
            'tesla_supercharger' => 'Tesla Supercharger',
            'nacs' => 'NACS',
            'gb/t_20234.2' => 'GB/T AC',
            'gb/t_20234.3' => 'GB/T DC',
        ][$tag] ?? Str::headline(str_replace('_', ' ', $tag));
    }

    private function positiveInteger(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $value = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 2 * $radius * asin(min(1, sqrt($value)));
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /** @return array{lat:float,lng:float}|null */
    private function validCoordinates(mixed $latitude, mixed $longitude): ?array
    {
        $lat = $this->number($latitude);
        $lng = $this->number($longitude);

        return $lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
            ? ['lat' => $lat, 'lng' => $lng]
            : null;
    }

    private function formatNumber(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', '.')
            : rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }
}

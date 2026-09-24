<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Tra IP → "Thành phố, Tỉnh, VN" cho một lead, ghi vào cột location.
 *
 * Dùng ip-api.com bản miễn phí: không cần key, 45 request/phút — form lead
 * không bao giờ tới mức đó. Chạy nền để khách không phải chờ; API sập thì
 * lead vẫn nguyên, chỉ thiếu khu vực.
 */
class ResolveLeadLocation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Model $lead) {}

    public function handle(): void
    {
        $ip = $this->lead->ip;

        // IP nội bộ / loopback (chạy local, cùng mạng LAN) thì API không tra
        // được, khỏi gọi cho tốn quota.
        if (blank($ip) || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return;
        }

        $response = Http::timeout(5)
            ->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,countryCode,regionName,city', 'lang' => 'en']);

        if (! $response->ok() || $response->json('status') !== 'success') {
            return;
        }

        $location = collect([$response->json('city'), $response->json('regionName'), $response->json('countryCode')])
            ->filter()
            ->unique()
            ->implode(', ');

        $this->lead->forceFill(['location' => $location ?: null])->save();
    }
}

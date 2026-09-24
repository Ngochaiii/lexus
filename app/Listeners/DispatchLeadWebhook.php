<?php

namespace App\Listeners;

use App\Events\LeadReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * POST dữ liệu lead sang webhook_url của form (Google Sheet, Make, CRM...).
 * Bỏ trống thì không bắn. ShouldQueue để không chặn response và tự retry.
 */
class DispatchLeadWebhook implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function handle(LeadReceived $event): void
    {
        $lead = $event->lead;
        $url = $lead->form?->webhook_url;

        if (blank($url)) {
            return;
        }

        $response = Http::timeout(10)->acceptJson()->post($url, [
            'id'         => $lead->id,
            'form'       => $lead->form?->key,
            'name'       => $lead->name,
            'phone'      => $lead->phone,
            'email'      => $lead->email,
            'product_id' => $lead->product_id,
            'data'       => $lead->data,
            'utm'        => $lead->utm,
            'created_at' => $lead->created_at?->toIso8601String(),
        ]);

        // Ném để queue retry theo backoff nếu đích trả lỗi.
        if ($response->failed()) {
            Log::warning('Lead webhook thất bại', ['lead' => $lead->id, 'status' => $response->status()]);
            $response->throw();
        }
    }
}

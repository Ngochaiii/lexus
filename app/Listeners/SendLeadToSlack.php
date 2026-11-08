<?php

namespace App\Listeners;

use App\Events\LeadReceived;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Báo khách mới vào kênh Slack (Incoming Webhook, LEAD_SLACK_WEBHOOK) để
 * chị Hà gọi lại ngay. Bỏ trống thì không gửi. ShouldQueue để không chặn
 * khách và tự thử lại khi Slack lỗi.
 */
class SendLeadToSlack implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    /** Ô đã hiện riêng ở đầu tin, không lặp lại trong phần "khách ghi". */
    private const SHOWN = ['name', 'phone', 'email', 'variant', 'attribution', 'product_id', 'variant_id'];

    public function handle(LeadReceived $event): void
    {
        $url = config('catalog.leads.slack_webhook');

        if (blank($url)) {
            return;
        }

        $response = Http::timeout(10)->post($url, ['text' => $this->message($event->lead)]);

        if ($response->failed()) {
            Log::warning('Báo lead sang Slack thất bại', ['lead' => $event->lead->id, 'status' => $response->status()]);
            $response->throw();
        }
    }

    public function message($lead): string
    {
        $lead->loadMissing(['form', 'product']);
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_NOQUOTES, 'UTF-8');

        $car = trim(($lead->product?->name ?? '').' '.(($lead->data['variant'] ?? null) ? '· '.$lead->data['variant'] : ''));

        // Nghi spam (StoreLead::suspicion): vẫn báo để chị Hà tự xem, nhưng không
        // nhắc cả kênh cho điện thoại kêu.
        $head = $lead->status === 'spam'
            ? ':warning: *Nghi spam* (đã để vào mục Spam) — '.$e($lead->form?->name ?? 'Form')
            // <!channel>: thành viên nhận thông báo điện thoại cả khi kênh để
            // mặc định (chỉ báo khi được nhắc tên).
            : '<!channel> :bell: *Khách mới để lại số* — '.$e($lead->form?->name ?? 'Form');

        $lines = [
            $head,
            '*Tên:* '.$e($lead->name ?: '(không ghi)'),
            '*Điện thoại:* '.$e($lead->phone ?: '(không ghi)'),
        ];
        if ($lead->email) {
            $lines[] = '*Email:* '.$e($lead->email);
        }
        if ($car !== '') {
            $lines[] = '*Xe quan tâm:* '.$e($car);
        }

        $labels = $lead->form?->fields?->pluck('label', 'key') ?? collect();
        foreach ((array) $lead->data as $key => $value) {
            if (in_array($key, self::SHOWN, true) || blank($value) || ! is_scalar($value)) {
                continue;
            }
            $lines[] = '*'.$e($labels[$key] ?? $key).':* '.$e($value);
        }

        if ($lead->status === 'spam' && $lead->note) {
            $lines[] = '*Lý do:* '.$e($lead->note);
        }
        $lines[] = '*Nguồn:* '.$e($this->source($lead));
        if ($lead->landing_page) {
            $lines[] = '*Trang vào:* '.$e($lead->landing_page);
        }
        $lines[] = '*Lúc:* '.$lead->created_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        $lines[] = '<'.url('/admin/leads').'|Xem trong Admin>';

        return implode("\n", $lines);
    }

    private function source($lead): string
    {
        if ($lead->medium === 'cpc' && $lead->source === 'google') {
            return 'Quảng cáo Google';
        }

        $label = Lead::sourceLabel($lead->source);

        return match ($lead->medium) {
            'organic' => $label.' · tìm kiếm tự nhiên',
            'cpc'     => $label.' · quảng cáo',
            default   => $label,
        };
    }
}

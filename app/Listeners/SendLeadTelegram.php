<?php

namespace App\Listeners;

use App\Events\LeadReceived;
use App\Models\Lead;
use App\Support\Phone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Báo lead mới về Telegram của chuyên viên — điện thoại rung ngay, gọi lại
 * trong 5 phút (mail có thể chậm vài phút hoặc bị tắt thông báo).
 *
 * Cấu hình trong .env: TELEGRAM_BOT_TOKEN (bot tạo bằng @BotFather) và
 * TELEGRAM_CHAT_ID (một hoặc nhiều, cách nhau dấu phẩy). Lấy chat id: nhắn
 * cho bot một câu rồi chạy `php artisan lead:telegram-test`.
 * Bỏ trống thì không gửi. Chỉ lead MỚI (lead trùng không bắn LeadReceived).
 */
class SendLeadTelegram implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function handle(LeadReceived $event): void
    {
        $config = (array) config('catalog.leads.telegram', []);
        $token = $config['bot_token'] ?? null;
        $chats = array_filter((array) ($config['chat_ids'] ?? []));

        if (blank($token) || $chats === []) {
            return;
        }

        $text = self::message($event->lead);
        foreach ($chats as $chat) {
            $response = Http::timeout(10)->asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => (string) $chat,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::warning('Báo lead Telegram thất bại', ['lead' => $event->lead->id, 'status' => $response->status(), 'body' => $response->body()]);
                $response->throw();
            }
        }
    }

    public static function message(Lead $lead): string
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES);
        $lead->loadMissing('form', 'product', 'variant');

        $car = trim(($lead->product?->name ?? '').' '.($lead->variant?->name ?? ''));
        $source = Lead::sourceLabel($lead->source).($lead->medium === 'cpc' ? ' Ads' : '');
        $keyword = $lead->utm['utm_term'] ?? null;

        return implode("\n", array_filter([
            '🔔 <b>Lead mới</b> · '.$e($lead->form?->name ?? 'Form'),
            '👤 '.$e($lead->name ?: '—'),
            '📞 '.$e(Phone::format($lead->phone) ?: $lead->phone),
            $car !== '' ? '🚗 '.$e($car) : null,
            '📍 Nguồn: '.$e($source).($keyword ? ' · từ khoá "'.$e($keyword).'"' : ''),
            '⏱ Gọi lại trong 5 phút',
            url('/admin/leads'),
        ]));
    }
}

<?php

namespace App\Listeners;

use App\Events\LeadReceived;
use App\Mail\LeadNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Gửi mail báo khách mới tới LEAD_NOTIFY_EMAILS (mọi form) cộng notify_emails
 * riêng của form. Không có địa chỉ nào thì không gửi. ShouldQueue: không bắt
 * khách chờ SMTP, chạy trong queue worker và tự thử lại khi SMTP lỗi.
 */
class SendLeadNotification implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function handle(LeadReceived $event): void
    {
        $lead = $event->lead;

        $emails = collect((array) config('catalog.leads.notify_emails', []))
            ->merge($lead->form?->notify_emails ?? [])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return;
        }

        Mail::to($emails)->send(new LeadNotification($lead));
    }
}

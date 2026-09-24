<?php

namespace App\Listeners;

use App\Events\LeadReceived;
use App\Mail\LeadNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Gửi mail cho các địa chỉ trong notify_emails của form. Bỏ trống thì không gửi.
 * ShouldQueue: không bắt khách chờ SMTP, chạy trong queue worker.
 */
class SendLeadNotification implements ShouldQueue
{
    public function handle(LeadReceived $event): void
    {
        $lead = $event->lead;
        $emails = $lead->form?->notify_emails ?? [];

        if (blank($emails)) {
            return;
        }

        Mail::to($emails)->send(new LeadNotification($lead));
    }
}

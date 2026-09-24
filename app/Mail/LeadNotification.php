<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email báo có khách để lại thông tin, gửi tới notify_emails của form.
 */
class LeadNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Model $lead) {}

    public function envelope(): Envelope
    {
        $name = $this->lead->name ?: 'Khách';
        $form = $this->lead->form?->name ?? 'Form';

        return new Envelope(subject: "[{$form}] Liên hệ mới từ {$name}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.lead',
            with: ['lead' => $this->lead->loadMissing(['form', 'product'])],
        );
    }
}

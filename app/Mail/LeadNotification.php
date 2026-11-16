<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email báo có khách để lại thông tin (SendLeadNotification). Tiêu đề ghi tên
 * web + tên, số khách để nhìn hộp thư là biết gọi ai, từ web nào.
 */
class LeadNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Model $lead) {}

    public function envelope(): Envelope
    {
        $site = config('catalog.leads.site_label');
        $name = $this->lead->name ?: 'Khách';
        $phone = $this->lead->phone ? ' · '.$this->lead->phone : '';
        $form = $this->lead->form?->name ?? 'Form';
        $what = $this->lead->status === 'spam' ? 'Nghi spam' : 'Khách mới';

        // Web ghi đầu tiêu đề: nhiều web dùng chung một hộp thư.
        return new Envelope(subject: ($site ? "[{$site}] " : '')."{$what}: {$name}{$phone} — {$form}");
    }

    public function content(): Content
    {
        $lead = $this->lead->loadMissing(['form', 'product']);
        $labels = $lead->form?->fields?->pluck('label', 'key') ?? collect();

        // Các ô khách ghi thêm (ghi chú, nơi đăng ký…), trừ ô đã hiện riêng.
        $extra = collect((array) $lead->data)
            ->except(['name', 'phone', 'email', 'variant', 'attribution', 'product_id', 'variant_id'])
            ->filter(fn ($v) => filled($v) && is_scalar($v))
            ->mapWithKeys(fn ($v, $k) => [(string) ($labels[$k] ?? $k) => (string) $v]);

        return new Content(
            markdown: 'mail.lead',
            with: [
                'lead' => $lead,
                'site' => config('catalog.leads.site_label'),
                'extra' => $extra,
                'variant' => $lead->data['variant'] ?? null,
            ],
        );
    }
}

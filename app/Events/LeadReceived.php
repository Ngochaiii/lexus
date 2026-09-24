<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Bắn khi có lead mới. Hai listener nghe: gửi mail cho notify_emails và
 * bắn webhook_url. Tách qua event để thêm kênh khác (CRM, Zalo...) sau này
 * chỉ cần thêm listener, không đụng controller.
 */
class LeadReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Model $lead) {}
}

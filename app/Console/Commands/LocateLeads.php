<?php

namespace App\Console\Commands;

use App\Jobs\ResolveLeadLocation;
use App\Support\Catalog;
use Illuminate\Console\Command;

/**
 * Tra lại tỉnh/thành cho các lead đã có IP nhưng chưa có location — dùng
 * một lần sau khi thêm cột, hoặc khi ip-api.com từng sập một lúc.
 *
 *     php artisan leads:locate
 *
 * Chạy thẳng (không qua queue) để thấy kết quả ngay; nghỉ 1.5s giữa các
 * request cho khỏi chạm trần 45 request/phút của ip-api.com.
 */
class LocateLeads extends Command
{
    protected $signature = 'leads:locate';

    protected $description = 'Tra IP → tỉnh/thành cho các liên hệ chưa có khu vực';

    public function handle(): int
    {
        $leads = Catalog::model('lead')::query()
            ->whereNotNull('ip')
            ->whereNull('location')
            ->orderBy('id')
            ->get();

        $this->info("Cần tra {$leads->count()} liên hệ.");

        foreach ($leads as $i => $lead) {
            (new ResolveLeadLocation($lead))->handle();
            $this->line(sprintf('#%d %s → %s', $lead->id, $lead->ip, $lead->fresh()->location ?? '(không rõ)'));

            if ($i < $leads->count() - 1 && ! app()->runningUnitTests()) {
                usleep(1_500_000);
            }
        }

        return self::SUCCESS;
    }
}

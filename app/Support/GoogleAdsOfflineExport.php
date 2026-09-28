<?php

namespace App\Support;

use App\Models\Lead;
use Illuminate\Support\Collection;

/**
 * File CSV nhập chuyển đổi ngoại tuyến vào Google Ads (Mục tiêu → Lượt chuyển
 * đổi → Tải lên): mỗi dòng là một lượt bấm quảng cáo (gclid) đã thành khách
 * hẹn lái thử trở lên. Google học tìm người giống khách thật, không chỉ người
 * điền form.
 *
 * Tên chuyển đổi phải TRÙNG KHỚP hành động "Nhập → Lượt nhấp" tạo trong Ads:
 * config('catalog.leads.ads_conversion_name').
 *
 * Google chỉ nhận lượt bấm trong 90 ngày. Lead chỉ có gbraid/wbraid (một số
 * lượt iOS) cần mẫu tải lên riêng — chưa xuất ở đây.
 */
class GoogleAdsOfflineExport
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /** @return Collection<int, Lead> */
    public static function leads(): Collection
    {
        return Catalog::query('lead')
            ->whereNotNull('gclid')
            ->whereIn('status', Lead::QUALIFIED)
            ->whereNotNull('qualified_at')
            ->where('created_at', '>=', now()->subDays(90))
            ->orderBy('qualified_at')
            ->get(['id', 'gclid', 'qualified_at']);
    }

    public static function csv(): string
    {
        $name = (string) config('catalog.leads.ads_conversion_name', 'CRM Hen lai thu');

        $lines = [
            'Parameters:TimeZone='.self::TIMEZONE,
            'Google Click ID,Conversion Name,Conversion Time',
        ];
        foreach (self::leads() as $lead) {
            $lines[] = implode(',', [
                $lead->gclid,
                $name,
                $lead->qualified_at->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d H:i:s'),
            ]);
        }

        return implode("\n", $lines)."\n";
    }
}

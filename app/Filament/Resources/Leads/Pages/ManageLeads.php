<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Support\GoogleAdsOfflineExport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        // Không có nút "Tạo": lead đến từ form. Chỉ có file báo lại Google Ads
        // lượt bấm nào đã thành khách hẹn lái thử (nhập chuyển đổi ngoại tuyến).
        return [
            Action::make('googleAdsExport')
                ->label('Xuất cho Google Ads')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Xuất chuyển đổi cho Google Ads')
                ->modalDescription(fn () => sprintf(
                    'Có %d lead từ quảng cáo đã hẹn lái thử trở lên trong 90 ngày. Tải file rồi vào Google Ads → Mục tiêu → Lượt chuyển đổi → Tải lên. Tên chuyển đổi trong Ads phải là “%s”.',
                    GoogleAdsOfflineExport::leads()->count(),
                    config('catalog.leads.ads_conversion_name'),
                ))
                ->modalSubmitActionLabel('Tải file CSV')
                ->action(fn () => response()->streamDownload(
                    function () { echo GoogleAdsOfflineExport::csv(); },
                    'google-ads-chuyen-doi-'.now()->format('Y-m-d').'.csv',
                    ['Content-Type' => 'text/csv; charset=UTF-8'],
                )),
        ];
    }
}

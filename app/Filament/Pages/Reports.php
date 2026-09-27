<?php

namespace App\Filament\Pages;

use App\Support\Insights;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Báo cáo bán hàng & web: tỷ lệ chốt, nguồn khách, phiên bản được hỏi,
 * lượt bấm Gọi/Zalo theo trang, tốc độ thật của khách. Số liệu: App\Support\Insights.
 */
class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.reports';

    /** Khoảng thời gian: 7 / 30 / 90 ngày. */
    public int $days = 30;

    public static function getNavigationLabel(): string
    {
        return 'Báo cáo';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Khách hàng';
    }

    public function getTitle(): string
    {
        return 'Báo cáo bán hàng & website';
    }

    public function setDays(int $days): void
    {
        $this->days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    }

    protected function getViewData(): array
    {
        $insights = new Insights($this->days);

        return [
            'overview' => $insights->overview(),
            'funnel' => $insights->funnel(),
            'sources' => $insights->bySource(),
            'variants' => $insights->byVariant(),
            'lost' => $insights->lostReasons(),
            'clicks' => $insights->contactClicks(),
            'vitals' => $insights->vitals(),
            'slowest' => $insights->slowestPages(),
        ];
    }
}

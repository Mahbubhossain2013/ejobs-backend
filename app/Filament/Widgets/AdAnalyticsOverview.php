<?php

namespace App\Filament\Widgets;

use App\Models\Ad;
use App\Models\AdLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdAnalyticsOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalAds = Ad::count();
        $totalImpressions = Ad::sum('total_impressions');
        $totalClicks = Ad::sum('total_clicks');
        $totalRevenue = AdLog::sum('revenue');

        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0.00;

        return [
            Stat::make('Ad Campaigns', $totalAds)
                ->description('Total created ads')
                ->color('primary')
                ->chart([3, 5, 2, 7, 5, 9, 11]),
            Stat::make('Total Impressions', number_format($totalImpressions))
                ->description('All served banner impressions')
                ->color('success')
                ->chart([50, 100, 300, 500, 900, 1500, 2200]),
            Stat::make('Ad Clicks (CTR %)', number_format($totalClicks) . ' (' . number_format($ctr, 2) . '%)')
                ->description('Overall click-through rate')
                ->color('warning')
                ->chart([5, 12, 10, 25, 18, 30, 45]),
            Stat::make('Ads Revenue (CPC/CPM)', '৳' . number_format($totalRevenue, 2))
                ->description('Platform earnings from campaigns')
                ->color('danger')
                ->chart([100, 200, 150, 450, 300, 600, 950]),
        ];
    }
}

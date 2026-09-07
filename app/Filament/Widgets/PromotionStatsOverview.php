<?php

namespace App\Filament\Widgets;

use App\Models\Promotion;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PromotionStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCampaigns = Promotion::where('status', 'active')->count();
        
        // Sum deductions from promotion_ledgers today
        $dailyRevenue = DB::table('promotion_ledgers')
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount_deducted');

        $totalImpressions = Promotion::sum('impressions');
        $totalClicks = Promotion::sum('clicks');
        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0.00;

        $suspendedCount = Promotion::where('status', 'suspended')
            ->orWhere('is_suspended', true)
            ->count();

        $aiFlaggedCount = Promotion::whereIn('moderation_status', ['high_risk', 'uncertain'])->count();

        return [
            Stat::make('Active Campaigns', $activeCampaigns)
                ->description('Running promotions')
                ->color('primary')
                ->chart([2, 4, 3, 5, 4, 7, $activeCampaigns]),

            Stat::make('Daily Revenue', '৳' . number_format($dailyRevenue, 2))
                ->description('Earned from promotions today')
                ->color('success')
                ->chart([150, 200, 180, 250, 310, 400, $dailyRevenue > 0 ? $dailyRevenue : 50]),

            Stat::make('Total Traffic (CTR %)', number_format($totalImpressions) . ' Imps (' . number_format($ctr, 2) . '%)')
                ->description(number_format($totalClicks) . ' clicks recorded')
                ->color('warning')
                ->chart([10, 20, 15, 30, 25, 45, $totalClicks > 0 ? $totalClicks : 5]),

            Stat::make('Flagged & Suspended', "Suspended: {$suspendedCount} / AI: {$aiFlaggedCount}")
                ->description('Requires security attention')
                ->color('danger')
                ->chart([1, 0, 2, 1, 3, 2, $suspendedCount + $aiFlaggedCount]),
        ];
    }
}

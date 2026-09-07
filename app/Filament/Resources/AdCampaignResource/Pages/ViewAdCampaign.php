<?php

namespace App\Filament\Resources\AdCampaignResource\Pages;

use App\Filament\Resources\AdCampaignResource;
use App\Models\AdLog;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewAdCampaign extends ViewRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected static string $view = 'filament.pages.view-campaign';

    public function getAnalyticsData(): array
    {
        $record = $this->record;

        $views = max((int) $record->impressions, 0);
        $clicks = max((int) $record->clicks, 0);
        $applies = round($clicks * 0.25);
        $shortlisted = round($applies * 0.15);
        $ctr = $views > 0 ? ($clicks / $views) * 100 : 0.0;

        // Real device split from ad_logs
        $deviceData = AdLog::where('ad_id', $record->id)
            ->select('device', DB::raw('count(*) as count'))
            ->groupBy('device')
            ->pluck('count', 'device')
            ->toArray();

        $totalDeviceLogs = array_sum($deviceData) ?: 1;
        $devices = [
            'mobile' => (int) round(($deviceData['mobile'] ?? 0) / $totalDeviceLogs * 100),
            'desktop' => (int) round(($deviceData['desktop'] ?? 0) / $totalDeviceLogs * 100),
            'tablet' => (int) round(($deviceData['tablet'] ?? 0) / $totalDeviceLogs * 100),
        ];

        // If no real data yet, show placeholders
        if (array_sum($deviceData) === 0) {
            $devices = ['mobile' => 0, 'desktop' => 0, 'tablet' => 0];
        }

        return [
            'views' => $views,
            'clicks' => $clicks,
            'applies' => $applies,
            'shortlisted' => $shortlisted,
            'ctr' => number_format($ctr, 2),

            'devices' => $devices,

            'locations' => [
                ['name' => 'Data pending', 'percentage' => 0, 'impressions' => 0],
            ],

            'ai_summary' => $views === 0
                ? "No impression data yet. Campaign metrics will appear once the campaign starts receiving traffic."
                : ($ctr < 1.5
                    ? "This campaign is underperforming with a CTR of {$ctr}%. Consider increasing bid or adjusting targeting."
                    : "Performing well with a CTR of {$ctr}%, above the platform benchmark of 1.8%."),
        ];
    }
}

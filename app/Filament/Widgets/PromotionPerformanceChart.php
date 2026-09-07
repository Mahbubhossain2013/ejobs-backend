<?php

namespace App\Filament\Widgets;

use App\Models\Promotion;
use Filament\Widgets\ChartWidget;

class PromotionPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Campaign Type Distribution';

    protected function getData(): array
    {
        // Query counts of active promotions grouped by campaign_type
        $campaignTypes = Promotion::select('campaign_type', \DB::raw('count(*) as total'))
            ->groupBy('campaign_type')
            ->get();

        $labels = [];
        $data = [];
        $colors = ['#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981'];

        foreach ($campaignTypes as $type) {
            $labels[] = ucwords(str_replace('_', ' ', $type->campaign_type));
            $data[] = $type->total;
        }

        // Empty state - no fake data
        if (empty($data)) {
            $labels = ['No campaigns yet'];
            $data = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Active Campaigns',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

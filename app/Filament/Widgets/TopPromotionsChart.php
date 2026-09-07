<?php

namespace App\Filament\Widgets;

use App\Models\Promotion;
use Filament\Widgets\ChartWidget;

class TopPromotionsChart extends ChartWidget
{
    protected static ?string $heading = 'Top Performing Campaigns';

    protected function getData(): array
    {
        // Query top 5 promotions by impressions/clicks
        $topPromos = Promotion::orderByDesc('impressions')
            ->limit(5)
            ->get();

        $labels = [];
        $clicks = [];
        $imps = [];

        foreach ($topPromos as $promo) {
            $labels[] = strlen($promo->title) > 20 ? substr($promo->title, 0, 17) . '...' : $promo->title;
            $clicks[] = $promo->clicks;
            $imps[] = $promo->impressions;
        }

        // Empty state - no fake data
        if (empty($imps)) {
            $labels = ['No campaigns yet'];
            $imps = [0];
            $clicks = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Impressions',
                    'data' => $imps,
                    'backgroundColor' => '#3B82F6',
                ],
                [
                    'label' => 'Clicks',
                    'data' => $clicks,
                    'backgroundColor' => '#F59E0B',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

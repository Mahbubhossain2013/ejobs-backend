<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Ad Revenue Trend (Last 7 Days)';
    protected static string $color = 'success';

    protected function getData(): array
    {
        $days = [];
        $revenue = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('M d');

            // Sum deductions for this date
            $sum = DB::table('promotion_ledgers')
                ->whereDate('created_at', $date->toDateString())
                ->sum('amount_deducted');
                
            $revenue[] = floatval($sum);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (৳)',
                    'data' => $revenue,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => 'start',
                ],
            ],
            'labels' => $days,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

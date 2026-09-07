<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class InvoiceRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Revenue Breakdown';
    protected static ?int    $sort    = 2;
    protected array|string|int $columnSpan = 'full';

    public ?string $filter = 'revenue';

    protected function getFilters(): ?array
    {
        return [
            'revenue'      => 'Total Revenue',
            'subscription' => 'Subscription Revenue',
            'job_boost'    => 'Job Boost Revenue',
            'milestone'    => 'Milestone Payments',
            'escrow'       => 'Escrow Activity',
        ];
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));
        $labels = $months->map(fn($m) => $m->format('M Y'))->toArray();

        $query = Invoice::paid();

        if ($this->filter !== 'revenue') {
            $type = match($this->filter) {
                'escrow'       => ['escrow_funding', 'escrow_release'],
                default        => [$this->filter],
            };
            $query->whereIn('type', $type);
        }

        $data = $months->map(function (Carbon $month) use ($query) {
            return (clone $query)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('total_amount');
        })->toArray();

        $platformFees = $months->map(function (Carbon $month) {
            return Invoice::thisMonth()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('platform_fee');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue ($)',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(26, 86, 219, 0.15)',
                    'borderColor'     => '#1a56db',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Platform Fees ($)',
                    'data'            => $platformFees,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.15)',
                    'borderColor'     => '#f97316',
                    'borderWidth'     => 2,
                    'fill'            => false,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

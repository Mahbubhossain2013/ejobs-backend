<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatusOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $paidMonth    = Invoice::paid()->thisMonth()->sum('total_amount');
        $pendingCount = Invoice::pending()->count();
        $overdueCount = Invoice::overdue()->count();
        $refundMonth  = Invoice::byStatus('refunded')->thisMonth()->sum('total_amount');

        $paidLastMonth = Invoice::paid()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');

        $trend = $paidLastMonth > 0
            ? round((($paidMonth - $paidLastMonth) / $paidLastMonth) * 100, 1)
            : 0;

        $trendColor = $trend >= 0 ? 'success' : 'danger';
        $trendIcon  = $trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return [
            Stat::make('Revenue This Month', '$' . number_format($paidMonth, 2))
                ->description(($trend >= 0 ? '↑' : '↓') . abs($trend) . '% vs last month')
                ->descriptionIcon($trendIcon)
                ->color($trendColor)
                ->chart([
                    Invoice::paid()->whereDate('created_at', now()->subDays(6))->sum('total_amount'),
                    Invoice::paid()->whereDate('created_at', now()->subDays(5))->sum('total_amount'),
                    Invoice::paid()->whereDate('created_at', now()->subDays(4))->sum('total_amount'),
                    Invoice::paid()->whereDate('created_at', now()->subDays(3))->sum('total_amount'),
                    Invoice::paid()->whereDate('created_at', now()->subDays(2))->sum('total_amount'),
                    Invoice::paid()->whereDate('created_at', now()->subDays(1))->sum('total_amount'),
                    Invoice::paid()->whereDate('created_at', now())->sum('total_amount'),
                ]),

            Stat::make('Pending Invoices', $pendingCount)
                ->description('$' . number_format(Invoice::pending()->sum('total_amount'), 2) . ' outstanding')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Overdue Invoices', $overdueCount)
                ->description('$' . number_format(Invoice::overdue()->sum('total_amount'), 2) . ' overdue')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Refunds This Month', '$' . number_format($refundMonth, 2))
                ->description(Invoice::byStatus('refunded')->thisMonth()->count() . ' refund invoices')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('gray'),

            Stat::make('All Time Revenue', '$' . number_format(Invoice::paid()->sum('total_amount'), 2))
                ->description(Invoice::paid()->count() . ' total paid invoices')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Platform Fees (Month)', '$' . number_format(Invoice::thisMonth()->sum('platform_fee'), 2))
                ->description('Fees collected this month')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),
        ];
    }
}

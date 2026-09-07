<?php

namespace App\Filament\Widgets;

use App\Models\MediaOptimization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImageAnalyticsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOptimized = MediaOptimization::count();
        $totalSavedBytes = MediaOptimization::sum('saved_bytes');
        $totalOriginalBytes = MediaOptimization::sum('original_size');

        $savedMb = round($totalSavedBytes / (1024 * 1024), 2);
        $efficiency = $totalOriginalBytes > 0 
            ? round(($totalSavedBytes / $totalOriginalBytes) * 100, 1) 
            : 0;

        return [
            Stat::make('Total Optimized Images', $totalOptimized)
                ->description('Images processed globally')
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),

            Stat::make('Cumulative Storage Saved', "{$savedMb} MB")
                ->description('Disk space recovered')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('primary'),

            Stat::make('Avg Compression Efficiency', "{$efficiency}%")
                ->description('File size reduction ratio')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('info'),

            Stat::make('Optimized Formats Active', MediaOptimization::where('status', 'success')->distinct('format')->count())
                ->description('WebP and SVG files')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('warning'),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\PromotionStatsOverview;
use App\Filament\Widgets\PromotionRevenueChart;
use App\Filament\Widgets\PromotionPerformanceChart;
use App\Filament\Widgets\TopPromotionsChart;

class AdsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Ads & Promotion Control Center';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Ads & Promotion Control Center';
    
    protected static string $view = 'filament.pages.ads-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            PromotionStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PromotionRevenueChart::class,
            PromotionPerformanceChart::class,
            TopPromotionsChart::class,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_ads_dashboard');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_ads_dashboard');
    }
}

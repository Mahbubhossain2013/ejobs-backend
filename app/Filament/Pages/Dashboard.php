<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\StatsOverview;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\RecentApplications;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            StatsOverview::class,
            RecentApplications::class,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_dashboard');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_dashboard');
    }
}

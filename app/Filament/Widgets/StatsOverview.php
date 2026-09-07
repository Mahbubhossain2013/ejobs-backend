<?php

namespace App\Filament\Widgets;

use App\Models\Job;
use App\Models\User;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return[
            Stat::make('Total Jobs', Job::count())
                ->description('Active job listings')
                ->color('success'),
            Stat::make('Total Employers', User::role('employer')->count())
                ->description('Registered companies')
                ->color('primary'),
            Stat::make('Total Candidates', User::role('candidate')->count())
                ->description('Registered job seekers')
                ->color('info'),
        ];
    }
}
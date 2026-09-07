<?php

namespace App\Filament\Widgets;

use App\Models\Resume;
use App\Models\CvTemplate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CvBuilderStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Fetch CV metrics safely
        $totalResumes = Resume::count();
        $activeTemplates = CvTemplate::where('is_active', true)->count();
        $sharedResumes = Resume::where('is_public', true)->count();
        $totalViews = Resume::sum('views_count');

        return [
            Stat::make('Total CV Resumes Built', $totalResumes)
                ->description('Candidates generated resumes')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Active CV Templates', $activeTemplates)
                ->description('Pre-configured styles')
                ->descriptionIcon('heroicon-m-rectangle-group')
                ->color('primary'),

            Stat::make('Shared Public CV Links', $sharedResumes)
                ->description('Active published live links')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),

            Stat::make('CV Views & Impressions', $totalViews)
                ->description('Recruiter engagement count')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}

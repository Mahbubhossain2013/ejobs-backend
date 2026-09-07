<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class UserTypeChart extends ChartWidget
{
    protected static ?string $heading = 'User Distribution';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $candidates = User::role('candidate')->count();
        $employers = User::role('employer')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => [$candidates, $employers],
                    'backgroundColor' => ['#059669', '#1C2541'],
                ],
            ],
            'labels' => ['Candidates', 'Employers'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
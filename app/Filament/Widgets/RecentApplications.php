<?php

namespace App\Filament\Widgets;

use App\Models\JobApplication;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentApplications extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\JobApplication::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Candidate'),
                Tables\Columns\TextColumn::make('job.title')->label('Job Applied'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ]);
    }
}
<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Reporter Information')->schema([
                Infolists\Components\TextEntry::make('reporter.name')->label('Reporter Name'),
                Infolists\Components\TextEntry::make('reporter.email')->label('Reporter Email'),
                Infolists\Components\TextEntry::make('created_at')->label('Reported At')->dateTime('M d, Y H:i:s'),
            ])->columns(3),

            Infolists\Components\Section::make('Report Details')->schema([
                Infolists\Components\TextEntry::make('reportable_type')
                    ->label('Reported Item Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Infolists\Components\TextEntry::make('reportable_id')->label('Reported Item ID'),
                Infolists\Components\TextEntry::make('reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'spam' => 'warning',
                        'scam' => 'danger',
                        'inappropriate' => 'danger',
                        'fake' => 'danger',
                        'duplicate' => 'info',
                        'other' => 'gray',
                    }),
                Infolists\Components\TextEntry::make('description')->label('Description')->columnSpanFull(),
            ])->columns(3),

            Infolists\Components\Section::make('Resolution')->schema([
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                    }),
                Infolists\Components\TextEntry::make('action_taken')
                    ->label('Action Taken')
                    ->badge()
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('admin_notes')->label('Admin Notes')->columnSpanFull(),
                Infolists\Components\TextEntry::make('resolved_at')->label('Resolved At')->dateTime('M d, Y H:i:s')->placeholder('—'),
            ])->columns(3),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

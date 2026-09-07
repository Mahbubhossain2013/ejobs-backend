<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Report';

    protected static ?string $pluralModelLabel = 'Reports';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('reporter');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Report Details')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ])
                    ->required(),
                Forms\Components\Select::make('action_taken')
                    ->label('Action Taken')
                    ->options([
                        'warning' => 'Warning Sent',
                        'job_removed' => 'Job Removed',
                        'user_banned' => 'User Banned',
                        'no_action' => 'No Action Needed',
                    ]),
                Forms\Components\Textarea::make('admin_notes')
                    ->label('Admin Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->color(fn (string $state): string => match (class_basename($state)) {
                        'Job' => 'warning',
                        'Company' => 'info',
                        'User' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'spam' => 'warning',
                        'scam' => 'danger',
                        'inappropriate' => 'danger',
                        'fake' => 'danger',
                        'duplicate' => 'info',
                        'other' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('action_taken')
                    ->label('Action')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state): string => match ($state) {
                        'warning' => 'warning',
                        'job_removed' => 'danger',
                        'user_banned' => 'danger',
                        'no_action' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reported')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),

                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'spam' => 'Spam',
                        'scam' => 'Scam',
                        'inappropriate' => 'Inappropriate',
                        'fake' => 'Fake',
                        'duplicate' => 'Duplicate',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('reportable_type')
                    ->label('Reportable Type')
                    ->options([
                        'App\\Models\\Job' => 'Job',
                        'App\\Models\\Company' => 'Company',
                        'App\\Models\\User' => 'User',
                    ]),

                Tables\Filters\Filter::make('pending')
                    ->query(fn (Builder $query) => $query->where('status', 'pending'))
                    ->label('Pending Only')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Quick action: Mark as Resolved
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Resolve Report')
                    ->modalDescription('Mark this report as resolved with no further action.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Resolution Notes')
                            ->rows(2),
                    ])
                    ->action(function (Report $record, array $data): void {
                        $record->update([
                            'status' => 'resolved',
                            'action_taken' => 'no_action',
                            'admin_notes' => $data['admin_notes'] ?? null,
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending'),

                // Quick action: Dismiss
                Tables\Actions\Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Report $record): void {
                        $record->update([
                            'status' => 'dismissed',
                            'action_taken' => 'no_action',
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending'),

                // Quick action: Remove Job
                Tables\Actions\Action::make('remove_job')
                    ->label('Remove Job')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Reported Job')
                    ->modalDescription('This will deactivate the reported job. The employer will be notified.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Reason for Removal')
                            ->rows(2)
                            ->required(),
                    ])
                    ->action(function (Report $record, array $data): void {
                        // Deactivate the job
                        if ($record->reportable_type === \App\Models\Job::class) {
                            \App\Models\Job::where('id', $record->reportable_id)->update(['is_active' => false]);
                        }
                        $record->update([
                            'status' => 'resolved',
                            'action_taken' => 'job_removed',
                            'admin_notes' => $data['admin_notes'],
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending' && $record->reportable_type === \App\Models\Job::class),

                // Quick action: Ban User
                Tables\Actions\Action::make('ban_user')
                    ->label('Ban User')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ban Report Target')
                    ->modalDescription('This will ban the user who posted the reported content.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Ban Reason')
                            ->rows(2)
                            ->required(),
                    ])
                    ->action(function (Report $record, array $data): void {
                        if ($record->reportable_type === \App\Models\User::class) {
                            $user = \App\Models\User::find($record->reportable_id);
                            if ($user) {
                                if ($user->profile) {
                                    $user->profile->update([
                                        'ban_status' => true,
                                        'restriction_status' => 'suspended',
                                        'moderation_notes' => $data['admin_notes'],
                                    ]);
                                }
                                if ($user->company) {
                                    $user->company->update([
                                        'ban_status' => true,
                                        'restriction_status' => 'suspended',
                                        'moderation_notes' => $data['admin_notes'],
                                    ]);
                                }
                            }
                        }
                        $record->update([
                            'status' => 'resolved',
                            'action_taken' => 'user_banned',
                            'admin_notes' => $data['admin_notes'],
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending' && $record->reportable_type === \App\Models\User::class),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_reports');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_reports');
    }
}

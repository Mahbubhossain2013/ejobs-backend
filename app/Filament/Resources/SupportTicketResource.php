<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Trust & Safety';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket Details')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->disabled(fn ($livewire) => !($livewire instanceof Pages\CreateSupportTicket)),

                    Forms\Components\Select::make('category')
                        ->options([
                            'billing' => 'Billing & Invoice',
                            'technical' => 'Technical Support',
                            'account' => 'Account Settings',
                            'ai' => 'AI System Support',
                            'general' => 'General Inquiry',
                        ])
                        ->required()
                        ->default('general'),

                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->required()
                        ->default('medium'),

                    Forms\Components\Select::make('status')
                        ->options([
                            'open' => 'Open',
                            'in_progress' => 'In Progress',
                            'pending' => 'Pending',
                            'answered' => 'Answered',
                            'resolved' => 'Resolved',
                            'closed' => 'Closed',
                        ])
                        ->required()
                        ->default('open')
                        ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin'])),

                    Forms\Components\Select::make('assigned_admin_id')
                        ->label('Assigned Support Agent')
                        ->relationship('assignedAdmin', 'name', fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'admin')))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin'])),

                    Forms\Components\Textarea::make('message')
                        ->label('Initial Message')
                        ->placeholder('Please describe your issue in detail...')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull()
                        ->visible(fn ($livewire) => $livewire instanceof Pages\CreateSupportTicket),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin'])),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->color('gray'),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->color(fn (SupportTicket $record) => $record->priority_color),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->color(fn (SupportTicket $record) => $record->status_color),

                Tables\Columns\TextColumn::make('assignedAdmin.name')
                    ->label('Agent')
                    ->placeholder('Unassigned')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'pending' => 'Pending',
                        'answered' => 'Answered',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'billing' => 'Billing',
                        'technical' => 'Technical Support',
                        'account' => 'Account Settings',
                        'ai' => 'AI System Support',
                        'general' => 'General Inquiry',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin'])),
                    
                    Tables\Actions\BulkAction::make('bulk_status_change')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin']))
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'open' => 'Open',
                                    'in_progress' => 'In Progress',
                                    'pending' => 'Pending',
                                    'answered' => 'Answered',
                                    'resolved' => 'Resolved',
                                    'closed' => 'Closed',
                                ])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $records->each(fn ($record) => $record->update(['status' => $data['status']]));
                        }),

                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Assign Agent')
                        ->icon('heroicon-o-user-plus')
                        ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin']))
                        ->form([
                            Forms\Components\Select::make('assigned_admin_id')
                                ->label('Support Agent')
                                ->options(User::role('admin')->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $records->each(fn ($record) => $record->update(['assigned_admin_id' => $data['assigned_admin_id']]));
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_support_tickets');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_support_tickets');
    }
}

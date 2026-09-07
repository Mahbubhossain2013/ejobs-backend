<?php

namespace App\Filament\Resources\BadgeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Assigned Users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expiration Date')
                    ->helperText('Leave empty for infinite duration.'),
                Forms\Components\TextInput::make('assigned_by')
                    ->default('admin')
                    ->required(),
                Forms\Components\Toggle::make('is_visible')
                    ->label('Visible on Profile')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.assigned_by')
                    ->label('Assigned By')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('pivot.expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?: 'Permanent'),
                Tables\Columns\IconColumn::make('pivot.is_visible')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign Badge to User')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At (Optional)'),
                        Forms\Components\TextInput::make('assigned_by')
                            ->label('Assigned By')
                            ->default('admin')
                            ->required(),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}

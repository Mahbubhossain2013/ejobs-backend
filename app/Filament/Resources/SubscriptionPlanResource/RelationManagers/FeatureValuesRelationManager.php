<?php

namespace App\Filament\Resources\SubscriptionPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\PlanFeature;

class FeatureValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'featureValues';
    protected static ?string $title = 'Plan Feature Configurations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('plan_feature_id')
                    ->label('Feature Key')
                    ->options(PlanFeature::pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                        $state ? $set('value', PlanFeature::find($state)?->type === 'boolean' ? 'false' : '0') : null
                    )
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                Forms\Components\TextInput::make('value')
                    ->label('Configuration Value')
                    ->required()
                    ->helperText(function (Forms\Get $get) {
                        $featureId = $get('plan_feature_id');
                        if (!$featureId) {
                            return 'Select a feature to see helper description.';
                        }
                        $feature = PlanFeature::find($featureId);
                        if ($feature) {
                            return "Type: {$feature->type}. Description: {$feature->description} (e.g. use 'true'/'false' for boolean type).";
                        }
                        return '';
                    })
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                Tables\Columns\TextColumn::make('feature.name')
                    ->label('Feature Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('feature.feature_key')
                    ->label('Key')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Configured Value')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'true' => 'success',
                        'false' => 'danger',
                        default => 'info',
                    })
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('feature.description')
                    ->label('Description')
                    ->wrap()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Configure New Feature')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

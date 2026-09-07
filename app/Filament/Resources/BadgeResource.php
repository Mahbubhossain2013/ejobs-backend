<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BadgeResource\Pages;
use App\Filament\Resources\BadgeResource\RelationManagers\UsersRelationManager;
use App\Models\Badge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Trust & Safety';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Badge Aesthetics')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                $operation === 'create' ? $set('badge_key', \Illuminate\Support\Str::slug($state)) : null
                            )
                            ->maxLength(255),
                        Forms\Components\TextInput::make('badge_key')
                            ->required()
                            ->unique(Badge::class, 'badge_key', ignoreRecord: true)
                            ->maxLength(255),
                    ]),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('role')
                            ->label('Target Account Role')
                            ->options([
                                'both' => 'Both Roles',
                                'candidate' => 'Candidates Only',
                                'employer' => 'Employers Only',
                            ])
                            ->default('both')
                            ->required(),
                        Forms\Components\Select::make('icon_type')
                            ->label('Icon Representation')
                            ->options([
                                'class' => 'Lucide / Heroicon CSS Class',
                                'image' => 'Uploaded SVG/PNG Image File',
                            ])
                            ->default('class')
                            ->live()
                            ->required(),
                        Forms\Components\FileUpload::make('icon_path')
                            ->label('Upload SVG/PNG Badge')
                            ->directory('badges')
                            ->image()
                            ->visible(fn (Forms\Get $get) => $get('icon_type') === 'image')
                            ->required(fn (Forms\Get $get) => $get('icon_type') === 'image'),
                    ]),
                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Design Settings & Automation')->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('CSS Class Name')
                            ->placeholder('heroicon-s-shield-check')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('icon_type') === 'class')
                            ->required(fn (Forms\Get $get) => $get('icon_type') === 'class'),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Badge Accent Color')
                            ->default('#10B981'),
                        Forms\Components\TextInput::make('priority')
                            ->label('Sort Priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher priority badges are displayed first.'),
                    ]),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('rarity')
                            ->label('Rarity Tier')
                            ->options([
                                'common' => 'Common',
                                'rare' => 'Rare',
                                'legendary' => 'Legendary',
                            ])
                            ->default('common')
                            ->required(),
                        Forms\Components\Select::make('badge_type')
                            ->label('Badge Type')
                            ->options([
                                'standard' => 'Standard',
                                'seasonal' => 'Seasonal',
                                'event' => 'Event',
                            ])
                            ->default('standard')
                            ->required(),
                        Forms\Components\Toggle::make('is_hidden')
                            ->label('Is Hidden (Easter Egg)')
                            ->default(false)
                            ->helperText('Hidden from public catalogs until earned.'),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Toggle::make('is_automatic')
                            ->label('System Managed Automated Badge')
                            ->default(false)
                            ->live()
                            ->helperText('If enabled, the system AI agent will automatically grant or revoke this badge based on criteria below.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Badge Active Status')
                            ->default(true),
                    ]),
                    Forms\Components\Repeater::make('rules')
                        ->label('Automated Activation Rules')
                        ->schema([
                            Forms\Components\Select::make('field')
                                ->options([
                                    'completed_jobs' => 'Completed Jobs Count',
                                    'trust_score' => 'Trust Score',
                                    'rating' => 'Rating',
                                    'earnings' => 'Earnings (Candidate)',
                                    'spend' => 'Spend (Employer)',
                                    'verified' => 'Verification Status (true/false)',
                                ])
                                ->required(),
                            Forms\Components\Select::make('operator')
                                ->options([
                                    '>=' => '>= (Greater than or equal to)',
                                    '>' => '> (Greater than)',
                                    '<=' => '<= (Less than or equal to)',
                                    '<' => '< (Less than)',
                                    '=' => '= (Equal to)',
                                    '!=' => '!= (Not equal to)',
                                ])
                                ->required()
                                ->default('>='),
                            Forms\Components\TextInput::make('value')
                                ->required()
                                ->placeholder('e.g. 10 or true')
                                ->maxLength(255),
                        ])
                        ->columns(3)
                        ->visible(fn (Forms\Get $get) => $get('is_automatic'))
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('badge_key')
                    ->label('Key')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('rarity')
                    ->label('Rarity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'common' => 'gray',
                        'rare' => 'warning',
                        'legendary' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('badge_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Accent Color'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Assigned Users')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\SkillCenter;

use App\Filament\Resources\SkillCenter\CertificateTemplateResource\Pages;
use App\Models\CertificateTemplate;
use App\Models\SkillCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CertificateTemplateResource extends Resource
{
    protected static ?string $model = CertificateTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Skill Center';

    protected static ?string $navigationLabel = 'Certificate Templates';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->rows(3),
                ])->columns(2),

            Forms\Components\Section::make('Design')
                ->schema([
                    Forms\Components\Select::make('orientation')
                        ->options([
                            'landscape' => 'Landscape',
                            'portrait' => 'Portrait',
                        ])
                        ->default('landscape')
                        ->required(),
                    Forms\Components\ColorPicker::make('background_color')
                        ->default('#ffffff'),
                    Forms\Components\ColorPicker::make('primary_color')
                        ->default('#2563eb'),
                    Forms\Components\ColorPicker::make('accent_color')
                        ->default('#f59e0b'),
                ])->columns(2),

            Forms\Components\Section::make('Assets')
                ->schema([
                    Forms\Components\FileUpload::make('logo_path')
                        ->image()
                        ->directory('certificate-templates'),
                    Forms\Components\FileUpload::make('watermark_path')
                        ->image()
                        ->directory('certificate-templates'),
                ])->columns(2),

            Forms\Components\Section::make('Settings')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                    Forms\Components\Toggle::make('is_default')
                        ->default(false)
                        ->helperText('Only one template can be default'),
                ])->columns(2),

            Forms\Components\Section::make('Course Assignment')
                ->description('Select which Skill Courses should use this template.')
                ->schema([
                    Forms\Components\Select::make('assigned_course_ids')
                        ->label('Active Skill Courses')
                        ->multiple()
                        ->options(fn () => SkillCourse::where('is_active', true)->pluck('title', 'id')->toArray())
                        ->hidden(fn ($record) => !$record)
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('orientation')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'landscape' => 'info',
                        'portrait' => 'warning',
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_active', true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateTemplates::route('/'),
            'create' => Pages\CreateCertificateTemplate::route('/create'),
            'edit' => Pages\EditCertificateTemplate::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_skill_courses');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_skill_courses');
    }
}

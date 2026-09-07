<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Jobs';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $modelLabel = 'Category';
    protected static ?string $pluralModelLabel = 'Categories';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\TextInput::make('name_en')
                    ->label('Category Name (English)')
                    ->required(),
                Forms\Components\TextInput::make('name_bn')
                    ->label('Category Name (Bangla)')
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->label('Parent Category (for Sub-categories)')
                    ->relationship('parent', 'name_en')
                    ->nullable()
                    ->placeholder('Select Parent Category (Optional)'),
                Forms\Components\Select::make('icon')
                    ->label('Select Icon')
                    ->options([
                        'briefcase' => 'Briefcase',
                        'building-2' => 'Building',
                        'users' => 'NGO/Users',
                        'bell-ring' => 'Alert',
                        'star' => 'Star/Future',
                        'layers' => 'Top Category',
                        'code' => 'Code/Development',
                        'palette' => 'Palette/Design',
                        'cpu' => 'Cpu/Tech',
                        'trending-up' => 'Trending/Marketing',
                        'pen-tool' => 'Pen/Writing',
                    ])
                    ->default('briefcase')
                    ->required(),
                Forms\Components\Toggle::make('is_remote')
                    ->label('Is Remote Job Category')
                    ->default(false),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active (visible on website)')
                    ->default(true),
                Forms\Components\Toggle::make('is_highlighted')
                    ->label('Highlight on Homepage')
                    ->default(false),
            ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name_en')->label('English Name')->searchable(),
            Tables\Columns\TextColumn::make('name_bn')->label('Bangla Name'),
            Tables\Columns\TextColumn::make('parent.name_en')->label('Parent Category')->default('-'),
            Tables\Columns\IconColumn::make('is_remote')->boolean()->label('Remote'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            Tables\Columns\IconColumn::make('is_highlighted')->boolean()->label('Highlighted'),
            Tables\Columns\TextColumn::make('icon'),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make()->after(function () {
                Cache::forget('active_categories_with_children');
                Cache::forget('highlighted_categories');
            }),
            Tables\Actions\DeleteAction::make()->after(function () {
                Cache::forget('active_categories_with_children');
                Cache::forget('highlighted_categories');
            }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()->after(function () {
                    Cache::forget('active_categories_with_children');
                    Cache::forget('highlighted_categories');
                }),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
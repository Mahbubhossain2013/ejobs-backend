<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoticeResource\Pages;
use App\Models\Notice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class NoticeResource extends Resource
{
    protected static ?string $model = Notice::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Menu';
    protected static ?string $navigationLabel = 'Notices';
    protected static ?string $modelLabel = 'Notice';
    protected static ?string $pluralModelLabel = 'Notices';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Select::make('category')
                    ->label('Category (English)')
                    ->options([
                        'Recruitment' => 'Recruitment',
                        'Notice' => 'Notice',
                    ])
                    ->default('Notice')
                    ->required(),
                Forms\Components\TextInput::make('category_bn')
                    ->label('Category (Bangla)')
                    ->placeholder('e.g. নিয়োগ বা নোটিশ')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('Title (English)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title_bn')
                    ->label('Title (Bangla)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('published_at')
                    ->label('Published Date')
                    ->default(now())
                    ->required(),
            ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('category')->label('Category (EN)')->sortable(),
            Tables\Columns\TextColumn::make('category_bn')->label('Category (BN)'),
            Tables\Columns\TextColumn::make('title')->label('Title (EN)')->searchable(),
            Tables\Columns\TextColumn::make('title_bn')->label('Title (BN)')->searchable(),
            Tables\Columns\TextColumn::make('published_at')->label('Published Date')->date()->sortable(),
        ])
        ->filters([])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotices::route('/'),
            'create' => Pages\CreateNotice::route('/create'),
            'edit' => Pages\EditNotice::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_notices');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_notices');
    }
}

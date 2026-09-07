<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Categories'),
            'highlighted' => Tab::make('Highlighted Categories')
                ->modifyQueryUsing(fn ($query) => $query->where('is_highlighted', true)),
            'corporate' => Tab::make('Corporate Categories')
                ->modifyQueryUsing(fn ($query) => $query->where('is_remote', false)),
            'remote' => Tab::make('Remote Categories')
                ->modifyQueryUsing(fn ($query) => $query->where('is_remote', true)),
        ];
    }
}

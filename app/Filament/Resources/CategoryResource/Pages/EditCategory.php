<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('active_categories');
                    Cache::forget('active_categories_with_children');
                }),
        ];
    }

    protected function afterSave(): void
    {
        Cache::forget('active_categories');
        Cache::forget('active_categories_with_children');
    }
}

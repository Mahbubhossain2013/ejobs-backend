<?php

namespace App\Filament\Resources\TaxSettingResource\Pages;
use App\Filament\Resources\TaxSettingResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
class ListTaxSettings extends ListRecords
{
    protected static string $resource = TaxSettingResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}

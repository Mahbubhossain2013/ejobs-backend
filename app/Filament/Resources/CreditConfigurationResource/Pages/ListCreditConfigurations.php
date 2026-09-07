<?php

namespace App\Filament\Resources\CreditConfigurationResource\Pages;

use App\Filament\Resources\CreditConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreditConfigurations extends ListRecords
{
    protected static string $resource = CreditConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

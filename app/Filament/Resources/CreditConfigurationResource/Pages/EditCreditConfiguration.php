<?php

namespace App\Filament\Resources\CreditConfigurationResource\Pages;

use App\Filament\Resources\CreditConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCreditConfiguration extends EditRecord
{
    protected static string $resource = CreditConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

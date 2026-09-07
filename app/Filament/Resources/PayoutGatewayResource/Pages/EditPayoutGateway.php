<?php

namespace App\Filament\Resources\PayoutGatewayResource\Pages;

use App\Filament\Resources\PayoutGatewayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayoutGateway extends EditRecord
{
    protected static string $resource = PayoutGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

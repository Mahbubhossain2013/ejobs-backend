<?php

namespace App\Filament\Resources\PayoutGatewayResource\Pages;

use App\Filament\Resources\PayoutGatewayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayoutGateways extends ListRecords
{
    protected static string $resource = PayoutGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

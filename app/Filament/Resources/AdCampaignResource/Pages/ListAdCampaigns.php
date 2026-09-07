<?php

namespace App\Filament\Resources\AdCampaignResource\Pages;

use App\Filament\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

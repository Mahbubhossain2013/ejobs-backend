<?php

namespace App\Filament\Resources\AdCampaignResource\Pages;

use App\Filament\Resources\AdCampaignResource;
use App\Services\Ad\AdAiModerationService;
use Filament\Resources\Pages\CreateRecord;

class CreateAdCampaign extends CreateRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // Trigger automated AI screening on save
        AdAiModerationService::moderateCampaign($record, $record->user);
    }
}

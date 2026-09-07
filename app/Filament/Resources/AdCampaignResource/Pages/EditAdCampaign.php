<?php

namespace App\Filament\Resources\AdCampaignResource\Pages;

use App\Filament\Resources\AdCampaignResource;
use App\Services\Ad\AdAiModerationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdCampaign extends EditRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        
        // Re-moderate when config values change
        AdAiModerationService::moderateCampaign($record, $record->user);
    }
}

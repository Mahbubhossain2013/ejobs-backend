<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Services\Ad\AdServingService;
use App\Models\AdPlacement;
use App\Models\AdAudit;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAd extends EditRecord
{
    protected static string $resource = AdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    AdServingService::invalidateAllCaches();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['media_file']) && $data['media_file']) {
            $data['media_path'] = $data['media_file'];
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $oldRecord = $this->record->replicate();

        // 1. Sync Placements Slots Checklist
        $slots = $this->data['placed_slots'] ?? [];
        $this->record->placements()->delete();
        foreach ($slots as $slot) {
            AdPlacement::create([
                'ad_id' => $this->record->id,
                'slot' => $slot,
                'is_enabled' => true
            ]);
        }

        // 2. Audit Trail
        AdAudit::create([
            'ad_id' => $this->record->id,
            'user_id' => auth()->id(),
            'action' => 'edit',
            'old_values' => $oldRecord->toArray(),
            'new_values' => $this->record->toArray(),
        ]);

        // 3. Clear Caches
        AdServingService::invalidateAllCaches();
    }
}

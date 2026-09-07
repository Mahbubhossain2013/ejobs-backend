<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use App\Services\Ad\AdServingService;
use App\Models\AdPlacement;
use App\Models\AdAudit;
use Filament\Resources\Pages\CreateRecord;

class CreateAd extends CreateRecord
{
    protected static string $resource = AdResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['media_file']) && $data['media_file']) {
            $data['media_path'] = $data['media_file'];
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Save Placements Slots Checklist
        $slots = $this->data['placed_slots'] ?? [];
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
            'action' => 'create',
            'new_values' => $this->record->toArray(),
        ]);

        // 3. Clear Caches
        AdServingService::invalidateAllCaches();
    }
}

<?php

namespace App\Services\Ad;

use App\Models\AdAudit;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdAuditService
{
    /**
     * Audit log an administrative action
     */
    public static function logAction(string $action, ?int $adOrPromoId, array $oldValues, array $newValues, ?string $notes = null): void
    {
        try {
            AdAudit::create([
                'ad_id' => $adOrPromoId, // Maps to either Ad ID or Promotion ID depending on context
                'user_id' => auth()->id() ?? 1, // Fallback to system admin
                'action' => $action,
                'old_values' => array_merge($oldValues, ['notes' => $notes]),
                'new_values' => $newValues,
            ]);

            Log::info("Admin Audit: Action '{$action}' performed by user ID " . (auth()->id() ?? 'System') . " on target ID {$adOrPromoId}.");
        } catch (\Exception $e) {
            Log::error("Ad Audit Logging failed: " . $e->getMessage());
        }
    }
}

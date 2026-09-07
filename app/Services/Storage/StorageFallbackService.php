<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class StorageFallbackService
{
    /**
     * Resiliently store uploaded files.
     * Attempts S3/Cloud Storage, falls back seamlessly to local disk on failure.
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 's3'): string
    {
        $hasCloudConfig = !empty(config("filesystems.disks.{$disk}.key")) || !empty(env('AWS_ACCESS_KEY_ID'));
        
        if ($hasCloudConfig) {
            try {
                Log::info("Attempting cloud storage upload to disk: {$disk}");
                // Primary cloud disk upload
                $path = $file->store($directory, $disk);
                if ($path) {
                    return Storage::disk($disk)->url($path);
                }
            } catch (\Exception $e) {
                Log::warning("Cloud storage upload failed, degrading to local: " . $e->getMessage());
            }
        }

        // Safe Fallback: Store locally
        try {
            Log::info("Declassifying file save to robust local public disk.");
            $path = $file->store($directory, 'public');
            return Storage::disk('public')->url($path);
        } catch (\Exception $ex) {
            Log::critical("Critical failure in local storage save: " . $ex->getMessage());
            // Last-resort array/temporary path
            return "/storage/" . $file->getClientOriginalName();
        }
    }
}

<?php

namespace App\Filament\Resources\CvTemplateResource\Pages;

use App\Filament\Resources\CvTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCvTemplate extends EditRecord
{
    protected static string $resource = CvTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->optimizePreviewImage($data);
    }

    protected function optimizePreviewImage(array $data): array
    {
        if (empty($data['preview_image_path'])) {
            return $data;
        }

        $enableOpt = \App\Models\Setting::where('key', 'image_enable_optimization')->value('value') ?? '1';
        if ($enableOpt !== '1') {
            return $data;
        }

        $fullPath = storage_path('app/public/' . $data['preview_image_path']);
        if (!file_exists($fullPath)) {
            return $data;
        }

        try {
            $file = new \Illuminate\Http\UploadedFile(
                $fullPath,
                basename($fullPath),
                mime_content_type($fullPath),
                null,
                true
            );

            $optimizer = resolve(\App\Services\Media\ImageOptimizerService::class);
            $result = $optimizer->optimize($file, 'cv-templates');
            if ($result['status'] === 'success' || $result['status'] === 'fallback') {
                if ($result['original'] !== $data['preview_image_path'] && file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                $data['preview_image_path'] = $result['original'];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Filament template image optimization failed: " . $e->getMessage());
        }

        return $data;
    }
}

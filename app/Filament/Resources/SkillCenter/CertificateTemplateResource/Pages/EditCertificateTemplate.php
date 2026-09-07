<?php

namespace App\Filament\Resources\SkillCenter\CertificateTemplateResource\Pages;

use App\Filament\Resources\SkillCenter\CertificateTemplateResource;
use App\Models\SkillCourse;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateTemplate extends EditRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record) {
            $data['assigned_course_ids'] = SkillCourse::where('certificate_template_id', $this->record->id)
                ->pluck('id')
                ->toArray();
        }

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $assignedCourseIds = $data['assigned_course_ids'] ?? [];

        $record = parent::handleRecordUpdate($record, $data);

        if (!empty($assignedCourseIds)) {
            SkillCourse::where('certificate_template_id', $record->id)
                ->whereNotIn('id', $assignedCourseIds)
                ->update(['certificate_template_id' => null]);

            SkillCourse::whereIn('id', $assignedCourseIds)
                ->update(['certificate_template_id' => $record->id]);
        } else {
            SkillCourse::where('certificate_template_id', $record->id)
                ->update(['certificate_template_id' => null]);
        }

        return $record;
    }
}

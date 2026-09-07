<?php

namespace App\Filament\Resources\SkillCenter\CertificateTemplateResource\Pages;

use App\Filament\Resources\SkillCenter\CertificateTemplateResource;
use App\Models\SkillCourse;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateTemplate extends CreateRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

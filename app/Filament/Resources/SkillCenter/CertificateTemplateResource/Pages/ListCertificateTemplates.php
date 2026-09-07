<?php

namespace App\Filament\Resources\SkillCenter\CertificateTemplateResource\Pages;

use App\Filament\Resources\SkillCenter\CertificateTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCertificateTemplates extends ListRecords
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

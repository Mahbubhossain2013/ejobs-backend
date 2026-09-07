<?php

namespace App\Filament\Resources\SkillCourseResource\Pages;

use App\Filament\Resources\SkillCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSkillCourse extends EditRecord
{
    protected static string $resource = SkillCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

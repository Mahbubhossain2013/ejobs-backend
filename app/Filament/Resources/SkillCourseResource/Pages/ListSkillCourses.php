<?php

namespace App\Filament\Resources\SkillCourseResource\Pages;

use App\Filament\Resources\SkillCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSkillCourses extends ListRecords
{
    protected static string $resource = SkillCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

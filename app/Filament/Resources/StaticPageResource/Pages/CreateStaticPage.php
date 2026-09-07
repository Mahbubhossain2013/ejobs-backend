<?php

namespace App\Filament\Resources\StaticPageResource\Pages;

use App\Filament\Resources\StaticPageResource;
use App\Models\StaticPage;
use Filament\Resources\Pages\CreateRecord;

class CreateStaticPage extends CreateRecord
{
    protected static string $resource = StaticPageResource::class;

    protected function afterCreate(): void
    {
        StaticPage::flushCache($this->record->slug);
    }
}

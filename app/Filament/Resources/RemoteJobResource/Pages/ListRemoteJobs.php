<?php

namespace App\Filament\Resources\RemoteJobResource\Pages;

use App\Filament\Resources\RemoteJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRemoteJobs extends ListRecords
{
    protected static string $resource = RemoteJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

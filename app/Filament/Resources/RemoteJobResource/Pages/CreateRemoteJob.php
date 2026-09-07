<?php

namespace App\Filament\Resources\RemoteJobResource\Pages;

use App\Filament\Resources\RemoteJobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRemoteJob extends CreateRecord
{
    protected static string $resource = RemoteJobResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_remote_project'] = true;
        return $data;
    }
}

<?php

namespace App\Filament\Resources\RemoteJobResource\Pages;

use App\Filament\Resources\RemoteJobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRemoteJob extends EditRecord
{
    protected static string $resource = RemoteJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

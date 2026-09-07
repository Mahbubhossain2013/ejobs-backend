<?php

namespace App\Filament\Resources\BillingProfileResource\Pages;
use App\Filament\Resources\BillingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditBillingProfile extends EditRecord
{
    protected static string $resource = BillingProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}

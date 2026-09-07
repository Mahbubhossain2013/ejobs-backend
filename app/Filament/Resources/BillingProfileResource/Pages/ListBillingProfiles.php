<?php

namespace App\Filament\Resources\BillingProfileResource\Pages;
use App\Filament\Resources\BillingProfileResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
class ListBillingProfiles extends ListRecords
{
    protected static string $resource = BillingProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}

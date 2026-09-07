<?php

namespace App\Filament\Resources\InvoiceTemplateResource\Pages;

use App\Filament\Resources\InvoiceTemplateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListInvoiceTemplates extends ListRecords
{
    protected static string $resource = InvoiceTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('New Template')];
    }
}

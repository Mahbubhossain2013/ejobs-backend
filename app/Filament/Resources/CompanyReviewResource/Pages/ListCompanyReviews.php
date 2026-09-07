<?php

namespace App\Filament\Resources\CompanyReviewResource\Pages;

use App\Filament\Resources\CompanyReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyReviews extends ListRecords
{
    protected static string $resource = CompanyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No custom creation from admin portal needed since reviews are written by candidates.
        ];
    }
}

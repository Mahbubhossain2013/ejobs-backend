<?php

namespace App\Filament\Resources\WithdrawalResource\Pages;

use App\Filament\Resources\WithdrawalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWithdrawal extends CreateRecord
{
    protected static string $resource = WithdrawalResource::class;

    // After clicking "Create", go back to the list table
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
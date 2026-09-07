<?php

namespace App\Filament\Resources\CandidateResource\Pages;

use App\Filament\Resources\CandidateResource;
use App\Models\User;
use App\Services\Notification\AdminEmailService;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\EditRecord;

class EditCandidate extends EditRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),

            Actions\Action::make('toggle_verified')
                ->label(fn () => $this->getRecord()->profile && $this->getRecord()->profile->is_verified ? 'Unverify' : 'Verify')
                ->icon(fn () => $this->getRecord()->profile && $this->getRecord()->profile->is_verified ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge')
                ->color(fn () => $this->getRecord()->profile && $this->getRecord()->profile->is_verified ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->getRecord()->profile && $this->getRecord()->profile->is_verified ? 'Unverify User' : 'Verify User')
                ->modalDescription(fn () => $this->getRecord()->profile && $this->getRecord()->profile->is_verified ? 'This will revoke the verified status.' : 'This will mark the user as verified.')
                ->action(function () {
                    $record = $this->getRecord();
                    $profile = $record->profile()->firstOrCreate(['user_id' => $record->id]);
                    $wasVerified = (bool) $profile->is_verified;
                    $profile->update(['is_verified' => !$wasVerified]);

                    $newStatus = $wasVerified ? 'unverified' : 'verified';

                    AdminEmailService::notifyUser(
                        $record,
                        'Your verification status has been updated',
                        'emails.verification_status_changed',
                        [
                            'userName' => $record->name,
                            'type' => 'account',
                            'status' => $newStatus,
                            'reason' => $wasVerified ? 'Your verified status has been revoked by an administrator.' : 'Your account has been verified by an administrator.',
                        ]
                    );

                    FilamentNotification::make()
                        ->title('User ' . ucfirst($newStatus))
                        ->success()
                        ->send();
                }),
        ];
    }
}

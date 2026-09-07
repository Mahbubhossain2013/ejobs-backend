<?php

namespace App\Filament\Resources\VerificationResource\Pages;

use App\Filament\Resources\VerificationResource;
use App\Services\Security\AiVerificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVerification extends EditRecord
{
    protected static string $resource = VerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reverify_nid')
                ->label('Re-verify via NID API')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Re-run NID Verification')
                ->modalDescription('This will call the Government NID Database API to verify the NID number and date of birth. Any existing AI data will be overwritten with fresh results.')
                ->visible(fn () => $this->record->verification_type === 'nid' && $this->record->nid_number && $this->record->dob)
                ->action(function () {
                    $record = $this->record;
                    $user = $record->user;

                    if (!$user) {
                        Notification::make()->title('User not found')->danger()->send();
                        return;
                    }

                    try {
                        $aiService = app(AiVerificationService::class);
                        $aiResult = $aiService->analyzeNid(
                            $record->nid_number,
                            $record->dob->format('Y-m-d'),
                            $record->document_path ?? '',
                            $record->document_back_path,
                            $user
                        );

                        $record->update([
                            'status' => $aiResult['status'] === 'approved' ? 'approved' : ($aiResult['status'] === 'rejected' ? 'rejected' : 'manual_review'),
                            'ai_confidence_score' => $aiResult['confidence_score'],
                            'ai_analysis_data' => $aiResult,
                            'notes' => $aiResult['reasoning'],
                        ]);

                        if ($aiResult['status'] === 'approved') {
                            $record->update(['verified_at' => now(), 'reviewed_by' => auth()->id()]);
                        }

                        Notification::make()
                            ->title('NID Re-verification Complete')
                            ->body("Status: " . ucfirst($aiResult['status']) . " | Confidence: " . $aiResult['confidence_score'] . "%")
                            ->success()
                            ->send();

                        // Refresh the page to show updated data
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));

                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Re-verification Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}

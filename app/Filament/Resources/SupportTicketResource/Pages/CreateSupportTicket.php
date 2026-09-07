<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'open';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $initialMessage = $this->data['message'] ?? '';

        if (!empty($initialMessage)) {
            TicketMessage::create([
                'support_ticket_id' => $record->id,
                'sender_id' => auth()->id(),
                'message' => $initialMessage,
                'is_admin_reply' => auth()->user()->hasAnyRole(['super_admin', 'admin']),
                'admin_role_label' => auth()->user()->hasAnyRole(['super_admin', 'admin']) ? (auth()->user()->admin_role ?? 'Support Agent') : null,
            ]);
        }
    }
}

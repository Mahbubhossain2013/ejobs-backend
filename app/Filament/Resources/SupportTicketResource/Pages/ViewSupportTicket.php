<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.resources.support-ticket-resource.pages.view-ticket';

    public ?string $replyMessage = '';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_ticket')
                ->label('Assign Agent')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin']))
                ->form([
                    Forms\Components\Select::make('assigned_admin_id')
                        ->label('Support Agent')
                        ->options(User::role('admin')->pluck('name', 'id'))
                        ->required()
                        ->default(fn () => $this->getRecord()->assigned_admin_id),
                ])
                ->action(function (array $data) {
                    $this->getRecord()->update(['assigned_admin_id' => $data['assigned_admin_id']]);
                    Notification::make()->title('Ticket successfully assigned.')->success()->send();
                }),

            Actions\Action::make('resolve_ticket')
                ->label('Mark as Resolved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->getRecord()->status, ['open', 'pending', 'answered']))
                ->action(function () {
                    $this->getRecord()->update(['status' => 'resolved']);
                    Notification::make()->title('Ticket resolved.')->success()->send();
                }),

            Actions\Action::make('close_ticket')
                ->label('Close Ticket')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->getRecord()->status !== 'closed')
                ->action(function () {
                    $ticket = $this->getRecord();
                    $ticket->update(['status' => 'closed']);

                    // Send email to user
                    try {
                        $ticketOwner = $ticket->user;
                        if ($ticketOwner && $ticketOwner->email) {
                            $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                            $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/support';
                            Mail::send('emails.ticket_closed', [
                                'brandName' => $brandName,
                                'userName' => $ticketOwner->name,
                                'subject' => $ticket->subject,
                                'dashboardUrl' => $dashboardUrl,
                            ], function ($mail) use ($ticketOwner, $brandName) {
                                $mail->to($ticketOwner->email)
                                     ->subject("{$brandName} — Your support ticket has been closed");
                            });
                        }
                    } catch (\Throwable $e) {
                        Log::error("Ticket closed email failed for ticket {$ticket->id}: " . $e->getMessage());
                    }

                    Notification::make()->title('Ticket closed.')->success()->send();
                }),
        ];
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyMessage' => 'required|string|min:2',
        ]);

        $user = auth()->user();
        $ticket = $this->getRecord();

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => $user->id,
            'message' => $this->replyMessage,
            'is_admin_reply' => $user->hasAnyRole(['super_admin', 'admin']),
            'admin_role_label' => $user->hasAnyRole(['super_admin', 'admin']) ? ($user->admin_role ?? 'Support Agent') : null,
        ]);

        SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $this->replyMessage,
            'is_admin' => $user->hasAnyRole(['super_admin', 'admin']),
        ]);

        // Dynamically update status
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            $ticket->update(['status' => 'in_progress']);

            // Send email to user when admin replies
            try {
                $ticketOwner = $ticket->user;
                if ($ticketOwner && $ticketOwner->email) {
                    $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                    $ticketUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/support';
                    $replyPreview = mb_strimwidth(strip_tags($this->replyMessage), 0, 200, '...');
                    Mail::send('emails.ticket_reply', [
                        'brandName' => $brandName,
                        'userName' => $ticketOwner->name,
                        'ticketSubject' => $ticket->subject,
                        'replyPreview' => $replyPreview,
                        'ticketUrl' => $ticketUrl,
                    ], function ($mail) use ($ticketOwner, $brandName) {
                        $mail->to($ticketOwner->email)
                             ->subject("{$brandName} — Support ticket reply");
                    });
                }
            } catch (\Throwable $e) {
                Log::error("Ticket reply email failed for ticket {$ticket->id}: " . $e->getMessage());
            }
        } else {
            $ticket->update(['status' => 'pending']); // User replied, status changes to pending (awaits admin)
        }

        $this->replyMessage = ''; // Clear message input

        Notification::make()
            ->title('Reply posted successfully')
            ->success()
            ->send();
    }
}

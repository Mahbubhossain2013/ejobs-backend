<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Escrow;
use App\Models\Dispute;
use App\Models\Wallet;
use App\Models\Message;
use App\Models\Conversation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class DisputeResource extends Resource
{
    protected static ?string $model = Escrow::class;
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Dispute Center';
    protected static ?string $slug = 'disputes';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotIn('status', ['released', 'refunded', 'cancelled', 'completed', 'disbursed']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('job.title')->label('Project')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('job.is_remote_project')
                    ->label('Type')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Remote' : 'On-site'),
                Tables\Columns\TextColumn::make('job.budget')
                    ->label('Budget')
                    ->money('BDT')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('amount')->label('Locked Amount')->money('BDT')->weight('bold')->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Days Locked')
                    ->getStateUsing(fn ($record) => $record->created_at ? now()->diffInDays($record->created_at) . 'd' : '—'),
                Tables\Columns\TextColumn::make('employer.name')->label('Employer'),
                Tables\Columns\TextColumn::make('candidate.name')->label('Candidate'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disputed' => 'danger',
                        'active'   => 'warning',
                        'funded'   => 'info',
                        'pending'  => 'gray',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('employer.wallet.balance')
                    ->label('Employer Balance')
                    ->money('BDT')
                    ->color('success'),
                Tables\Columns\TextColumn::make('employer.wallet.locked_balance')
                    ->label('Employer Locked')
                    ->money('BDT')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('dispute.reason')
                    ->label('Dispute Reason')
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('dispute.status')
                    ->label('Dispute Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'open' => 'danger',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'disputed' => 'Disputed',
                    'active' => 'Active',
                    'funded' => 'Funded',
                ])
                ->multiple(),
            Tables\Filters\SelectFilter::make('has_dispute')
                ->label('Has Dispute')
                ->options([
                    'yes' => 'Yes',
                    'no' => 'No',
                ])
                ->query(function (Builder $query, ?string $value) {
                    if ($value === 'yes') {
                        $query->whereHas('dispute');
                    } elseif ($value === 'no') {
                        $query->whereDoesntHave('dispute');
                    }
                }),
        ])
        ->actions([
            // Mark as Disputed
            Tables\Actions\Action::make('mark_disputed')
                ->label('Mark Disputed')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn ($record) => $record->status !== 'disputed')
                ->requiresConfirmation()
                ->modalHeading('Mark as Disputed')
                ->modalDescription('This escrow will be flagged as disputed. Both employer and candidate will be notified.')
                ->form([
                    Forms\Components\Select::make('opened_by')
                        ->label('Opened By')
                        ->options(fn () => [
                            $record->employer_id => 'Employer: ' . $record->employer?->name,
                            $record->candidate_id => 'Candidate: ' . $record->candidate?->name,
                            auth()->id() => 'Admin (Self)',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->label('Dispute Reason')
                        ->required()
                        ->placeholder('Why is this being disputed?'),
                ])
                ->action(function (Escrow $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        $record->update(['status' => 'disputed']);

                        Dispute::updateOrCreate(
                            ['job_id' => $record->job_id, 'status' => 'open'],
                            [
                                'opened_by' => $data['opened_by'],
                                'reason' => $data['reason'],
                                'status' => 'open',
                            ]
                        );

                        $conversation = Conversation::where('job_id', $record->job_id)->first();
                        if ($conversation) {
                            Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => auth()->id(),
                                'message' => "SYSTEM: This project has been marked as DISPUTED by Admin. Reason: " . $data['reason'] . ". Funds are locked until resolution.",
                            ]);
                        }
                    });
                    Notification::make()->title('Escrow marked as disputed')->success()->send();
                }),

            // FORCE PAYOUT TO CANDIDATE
            Tables\Actions\Action::make('force_payout')
                ->label('Pay Candidate')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Resolve in favor of Candidate')
                ->modalDescription('This will release the escrow funds to the candidate (minus platform fee). This action is irreversible.')
                ->form([
                    Forms\Components\Textarea::make('resolution_note')
                        ->label('Resolution Note')
                        ->required()
                        ->placeholder('Explain why the candidate won the dispute...'),
                ])
                ->action(function (Escrow $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        $platformFee = $record->amount * 0.05;
                        $candidatePayout = $record->amount - $platformFee;

                        $record->job->update(['project_status' => 'completed']);
                        $record->update(['status' => 'released', 'platform_fee' => $platformFee]);

                        $employerWallet = Wallet::where('user_id', $record->employer_id)->lockForUpdate()->first();
                        $employerWallet->locked_balance -= $record->amount;
                        $employerWallet->save();

                        $candidateWallet = Wallet::firstOrCreate(['user_id' => $record->candidate_id]);
                        $candidateWallet->credit($candidatePayout, 'dispute_payout', $record->id, 'Dispute resolved in your favor: ' . $record->job->title);

                        Dispute::where('job_id', $record->job_id)->where('status', 'open')->update(['status' => 'resolved', 'admin_notes' => $data['resolution_note']]);

                        $conversation = Conversation::where('job_id', $record->job_id)->first();
                        if ($conversation) {
                            Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => auth()->id(),
                                'message' => "SYSTEM (DISPUTE RESOLVED): Paid to Candidate. Reason: " . $data['resolution_note'],
                            ]);
                        }
                    });
                    Notification::make()->title('Dispute resolved & Candidate Paid')->success()->send();
                }),

            // FORCE REFUND TO EMPLOYER
            Tables\Actions\Action::make('force_refund')
                ->label('Refund Employer')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Resolve in favor of Employer')
                ->modalDescription('This will return the locked funds back to the employer\'s available balance. The candidate gets nothing.')
                ->form([
                    Forms\Components\Textarea::make('resolution_note')
                        ->label('Resolution Note')
                        ->required()
                        ->placeholder('Explain why the employer won the dispute...'),
                ])
                ->action(function (Escrow $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        $record->job->update(['project_status' => 'cancelled']);
                        $record->update(['status' => 'refunded', 'platform_fee' => 0]);

                        $employerWallet = Wallet::where('user_id', $record->employer_id)->lockForUpdate()->first();
                        $employerWallet->locked_balance -= $record->amount;
                        $employerWallet->balance += $record->amount;
                        $employerWallet->save();

                        $employerWallet->transactions()->create([
                            'type' => 'credit',
                            'amount' => $record->amount,
                            'reference_type' => 'dispute_refund',
                            'reference_id' => $record->id,
                            'description' => 'Dispute Refund: ' . $record->job->title,
                            'status' => 'completed'
                        ]);

                        Dispute::where('job_id', $record->job_id)->where('status', 'open')->update(['status' => 'resolved', 'admin_notes' => $data['resolution_note']]);

                        $conversation = Conversation::where('job_id', $record->job_id)->first();
                        if ($conversation) {
                            Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => auth()->id(),
                                'message' => "SYSTEM (DISPUTE RESOLVED): Refunded to Employer. Reason: " . $data['resolution_note'],
                            ]);
                        }
                    });
                    Notification::make()->title('Dispute resolved & Employer Refunded')->success()->send();
                }),

            // PARTIAL RESOLUTION
            Tables\Actions\Action::make('partial_resolution')
                ->label('Split')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Partial Resolution')
                ->modalDescription('Split the escrow funds between candidate and employer.')
                ->form([
                    Forms\Components\TextInput::make('refund_percentage')
                        ->label('Refund to Employer (%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(50)
                        ->suffix('%'),
                    Forms\Components\Textarea::make('resolution_note')
                        ->label('Resolution Note')
                        ->required()
                        ->placeholder('Explain the partial resolution terms...'),
                ])
                ->action(function (Escrow $record, array $data) {
                    $refundPct = (float) $data['refund_percentage'];
                    $refundAmount = $record->amount * ($refundPct / 100);
                    $candidatePayout = $record->amount - $refundAmount;
                    $platformFee = $record->amount * 0.05;

                    DB::transaction(function () use ($record, $refundAmount, $candidatePayout, $platformFee, $data) {
                        $record->job->update(['project_status' => 'completed']);
                        $record->update(['status' => 'released', 'platform_fee' => $platformFee]);

                        Dispute::where('job_id', $record->job_id)->where('status', 'open')->update([
                            'refund_percentage' => $data['refund_percentage'],
                            'admin_notes' => $data['resolution_note'],
                            'status' => 'resolved',
                        ]);

                        $employerWallet = Wallet::where('user_id', $record->employer_id)->lockForUpdate()->first();
                        $employerWallet->locked_balance -= $record->amount;
                        $employerWallet->save();

                        $candidateWallet = Wallet::firstOrCreate(['user_id' => $record->candidate_id]);
                        $candidateWallet->credit($candidatePayout, 'dispute_partial_payout', $record->id, 'Partial dispute resolution for: ' . $record->job->title);

                        if ($refundAmount > 0) {
                            $employerWallet->balance += $refundAmount;
                            $employerWallet->save();
                            $employerWallet->transactions()->create([
                                'type' => 'credit',
                                'amount' => $refundAmount,
                                'reference_type' => 'dispute_partial_refund',
                                'reference_id' => $record->id,
                                'description' => 'Partial dispute refund: ' . $record->job->title,
                                'status' => 'completed',
                            ]);
                        }

                        $conversation = Conversation::where('job_id', $record->job_id)->first();
                        if ($conversation) {
                            Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => auth()->id(),
                                'message' => "SYSTEM (PARTIAL RESOLUTION): {$data['refund_percentage']}% refunded to Employer ({$refundAmount} BDT), {$candidatePayout} BDT paid to Candidate. Reason: " . $data['resolution_note'],
                            ]);
                        }
                    });
                    Notification::make()->title('Partial resolution applied successfully')->success()->send();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
        ];
    }
}

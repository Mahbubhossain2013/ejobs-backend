<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Services\Notification\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';

    // --- 1. FORM SETUP (Fallback view & admin overrides) ---
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Withdrawal Information')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Candidate'),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('৳')
                        ->required()
                        ->minValue(500),

                    Forms\Components\TextInput::make('charge')
                        ->numeric()
                        ->prefix('৳')
                        ->label('Fee'),

                    Forms\Components\TextInput::make('payable')
                        ->numeric()
                        ->prefix('৳')
                        ->label('Net Payout'),

                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'bKash' => 'bKash',
                            'Nagad' => 'Nagad',
                            'Rocket' => 'Rocket',
                            'Bank Transfer' => 'Bank Transfer',
                        ])
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('pending')
                        ->required(),

                    Forms\Components\Textarea::make('account_details')
                        ->placeholder('e.g. 017xxxxxxxx or Bank Account Info')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('admin_note')
                        ->label('Admin Notes / Rejection Reason')
                        ->columnSpanFull(),
                ])->columns(2)
        ]);
    }

    // --- 2. TABLE SETUP & ACTION LOGIC ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('BDT')->fontFamily('mono')->sortable(),
                Tables\Columns\TextColumn::make('charge')->money('BDT')->label('Fee')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('payable')->money('BDT')->label('Net Payout')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('payment_method')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Requested At'),
            ])
            ->actions([
                // Approve Payout
                Tables\Actions\Action::make('approve')
                    ->label('Approve Payout')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->action(function (Withdrawal $record) {
                        $wallet = $record->user->wallet;

                        if ($wallet && $wallet->locked_balance >= $record->amount) {
                            $wallet->decrement('locked_balance', $record->amount);
                        }

                        $record->update(['status' => 'approved']);
                        
                        app(NotificationService::class)->sendNotification(
                            $record->user,
                            'Withdrawal Approved',
                            'Your withdrawal of ' . number_format($record->payable, 2) . ' BDT has been processed successfully.',
                            'billing',
                            '/dashboard/wallet'
                        );
                        
                        Notification::make()
                            ->title('Payout Approved')
                            ->body('The requested funds have been settled against the user\'s locked balance.')
                            ->success()
                            ->send();
                    }),

                // Reject Payout
                Tables\Actions\Action::make('reject')
                    ->label('Reject Payout')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Reason for Rejection')
                            ->required()
                    ])
                    ->action(function (Withdrawal $record, array $data) {
                        $wallet = $record->user->wallet;

                        if ($wallet) {
                            // Return funds seamlessly to user pools
                            $wallet->increment('balance', $record->amount);
                            
                            if (Schema::hasColumn('wallets', 'withdrawable_balance')) {
                                $wallet->increment('withdrawable_balance', $record->amount);
                            }

                            if ($wallet->locked_balance >= $record->amount) {
                                $wallet->decrement('locked_balance', $record->amount);
                            }
                        }

                        $record->update([
                            'status' => 'rejected',
                            'admin_note' => $data['admin_note']
                        ]);

                        app(NotificationService::class)->sendNotification(
                            $record->user,
                            'Withdrawal Rejected',
                            'Your withdrawal of ' . number_format($record->amount, 2) . ' BDT has been rejected. Reason: ' . $data['admin_note'],
                            'billing',
                            '/dashboard/wallet'
                        );

                        Notification::make()
                            ->title('Payout Rejected')
                            ->body('Funds returned safely back to user balance.')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'create' => Pages\CreateWithdrawal::route('/create'),
            'edit' => Pages\EditWithdrawal::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_withdrawals');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_withdrawals');
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepositResource\Pages;
use App\Models\Deposit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';
    protected static ?string $navigationGroup = 'Finance';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('BDT')
                    ->fontFamily('mono')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('gateway.display_name')->label('Method'),
                Tables\Columns\TextColumn::make('transaction_id')->label('TrxID')->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Requested')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(Deposit $record) => $record->status === 'pending')
                    ->action(fn(Deposit $record) => self::approveDeposit($record)),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn(Deposit $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_feedback')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (Deposit $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'admin_feedback' => $data['admin_feedback'],
                        ]);
                        Notification::make()->title('Deposit rejected')->success()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function approveDeposit(Deposit $deposit)
    {
        if ($deposit->status !== 'pending') return;

        $deposit->update(['status' => 'approved']);

        $wallet = \App\Models\Wallet::firstOrCreate(
            ['user_id' => $deposit->user_id],
            ['balance' => 0, 'locked_balance' => 0, 'withdrawable_balance' => 0]
        );
        $wallet->increment('balance', $deposit->amount);
        $wallet->increment('withdrawable_balance', $deposit->amount);

        \App\Models\WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $deposit->amount,
            'reference_type' => 'deposit',
            'reference_id' => $deposit->id,
            'description' => "Fund added via " . ($deposit->gateway?->display_name ?? 'Payment Gateway'),
            'status' => 'completed'
        ]);

        \Illuminate\Support\Facades\Cache::forget("wallet_balance_{$deposit->user_id}");

        Notification::make()->title('Deposit Approved — balance updated.')->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeposits::route('/'),
            'create' => Pages\CreateDeposit::route('/create'),
            'edit' => Pages\EditDeposit::route('/{record}/edit'),
        ];
    }
}

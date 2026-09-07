<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletLedgerResource\Pages;
use App\Models\WalletTransaction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class WalletLedgerResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-bangladeshi';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Ads Transaction Ledgers';
    protected static ?string $modelLabel = 'Ledger Record';
    protected static ?string $pluralModelLabel = 'Ledger Records';
    protected static ?string $slug = 'finance/ledgers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('wallet_id')
                        ->relationship('wallet', 'id')
                        ->disabled(),
                    Forms\Components\TextInput::make('type')->disabled(),
                    Forms\Components\TextInput::make('amount')->numeric()->prefix('৳')->disabled(),
                    Forms\Components\TextInput::make('balance_after')->numeric()->prefix('৳')->disabled(),
                    Forms\Components\TextInput::make('reference_type')->disabled(),
                    Forms\Components\TextInput::make('reference_id')->disabled(),
                    Forms\Components\TextInput::make('status')->disabled(),
                ]),
                Forms\Components\Textarea::make('description')->disabled()->rows(3),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('wallet.user.name')
                ->label('Employer')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->colors([
                    'success' => 'credit',
                    'danger' => 'debit',
                ])
                ->formatStateUsing(fn ($state) => strtoupper($state)),

            Tables\Columns\TextColumn::make('amount')
                ->money('BDT')
                ->sortable(),

            Tables\Columns\TextColumn::make('balance_after')
                ->label('Balance After')
                ->money('BDT')
                ->sortable(),

            Tables\Columns\TextColumn::make('reference_type')
                ->label('Ref Type')
                ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                ->badge(),

            Tables\Columns\TextColumn::make('description')
                ->wrap()
                ->searchable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'success' => 'completed',
                    'danger' => 'failed',
                    'warning' => 'disputed',
                    'info' => 'refunded',
                ])
                ->formatStateUsing(fn ($state) => strtoupper($state)),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Billed Time')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('reference_type')
                ->options([
                    'promotion_lock' => 'Upfront Budget Lock',
                    'promotion_unlock' => 'Budget Unlock / Release',
                    'promotion_billing' => 'Hourly Spend Deduction',
                    'wallet_freeze' => 'Wallet Freeze Event',
                    'deposit' => 'Manual/Gateway Deposits',
                ]),
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'disputed' => 'Disputed',
                    'refunded' => 'Refunded',
                ]),
        ])
        ->actions([
            // Resolve Dispute Action
            Action::make('resolve_dispute')
                ->label('Resolve Dispute')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn (WalletTransaction $record) => $record->status === 'disputed')
                ->requiresConfirmation()
                ->action(function (WalletTransaction $record) {
                    $record->update(['status' => 'completed']);
                    Notification::make()->title('Ledger Dispute Resolved successfully.')->success()->send();
                }),

            // Refund transaction
            Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn (WalletTransaction $record) => $record->type === 'debit' && $record->status === 'completed')
                ->requiresConfirmation()
                ->action(function (WalletTransaction $record) {
                    // Credit refund back to employer wallet
                    $wallet = $record->wallet;
                    if ($wallet) {
                        $wallet->credit($record->amount, 'promotion_refund', $record->reference_id, "Refunded BDT {$record->amount} for campaign transaction.");
                        $record->update(['status' => 'refunded']);
                        Notification::make()->title("BDT {$record->amount} refunded successfully.")->success()->send();
                    }
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletLedgers::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_wallet_ledger');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_wallet_ledger');
    }
}

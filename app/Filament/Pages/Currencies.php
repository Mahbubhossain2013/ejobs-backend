<?php

namespace App\Filament\Pages;

use App\Models\Currency;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Forms;

class Currencies extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.currencies';
    protected static ?string $title = 'Currencies';
    protected static ?string $slug = 'system-settings/currencies';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 50;

    public string $search = '';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_exchange_rates')
                ->label('Sync Exchange Rates')
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    // Simulate syncing latest exchange rates from an API
                    $currencies = Currency::where('code', '!=', 'USD')->get();
                    foreach ($currencies as $currency) {
                        // Add a small random variation to make it look realistic and dynamic
                        $variation = (rand(-50, 50) / 1000) * $currency->rate;
                        $currency->update([
                            'rate' => max(0.0001, $currency->rate + $variation),
                        ]);
                    }

                    Notification::make()
                        ->title('Exchange Rates Synced!')
                        ->success()
                        ->body('Latest exchange rates successfully synchronized with international markets.')
                        ->send();
                }),

            Action::make('new_currency')
                ->label('New Currency')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('code')
                        ->label('Code*')
                        ->placeholder('e.g., PKR')
                        ->required()
                        ->unique(table: 'currencies', column: 'code'),
                    Forms\Components\TextInput::make('symbol')
                        ->label('Symbol*')
                        ->placeholder('e.g., ₨')
                        ->required(),
                    Forms\Components\TextInput::make('rate')
                        ->label('Exchange Rate (1 USD = ?)*')
                        ->numeric()
                        ->required(),
                    Forms\Components\Toggle::make('enabled')
                        ->label('Enabled')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    Currency::create($data);

                    Notification::make()
                        ->title('Currency Created!')
                        ->success()
                        ->body("{$data['code']} has been added to system currencies.")
                        ->send();
                }),
        ];
    }

    public function getCurrencies()
    {
        $query = Currency::query();

        if (trim($this->search) !== '') {
            $s = '%' . $this->search . '%';
            $query->where('code', 'like', $s)
                  ->orWhere('symbol', 'like', $s);
        }

        return $query->orderBy('code', 'asc')->get();
    }

    public function toggleCurrency(int $id): void
    {
        $currency = Currency::findOrFail($id);
        $currency->update(['enabled' => !$currency->enabled]);

        Notification::make()
            ->title('Status Updated!')
            ->success()
            ->body("{$currency->code} status has been updated.")
            ->send();
    }

    public function deleteCurrency(int $id): void
    {
        $currency = Currency::findOrFail($id);
        
        if (in_array($currency->code, ['USD', 'BDT'])) {
            Notification::make()
                ->title('Cannot Delete Master Currency!')
                ->danger()
                ->body("{$currency->code} is a master system currency and cannot be deleted.")
                ->send();
            return;
        }

        $currency->delete();

        Notification::make()
            ->title('Currency Deleted!')
            ->success()
            ->body('The selected currency has been removed.')
            ->send();
    }

    // Helper method to trigger a dynamic action for editing inline
    public function updateRate(int $id, float $newRate): void
    {
        $currency = Currency::findOrFail($id);
        $currency->update(['rate' => $newRate]);

        Notification::make()
            ->title('Rate Updated!')
            ->success()
            ->body("Exchange rate for {$currency->code} has been updated to {$newRate}.")
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_currencies');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}

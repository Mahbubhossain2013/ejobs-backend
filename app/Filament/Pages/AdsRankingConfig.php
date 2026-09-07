<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Ad\AdAuditService;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class AdsRankingConfig extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Ranking Engine Config';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Ads Ranking Algorithm Configurator';
    protected static ?string $slug = 'marketing/ranking-config';
    
    protected static string $view = 'filament.pages.ads-ranking-config';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = [
            'ranking_relevance_weight' => intval(Setting::where('key', 'ranking_relevance_weight')->value('value') ?? 40),
            'ranking_bid_weight' => intval(Setting::where('key', 'ranking_bid_weight')->value('value') ?? 30),
            'ranking_ctr_weight' => intval(Setting::where('key', 'ranking_ctr_weight')->value('value') ?? 20),
            'ranking_freshness_weight' => intval(Setting::where('key', 'ranking_freshness_weight')->value('value') ?? 10),
            'ranking_auto_ai_enabled' => filter_var(Setting::where('key', 'ranking_auto_ai_enabled')->value('value') ?? true, FILTER_VALIDATE_BOOLEAN),
            'search_ad_injection_enabled' => filter_var(Setting::where('key', 'search_ad_injection_enabled')->value('value') ?? true, FILTER_VALIDATE_BOOLEAN),
        ];

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Card::make([
                    Forms\Components\Section::make('Algorithm Weights Tuning')
                        ->description('Adjust importance weights. The sum of these 4 parameters must total exactly 100%.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('ranking_relevance_weight')
                                    ->label('Relevance Weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Importance score for skills and location matches.'),
                                
                                Forms\Components\TextInput::make('ranking_bid_weight')
                                    ->label('Bid Sizing Weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Importance score for budget and bid multipliers.'),

                                Forms\Components\TextInput::make('ranking_ctr_weight')
                                    ->label('CTR Performance Weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Importance score for click feedback metrics.'),

                                Forms\Components\TextInput::make('ranking_freshness_weight')
                                    ->label('Freshness Decay Weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Importance score for newly launched promotions.'),
                            ]),
                        ]),
                ])->columnSpan(2),

                Forms\Components\Card::make([
                    Forms\Components\Section::make('Automated Overrides')
                        ->description('Configure platform and injection flags.')
                        ->schema([
                            Forms\Components\Toggle::make('ranking_auto_ai_enabled')
                                ->label('Enable Auto-Ranking AI')
                                ->helperText('Allows self-optimizing pipelines to maximize CTR performance.'),
                            
                            Forms\Components\Toggle::make('search_ad_injection_enabled')
                                ->label('Enable Search Ad Injections')
                                ->helperText('Auto injects sponsored listings between candidate search items.'),
                        ]),
                ])->columnSpan(1),
            ])
        ])->statePath('data');
    }

    public function saveSettings(): void
    {
        $state = $this->form->getState();

        $relevance = intval($state['ranking_relevance_weight'] ?? 0);
        $bid = intval($state['ranking_bid_weight'] ?? 0);
        $ctr = intval($state['ranking_ctr_weight'] ?? 0);
        $freshness = intval($state['ranking_freshness_weight'] ?? 0);

        $total = $relevance + $bid + $ctr + $freshness;

        if ($total !== 100) {
            Notification::make()
                ->title('Validation Failed')
                ->body("The sum of relevance, bid, CTR, and freshness weights MUST be exactly 100%. Currently it is {$total}%.")
                ->danger()
                ->send();
            return;
        }

        Setting::updateOrCreate(['key' => 'ranking_relevance_weight'], ['value' => $relevance]);
        Setting::updateOrCreate(['key' => 'ranking_bid_weight'], ['value' => $bid]);
        Setting::updateOrCreate(['key' => 'ranking_ctr_weight'], ['value' => $ctr]);
        Setting::updateOrCreate(['key' => 'ranking_freshness_weight'], ['value' => $freshness]);
        Setting::updateOrCreate(['key' => 'ranking_auto_ai_enabled'], ['value' => $state['ranking_auto_ai_enabled']]);
        Setting::updateOrCreate(['key' => 'search_ad_injection_enabled'], ['value' => $state['search_ad_injection_enabled']]);

        AdAuditService::logAction('ranking_weights_update', null, [], $state, 'Dynamic algorithm weights adjusted.');

        Notification::make()->title('Ranking weights updated successfully. Cache invalidated.')->success()->send();
    }

    /**
     * One-click AI recommendation application
     */
    public function applySuggestion(int $relevance, int $bid, int $ctr, int $freshness, string $label): void
    {
        $this->form->fill([
            'ranking_relevance_weight' => $relevance,
            'ranking_bid_weight' => $bid,
            'ranking_ctr_weight' => $ctr,
            'ranking_freshness_weight' => $freshness,
            'ranking_auto_ai_enabled' => true,
            'search_ad_injection_enabled' => true
        ]);

        $this->saveSettings();

        Notification::make()
            ->title('AI Suggestion Applied')
            ->body("Applied suggestion: {$label}")
            ->success()
            ->send();
    }
}

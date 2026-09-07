<?php

namespace App\Filament\Pages;

use App\Models\AiConfig;
use App\Models\AiAlgorithmSetting;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class AiManager extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'System';
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $title = 'AI Manager';
    protected static string $view = 'filament.pages.ai-manager';

    public ?array $data = [];
    public array $fobignModels = [];
    public bool $fobignEnabled = false;

    /**
     * Fetch available FIN models from Fobign API.
     * Cached for 24 hours to avoid repeated API calls.
     */
    public function fetchFobignModels(): void
    {
        try {
            $fobignConfig = AiConfig::where('provider_key', 'fobign')->first();
            if (!$fobignConfig || !$fobignConfig->api_key) {
                $this->fobignModels = $this->getDefaultFinModels();
                return;
            }

            $cached = Cache::get('fobign_models_list');
            if ($cached) {
                $this->fobignModels = $cached;
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $fobignConfig->api_key,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get('https://api.fobign.com/v1/models');

            if ($response->successful()) {
                $body = $response->json();
                $models = collect($body['data'] ?? [])->map(fn ($m) => $m['id'] ?? $m)->toArray();
                $this->fobignModels = !empty($models) ? $models : $this->getDefaultFinModels();
            } else {
                $this->fobignModels = $this->getDefaultFinModels();
            }

            Cache::put('fobign_models_list', $this->fobignModels, now()->addHours(24));
        } catch (\Exception $e) {
            $this->fobignModels = $this->getDefaultFinModels();
        }
    }

    /**
     * Default FIN models list (used when API is unavailable).
     */
    private function getDefaultFinModels(): array
    {
        return [
            'fin-1',
            'fin-1-pro',
            'fin-1-mini',
            'fin-1-turbo',
            'fin-1-flash',
            'fin-1-fast',
            'fin-1-large',
            'fin-vision-1',
            'fin-code-1',
        ];
    }

    /**
     * Toggle provider visibility (hide/show). Fobign at Priority 1 cannot be hidden.
     */
    public function toggleProviderVisibility(int $providerId): void
    {
        $config = AiConfig::find($providerId);
        if (!$config) return;

        // Prevent hiding Fobign when it's Priority 1
        if ($config->provider_key === 'fobign' && $config->priority === 1) {
            Notification::make()
                ->title('Cannot Hide Primary Provider')
                ->warning()
                ->body('Fobign (FIN) is locked as Priority 1 and cannot be hidden.')
                ->send();
            return;
        }

        $config->update(['is_hidden' => !$config->is_hidden]);

        // Store in local cache for quick UI access
        $hiddenIds = Cache::get('ai_provider_hidden_ids', []);
        if ($config->is_hidden) {
            $hiddenIds[] = $config->id;
        } else {
            $hiddenIds = array_diff($hiddenIds, [$config->id]);
        }
        Cache::put('ai_provider_hidden_ids', array_values($hiddenIds), now()->addDays(30));

        Notification::make()
            ->title($config->is_hidden ? 'Provider Hidden' : 'Provider Visible')
            ->success()
            ->body("{$config->provider_name} has been " . ($config->is_hidden ? 'hidden from failover chain.' : 'restored to failover chain.'))
            ->send();

        $this->mount();
    }

    public function mount(): void
    {
        // Fetch FIN models from API
        $this->fetchFobignModels();

        // Check if Fobign is enabled
        $fobignConfig = AiConfig::where('provider_key', 'fobign')->first();
        $this->fobignEnabled = $fobignConfig && $fobignConfig->is_active;

        $settings = AiAlgorithmSetting::getActive();
        $state = $settings->toArray();

        // Load provider configs — Fobign always first
        $providers = AiConfig::orderBy('priority', 'asc')->get()->toArray();
        $state['providers'] = $providers;

        // Hidden provider IDs for UI
        $hiddenIds = Cache::get('ai_provider_hidden_ids', []);
        $state['hidden_provider_ids'] = $hiddenIds;

        $this->form->fill($state);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_failures')
                ->label('Reset Provider Failures')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    \App\Services\Ai\AiManagerService::resetFailures();
                    $this->mount();
                    Notification::make()
                        ->title('Failures Reset Successfully')
                        ->success()
                        ->body('All provider failure counters have been reset to zero.')
                        ->send();
                }),
        ];
    }

    public function form(Form $form): Form
    {
        $isFobignModel = fn (?array $state): bool => ($state['provider_key'] ?? '') === 'fobign';

        return $form->schema([
            Forms\Components\Tabs::make('AI Brain Panel')
                ->tabs([
                    // Tab 1: Multi-AI Failover Pipelines
                    Forms\Components\Tabs\Tab::make('Multi-AI Failover Chain')
                        ->icon('heroicon-o-squares-plus')
                        ->schema([
                            Forms\Components\Repeater::make('providers')
                                ->label('Available AI Engines')
                                ->schema([
                                    // Row 1: Provider info + Enable
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('provider_name')
                                                ->label('Engine Name')
                                                ->disabled()
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('provider_key')
                                                ->label('Unique Key')
                                                ->disabled()
                                                ->dehydrated(),
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Enable Engine')
                                                ->inline(false),
                                        ]),

                                    // Row 2: Priority + Ratings
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Select::make('priority')
                                                ->label('Failover Order')
                                                ->options([
                                                    1 => '1 - Optimized Engine (Primary)',
                                                    2 => '2 - Backup Engine',
                                                    3 => '3 - Fallback Engine',
                                                    4 => '4 - Last Resort',
                                                ])
                                                ->disabled(fn (Forms\Get $get): bool => $get('provider_key') === 'fobign')
                                                ->required(),
                                            Forms\Components\Select::make('cost_rating')
                                                ->label('Cost Rating (1-5)')
                                                ->options([1 => '1 (Low)', 2 => '2', 3 => '3', 4 => '4', 5 => '5 (High)']),
                                            Forms\Components\Select::make('speed_rating')
                                                ->label('Speed Rating (1-5)')
                                                ->options([1 => '1 (Slow)', 2 => '2', 3 => '3', 4 => '4', 5 => '5 (Fast)']),
                                            Forms\Components\Select::make('accuracy_rating')
                                                ->label('Accuracy Rating (1-5)')
                                                ->options([1 => '1 (Low)', 2 => '2', 3 => '3', 4 => '4', 5 => '5 (High)']),
                                        ]),

                                    // Row 3: Model + Timeout + API Key
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            // For Fobign: dynamic model dropdown from API
                                            Forms\Components\Select::make('model_code')
                                                ->label(fn (Forms\Get $get): string => $get('provider_key') === 'fobign' ? 'FIN Model (Live from API)' : 'Model Code')
                                                ->options(fn (Forms\Get $get): array => $get('provider_key') === 'fobign'
                                                    ? array_combine($this->fobignModels, $this->fobignModels)
                                                    : []
                                                )
                                                ->searchable()
                                                ->required()
                                                ->visible(fn (Forms\Get $get): bool => $get('provider_key') === 'fobign'),

                                            // For non-Fobign: text input
                                            Forms\Components\TextInput::make('model_code')
                                                ->label('Model Code')
                                                ->placeholder('e.g. gemini-1.5-flash')
                                                ->required()
                                                ->visible(fn (Forms\Get $get): bool => $get('provider_key') !== 'fobign'),

                                            Forms\Components\TextInput::make('timeout')
                                                ->label('Timeout (seconds)')
                                                ->numeric()
                                                ->default(30)
                                                ->required(),
                                            Forms\Components\TextInput::make('api_key')
                                                ->label('API Secret Key')
                                                ->password()
                                                ->revealable()
                                                ->required(),
                                        ]),

                                    // Row 4: API URL + System Instruction
                                    Forms\Components\TextInput::make('api_url')
                                        ->label('Base API Endpoint URL')
                                        ->placeholder('Leave blank for default provider URL'),

                                    Forms\Components\Textarea::make('system_instruction')
                                        ->label('System Instruction')
                                        ->rows(2),

                                    // Row 5: Failure info
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('failure_count')
                                                ->label('System Failure Count')
                                                ->disabled()
                                                ->dehydrated(),
                                            Forms\Components\DateTimePicker::make('last_failed_at')
                                                ->label('Last Fail Date')
                                                ->disabled()
                                                ->dehydrated(),
                                        ]),
                                ])
                                ->columns(1)
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->reorderable(false)
                                ->itemLabel(fn (array $state): ?string => match ($state['provider_key'] ?? '') {
                                    'fobign' => 'Fobign FIN — Optimized Engine (Locked Priority 1)',
                                    default => ($state['provider_name'] ?? '') . ' (Priority: ' . ($state['priority'] ?? '?') . ')',
                                }),
                        ]),

                    // Tab 2: Recommendation Rules Manager
                    Forms\Components\Tabs\Tab::make('AI Recommendation Weights')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Forms\Components\Section::make('Relevance Weights Breakdown (Should total 100%)')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('skills_matching_weight')
                                                ->label('Skill Overlap Matching Weight')
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            Forms\Components\TextInput::make('saved_jobs_weight')
                                                ->label('Saved Job Priority Weight')
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            Forms\Components\TextInput::make('company_follows_weight')
                                                ->label('Company Engagement Weight')
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            Forms\Components\TextInput::make('recent_activity_weight')
                                                ->label('Platform Recency Weight')
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            Forms\Components\TextInput::make('location_relevance_weight')
                                                ->label('Location Relevance Weight')
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                            Forms\Components\TextInput::make('premium_boosting_weight')
                                                ->label('Premium Account Boost Weight')
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required(),
                                        ]),
                                ]),
                        ]),

                    // Tab 3: Behavior Tracking Panel
                    Forms\Components\Tabs\Tab::make('Behavior Telemetry Control')
                        ->icon('heroicon-o-eye')
                        ->schema([
                            Forms\Components\Section::make('Toggle Telemetry Parameters')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Toggle::make('track_job_views')
                                                ->label('Track Job Clicks & Views')
                                                ->helperText('Record times a user opens detail cards'),
                                            Forms\Components\Toggle::make('track_job_saves')
                                                ->label('Track Bookmark Saves')
                                                ->helperText('Record when jobs are bookmarked'),
                                            Forms\Components\Toggle::make('track_applications')
                                                ->label('Track Form Applications')
                                                ->helperText('Record proposal submissions'),

                                            Forms\Components\Toggle::make('track_profile_visits')
                                                ->label('Track Profile Impressions')
                                                ->helperText('Record when profiles are reviewed'),
                                            Forms\Components\Toggle::make('track_company_visits')
                                                ->label('Track Company Visits')
                                                ->helperText('Record when brand profiles are browsed'),
                                            Forms\Components\Toggle::make('track_search_history')
                                                ->label('Track Search Queries')
                                                ->helperText('Record live input keyword histories'),

                                            Forms\Components\Toggle::make('track_scroll_depth')
                                                ->label('Track Scroll Depth')
                                                ->helperText('Log average reading depths on pages'),
                                            Forms\Components\Toggle::make('track_click_patterns')
                                                ->label('Track Interactive Clicks')
                                                ->helperText('Capture user click heat patterns'),
                                            Forms\Components\Toggle::make('track_session_engagement')
                                                ->label('Track Engagement Times')
                                                ->helperText('Record average seconds active'),
                                        ]),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
        ])->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();

        // 1. Update AI Algorithm settings
        $settings = AiAlgorithmSetting::getActive();
        $settings->update([
            'skills_matching_weight' => $formData['skills_matching_weight'],
            'saved_jobs_weight' => $formData['saved_jobs_weight'],
            'company_follows_weight' => $formData['company_follows_weight'],
            'recent_activity_weight' => $formData['recent_activity_weight'],
            'location_relevance_weight' => $formData['location_relevance_weight'],
            'premium_boosting_weight' => $formData['premium_boosting_weight'],

            'track_job_views' => $formData['track_job_views'],
            'track_job_saves' => $formData['track_job_saves'],
            'track_applications' => $formData['track_applications'],
            'track_profile_visits' => $formData['track_profile_visits'],
            'track_company_visits' => $formData['track_company_visits'],
            'track_search_history' => $formData['track_search_history'],
            'track_scroll_depth' => $formData['track_scroll_depth'],
            'track_click_patterns' => $formData['track_click_patterns'],
            'track_session_engagement' => $formData['track_session_engagement'],
        ]);

        // 2. Update AI Config providers — Fobign priority is always locked to 1
        foreach ($formData['providers'] ?? [] as $provData) {
            $priority = $provData['priority'] ?? 99;
            // Force Fobign to priority 1 always
            if ($provData['provider_key'] === 'fobign') {
                $priority = 1;
            }

            AiConfig::where('provider_key', $provData['provider_key'])->update([
                'is_active' => $provData['is_active'],
                'priority' => $priority,
                'cost_rating' => $provData['cost_rating'],
                'speed_rating' => $provData['speed_rating'],
                'accuracy_rating' => $provData['accuracy_rating'],
                'model_code' => $provData['model_code'],
                'timeout' => $provData['timeout'],
                'api_key' => $provData['api_key'],
                'api_url' => $provData['api_url'] ?? null,
                'system_instruction' => $provData['system_instruction'] ?? null,
            ]);
        }

        // Clear model cache when settings change
        Cache::forget('fobign_models_list');

        Notification::make()
            ->title('AI Algorithms Re-configured!')
            ->success()
            ->body("Fobign FIN locked as Priority 1. Weights, telemetry, and failover pipelines updated.")
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_ai_settings');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_ai_settings');
    }
}

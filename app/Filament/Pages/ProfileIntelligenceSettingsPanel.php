<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ProfileIntelligenceSettingsPanel extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'System';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Profile Strength Settings';
    protected static ?string $slug = 'system-settings/profile-intelligence';
    protected static string $view = 'filament.pages.profile-intelligence-settings-panel';

    public ?array $data = [];

    public function mount(): void
    {
        // Load settings from database
        $keys = [
            'weight_basic_info' => 10,
            'weight_resume' => 15,
            'weight_skills' => 10,
            'weight_experience' => 15,
            'weight_education' => 10,
            'weight_certifications' => 10,
            'weight_avatar' => 10,
            'weight_portfolio' => 10,
            'weight_social_links' => 5,
            'weight_bio' => 5,
            'min_required_strength' => 45,
            'restrict_low_strength_apply' => false,
        ];

        foreach ($keys as $key => $default) {
            $val = Setting::where('key', $key)->value('value');
            if ($val !== null) {
                if ($key === 'restrict_low_strength_apply') {
                    $this->data[$key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $this->data[$key] = (int)$val;
                }
            } else {
                $this->data[$key] = $default;
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Candidate Profile Completion Weights (Must add up to 100%)')
                    ->schema([
                        Forms\Components\TextInput::make('weight_basic_info')
                            ->label('Basic Information (Phone, City)')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_resume')
                            ->label('Resume Uploaded')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_skills')
                            ->label('Skills Selected (>=3)')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_experience')
                            ->label('Work Experience Added')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_education')
                            ->label('Education History')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_certifications')
                            ->label('Professional Certifications')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_avatar')
                            ->label('Profile Photo')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_portfolio')
                            ->label('Portfolio / Projects Showcase')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_social_links')
                            ->label('Social Media / Portfolio Links')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\TextInput::make('weight_bio')
                            ->label('Bio / About Me')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                    ])->columns(2),

                Forms\Components\Section::make('Visibility & Application Controls')
                    ->schema([
                        Forms\Components\TextInput::make('min_required_strength')
                            ->label('Minimum Required Profile Strength to Apply (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)->maxValue(100),
                        Forms\Components\Toggle::make('restrict_low_strength_apply')
                            ->label('Restrict low-strength candidates from applying to premium jobs')
                            ->onColor('danger'),
                    ])->columns(2)
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        // Calculate total
        $sum = $state['weight_basic_info'] + $state['weight_resume'] + $state['weight_skills'] +
               $state['weight_experience'] + $state['weight_education'] + $state['weight_certifications'] +
               $state['weight_avatar'] + $state['weight_portfolio'] + $state['weight_social_links'] +
               $state['weight_bio'];

        if ($sum !== 100) {
            Notification::make()
                ->title('Warning')
                ->body("Your weights sum to {$sum}%. Ideally they should equal exactly 100%. Form saved successfully anyway.")
                ->warning()
                ->send();
        }

        foreach ($state as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string)$value]
            );
        }

        Notification::make()
            ->title('Settings Saved')
            ->body('Profile completion weights and visibility restrictions updated successfully!')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_profile_intelligence');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ThemeSyncSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.theme-sync-settings';

    protected static ?string $title = 'Theme Sync Settings';

    protected static ?string $slug = 'system-settings/theme-sync';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all();
        $data = [];
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            $data[$setting->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
        }
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Brand Colors')
                ->description('Configure the primary, secondary, and accent colors used across the frontend')
                ->icon('heroicon-o-swatch')
                ->schema([
                    Forms\Components\ColorPicker::make('theme_primary_color')
                        ->label('Primary Color')
                        ->default('#2563EB')
                        ->helperText('Main brand color used for buttons, links, and key UI elements'),
                    Forms\Components\ColorPicker::make('theme_secondary_color')
                        ->label('Secondary Color')
                        ->default('#7C3AED')
                        ->helperText('Secondary color used for accents and highlights'),
                    Forms\Components\ColorPicker::make('theme_accent_color')
                        ->label('Accent Color')
                        ->default('#F59E0B')
                        ->helperText('Accent color used for notifications, badges, and call-to-action elements'),
                ])->columns(3),

            Forms\Components\Section::make('Layout & Typography')
                ->description('Adjust border radius, font size, and font family for the frontend theme')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    Forms\Components\Select::make('global_font_size')
                        ->label('Global Font Size')
                        ->options([
                            '14px' => '14px — Small',
                            '16px' => '16px — Default',
                            '18px' => '18px — Medium',
                            '20px' => '20px — Large',
                            '22px' => '22px — Extra Large (Recommended)',
                            '24px' => '24px — Display',
                        ])
                        ->default('22px')
                        ->helperText('Base font size for the entire application'),
                    Forms\Components\Select::make('phone_font_size')
                        ->label('Phone Font Size (< 640px)')
                        ->options([
                            '10px' => '10px — Tiny',
                            '11px' => '11px — Small',
                            '12px' => '12px — Compact',
                            '13px' => '13px — Default',
                            '14px' => '14px — Medium',
                            '15px' => '15px — Large',
                            '16px' => '16px — Extra Large',
                        ])
                        ->default('13px')
                        ->helperText('Font size for mobile phones (screen width < 640px)'),
                    Forms\Components\Select::make('tablet_font_size')
                        ->label('Tablet Font Size (641px - 1024px)')
                        ->options([
                            '12px' => '12px — Small',
                            '13px' => '13px — Compact',
                            '14px' => '14px — Default',
                            '15px' => '15px — Medium',
                            '16px' => '16px — Large',
                            '17px' => '17px — Extra Large',
                            '18px' => '18px — Display',
                        ])
                        ->default('14px')
                        ->helperText('Font size for tablets (screen width 641px - 1024px)'),
                    Forms\Components\Select::make('theme_border_radius')
                        ->label('Border Radius')
                        ->options([
                            '0' => 'None (Sharp)',
                            '0.25rem' => 'Small (4px)',
                            '0.375rem' => 'Medium (6px)',
                            '0.5rem' => 'Large (8px)',
                            '0.75rem' => 'Extra Large (12px)',
                            '9999px' => 'Full (Pill)',
                        ])
                        ->default('0.375rem')
                        ->helperText('Controls the roundness of cards, buttons, and inputs'),
                    Forms\Components\Select::make('theme_font_family')
                        ->label('Font Family')
                        ->options([
                            'Inter' => 'Inter (Default)',
                            'Poppins' => 'Poppins',
                            'Roboto' => 'Roboto',
                            'Open Sans' => 'Open Sans',
                            'Lato' => 'Lato',
                            'Nunito' => 'Nunito',
                            'DM Sans' => 'DM Sans',
                        ])
                        ->default('Inter')
                        ->helperText('Primary font used throughout the application'),
                ])->columns(3),

            Forms\Components\Section::make('Analytics & Tracking')
                ->description('Configure Google Tag Manager and Facebook Pixel tracking')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\TextInput::make('gtm_id')
                        ->label('Google Tag Manager ID')
                        ->placeholder('GTM-XXXXXXX')
                        ->helperText('Your GTM container ID from tagmanager.google.com'),
                    Forms\Components\TextInput::make('facebook_pixel_id')
                        ->label('Facebook Pixel ID')
                        ->placeholder('123456789012345')
                        ->helperText('Your Meta/Facebook Pixel ID from Events Manager'),
                ])->columns(2),

            Forms\Components\Section::make('Appearance')
                ->description('Control dark mode and other visual preferences')
                ->icon('heroicon-o-sun')
                ->schema([
                    Forms\Components\Toggle::make('theme_dark_mode_enabled')
                        ->label('Dark Mode Support')
                        ->default(true)
                        ->helperText('Allow users to toggle between light and dark mode'),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Setting::flushCache();

        Notification::make()
            ->title('Theme sync settings saved successfully!')
            ->success()
            ->send();
    }
}

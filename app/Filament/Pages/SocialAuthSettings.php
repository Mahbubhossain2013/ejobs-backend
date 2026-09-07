<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SocialAuthSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Social Auth';
    protected static ?string $title = 'Social Authentication Settings';
    protected static ?string $slug = 'system-settings/social-auth-settings';
    protected static ?int $navigationSort = 12;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $description = 'Configure Google and Facebook social login credentials.';

    protected static string $view = 'filament.pages.social-auth-settings';

    public ?array $data = [];

    public bool $google_enabled = false;
    public ?string $google_client_id = null;
    public ?string $google_client_secret = null;
    public bool $facebook_enabled = false;
    public ?string $facebook_client_id = null;
    public ?string $facebook_client_secret = null;

    public function mount(): void
    {
        $this->form->fill([
            'google_enabled' => Setting::where('key', 'social_auth_google_enabled')->first()?->value === 'true',
            'google_client_id' => Setting::where('key', 'social_auth_google_client_id')->first()?->value ?? '',
            'google_client_secret' => Setting::where('key', 'social_auth_google_client_secret')->first()?->value ?? '',
            'facebook_enabled' => Setting::where('key', 'social_auth_facebook_enabled')->first()?->value === 'true',
            'facebook_client_id' => Setting::where('key', 'social_auth_facebook_client_id')->first()?->value ?? '',
            'facebook_client_secret' => Setting::where('key', 'social_auth_facebook_client_secret')->first()?->value ?? '',
        ]);
    }

    public function getFormSchema(): array
    {
        $appUrl = config('app.url', env('APP_URL', 'http://localhost:8000'));

        return [
            // ── Google Section ──
            Forms\Components\Section::make('Google Login')
                ->description('Configure Google OAuth 2.0 credentials for social login')
                ->icon('heroicon-m-globe-alt')
                ->iconColor('success')
                ->schema([
                    Forms\Components\Toggle::make('google_enabled')
                        ->label('Enable Google Login')
                        ->default(false)
                        ->inline(false),

                    Forms\Components\TextInput::make('google_client_id')
                        ->label('Google Client ID')
                        ->placeholder('123456789-xxxx.apps.googleusercontent.com')
                        ->helperText('Found in Google Cloud Console > APIs & Services > Credentials')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('google_client_secret')
                        ->label('Google Client Secret')
                        ->password()
                        ->revealable()
                        ->placeholder('GOCSPX-xxxxxxxxxxxxxxxxxxxx')
                        ->helperText('Click "Show" to reveal the secret value')
                        ->maxLength(255),
                ])->columns(1),

            // ── Facebook Section ──
            Forms\Components\Section::make('Facebook Login')
                ->description('Configure Facebook OAuth credentials for social login')
                ->icon('heroicon-m-users')
                ->iconColor('info')
                ->schema([
                    Forms\Components\Toggle::make('facebook_enabled')
                        ->label('Enable Facebook Login')
                        ->default(false)
                        ->inline(false),

                    Forms\Components\TextInput::make('facebook_client_id')
                        ->label('Facebook App ID')
                        ->placeholder('123456789012345')
                        ->helperText('Found in Facebook Developers > App Settings > Basic')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('facebook_client_secret')
                        ->label('Facebook App Secret')
                        ->password()
                        ->revealable()
                        ->placeholder('abcdef1234567890abcdef1234567890')
                        ->helperText('Click "Show" to reveal the secret value')
                        ->maxLength(255),
                ])->columns(1),

            // ── Setup Instructions ──
            Forms\Components\Section::make('Redirect URI Configuration')
                ->description('Configure these redirect URIs in your OAuth provider settings')
                ->icon('heroicon-m-link')
                ->schema([
                    Forms\Components\Placeholder::make('google_redirect')
                        ->label('Google Redirect URI')
                        ->content(fn() => new \Illuminate\Support\HtmlString(
                            '<div class="flex items-center gap-2">'
                                . '<code class="bg-muted px-2 py-1 rounded text-sm font-mono">' . $appUrl . '/api/auth/google/callback</code>'
                                . '<span class="text-xs text-muted-foreground">— Copy this to Google Cloud Console</span>'
                                . '</div>'
                        )),
                    Forms\Components\Placeholder::make('facebook_redirect')
                        ->label('Facebook Redirect URI')
                        ->content(fn() => new \Illuminate\Support\HtmlString(
                            '<div class="flex items-center gap-2">'
                                . '<code class="bg-muted px-2 py-1 rounded text-sm font-mono">' . $appUrl . '/api/auth/facebook/callback</code>'
                                . '<span class="text-xs text-muted-foreground">— Copy this to Facebook Developers</span>'
                                . '</div>'
                        )),
                    Forms\Components\Placeholder::make('how_to')
                        ->label('How to Setup')
                        ->content(fn() => new \Illuminate\Support\HtmlString(
                            '<div class="text-sm space-y-3">'
                                . '<div class="flex items-start gap-2">'
                                . '<span class="font-bold text-primary">Google:</span>'
                                . '<span>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-primary underline">Google Cloud Console</a> → Create OAuth 2.0 Client ID → Add the redirect URI above → Copy Client ID and Secret.</span>'
                                . '</div>'
                                . '<div class="flex items-start gap-2">'
                                . '<span class="font-bold text-primary">Facebook:</span>'
                                . '<span>Go to <a href="https://developers.facebook.com/apps/" target="_blank" class="text-primary underline">Facebook Developers</a> → Create App → Add Facebook Login → Set the redirect URI above → Copy App ID and Secret.</span>'
                                . '</div>'
                                . '</div>'
                        )),
                ])->columns(1),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // ── Validation: if enabled, credentials must be provided ──
        if ($data['google_enabled'] && empty(trim($data['google_client_id'] ?? ''))) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Google Client ID is required when Google Login is enabled.')
                ->send();
            return;
        }
        if ($data['google_enabled'] && empty(trim($data['google_client_secret'] ?? ''))) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Google Client Secret is required when Google Login is enabled.')
                ->send();
            return;
        }
        if ($data['facebook_enabled'] && empty(trim($data['facebook_client_id'] ?? ''))) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Facebook App ID is required when Facebook Login is enabled.')
                ->send();
            return;
        }
        if ($data['facebook_enabled'] && empty(trim($data['facebook_client_secret'] ?? ''))) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Facebook App Secret is required when Facebook Login is enabled.')
                ->send();
            return;
        }

        // ── Auto-enable: if credentials are filled, auto-enable the provider ──
        if (!empty(trim($data['google_client_id'] ?? '')) && !empty(trim($data['google_client_secret'] ?? ''))) {
            $data['google_enabled'] = true;
        }
        if (!empty(trim($data['facebook_client_id'] ?? '')) && !empty(trim($data['facebook_client_secret'] ?? ''))) {
            $data['facebook_enabled'] = true;
        }

        // ── Save to database ──
        $settingsToSave = [
            'social_auth_google_enabled' => $data['google_enabled'] ? 'true' : 'false',
            'social_auth_google_client_id' => trim($data['google_client_id'] ?? ''),
            'social_auth_google_client_secret' => trim($data['google_client_secret'] ?? ''),
            'social_auth_facebook_enabled' => $data['facebook_enabled'] ? 'true' : 'false',
            'social_auth_facebook_client_id' => trim($data['facebook_client_id'] ?? ''),
            'social_auth_facebook_client_secret' => trim($data['facebook_client_secret'] ?? ''),
        ];

        foreach ($settingsToSave as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // ── Update runtime config for Socialite ──
        config([
            'services.google.client_id' => $data['google_client_id'] ?? '',
            'services.google.client_secret' => $data['google_client_secret'] ?? '',
            'services.facebook.client_id' => $data['facebook_client_id'] ?? '',
            'services.facebook.client_secret' => $data['facebook_client_secret'] ?? '',
        ]);

        // ── Update form state (reflect auto-enable) ──
        $this->form->fill($data);

        $enabledProviders = [];
        if ($data['google_enabled']) $enabledProviders[] = 'Google';
        if ($data['facebook_enabled']) $enabledProviders[] = 'Facebook';

        Notification::make()
            ->title('Social Auth Settings Saved')
            ->success()
            ->body(!empty($enabledProviders)
                ? 'Enabled providers: ' . implode(', ', $enabledProviders)
                : 'No providers enabled. Enter credentials to enable social login.')
            ->send();
    }
}

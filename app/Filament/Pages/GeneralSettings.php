<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class GeneralSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.general-settings';
    protected static ?string $title = 'General Settings';
    protected static ?string $slug = 'system-settings/general-settings';
    protected static bool $shouldRegisterNavigation = false; // Accessible via Dashboard Cards Hub

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
            Forms\Components\Tabs::make('General Configuration')->tabs([

                Forms\Components\Tabs\Tab::make('Core')->schema([
                    Forms\Components\Section::make('Basic Information')
                        ->description('Configure the core settings for your application')
                        ->schema([
                            Forms\Components\TextInput::make('site_name')
                                ->label('Site Name*')
                                ->required()
                                ->helperText('This will appear in the browser title and various places throughout the application'),
                            Forms\Components\Select::make('timezone')
                                ->label('Default Timezone*')
                                ->options([
                                    'UTC' => 'UTC',
                                    'Asia/Dhaka' => 'Asia/Dhaka',
                                    'America/New_York' => 'America/New_York',
                                    'Europe/London' => 'Europe/London',
                                ])
                                ->default('Asia/Dhaka')
                                ->helperText('All dates and times will be displayed according to this timezone'),
                        ]),
                    Forms\Components\Section::make('Currency & Financial')
                        ->description('Configure currency settings')
                        ->schema([
                            Forms\Components\Select::make('default_currency')
                                ->label('Default Currency*')
                                ->options(fn () => \App\Models\Currency::where('enabled', true)->pluck('code', 'code')->toArray())
                                ->default('BDT')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $currency = \App\Models\Currency::where('code', $state)->first();
                                    if ($currency) {
                                        $set('currency_symbol', $currency->symbol);
                                    }
                                })
                                ->helperText('Currency used for all financial transactions'),
                            Forms\Components\TextInput::make('currency_symbol')
                                ->label('Currency Symbol')
                                ->disabled()
                                ->dehydrated()
                                ->helperText('Automatically set based on selected currency'),
                        ]),
                    Forms\Components\Section::make('Localization')
                        ->description('Configure language and date format preferences')
                        ->schema([
                            Forms\Components\Select::make('default_language')
                                ->label('Default Language*')
                                ->options(['en' => 'English', 'bn' => 'Bengali'])
                                ->default('en')
                                ->helperText('Checkout interface language'),
                            Forms\Components\Select::make('week_starts_on')
                                ->label('Week Starts On*')
                                ->options([
                                    'Saturday' => 'Saturday',
                                    'Sunday' => 'Sunday',
                                    'Monday' => 'Monday'
                                ])
                                ->default('Saturday')
                                ->helperText('Determines first day of week in reports'),
                        ]),
                    Forms\Components\Section::make('Analytics & Tracking')
                        ->description('Set up tools to monitor traffic and user behavior')
                        ->schema([
                            Forms\Components\TextInput::make('gtm_id')
                                ->label('Google Tag Manager ID')
                                ->placeholder('GTM-XXXXXXX')
                                ->helperText('Your unique Google Tag Manager container ID'),
                            Forms\Components\TextInput::make('facebook_pixel_id')
                                ->label('Facebook Pixel ID')
                                ->placeholder('123456789012345')
                                ->helperText('Your Meta/Facebook Pixel ID (15-16 digit number from Events Manager)'),
                        ])->columns(2),
                ]),

                Forms\Components\Tabs\Tab::make('Business Details')->schema([
                    Forms\Components\Section::make('Company Information')
                        ->description('Enter your business details for invoices and documentation')
                        ->schema([
                            Forms\Components\TextInput::make('company_address')
                                ->label('Street Address')
                                ->placeholder('123 Business Ave')
                                ->helperText('Street address including building/suite number'),
                            Forms\Components\TextInput::make('city')
                                ->label('City/Town')
                                ->placeholder('New York')
                                ->helperText('City or town name'),
                            Forms\Components\TextInput::make('zip_code')
                                ->label('Postal/ZIP Code')
                                ->placeholder('10001')
                                ->helperText('Postal or ZIP code'),
                            Forms\Components\Select::make('country')
                                ->label('Country')
                                ->options([
                                    'Bangladesh' => 'Bangladesh',
                                    'United States' => 'United States',
                                    'United Kingdom' => 'United Kingdom',
                                    'Canada' => 'Canada'
                                ])
                                ->helperText('Country where your business is registered'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Contact & Social')->schema([
                    Forms\Components\Section::make('Support Contact Information')
                        ->description('Configure support channels for your customers')
                        ->schema([
                            Forms\Components\TextInput::make('support_phone')
                                ->label('Support Phone Number')
                                ->helperText('Customer service phone number (with country code)'),
                            Forms\Components\TextInput::make('support_email')
                                ->label('Support Email Address')
                                ->email()
                                ->helperText('Primary contact email for support inquiries'),
                            Forms\Components\TextInput::make('support_website')
                                ->label('Support Website')
                                ->url()
                                ->placeholder('https://')
                                ->helperText('Link to your support portal or help center'),
                        ]),
                    Forms\Components\Section::make('Social Media Profiles')
                        ->description('Link your business social media accounts')
                        ->schema([
                            Forms\Components\TextInput::make('facebook_page')
                                ->label('Facebook Page')
                                ->placeholder('https://facebook.com/'),
                            Forms\Components\TextInput::make('facebook_messenger')
                                ->label('Facebook Messenger')
                                ->placeholder('https://m.me/'),
                            Forms\Components\TextInput::make('whatsapp_number')
                                ->label('WhatsApp Number')
                                ->placeholder('+1234567890'),
                            Forms\Components\TextInput::make('telegram_channel')
                                ->label('Telegram')
                                ->placeholder('https://t.me/'),
                            Forms\Components\TextInput::make('youtube_channel')
                                ->label('YouTube Channel')
                                ->placeholder('https://youtube.com/'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Logo & Favicon')->schema([
                    Forms\Components\Section::make('Logo & Favicon')
                        ->description('Configure the logos used throughout your application')
                        ->schema([
                            Forms\Components\FileUpload::make('site_logo')
                                ->label('Primary Logo')
                                ->image()
                                ->directory('brand')
                                ->helperText('Main logo used in header and emails (Light Mode)'),
                            Forms\Components\FileUpload::make('site_logo_dark')
                                ->label('Secondary Logo (Dark Mode)')
                                ->image()
                                ->directory('brand')
                                ->helperText('Secondary logo used on dark backgrounds'),
                            Forms\Components\FileUpload::make('round_logo')
                                ->label('Round Logo')
                                ->image()
                                ->directory('brand')
                                ->helperText('Used for avatars and round logo displays'),
                            Forms\Components\FileUpload::make('site_favicon')
                                ->label('Favicon')
                                ->image()
                                ->directory('brand')
                                ->helperText('Browser tab icon (Light Mode)'),
                            Forms\Components\FileUpload::make('site_favicon_dark')
                                ->label('Secondary Favicon (Dark Mode)')
                                ->image()
                                ->directory('brand')
                                ->helperText('Favicon icon used on dark backgrounds'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Theme Settings')->schema([
                    Forms\Components\Section::make('Navbar Styling')
                        ->description('Customize navbar background, text, and hover colors')
                        ->schema([
                            Forms\Components\ColorPicker::make('nav_bg')
                                ->label('Navbar Background Color')
                                ->default('#076938'),
                            Forms\Components\ColorPicker::make('nav_bg_dark')
                                ->label('Navbar Background Color (Dark Mode)')
                                ->default('#076938'),
                            Forms\Components\ColorPicker::make('nav_text_color')
                                ->label('Navbar Text Color')
                                ->default('#FFFFFF')
                                ->helperText('Color of navigation links and text in the navbar'),
                            Forms\Components\ColorPicker::make('nav_text_hover')
                                ->label('Navbar Text Hover Color')
                                ->default('#F59E0B')
                                ->helperText('Color of navigation links on hover'),
                        ])->columns(2),

                    Forms\Components\Section::make('Theme & UI Customization')
                        ->description('Configure dynamic border-radius shapes, accent colors, and styling parameters for JobBazar.bd')
                        ->schema([
                            Forms\Components\Select::make('border_radius')
                                ->label('Border Radius Options')
                                ->options([
                                    '4px' => '4px (Compact)',
                                    '6px' => '6px (Default)',
                                    '8px' => '8px (Sleek)',
                                    '12px' => '12px (Rounded)',
                                    '16px' => '16px (Extremely Rounded)',
                                    '9999px' => 'Full Pill (Pancake)',
                                ])
                                ->default('6px')
                                ->helperText('Select a custom border radius to adjust cards, inputs, and button designs across the entire platform'),
                            Forms\Components\ColorPicker::make('primary_color')
                                ->label('Primary Brand Color')
                                ->default('#2563EB')
                                ->helperText('Accent color used for active badges, hover lines, and dynamic UI elements'),
                            Forms\Components\ColorPicker::make('button_bg')
                                ->label('Primary Button Background')
                                ->default('#2563EB'),
                            Forms\Components\Select::make('english_font')
                                ->label('English Font Family')
                                ->options([
                                    'Inter' => 'Inter (Default Sans)',
                                    'Outfit' => 'Outfit (Modern Sans)',
                                    'Poppins' => 'Poppins (Geometric Sans)',
                                    'Roboto' => 'Roboto (Neo-Grotesque)',
                                    'Open Sans' => 'Open Sans (Neutral Sans)',
                                    'Montserrat' => 'Montserrat (Classic Sans)',
                                    'Lato' => 'Lato (Warm Sans)',
                                    'Playfair Display' => 'Playfair Display (Elegant Serif)',
                                ])
                                ->default('Inter')
                                ->required()
                                ->helperText('Select the primary font used for English text'),
                            Forms\Components\Select::make('bangla_font')
                                ->label('Bangla Font Family')
                                ->options([
                                    'Hind Siliguri' => 'Hind Siliguri (Default Sans)',
                                    'Noto Sans Bengali' => 'Noto Sans Bengali (Sleek Sans)',
                                    'SolaimanLipi' => 'SolaimanLipi (Classic Serif)',
                                    'Kalpurush' => 'Kalpurush (Classic Sans)',
                                    'Mina' => 'Mina (Playful Sans)',
                                    'Atma' => 'Atma (Casual Sans)',
                                    'Galada' => 'Galada (Calligraphy / Cursive)',
                                ])
                                ->default('Hind Siliguri')
                                ->required()
                                ->helperText('Select the primary font used for Bangla text'),
                            Forms\Components\Select::make('font_size')
                                ->label('Global Font Size*')
                                ->options([
                                    '12px' => '12px - Small',
                                    '13px' => '13px - Compact',
                                    '14px' => '14px - Small-Medium',
                                    '15px' => '15px - Medium-Small',
                                    '16px' => '16px - Default',
                                    '17px' => '17px - Medium-Large',
                                    '18px' => '18px - Large',
                                    '20px' => '20px - Extra Large',
                                    '22px' => '22px - Display',
                                ])
                                ->default('16px')
                                ->required()
                                ->helperText('Set the base font size for the entire application'),
                            Forms\Components\Select::make('phone_font_size')
                                ->label('Phone Font Size (< 640px)')
                                ->options([
                                    '10px' => '10px - Tiny',
                                    '11px' => '11px - Small',
                                    '12px' => '12px - Compact',
                                    '13px' => '13px - Default',
                                    '14px' => '14px - Medium',
                                    '15px' => '15px - Large',
                                    '16px' => '16px - Extra Large',
                                ])
                                ->default('13px')
                                ->required()
                                ->helperText('Font size for mobile phones (screen width < 640px)'),
                            Forms\Components\Select::make('tablet_font_size')
                                ->label('Tablet Font Size (641px - 1024px)')
                                ->options([
                                    '12px' => '12px - Small',
                                    '13px' => '13px - Compact',
                                    '14px' => '14px - Default',
                                    '15px' => '15px - Medium',
                                    '16px' => '16px - Large',
                                    '17px' => '17px - Extra Large',
                                    '18px' => '18px - Display',
                                ])
                                ->default('14px')
                                ->required()
                                ->helperText('Font size for tablets (screen width 641px - 1024px)'),
                        ]),
                ]),

            ])
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Notification::make()->title('General settings saved successfully!')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_general_settings');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}

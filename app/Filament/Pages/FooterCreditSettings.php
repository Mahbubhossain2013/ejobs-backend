<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\StaticPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class FooterCreditSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static string $view = 'filament.pages.footer-credit-settings';

    protected static ?string $title = 'Footer Settings';

    protected static ?string $slug = 'menu/footer-settings';

    protected static bool $shouldRegisterNavigation = true;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all();
        $data = [];
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            $data[$setting->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
        }

        $defaults = [
            'footer_credit_enabled' => '1',
            'footer_credit_text' => 'Developed by NEXTIN',
            'footer_credit_url' => 'https://nextin.fobign.com',
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
                $data[$key] = $default;
            }
        }

        // Pre-fill copyright text with site_name-based default when empty
        if (!isset($data['footer_copyright_text']) || $data['footer_copyright_text'] === '' || $data['footer_copyright_text'] === null) {
            $siteName = $data['site_name'] ?? config('app.name', 'Job Portal');
            $data['footer_copyright_text'] = '&copy; ' . date('Y') . ' ' . $siteName . '. All rights reserved.';
        }

        // Load static pages content
        $pages = StaticPage::whereIn('slug', ['about', 'contact', 'privacy', 'terms'])->get();
        foreach ($pages as $page) {
            $data[$page->slug . '_title_en'] = $page->title_en;
            $data[$page->slug . '_title_bn'] = $page->title_bn;
            $data[$page->slug . '_content_en'] = $page->content_en;
            $data[$page->slug . '_content_bn'] = $page->content_bn;
        }

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Footer Configuration')
                ->description('Manage the copyright and credit text displayed in the application footer')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\TextInput::make('footer_copyright_text')
                        ->label('Copyright Text')
                        ->placeholder('e.g. MyCompany. All rights reserved.')
                        ->helperText('Customize the copyright line. Leave empty to use default: "© {year} {site_name}. All rights reserved."'),
                ]),

            Forms\Components\Section::make('Footer Pages Content')
                ->description('Edit content for the static footer pages')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Forms\Components\Tabs::make('Pages')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('About Us')
                                ->schema([
                                    Forms\Components\TextInput::make('about_title_en')
                                        ->label('Title (English)')
                                        ->required(),
                                    Forms\Components\TextInput::make('about_title_bn')
                                        ->label('Title (Bangla)')
                                        ->required(),
                                    Forms\Components\RichEditor::make('about_content_en')
                                        ->label('Content (English)')
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\RichEditor::make('about_content_bn')
                                        ->label('Content (Bangla)')
                                        ->required()
                                        ->columnSpanFull(),
                                ])->columns(2),
                            Forms\Components\Tabs\Tab::make('Contact Us')
                                ->schema([
                                    Forms\Components\TextInput::make('contact_title_en')
                                        ->label('Title (English)')
                                        ->required(),
                                    Forms\Components\TextInput::make('contact_title_bn')
                                        ->label('Title (Bangla)')
                                        ->required(),
                                    Forms\Components\RichEditor::make('contact_content_en')
                                        ->label('Content (English)')
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\RichEditor::make('contact_content_bn')
                                        ->label('Content (Bangla)')
                                        ->required()
                                        ->columnSpanFull(),
                                ])->columns(2),
                            Forms\Components\Tabs\Tab::make('Privacy Policy')
                                ->schema([
                                    Forms\Components\TextInput::make('privacy_title_en')
                                        ->label('Title (English)')
                                        ->required(),
                                    Forms\Components\TextInput::make('privacy_title_bn')
                                        ->label('Title (Bangla)')
                                        ->required(),
                                    Forms\Components\RichEditor::make('privacy_content_en')
                                        ->label('Content (English)')
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\RichEditor::make('privacy_content_bn')
                                        ->label('Content (Bangla)')
                                        ->required()
                                        ->columnSpanFull(),
                                ])->columns(2),
                            Forms\Components\Tabs\Tab::make('Terms of Service')
                                ->schema([
                                    Forms\Components\TextInput::make('terms_title_en')
                                        ->label('Title (English)')
                                        ->required(),
                                    Forms\Components\TextInput::make('terms_title_bn')
                                        ->label('Title (Bangla)')
                                        ->required(),
                                    Forms\Components\RichEditor::make('terms_content_en')
                                        ->label('Content (English)')
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\RichEditor::make('terms_content_bn')
                                        ->label('Content (Bangla)')
                                        ->required()
                                        ->columnSpanFull(),
                                ])->columns(2),
                        ]),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settingKeys = [
            'footer_copyright_text',
            'footer_credit_enabled',
            'footer_credit_text',
            'footer_credit_url',
        ];

        foreach ($settingKeys as $key) {
            if (array_key_exists($key, $state)) {
                $value = $state[$key];
                $storedValue = is_array($value) ? json_encode($value) : $value;
                Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
            }
        }

        Setting::flushCache();

        $slugs = ['about', 'contact', 'privacy', 'terms'];
        foreach ($slugs as $slug) {
            StaticPage::updateOrCreate(
                ['slug' => $slug],
                [
                    'title_en' => $state[$slug . '_title_en'] ?? '',
                    'title_bn' => $state[$slug . '_title_bn'] ?? '',
                    'content_en' => $state[$slug . '_content_en'] ?? '',
                    'content_bn' => $state[$slug . '_content_bn'] ?? '',
                    'is_active' => true,
                ]
            );
            StaticPage::flushCache($slug);
        }

        Notification::make()
            ->title('Footer and page settings saved successfully!')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_footer_settings');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_footer_settings');
    }
}

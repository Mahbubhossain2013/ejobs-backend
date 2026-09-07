<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class StaticContentPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.static-content-page';
    protected static ?string $title = 'Content Pages';
    protected static ?string $slug = 'system-settings/content-pages';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::whereIn('key', [
            'page_privacy', 'page_terms', 'page_contact', 'page_about', 'page_faq',
        ])->pluck('value', 'key')->toArray();

        $data = [];
        foreach ($settings as $key => $value) {
            $decoded = json_decode($value, true);
            $data[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Content Pages')->tabs([

                Forms\Components\Tabs\Tab::make('Privacy Policy')->schema([
                    Forms\Components\Section::make('Privacy Policy Content')
                        ->description('Manage the privacy policy content displayed on /privacy')
                        ->schema([
                            Forms\Components\RichEditor::make('page_privacy')
                                ->label('Privacy Policy')
                                ->columnSpanFull()
                                ->helperText('Supports HTML. This content will be displayed on the public privacy policy page.'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Terms of Service')->schema([
                    Forms\Components\Section::make('Terms of Service Content')
                        ->description('Manage the terms of service content displayed on /terms')
                        ->schema([
                            Forms\Components\RichEditor::make('page_terms')
                                ->label('Terms of Service')
                                ->columnSpanFull()
                                ->helperText('Supports HTML. This content will be displayed on the public terms of service page.'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Contact Us')->schema([
                    Forms\Components\Section::make('Contact Us Content')
                        ->description('Manage the contact page content displayed on /contact')
                        ->schema([
                            Forms\Components\RichEditor::make('page_contact')
                                ->label('Contact Us')
                                ->columnSpanFull()
                                ->helperText('Supports HTML. This content will be displayed on the public contact page.'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('About Us')->schema([
                    Forms\Components\Section::make('About Us Content')
                        ->description('Manage the about page content displayed on /about')
                        ->schema([
                            Forms\Components\RichEditor::make('page_about')
                                ->label('About Us')
                                ->columnSpanFull()
                                ->helperText('Supports HTML. This content will be displayed on the public about page.'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('FAQ')->schema([
                    Forms\Components\Section::make('FAQ Content')
                        ->description('Manage the FAQ page content displayed on /faq')
                        ->schema([
                            Forms\Components\RichEditor::make('page_faq')
                                ->label('FAQ')
                                ->columnSpanFull()
                                ->helperText('Supports HTML. This content will be displayed on the public FAQ page.'),
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

        Setting::flushCache();

        Notification::make()->title('Content pages saved successfully!')->success()->send();
    }
}

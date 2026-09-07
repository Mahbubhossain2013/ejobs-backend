<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SeoSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.seo-settings';
    protected static ?string $title = 'SEO Settings';
    protected static ?string $slug = 'system-settings/seo-settings';
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
            Forms\Components\Section::make('Search Engine Optimization')
                ->description('Improve your visibility in search engines')
                ->schema([
                    Forms\Components\Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(4)
                        ->placeholder('Enter a compelling description of your business that will appear in search results')
                        ->helperText('This appears in search engine results below your site title'),
                    Forms\Components\TagsInput::make('seo_keywords')
                        ->label('SEO Keywords')
                        ->placeholder('Add keyword')
                        ->helperText('Keywords that represent your business (comma separated)'),
                ]),

            Forms\Components\Section::make('Social Media Sharing')
                ->description('Customize how your content appears when shared on social platforms')
                ->schema([
                    Forms\Components\FileUpload::make('og_image')
                        ->label('Facebook/LinkedIn Image')
                        ->image()
                        ->directory('seo')
                        ->helperText('Recommended size: 1200x630 pixels'),
                    Forms\Components\FileUpload::make('twitter_image')
                        ->label('Twitter/X Card Image')
                        ->image()
                        ->directory('seo')
                        ->helperText('Recommended size: 1200x628 pixels'),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Notification::make()->title('SEO settings saved successfully!')->success()->send();
    }
}

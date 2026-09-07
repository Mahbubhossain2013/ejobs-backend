<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DeveloperPortal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.developer-portal';

    protected static ?string $title = 'Developer Portal';

    protected static ?string $slug = 'system-settings/developer';

    protected static bool $shouldRegisterNavigation = false;

    public string $baseUrl = '';

    public function mount(): void
    {
        $this->baseUrl = config('app.url', url('/api'));
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_developer_portal');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}

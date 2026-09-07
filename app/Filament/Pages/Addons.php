<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Addons extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.addons';
    protected static ?string $title = 'System Addons';
    protected static ?string $slug = 'system-settings/addons';
    protected static bool $shouldRegisterNavigation = false;

    public string $search = '';

    /**
     * Get installed addons from database.
     * Returns empty array when no addons are installed.
     */
    public function getAddons(): array
    {
        $addons = [];

        if (trim($this->search) !== '') {
            $query = strtolower($this->search);
            $addons = array_filter($addons, function ($addon) use ($query) {
                return str_contains(strtolower($addon['name']), $query) ||
                       str_contains(strtolower($addon['description']), $query);
            });
        }

        return $addons;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_addons');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}

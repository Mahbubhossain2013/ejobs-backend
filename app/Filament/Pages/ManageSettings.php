<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\GeneralSettings;
use App\Filament\Pages\CronJobMonitor;
use App\Filament\Pages\Currencies;
use App\Filament\Pages\MailSettings;
use App\Filament\Pages\SmsSettings;
use App\Filament\Pages\Addons;
use App\Filament\Pages\NotificationSettings;
use App\Filament\Pages\SeoSettings;
use App\Filament\Pages\FinancialSettings;
use App\Filament\Pages\SecuritySettingsPanel;
use App\Filament\Pages\VerificationSettingsPanel;
use App\Filament\Pages\PerformanceInfrastructure;
use App\Filament\Pages\BackupRestore;
use App\Filament\Pages\DeveloperPortal;
use App\Filament\Pages\SystemHealthCenter;
use App\Filament\Pages\SystemInformation;
use App\Filament\Pages\ThemeSyncSettings;
use App\Filament\Pages\ProfileIntelligenceSettingsPanel;
use App\Filament\Pages\SocialAuthSettings;
use App\Filament\Pages\AiManager;
use App\Filament\Resources\FaqResource;
use App\Filament\Resources\StaticPageResource;
use App\Filament\Pages\StaticContentPage;
use Filament\Pages\Page;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.manage-settings';
    protected static ?string $title = 'System Settings';
    protected static ?string $slug = 'system-settings';

    public string $search = '';

    public function getSettingsCards(): array
    {
        $cards = [
            [
                'title' => 'General',
                'description' => 'Manage essential system preferences and core configurations.',
                'url' => GeneralSettings::getUrl(),
                'icon' => 'heroicon-o-cog-6-tooth',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_general_settings'),
            ],
            [
                'title' => 'Cron Job',
                'description' => 'Configure scheduler & queue workers, monitor heartbeat, and review task health.',
                'url' => CronJobMonitor::getUrl(),
                'icon' => 'heroicon-o-clock',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_cron_jobs'),
            ],
            [
                'title' => 'Currency',
                'description' => 'Manage system currencies, exchange rates, and formatting options.',
                'url' => Currencies::getUrl(),
                'icon' => 'heroicon-o-currency-dollar',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_currencies'),
            ],
            [
                'title' => 'Mail Settings',
                'description' => 'Configure system-wide email delivery. Brands can override these settings if needed.',
                'url' => MailSettings::getUrl(),
                'icon' => 'heroicon-o-envelope',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_mail_settings'),
            ],
            [
                'title' => 'SMS Settings',
                'description' => 'Configure system-wide SMS delivery. Brands can override these settings if needed.',
                'url' => SmsSettings::getUrl(),
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
            [
                'title' => 'Addons',
                'description' => 'Manage and configure installed addons to extend system functionality.',
                'url' => Addons::getUrl(),
                'icon' => 'heroicon-o-puzzle-piece',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_addons'),
            ],
            [
                'title' => 'Notification Channels',
                'description' => 'Configure system-wide notifications for admins, candidates, and employers.',
                'url' => NotificationSettings::getUrl(),
                'icon' => 'heroicon-o-bell',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_notification_settings'),
            ],
            [
                'title' => 'Content Pages',
                'description' => 'Manage static content pages such as Privacy Policy, Terms of Service, Contact Us, About Us, and FAQ.',
                'url' => StaticPageResource::getUrl('index'),
                'icon' => 'heroicon-o-document-text',
            ],
            [
                'title' => 'FAQs',
                'description' => 'Manage frequently asked questions displayed across candidate and employer portals.',
                'url' => FaqResource::getUrl(),
                'icon' => 'heroicon-o-question-mark-circle',
            ],
            [
                'title' => 'SEO Settings',
                'description' => 'Optimize meta descriptors, site keywords, and search engine open graph assets.',
                'url' => SeoSettings::getUrl(),
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'title' => 'Service Charges & Fees',
                'description' => 'Configure platform service charges, candidate application fees, escrow percentages, and withdrawal thresholds.',
                'url' => FinancialSettings::getUrl(),
                'icon' => 'heroicon-o-banknotes',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_financial_settings'),
            ],
            [
                'title' => '2FA & Account Security',
                'description' => 'Configure system-wide 2FA rules, manage forced roles, reset user secrets, and override security controls.',
                'url' => SecuritySettingsPanel::getUrl(),
                'icon' => 'heroicon-o-shield-check',
            ],
            [
                'title' => 'Identity & Verification',
                'description' => 'Configure NID, phone, email, and company verification requirements, set fees, and adjust AI OCR settings.',
                'url' => VerificationSettingsPanel::getUrl(),
                'icon' => 'heroicon-o-shield-check',
            ],
            [
                'title' => 'Performance & Infrastructure',
                'description' => 'Monitor health, configure fallback priorities, clean cache, and inspect Redis/Socket queues.',
                'url' => PerformanceInfrastructure::getUrl(),
                'icon' => 'heroicon-o-cpu-chip',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_infrastructure'),
            ],
            [
                'title' => 'Backup & Restore',
                'description' => 'Create database backups, storage backups, and restore from previous backup files.',
                'url' => BackupRestore::getUrl(),
                'icon' => 'heroicon-o-arrow-up-tray',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_backups'),
            ],
            [
                'title' => 'Currencies',
                'description' => 'Manage system currencies, exchange rates, and formatting options.',
                'url' => Currencies::getUrl(),
                'icon' => 'heroicon-o-currency-dollar',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_currencies'),
            ],
            [
                'title' => 'System Health',
                'description' => 'Check database, Redis, mail, queue, and storage health status.',
                'url' => SystemHealthCenter::getUrl(),
                'icon' => 'heroicon-o-heart',
            ],
            [
                'title' => 'System Information',
                'description' => 'View PHP version, Laravel version, server environment, and installed packages.',
                'url' => SystemInformation::getUrl(),
                'icon' => 'heroicon-o-information-circle',
            ],
            [
                'title' => 'Theme & Appearance',
                'description' => 'Sync frontend theme colors, fonts, and dark mode settings.',
                'url' => ThemeSyncSettings::getUrl(),
                'icon' => 'heroicon-o-paint-brush',
            ],
            [
                'title' => 'Profile Strength',
                'description' => 'Configure candidate profile completion weights and scoring rules.',
                'url' => ProfileIntelligenceSettingsPanel::getUrl(),
                'icon' => 'heroicon-o-adjustments-horizontal',
                'visible' => fn () => auth()->user()->hasPermissionTo('manage_profile_intelligence'),
            ],
            [
                'title' => 'Social Authentication',
                'description' => 'Configure Google and Facebook social login settings.',
                'url' => SocialAuthSettings::getUrl(),
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'title' => 'AI Control Panel',
                'description' => 'Manage AI provider configurations, failover chains, and usage telemetry.',
                'url' => AiManager::getUrl(),
                'icon' => 'heroicon-o-cpu-chip',
            ],
            [
                'title' => 'Developer Portal',
                'description' => 'API documentation, developer tools, and integration guides.',
                'url' => DeveloperPortal::getUrl(),
                'icon' => 'heroicon-o-code-bracket',
                'visible' => fn () => auth()->user()->hasPermissionTo('view_developer_portal'),
            ],
        ];

        if (trim($this->search) !== '') {
            $query = strtolower($this->search);
            $cards = array_filter($cards, function ($card) use ($query) {
                return str_contains(strtolower($card['title']), $query) ||
                       str_contains(strtolower($card['description']), $query);
            });
        }

        $cards = array_filter($cards, function ($card) {
            return !isset($card['visible']) || $card['visible']();
        });

        return $cards;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_system_settings');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_system_settings');
    }
}
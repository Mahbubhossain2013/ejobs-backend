<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AdminGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Admin Guide';
    protected static ?string $title = 'Admin Configuration Guide';
    protected static ?string $slug = 'admin-guide';
    protected static ?int $navigationSort = 999;
    protected ?string $heading = '';

    protected static string $view = 'filament.pages.admin-guide';

    public function getGuideData(): array
    {
        return [
            [
                'section' => 'General & Branding',
                'icon' => 'heroicon-o-cog-6-tooth',
                'description' => 'Site identity, appearance, and global content settings',
                'items' => [
                    [
                        'task' => 'Change site name / title',
                        'where' => 'System Settings > General > Core',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\GeneralSettings::getUrl(),
                    ],
                    [
                        'task' => 'Change logo & favicon in admin',
                        'where' => 'System Settings > General > Logo & Favicon',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\GeneralSettings::getUrl(),
                    ],
                    [
                        'task' => 'Change theme colors & fonts',
                        'where' => 'System Settings > General > Theme Settings',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\ThemeSyncSettings::getUrl(),
                    ],
                    [
                        'task' => 'Set default language & timezone',
                        'where' => 'System Settings > General > Core',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\GeneralSettings::getUrl(),
                    ],
                    [
                        'task' => 'Configure homepage content',
                        'where' => 'Menu > Homepage Settings',
                        'status' => 'partial',
                        'url' => \App\Filament\Pages\HomepageSettings::getUrl(),
                    ],
                    [
                        'task' => 'Dynamic site logo in frontend',
                        'where' => 'GeneralSettings > site_logo',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\GeneralSettings::getUrl(),
                    ],
                    [
                        'task' => 'Dynamic favicon in browser tab',
                        'where' => 'GeneralSettings > site_favicon',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\GeneralSettings::getUrl(),
                    ],
                    [
                        'task' => 'SEO meta tags in frontend',
                        'where' => 'System Settings > SEO Settings',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\SeoSettings::getUrl(),
                    ],
                    [
                        'task' => 'Footer credits & social links',
                        'where' => 'Footer Credit / Contact & Social',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\FooterCreditSettings::getUrl(),
                    ],
                ],
            ],
            [
                'section' => 'User Management',
                'icon' => 'heroicon-o-users',
                'description' => 'Candidate, employer, and support management',
                'items' => [
                    [
                        'task' => 'View / edit candidates',
                        'where' => 'User Management > Candidates',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CandidateResource::getUrl(),
                    ],
                    [
                        'task' => 'View / edit employers',
                        'where' => 'User Management > Employers',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\EmployerResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage company profiles',
                        'where' => 'User Management > Companies',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CompanyResource::getUrl(),
                    ],
                    [
                        'task' => 'Review company reviews',
                        'where' => 'User Management > Company Reviews',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CompanyReviewResource::getUrl(),
                    ],
                    [
                        'task' => 'SSO login (impersonate user)',
                        'where' => 'Candidates/Employers > Actions > SSO',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CandidateResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage user wallets',
                        'where' => 'Candidates/Employers > Actions > Wallet',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CandidateResource::getUrl(),
                    ],
                    [
                        'task' => 'Award & revoke badges',
                        'where' => 'Candidates/Employers > Actions > Badge',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\BadgeResource::getUrl(),
                    ],
                    [
                        'task' => 'Support ticket system',
                        'where' => 'Support > Support Tickets',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\SupportTicketResource::getUrl(),
                    ],
                ],
            ],
            [
                'section' => 'Job Management',
                'icon' => 'heroicon-o-briefcase',
                'description' => 'Jobs, categories, and application tracking',
                'items' => [
                    [
                        'task' => 'Create / edit jobs',
                        'where' => 'Job Management > Jobs',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\JobResource::getUrl(),
                    ],
                    [
                        'task' => 'Create / edit remote jobs',
                        'where' => 'Job Management > Remote Jobs',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\RemoteJobResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage job categories',
                        'where' => 'Job Management > Categories',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CategoryResource::getUrl(),
                    ],
                    [
                        'task' => 'View job applications',
                        'where' => 'Dashboard widget',
                        'status' => 'working',
                        'url' => url('/admin'),
                    ],
                ],
            ],
            [
                'section' => 'Identity & Verification',
                'icon' => 'heroicon-o-shield-check',
                'description' => 'Verification, badges, and trust enforcement',
                'items' => [
                    [
                        'task' => 'Approve / reject verifications',
                        'where' => 'Identity & Trust > Verifications',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\VerificationResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage badges & auto-rules',
                        'where' => 'Identity & Trust > Badges',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\BadgeResource::getUrl(),
                    ],
                    [
                        'task' => 'Configure verification rules',
                        'where' => 'Identity & Trust > Verification Settings',
                        'status' => 'partial',
                        'url' => \App\Filament\Pages\VerificationSettingsPanel::getUrl(),
                    ],
                    [
                        'task' => 'Enforce verification gate (apply)',
                        'where' => 'Verification Settings > Restrictions',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\VerificationSettingsPanel::getUrl(),
                    ],
                ],
            ],
            [
                'section' => 'Finance & Billing',
                'icon' => 'heroicon-o-banknotes',
                'description' => 'Payments, invoices, taxes, and billing profiles',
                'items' => [
                    [
                        'task' => 'Approve deposits',
                        'where' => 'Financial Management > Deposits',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\DepositResource::getUrl(),
                    ],
                    [
                        'task' => 'Process withdrawals',
                        'where' => 'Financial Management > Withdrawals',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\WithdrawalResource::getUrl(),
                    ],
                    [
                        'task' => 'Resolve disputes',
                        'where' => 'Financial Management > Disputes',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\DisputeResource::getUrl(),
                    ],
                    [
                        'task' => 'Configure payment gateways',
                        'where' => 'System Settings > Gateway Manager',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\GatewayResource::getUrl(),
                    ],
                    [
                        'task' => 'Configure payout methods',
                        'where' => 'Financial Management > Payout Manager',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\PayoutGatewayResource::getUrl(),
                    ],
                    [
                        'task' => 'Set application fee',
                        'where' => 'System Settings > Financial Settings',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\FinancialSettings::getUrl(),
                    ],
                    [
                        'task' => 'View / download invoices',
                        'where' => 'Billing & Finance > Invoices',
                        'status' => 'partial',
                        'url' => \App\Filament\Resources\InvoiceResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage tax rates & tax display',
                        'where' => 'Billing & Finance > Tax Settings',
                        'status' => 'partial',
                        'url' => \App\Filament\Resources\TaxSettingResource::getUrl(),
                    ],
                    [
                        'task' => 'Multi-currency switcher frontend',
                        'where' => 'System Settings > Currencies',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\Currencies::getUrl(),
                    ],
                    [
                        'task' => 'View transaction ledgers',
                        'where' => 'Billing & Finance > Transaction Ledgers',
                        'status' => 'missing',
                        'url' => \App\Filament\Resources\WalletLedgerResource::getUrl(),
                    ],
                ],
            ],
            [
                'section' => 'Marketing & Ads',
                'icon' => 'heroicon-o-megaphone',
                'description' => 'Ad placements, campaigns, and ranking engine',
                'items' => [
                    [
                        'task' => 'Create / manage ad placements',
                        'where' => 'Marketing Operations > Ad Placements',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\AdResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage ad campaigns',
                        'where' => 'Marketing Operations > Campaign Manager',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\AdCampaignResource::getUrl(),
                    ],
                    [
                        'task' => 'Configure ranking engine',
                        'where' => 'Marketing Operations > Ranking Config',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\AdsRankingConfig::getUrl(),
                    ],
                    [
                        'task' => 'View ads dashboard',
                        'where' => 'Marketing Operations > Control Center',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\AdsDashboard::getUrl(),
                    ],
                ],
            ],
            [
                'section' => 'Content & CV',
                'icon' => 'heroicon-o-document-text',
                'description' => 'CV templates, FAQs, notices, and subscriptions',
                'items' => [
                    [
                        'task' => 'Manage CV templates',
                        'where' => 'CV Management > CV Templates',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\CvTemplateResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage subscription plans',
                        'where' => 'Monetization > Subscription Plans',
                        'status' => 'working',
                        'url' => \App\Filament\Resources\SubscriptionPlanResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage FAQs',
                        'where' => 'System Settings > FAQs',
                        'status' => 'partial',
                        'url' => \App\Filament\Resources\FaqResource::getUrl(),
                    ],
                    [
                        'task' => 'Manage notices',
                        'where' => 'Menu > Notices',
                        'status' => 'missing',
                        'url' => \App\Filament\Resources\NoticeResource::getUrl(),
                    ],
                ],
            ],
            [
                'section' => 'System & Infrastructure',
                'icon' => 'heroicon-o-server-stack',
                'description' => 'Email, SMS, notifications, AI, currencies, and security',
                'items' => [
                    [
                        'task' => 'Configure SMTP email',
                        'where' => 'System Settings > Mail Settings',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\MailSettings::getUrl(),
                    ],
                    [
                        'task' => 'Configure SMS provider',
                        'where' => 'System Settings > SMS Settings',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\SmsSettings::getUrl(),
                    ],
                    [
                        'task' => 'Send push notifications',
                        'where' => 'System Management > Push Notifications',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\SendNotification::getUrl(),
                    ],
                    [
                        'task' => 'Manage currencies',
                        'where' => 'System Settings > Currencies',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\Currencies::getUrl(),
                    ],
                    [
                        'task' => 'Backup & restore system',
                        'where' => 'System Settings > Backup & Restore',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\BackupRestore::getUrl(),
                    ],
                    [
                        'task' => 'System health monitor',
                        'where' => 'System Settings > System Health Center',
                        'status' => 'working',
                        'url' => \App\Filament\Pages\SystemHealthCenter::getUrl(),
                    ],
                    [
                        'task' => 'Configure notification rules',
                        'where' => 'System Settings > Notification Settings',
                        'status' => 'partial',
                        'url' => \App\Filament\Pages\NotificationSettings::getUrl(),
                    ],
                    [
                        'task' => 'Configure AI settings & weights',
                        'where' => 'System Settings > AI Manager',
                        'status' => 'partial',
                        'url' => \App\Filament\Pages\AiManager::getUrl(),
                    ],
                    [
                        'task' => 'Security & 2FA settings',
                        'where' => 'System Settings > Security & 2FA',
                        'status' => 'partial',
                        'url' => \App\Filament\Pages\SecuritySettingsPanel::getUrl(),
                    ],
                    [
                        'task' => 'Profile intelligence weights',
                        'where' => 'System Settings > Profile Intelligence',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\ProfileIntelligenceSettingsPanel::getUrl(),
                    ],
                    [
                        'task' => 'Behavior tracking toggles',
                        'where' => 'System Settings > AI Manager > Telemetry',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\AiManager::getUrl(),
                    ],
                    [
                        'task' => 'API key management',
                        'where' => 'System Settings > API Keys',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\DeveloperPortal::getUrl(),
                    ],
                    [
                        'task' => 'Cron job monitor',
                        'where' => 'System Settings > Cron Job Monitor',
                        'status' => 'missing',
                        'url' => \App\Filament\Pages\CronJobMonitor::getUrl(),
                    ],
                ],
            ],
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'working' => 'success',
            'partial' => 'warning',
            'missing' => 'danger',
            default => 'gray',
        };
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_admin_guide');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_admin_guide');
    }
}
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

use App\Models\Setting;
use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoicePaid;
use App\Listeners\Billing\SendInvoiceEmailOnCreation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register billing event listeners
        Event::listen(InvoiceCreated::class, SendInvoiceEmailOnCreation::class);

        // Register filament company logo provider manually because it's installed via custom repositories
        $this->app->register(\TinusG\FilamentCompanyLogoColumn\CompanyLogoColumnServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' && request()->secure()) {
            URL::forceScheme('https');
        }

        // Global API rate limiter — 300 requests per minute per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Super admin bypass for all gate checks
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (isset($user->admin_role) && in_array($user->admin_role, ['super_admin', 'admin'])) {
                return true;
            }
        });

        // 1. Explicitly register SupportTicket with SupportTicketPolicy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\SupportTicket::class, \App\Policies\SupportTicketPolicy::class);

        // 2. Dynamically bind wildcard AdminResourcePolicy to all other models
        $modelsPath = app_path('Models');
        if (is_dir($modelsPath)) {
            foreach (glob($modelsPath . '/*.php') as $file) {
                $modelName = basename($file, '.php');
                if (in_array($modelName, ['SupportTicket', 'User'])) {
                    continue; // Skip SupportTicket and User models
                }
                $modelClass = 'App\\Models\\' . $modelName;
                if (class_exists($modelClass)) {
                    \Illuminate\Support\Facades\Gate::policy($modelClass, \App\Policies\AdminResourcePolicy::class);
                }
            }
        }

        // Dynamically override system configs using Platform Settings from Database
        try {
            if (\Schema::hasTable('settings')) {
                $settings = Cache::remember('platform_settings', 3600, function () {
                    return Setting::select('key', 'value')->get()->pluck('value', 'key')->toArray();
                });


                // 2. SMS Setup Override
                if (isset($settings['sms_enabled']) && $settings['sms_enabled']) {
                    config([
                        'services.bulksmsbd.api_token' => $settings['sms_api_token'] ?? '',
                        'services.bulksmsbd.sender_id' => $settings['sms_sender_id'] ?? '',
                        'services.mimsms.api_token' => $settings['sms_api_token'] ?? '',
                        'services.mimsms.sender_id' => $settings['sms_sender_id'] ?? '',
                    ]);
                }

                // 3. Google OAuth Override
                if (!empty($settings['social_auth_google_client_id'])) {
                    config([
                        'services.google.client_id' => $settings['social_auth_google_client_id'],
                        'services.google.client_secret' => $settings['social_auth_google_client_secret'] ?? '',
                        'services.google.redirect' => $settings['social_auth_google_redirect'] ?? env('GOOGLE_REDIRECT_URI', config('app.url') . '/api/auth/google/callback'),
                    ]);
                }

                // 4. Facebook OAuth Override
                if (!empty($settings['social_auth_facebook_client_id'])) {
                    config([
                        'services.facebook.client_id' => $settings['social_auth_facebook_client_id'],
                        'services.facebook.client_secret' => $settings['social_auth_facebook_client_secret'] ?? '',
                        'services.facebook.redirect' => $settings['social_auth_facebook_redirect'] ?? env('FACEBOOK_REDIRECT_URI', config('app.url') . '/api/auth/facebook/callback'),
                    ]);
                }

                // 5. Timezone Override
                if (isset($settings['timezone'])) {
                    date_default_timezone_set($settings['timezone']);
                }
            }
        } catch (\Exception $e) {
            // Prevent boot crashes when database is not migrated/ready yet
        }
    }
}

<?php

namespace Filament\Support\Components\Contracts {
    if (!interface_exists(HasEmbeddedView::class, false)) {
        interface HasEmbeddedView {}
    }
}

namespace {
    use Illuminate\Foundation\Application;
    use Illuminate\Foundation\Configuration\Exceptions;
    use Illuminate\Foundation\Configuration\Middleware;

    return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            api: __DIR__.'/../routes/api.php',
            commands: __DIR__.'/../routes/console.php',
            channels: __DIR__.'/../routes/channels.php',
            health: '/up',
        )
        ->withMiddleware(function (Middleware $middleware) {
            $middleware->trustProxies(at: [
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR,
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST,
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT,
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
            ]);
            $middleware->statefulApi();
            $middleware->throttleApi();

            // Redirect root domain to /admin
            // $middleware->append(\App\Http\Middleware\RootRedirectMiddleware::class);

            // Force HTTPS in production
            $middleware->append(\App\Http\Middleware\ForceHttpsMiddleware::class);

            // Exempt API routes and payment callbacks from CSRF verification
            $middleware->validateCsrfTokens(except: [
                'api/*',
                'payment/*',
                'api/payment/*',
            ]);

            // Enable CORS for API requests
            $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
            $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);

            // Tell Laravel what the 'role' middleware is
            $middleware->alias([
                'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
                'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
                'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
                'check_feature' => \App\Http\Middleware\CheckSubscriptionFeature::class,
                'security_monitor' => \App\Http\Middleware\SecurityMonitorMiddleware::class,
                'ai_moderation' => \App\Http\Middleware\AiModerationMiddleware::class,
                'verification' => \App\Http\Middleware\VerificationMiddleware::class,
            ]);
        })
        ->withExceptions(function (Exceptions $exceptions) {
            //
        })->create();
}
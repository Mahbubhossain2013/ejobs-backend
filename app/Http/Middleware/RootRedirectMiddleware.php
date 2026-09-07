<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RootRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() === '/') {
            $statusCode = $request->secure() ? 301 : 302;

            return new RedirectResponse(
                url('/admin'),
                $statusCode,
                [
                    'X-Redirect-From' => 'root',
                    'Cache-Control' => 'no-cache, must-revalidate',
                ]
            );
        }

        return $next($request);
    }
}

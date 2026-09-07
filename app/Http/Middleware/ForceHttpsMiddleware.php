<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttpsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (config('app.env') === 'production' && !$request->secure() && !$request->header('X-Forwarded-Proto')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}

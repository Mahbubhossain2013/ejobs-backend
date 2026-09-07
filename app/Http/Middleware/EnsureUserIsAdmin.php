<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Check the admin_role column on the users table
            $adminRoles = ['super_admin', 'admin', 'manager', 'viewer'];

            if (!empty($user->admin_role) && in_array($user->admin_role, $adminRoles)) {
                return $next($request);
            }

            // Fallback: check Spatie roles if admin_role column is not set
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($adminRoles)) {
                return $next($request);
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        auth()->logout();
        return redirect('/login')->with('error', 'You do not have access to this portal.');
    }
}

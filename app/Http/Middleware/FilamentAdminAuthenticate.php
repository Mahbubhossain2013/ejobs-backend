<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Http\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Database\Eloquent\Model;

class FilamentAdminAuthenticate extends BaseAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);
            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentPanel();

        if ($user instanceof FilamentUser) {
            if (! $user->canAccessPanel($panel)) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                abort(redirect()->to(Filament::getLoginUrl())->with('error', 'আপনার অ্যাকাউন্টে অ্যাডমিন প্যানেল ব্যবহারের অনুমতি নেই। অনুগ্রহ করে অ্যাডমিন অ্যাকাউন্ট দিয়ে লগইন করুন।'));
            }
        } elseif (config('app.env') !== 'local') {
            abort(403);
        }
    }
}
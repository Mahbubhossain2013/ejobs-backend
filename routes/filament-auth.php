<?php
// This file registers auth routes that Filament needs but which get lost
// when the panel path is '' (root) and Breeze auth routes take the same URIs.

use Illuminate\Support\Facades\Route;

Route::post('/filament-logout', [\Filament\Http\Controllers\Auth\LogoutController::class])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.auth.logout');

Route::get('/filament-login', [\Filament\Pages\Auth\Login::class])
    ->middleware(['web', 'guest'])
    ->name('filament.admin.auth.login');

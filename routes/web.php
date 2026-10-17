<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/login', fn () => redirect()->route('filament.admin.auth.login'))->name('login');
Route::get('/register', fn () => redirect()->route('filament.admin.auth.register'))->name('register');
Route::get('/dashboard', fn () => redirect()->route('filament.admin.pages.dashboard'))->name('dashboard');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', fn () => redirect()->route('filament.admin.pages.dashboard'));
    });

require __DIR__.'/settings.php';

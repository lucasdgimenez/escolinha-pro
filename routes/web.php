<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/healthz', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('guest')->group(function () {
    Route::livewire('/registro', 'pages::auth.register')->name('register');
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/esqueci-a-senha', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/redefinir-senha/{token}', 'pages::auth.reset-password')->name('password.reset');
    Route::livewire('/convite/{token}', 'pages::auth.accept-invitation')->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/verificar-email', 'pages::auth.verify-email')->name('verification.notice');
    Route::get('/verificar-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::livewire('/portal', 'pages::portal')->name('portal');

    Route::middleware(['verified', 'role:super_admin,academy_director,coach'])->group(function () {
        Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    });

    Route::middleware(['verified', 'role:super_admin,academy_director'])->group(function () {
        Route::livewire('/convites', 'pages::invitations.index')->name('invitations.index');
        Route::livewire('/academia', 'pages::academy.profile')->name('academy.profile');
        Route::livewire('/categorias', 'pages::academy.categories')->name('academy.categories');
        Route::livewire('/atletas', 'pages::players.index')->name('players.index');
        Route::livewire('/atletas/novo', 'pages::players.create')->name('players.create');
        Route::livewire('/atletas/importar', 'pages::players.import')->name('players.import');
    });
});

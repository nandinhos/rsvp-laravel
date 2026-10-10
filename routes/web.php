<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestImportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', fn () => redirect()->route('events.index'))
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Seleção de eventos autorizados (PRD §3).
    Route::get('events', [EventController::class, 'index'])->name('events.index');

    // Modelo de importação para download (PRD §6).
    Route::get('import/template', [GuestImportController::class, 'template'])->name('guests.import.template');

    // Lista de convidados por evento (PRD §5). Autorização por evento no controller.
    Route::prefix('events/{event}')->group(function () {
        Route::get('', [DashboardController::class, 'show'])->name('events.dashboard');
        Route::get('guests', [GuestController::class, 'index'])->name('guests.index');
        Route::post('guests', [GuestController::class, 'store'])->name('guests.store');
        Route::patch('guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        // Ações rápidas (PRD §5).
        Route::post('guests/{guest}/confirm', [GuestController::class, 'confirm'])->name('guests.confirm');
        Route::post('guests/{guest}/decline', [GuestController::class, 'decline'])->name('guests.decline');
        Route::post('guests/{guest}/check-in', [GuestController::class, 'checkIn'])->name('guests.check-in');

        // Importação (PRD §6).
        Route::get('import', [GuestImportController::class, 'create'])->name('guests.import.create');
        Route::post('import/preview', [GuestImportController::class, 'preview'])->name('guests.import.preview');
        Route::post('import', [GuestImportController::class, 'store'])->name('guests.import.store');
    });
});

require __DIR__.'/auth.php';

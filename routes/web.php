<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('welcome');
})->name('home');

// Rutas para administrador
Route::middleware(['auth', 'check.role:superadmin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

// Rutas para usuario normal
Route::middleware(['auth', 'check.role:usuario'])->group(function () {
    Route::get('/userhome', function () {
        return view('userhome');
    })->name('userhome');
});

require __DIR__.'/auth.php';

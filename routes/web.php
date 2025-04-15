<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Rutas para administrador
Route::middleware(['auth', 'check.role:superadmin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::resource('users', RegisteredUserController::class);
});

// Gestión de Productos (para usuarios autenticados)
Route::middleware('auth')->group(function(){
    Route::resource('products', ProductController::class);
});

// Rutas para usuario normal
Route::middleware(['auth', 'check.role:usuario'])->group(function () {
    Route::get('/userhome', function () {
        return view('userhome');
    })->name('userhome');
});

// Rutas para gestión de roles (solo para SuperAdmin)
Route::resource('roles', RoleController::class);

require __DIR__.'/auth.php';

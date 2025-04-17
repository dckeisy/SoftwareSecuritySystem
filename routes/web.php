<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Routes for administrator
Route::middleware(['auth', 'check.role:superadmin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::resource('users', RegisteredUserController::class);
});

// Product Management (for authenticated users)
Route::middleware('auth')->group(function(){
    Route::resource('products', ProductController::class);
});

// Routes for normal user
Route::middleware(['auth', 'check.role:usuario'])->group(function () {
    Route::get('/userhome', function () {
        return view('userhome');
    })->name('userhome');
});

// Routes for role management
Route::middleware('auth')->group(function(){
    Route::resource('roles', RoleController::class);
});

require __DIR__.'/auth.php';

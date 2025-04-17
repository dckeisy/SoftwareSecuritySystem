<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Ruta principal - accesible sin autenticación
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Ruta para probar autenticación sin restricciones (para tests)
Route::middleware(['auth'])->group(function () {
    // Ruta de test para verificar la autorización
    Route::get('/test-auth', function () {
        return response()->json(['message' => 'Autorizado correctamente']);
    })->middleware(['check.role:Auditor,Registrador,SuperAdmin'])->name('test.auth');
});

// Rutas del sistema - requieren autenticación
Route::middleware(['auth'])->group(function () {

    // Rutas para SuperAdmin
    Route::middleware(['check.role:SuperAdmin'])->group(function () {
        // Dashboard
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    });

    // Usuarios - CRUD
    Route::middleware(['check.entity.permission:ver-reportes,usuarios'])->get('users', [RegisteredUserController::class, 'index'])->name('users.index');
    Route::middleware(['check.entity.permission:crear,usuarios'])->get('users/create', [RegisteredUserController::class, 'create'])->name('users.create');
    Route::middleware(['check.entity.permission:crear,usuarios'])->post('users', [RegisteredUserController::class, 'store'])->name('users.store');
    Route::middleware(['check.entity.permission:editar,usuarios'])->get('users/{user}/edit', [RegisteredUserController::class, 'edit'])->name('users.edit');
    Route::middleware(['check.entity.permission:editar,usuarios'])->put('users/{user}', [RegisteredUserController::class, 'update'])->name('users.update');
    Route::middleware(['check.entity.permission:borrar,usuarios'])->delete('users/{user}', [RegisteredUserController::class, 'destroy'])->name('users.destroy');
    
    // Roles - CRUD
    Route::middleware(['check.entity.permission:ver-reportes,roles'])->get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::middleware(['check.entity.permission:crear,roles'])->get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::middleware(['check.entity.permission:crear,roles'])->post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::middleware(['check.entity.permission:editar,roles'])->get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::middleware(['check.entity.permission:editar,roles'])->put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::middleware(['check.entity.permission:borrar,roles'])->delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    
    // Rutas adicionales para gestión de permisos
    Route::middleware(['check.entity.permission:editar,roles'])->get('/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::middleware(['check.entity.permission:editar,roles'])->put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.update_permissions');

    // Ruta para usuarios normales (Auditores, Registradores, etc.)
    Route::get('/userhome', function () {
        return view('userhome');
    })->name('userhome');

    // Productos - CRUD con verificación de permisos
    Route::middleware(['check.entity.permission:ver-reportes,productos'])->get('products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware(['check.entity.permission:crear,productos'])->get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::middleware(['check.entity.permission:crear,productos'])->post('products', [ProductController::class, 'store'])->name('products.store');
    Route::middleware(['check.entity.permission:editar,productos'])->get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::middleware(['check.entity.permission:editar,productos'])->put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::middleware(['check.entity.permission:borrar,productos'])->delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// Rutas de autenticación
require __DIR__.'/auth.php';

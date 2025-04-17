<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Role;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para obtener los permisos de un rol
Route::get('/roles/{role}/permissions', function (Role $role) {
    $result = [];
    $entities = $role->entities();
    
    foreach ($entities as $entity) {
        $permissions = $role->getPermissionsForEntity($entity->id);
        $result[$entity->name] = $permissions->pluck('name')->toArray();
    }
    
    return response()->json($result);
}); 
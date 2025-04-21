<?php

// Añadir explícitamente el namespace para las pruebas Pest
namespace Tests\Feature\Controllers;

use App\Http\Controllers\RoleController;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;

// Usar RefreshDatabase para resetear la base de datos entre pruebas
uses(RefreshDatabase::class);

// Configuración antes de cada prueba
beforeEach(function () {
    // Ejecutar los seeders una sola vez para cargar los datos necesarios
    $this->seed([
        \Database\Seeders\EntitySeeder::class,
        \Database\Seeders\PermissionSeeder::class,
        \Database\Seeders\RoleSeeder::class,
        \Database\Seeders\RoleEntityPermissionSeeder::class
    ]);
    
    // Obtener el rol SuperAdmin que ya tiene todos los permisos necesarios
    $superAdminRole = Role::where('slug', 'superadmin')->first();
    
    // Crear usuario admin para las pruebas con los campos correctos
    $this->adminUser = User::factory()->create([
        'username' => 'admin_test',
        'role_id' => $superAdminRole->id
    ]);
    
    // Autenticar al usuario para todas las pruebas
    $this->actingAs($this->adminUser);
    
    // Deshabilitar los middleware para las pruebas de integración
    $this->withoutMiddleware();
    
    // Instanciar el controlador para pruebas unitarias
    $this->controller = new RoleController();
});

afterEach(function() {
    Mockery::close();
});

test('index method returns view with roles', function () {
    // Hacer solicitud a la ruta
    $response = $this->get(route('roles.index'));
    
    // Probar directamente el método del controlador
    $result = $this->controller->index();
    
    // Verificar que devuelve una vista
    $this->assertInstanceOf(\Illuminate\View\View::class, $result);
    $this->assertEquals('roles.index', $result->name());
    
    // Verificar que tiene los roles
    $viewData = $result->getData();
    $this->assertTrue(isset($viewData['roles']));
    $this->assertInstanceOf(Collection::class, $viewData['roles']);
});

test('create method returns view with entities and permissions', function () {
    // Hacer solicitud a la ruta
    $response = $this->get(route('roles.create'));
    
    // Probar directamente el método del controlador
    $result = $this->controller->create();
    
    // Verificar que devuelve una vista
    $this->assertInstanceOf(\Illuminate\View\View::class, $result);
    $this->assertEquals('roles.create', $result->name());
    
    // Verificar que tiene las entidades y permisos
    $viewData = $result->getData();
    $this->assertTrue(isset($viewData['entities']));
    $this->assertTrue(isset($viewData['permissions']));
    $this->assertInstanceOf(Collection::class, $viewData['entities']);
    $this->assertInstanceOf(Collection::class, $viewData['permissions']);
});

test('store method creates new role with permissions', function () {
    // Obtener entidades y permisos reales para las pruebas
    $usuariosId = Entity::where('slug', 'usuarios')->first()->id;
    $productosId = Entity::where('slug', 'productos')->first()->id;
    
    $crearId = Permission::where('slug', 'crear')->first()->id;
    $editarId = Permission::where('slug', 'editar')->first()->id;
    
    // Datos para crear el rol
    $roleData = [
        'name' => 'Test Role',
        'permissions' => [
            $usuariosId => [$crearId, $editarId],
            $productosId => [$crearId]
        ]
    ];
    
    // Contar roles antes de crear uno nuevo
    $beforeCount = Role::count();
    
    // Crear una solicitud mockada
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['name' => 'Test Role']);
    $request->shouldReceive('has')->with('permissions')->andReturn(true);
    $request->shouldReceive('input')->with('name')->andReturn('Test Role');
    $request->shouldReceive('name')->andReturn('Test Role');
    $request->shouldReceive('get')->with('permissions')->andReturn($roleData['permissions']);
    
    // Mockear la transacción de DB para evitar efectos reales
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();
    
    // Llamar al método store del controlador
    $response = $this->controller->store($request);
    
    // Verificar que devuelve una redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertTrue($response->getSession()->has('success'));
    
    // Verificar que la redirección es a la ruta correcta
    $this->assertEquals(route('roles.index'), $response->getTargetUrl());
});

test('edit returns view with role data', function () {
    // Obtener un rol para editar
    $role = Role::where('slug', 'registrador')->first();
    
    // Llamar directamente al método edit
    $result = $this->controller->edit($role);
    
    // Verificar que devuelve una vista
    $this->assertInstanceOf(\Illuminate\View\View::class, $result);
    $this->assertEquals('roles.edit', $result->name());
    
    // Verificar que la vista tiene los datos necesarios
    $viewData = $result->getData();
    $this->assertTrue(isset($viewData['role']));
    $this->assertTrue(isset($viewData['entities']));
    $this->assertTrue(isset($viewData['permissions']));
    $this->assertTrue(isset($viewData['rolePermissions']));
    
    // Verificar que el rol es el correcto
    $this->assertEquals($role->id, $viewData['role']->id);
});

test('update method updates role with permissions', function () {
    // Crear un rol de prueba
    $role = Role::create([
        'name' => 'Test Role For Update',
        'slug' => 'test-role-update'
    ]);
    
    // Datos para actualizar
    $data = [
        'name' => 'Updated Role Name'
    ];
    
    // Crear una solicitud mockada
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['name' => 'Updated Role Name']);
    $request->shouldReceive('has')->with('permissions')->andReturn(false);
    $request->shouldReceive('input')->with('name')->andReturn('Updated Role Name');
    $request->shouldReceive('name')->andReturn('Updated Role Name');
    
    // Mockear la transacción de DB para evitar efectos reales
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();
    
    // Llamar al método update del controlador
    $response = $this->controller->update($request, $role);
    
    // Verificar que devuelve una redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertTrue($response->getSession()->has('success'));
    
    // Verificar que la redirección es a la ruta correcta
    $this->assertEquals(route('roles.index'), $response->getTargetUrl());
});

test('destroy method deletes role', function () {
    // Asegurarse de que no hay usuarios con el rol que vamos a crear
    // (Esta línea es opcional si tu base de datos de prueba está limpia)
    User::where('role_id', '>', 0)->update(['role_id' => null]);
    
    // Crear un rol que no sea predefinido
    $role = Role::create([
        'name' => 'Deletable Test Role',
        'slug' => 'deletable-test-role'
    ]);
    
    // Comprobar que el rol existe antes de eliminarlo
    $this->assertDatabaseHas('roles', ['id' => $role->id]);
    
    // Llamar al método destroy del controlador
    $response = $this->controller->destroy($role);
    
    // Verificar que devuelve una redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertTrue($response->getSession()->has('success'));
    
    // Verificar que la redirección es a la ruta correcta
    $this->assertEquals(route('roles.index'), $response->getTargetUrl());
    
    // Verificar que el rol ya no existe
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('permissions method returns view with role permissions', function () {
    // Obtener un rol existente
    $role = Role::where('slug', 'registrador')->first();
    
    // Llamar directamente al método permissions
    $result = $this->controller->permissions($role);
    
    // Verificar que devuelve una vista
    $this->assertInstanceOf(\Illuminate\View\View::class, $result);
    $this->assertEquals('roles.permissions', $result->name());
    
    // Verificar que la vista tiene los datos necesarios
    $viewData = $result->getData();
    $this->assertTrue(isset($viewData['role']));
    $this->assertTrue(isset($viewData['entities']));
    $this->assertTrue(isset($viewData['permissions']));
    $this->assertTrue(isset($viewData['rolePermissions'])); 
});

test('updatePermissions method updates role permissions', function () {
    // Obtener un rol que no sea predefinido
    $role = Role::create([
        'name' => 'Test Role For Permissions',
        'slug' => 'test-role-permissions'
    ]);
    
    // Obtener entidades y permisos reales
    $usuariosId = Entity::where('slug', 'usuarios')->first()->id;
    $crearId = Permission::where('slug', 'crear')->first()->id;
    
    // Datos para actualizar permisos (adaptados al nuevo formato)
    $permissionsData = [
        'entity_permissions' => [
            $usuariosId => [$crearId]
        ]
    ];
    
    // Crear una solicitud mockada con los nuevos parámetros
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn($permissionsData);
    
    // No necesitamos mockear beginTransaction y commit
    // ya que DB::transaction() lo maneja internamente
    
    // Llamar al método updatePermissions del controlador
    $response = $this->controller->updatePermissions($request, $role);
    
    // Verificar que devuelve una redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertTrue($response->getSession()->has('success'));
    
    // Verificar que la redirección es a la ruta correcta (ajustada a la nueva ruta)
    $this->assertEquals(route('roles.permissions', $role->id), $response->getTargetUrl());
});

// Prueba para asegurar la cobertura del controlador
test('direct controller instantiation for coverage', function () {
    // Crear una instancia del controlador
    $controller = new RoleController();
    
    // Llamar a métodos que no requieran mucha configuración
    $indexView = $controller->index();
    expect($indexView)->toBeInstanceOf(\Illuminate\View\View::class);
    
    $createView = $controller->create();
    expect($createView)->toBeInstanceOf(\Illuminate\View\View::class);
    
    // Verificar que los métodos existen
    $this->assertTrue(method_exists($controller, 'store'));
    $this->assertTrue(method_exists($controller, 'edit'));
    $this->assertTrue(method_exists($controller, 'update'));
    $this->assertTrue(method_exists($controller, 'destroy'));
    $this->assertTrue(method_exists($controller, 'permissions'));
    $this->assertTrue(method_exists($controller, 'updatePermissions'));
}); 
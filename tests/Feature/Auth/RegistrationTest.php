<?php
use App\Models\User;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crear entidades y permisos necesarios para que el SuperAdmin pueda gestionar usuarios
    // Crear las entidades necesarias
    $usuarios = Entity::create(['name' => 'Usuarios', 'slug' => 'usuarios']);
    
    // Crear los permisos necesarios
    $crear = Permission::create(['name' => 'Crear', 'slug' => 'crear']);
    $editar = Permission::create(['name' => 'Editar', 'slug' => 'editar']);
    $borrar = Permission::create(['name' => 'Borrar', 'slug' => 'borrar']);
    $ver = Permission::create(['name' => 'Ver Reportes', 'slug' => 'ver-reportes']);

    // Crear el rol SuperAdmin
    $this->superadminRole = Role::create([
        'name' => 'SuperAdmin',
        'slug' => 'superadmin'
    ]);
    
    // Asignar permisos al rol SuperAdmin
    RoleEntityPermission::create([
        'role_id' => $this->superadminRole->id,
        'entity_id' => $usuarios->id,
        'permission_id' => $crear->id
    ]);
    
    RoleEntityPermission::create([
        'role_id' => $this->superadminRole->id,
        'entity_id' => $usuarios->id,
        'permission_id' => $editar->id
    ]);
    
    RoleEntityPermission::create([
        'role_id' => $this->superadminRole->id,
        'entity_id' => $usuarios->id,
        'permission_id' => $borrar->id
    ]);
    
    RoleEntityPermission::create([
        'role_id' => $this->superadminRole->id,
        'entity_id' => $usuarios->id,
        'permission_id' => $ver->id
    ]);
    
    // Crear un usuario SuperAdmin
    $this->user = User::factory()->create([
        'role_id' => $this->superadminRole->id,
    ]);
    
    // Crear un rol de usuario normal para las pruebas
    $this->userRole = Role::create([
        'name' => 'TestRole',
        'slug' => 'testrole'
    ]);
});

test('user permissions module exists', function () {
    // Verificar que existen las clases necesarias para gestionar permisos
    $this->assertTrue(class_exists('App\Models\User'));
    $this->assertTrue(class_exists('App\Models\Role'));
    $this->assertTrue(class_exists('App\Models\Entity'));
    $this->assertTrue(class_exists('App\Models\Permission'));
});

test('user creation works with roles', function () {
    // Crear un nuevo usuario con rol
    $newUser = User::create([
        'username' => 'new_test_user',
        'password' => bcrypt('password123'),
        'role_id' => $this->userRole->id
    ]);
    
    // Verificar que el usuario fue creado correctamente
    $this->assertDatabaseHas('users', [
        'username' => 'new_test_user',
        'role_id' => $this->userRole->id
    ]);
    
    // Verificar que la relación con el rol funciona
    $this->assertEquals('TestRole', $newUser->role->name);
});

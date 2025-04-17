<?php
use App\Models\User;
use App\Models\Role;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

// Crear un usuario con el rol 'SuperAdmin' que tiene permiso para registrar nuevos usuarios
beforeEach(function () {
    // Crear el rol SuperAdmin
    $this->superadminRole = Role::create([
        'name' => 'SuperAdmin',
        'slug' => 'superadmin'
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

// Limpieza después de cada prueba
afterEach(function () {
    // Limpiar los datos creados
    User::where('username', 'TestUser')->delete();
    $this->user->delete();
    $this->superadminRole->delete();
    $this->userRole->delete();
});

test('registration screen can be rendered', function () {
    // Actuar como el usuario superadmin
    $this->actingAs($this->user);
    // Enviar una solicitud GET a la ruta de usuarios
    $response = $this->get('/users');
    // La pantalla de registro debe cargarse correctamente (HTTP 200 OK)
    $response->assertStatus(200);
});

test('new users can register', function () {
    // Actuar como el usuario superadmin
    $this->actingAs($this->user);
    // Enviar una solicitud POST a la ruta de registro con datos de usuario válidos
    $response = $this->post(route('users.store'), [
        'username' => 'TestUser',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role_id' => $this->userRole->id,
    ]);
    
    // Verificar que se haya creado el usuario
    $this->assertDatabaseHas('users', [
        'username' => 'TestUser',
        'role_id' => $this->userRole->id
    ]);
    
    // Verificar redirección
    $response->assertRedirect(route('users.index'));
});

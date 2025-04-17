<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

// Setup: ejecutado antes de cada test
beforeEach(function () {
    // Crear rol para las pruebas
    $this->role = Role::create([
        'name' => 'TestRole',
        'slug' => 'testrole'
    ]);
    
    // Crear un usuario con el rol
    $this->user = User::factory()->create([
        'role_id' => $this->role->id
    ]);
});

// Limpieza: ejecutada después de cada test
afterEach(function () {
    RateLimiter::clear($this->user->id);
    $this->user->delete();
    $this->role->delete();
});


test('login screen can be rendered', function () {
    // Act: Enviar una solicitud GET a la ruta de login
    $response = $this->get('/login');
    // Assert: Verificar que la respuesta tiene un estado 200 (OK)
    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    // Determine la contraseña correcta ('user12345' es la predeterminada en la factory)
    $password = 'user12345';

    // Act: Intentar iniciar sesión con las credenciales del usuario
    $response = $this->post('/login', [
        'username' => $this->user->username,
        'password' => $password
    ]);

    // Assert: Verificar que el usuario está autenticado
    $this->assertAuthenticated();
    
    // Verificar que el usuario es redirigido a la ruta correcta según su rol
    // Si no es SuperAdmin, debería redirigir a 'userhome'
    $response->assertRedirect(route('userhome', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    // Act: Intentar iniciar sesión con el nombre de usuario correcto pero una contraseña no válida
    $this->post('/login', [
        'username' => $this->user->username,
        'password' => 'wrong-password',
    ]);

    // Assert: Verificar que el usuario permanece no autenticado (invitado)
    $this->assertGuest();
});

test('users can logout', function () {
    // Act: Enviar una solicitud POST a la ruta de cierre de sesión mientras está autenticado
    $response = $this->actingAs($this->user)->post('/logout');

    // Assert: Confirmar que el usuario ha cerrado sesión y es redirigido a la página de inicio
    $this->assertGuest();
    $response->assertRedirect('/');
});

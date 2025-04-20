<?php

use App\Models\User;
use App\Models\Role;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('throws a ValidationException after many unsuccessful attempts', function () {
    // Crear un rol para el usuario
    $role = Role::create([
        'name' => 'SuperAdmin',
        'slug' => 'superAdmin'
    ]);

    // Crear un usuario para las pruebas
    $user = User::factory()->create([
        'role_id' => $role->id
    ]);

    // Simulamos 6 intentos fallidos
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);
    }

    // Intentamos iniciar sesión con credenciales incorrectas nuevamente
    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    // Esperamos una redirección (código 302) debido a la validación fallida
    $this->assertEquals(302, $response->status());

    // Limpieza
    $user->delete();
    $role->delete();
});

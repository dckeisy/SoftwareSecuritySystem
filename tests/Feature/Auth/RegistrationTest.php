<?php
use App\Models\User;

test('registration screen can be rendered', function () {
    $superadmin = User::factory()->create([
        'role' => 'superadmin',
    ]);

    $this->actingAs($superadmin);

    $response = $this->get('/register');
    $response->assertStatus(200);
});

test('new users can register', function () {
    // Crear un usuario superadmin
    $superadmin = User::factory()->create([
        'role' => 'superadmin',
    ]);

    // Autenticarlo
    $this->actingAs($superadmin);

    // Intentar registrar un nuevo usuario
    $response = $this->post('/register', [
        'username' => 'TestUser',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'user',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

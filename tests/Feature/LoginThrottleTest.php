<?php

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;

use function Laravel\Prompts\error;

it('lanza una excepción ValidationException después de muchos intentos fallidos', function () {
    // Crear el usuario
    $user = User::factory()->create();

    // Simulamos 6 intentos fallidos
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);
    }

    // Ahora intentamos hacer login nuevamente para que se active la limitación
    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertEquals(302, $response->status());
});

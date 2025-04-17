<?php

use Illuminate\Support\Facades\RateLimiter;
/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('blocks login after too many attempts', function () {
    $this->markTestSkipped('Las rutas de autenticación no están disponibles');
    
    // Este test simula un escenario donde un usuario intenta iniciar sesión demasiadas veces
    $ip = '127.0.0.1';
    $maxAttempts = 5;
    $key = $ip;

    // Limpiar cualquier intento previo
    RateLimiter::clear($key);

    // Alcanzar el límite
    for ($i = 0; $i < $maxAttempts; $i++) {
        RateLimiter::hit($key);
    }

    // Llamar al controlador a través de HTTP
    $this->get('/login', ['REMOTE_ADDR' => $ip])
        ->assertViewIs('auth.login')
        ->assertViewHas('blocked', true)
        ->assertViewHas('seconds', RateLimiter::availableIn($key));
});


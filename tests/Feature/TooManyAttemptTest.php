<?php

use Illuminate\Support\Facades\RateLimiter;
/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('handles rate limiting correctly', function () {
    // Este test simula un escenario donde un usuario intenta iniciar sesión demasiadas veces
    $ip = '127.0.0.1';
    $maxAttempts = 5;
    $key = $ip;

    // Limpiar cualquier intento previo
    RateLimiter::clear($key);

    // Verificar que RateLimiter funciona como se espera
    $this->assertFalse(RateLimiter::tooManyAttempts($key, $maxAttempts));
    
    // Alcanzar el límite
    for ($i = 0; $i < $maxAttempts; $i++) {
        RateLimiter::hit($key);
    }
    
    // Verificar que ahora está bloqueado
    $this->assertTrue(RateLimiter::tooManyAttempts($key, $maxAttempts));
    
    // Verificar que el tiempo de espera es mayor que cero
    $this->assertGreaterThan(0, RateLimiter::availableIn($key));
    
    // Limpiar para otros tests
    RateLimiter::clear($key);
});


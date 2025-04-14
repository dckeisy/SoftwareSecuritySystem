<?php

use Illuminate\Support\Facades\RateLimiter;
/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('blocks login after too many attempts', function () {
    /**
     * @example 🧪 Arrange:
     * This test simulates a scenario where a user tries to log in too many times
     */

    $ip = '127.0.0.1';
    $maxAttempts = 5;
    $key = $ip;

    // Clear any previous attempts
    RateLimiter::clear($key);

    // Hit the limit
    for ($i = 0; $i < $maxAttempts; $i++) {
        RateLimiter::hit($key);
    }

    // Call the actual controller via HTTP
    $this->get('/login', ['REMOTE_ADDR' => $ip]) // 🚀 Act
        ->assertViewIs('auth.login') // ✅ Assert:
        ->assertViewHas('blocked', true) // ✅ Assert:
        ->assertViewHas('seconds', RateLimiter::availableIn($key)); // ✅ Assert:
})->skip();


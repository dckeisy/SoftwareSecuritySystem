<?php

use App\Models\User;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('throws a ValidationException after many unsuccessful attempts', function () {
    // 🧪 Arrange:
    // We use the User factory to create a user
    $user = User::factory()->create();

    // Simulamos 6 intentos fallidos
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);
    }

    // 🚀 Act
    // We try to log in with the incorrect credentials
    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);
    // ✅ Assert:
    // We expect a ValidationException to be thrown with a status code of 302.
    $this->assertEquals(302, $response->status());
});//->skip()

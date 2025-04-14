<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

// 🛠️ Setup: executed before each test
beforeEach(function () {
    // Create a user
    $this->user = User::factory()->create();
});
// 🧹 Cleanup: executed after each test
afterEach(function () {
    RateLimiter::clear($this->user->id);
});


test('login screen can be rendered', function () {
    // 🚀 Act: Send a GET request to the login route
    $response = $this->get('/login');
    // ✅ Assert: Check that the response status is 200 (OK)
    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
     // 🧪 Arrange: determine the correct password based on their role
    $password = $this->user->role === 'superAdmin' ? 'admin12345' : 'user12345';

    // 🚀 Act: Attempt to log in with the user's credentials
    $response = $this->post('/login', [
        'username' => $this->user->username,
        'password' => $password
    ]);

    // ✅ Assert: Check that the user is authenticated and redirected to the dashboard
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
     // 🚀 Act: Attempt to log in with the correct username but an invalid password
    $this->post('/login', [
        'username' => $this->user->username,
        'password' => 'wrong-password',
    ]);

    // ✅ Assert: Verify that the user remains unauthenticated (guest)
    $this->assertGuest();
});

test('users can logout', function () {
    // 🚀 Act: Send a POST request to the logout route while authenticated
    $response = $this->actingAs($this->user)->post('/logout');

     // ✅ Assert: Confirm the user is logged out and redirected to the homepage
    $this->assertGuest();
    $response->assertRedirect('/');
});

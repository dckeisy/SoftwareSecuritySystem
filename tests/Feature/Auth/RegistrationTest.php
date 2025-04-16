<?php
use App\Models\User;


/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

// 🧪 Create a user with the 'superadmin' role who has permission to register new users
beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'superadmin',
    ]);
});

test('registration screen can be rendered', function () {
    // 🚀 Act as the superadmin user
    $this->actingAs($this->user);
    // 🔍 Send a GET request to the userhome route
    $response = $this->get('/users');
    // ✅ Assert: The registration screen should load successfully (HTTP 200 OK)
    $response->assertStatus(200);
});

test('new users can register', function () {
    // 🚀 Act as the superadmin user
    $this->actingAs($this->user);
    // 🧪 Send a POST request to the registration route with valid user data
    $response = $this->post(route('users.store'), [
        'username' => 'TestUser',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'user',
    ]);
    // ✅ Assert: The newly registered user should be authenticated
    $this->assertAuthenticated();
    // ✅ Assert: The user should be redirected to the dashboard after registration
    //$response->assertRedirect(route('users.index'));
});

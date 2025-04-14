<?php

use App\Models\User;
/**
 * @author Kendall Angulo Chaves <kendallangulo01@gmail.com>
 */

beforeEach(function () {
    //🧪 ARRANGE: Create a test user with the role "usuario" (user),
    $this->user = User::factory()->make([
        'role' => 'usuario',
        'username' => 'testuser'
    ]);
});

it('denies access to /dashboard for unauthorized roles', function () {
    // 🚀  ACT & ASSERT: Try to access /dashboard and expect redirection to /userhome
    $this->actingAs($this->user)
    ->get('/dashboard')
    ->assertRedirect(route('userhome'));
});

it('redirects to userhome if the role is usuario', function () {
    // 🚀 ACT: Authenticate the user and send a GET request to /userhome.
    $response = $this->actingAs($this->user)
        ->get('/userhome');

    // ASSERT: Verify that the response has a 200 (OK) status code, indicating access is allowed.
    $this->assertEquals(200, $response->status());
});

it('allows access to the dashboard only for superadmin', function () {
    //🧪  ====================== ARRANGE ======================
    // Create User model for a superadmin.
    $superadmin = User::factory()->make([
        'role' => 'superadmin',
        'username' => 'admin_user'
    ]);

    //🚀 ====================== ACT ======================
    // Authenticate the mock user and send a GET request to the dashboard route.
    $response = $this->actingAs($superadmin)
                    ->get(route('dashboard'));
    // ====================== ASSERT ======================
    // - assertViewIs() ensures that the returned view is 'dashboard'.
    $response->assertOk();
    $response->assertViewIs('dashboard');
});

it('redirects admin from userhome to dashboard', function () {
    //🧪 ====================== ARRANGE ======================
    // Create a test user with the role "superadmin" (admin).
    $admin = User::factory()->make([
        'role' => 'superadmin',
        'username' => 'admin_user'
    ]);

    //🚀  ACT & ASSERT - Try to access /userhome and expect redirection to /dashboard
    $this->actingAs($admin)
        ->get('/userhome')
        ->assertRedirect(route('dashboard'));
});

it('redirects home if dont have a role', function () {
    // 🧪 ARRANGE: Create a test user with an invalid role.
    $admin = User::factory()->make([
        'role' => 'jflsadjl',
        'username' => 'admin_user'
    ]);
    //🚀 ACT & ASSERT: Try to access /userhome and expect redirection to /home
    $this->actingAs($admin)
        ->get('/userhome')
        ->assertRedirect(route('home'));
});

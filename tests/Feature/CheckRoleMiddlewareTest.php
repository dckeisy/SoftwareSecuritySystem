<?php

use App\Models\User;

it('denies access to /dashboard for unauthorized roles', function () {
    // ARRANGE: Create a test user with the role "invitado" (guest),
    $user = User::factory()->make([
        'role' => 'usuario', // Unauthorized role for /userhome
        'username' => 'testuser'
    ]);

     // ACT & ASSERT: Try to access /dashboard and expect redirection to /userhome
     $this->actingAs($user)
     ->get('/dashboard')
     ->assertRedirect(route('userhome'));
});

it('redirects to userhome if the role is usuario', function () {
    // ARRANGE: Create a test user with the role "usuario", which is allowed to access /userhome.
    $user = User::factory()->make([
        'role' => 'usuario',
        'username' => 'testuser'
    ]);

    // ACT: Authenticate the user and send a GET request to /userhome.
    $response = $this->actingAs($user)
        ->get('/userhome');

    // ASSERT: Verify that the response has a 200 (OK) status code, indicating access is allowed.
    $this->assertEquals(200, $response->status());
});

it('allows access to the dashboard only for superadmin', function () {
    // ====================== ARRANGE ======================
    // Create a partial mock of the User model for a superadmin.
    $superadmin = $this->partialMock(User::class);
    $superadmin->role = 'superadmin';
    $superadmin->username = 'admin_user';

    // ====================== ACT ======================
    // Authenticate the mock user and send a GET request to the dashboard route.
    $response = $this->actingAs($superadmin)
                    ->get(route('dashboard'));
    // ====================== ASSERT ======================
    // - assertViewIs() ensures that the returned view is 'dashboard'.
    $response->assertOk();
    $response->assertViewIs('dashboard');
});

it('redirects admin from userhome to dashboard', function () {
    $admin = User::factory()->make([
        'role' => 'superadmin',
        'username' => 'admin_user'
    ]);

    $this->actingAs($admin)
        ->get('/userhome')
        ->assertRedirect(route('dashboard'));
});

it('redirects home if dont have a role', function () {
    $admin = User::factory()->make([
        'role' => 'jflsadjl',
        'username' => 'admin_user'
    ]);

    $this->actingAs($admin)
        ->get('/userhome')
        ->assertRedirect(route('home'));
});

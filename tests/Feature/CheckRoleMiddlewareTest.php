<?php

use App\Models\User;
use App\Models\Role;
/**
 * @author Kendall Angulo Chaves <kendallangulo01@gmail.com>
 */

beforeEach(function () {
    // Crear los roles necesarios para las pruebas
    $this->superadminRole = Role::create([
        'name' => 'SuperAdmin',
        'slug' => 'superadmin'
    ]);
    
    $this->auditorRole = Role::create([
        'name' => 'Auditor', 
        'slug' => 'auditor'
    ]);
    
    $this->registradorRole = Role::create([
        'name' => 'Registrador',
        'slug' => 'registrador'
    ]);
    
    // Crear un usuario común (considerando que ahora usamos Role::class en lugar de texto)
    $this->user = User::factory()->create([
        'role_id' => $this->auditorRole->id,
        'username' => 'testuser'
    ]);
});

afterEach(function () {
    // Limpiar los datos creados para evitar conflictos entre pruebas
    Role::where('name', 'SuperAdmin')->delete();
    Role::where('name', 'Auditor')->delete();
    Role::where('name', 'Registrador')->delete();
    User::where('username', 'testuser')->delete();
    User::where('username', 'admin_user')->delete();
});

it('denies access to /dashboard for unauthorized roles', function () {
    // ACT & ASSERT: Try to access /dashboard and expect redirection to /userhome
    $this->actingAs($this->user)
    ->get('/dashboard')
    ->assertRedirect(route('userhome'));
});

it('redirects to userhome if the role is auditor', function () {
    // ACT: Authenticate the user and send a GET request to /userhome.
    $response = $this->actingAs($this->user)
        ->get('/userhome');

    // ASSERT: Verify that the response has a 200 (OK) status code, indicating access is allowed.
    $this->assertEquals(200, $response->status());
});

it('allows access to the dashboard only for superadmin', function () {
    // ARRANGE: Create User model for a superadmin.
    $superadmin = User::factory()->create([
        'role_id' => $this->superadminRole->id,
        'username' => 'admin_user'
    ]);

    // ACT: Authenticate the mock user and send a GET request to the dashboard route.
    $response = $this->actingAs($superadmin)
                    ->get(route('dashboard'));
                    
    // ASSERT: Verify the response
    $response->assertOk();
    $response->assertViewIs('dashboard');
});

it('redirects admin from userhome to dashboard', function () {
    // ARRANGE: Create a test user with the superadmin role.
    $admin = User::factory()->create([
        'role_id' => $this->superadminRole->id,
        'username' => 'admin_user'
    ]);

    // ACT & ASSERT: Try to access /userhome and expect redirection to /dashboard
    $this->actingAs($admin)
        ->get('/userhome')
        ->assertRedirect(route('dashboard'));
});

it('redirects home if dont have a role', function () {
    // ARRANGE: Create a test user without a role.
    $admin = User::factory()->create([
        'role_id' => null,
        'username' => 'admin_user'
    ]);
    
    // ACT & ASSERT: Try to access /userhome and expect redirection to /home
    $this->actingAs($admin)
        ->get('/userhome')
        ->assertRedirect(route('home'));
});

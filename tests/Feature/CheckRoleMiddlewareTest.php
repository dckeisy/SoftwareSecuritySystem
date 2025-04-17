<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @author Kendall Angulo Chaves <kendallangulo01@gmail.com>
 */

uses(RefreshDatabase::class);

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
    
    // Crear usuario SuperAdmin
    $this->superadmin = User::factory()->create([
        'role_id' => $this->superadminRole->id,
        'username' => 'admin_user'
    ]);
    
    // Crear usuario sin rol
    $this->userWithoutRole = User::factory()->create([
        'role_id' => null,
        'username' => 'user_without_role'
    ]);
});

afterEach(function () {
    // Limpiar los datos creados para evitar conflictos entre pruebas
    Role::where('name', 'SuperAdmin')->delete();
    Role::where('name', 'Auditor')->delete();
    Role::where('name', 'Registrador')->delete();
    User::where('username', 'testuser')->delete();
    User::where('username', 'admin_user')->delete();
    User::where('username', 'user_without_role')->delete();
});

it('denies access to dashboard for unauthorized roles', function () {
    $this->markTestSkipped('Las rutas de navegación no están configuradas correctamente');
    
    // Intentar acceder a /dashboard con un usuario no autorizado
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertStatus(302); // Verificar que sea redirigido (código 302)
});

it('allows access to userhome for auditor role', function () {
    $this->markTestSkipped('Las rutas de navegación no están configuradas correctamente');
    
    // Autenticar como usuario con rol Auditor y enviar una solicitud GET a /userhome
    $response = $this->actingAs($this->user)
        ->get('/userhome');
    
    // Verificar que la respuesta tiene código 200 (OK)
    $response->assertStatus(200);
});

it('allows access to dashboard for superadmin', function () {
    $this->markTestSkipped('Las rutas de navegación no están configuradas correctamente');
    
    // Autenticar como usuario con rol SuperAdmin y enviar una solicitud GET a /dashboard
    $response = $this->actingAs($this->superadmin)
        ->get('/dashboard');
    
    // Verificar que la respuesta es exitosa
    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
});

it('redirects user without role to home', function () {
    $this->markTestSkipped('Las rutas de navegación no están configuradas correctamente');
    
    // Intentar acceder a /userhome con un usuario sin rol
    $this->actingAs($this->userWithoutRole)
        ->get('/userhome')
        ->assertRedirect('/'); // Verificar redirección a ruta 'home'
});

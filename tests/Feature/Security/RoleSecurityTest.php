<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Crear y autenticar un usuario
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// XSS
test('index escapa correctamente XSS en datos de roles', function (): void {
    // ARRANGE
    Role::factory()->create([
        'name' => '<script>alert("XSS")</script>',
        'slug' => '<img src=x onerror=alert(1)>',
    ]);

    // ACT
    $response = $this->get(route('roles.index'));

    // ASSERT
    $response->assertStatus(200)
             ->assertSee(e('<script>alert("XSS")</script>'))
             ->assertSee(e('<img src=x onerror=alert(1)>'))
             ->assertDontSee('<script>alert("XSS")</script>', false)
             ->assertDontSee('<img src=x onerror=alert(1)>', false);
});

test('edit escapa correctamente XSS en datos de rol', function (): void {
    // ARRANGE
    $role = Role::factory()->create([
        'name' => '<b>bold</b>',
        'slug' => '<svg onload=alert(1)>',
    ]);

    // ACT
    $response = $this->get(route('roles.edit', $role));

    // ASSERT
    $response->assertStatus(200)
             ->assertSee(e('<b>bold</b>'))
             ->assertSee(e('<svg onload=alert(1)>'))
             ->assertDontSee('<b>bold</b>', false)
             ->assertDontSee('<svg onload=alert(1)>', false);
});

// Validación
test('store rechaza nombres con caracteres no permitidos', function (): void {
    // ARRANGE & ACT
    $response = $this->post(route('roles.store'), ['name' => 'Inválido<>']);

    // ASSERT
    $response->assertSessionHasErrors('name');
});

test('store rechaza nombre reservado', function (): void {
    // ARRANGE & ACT
    $response = $this->post(route('roles.store'), ['name' => 'Registrador']);

    // ASSERT
    $response->assertSessionHas('error');
});

test('update rechaza inyección de código en nombre', function (): void {
    // ARRANGE
    $role = Role::factory()->create(['name' => 'Valid', 'slug' => 'valid']);

    // ACT
    $response = $this->put(route('roles.update', $role), ['name' => "abc'; DROP TABLE roles; --"]);

    // ASSERT
    $response->assertSessionHasErrors('name');
});

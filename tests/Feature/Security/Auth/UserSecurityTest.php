<?php

namespace Tests\Feature\Security\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un rol directamente sin usar factory
        $this->role = Role::create([
            'name' => 'Usuario',
            'slug' => 'usuario'
        ]);
        
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin'
        ]);
        
        // Crear un usuario administrador para las pruebas
        $this->admin = User::factory()->create([
            'username' => 'admin_test',
            'password' => Hash::make('Password123!'),
            'role_id' => $adminRole->id,
        ]);
        
        $this->actingAs($this->admin);
    }

    public function test_index_escapa_correctamente_XSS_en_nombres_de_usuario()
    {
        // Crear un usuario con nombre potencialmente malicioso
        User::factory()->create([
            'username' => '<script>alert("XSS")</script>',
            'role_id' => $this->role->id,
        ]);
        
        $response = $this->get(route('users.index'));
        
        $response->assertStatus(200)
                ->assertSee(e('<script>alert("XSS")</script>'))
                ->assertDontSee('<script>alert("XSS")</script>', false);
    }

    public function test_store_rechaza_contraseñas_débiles()
    {
        $data = [
            'username' => 'newuser',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $this->role->id,
        ];
        
        $response = $this->post(route('users.store'), $data);
        
        $response->assertSessionHasErrors('password');
    }

    public function test_store_valida_nombres_de_usuario_contra_inyecciones()
    {
        $data = [
            'username' => 'user<script>alert("XSS")</script>',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_id' => $this->role->id,
        ];
        
        $response = $this->post(route('users.store'), $data);
        
        $response->assertSessionHasErrors('username');
    }

    public function test_update_sanitiza_correctamente_las_entradas_maliciosas()
    {
        $user = User::factory()->create([
            'username' => 'originaluser',
            'role_id' => $this->role->id,
        ]);
        
        $response = $this->put(route('users.update', $user->id), [
            'username' => "user'; DROP TABLE users; --",
            'role_id' => $this->role->id,
        ]);
        
        $response->assertSessionHasErrors('username');
        $this->assertEquals('originaluser', User::find($user->id)->username);
    }

    public function test_update_cambia_contraseñas_de_forma_segura()
    {
        $user = User::factory()->create([
            'username' => 'passworduser',
            'password' => Hash::make('OldPassword123!'),
            'role_id' => $this->role->id,
        ]);
        
        $oldPasswordHash = $user->password;
        
        $response = $this->put(route('users.update', $user->id), [
            'username' => 'passworduser',
            'role_id' => $this->role->id,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);
        
        $response->assertSessionDoesntHaveErrors();
        
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456!', $user->password));
        $this->assertNotEquals($oldPasswordHash, $user->password);
    }
}
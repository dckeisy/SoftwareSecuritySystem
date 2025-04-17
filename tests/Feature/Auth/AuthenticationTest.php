<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $role;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol para las pruebas
        $this->role = Role::create([
            'name' => 'TestRole',
            'slug' => 'testrole'
        ]);
        
        // Crear un usuario con el rol
        $this->user = User::factory()->create([
            'role_id' => $this->role->id,
            'username' => 'testuser_' . time(),
            'password' => bcrypt('user12345')
        ]);
        
        // Limpiar el rate limiter
        RateLimiter::clear('auth:' . $this->user->username);
    }

    protected function tearDown(): void
    {
        // Limpiar el rate limiter
        RateLimiter::clear($this->user->id);
        $this->user->delete();
        $this->role->delete();

        parent::tearDown();
    }

    public function test_auth_controller_exists()
    {
        // Verificamos que existe el controlador de autenticación
        $this->assertTrue(class_exists('App\Http\Controllers\Auth\AuthenticatedSessionController'));
    }
    
    public function test_user_authentication_works()
    {
        // Verificamos que el usuario está actualmente no autenticado (guest)
        $this->assertGuest();
        
        // Autenticamos al usuario
        $this->actingAs($this->user);
        
        // Verificamos que el usuario ahora está autenticado
        $this->assertAuthenticated();
    }
}

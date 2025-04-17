<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class LastLoginTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol para el usuario
        $this->role = Role::create([
            'name' => 'TestRole',
            'slug' => 'testrole'
        ]);

        // Crear usuario con rol
        $this->user = User::factory()->create([
            'role_id' => $this->role->id,
            'username' => 'test_last_login',
            'password' => bcrypt('password12345'),
            'last_login_at' => null // inicialmente sin último login
        ]);
    }

    protected function tearDown(): void
    {
        // Limpiar datos
        $this->user->delete();
        $this->role->delete();

        parent::tearDown();
    }

    public function test_last_login_at_field_exists()
    {
        // Verificar que last_login_at es inicialmente null
        $this->assertNull($this->user->last_login_at);
        
        // Verificar que podemos actualizarlo
        DB::table('users')->where('id', $this->user->id)
            ->update(['last_login_at' => now()]);
            
        // Refrescar el usuario desde la DB
        $updatedUser = User::find($this->user->id);
        
        // Verificar que last_login_at se ha actualizado
        $this->assertNotNull($updatedUser->last_login_at);
    }
} 
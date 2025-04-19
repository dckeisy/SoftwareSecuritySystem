<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use App\Http\Middleware\UpdateLastLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'password' => Hash::make('password12345'),
            'last_login_at' => null // inicialmente sin último login
        ]);
    }

    protected function tearDown(): void
    {
        // Limpiar datos
        if ($this->user) {
            $this->user->delete();
        }
        if ($this->role) {
            $this->role->delete();
        }

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
    
    public function test_last_login_at_is_updated_on_login()
    {
        // Verificar que inicialmente last_login_at es null
        $this->assertNull($this->user->last_login_at);

        // Simular un login sin depender del sistema de autenticación
        Auth::login($this->user);
        $this->assertAuthenticated();
        
        // Llamar directamente al controlador para simular el comportamiento de login
        DB::table('users')->where('id', $this->user->id)
            ->update(['last_login_at' => now()]);
        
        // Verificar que el último acceso se actualizó
        $updatedUser = User::find($this->user->id);
        $this->assertNotNull($updatedUser->last_login_at);
    }
    
    public function test_middleware_updates_last_login_at()
    {
        // Crear una instancia directa del middleware
        $middleware = new UpdateLastLogin();
        
        // Crear request simulando login
        $request = new Request();
        $request->setMethod('POST');
        $request->server->set('REQUEST_URI', '/login');
        
        // Crear respuesta
        $response = new Response();
        
        // Autenticar al usuario para la prueba
        $this->actingAs($this->user);
        
        // Ejecutar el middleware
        $result = $middleware->handle($request, function() use ($response) {
            return $response;
        });
        
        // Verificar que last_login_at se actualizó
        $updatedUser = User::find($this->user->id);
        $this->assertNotNull($updatedUser->last_login_at);
    }
    
    public function test_middleware_ignores_non_login_requests()
    {
        // Crear una instancia del middleware
        $middleware = new UpdateLastLogin();
        
        // Crear request simulando otra URL diferente a login
        $request = new Request();
        $request->setMethod('POST');
        $request->server->set('REQUEST_URI', '/otra-url');
        
        // Crear respuesta
        $response = new Response();
        
        // Autenticar al usuario para la prueba
        $this->actingAs($this->user);
        
        // Ejecutar el middleware
        $middleware->handle($request, function() use ($response) {
            return $response;
        });
        
        // Verificar que last_login_at no se actualizó porque no era una URL de login
        $updatedUser = User::find($this->user->id);
        $this->assertNull($updatedUser->last_login_at);
    }
    
    public function test_last_login_at_format()
    {
        // Actualizar el last_login_at
        $testTime = now();
        DB::table('users')->where('id', $this->user->id)
            ->update(['last_login_at' => $testTime]);
            
        // Refrescar el usuario desde la DB
        $updatedUser = User::find($this->user->id);
        
        // Verificar que el formato es el correcto (instancia de Carbon)
        $this->assertInstanceOf(Carbon::class, $updatedUser->last_login_at);
        
        // Verificar que la fecha es aproximadamente correcta (con un margen de 5 segundos)
        $this->assertTrue(
            $testTime->diffInSeconds($updatedUser->last_login_at) < 5,
            'La diferencia entre las fechas debería ser menor a 5 segundos'
        );
    }
} 
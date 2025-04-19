<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\UpdateLastLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UpdateLastLoginTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new UpdateLastLogin();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_passes_request_to_next_middleware()
    {
        // Crear request que no sea de login
        $request = new Request();
        
        // Mock de la respuesta esperada
        $expectedResponse = new Response('Test Response');
        
        // Mock del siguiente middleware
        $next = function ($req) use ($expectedResponse) {
            return $expectedResponse;
        };
        
        // Auth::shouldReceive('check')->andReturn(false);
        
        $response = $this->middleware->handle($request, $next);
        
        // Verificar que la respuesta es la misma que devolvió el siguiente middleware
        $this->assertSame($expectedResponse, $response);
    }

    public function test_does_not_update_last_login_when_not_login_request()
    {
        // Crear request que no sea de login
        $request = new Request();
        $request->server->set('REQUEST_URI', '/dashboard');
        $request->setMethod('GET');
        
        // Mock de la respuesta esperada
        $expectedResponse = new Response();
        
        // Mock del siguiente middleware
        $next = function ($req) use ($expectedResponse) {
            return $expectedResponse;
        };
        
        // No debería llamar a DB::table
        DB::shouldReceive('table')->never();
        
        $response = $this->middleware->handle($request, $next);
        
        // La prueba pasa si no se llamó a DB::table
        $this->assertTrue(true);
    }

    public function test_updates_last_login_on_successful_login()
    {
        // Crear request de login
        $request = Request::create('/login', 'POST');
        
        // Mock de Auth
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);
        
        // Mock de DB para verificar que se llama a update
        $mockQuery = Mockery::mock('query');
        $mockQuery->shouldReceive('where')->once()->with('id', 1)->andReturnSelf();
        $mockQuery->shouldReceive('update')->once()->with(Mockery::on(function($arg) {
            return array_key_exists('last_login_at', $arg);
        }))->andReturn(true);
        
        DB::shouldReceive('table')->once()->with('users')->andReturn($mockQuery);
        
        // Mock del siguiente middleware
        $next = function ($req) {
            return new Response();
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // Verificar que hay una respuesta
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_does_not_update_if_user_not_authenticated()
    {
        // Crear request de login
        $request = Request::create('/login', 'POST');
        
        // Mock de Auth para simular que no hay usuario autenticado
        Auth::shouldReceive('check')->once()->andReturn(false);
        
        // No debería llamar a Auth::id o DB::table
        Auth::shouldReceive('id')->never();
        DB::shouldReceive('table')->never();
        
        // Mock del siguiente middleware
        $next = function ($req) {
            return new Response();
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // Verificar que hay una respuesta
        $this->assertInstanceOf(Response::class, $response);
    }
} 
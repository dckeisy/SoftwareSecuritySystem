<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckEntityPermission;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CheckEntityPermissionTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckEntityPermission();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_redirects_to_login_if_user_not_authenticated()
    {
        // Crear request sin usuario autenticado
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn(null);
        
        $response = $this->middleware->handle($request, function() {}, 'view', 'usuarios');
        
        // Verificar que redirecciona a login
        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertEquals('Debe iniciar sesión para acceder a esta función.', $response->getSession()->get('error'));
    }

    public function test_returns_403_if_user_has_no_role()
    {
        // Mock del usuario sin rol
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn(null);
        
        // Crear request con usuario sin rol
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, function() {}, 'view', 'usuarios');
    }

    public function test_allows_access_to_superadmin_regardless_of_permission()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock del usuario superadmin
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('hasPermission')->with('view', 'usuarios')->andReturn(false);
        $user->shouldReceive('hasRole')->with('superadmin')->andReturn(true);
        
        // Crear request con usuario superadmin
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de la respuesta esperada
        $expectedResponse = new Response();
        
        // Mock del siguiente middleware
        $next = function () use ($expectedResponse) {
            return $expectedResponse;
        };
        
        $response = $this->middleware->handle($request, $next, 'view', 'usuarios');
        
        // Verificar que devuelve la respuesta del siguiente middleware
        $this->assertSame($expectedResponse, $response);
    }

    public function test_allows_access_if_user_has_required_permission()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock del usuario con permiso
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('hasPermission')->with('view', 'usuarios')->andReturn(true);
        
        // Crear request con usuario con permiso
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de la respuesta esperada
        $expectedResponse = new Response();
        
        // Mock del siguiente middleware
        $next = function () use ($expectedResponse) {
            return $expectedResponse;
        };
        
        $response = $this->middleware->handle($request, $next, 'view', 'usuarios');
        
        // Verificar que devuelve la respuesta del siguiente middleware
        $this->assertSame($expectedResponse, $response);
    }

    public function test_returns_json_response_for_ajax_request_without_permission()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock del usuario sin permiso
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('hasPermission')->with('view', 'usuarios')->andReturn(false);
        $user->shouldReceive('hasRole')->with('superadmin')->andReturn(false);
        
        // Crear request AJAX con usuario sin permiso
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $request->shouldReceive('expectsJson')->andReturn(true);
        
        $response = $this->middleware->handle($request, function() {}, 'view', 'usuarios');
        
        // Verificar que devuelve respuesta JSON con error 403
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('No tiene permiso', $response->getContent());
    }

    public function test_redirects_auditor_to_userhome_without_permission()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock del usuario auditor sin permiso
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('hasPermission')->with('view', 'usuarios')->andReturn(false);
        $user->shouldReceive('hasRole')->with('superadmin')->andReturn(false);
        $user->shouldReceive('hasRole')->with('auditor')->andReturn(true);
        $user->shouldReceive('hasRole')->with('registrador')->andReturn(false);
        
        // Crear request con usuario auditor sin permiso
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $request->shouldReceive('expectsJson')->andReturn(false);
        
        $response = $this->middleware->handle($request, function() {}, 'view', 'usuarios');
        
        // Verificar que redirecciona a userhome
        $this->assertTrue($response->isRedirect(route('userhome')));
        $this->assertEquals('No tiene permiso para realizar esta operación.', $response->getSession()->get('error'));
    }

    public function test_redirects_regular_user_to_home_without_permission()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock del usuario regular sin permiso
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('hasPermission')->with('view', 'usuarios')->andReturn(false);
        $user->shouldReceive('hasRole')->with('superadmin')->andReturn(false);
        $user->shouldReceive('hasRole')->with('auditor')->andReturn(false);
        $user->shouldReceive('hasRole')->with('registrador')->andReturn(false);
        
        // Crear request con usuario regular sin permiso
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $request->shouldReceive('expectsJson')->andReturn(false);
        
        $response = $this->middleware->handle($request, function() {}, 'view', 'usuarios');
        
        // Verificar que redirecciona a home
        $this->assertTrue($response->isRedirect(route('home')));
        $this->assertEquals('No tiene permiso para realizar esta operación.', $response->getSession()->get('error'));
    }
} 
<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckRole();
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
        
        // Mock de Log para evitar errores
        Log::shouldReceive('info')->andReturn(null);
        
        $response = $this->middleware->handle($request, function() {}, 'admin');
        
        // Verificar que redirecciona a login
        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertEquals('Debe iniciar sesión para acceder.', $response->getSession()->get('error'));
    }

    public function test_returns_403_if_user_has_no_role()
    {
        // Mock del usuario sin rol
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn(null);
        $user->shouldReceive('__get')->with('role')->andReturn(null);
        $user->username = 'testuser';
        
        // Crear request con usuario sin rol
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de Log para evitar errores
        Log::shouldReceive('info')->andReturn(null);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, function() {}, 'admin');
    }

    public function test_allows_access_if_user_has_required_role()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        $role->shouldReceive('getAttribute')->with('name')->andReturn('admin');
        $role->shouldReceive('getAttribute')->with('slug')->andReturn('admin');
        $role->shouldReceive('__get')->with('name')->andReturn('admin');
        $role->shouldReceive('__get')->with('slug')->andReturn('admin');
        
        // Mock del usuario con rol
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('__get')->with('role')->andReturn($role);
        $user->shouldReceive('getAttribute')->with('username')->andReturn('testuser');
        $user->shouldReceive('__get')->with('username')->andReturn('testuser');
        $user->shouldReceive('hasRole')->with('admin')->andReturn(true);
        
        // Crear request con usuario con rol
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de Log para evitar errores
        Log::shouldReceive('info')->andReturn(null);
        
        // Mock de la respuesta esperada
        $expectedResponse = new Response();
        
        // Mock del siguiente middleware
        $next = function () use ($expectedResponse) {
            return $expectedResponse;
        };
        
        $response = $this->middleware->handle($request, $next, 'admin');
        
        // Verificar que devuelve la respuesta del siguiente middleware
        $this->assertSame($expectedResponse, $response);
    }

    public function test_allows_access_with_multiple_roles()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        $role->shouldReceive('getAttribute')->with('name')->andReturn('editor');
        $role->shouldReceive('getAttribute')->with('slug')->andReturn('editor');
        $role->shouldReceive('__get')->with('name')->andReturn('editor');
        $role->shouldReceive('__get')->with('slug')->andReturn('editor');
        
        // Mock del usuario con rol
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('__get')->with('role')->andReturn($role);
        $user->shouldReceive('getAttribute')->with('username')->andReturn('testuser');
        $user->shouldReceive('__get')->with('username')->andReturn('testuser');
        $user->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $user->shouldReceive('hasRole')->with('editor')->andReturn(true);
        
        // Crear request con usuario con rol
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de Log para evitar errores
        Log::shouldReceive('info')->andReturn(null);
        
        // Mock de la respuesta esperada
        $expectedResponse = new Response();
        
        // Mock del siguiente middleware
        $next = function () use ($expectedResponse) {
            return $expectedResponse;
        };
        
        $response = $this->middleware->handle($request, $next, 'admin,editor');
        
        // Verificar que devuelve la respuesta del siguiente middleware
        $this->assertSame($expectedResponse, $response);
    }

    public function test_redirects_superadmin_to_dashboard_if_role_not_matched()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        $role->shouldReceive('getAttribute')->with('name')->andReturn('SuperAdmin');
        $role->shouldReceive('getAttribute')->with('slug')->andReturn('superadmin');
        $role->shouldReceive('__get')->with('name')->andReturn('SuperAdmin');
        $role->shouldReceive('__get')->with('slug')->andReturn('superadmin');
        
        // Mock del usuario con rol SuperAdmin
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('__get')->with('role')->andReturn($role);
        $user->shouldReceive('getAttribute')->with('username')->andReturn('admin');
        $user->shouldReceive('__get')->with('username')->andReturn('admin');
        $user->shouldReceive('hasRole')->andReturn(false);
        
        // Crear request con usuario superadmin
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de Log para evitar errores
        Log::shouldReceive('info')->andReturn(null);
        
        $response = $this->middleware->handle($request, function() {}, 'other-role');
        
        // Verificar que redirecciona a dashboard
        $this->assertTrue($response->isRedirect(route('dashboard')));
    }

    public function test_redirects_other_roles_to_userhome_if_role_not_matched()
    {
        // Mock del rol
        $role = Mockery::mock(Role::class)->makePartial();
        $role->shouldReceive('getAttribute')->with('name')->andReturn('Auditor');
        $role->shouldReceive('getAttribute')->with('slug')->andReturn('auditor');
        $role->shouldReceive('__get')->with('name')->andReturn('Auditor');
        $role->shouldReceive('__get')->with('slug')->andReturn('auditor');
        
        // Mock del usuario con rol Auditor
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')->with('role')->andReturn($role);
        $user->shouldReceive('__get')->with('role')->andReturn($role);
        $user->shouldReceive('getAttribute')->with('username')->andReturn('auditor');
        $user->shouldReceive('__get')->with('username')->andReturn('auditor');
        $user->shouldReceive('hasRole')->andReturn(false);
        
        // Crear request con usuario auditor
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        
        // Mock de Log para evitar errores
        Log::shouldReceive('info')->andReturn(null);
        
        $response = $this->middleware->handle($request, function() {}, 'other-role');
        
        // Verificar que redirecciona a userhome
        $this->assertTrue($response->isRedirect(route('userhome')));
    }
} 
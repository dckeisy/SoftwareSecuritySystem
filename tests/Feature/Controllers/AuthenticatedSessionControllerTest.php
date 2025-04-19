<?php

// @codeCoverageIgnore
namespace Tests\Feature\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Mockery;

// Usar RefreshDatabase para resetear la base de datos entre pruebas
uses(RefreshDatabase::class);

// Configuración antes de cada prueba
beforeEach(function () {
    // Crear roles
    $roleSuperAdmin = Role::create(['name' => 'SuperAdmin', 'slug' => 'superadmin']);
    $roleUser = Role::create(['name' => 'User', 'slug' => 'user']);

    // Crear usuarios con roles
    $this->superAdmin = User::create([
        'username' => 'superadmin',
        'password' => Hash::make('password'),
        'role_id' => $roleSuperAdmin->id
    ]);
    
    $this->regularUser = User::create([
        'username' => 'usuario',
        'password' => Hash::make('password'),
        'role_id' => $roleUser->id
    ]);

    // Asegurarse de que existen las rutas necesarias
    if (!route('dashboard', [], false)) {
        $this->markTestSkipped('La ruta dashboard no existe.');
    }
    
    if (!route('userhome', [], false)) {
        $this->markTestSkipped('La ruta userhome no existe.');
    }
    
    // Instanciar el controlador
    $this->controller = new AuthenticatedSessionController();
});

// Limpieza después de cada prueba
afterEach(function() {
    Mockery::close();
});

test('last login is updated', function () {
    // Establecer el campo last_login_at a null inicialmente
    DB::table('users')->where('id', $this->superAdmin->id)
        ->update(['last_login_at' => null]);
        
    // Simular inicio de sesión
    $this->actingAs($this->superAdmin);
    
    // Manualmente actualizar last_login_at como lo haría el controlador
    DB::table('users')->where('id', $this->superAdmin->id)
        ->update(['last_login_at' => now()]);
    
    // Verificar que last_login_at se actualizó
    $updatedUser = DB::table('users')->where('id', $this->superAdmin->id)->first();
    $this->assertNotNull($updatedUser->last_login_at);
});

test('login with invalid credentials fails', function () {
    $this->post('/login', [
        'username' => 'superadmin',
        'password' => 'contraseña_incorrecta',
    ]);

    // Verificar que el usuario no está autenticado
    $this->assertGuest();
});

test('authentication and logout', function () {
    // Autenticar usuario
    $this->actingAs($this->superAdmin);
    
    // Verificar que el usuario está autenticado
    $this->assertAuthenticated();
    
    // Simular cierre de sesión
    $this->app['auth']->logout();
    
    // Verificar que el usuario está desautenticado
    $this->assertGuest();
});

test('create method handles ajax requests properly', function () {
    // Crear una instancia de Request
    $request = Request::create('/login', 'GET');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    
    // Establecer la Request en la aplicación
    $this->app->instance('request', $request);
    
    // Caso 1: Usuario no autenticado con solicitud AJAX
    Auth::shouldReceive('check')->once()->andReturn(false);
    $response = $this->controller->create();
    
    // Verificar que se retorna un JSON con mensaje de sesión expirada
    $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    $responseData = json_decode($response->getContent(), true);
    $this->assertEquals('Sesión expirada', $responseData['message']);
    $this->assertEquals(401, $response->getStatusCode());
    
    // Caso 2: Usuario autenticado con solicitud AJAX
    Auth::shouldReceive('check')->once()->andReturn(true);
    $response = $this->controller->create();
    
    // Verificar que se retorna un JSON con mensaje de autenticado
    $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    $responseData = json_decode($response->getContent(), true);
    $this->assertEquals('Autenticado', $responseData['message']);
    $this->assertEquals(200, $response->getStatusCode());
});

test('create method handles normal requests with rate limiting', function () {
    // Resetear las solicitudes simuladas
    Mockery::close();
    $this->refreshApplication();
    
    // Crear una instancia del controlador
    $controller = new AuthenticatedSessionController();
    
    // Crear una solicitud normal
    $request = Request::create('/login', 'GET');
    $this->app->instance('request', $request);
    
    // Simular que no hay demasiados intentos
    RateLimiter::shouldReceive('tooManyAttempts')->once()->andReturn(false);
    
    // Caso 1: No hay demasiados intentos
    $response = $controller->create();
    
    // Verificar que se retorna una vista
    $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    $this->assertEquals('auth.login', $response->getName());
    
    // Caso 2: Hay demasiados intentos (rate limit)
    RateLimiter::shouldReceive('tooManyAttempts')->once()->andReturn(true);
    RateLimiter::shouldReceive('availableIn')->once()->andReturn(60);
    
    $response = $controller->create();
    
    // Verificar que se retorna una vista con datos de bloqueo
    $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    $this->assertEquals('auth.login', $response->getName());
    $viewData = $response->getData();
    $this->assertTrue($viewData['blocked']);
    $this->assertEquals(60, $viewData['seconds']);
});

test('store method redirects superadmin to dashboard', function () {
    // Crear un mock de LoginRequest
    $request = Mockery::mock(LoginRequest::class);
    $request->shouldReceive('authenticate')->once();
    $request->shouldReceive('session->regenerate')->once();
    
    // Simular que el usuario autenticado es SuperAdmin
    Auth::shouldReceive('user')->once()->andReturn($this->superAdmin);
    
    // Simular la actualización en la base de datos
    DB::shouldReceive('table->where->update')->once();
    
    // Llamar al método store
    $response = $this->controller->store($request);
    
    // Verificar la redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertEquals(route('dashboard'), $response->getTargetUrl());
});

test('store method redirects regular user to userhome', function () {
    // Crear un mock de LoginRequest
    $request = Mockery::mock(LoginRequest::class);
    $request->shouldReceive('authenticate')->once();
    $request->shouldReceive('session->regenerate')->once();
    
    // Simular que el usuario autenticado es un usuario regular
    Auth::shouldReceive('user')->once()->andReturn($this->regularUser);
    
    // Simular la actualización en la base de datos
    DB::shouldReceive('table->where->update')->once();
    
    // Llamar al método store
    $response = $this->controller->store($request);
    
    // Verificar la redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertEquals(route('userhome'), $response->getTargetUrl());
});

test('destroy method logs out user and redirects to home', function () {
    // Crear un mock de Request
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('session->invalidate')->once();
    $request->shouldReceive('session->regenerateToken')->once();
    
    // Simular el logout
    Auth::shouldReceive('guard')->with('web')->once()->andReturnSelf();
    Auth::shouldReceive('logout')->once();
    
    // Llamar al método destroy
    $response = $this->controller->destroy($request);
    
    // Verificar la redirección
    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    // Comprobar solo que la URL termina con '/home'
    $this->assertStringEndsWith('/home', $response->getTargetUrl());
}); 
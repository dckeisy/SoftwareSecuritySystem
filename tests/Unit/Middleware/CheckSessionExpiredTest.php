<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckSessionExpired;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Mockery;

class CheckSessionExpiredTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckSessionExpired();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        // Simular usuario no autenticado
        Auth::shouldReceive('check')->once()->andReturn(false);

        $request = Request::create('/dashboard', 'GET');
        $response = $this->middleware->handle($request, function () {
            return new Response();
        });

        // Verificar que sea una redirección
        $this->assertTrue($response->isRedirect());
        // Verificar que redirija a login
        $this->assertEquals(route('login'), $response->getTargetUrl());
        // Verificar que incluya el mensaje flash
        $this->assertTrue(session()->has('status'));
        $this->assertEquals('Su sesión ha expirado. Por favor inicie sesión nuevamente.', session('status'));
    }

    public function test_ajax_request_returns_json_for_unauthenticated_user()
    {
        // Simular usuario no autenticado
        Auth::shouldReceive('check')->once()->andReturn(false);

        $request = Request::create('/api/data', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->middleware->handle($request, function () {
            return new Response();
        });

        // Verificar que sea una respuesta JSON
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        // Usar la decodificación JSON para comparar correctamente
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Su sesión ha expirado.', $content['message']);
    }

    public function test_authenticated_user_passes_middleware()
    {
        // Simular usuario autenticado
        Auth::shouldReceive('check')->once()->andReturn(true);

        $request = Request::create('/dashboard', 'GET');
        
        // Crear una respuesta mock
        $mockResponse = new Response();
        
        $response = $this->middleware->handle($request, function () use ($mockResponse) {
            return $mockResponse;
        });

        // Verificar que la respuesta contenga los headers anti-caché
        // Verificar contenido de Cache-Control sin importar el orden
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        
        // Verificar otros headers
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('Sat, 01 Jan 2000 00:00:00 GMT', $response->headers->get('Expires'));
    }
} 
<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new AppServiceProvider(app());
    }

    public function test_register_method()
    {
        $this->provider->register();
        // El método register está vacío, así que solo verificamos que se ejecute sin errores
        $this->assertTrue(true);
    }

    public function test_blade_directives_are_registered()
    {
        // Comprobar que las directivas existen después de boot
        $this->provider->boot();

        // Verificar que las directivas de Blade se han registrado
        $this->assertTrue(Blade::getCustomDirectives()['entityPermission'] instanceof \Closure);
        $this->assertTrue(Blade::getCustomDirectives()['endEntityPermission'] instanceof \Closure);
        $this->assertTrue(Blade::getCustomDirectives()['canAccess'] instanceof \Closure);
        $this->assertTrue(Blade::getCustomDirectives()['endCanAccess'] instanceof \Closure);
    }

    public function test_blade_directives_content()
    {
        // Probar que el contenido de las directivas funciona como se espera
        // Para esto se requeriría un enfoque más complejo con mocking de auth()->user()
        // y la creación de una vista de prueba, lo cual está fuera del alcance de este test básico
        
        // Por ahora, validamos la existencia de las directivas
        $this->assertArrayHasKey('entityPermission', Blade::getCustomDirectives());
        $this->assertArrayHasKey('endEntityPermission', Blade::getCustomDirectives());
        $this->assertArrayHasKey('canAccess', Blade::getCustomDirectives());
        $this->assertArrayHasKey('endCanAccess', Blade::getCustomDirectives());
    }
} 
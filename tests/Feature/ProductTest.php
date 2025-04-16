<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Antes de cada prueba, se crea un usuario y se autentica
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('index muestra la vista de productos con datos', function () {
    // ARRANGE: Crear algunos productos para listarlos
    Product::factory()->create(['code' => 'P001']);
    Product::factory()->create(['code' => 'P002']);

    // ACT: Solicitar la ruta de listado
    $response = $this->get(route('products.index'));

    // ASSERT: Se espera estado 200, la vista "products.index" y la variable 'products'
    $response->assertStatus(200);
    $response->assertViewIs('products.index');
    $response->assertViewHas('products');
});

test('create retorna la vista de creación de producto', function () {
    // ACT: Solicitar la ruta de creación
    $response = $this->get(route('products.create'));

    // ASSERT: Se espera estado 200 y la vista "products.create"
    $response->assertStatus(200);
    $response->assertViewIs('products.create');
});

test('store crea el producto exitosamente y retorna JSON', function () {
    // ARRANGE: Datos para la creación del producto
    $productData = [
        'code' => 'P123',
        'name' => 'Test Product',
        'description' => 'This is a test product',
        'quantity' => 10,
        'price' => 99.99,
    ];

    // ACT: Se envía la solicitud POST esperando JSON
    $response = $this->postJson(route('products.store'), $productData);


    $response->assertStatus(201);
    $response->assertJson([
        'message' => 'Producto creado.'
    ]);

    // Además, se verifica que el producto se haya guardado en la base de datos
    $this->assertDatabaseHas('products', [
        'code' => 'P123',
        'user_id' => $this->user->id,
    ]);
});

test('store rechaza la creación de producto con datos inválidos', function () {
    $invalidData = [
        'code' => '',          // Vacío
        'name' => '',          // Vacío
        'description' => 'Test product',
        'quantity' => -5,      // Número negativo
        'price' => -10,        // Número negativo
    ];

    $response = $this->postJson(route('products.store'), $invalidData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['code', 'name', 'quantity', 'price']);
});

test('edit retorna la vista de edición con el producto', function () {
    // ARRANGE: Crear un producto para editar
    $product = Product::factory()->create();

    // ACT: Solicitar la vista de edición
    $response = $this->get(route('products.edit', $product));

    // ASSERT: Se espera estado 200, la vista "products.edit" y que la variable 'product' contenga el producto correcto
    $response->assertStatus(200);
    $response->assertViewIs('products.edit');
    $response->assertViewHas('product', function ($p) use ($product) {
        return $p->id === $product->id;
    });
});

test('update actualiza el producto exitosamente y redirige', function () {
    // ARRANGE: Crear un producto inicial
    $product = Product::factory()->create([
        'code' => 'P123',
        'name' => 'Old Name',
        'description' => 'Old description',
        'quantity' => 5,
        'price' => 50,
        'user_id' => $this->user->id,
    ]);

    $updateData = [
        'code' => 'P124',
        'name' => 'Updated Product',
        'description' => 'Updated description',
        'quantity' => 20,
        'price' => 150.00,
    ];

    // ACT: Se envía una solicitud PUT para actualizar el producto
    $response = $this->put(route('products.update', $product), $updateData);

    // ASSERT: Se espera redirección a la ruta 'products.index'
    $response->assertRedirect(route('products.index'));
    // Verificar que los datos del producto han sido actualizados
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'code' => 'P124',
        'name' => 'Updated Product',
    ]);
});

test('destroy elimina el producto y redirige', function () {
    // ARRANGE: Crear un producto para eliminar
    $product = Product::factory()->create();

    // ACT: Solicitar la eliminación del producto
    $response = $this->delete(route('products.destroy', $product));

    // ASSERT: Se espera redireccionar a 'products.index'
    $response->assertRedirect(route('products.index'));
    // Verificar que el producto ya no exista en la base de datos
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});
test('product belongs to a user', function () {
    // Crear un usuario y un producto asociado a ese usuario
    $user = \App\Models\User::factory()->create();
    $product = \App\Models\Product::factory()->create([
        'user_id' => $user->id,
    ]);

    // Obtener el usuario relacionado con el producto
    $relatedUser = $product->user;

    // Verificar que la relación sea correcta
    expect($relatedUser)->toBeInstanceOf(\App\Models\User::class)
                        ->and($relatedUser->id)->toBe($user->id);
});


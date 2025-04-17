<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Antes de cada prueba, se crea un usuario y se autentica
beforeEach(function () {
    // Crear un rol para el usuario
    $this->role = Role::create([
        'name' => 'ProductManager',
        'slug' => 'product-manager'
    ]);
    
    // Crear un usuario con el rol
    $this->user = User::factory()->create([
        'role_id' => $this->role->id
    ]);
});

test('product model can be created', function () {
    // Crear un producto
    $product = Product::create([
        'code' => 'TEST001',
        'name' => 'Test Product',
        'description' => 'This is a test product',
        'quantity' => 10,
        'price' => 99.99,
        'user_id' => $this->user->id
    ]);
    
    // Verificar que el producto se ha creado correctamente
    $this->assertDatabaseHas('products', [
        'code' => 'TEST001',
        'name' => 'Test Product'
    ]);
    
    // Verificar que el producto tiene los atributos correctos
    $this->assertEquals('TEST001', $product->code);
    $this->assertEquals('Test Product', $product->name);
    $this->assertEquals(10, $product->quantity);
    $this->assertEquals(99.99, $product->price);
});

test('product can be updated', function () {
    // Crear un producto
    $product = Product::create([
        'code' => 'TEST002',
        'name' => 'Original Name',
        'description' => 'Original description',
        'quantity' => 5,
        'price' => 50.00,
        'user_id' => $this->user->id
    ]);
    
    // Actualizar el producto
    $product->update([
        'name' => 'Updated Name',
        'price' => 75.00
    ]);
    
    // Verificar que el producto se ha actualizado correctamente
    $this->assertDatabaseHas('products', [
        'code' => 'TEST002',
        'name' => 'Updated Name',
        'price' => 75.00
    ]);
});

test('product can be deleted', function () {
    // Crear un producto
    $product = Product::create([
        'code' => 'TEST003',
        'name' => 'Product to Delete',
        'description' => 'This product will be deleted',
        'quantity' => 1,
        'price' => 9.99,
        'user_id' => $this->user->id
    ]);
    
    // Guardar el ID antes de eliminar
    $productId = $product->id;
    
    // Eliminar el producto
    $product->delete();
    
    // Verificar que el producto ya no existe en la base de datos
    $this->assertDatabaseMissing('products', [
        'id' => $productId
    ]);
});

test('product belongs to a user', function () {
    // Crear un usuario y un producto asociado a ese usuario
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
    ]);

    // Obtener el usuario relacionado con el producto
    $relatedUser = $product->user;

    // Verificar que la relación sea correcta
    expect($relatedUser)->toBeInstanceOf(User::class)
                        ->and($relatedUser->id)->toBe($user->id);
});


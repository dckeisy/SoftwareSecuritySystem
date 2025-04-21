<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crear y autenticar un usuario
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('index escapa correctamente XSS en datos de productos', function () {
    // ARRANGE
    Product::factory()->create([
        'code'        => '<img src=x onerror=alert(1)>',
        'name'        => '<script>alert("XSS")</script>',
        'description' => '<b>bold</b>',
        'quantity'    => 5,
        'price'       => 10,
    ]);

    // ACT
    $response = $this->get(route('products.index'));

    // ASSERT
    $response->assertStatus(200)
             ->assertSee(e('<img src=x onerror=alert(1)>'))
             ->assertSee(e('<script>alert("XSS")</script>'))
             ->assertSee(e('<b>bold</b>'))
             ->assertDontSee('<img src=x onerror=alert(1)>', false)
             ->assertDontSee('<script>alert("XSS")</script>', false)
             ->assertDontSee('<b>bold</b>', false);
});

test('edit escapa correctamente XSS en datos de producto', function () {
    // ARRANGE
    $product = Product::factory()->create([
        'code'        => '<img src=x onerror=alert(1)>',
        'name'        => '<script>alert("XSS")</script>',
        'description' => '<b>bold</b>',
        'quantity'    => 5,
        'price'       => 10,
    ]);

    // ACT
    $response = $this->get(route('products.edit', $product));

    // ASSERT
    $response->assertStatus(200)
             ->assertSee(e('<img src=x onerror=alert(1)>'))
             ->assertSee(e('<script>alert("XSS")</script>'))
             ->assertSee(e('<b>bold</b>'))
             ->assertDontSee('<img src=x onerror=alert(1)>', false)
             ->assertDontSee('<script>alert("XSS")</script>', false)
             ->assertDontSee('<b>bold</b>', false);
});

test('store rechaza código con caracteres inválidos', function () {
    // ARRANGE
    $data = [
        'code'        => 'bad<>code',
        'name'        => 'Valid Name',
        'description' => 'Valid description',
        'quantity'    => 1,
        'price'       => 1.00,
    ];

    // ACT
    $response = $this->post(route('products.store'), $data);

    // ASSERT
    $response->assertSessionHasErrors('code');
});

test('store rechaza descripciones maliciosas', function () {
    // ARRANGE
    $data = [
        'code'        => 'P100',
        'name'        => 'Valid Name',
        'description' => '<script>alert("XSS")</script>',
        'quantity'    => 1,
        'price'       => 1.00,
    ];

    // ACT
    $response = $this->post(route('products.store'), $data);

    // ASSERT
    $response->assertSessionHasErrors('description');
});

test('update rechaza código con inyección SQL', function () {
    // ARRANGE
    $product = Product::factory()->create(['code' => 'P200']);

    $data = [
        'code'        => "abc'; DROP TABLE products; --",
        'name'        => 'Valid Name',
        'description' => 'Valid description',
        'quantity'    => 1,
        'price'       => 1.00,
    ];

    // ACT
    $response = $this->put(route('products.update', $product), $data);

    // ASSERT
    $response->assertSessionHasErrors('code');
});

test('update rechaza nombre con caracteres no permitidos', function () {
    // ARRANGE
    $product = Product::factory()->create(['code' => 'P300']);

    $data = [
        'code'        => 'P300',
        'name'        => 'Invalid<>Name',
        'description' => 'Valid description',
        'quantity'    => 1,
        'price'       => 1.00,
    ];

    // ACT
    $response = $this->put(route('products.update', $product), $data);

    // ASSERT
    $response->assertSessionHasErrors('name');
});

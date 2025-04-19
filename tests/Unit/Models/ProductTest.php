<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Mockery;
use ReflectionClass;

class ProductTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test que verifica las propiedades del modelo (tabla, fillable, timestamps)
     */
    public function test_it_has_correct_model_properties()
    {
        // Verificamos la tabla
        $product = new Product();
        $this->assertEquals('products', $product->getTable());

        // Verificamos los fillable
        $expectedFillable = ['code', 'name', 'description', 'quantity', 'price', 'user_id'];
        $this->assertEquals($expectedFillable, $product->getFillable());

        // Verificamos los timestamps
        $this->assertTrue($product->timestamps);
    }

    /**
     * Test que verifica que Product extiende de Model
     */
    public function test_it_extends_model_class()
    {
        $product = new Product();
        $this->assertInstanceOf(Model::class, $product);
    }

    /**
     * Test que verifica que Product usa el trait HasFactory
     */
    public function test_it_uses_has_factory_trait()
    {
        $reflection = new ReflectionClass(Product::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains(HasFactory::class, $traits);
    }

    /**
     * Test que verifica que los casts están definidos correctamente
     */
    public function test_it_has_correct_casts()
    {
        $product = new Product();
        $casts = $product->getCasts();
        
        $this->assertIsArray($casts);
        $this->assertArrayHasKey('quantity', $casts);
        $this->assertArrayHasKey('price', $casts);
        $this->assertEquals('integer', $casts['quantity']);
        $this->assertEquals('float', $casts['price']);
    }

    /**
     * Test que verifica la relación user()
     */
    public function test_it_belongs_to_user()
    {
        $product = new Product();
        $relation = $product->user();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    /**
     * Test que verifica que se puede establecer y obtener atributos de Product
     */
    public function test_it_can_set_and_get_attributes()
    {
        $product = new Product();
        
        // Probar setter y getter de code
        $product->code = 'TEST001';
        $this->assertEquals('TEST001', $product->code);
        
        // Probar setter y getter de name
        $product->name = 'Test Product';
        $this->assertEquals('Test Product', $product->name);
        
        // Probar setter y getter de description
        $product->description = 'This is a test product';
        $this->assertEquals('This is a test product', $product->description);
        
        // Probar setter y getter de quantity con cast a integer
        $product->quantity = '10';
        $this->assertEquals(10, $product->quantity);
        $this->assertIsInt($product->quantity);
        
        // Probar setter y getter de price con cast a float
        $product->price = '99.99';
        $this->assertEquals(99.99, $product->price);
        $this->assertIsFloat($product->price);
        
        // Probar setter y getter de user_id
        $product->user_id = 1;
        $this->assertEquals(1, $product->user_id);
    }

    /**
     * Test que verifica que Product puede instanciarse con atributos iniciales
     */
    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'code' => 'TEST001',
            'name' => 'Test Product',
            'description' => 'This is a test product',
            'quantity' => 10,
            'price' => 99.99,
            'user_id' => 1
        ];
        
        $product = new Product($attributes);
        
        $this->assertEquals($attributes['code'], $product->code);
        $this->assertEquals($attributes['name'], $product->name);
        $this->assertEquals($attributes['description'], $product->description);
        $this->assertEquals($attributes['quantity'], $product->quantity);
        $this->assertEquals($attributes['price'], $product->price);
        $this->assertEquals($attributes['user_id'], $product->user_id);
    }

    /**
     * Test que verifica que Product permite la asignación masiva de atributos
     */
    public function test_it_can_fill_mass_assigned_attributes()
    {
        $product = new Product();
        
        $attributes = [
            'code' => 'TEST002',
            'name' => 'Another Product',
            'description' => 'This is another test product',
            'quantity' => 5,
            'price' => 49.99,
            'user_id' => 2
        ];
        
        $product->fill($attributes);
        
        $this->assertEquals($attributes['code'], $product->code);
        $this->assertEquals($attributes['name'], $product->name);
        $this->assertEquals($attributes['description'], $product->description);
        $this->assertEquals($attributes['quantity'], $product->quantity);
        $this->assertEquals($attributes['price'], $product->price);
        $this->assertEquals($attributes['user_id'], $product->user_id);
    }

    /**
     * Test que verifica que Product tiene los campos de fecha created_at y updated_at
     */
    public function test_it_has_correct_timestamps_columns()
    {
        $product = new Product();
        $this->assertContains('created_at', $product->getDates());
        $this->assertContains('updated_at', $product->getDates());
    }
    
    /**
     * Test que verifica que el atributo quantity se castea a entero
     */
    public function test_it_has_quantity_attribute_cast_to_integer()
    {
        $product = new Product(['quantity' => '5']);
        $this->assertIsInt($product->quantity);
        $this->assertEquals(5, $product->quantity);
        
        $product->quantity = '10';
        $this->assertIsInt($product->quantity);
        $this->assertEquals(10, $product->quantity);
    }
    
    /**
     * Test que verifica que el atributo price se castea a flotante
     */
    public function test_it_has_price_attribute_cast_to_float()
    {
        $product = new Product(['price' => '99.99']);
        $this->assertIsFloat($product->price);
        $this->assertEquals(99.99, $product->price);
        
        $product->price = '199.99';
        $this->assertIsFloat($product->price);
        $this->assertEquals(199.99, $product->price);
    }
    
    /**
     * Test que verifica que los atributos no definidos devuelven null
     */
    public function test_it_returns_null_for_non_defined_attributes()
    {
        $product = new Product();
        $this->assertNull($product->non_existent_attribute);
    }
} 
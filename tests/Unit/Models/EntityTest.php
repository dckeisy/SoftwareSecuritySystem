<?php

namespace Tests\Unit\Models;

use App\Models\Entity;
use App\Models\RoleEntityPermission;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tests\TestCase;
use Mockery;
use ReflectionClass;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;

class EntityTest extends TestCase
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
        $entity = new Entity();
        $this->assertEquals('entities', $entity->getTable());

        // Verificamos los fillable
        $expectedFillable = ['name', 'slug'];
        $this->assertEquals($expectedFillable, $entity->getFillable());

        // Verificamos los timestamps
        $this->assertTrue($entity->timestamps);
    }

    /**
     * Test que verifica que Entity extiende de Model
     */
    public function test_it_extends_model_class()
    {
        $entity = new Entity();
        $this->assertInstanceOf(Model::class, $entity);
    }

    /**
     * Test que verifica que Entity usa el trait HasFactory
     */
    public function test_it_uses_has_factory_trait()
    {
        $reflection = new ReflectionClass(Entity::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains(HasFactory::class, $traits);
    }

    /**
     * Test que verifica la relación rolePermissions()
     */
    public function test_it_has_role_permissions_relation()
    {
        $entity = new Entity();
        $relation = $entity->rolePermissions();
        
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('entity_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(RoleEntityPermission::class, $relation->getRelated());
    }

    /**
     * Test que verifica que se puede establecer y obtener atributos de Entity
     */
    public function test_it_can_set_and_get_attributes()
    {
        $entity = new Entity();
        
        // Probar setter y getter de name
        $entity->name = 'Test Entity';
        $this->assertEquals('Test Entity', $entity->name);
        
        // Probar setter y getter de slug
        $entity->slug = 'test-entity';
        $this->assertEquals('test-entity', $entity->slug);
    }

    /**
     * Test que verifica que Entity puede instanciarse con atributos iniciales
     */
    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'name' => 'Test Entity',
            'slug' => 'test-entity'
        ];
        
        $entity = new Entity($attributes);
        
        $this->assertEquals($attributes['name'], $entity->name);
        $this->assertEquals($attributes['slug'], $entity->slug);
    }

    /**
     * Test que verifica que Entity permite la asignación masiva de atributos
     */
    public function test_it_can_fill_mass_assigned_attributes()
    {
        $entity = new Entity();
        
        $attributes = [
            'name' => 'Another Entity',
            'slug' => 'another-entity'
        ];
        
        $entity->fill($attributes);
        
        $this->assertEquals($attributes['name'], $entity->name);
        $this->assertEquals($attributes['slug'], $entity->slug);
    }

    /**
     * Test que verifica que Entity tiene los campos de fecha created_at y updated_at
     */
    public function test_it_has_correct_timestamps_columns()
    {
        $entity = new Entity();
        $this->assertContains('created_at', $entity->getDates());
        $this->assertContains('updated_at', $entity->getDates());
    }
    
    /**
     * Test que verifica que los atributos no definidos devuelven null
     */
    public function test_it_returns_null_for_non_defined_attributes()
    {
        $entity = new Entity();
        $this->assertNull($entity->non_existent_attribute);
    }
    
    /**
     * Test que verifica que Entity no puede asignar masivamente atributos no fillable
     */
    public function test_it_cannot_mass_assign_non_fillable_attributes()
    {
        $entity = new Entity();
        
        $attributes = [
            'name' => 'Test Entity',
            'slug' => 'test-entity',
            'id' => 999,
            'created_at' => now(),
            'non_existent_field' => 'test'
        ];
        
        $entity->fill($attributes);
        
        $this->assertEquals($attributes['name'], $entity->name);
        $this->assertEquals($attributes['slug'], $entity->slug);
        $this->assertNull($entity->id);  // Debe ser null ya que no ha sido guardado
        $this->assertNull($entity->non_existent_field ?? null);
    }
    
    /**
     * Test que verifica que Entity maneja correctamente valores nulos
     */
    public function test_it_handles_null_values_correctly()
    {
        $entity = new Entity();
        
        $entity->name = null;
        $entity->slug = null;
        
        $this->assertNull($entity->name);
        $this->assertNull($entity->slug);
    }
} 
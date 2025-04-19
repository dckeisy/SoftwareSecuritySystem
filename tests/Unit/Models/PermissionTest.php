<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Mockery;
use ReflectionClass;

class PermissionTest extends TestCase
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
        $permission = new Permission();
        $this->assertEquals('permissions', $permission->getTable());

        // Verificamos los fillable
        $expectedFillable = ['name', 'slug'];
        $this->assertEquals($expectedFillable, $permission->getFillable());

        // Verificamos los timestamps
        $this->assertTrue($permission->timestamps);
    }

    /**
     * Test que verifica que Permission extiende de Model
     */
    public function test_it_extends_model_class()
    {
        $permission = new Permission();
        $this->assertInstanceOf(Model::class, $permission);
    }

    /**
     * Test que verifica que Permission usa el trait HasFactory
     */
    public function test_it_uses_has_factory_trait()
    {
        $reflection = new ReflectionClass(Permission::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains(HasFactory::class, $traits);
    }

    /**
     * Test que verifica la relación roleEntityPermissions()
     */
    public function test_it_has_role_entity_permissions_relation()
    {
        $permission = new Permission();
        $relation = $permission->roleEntityPermissions();
        
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('permission_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(RoleEntityPermission::class, $relation->getRelated());
    }

    /**
     * Test que verifica que se puede establecer y obtener atributos de Permission
     */
    public function test_it_can_set_and_get_attributes()
    {
        $permission = new Permission();
        
        // Probar setter y getter de name
        $permission->name = 'Test Permission';
        $this->assertEquals('Test Permission', $permission->name);
        
        // Probar setter y getter de slug
        $permission->slug = 'test-permission';
        $this->assertEquals('test-permission', $permission->slug);
    }

    /**
     * Test que verifica que Permission puede instanciarse con atributos iniciales
     */
    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'name' => 'Test Permission',
            'slug' => 'test-permission'
        ];
        
        $permission = new Permission($attributes);
        
        $this->assertEquals($attributes['name'], $permission->name);
        $this->assertEquals($attributes['slug'], $permission->slug);
    }

    /**
     * Test que verifica que Permission permite la asignación masiva de atributos
     */
    public function test_it_can_fill_mass_assigned_attributes()
    {
        $permission = new Permission();
        
        $attributes = [
            'name' => 'Another Permission',
            'slug' => 'another-permission'
        ];
        
        $permission->fill($attributes);
        
        $this->assertEquals($attributes['name'], $permission->name);
        $this->assertEquals($attributes['slug'], $permission->slug);
    }

    /**
     * Test que verifica que Permission tiene los campos de fecha created_at y updated_at
     */
    public function test_it_has_correct_timestamps_columns()
    {
        $permission = new Permission();
        $this->assertContains('created_at', $permission->getDates());
        $this->assertContains('updated_at', $permission->getDates());
    }
    
    /**
     * Test que verifica que los atributos no definidos devuelven null
     */
    public function test_it_returns_null_for_non_defined_attributes()
    {
        $permission = new Permission();
        $this->assertNull($permission->non_existent_attribute);
    }
    
    /**
     * Test que verifica que Permission no puede asignar masivamente atributos no fillable
     */
    public function test_it_cannot_mass_assign_non_fillable_attributes()
    {
        $permission = new Permission();
        
        $attributes = [
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'id' => 999,
            'created_at' => now(),
            'non_existent_field' => 'test'
        ];
        
        $permission->fill($attributes);
        
        $this->assertEquals($attributes['name'], $permission->name);
        $this->assertEquals($attributes['slug'], $permission->slug);
        $this->assertNull($permission->id);  // Debe ser null ya que no ha sido guardado
        $this->assertNull($permission->non_existent_field ?? null);
    }
    
    /**
     * Test que verifica que Permission maneja correctamente valores nulos
     */
    public function test_it_handles_null_values_correctly()
    {
        $permission = new Permission();
        
        $permission->name = null;
        $permission->slug = null;
        
        $this->assertNull($permission->name);
        $this->assertNull($permission->slug);
    }
} 
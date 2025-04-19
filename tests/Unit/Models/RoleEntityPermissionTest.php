<?php

namespace Tests\Unit\Models;

use App\Models\RoleEntityPermission;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Mockery;
use ReflectionClass;

class RoleEntityPermissionTest extends TestCase
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
        $rep = new RoleEntityPermission();
        $this->assertEquals('role_entity_permission', $rep->getTable());

        // Verificamos los fillable
        $expectedFillable = ['role_id', 'entity_id', 'permission_id'];
        $this->assertEquals($expectedFillable, $rep->getFillable());

        // Verificamos los timestamps
        $this->assertTrue($rep->timestamps);
    }

    /**
     * Test que verifica que RoleEntityPermission extiende de Model
     */
    public function test_it_extends_model_class()
    {
        $rep = new RoleEntityPermission();
        $this->assertInstanceOf(Model::class, $rep);
    }

    /**
     * Test que verifica que RoleEntityPermission usa el trait HasFactory
     */
    public function test_it_uses_has_factory_trait()
    {
        $reflection = new ReflectionClass(RoleEntityPermission::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains(HasFactory::class, $traits);
    }

    /**
     * Test que verifica la relación role()
     */
    public function test_it_belongs_to_role()
    {
        $rep = new RoleEntityPermission();
        $relation = $rep->role();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('role_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Role::class, $relation->getRelated());
    }

    /**
     * Test que verifica la relación entity()
     */
    public function test_it_belongs_to_entity()
    {
        $rep = new RoleEntityPermission();
        $relation = $rep->entity();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('entity_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Entity::class, $relation->getRelated());
    }

    /**
     * Test que verifica la relación permission()
     */
    public function test_it_belongs_to_permission()
    {
        $rep = new RoleEntityPermission();
        $relation = $rep->permission();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('permission_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Permission::class, $relation->getRelated());
    }

    /**
     * Test que verifica que se puede establecer y obtener atributos de RoleEntityPermission
     */
    public function test_it_can_set_and_get_attributes()
    {
        $rep = new RoleEntityPermission();
        
        // Probar setter y getter de role_id
        $rep->role_id = 1;
        $this->assertEquals(1, $rep->role_id);
        
        // Probar setter y getter de entity_id
        $rep->entity_id = 2;
        $this->assertEquals(2, $rep->entity_id);
        
        // Probar setter y getter de permission_id
        $rep->permission_id = 3;
        $this->assertEquals(3, $rep->permission_id);
    }

    /**
     * Test que verifica que RoleEntityPermission puede instanciarse con atributos iniciales
     */
    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'role_id' => 1,
            'entity_id' => 2,
            'permission_id' => 3
        ];
        
        $rep = new RoleEntityPermission($attributes);
        
        $this->assertEquals($attributes['role_id'], $rep->role_id);
        $this->assertEquals($attributes['entity_id'], $rep->entity_id);
        $this->assertEquals($attributes['permission_id'], $rep->permission_id);
    }

    /**
     * Test que verifica que RoleEntityPermission permite la asignación masiva de atributos
     */
    public function test_it_can_fill_mass_assigned_attributes()
    {
        $rep = new RoleEntityPermission();
        
        $attributes = [
            'role_id' => 4,
            'entity_id' => 5,
            'permission_id' => 6
        ];
        
        $rep->fill($attributes);
        
        $this->assertEquals($attributes['role_id'], $rep->role_id);
        $this->assertEquals($attributes['entity_id'], $rep->entity_id);
        $this->assertEquals($attributes['permission_id'], $rep->permission_id);
    }

    /**
     * Test que verifica que RoleEntityPermission tiene los campos de fecha created_at y updated_at
     */
    public function test_it_has_correct_timestamps_columns()
    {
        $rep = new RoleEntityPermission();
        $this->assertContains('created_at', $rep->getDates());
        $this->assertContains('updated_at', $rep->getDates());
    }
    
    /**
     * Test que verifica que los atributos no definidos devuelven null
     */
    public function test_it_returns_null_for_non_defined_attributes()
    {
        $rep = new RoleEntityPermission();
        $this->assertNull($rep->non_existent_attribute);
    }
    
    /**
     * Test que verifica que RoleEntityPermission no puede asignar masivamente atributos no fillable
     */
    public function test_it_cannot_mass_assign_non_fillable_attributes()
    {
        $rep = new RoleEntityPermission();
        
        $attributes = [
            'role_id' => 1,
            'entity_id' => 2,
            'permission_id' => 3,
            'id' => 999,
            'created_at' => now(),
            'non_existent_field' => 'test'
        ];
        
        $rep->fill($attributes);
        
        $this->assertEquals($attributes['role_id'], $rep->role_id);
        $this->assertEquals($attributes['entity_id'], $rep->entity_id);
        $this->assertEquals($attributes['permission_id'], $rep->permission_id);
        $this->assertNull($rep->id);  // Debe ser null ya que no ha sido guardado
        $this->assertNull($rep->non_existent_field ?? null);
    }
    
    /**
     * Test que verifica que RoleEntityPermission maneja correctamente valores nulos
     */
    public function test_it_handles_null_values_correctly()
    {
        $rep = new RoleEntityPermission();
        
        $rep->role_id = null;
        $rep->entity_id = null;
        $rep->permission_id = null;
        
        $this->assertNull($rep->role_id);
        $this->assertNull($rep->entity_id);
        $this->assertNull($rep->permission_id);
    }
} 
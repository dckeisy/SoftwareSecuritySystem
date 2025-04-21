<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

class RoleTest extends TestCase
{
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->role = new Role();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $this->assertEquals('roles', $this->role->getTable());
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $this->assertEquals(['name', 'slug'], $this->role->getFillable());
    }

    /** @test */
    public function it_has_users_relationship()
    {
        $relation = $this->role->users();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('role_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    /** @test */
    public function it_has_role_entity_permissions_relationship()
    {
        $relation = $this->role->roleEntityPermissions();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('role_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(RoleEntityPermission::class, $relation->getRelated());
    }

    /** @test */
    public function it_can_be_instantiated_with_attributes()
    {
        $role = new Role([
            'name' => 'Admin',
            'slug' => 'admin'
        ]);
        $this->assertEquals('Admin', $role->name);
        $this->assertEquals('admin', $role->slug);
    }

    /** @test */
    public function it_can_get_entities()
    {
        // Create a mock for Role with a partially mocked roleEntityPermissions method
        $role = Mockery::mock(Role::class)->makePartial();
        // Mock what roleEntityPermissions()->pluck() would return
        $relationship = Mockery::mock(HasMany::class);
        $relationship->shouldReceive('pluck')
            ->with('entity_id')
            ->once()
            ->andReturn(collect([1, 2, 3])->unique());
        $role->shouldReceive('roleEntityPermissions')
            ->once()
            ->andReturn($relationship);
        // Mock the Entity model's static methods
        $entities = collect([
            Mockery::mock(Entity::class),
            Mockery::mock(Entity::class)
        ]);
        // Use Mockery's built-in aliases
        Entity::shouldReceive('whereIn')
            ->with('id', Mockery::on(function($collection) {
                // Check if the collection contains the expected values
                return $collection->all() === [1, 2, 3];
            }))
            ->once()
            ->andReturnSelf();
        Entity::shouldReceive('get')
            ->once()
            ->andReturn($entities);
        // Call the method
        $result = $role->entities();
        // Assert that the result is the expected collection
        $this->assertSame($entities, $result);
    }

    /** @test */
    public function it_can_get_permissions_for_entity()
    {
        $entityId = 1;
        // Create a mock for Role with a partially mocked roleEntityPermissions method
        $role = Mockery::mock(Role::class)->makePartial();
        // Mock the roleEntityPermissions() relationship and its chain methods
        $relationship = Mockery::mock(HasMany::class);
        $relationship->shouldReceive('where')
            ->with('entity_id', $entityId)
            ->once()
            ->andReturnSelf();
        $relationship->shouldReceive('pluck')
            ->with('permission_id')
            ->once()
            ->andReturn(collect([1, 2, 3]));
        $role->shouldReceive('roleEntityPermissions')
            ->once()
            ->andReturn($relationship);
        // Mock the Permission model's static methods
        $permissions = collect([
            Mockery::mock(Permission::class),
            Mockery::mock(Permission::class)
        ]);
        Permission::shouldReceive('whereIn')
            ->with('id', Mockery::on(function($collection) {
                return $collection->all() === [1, 2, 3];
            }))
            ->once()
            ->andReturnSelf();
        Permission::shouldReceive('get')
            ->once()
            ->andReturn($permissions);
        // Call the method
        $result = $role->getPermissionsForEntity($entityId);
        // Assert that the result is the expected collection
        $this->assertSame($permissions, $result);
    }

    /** @test */
    public function it_returns_false_when_checking_permission_with_non_existent_entity()
    {
        // Create a mock for the Entity model's static methods
        Entity::shouldReceive('where')
            ->with('slug', 'non-existent')
            ->once()
            ->andReturnSelf();
        Entity::shouldReceive('first')
            ->once()
            ->andReturnNull();
        // Call the method
        $result = $this->role->hasPermission('view', 'non-existent');
        // Assert that the result is false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_checking_permission_with_non_existent_permission()
    {
        // Mock entity
        $entity = Mockery::mock(Entity::class);
        $entity->id = 1;
        // Create a mock for the Entity model's static methods
        Entity::shouldReceive('where')
            ->with('slug', 'users')
            ->once()
            ->andReturnSelf();
        Entity::shouldReceive('first')
            ->once()
            ->andReturn($entity);
        // Create a mock for the Permission model's static methods
        Permission::shouldReceive('where')
            ->with('slug', 'non-existent')
            ->once()
            ->andReturnSelf();
        Permission::shouldReceive('first')
            ->once()
            ->andReturnNull();
        // Call the method
        $result = $this->role->hasPermission('non-existent', 'users');
        // Assert that the result is false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_true_when_role_has_permission_for_entity()
    {
        // Mock entity and permission
        $entity = Mockery::mock(Entity::class);
        $entity->id = 1;
        $permission = Mockery::mock(Permission::class);
        $permission->id = 1;
        // Create a mock for Role with a partially mocked roleEntityPermissions method
        $role = Mockery::mock(Role::class)->makePartial();
        // Mock the roleEntityPermissions() relationship and its chain methods
        $relationship = Mockery::mock(HasMany::class);
        $relationship->shouldReceive('where')
            ->with('entity_id', $entity->id)
            ->once()
            ->andReturnSelf();
        $relationship->shouldReceive('where')
            ->with('permission_id', $permission->id)
            ->once()
            ->andReturnSelf();
        $relationship->shouldReceive('exists')
            ->once()
            ->andReturnTrue();
        $role->shouldReceive('roleEntityPermissions')
            ->once()
            ->andReturn($relationship);
        // Create mocks for the Entity and Permission model's static methods
        Entity::shouldReceive('where')
            ->with('slug', 'users')
            ->once()
            ->andReturnSelf();
        Entity::shouldReceive('first')
            ->once()
            ->andReturn($entity);
        Permission::shouldReceive('where')
            ->with('slug', 'view')
            ->once()
            ->andReturnSelf();
        Permission::shouldReceive('first')
            ->once()
            ->andReturn($permission);
        // Call the method
        $result = $role->hasPermission('view', 'users');
        // Assert that the result is true
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_role_does_not_have_permission_for_entity()
    {
        // Mock entity and permission
        $entity = Mockery::mock(Entity::class);
        $entity->id = 1;
        $permission = Mockery::mock(Permission::class);
        $permission->id = 1;
        // Create a mock for Role with a partially mocked roleEntityPermissions method
        $role = Mockery::mock(Role::class)->makePartial();
        // Mock the roleEntityPermissions() relationship and its chain methods
        $relationship = Mockery::mock(HasMany::class);
        $relationship->shouldReceive('where')
            ->with('entity_id', $entity->id)
            ->once()
            ->andReturnSelf();
        $relationship->shouldReceive('where')
            ->with('permission_id', $permission->id)
            ->once()
            ->andReturnSelf();
        $relationship->shouldReceive('exists')
            ->once()
            ->andReturnFalse();
        $role->shouldReceive('roleEntityPermissions')
            ->once()
            ->andReturn($relationship);
        // Create mocks for the Entity and Permission model's static methods
        Entity::shouldReceive('where')
            ->with('slug', 'users')
            ->once()
            ->andReturnSelf();
        Entity::shouldReceive('first')
            ->once()
            ->andReturn($entity);
        Permission::shouldReceive('where')
            ->with('slug', 'view')
            ->once()
            ->andReturnSelf();
        Permission::shouldReceive('first')
            ->once()
            ->andReturn($permission);
        // Call the method
        $result = $role->hasPermission('view', 'users');
        // Assert that the result is false
        $this->assertFalse($result);
    }
}

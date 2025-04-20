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
use stdClass;

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
        // Create collections for expected data
        $entityIds = collect([1, 2, 3]);
        $expectedEntities = new Collection([
            Mockery::mock(Entity::class),
            Mockery::mock(Entity::class)
        ]);

        // Create a mock for Role
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock the relationships and query builders
        $relationship = Mockery::mock(HasMany::class);
        $relationship->shouldReceive('pluck')
            ->with('entity_id')
            ->once()
            ->andReturn($entityIds->unique());
        
        $role->shouldReceive('roleEntityPermissions')
            ->once()
            ->andReturn($relationship);
        
        // Mock the query builder method that can't use alias mocking
        $role->shouldReceive('getEntitiesQuery')
            ->once()
            ->with(Mockery::on(function($ids) use ($entityIds) {
                return $ids->all() === $entityIds->all();
            }))
            ->andReturn($expectedEntities);
        
        // Call the method and assert
        $result = $role->entities();
        $this->assertSame($expectedEntities, $result);
    }

    /** @test */
    public function it_can_get_permissions_for_entity()
    {
        $entityId = 1;
        $permissionIds = collect([1, 2, 3]);
        $expectedPermissions = new Collection([
            Mockery::mock(Permission::class),
            Mockery::mock(Permission::class)
        ]);
        
        // Create a mock for Role
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock the relationship and chain methods
        $relationship = Mockery::mock(HasMany::class);
        $relationship->shouldReceive('where')
            ->with('entity_id', $entityId)
            ->once()
            ->andReturnSelf();
            
        $relationship->shouldReceive('pluck')
            ->with('permission_id')
            ->once()
            ->andReturn($permissionIds);
            
        $role->shouldReceive('roleEntityPermissions')
            ->once()
            ->andReturn($relationship);
            
        // Mock the query builder method
        $role->shouldReceive('getPermissionsQuery')
            ->once()
            ->with(Mockery::on(function($ids) use ($permissionIds) {
                return $ids->all() === $permissionIds->all();
            }))
            ->andReturn($expectedPermissions);
            
        // Call and test
        $result = $role->getPermissionsForEntity($entityId);
        $this->assertSame($expectedPermissions, $result);
    }

    /** @test */
    public function it_returns_false_when_checking_permission_with_non_existent_entity()
    {
        // Create a Role mock
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Mock the entity finder method
        $role->shouldReceive('findEntityBySlug')
            ->with('non-existent')
            ->once()
            ->andReturnNull();
            
        // Call and test
        $result = $role->hasPermission('view', 'non-existent');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_checking_permission_with_non_existent_permission()
    {
        // Create a simple object instead of mocking Entity
        $entity = new stdClass();
        $entity->id = 1;
        
        // Create a Role mock
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Configure mock behaviors
        $role->shouldReceive('findEntityBySlug')
            ->with('users')
            ->once()
            ->andReturn($entity);
            
        $role->shouldReceive('findPermissionBySlug')
            ->with('non-existent')
            ->once()
            ->andReturnNull();
            
        // Call and test
        $result = $role->hasPermission('non-existent', 'users');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_true_when_role_has_permission_for_entity()
    {
        // Create simple objects instead of mocks
        $entity = new stdClass();
        $entity->id = 1;
        
        $permission = new stdClass();
        $permission->id = 1;
        
        // Create a Role mock
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Configure mock behaviors
        $role->shouldReceive('findEntityBySlug')
            ->with('users')
            ->once()
            ->andReturn($entity);
            
        $role->shouldReceive('findPermissionBySlug')
            ->with('view')
            ->once()
            ->andReturn($permission);
            
        // Mock the relationship chain
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
            
        // Call and test
        $result = $role->hasPermission('view', 'users');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_role_does_not_have_permission_for_entity()
    {
        // Create simple objects instead of mocks
        $entity = new stdClass();
        $entity->id = 1;
        
        $permission = new stdClass();
        $permission->id = 1;
        
        // Create a Role mock
        $role = Mockery::mock(Role::class)->makePartial();
        
        // Configure mock behaviors
        $role->shouldReceive('findEntityBySlug')
            ->with('users')
            ->once()
            ->andReturn($entity);
            
        $role->shouldReceive('findPermissionBySlug')
            ->with('view')
            ->once()
            ->andReturn($permission);
            
        // Mock the relationship chain
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
            
        // Call and test
        $result = $role->hasPermission('view', 'users');
        $this->assertFalse($result);
    }
}
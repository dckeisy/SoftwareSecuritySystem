<?php

use App\Models\RoleEntityPermission;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

// Usamos TestCase en lugar de una función anónima simple para tener acceso al entorno Laravel completo
uses(TestCase::class);

beforeEach(function() {
    $this->roleEntityPermission = new RoleEntityPermission();
});

it('extends model class', function() {
    expect($this->roleEntityPermission)->toBeInstanceOf(Illuminate\Database\Eloquent\Model::class);
});

it('uses has factory trait', function() {
    $reflection = new ReflectionClass(RoleEntityPermission::class);
    $traits = $reflection->getTraitNames();
    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
});

it('has correct table name', function() {
    expect($this->roleEntityPermission->getTable())->toBe('role_entity_permission');
});

it('has correct fillable attributes', function() {
    expect($this->roleEntityPermission->getFillable())->toBe(['role_id', 'entity_id', 'permission_id']);
});

it('has timestamps enabled', function() {
    expect($this->roleEntityPermission->timestamps)->toBeTrue();
});

// Pruebas para las relaciones
it('belongs to role', function() {
    // Creamos mock para evitar que intente conectar con la BD
    $this->mock(Role::class);
    
    $relation = $this->roleEntityPermission->role();
    
    expect($relation)->toBeInstanceOf(BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('role_id');
});

it('belongs to entity', function() {
    // Creamos mock para evitar que intente conectar con la BD
    $this->mock(Entity::class);
    
    $relation = $this->roleEntityPermission->entity();
    
    expect($relation)->toBeInstanceOf(BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('entity_id');
});

it('belongs to permission', function() {
    // Creamos mock para evitar que intente conectar con la BD
    $this->mock(Permission::class);
    
    $relation = $this->roleEntityPermission->permission();
    
    expect($relation)->toBeInstanceOf(BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('permission_id');
});
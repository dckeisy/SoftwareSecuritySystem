<?php

use App\Models\RoleEntityPermission;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Para un test eficaz, creamos una instancia real del modelo
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

// Tests específicos para las relaciones que ejecutan los métodos que necesitamos cubrir

it('belongs to role', function() {
    $relation = $this->roleEntityPermission->role();
    
    expect($relation)->toBeInstanceOf(BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('role_id');
    expect($relation->getRelated())->toBeInstanceOf(Role::class);
});

it('belongs to entity', function() {
    $relation = $this->roleEntityPermission->entity();
    
    expect($relation)->toBeInstanceOf(BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('entity_id');
    expect($relation->getRelated())->toBeInstanceOf(Entity::class);
});

it('belongs to permission', function() {
    $relation = $this->roleEntityPermission->permission();
    
    expect($relation)->toBeInstanceOf(BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('permission_id');
    expect($relation->getRelated())->toBeInstanceOf(Permission::class);
});

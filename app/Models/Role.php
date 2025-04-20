<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roleEntityPermissions()
    {
        return $this->hasMany(RoleEntityPermission::class);
    }

    // Get all entities with permissions assigned to this role
    public function entities()
    {
        $entityIds = $this->roleEntityPermissions()->pluck('entity_id')->unique();
        return $this->getEntitiesQuery($entityIds);
    }

    // Helper method for testability
    public function getEntitiesQuery($entityIds)
    {
        return Entity::whereIn('id', $entityIds)->get();
    }

    // Get all permissions assigned to this role, for a specific entity
    public function getPermissionsForEntity($entityId)
    {
        $permissionIds = $this->roleEntityPermissions()
            ->where('entity_id', $entityId)
            ->pluck('permission_id');
            
        return $this->getPermissionsQuery($permissionIds);
    }

    // Helper method for testability
    public function getPermissionsQuery($permissionIds)
    {
        return Permission::whereIn('id', $permissionIds)->get();
    }

    // Check if the role has a specific permission for a specific entity
    public function hasPermission($permissionSlug, $entitySlug)
    {
        $entity = $this->findEntityBySlug($entitySlug);
        if (!$entity) {
            return false;
        }
        
        $permission = $this->findPermissionBySlug($permissionSlug);
        if (!$permission) {
            return false;
        }
        
        return $this->roleEntityPermissions()
            ->where('entity_id', $entity->id)
            ->where('permission_id', $permission->id)
            ->exists();
    }
    
    // Helper methods for testability
    public function findEntityBySlug($slug)
    {
        return Entity::where('slug', $slug)->first();
    }
    
    public function findPermissionBySlug($slug)
    {
        return Permission::where('slug', $slug)->first();
    }
}
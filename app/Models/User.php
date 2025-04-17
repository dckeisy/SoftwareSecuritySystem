<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'role_id',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Check if the user has a specific role
    public function hasRole($role)
    {
        if (!$this->role) {
            return false;
        }
        
        if (is_string($role)) {
            // Comparar tanto con el slug como con el nombre del rol
            return $this->role->slug === $role || $this->role->name === $role;
        }
        
        return $role->id === $this->role_id;
    }

    // Check if the user has a specific permission for an entity
    public function hasPermission($permission, $entity)
    {
        if (!$this->role) {
            return false;
        }
        
        return $this->role->hasPermission($permission, $entity);
    }

    // Check if the user can access an entity (has some permission on it)
    public function canAccess($entity)
    {
        if (!$this->role) {
            return false;
        }
        
        $entityObj = Entity::where('slug', $entity)->first();
        if (!$entityObj) {
            return false;
        }
        
        return $this->role->roleEntityPermissions()
            ->where('entity_id', $entityObj->id)
            ->exists();
    }
    
    // Get all permissions that the user has through their role
    public function getAllPermissions()
    {
        if (!$this->role) {
            return collect([]);
        }
        
        $rolePermissions = [];
        $entities = $this->role->entities();
        
        foreach ($entities as $entity) {
            $permissions = $this->role->getPermissionsForEntity($entity->id);
            $rolePermissions[$entity->name] = $permissions->pluck('name')->toArray();
        }
        
        return $rolePermissions;
    }
}

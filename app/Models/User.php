<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Class User
 *
 * Modelo que representa a los usuarios del sistema
 *
 * @package App\Models
 */
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

    /**
     * Relación con el modelo Role
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Verifica si el usuario tiene un rol específico
     *
     * @param string|Role $role
     * @return bool
     */

    public function hasRole($role): bool
    {
        if (!$this->role) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        if (is_string($role)) {
            // Comparar tanto con el slug como con el nombre del rol
            return $this->role->slug === $role || $this->role->name === $role;
        }
         // @codeCoverageIgnoreStart
        return $role->id === $this->role_id;
         // @codeCoverageIgnoreEnd
    }

    /**
     * Verifica si el usuario tiene un permiso específico para una entidad
     *
     * @param string|Permission $permission
     * @param string|Entity $entity
     * @return bool
     */
    public function hasPermission($permission, $entity): bool
    {
        if (!$this->role) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        return $this->role->hasPermission($permission, $entity);
    }

    /**
     * Verifica si el usuario puede acceder a una entidad (tiene algún permiso sobre ella)
     *
     * @param string|Entity $entity
     * @return bool
     */
    public function canAccess($entity): bool
    {
        if (!$this->role) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        $entityObj = is_string($entity) ? Entity::where('slug', $entity)->first() : $entity;

        if (!$entityObj) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        return $this->role->roleEntityPermissions()
            ->where('entity_id', $entityObj->id)
            ->exists();
    }

    /**
     * Obtiene todos los permisos que el usuario tiene a través de su rol
     *
     * @return Collection|array
     */
    public function getAllPermissions()
    {
        if (!$this->role) {
            // @codeCoverageIgnoreStart
            return collect([]);
            // @codeCoverageIgnoreEnd
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

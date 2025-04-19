<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RoleEntityPermission
 * 
 * Representa la relación entre Roles, Entidades y Permisos
 * 
 * @package App\Models
 */
class RoleEntityPermission extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     * 
     * @var string
     */
    protected $table = 'role_entity_permission';
    
    /**
     * Atributos que pueden ser asignados masivamente
     * 
     * @var array
     */
    protected $fillable = ['role_id', 'entity_id', 'permission_id'];

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
     * Relación con el modelo Entity
     * 
     * @return BelongsTo
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Relación con el modelo Permission
     * 
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}

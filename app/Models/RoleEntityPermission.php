<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleEntityPermission extends Model
{
    use HasFactory;

    protected $table = 'role_entity_permission';
    
    protected $fillable = ['role_id', 'entity_id', 'permission_id'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}

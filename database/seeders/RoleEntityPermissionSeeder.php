<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Database\Seeder;

class RoleEntityPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Get the roles
        $superAdmin = Role::where('slug', 'superadmin')->first();
        $auditor = Role::where('slug', 'auditor')->first();
        $registrador = Role::where('slug', 'registrador')->first();
        
        // Get the entities
        $usuarios = Entity::where('slug', 'usuarios')->first();
        $roles = Entity::where('slug', 'roles')->first();
        $productos = Entity::where('slug', 'productos')->first();
        
        // Get the permissions
        $crear = Permission::where('slug', 'crear')->first();
        $editar = Permission::where('slug', 'editar')->first();
        $borrar = Permission::where('slug', 'borrar')->first();
        $verReportes = Permission::where('slug', 'ver-reportes')->first();
        
        // Assign permissions according to the requirements
        
        // SuperAdmin: Management of users and roles + view products (read-only)
        $this->assignPermission($superAdmin->id, $usuarios->id, $crear->id);
        $this->assignPermission($superAdmin->id, $usuarios->id, $editar->id);
        $this->assignPermission($superAdmin->id, $usuarios->id, $borrar->id);
        $this->assignPermission($superAdmin->id, $usuarios->id, $verReportes->id);
        
        $this->assignPermission($superAdmin->id, $roles->id, $crear->id);
        $this->assignPermission($superAdmin->id, $roles->id, $editar->id);
        $this->assignPermission($superAdmin->id, $roles->id, $borrar->id);
        $this->assignPermission($superAdmin->id, $roles->id, $verReportes->id);
        
        // SuperAdmin solo puede ver la lista de productos
        $this->assignPermission($superAdmin->id, $productos->id, $verReportes->id);
        
        // Auditor: Only can view lists of users and products
        $this->assignPermission($auditor->id, $usuarios->id, $verReportes->id);
        $this->assignPermission($auditor->id, $productos->id, $verReportes->id);
        
        // Registrador: Management of products + view list of users
        $this->assignPermission($registrador->id, $productos->id, $crear->id);
        $this->assignPermission($registrador->id, $productos->id, $editar->id);
        $this->assignPermission($registrador->id, $productos->id, $borrar->id);
        $this->assignPermission($registrador->id, $productos->id, $verReportes->id);
        $this->assignPermission($registrador->id, $usuarios->id, $verReportes->id);
    }
    
    private function assignPermission($roleId, $entityId, $permissionId)
    {
        RoleEntityPermission::create([
            'role_id' => $roleId,
            'entity_id' => $entityId,
            'permission_id' => $permissionId
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $entities = Entity::all();
        $permissions = Permission::all();
        return view('roles.create', compact('entities', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            
            // Generar el slug a partir del nombre
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            
            // Verificar si el slug ya existe y agregar un sufijo numérico si es necesario
            $count = 1;
            while (Role::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
            
            $role = Role::create([
                'name' => $request->name,
                'slug' => $slug
            ]);

            // Asignar permisos por defecto según el tipo de rol
            $defaultPermissions = [];
            if (in_array($role->slug, ['superadmin', 'superadmin-1', 'superadmin-2'])) {
                $defaultPermissions = $this->getSuperAdminDefaultPermissions();
            } elseif (str_starts_with($role->slug, 'auditor')) {
                $defaultPermissions = $this->getAuditorDefaultPermissions();
            } elseif (str_starts_with($role->slug, 'registrador')) {
                $defaultPermissions = $this->getRegistradorDefaultPermissions();
            }
            
            // Asignar permisos por defecto
            foreach ($defaultPermissions as $entityId => $permissionIds) {
                foreach ($permissionIds as $permissionId) {
                    RoleEntityPermission::create([
                        'role_id' => $role->id,
                        'entity_id' => $entityId,
                        'permission_id' => $permissionId
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'Rol creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al crear el rol: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Role $role)
    {
        $entities = Entity::all();
        $permissions = Permission::all();
        
        // Obtener permisos por defecto según el tipo de rol
        $defaultPermissions = [];
        if ($role->slug === 'superadmin') {
            $defaultPermissions = $this->getSuperAdminDefaultPermissions();
        } elseif ($role->slug === 'auditor') {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        } elseif ($role->slug === 'registrador') {
            $defaultPermissions = $this->getRegistradorDefaultPermissions();
        }
        
        // Obtener los permisos actuales del rol por entidad
        $rolePermissions = [];
        foreach ($entities as $entity) {
            $permissionIds = RoleEntityPermission::where('role_id', $role->id)
                ->where('entity_id', $entity->id)
                ->pluck('permission_id')
                ->toArray();
                
            $rolePermissions[$entity->id] = $permissionIds;
        }
        
        return view('roles.edit', compact('role', 'entities', 'permissions', 'rolePermissions', 'defaultPermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            
            $oldSlug = $role->slug;
            
            // Generar el nuevo slug a partir del nombre
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            
            // Si el nombre cambió, verificar si el nuevo slug ya existe y agregar un sufijo numérico si es necesario
            if ($baseSlug !== $oldSlug) {
                $count = 1;
                while (Role::where('slug', $slug)->where('id', '!=', $role->id)->exists()) {
                    $slug = $baseSlug . '-' . $count++;
                }
            } else {
                $slug = $oldSlug; // Mantener el mismo slug si el nombre no cambió
            }
            
            $role->update([
                'name' => $request->name,
                'slug' => $slug
            ]);
            
            // Si cambió el tipo de rol, actualizar los permisos por defecto
            if ($oldSlug !== $role->slug) {
                // Eliminar todos los permisos actuales
                RoleEntityPermission::where('role_id', $role->id)->delete();
                
                // Asignar nuevos permisos por defecto
                $defaultPermissions = [];
                if (in_array($role->slug, ['superadmin', 'superadmin-1', 'superadmin-2'])) {
                    $defaultPermissions = $this->getSuperAdminDefaultPermissions();
                } elseif (str_starts_with($role->slug, 'auditor')) {
                    $defaultPermissions = $this->getAuditorDefaultPermissions();
                } elseif (str_starts_with($role->slug, 'registrador')) {
                    $defaultPermissions = $this->getRegistradorDefaultPermissions();
                }
                
                // Asignar permisos por defecto
                foreach ($defaultPermissions as $entityId => $permissionIds) {
                    foreach ($permissionIds as $permissionId) {
                        RoleEntityPermission::create([
                            'role_id' => $role->id,
                            'entity_id' => $entityId,
                            'permission_id' => $permissionId
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'Rol actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al actualizar el rol: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Role $role)
    {
        try {
            // Verificar que no haya usuarios con este rol
            if ($role->users()->count() > 0) {
                return redirect()->route('roles.index')
                    ->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados.');
            }

            // Eliminar las relaciones de permisos
            RoleEntityPermission::where('role_id', $role->id)->delete();
            
            // Eliminar el rol
            $role->delete();

            return redirect()->route('roles.index')
                ->with('success', 'Rol eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('roles.index')
                ->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    // Métodos para gestión de permisos
    public function permissions(Role $role)
    {
        $entities = Entity::all();
        $permissions = Permission::all();
        $rolePermissions = [];
        $defaultPermissions = []; // Permisos que no se pueden quitar (por defecto)
        
        // Definir permisos por defecto según el slug del rol
        if (in_array($role->slug, ['superadmin', 'superadmin-1', 'superadmin-2'])) {
            $defaultPermissions = $this->getSuperAdminDefaultPermissions();
        } elseif (str_starts_with($role->slug, 'auditor')) {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        } elseif (str_starts_with($role->slug, 'registrador')) {
            $defaultPermissions = $this->getRegistradorDefaultPermissions();
        }
        
        // Obtener permisos actuales por entidad
        foreach ($entities as $entity) {
            $permissionIds = RoleEntityPermission::where('role_id', $role->id)
                ->where('entity_id', $entity->id)
                ->pluck('permission_id')
                ->toArray();
                
            $rolePermissions[$entity->id] = $permissionIds;
        }
        
        return view('roles.permissions', compact('role', 'entities', 'permissions', 'rolePermissions', 'defaultPermissions'));
    }

    public function updatePermissions(Request $request, Role $role)
    {
        try {
            DB::beginTransaction();
            
            // Definir permisos por defecto que no se pueden quitar
            $defaultPermissions = [];
            if (in_array($role->slug, ['superadmin', 'superadmin-1', 'superadmin-2'])) {
                $defaultPermissions = $this->getSuperAdminDefaultPermissions();
            } elseif (str_starts_with($role->slug, 'auditor')) {
                $defaultPermissions = $this->getAuditorDefaultPermissions();
            } elseif (str_starts_with($role->slug, 'registrador')) {
                $defaultPermissions = $this->getRegistradorDefaultPermissions();
            }
            
            // Eliminar permisos actuales que no sean por defecto
            foreach ($entities = Entity::all() as $entity) {
                $defaultPermissionIds = isset($defaultPermissions[$entity->id]) 
                    ? $defaultPermissions[$entity->id] 
                    : [];
                
                RoleEntityPermission::where('role_id', $role->id)
                    ->where('entity_id', $entity->id)
                    ->whereNotIn('permission_id', $defaultPermissionIds)
                    ->delete();
            }
            
            // Asignar los nuevos permisos (no eliminar los que son por defecto)
            if ($request->has('entity_permissions')) {
                foreach ($request->entity_permissions as $entityId => $permissionIds) {
                    // Filtrar permisos que ya existan por defecto para no duplicarlos
                    $defaultPermissionIds = isset($defaultPermissions[$entityId]) 
                        ? $defaultPermissions[$entityId] 
                        : [];
                        
                    $permissionsToAdd = array_diff($permissionIds, $defaultPermissionIds);
                    
                    foreach ($permissionsToAdd as $permissionId) {
                        // Verificar si ya existe este permiso
                        $exists = RoleEntityPermission::where('role_id', $role->id)
                            ->where('entity_id', $entityId)
                            ->where('permission_id', $permissionId)
                            ->exists();
                            
                        if (!$exists) {
                            RoleEntityPermission::create([
                                'role_id' => $role->id,
                                'entity_id' => $entityId,
                                'permission_id' => $permissionId
                            ]);
                        }
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('roles.permissions', $role->id)
                ->with('success', 'Permisos asignados exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al asignar permisos: ' . $e->getMessage());
        }
    }
    
    // Métodos para obtener permisos por defecto según el rol
    private function getSuperAdminDefaultPermissions()
    {
        $permissions = Permission::all()->pluck('id', 'slug')->toArray();
        $entities = Entity::all()->pluck('id', 'slug')->toArray();
        
        return [
            // Usuarios - Crear, Editar, Borrar, Ver
            $entities['usuarios'] => [
                $permissions['crear'], $permissions['editar'], 
                $permissions['borrar'], $permissions['ver-reportes']
            ],
            // Roles - Crear, Editar, Borrar, Ver
            $entities['roles'] => [
                $permissions['crear'], $permissions['editar'], 
                $permissions['borrar'], $permissions['ver-reportes']
            ],
            // Productos - Solo Ver (sin permisos de modificación)
            $entities['productos'] => [
                $permissions['ver-reportes']
            ],
        ];
    }
    
    private function getAuditorDefaultPermissions()
    {
        $permissions = Permission::all()->pluck('id', 'slug')->toArray();
        $entities = Entity::all()->pluck('id', 'slug')->toArray();
        
        return [
            // Usuarios - Solo Ver
            $entities['usuarios'] => [$permissions['ver-reportes']],
            // Productos - Solo Ver
            $entities['productos'] => [$permissions['ver-reportes']],
        ];
    }
    
    private function getRegistradorDefaultPermissions()
    {
        $permissions = Permission::all()->pluck('id', 'slug')->toArray();
        $entities = Entity::all()->pluck('id', 'slug')->toArray();
        
        return [
            // Usuarios - Solo Ver
            $entities['usuarios'] => [$permissions['ver-reportes']],
            // Productos - Crear, Editar, Borrar, Ver
            $entities['productos'] => [
                $permissions['crear'], $permissions['editar'], 
                $permissions['borrar'], $permissions['ver-reportes']
            ],
        ];
    }
}

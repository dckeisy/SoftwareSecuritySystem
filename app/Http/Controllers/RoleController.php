<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    // Constantes para nombres reservados
    private const RESERVED_ROLES = ['SuperAdmin', 'Auditor', 'Registrador'];

    public function index()
    {
        $roles = Role::all();
        // Escape de datos para prevenir XSS
        foreach ($roles as $role) {
            $role->name = e($role->name);
            $role->slug = e($role->slug);
        }
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
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                'unique:roles,name',
            ],
        ]);

        $name = strip_tags(trim($validated['name']));
        if (in_array($name, self::RESERVED_ROLES, true)) {
            return redirect()->back()
                ->with('error', 'No puede crear un rol con un nombre reservado.')
                ->withInput();
        }

        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $count = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        try {
            $role = DB::transaction(function () use ($name, $slug, $request) {
                $role = Role::create(['name' => $name, 'slug' => $slug]);
                $auditorDefaults = $this->getAuditorDefaultPermissions();
                foreach ($auditorDefaults as $entityId => $permIds) {
                    foreach ($permIds as $permId) {
                        RoleEntityPermission::create([
                            'role_id'       => $role->id,
                            'entity_id'     => (int)$entityId,
                            'permission_id' => (int)$permId,
                        ]);
                    }
                }
                foreach ($request->input('permissions', []) as $rawEntityId => $permIds) {
                    $entityId = filter_var($rawEntityId, FILTER_VALIDATE_INT);
                    if ($entityId === false) continue;
                    foreach ($permIds as $rawPermId) {
                        $permissionId = filter_var($rawPermId, FILTER_VALIDATE_INT);
                        if ($permissionId === false) continue;
                        if (!isset($auditorDefaults[$entityId]) ||
                            !in_array($permissionId, $auditorDefaults[$entityId], true)
                        ) {
                            RoleEntityPermission::create([
                                'role_id'       => $role->id,
                                'entity_id'     => $entityId,
                                'permission_id' => $permissionId,
                            ]);
                        }
                    }
                }
                return $role;
            });

            return redirect()->route('roles.index')
                ->with('success', 'Rol creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el rol: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Role $role)
    {
        $entities = Entity::all();
        $permissions = Permission::all();
        $role->name = e($role->name);
        $role->slug = e($role->slug);
        $rolePermissions = [];
        foreach ($entities as $entity) {
            $rolePermissions[$entity->id] = RoleEntityPermission::where('role_id', $role->id)
                ->where('entity_id', $entity->id)
                ->pluck('permission_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        }
        return view('roles.edit', compact('role', 'entities', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
        ]);

        $name = strip_tags(trim($validated['name']));
        if (in_array($role->name, self::RESERVED_ROLES, true)) {
            return redirect()->back()
                ->with('error', 'No puede editar un rol predefinido.')
                ->withInput();
        }

        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        if ($baseSlug !== $role->slug) {
            $count = 1;
            while (Role::where('slug', $slug)->where('id', '!=', $role->id)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
        }

        try {
            DB::transaction(function () use ($role, $name, $slug, $request) {
                $role->update(['name' => $name, 'slug' => $slug]);
                $auditorDefaults = $this->getAuditorDefaultPermissions();
                foreach (Entity::all() as $entity) {
                    $basic = $auditorDefaults[$entity->id] ?? [];
                    RoleEntityPermission::where('role_id', $role->id)
                        ->where('entity_id', $entity->id)
                        ->whereNotIn('permission_id', $basic)
                        ->delete();
                }
                foreach ($auditorDefaults as $entityId => $permIds) {
                    foreach ($permIds as $permId) {
                        RoleEntityPermission::firstOrCreate([
                            'role_id'       => $role->id,
                            'entity_id'     => (int)$entityId,
                            'permission_id' => (int)$permId,
                        ]);
                    }
                }
                foreach ($request->input('permissions', []) as $rawEntityId => $permIds) {
                    $entityId = filter_var($rawEntityId, FILTER_VALIDATE_INT);
                    if ($entityId === false) continue;
                    foreach ($permIds as $rawPermId) {
                        $permissionId = filter_var($rawPermId, FILTER_VALIDATE_INT);
                        if ($permissionId === false) continue;
                        $basic = $auditorDefaults[$entityId] ?? [];
                        if (!in_array($permissionId, $basic, true)) {
                            RoleEntityPermission::firstOrCreate([
                                'role_id'       => $role->id,
                                'entity_id'     => $entityId,
                                'permission_id' => $permissionId,
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('roles.index')
                ->with('success', 'Rol actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el rol: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::RESERVED_ROLES, true)) {
            return redirect()->route('roles.index')
                ->with('error', 'No puede eliminar un rol predefinido.');
        }
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'El rol tiene usuarios asignados.');
        }
        try {
            DB::transaction(function () use ($role) {
                RoleEntityPermission::where('role_id', $role->id)->delete();
                $role->delete();
            });
            return redirect()->route('roles.index')
                ->with('success', 'Rol eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('roles.index')
                ->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    public function permissions(Role $role)
    {
        $entities = Entity::all();
        $permissions = Permission::all();
        $defaultPermissions = [];
        if (Str::startsWith($role->slug, 'superadmin')) {
            $defaultPermissions = $this->getSuperAdminDefaultPermissions();
        } elseif (Str::startsWith($role->slug, 'auditor')) {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        } elseif (Str::startsWith($role->slug, 'registrador')) {
            $defaultPermissions = $this->getRegistradorDefaultPermissions();
        }
        $rolePermissions = [];
        foreach ($entities as $entity) {
            $rolePermissions[$entity->id] = RoleEntityPermission::where('role_id', $role->id)
                ->where('entity_id', $entity->id)
                ->pluck('permission_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        }
        return view('roles.permissions', compact('role', 'entities', 'permissions', 'rolePermissions', 'defaultPermissions')); 
    }

    public function updatePermissions(Request $request, Role $role)
    {
        if (Str::startsWith($role->slug, 'superadmin')) {
            $defaultPermissions = $this->getSuperAdminDefaultPermissions();
        } elseif (Str::startsWith($role->slug, 'auditor')) {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        } elseif (Str::startsWith($role->slug, 'registrador')) {
            $defaultPermissions = $this->getRegistradorDefaultPermissions();
        } else {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        }

        try {
            DB::transaction(function () use ($role, $request, $defaultPermissions) {
                foreach (Entity::all() as $entity) {
                    $basic = $defaultPermissions[$entity->id] ?? [];
                    RoleEntityPermission::where('role_id', $role->id)
                        ->where('entity_id', $entity->id)
                        ->whereNotIn('permission_id', $basic)
                        ->delete();
                }
                foreach ($defaultPermissions as $entityId => $permIds) {
                    foreach ($permIds as $permId) {
                        RoleEntityPermission::firstOrCreate([
                            'role_id'       => $role->id,
                            'entity_id'     => (int)$entityId,
                            'permission_id' => (int)$permId,
                        ]);
                    }
                }
                foreach ($request->input('entity_permissions', []) as $rawEntityId => $permIds) {
                    $entityId = filter_var($rawEntityId, FILTER_VALIDATE_INT);
                    if ($entityId === false) continue;
                    foreach ($permIds as $rawPermId) {
                        $permissionId = filter_var($rawPermId, FILTER_VALIDATE_INT);
                        if ($permissionId === false) continue;
                        $basic = $defaultPermissions[$entityId] ?? [];
                        if (!in_array($permissionId, $basic, true)) {
                            RoleEntityPermission::firstOrCreate([
                                'role_id'       => $role->id,
                                'entity_id'     => $entityId,
                                'permission_id' => $permissionId,
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('roles.permissions', $role->id)
                ->with('success', 'Permisos actualizados exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al asignar permisos: ' . $e->getMessage());
        }
    }

    // Permisos por defecto
    private function getSuperAdminDefaultPermissions()
    {
        $perms = Permission::all()->pluck('id', 'slug')->toArray();
        $ents  = Entity::all()->pluck('id', 'slug')->toArray();
        return [
            $ents['usuarios']  => [(int)$perms['crear'], (int)$perms['editar'], (int)$perms['borrar'], (int)$perms['ver-reportes']],
            $ents['roles']     => [(int)$perms['crear'], (int)$perms['editar'], (int)$perms['borrar'], (int)$perms['ver-reportes']],
            $ents['productos'] => [(int)$perms['ver-reportes']],
        ];
    }

    private function getAuditorDefaultPermissions()
    {
        $perms = Permission::all()->pluck('id', 'slug')->toArray();
        $ents  = Entity::all()->pluck('id', 'slug')->toArray();
        return [
            $ents['usuarios']  => [(int)$perms['ver-reportes']],
            $ents['productos'] => [(int)$perms['ver-reportes']],
        ];
    }

    private function getRegistradorDefaultPermissions()
    {
        $perms = Permission::all()->pluck('id', 'slug')->toArray();
        $ents  = Entity::all()->pluck('id', 'slug')->toArray();
        return [
            $ents['usuarios']  => [(int)$perms['ver-reportes']],
            $ents['productos'] => [(int)$perms['crear'], (int)$perms['editar'], (int)$perms['borrar'], (int)$perms['ver-reportes']],
        ];
    }
}

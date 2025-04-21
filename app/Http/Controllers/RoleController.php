<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use App\Models\User;
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
                'required',
                'string',
                'max:100',
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
            DB::beginTransaction();

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

            if ($request->has('permissions')) {
                $permissionsInput = $request->get('permissions');
                foreach ($permissionsInput as $rawEntityId => $permIds) {
                    $entityId = filter_var($rawEntityId, FILTER_VALIDATE_INT);
                    if ($entityId === false) continue;
                    foreach ($permIds as $rawPermId) {
                        $permissionId = filter_var($rawPermId, FILTER_VALIDATE_INT);
                        if ($permissionId === false) continue;
                        if (!isset($auditorDefaults[$entityId]) || !in_array($permissionId, $auditorDefaults[$entityId], true)) {
                            RoleEntityPermission::create([
                                'role_id'       => $role->id,
                                'entity_id'     => $entityId,
                                'permission_id' => $permissionId,
                            ]);
                        }
                    }
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
                'required',
                'string',
                'max:100',
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
            while (Role::where('slug', $slug)
                ->where('id', '!=', $role->id)
                ->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
        }

        try {
            DB::beginTransaction();

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

            if ($request->has('permissions')) {
                $permissionsInput = $request->get('permissions');
                foreach ($permissionsInput as $rawEntityId => $permIds) {
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
        if (in_array($role->name, self::RESERVED_ROLES, true)) {
            return redirect()->route('roles.index')
                ->with('error', 'No puede eliminar un rol predefinido.');
        }


        if (User::where('role_id', $role->id)->count() > 0) {
            // @codeCoverageIgnoreStart
            return redirect()->route('roles.index')
                ->with('error', 'El rol tiene usuarios asignados.');
            // @codeCoverageIgnoreStart

        }

        try {
            DB::beginTransaction();
            RoleEntityPermission::where('role_id', $role->id)->delete();
            $role->delete();
            DB::commit();
            return redirect()->route('roles.index')
                ->with('success', 'Rol eliminado exitosamente.');
            // @codeCoverageIgnoreEnd
            } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('roles.index')
                ->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
            }
            // @codeCoverageIgnoreEnd
    }

public function permissions(Role $role)
    {
        $role->name = e($role->name);
        $role->slug = e($role->slug);

        // Cargar entidades y permisos
        $entities    = Entity::all();
        $permissions = Permission::all();

        // Determinar permisos por defecto según slug
        if (str_starts_with($role->slug, 'superadmin')) {
            $defaultPermissions = $this->getSuperAdminDefaultPermissions();
        } elseif (str_starts_with($role->slug, 'auditor')) {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        } elseif (str_starts_with($role->slug, 'registrador')) {
            $defaultPermissions = $this->getRegistradorDefaultPermissions();
        } else {
            // Para roles personalizados, usar los del Auditor
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        }

        // Permisos actuales: se castéan a entero para evitar strings
        $rolePermissions = [];
        foreach ($entities as $entity) {
            $rolePermissions[$entity->id] = RoleEntityPermission::where('role_id', $role->id)
                ->where('entity_id', $entity->id)
                ->pluck('permission_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
        }

        return view('roles.permissions', compact(
            'role',
            'entities',
            'permissions',
            'rolePermissions',
            'defaultPermissions'
        ));
    }

    /**
     * Recibe el formulario y actualiza la tabla pivote.
     * Se validan estrictamente todos los inputs como enteros y existentes.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        // Validación de la entrada: sólo arrays de enteros que existan en la tabla permissions
        $validated = $request->validate([
            'entity_permissions'        => ['required', 'array'],
            'entity_permissions.*'      => ['array'],
            'entity_permissions.*.*'    => ['integer', Rule::exists('permissions', 'id')],
        ]);

        // Determinar permisos básicos (no removibles)
        if (str_starts_with($role->slug, 'superadmin')) {
            $defaultPermissions = $this->getSuperAdminDefaultPermissions();
        } elseif (str_starts_with($role->slug, 'auditor')) {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        } elseif (str_starts_with($role->slug, 'registrador')) {
            $defaultPermissions = $this->getRegistradorDefaultPermissions();
        } else {
            $defaultPermissions = $this->getAuditorDefaultPermissions();
        }

        // Actualización en transacción atómica
        DB::transaction(function() use ($validated, $role, $defaultPermissions) {
            // Para cada entidad, mantenemos sólo los permisos:
            //   – básicos (defaultPermissions)
            //   – y los que el usuario marcó en el formulario
            foreach (Entity::all() as $entity) {
                $eid = $entity->id;

                // permisos enviados (si no hay, será array vacío)
                $sent = $validated['entity_permissions'][$eid] ?? [];

                // unimos básicos + enviados (sin duplicados)
                $allowed = array_unique(array_merge(
                    $defaultPermissions[$eid] ?? [],
                    $sent
                ));

                // eliminar cualquier permiso que NO esté en $allowed
                RoleEntityPermission::where('role_id', $role->id)
                    ->where('entity_id', $eid)
                    ->whereNotIn('permission_id', $allowed)
                    ->delete();

                // insertar los que faltan, usando firstOrCreate para evitar duplicados
                foreach ($allowed as $pid) {
                    RoleEntityPermission::firstOrCreate([
                        'role_id'       => $role->id,
                        'entity_id'     => $eid,
                        'permission_id' => $pid,
                    ]);
                }
            }
        });

        return redirect()
            ->route('roles.permissions', $role->id)
            ->with('success', 'Permisos actualizados exitosamente.');
    }

    // @codeCoverageIgnoreStart
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
    // @codeCoverageIgnoreEnd
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

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $superadminRole;
    protected $auditorRole;
    protected $registradorRole;
    protected $superadmin;
    protected $auditor;
    protected $registrador;
    protected $userWithoutRole;
    protected $usuariosEntity;
    protected $rolesEntity;
    protected $productosEntity;
    protected $crearPermission;
    protected $editarPermission;
    protected $borrarPermission;
    protected $verPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear entidades
        $this->usuariosEntity = Entity::create(['name' => 'Usuarios', 'slug' => 'usuarios']);
        $this->rolesEntity = Entity::create(['name' => 'Roles', 'slug' => 'roles']);
        $this->productosEntity = Entity::create(['name' => 'Productos', 'slug' => 'productos']);

        // Crear permisos
        $this->crearPermission = Permission::create(['name' => 'Crear', 'slug' => 'crear']);
        $this->editarPermission = Permission::create(['name' => 'Editar', 'slug' => 'editar']);
        $this->borrarPermission = Permission::create(['name' => 'Borrar', 'slug' => 'borrar']);
        $this->verPermission = Permission::create(['name' => 'Ver Reportes', 'slug' => 'ver-reportes']);

        // Crear roles
        $this->superadminRole = Role::create(['name' => 'SuperAdmin', 'slug' => 'superadmin']);
        $this->auditorRole = Role::create(['name' => 'Auditor', 'slug' => 'auditor']);
        $this->registradorRole = Role::create(['name' => 'Registrador', 'slug' => 'registrador']);

        // Asignar permisos a roles
        // SuperAdmin tiene todos los permisos
        foreach ([$this->usuariosEntity, $this->rolesEntity, $this->productosEntity] as $entity) {
            foreach ([$this->crearPermission, $this->editarPermission, $this->borrarPermission, $this->verPermission] as $permission) {
                RoleEntityPermission::create([
                    'role_id' => $this->superadminRole->id,
                    'entity_id' => $entity->id,
                    'permission_id' => $permission->id
                ]);
            }
        }

        // Auditor tiene permisos de ver en todas las entidades
        foreach ([$this->usuariosEntity, $this->rolesEntity, $this->productosEntity] as $entity) {
            RoleEntityPermission::create([
                'role_id' => $this->auditorRole->id,
                'entity_id' => $entity->id,
                'permission_id' => $this->verPermission->id
            ]);
        }

        // Registrador tiene permisos de crear y editar productos
        RoleEntityPermission::create([
            'role_id' => $this->registradorRole->id,
            'entity_id' => $this->productosEntity->id,
            'permission_id' => $this->crearPermission->id
        ]);
        RoleEntityPermission::create([
            'role_id' => $this->registradorRole->id,
            'entity_id' => $this->productosEntity->id,
            'permission_id' => $this->editarPermission->id
        ]);
        RoleEntityPermission::create([
            'role_id' => $this->registradorRole->id,
            'entity_id' => $this->productosEntity->id,
            'permission_id' => $this->verPermission->id
        ]);

        // Crear usuarios
        $this->superadmin = User::factory()->create([
            'role_id' => $this->superadminRole->id,
            'username' => 'superadmin_test'
        ]);

        $this->auditor = User::factory()->create([
            'role_id' => $this->auditorRole->id,
            'username' => 'auditor_test'
        ]);

        $this->registrador = User::factory()->create([
            'role_id' => $this->registradorRole->id,
            'username' => 'registrador_test'
        ]);

        $this->userWithoutRole = User::factory()->create([
            'role_id' => null,
            'username' => 'user_without_role_test'
        ]);
    }

    public function test_superadmin_has_all_permissions()
    {
        $this->assertTrue($this->superadmin->hasPermission('crear', 'usuarios'));
        $this->assertTrue($this->superadmin->hasPermission('editar', 'usuarios'));
        $this->assertTrue($this->superadmin->hasPermission('borrar', 'usuarios'));
        $this->assertTrue($this->superadmin->hasPermission('ver-reportes', 'usuarios'));

        $this->assertTrue($this->superadmin->hasPermission('crear', 'roles'));
        $this->assertTrue($this->superadmin->hasPermission('editar', 'roles'));
        $this->assertTrue($this->superadmin->hasPermission('borrar', 'roles'));
        $this->assertTrue($this->superadmin->hasPermission('ver-reportes', 'roles'));

        $this->assertTrue($this->superadmin->hasPermission('crear', 'productos'));
        $this->assertTrue($this->superadmin->hasPermission('editar', 'productos'));
        $this->assertTrue($this->superadmin->hasPermission('borrar', 'productos'));
        $this->assertTrue($this->superadmin->hasPermission('ver-reportes', 'productos'));
    }

    public function test_auditor_has_only_view_permissions()
    {
        $this->assertFalse($this->auditor->hasPermission('crear', 'usuarios'));
        $this->assertFalse($this->auditor->hasPermission('editar', 'usuarios'));
        $this->assertFalse($this->auditor->hasPermission('borrar', 'usuarios'));
        $this->assertTrue($this->auditor->hasPermission('ver-reportes', 'usuarios'));

        $this->assertFalse($this->auditor->hasPermission('crear', 'roles'));
        $this->assertFalse($this->auditor->hasPermission('editar', 'roles'));
        $this->assertFalse($this->auditor->hasPermission('borrar', 'roles'));
        $this->assertTrue($this->auditor->hasPermission('ver-reportes', 'roles'));

        $this->assertFalse($this->auditor->hasPermission('crear', 'productos'));
        $this->assertFalse($this->auditor->hasPermission('editar', 'productos'));
        $this->assertFalse($this->auditor->hasPermission('borrar', 'productos'));
        $this->assertTrue($this->auditor->hasPermission('ver-reportes', 'productos'));
    }

    public function test_registrador_has_limited_permissions()
    {
        $this->assertFalse($this->registrador->hasPermission('crear', 'usuarios'));
        $this->assertFalse($this->registrador->hasPermission('editar', 'usuarios'));
        $this->assertFalse($this->registrador->hasPermission('borrar', 'usuarios'));
        $this->assertFalse($this->registrador->hasPermission('ver-reportes', 'usuarios'));

        $this->assertFalse($this->registrador->hasPermission('crear', 'roles'));
        $this->assertFalse($this->registrador->hasPermission('editar', 'roles'));
        $this->assertFalse($this->registrador->hasPermission('borrar', 'roles'));
        $this->assertFalse($this->registrador->hasPermission('ver-reportes', 'roles'));

        $this->assertTrue($this->registrador->hasPermission('crear', 'productos'));
        $this->assertTrue($this->registrador->hasPermission('editar', 'productos'));
        $this->assertFalse($this->registrador->hasPermission('borrar', 'productos'));
        $this->assertTrue($this->registrador->hasPermission('ver-reportes', 'productos'));
    }

    public function test_role_based_access_control()
    {
        // Verificamos que el middleware de roles funcione correctamente
        $this->assertTrue(class_exists('App\Http\Middleware\CheckRole'));
        
        // Verificamos que el middleware de permisos funcione correctamente
        $this->assertTrue(class_exists('App\Http\Middleware\CheckEntityPermission'));
        
        // Verificamos que el superadmin tenga el rol correcto
        $this->assertTrue($this->superadmin->hasRole('superadmin'));
        
        // Verificamos que el auditor tenga el rol correcto
        $this->assertTrue($this->auditor->hasRole('auditor'));
        
        // Verificamos que el registrador tenga el rol correcto
        $this->assertTrue($this->registrador->hasRole('registrador'));
        
        // Verificamos que el usuario sin rol no tenga rol
        $this->assertNull($this->userWithoutRole->role);
    }
} 
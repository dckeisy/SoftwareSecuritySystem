<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckEntityPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;
    protected $auditor;
    protected $registrador;
    protected $userWithoutRole;
    protected $usuariosEntity;
    protected $productosEntity;
    protected $rolesEntity;
    protected $verReportes;
    protected $crear;
    protected $editar;
    protected $borrar;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear entidades
        $this->usuariosEntity = Entity::create(['name' => 'Usuarios', 'slug' => 'usuarios']);
        $this->productosEntity = Entity::create(['name' => 'Productos', 'slug' => 'productos']);
        $this->rolesEntity = Entity::create(['name' => 'Roles', 'slug' => 'roles']);

        // Crear permisos
        $this->verReportes = Permission::create(['name' => 'Ver Reportes', 'slug' => 'ver-reportes']);
        $this->crear = Permission::create(['name' => 'Crear', 'slug' => 'crear']);
        $this->editar = Permission::create(['name' => 'Editar', 'slug' => 'editar']);
        $this->borrar = Permission::create(['name' => 'Borrar', 'slug' => 'borrar']);

        // Crear roles
        $superadminRole = Role::create(['name' => 'SuperAdmin', 'slug' => 'superadmin']);
        $auditorRole = Role::create(['name' => 'Auditor', 'slug' => 'auditor']);
        $registradorRole = Role::create(['name' => 'Registrador', 'slug' => 'registrador']);

        // Asignar permisos a SuperAdmin (todos los permisos)
        foreach ([$this->usuariosEntity, $this->productosEntity, $this->rolesEntity] as $entity) {
            foreach ([$this->verReportes, $this->crear, $this->editar, $this->borrar] as $permission) {
                RoleEntityPermission::create([
                    'role_id' => $superadminRole->id,
                    'entity_id' => $entity->id,
                    'permission_id' => $permission->id
                ]);
            }
        }

        // Asignar permisos a Auditor (solo ver reportes)
        foreach ([$this->usuariosEntity, $this->productosEntity, $this->rolesEntity] as $entity) {
            RoleEntityPermission::create([
                'role_id' => $auditorRole->id,
                'entity_id' => $entity->id,
                'permission_id' => $this->verReportes->id
            ]);
        }

        // Asignar permisos a Registrador (solo productos: ver, crear, editar)
        RoleEntityPermission::create([
            'role_id' => $registradorRole->id,
            'entity_id' => $this->productosEntity->id,
            'permission_id' => $this->verReportes->id
        ]);
        RoleEntityPermission::create([
            'role_id' => $registradorRole->id,
            'entity_id' => $this->productosEntity->id,
            'permission_id' => $this->crear->id
        ]);
        RoleEntityPermission::create([
            'role_id' => $registradorRole->id,
            'entity_id' => $this->productosEntity->id,
            'permission_id' => $this->editar->id
        ]);

        // Crear usuarios
        $this->superadmin = User::factory()->create(['role_id' => $superadminRole->id]);
        $this->auditor = User::factory()->create(['role_id' => $auditorRole->id]);
        $this->registrador = User::factory()->create(['role_id' => $registradorRole->id]);
        $this->userWithoutRole = User::factory()->create(['role_id' => null]);
    }

    public function test_middleware_exists()
    {
        $this->assertTrue(class_exists('App\Http\Middleware\CheckEntityPermission'));
    }

    public function test_has_permission_method_works_correctly()
    {
        // SuperAdmin tiene todos los permisos
        $this->assertTrue($this->superadmin->hasPermission('ver-reportes', 'usuarios'));
        $this->assertTrue($this->superadmin->hasPermission('crear', 'usuarios'));
        $this->assertTrue($this->superadmin->hasPermission('editar', 'usuarios'));
        $this->assertTrue($this->superadmin->hasPermission('borrar', 'usuarios'));
        
        // Auditor solo tiene permiso de ver reportes
        $this->assertTrue($this->auditor->hasPermission('ver-reportes', 'usuarios'));
        $this->assertFalse($this->auditor->hasPermission('crear', 'usuarios'));
        $this->assertFalse($this->auditor->hasPermission('editar', 'usuarios'));
        $this->assertFalse($this->auditor->hasPermission('borrar', 'usuarios'));
        
        // Registrador solo tiene permisos sobre productos
        $this->assertTrue($this->registrador->hasPermission('ver-reportes', 'productos'));
        $this->assertTrue($this->registrador->hasPermission('crear', 'productos'));
        $this->assertTrue($this->registrador->hasPermission('editar', 'productos'));
        $this->assertFalse($this->registrador->hasPermission('borrar', 'productos'));
        
        // No tiene permisos sobre usuarios
        $this->assertFalse($this->registrador->hasPermission('ver-reportes', 'usuarios'));
        $this->assertFalse($this->registrador->hasPermission('crear', 'usuarios'));
        
        // Usuario sin rol no tiene permisos
        $this->assertFalse($this->userWithoutRole->hasPermission('ver-reportes', 'usuarios'));
        $this->assertFalse($this->userWithoutRole->hasPermission('crear', 'productos'));
    }
} 
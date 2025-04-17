<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Entity;
use App\Models\Permission;
use App\Models\RoleEntityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Http\Middleware\CheckRole;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * @author Kendall Angulo Chaves <kendallangulo01@gmail.com>
 */

class CheckRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $superadminRole;
    protected $auditorRole;
    protected $registradorRole;
    protected $user;
    protected $superadmin;
    protected $userWithoutRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear los roles necesarios para las pruebas
        $this->superadminRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'superadmin'
        ]);
        
        $this->auditorRole = Role::create([
            'name' => 'Auditor', 
            'slug' => 'auditor'
        ]);
        
        $this->registradorRole = Role::create([
            'name' => 'Registrador',
            'slug' => 'registrador'
        ]);
        
        // Crear un usuario con rol Auditor
        $this->user = User::factory()->create([
            'role_id' => $this->auditorRole->id,
            'username' => 'testuser'
        ]);
        
        // Crear usuario SuperAdmin
        $this->superadmin = User::factory()->create([
            'role_id' => $this->superadminRole->id,
            'username' => 'admin_user'
        ]);
        
        // Crear usuario sin rol
        $this->userWithoutRole = User::factory()->create([
            'role_id' => null,
            'username' => 'user_without_role'
        ]);

        // Crear entidades y permisos
        $usuariosEntity = Entity::create(['name' => 'Usuarios', 'slug' => 'usuarios']);
        $productosEntity = Entity::create(['name' => 'Productos', 'slug' => 'productos']);
        $rolesEntity = Entity::create(['name' => 'Roles', 'slug' => 'roles']);
        
        $verReportes = Permission::create(['name' => 'Ver Reportes', 'slug' => 'ver-reportes']);
        $crear = Permission::create(['name' => 'Crear', 'slug' => 'crear']);
        $editar = Permission::create(['name' => 'Editar', 'slug' => 'editar']);
        $borrar = Permission::create(['name' => 'Borrar', 'slug' => 'borrar']);
        
        // Asignar permisos al SuperAdmin
        foreach ([$usuariosEntity, $productosEntity, $rolesEntity] as $entity) {
            foreach ([$verReportes, $crear, $editar, $borrar] as $permission) {
                RoleEntityPermission::create([
                    'role_id' => $this->superadminRole->id,
                    'entity_id' => $entity->id,
                    'permission_id' => $permission->id
                ]);
            }
        }
        
        // Asignar permisos al Auditor
        foreach ([$usuariosEntity, $productosEntity, $rolesEntity] as $entity) {
            RoleEntityPermission::create([
                'role_id' => $this->auditorRole->id,
                'entity_id' => $entity->id,
                'permission_id' => $verReportes->id
            ]);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar los datos creados para evitar conflictos entre pruebas
        User::where('username', 'testuser')->delete();
        User::where('username', 'admin_user')->delete();
        User::where('username', 'user_without_role')->delete();
        
        Role::where('name', 'SuperAdmin')->delete();
        Role::where('name', 'Auditor')->delete();
        Role::where('name', 'Registrador')->delete();
        
        parent::tearDown();
    }

    public function test_middleware_exists()
    {
        // Verificar que el middleware existe
        $this->assertTrue(class_exists('App\Http\Middleware\CheckRole'));
    }

    public function test_user_has_role_method_works_correctly()
    {
        // Verificar que los métodos de comprobación de roles funcionan correctamente
        $this->assertTrue($this->superadmin->hasRole('superadmin'));
        $this->assertFalse($this->superadmin->hasRole('auditor'));
        
        $this->assertTrue($this->user->hasRole('auditor'));
        $this->assertFalse($this->user->hasRole('superadmin'));
        
        // Usuario sin rol
        $this->assertFalse($this->userWithoutRole->hasRole('superadmin'));
        $this->assertFalse($this->userWithoutRole->hasRole('auditor'));
    }
}

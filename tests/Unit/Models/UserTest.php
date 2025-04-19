<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tests\TestCase;
use Mockery;
use ReflectionClass;

class UserTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Prueba las características básicas del modelo User
     */
    public function test_user_basic_features()
    {
        $user = new User();
        
        // Verificar la tabla
        $this->assertEquals('users', $user->getTable());
        
        // Verificar los campos fillable
        $this->assertContains('username', $user->getFillable());
        $this->assertContains('password', $user->getFillable());
        $this->assertContains('role_id', $user->getFillable());
        $this->assertContains('last_login_at', $user->getFillable());
        
        // Verificar los campos hidden
        $this->assertContains('password', $user->getHidden());
        
        // Verificar que extiende de Authenticatable
        $this->assertInstanceOf(Authenticatable::class, $user);
        
        // Verificar traits
        $reflection = new ReflectionClass(User::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
        $this->assertContains('Illuminate\Notifications\Notifiable', $traits);
    }

    /**
     * Prueba el método casts
     */
    public function test_user_casts()
    {
        $user = new User();
        $casts = $user->getCasts();
        
        $this->assertIsArray($casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('last_login_at', $casts);
        $this->assertEquals('hashed', $casts['password']);
        $this->assertEquals('datetime', $casts['last_login_at']);
    }

    /**
     * Prueba la relación con Role
     */
    public function test_user_role_relation()
    {
        $user = new User();
        $relation = $user->role();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('role_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Role::class, $relation->getRelated());
    }

    /**
     * Prueba la existencia de los métodos requeridos en User
     */
    public function test_user_has_required_methods()
    {
        $this->assertTrue(method_exists(User::class, 'hasRole'));
        $this->assertTrue(method_exists(User::class, 'hasPermission'));
        $this->assertTrue(method_exists(User::class, 'canAccess'));
        $this->assertTrue(method_exists(User::class, 'getAllPermissions'));
    }
} 
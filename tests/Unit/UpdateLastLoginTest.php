<?php

namespace Tests\Unit;

use App\Http\Middleware\UpdateLastLogin;
use PHPUnit\Framework\TestCase;

class UpdateLastLoginTest extends TestCase
{
    /**
     * Test básico para verificar que la clase existe.
     */
    public function test_update_last_login_middleware_exists()
    {
        $this->assertTrue(class_exists(UpdateLastLogin::class));
    }
} 
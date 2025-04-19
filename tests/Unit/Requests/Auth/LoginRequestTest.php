<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Mockery;

class LoginRequestTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorization_is_allowed()
    {
        $request = new LoginRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_validation_rules()
    {
        $request = new LoginRequest();
        $rules = $request->rules();
        
        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertContains('required', $rules['username']);
        $this->assertContains('string', $rules['username']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
    }

    public function test_throttle_key_is_ip_address()
    {
        $request = Mockery::mock(LoginRequest::class)->makePartial();
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        
        $this->assertEquals('127.0.0.1', $request->throttleKey());
    }

    public function test_ensure_is_not_rate_limited_does_not_throw_when_attempts_are_below_limit()
    {
        $request = Mockery::mock(LoginRequest::class)->makePartial();
        $request->shouldReceive('throttleKey')->andReturn('test_key');
        
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->with('test_key', 5)
            ->andReturn(false);
        
        // No debería lanzar una excepción
        $request->ensureIsNotRateLimited();
        $this->assertTrue(true); // Si llegamos aquí, no se lanzó ninguna excepción
    }

    public function test_ensure_is_not_rate_limited_throws_when_too_many_attempts()
    {
        $this->expectException(ValidationException::class);
        
        Event::fake();
        
        $request = Mockery::mock(LoginRequest::class)->makePartial();
        $request->shouldReceive('throttleKey')->andReturn('test_key');
        
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->with('test_key', 5)
            ->andReturn(true);
            
        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->with('test_key')
            ->andReturn(60);
        
        $request->ensureIsNotRateLimited();
        
        Event::assertDispatched(Lockout::class);
    }

    public function test_authenticate_clears_rate_limiter_when_successful()
    {
        $request = Mockery::mock(LoginRequest::class)->makePartial();
        $request->shouldReceive('ensureIsNotRateLimited')->once();
        $request->shouldReceive('throttleKey')->andReturn('test_key');
        $request->shouldReceive('only')->with('username', 'password')->andReturn(['username' => 'user1', 'password' => 'password']);
        $request->shouldReceive('boolean')->with('remember')->andReturn(false);
        
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['username' => 'user1', 'password' => 'password'], false)
            ->andReturn(true);
            
        RateLimiter::shouldReceive('clear')
            ->once()
            ->with('test_key');
        
        $request->authenticate();
        
        $this->assertNull(session('login_blocked'));
        $this->assertNull(session('block_seconds'));
    }

    public function test_authenticate_increments_rate_limiter_and_throws_exception_when_authentication_fails()
    {
        $this->expectException(ValidationException::class);
        
        $request = Mockery::mock(LoginRequest::class)->makePartial();
        $request->shouldReceive('ensureIsNotRateLimited')->once();
        $request->shouldReceive('throttleKey')->andReturn('test_key');
        $request->shouldReceive('only')->with('username', 'password')->andReturn(['username' => 'user1', 'password' => 'wrong_password']);
        $request->shouldReceive('boolean')->with('remember')->andReturn(false);
        
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['username' => 'user1', 'password' => 'wrong_password'], false)
            ->andReturn(false);
            
        RateLimiter::shouldReceive('hit')
            ->once()
            ->with('test_key', 90)
            ->andReturn(1);
        
        $request->authenticate();
    }
} 
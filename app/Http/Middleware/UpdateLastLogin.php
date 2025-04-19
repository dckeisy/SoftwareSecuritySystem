<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo actualizar si el usuario acaba de iniciar sesión
        if ($request->is('login') && $request->isMethod('post') && Auth::check()) {
            $userId = Auth::id();
            if ($userId) {
                DB::table('users')
                    ->where('id', $userId)
                    ->update(['last_login_at' => now()]);
            }
        }

        return $response;
    }
} 
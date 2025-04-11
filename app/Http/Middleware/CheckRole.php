<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,string $role): Response
    {
        if (strtolower($request->user()->role) !== strtolower($role)) {
            if (strtolower($request->user()->role) === 'superadmin') {
                return redirect()->route('dashboard');
            } elseif (strtolower($request->user()->role) === 'usuario') {
                return redirect()->route('userhome');
            } else {
                return redirect()->route('home');
            }
        }

        return $next($request);
    }
}

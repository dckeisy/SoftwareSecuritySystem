<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Verificar si el usuario está autenticado
        if (!$request->user()) {
            // Añadir log para depuración
            Log::info('CheckRole: Usuario no autenticado - redirigiendo a login');
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder.');
        }
        
        // Verificar si tiene un rol asignado
        if (!$request->user()->role) {
            // Añadir log para depuración
            Log::info('CheckRole: Usuario sin rol asignado - error 403');
            abort(403, 'No tiene un rol asignado para acceder a esta sección.');
        }
        
        // Permitir múltiples roles separados por coma
        $roles = explode(',', $role);
        
        // Añadir log para depuración
        Log::info('CheckRole: Verificando roles para usuario ' . $request->user()->name . ' con rol ' . $request->user()->role->name . ' (slug: ' . $request->user()->role->slug . ')');
        Log::info('CheckRole: Roles requeridos: ' . $role);
        
        // Verificar si el usuario tiene alguno de los roles especificados
        foreach ($roles as $singleRole) {
            if ($request->user()->hasRole(trim($singleRole))) {
                Log::info('CheckRole: Usuario tiene el rol requerido (' . $singleRole . ') - acceso permitido');
                return $next($request);
            }
        }
        
        // Si no tiene ninguno de los roles requeridos, redireccionar según su rol
        Log::info('CheckRole: Usuario no tiene ninguno de los roles requeridos - redirigiendo según su rol');
        
        // Los roles por defecto tienen comportamiento específico
        if ($request->user()->role->name === 'SuperAdmin') {
            Log::info('CheckRole: Redirigiendo a dashboard (SuperAdmin)');
            return redirect()->route('dashboard');
        } else {
            // Cualquier otro rol (incluyendo los personalizados) se redirige a userhome
            Log::info('CheckRole: Redirigiendo a userhome (rol: ' . $request->user()->role->name . ')');
            return redirect()->route('userhome');
        }
    }
}

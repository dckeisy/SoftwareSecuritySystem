<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Entity;
use App\Models\Permission;

class CheckEntityPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permissionSlug, $entitySlug): Response
    {
        // Verificar si el usuario está autenticado
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'Debe iniciar sesión para acceder a esta función.');
        }
        
        // Verificar si tiene un rol asignado
        if (!$request->user()->role) {
            abort(403, 'No tiene un rol asignado para acceder a esta sección.');
        }
        
        // Verificar si el usuario tiene el permiso específico para la entidad
        if (!$request->user()->hasPermission($permissionSlug, $entitySlug)) {
            // Si el usuario es SuperAdmin, permitir acceso a todo
            if ($request->user()->hasRole('superadmin')) {
                return $next($request);
            }
            
            // Para otros roles, redirigir según la naturaleza del error
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tiene permiso para realizar esta operación.'], 403);
            }
            
            // Redirigir a la página adecuada según el rol
            if ($request->user()->hasRole('auditor') || $request->user()->hasRole('registrador')) {
                return redirect()->route('userhome')
                    ->with('error', 'No tiene permiso para realizar esta operación.');
            }
            
            return redirect()->route('home')
                ->with('error', 'No tiene permiso para realizar esta operación.');
        }
        
        return $next($request);
    }
}

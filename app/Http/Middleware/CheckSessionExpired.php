<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionExpired
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario no está autenticado, redirigir al login
        if (!Auth::check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Su sesión ha expirado.'], 401);
            }
            return redirect()->route('login')->with('status', 'Su sesión ha expirado. Por favor inicie sesión nuevamente.');
        }

        // Si la sesión está activa, continuar y agregar encabezados para evitar caché
        $response = $next($request);
        
        // Prevenir que el navegador almacene en caché las páginas protegidas
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        
        return $response;
    }
} 
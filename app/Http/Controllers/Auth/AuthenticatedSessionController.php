<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\DB;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function create()
    {
        // Verificar si la solicitud es AJAX, lo que podría indicar una verificación de sesión
        if (request()->ajax() || request()->wantsJson()) {
            if (!Auth::check()) {
                return response()->json(['message' => 'Sesión expirada'], 401);
            }
            return response()->json(['message' => 'Autenticado'], 200);
        }

        $key = request()->ip(); // o el throttleKey que usás
        $maxAttempts = 5;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            session([
                'login_blocked' => true,
                'block_seconds' => $seconds,
            ]);
        } else {
            session()->forget(['login_blocked', 'block_seconds']);
        }
        $blocked = session('login_blocked', false);
        $seconds = session('block_seconds', 0);

        // Verificar si la sesión expiró para mostrar un mensaje
        $status = request()->has('expired') ? 'Su sesión ha expirado. Por favor inicie sesión nuevamente.' : session('status');

        return view('auth.login', compact('blocked', 'seconds', 'status'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();
        if ($user) {
            // Actualizar el tiempo de último login usando DB
            DB::table('users')->where('id', $user->id)
                ->update(['last_login_at' => now()]);
        }

        // Redirect based on user role
        if ($user && $user->role && $user->role->name === 'SuperAdmin') {
            return redirect()->intended(route('dashboard'));
        } else {
            return redirect()->intended(route('userhome'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

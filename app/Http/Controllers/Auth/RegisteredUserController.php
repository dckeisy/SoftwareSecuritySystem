<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function index()
    {
        $users = User::with('role')->get();
    
        // Convertimos la fecha UTC a la zona horaria local de cada usuario
        foreach ($users as $user) {
            if ($user->last_login_at) {
                $user->last_login_at = Carbon::parse($user->last_login_at)
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s');  // Cambiamos el formato a 'año-mes-día' que es más estándar
            }
            // Escapamos datos para prevenir XSS
            $user->username = e($user->username);

        }
    
        return view("auth.users.index", compact("users"));
    }

    public function create(): View
    {
        $roles = Role::all();
        return view('auth.users.create', compact('roles'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    try {
        $validated = $request->validate([
            'username' => [
                'required', 
                'string', 
                'max:255', 
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_.-]+$/'
            ],
            'password' => [
                'required', 
                'confirmed', 
                Rules\Password::min(10)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'username' => trim($validated['username']),
            'password' => Hash::make($validated['password']),
            'role_id' => (int)$validated['role_id'],
        ]);

        event(new Registered($user));
        
        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error al crear el usuario: ' . $e->getMessage())->withInput();
    }
}


    public function edit(User $user): View
    {
        $roles = Role::all();
        return view('auth.users.edit', compact('user', 'roles'));
    }
    
    public function update(Request $request, User $user): RedirectResponse
    {
        try {
         // Validamos los datos
         $validated = $request->validate([
            'username' => [
                'required', 
                'string', 
                'max:255', 
                'unique:users,username,' . $user->id . ',id',
                'regex:/^[a-zA-Z0-9_.-]+$/' // Solo permitir alfanuméricos y caracteres limitados
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);
      
        // Actualizamos con datos sanitizados
        $user->username = trim($validated['username']);
        $user->role_id = (int)$validated['role_id'];

        // Opcional actualización de contraseña
        if ($request->filled('password')) {
            $passwordValidated = $request->validate([
                'password' => [
                    'confirmed', 
                    Rules\Password::min(10)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised()
                ],
            ]);
            
            $user->password = Hash::make($passwordValidated['password']);
        }

        $user->save();
        
        // Regeneramos sesión SOLO si el usuario está modificando su propio perfil
        if (Auth::id() === $user->id) {
            $request->session()->regenerate();
        }

            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(User $user): RedirectResponse
    {
         // Evitamos que un usuario se elimine a sí mismo
         if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }
      
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}

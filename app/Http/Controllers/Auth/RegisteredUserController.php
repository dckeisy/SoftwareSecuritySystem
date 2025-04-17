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
        }
    
        return view("auth.users.index", compact("users"));
    }

    public function create(): View
    {
        $roles = Role::all();
        
        // Preparar los datos de roles para JavaScript
        $rolesData = [];
        foreach ($roles as $role) {
            $permissions = [];
            foreach ($role->entities() as $entity) {
                $permissions[$entity->name] = $role->getPermissionsForEntity($entity->id)->pluck('name')->toArray();
            }
            $rolesData[$role->id] = [
                'name' => $role->name,
                'permissions' => $permissions
            ];
        }
        
        return view('auth.users.create', compact('roles', 'rolesData')); 
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        try {
            $user = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
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
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id . ',id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        try {
            $user->username = $request->username;
            $user->role_id = $request->role_id;

            if ($request->filled('password')) {
                $request->validate([
                    'password' => ['confirmed', Rules\Password::defaults()],
                ]);
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            // Verificar que no se esté eliminando al usuario actual
            if (Auth::id() === $user->id) {
                return redirect()->route('users.index')->with('error', 'No puede eliminar su propio usuario.');
            }
            
            $user->delete();
            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }
}

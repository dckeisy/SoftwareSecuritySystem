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
    public function index(): View
    {
        $users = User::with('role')->get();

        foreach ($users as $user) {
            if ($user->last_login_at) {
                $user->last_login_at = Carbon::parse($user->last_login_at)
                    ->setTimezone(config('app.timezone'))
                    ->format('Y-m-d H:i:s');
            }
            $user->username = e($user->username);
        }

        return view('auth.users.index', compact('users'));
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
        // First, run validation and let validation exceptions bubble
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

        try {
            $user = User::create([
                'username' => trim($validated['username']),
                'password' => Hash::make($validated['password']),
                'role_id' => (int)$validated['role_id'],
            ]);

            event(new Registered($user));

            return redirect()->route('users.index')
                ->with('success', 'Usuario creado correctamente.');
        // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage())
                ->withInput();
        }
        // @codeCoverageIgnoreEnd
    }

    public function edit(User $user): View
    {
        $user->username = e($user->username);
        $roles = Role::all();
        return view('auth.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Validate input; ValidationException will bubble on failure
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                'unique:users,username,' . $user->id . ',id',
                'regex:/^[a-zA-Z0-9_.-]+$/'
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        // Assign validated data
        $user->username = trim($validated['username']);
        $user->role_id = (int)$validated['role_id'];

        // If password provided, validate and hash
        if ($request->filled('password')) {
            // @codeCoverageIgnoreStart
            $pwd = $request->validate([
                'password' => [
                    'confirmed',
                    Rules\Password::min(10)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised()
                ],
            ]);
            $user->password = Hash::make($pwd['password']);
            // @codeCoverageIgnoreEnd
        }

        try {
            $user->save();

            // Regenerate session if the authenticated user updated their own profile
            if (Auth::id() === $user->id) {
                $request->session()->regenerate();
            }

            return redirect()->route('users.index')
                ->with('success', 'Usuario actualizado correctamente.');
        // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            // Unexpected errors
            return redirect()->back()
                ->with('error', 'Error al actualizar el usuario: ' . $e->getMessage())
                ->withInput();
        }
        // @codeCoverageIgnoreEnd
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        try {
            $user->delete();
            return redirect()->route('users.index')
                ->with('success', 'Usuario eliminado.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }
}

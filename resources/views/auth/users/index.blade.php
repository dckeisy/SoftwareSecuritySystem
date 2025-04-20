<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Lista de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Mensajes de sesión --}}
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        @if(Auth::user()->hasPermission('crear', 'usuarios'))
                        <a href="{{ route('users.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Crear Usuario
                        </a>
                        @endif
                    </div>
                    <table class="min-w-full bg-white dark:bg-gray-700 mt-4">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">ID</th>
                                <th class="py-2 px-4 border-b">Username</th>
                                <th class="py-2 px-4 border-b">Rol</th>
                                <th class="py-2 px-4 border-b">Permisos</th>
                                <th class="py-2 px-4 border-b">Último Login</th>
                                <th class="py-2 px-4 border-b">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td class="py-2 px-4 border-b">{{ $user->id }}</td>
                                    <td class="py-2 px-4 border-b">{{ $user->username }}</td>
                                    <td class="py-2 px-4 border-b">
                                        @if ($user->role)
                                            {{ $user->role->name }}
                                        @else
                                            Sin rol asignado
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        @if ($user->role)
                                            @foreach ($user->getAllPermissions() as $entityName => $permissions)
                                                <div class="mb-1">
                                                    <span class="font-bold">{{ $entityName }}:</span>
                                                    @foreach ($permissions as $permission)
                                                        <span class="text-sm bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded mr-1">
                                                            {{ $permission }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @else
                                            Sin permisos
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        @if ($user->last_login_at)
                                            {{ $user->last_login_at }}
                                        @else
                                            El usuario nunca se ha logueado
                                        @endif
                                    </td>

                                    <td class="py-2 px-4 border-b">
                                        @if(Auth::user()->hasPermission('editar', 'usuarios'))
                                        <a href="{{ route('users.edit', $user) }}" class="text-blue-500">Editar</a>
                                        @endif
                                        
                                        @if(Auth::user()->hasPermission('borrar', 'usuarios'))
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500">Eliminar</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

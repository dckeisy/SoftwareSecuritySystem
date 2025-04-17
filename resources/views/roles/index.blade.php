<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Lista de Roles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        @if(Auth::user()->hasPermission('crear', 'roles'))
                        <a href="{{ route('roles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Crear Rol
                        </a>
                        @endif
                    </div>
                    <table class="table-fixed w-full bg-white dark:bg-gray-700 mt-4">
                        <thead>
                            <tr>
                                <th class="w-2/12 py-2 px-4 border-b text-center">Nombre</th>
                                <th class="w-2/12 py-2 px-4 border-b text-center">Slug</th>
                                <th class="py-2 px-4 border-b text-center">Permisos por Entidad</th>
                                <th class="w-2/12 py-2 px-4 border-b text-center">Acciones</th>
                                <th class="w-2/12 py-2 px-4 border-b text-center">Permisos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td class="py-2 px-4 border-b">{{ $role->name }}</td>
                                    <td class="py-2 px-4 border-b">{{ $role->slug }}</td>
                                    <td class="py-2 px-4 border-b">
                                        @foreach ($role->entities() as $entity)
                                            <div class="mb-1">
                                                <span class="font-bold">{{ $entity->name }}:</span>
                                                @foreach ($role->getPermissionsForEntity($entity->id) as $permission)
                                                    <span class="text-sm bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded mr-1">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="py-2 px-4 border-b text-center">
                                        @if(Auth::user()->hasPermission('editar', 'roles'))
                                        <a href="{{ route('roles.edit', $role->id) }}" class="text-blue-500">Editar</a>
                                        @endif
                                        
                                        @if(Auth::user()->hasPermission('borrar', 'roles'))
                                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 ml-2">Eliminar</button>
                                        </form>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 border-b text-center">
                                        @if(Auth::user()->hasPermission('editar', 'roles'))
                                        <a href="{{ route('roles.permissions', $role->id) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                            Asignar Permisos
                                        </a>
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
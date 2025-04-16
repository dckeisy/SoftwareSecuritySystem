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
                    <a href="{{ route('roles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Crear Rol
                    </a>
                    <table class="table-fixed w-full bg-white dark:bg-gray-700 mt-4">
                        <thead>
                            <tr>
                                <th class="w-1/4 py-2 px-4 border-b">Nombre</th>
                                <th class="w-2/5 py-2 px-4 border-b">Permisos</th>
                                <th class="w-1/6 py-2 px-4 border-b">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td class="py-2 px-4 border-b">{{ $role->name }}</td>
                                    <td class="py-2 px-4 border-b">
                                        @foreach ($role->permissions as $permission)
                                            <span>{{ $permission->name }}</span>
                                            @if (!$loop->last)
                                                <span> | </span>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <a href="{{ route('roles.edit', $role->id) }}" class="text-blue-500">Editar</a>
                                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 ml-2">Eliminar</button>
                                        </form>
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
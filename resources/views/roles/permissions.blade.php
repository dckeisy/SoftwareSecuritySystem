<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Asignar Permisos al Rol') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif
                    
                    <div class="mb-6">
                        <h3 class="text-lg font-medium">Detalles del Rol</h3>
                        <p><strong>Nombre:</strong> {{ $role->name }}</p>
                        <p><strong>Slug:</strong> {{ $role->slug }}</p>
                    </div>
                    
                    <form method="POST" action="{{ route('roles.update_permissions', $role->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <h3 class="text-lg font-medium mb-4">Asignar Permisos por Entidad</h3>
                        
                        <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded-md">
                            <p class="font-medium">Información importante:</p>
                            <ul class="list-disc pl-5 mt-1">
                                <li>Los permisos básicos (los mismos que tiene el rol Auditor) aparecerán marcados como "(Por defecto)".</li>
                                <li>Estos permisos básicos no se pueden quitar.</li>
                                <li>Puede asignar permisos adicionales según las necesidades del rol.</li>
                            </ul>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-700">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 bg-gray-200 dark:bg-gray-600 border-b">Entidad / Permiso</th>
                                        @foreach ($permissions as $permission)
                                            <th class="py-2 px-4 bg-gray-200 dark:bg-gray-600 border-b">{{ $permission->name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($entities as $entity)
                                        <tr>
                                            <td class="py-2 px-4 border-b font-medium">{{ $entity->name }}</td>
                                            
                                            @foreach ($permissions as $permission)
                                                <td class="py-2 px-4 border-b text-center">
                                                    @php
                                                        // Verificar si es un permiso predefinido del rol actual
                                                        $isDefaultPermission = isset($defaultPermissions[$entity->id]) && 
                                                                               in_array($permission->id, $defaultPermissions[$entity->id]);
                                                                               
                                                        // Verificar si es un permiso básico del Auditor (para roles personalizados)
                                                        $isAuditorPermission = false;
                                                        
                                                        // Solo aplicar para roles no predefinidos (que no sean SuperAdmin, Auditor o Registrador)
                                                        if (!in_array($role->name, ['SuperAdmin', 'Auditor', 'Registrador'])) {
                                                            // Emular la lógica del método getAuditorDefaultPermissions
                                                            if (
                                                                ($entity->slug == 'usuarios' && $permission->slug == 'ver-reportes') ||
                                                                ($entity->slug == 'productos' && $permission->slug == 'ver-reportes')
                                                            ) {
                                                                $isAuditorPermission = true;
                                                            }
                                                        }
                                                        
                                                        // Considerar como predeterminado si es un permiso del rol actual o del Auditor
                                                        $isDefaultPermission = $isDefaultPermission || $isAuditorPermission;
                                                        
                                                        $isChecked = $isDefaultPermission || 
                                                                    (isset($rolePermissions[$entity->id]) && 
                                                                     in_array($permission->id, $rolePermissions[$entity->id]));
                                                    @endphp
                                                    
                                                    @if ($isDefaultPermission)
                                                        <input type="checkbox" 
                                                               name="entity_permissions[{{ $entity->id }}][]" 
                                                               value="{{ $permission->id }}"
                                                               checked 
                                                               disabled 
                                                               class="opacity-50 cursor-not-allowed">
                                                        <input type="hidden" 
                                                               name="entity_permissions[{{ $entity->id }}][]" 
                                                               value="{{ $permission->id }}">
                                                        <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">(Por defecto)</span>
                                                    @else
                                                        <input type="checkbox" 
                                                               name="entity_permissions[{{ $entity->id }}][]" 
                                                               value="{{ $permission->id }}"
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Guardar Permisos
                            </button>
                            <a href="{{ route('roles.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-2">
                                Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
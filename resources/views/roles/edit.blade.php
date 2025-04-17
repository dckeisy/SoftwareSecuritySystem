<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Rol') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    @php
                        $isDefaultRole = in_array($role->name, ['SuperAdmin', 'Auditor', 'Registrador']);
                        $isDisabled = $isDefaultRole;
                    @endphp

                    @if($isDefaultRole)
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4">
                            Este es un rol predefinido del sistema y no puede ser editado.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('roles.update', $role->id) }}">
                        @csrf
                        @method('PUT')

                        <!-- Nombre -->
                        <div>
                            <x-input-label for="name" :value="__('Nombre del Rol')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $role->name)" required autofocus placeholder="Ingrese el nombre del rol" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Slug (solo lectura) -->
                        <div class="mt-4">
                            <x-input-label for="slug" :value="__('Slug (No Editable)')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="slug" 
                                class="block mt-1 w-full bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 cursor-not-allowed" 
                                type="text" 
                                :value="$role->slug" 
                                disabled />
                            <small class="text-gray-500 dark:text-gray-400">El slug se genera automáticamente a partir del nombre y no puede editarse directamente.</small>
                        </div>

                        <!-- Permisos por entidad -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium mb-4">Permisos</h3>
                            <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded-md">
                                <p class="font-medium">Información importante:</p>
                                <ul class="list-disc pl-5 mt-1">
                                    <li>Todos los roles mantienen permisos básicos (los mismos que tiene el rol Auditor).</li>
                                    <li>Estos permisos básicos no se pueden quitar.</li>
                                    <li>Puede seleccionar permisos adicionales según las necesidades del rol.</li>
                                </ul>
                            </div>
                            
                            @foreach ($entities as $entity)
                                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                                    <h4 class="font-semibold mb-2">{{ $entity->name }}</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        @foreach ($permissions as $permission)
                                            <div class="flex items-center">
                                                @php
                                                    // Verificar si es un permiso básico del Auditor
                                                    $isAuditorPermission = false;
                                                    
                                                    // Emular la lógica del método getAuditorDefaultPermissions
                                                    if (
                                                        ($entity->slug == 'usuarios' && $permission->slug == 'ver-reportes') ||
                                                        ($entity->slug == 'productos' && $permission->slug == 'ver-reportes')
                                                    ) {
                                                        $isAuditorPermission = true;
                                                    }
                                                    
                                                    $isChecked = $isAuditorPermission || 
                                                                (isset($rolePermissions[$entity->id]) && 
                                                                in_array($permission->id, $rolePermissions[$entity->id]));
                                                @endphp
                                                
                                                @if ($isAuditorPermission)
                                                    <input type="checkbox" 
                                                        id="permission_{{ $entity->id }}_{{ $permission->id }}" 
                                                        name="permissions[{{ $entity->id }}][]" 
                                                        value="{{ $permission->id }}"
                                                        checked
                                                        disabled
                                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500 opacity-50 cursor-not-allowed"
                                                        {{ $isDisabled ? 'disabled' : '' }}>
                                                    <input type="hidden" 
                                                        name="permissions[{{ $entity->id }}][]" 
                                                        value="{{ $permission->id }}">
                                                    <label for="permission_{{ $entity->id }}_{{ $permission->id }}" 
                                                        class="ml-2 text-sm {{ $isDisabled ? 'text-gray-500 dark:text-gray-500' : 'text-gray-500 dark:text-gray-400' }}">
                                                        {{ $permission->name }} <span class="text-xs">(Por defecto)</span>
                                                    </label>
                                                @else
                                                    <input type="checkbox" 
                                                        id="permission_{{ $entity->id }}_{{ $permission->id }}" 
                                                        name="permissions[{{ $entity->id }}][]" 
                                                        value="{{ $permission->id }}"
                                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500"
                                                        {{ $isChecked ? 'checked' : '' }}
                                                        {{ $isDisabled ? 'disabled' : '' }}>
                                                    <label for="permission_{{ $entity->id }}_{{ $permission->id }}" 
                                                        class="ml-2 text-sm {{ $isDisabled ? 'text-gray-500 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300' }}">
                                                        {{ $permission->name }}
                                                    </label>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('roles.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline mr-2">
                                {{ __('Cancelar') }}
                            </a>
                            @if(!$isDisabled)
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    {{ __('Actualizar') }}
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Crear Usuario') }}
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

                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf

                        <!-- Nombre de Usuario -->
                        <div>
                            <x-input-label for="username" :value="__('Nombre de Usuario')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="username" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="text" name="username" 
                                :value="old('username')" 
                                required autofocus autocomplete="username" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <!-- Contraseña -->
                        <div class="mt-4">
                            <x-input-label for="password" :value="__('Contraseña')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="password" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="password" name="password" 
                                required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="password_confirmation" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="password" name="password_confirmation" 
                                required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <!-- Rol -->
                        <div class="mt-4">
                            <x-input-label for="role_id" :value="__('Rol')" class="text-gray-800 dark:text-gray-100" />
                            <select id="role_id" name="role_id" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 rounded-md shadow-sm" 
                                required onchange="document.getElementById('role-info-' + this.value).style.display = 'block';">
                                <option value="">Seleccione un rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ $role->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                        </div>

                        <!-- Información de Roles -->
                        <div class="mt-6">
                            @foreach ($roles as $role)
                                <div id="role-info-{{ $role->id }}" class="bg-gray-100 dark:bg-gray-700 p-4 rounded-md mb-4" style="display: none;">
                                    <h3 class="text-md font-medium mb-2">Rol: {{ $role->name }}</h3>
                                    <div class="text-sm">
                                        @foreach ($role->entities() as $entity)
                                            @php
                                                $permissions = $role->getPermissionsForEntity($entity->id);
                                            @endphp
                                            @if(count($permissions) > 0)
                                                <div class="mb-2">
                                                    <p class="font-bold mb-1">{{ $entity->name }}:</p>
                                                    <ul class="list-disc pl-6 mb-2">
                                                        @foreach ($permissions as $permission)
                                                            <li>{{ $permission->name }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline mr-2">
                                {{ __('Cancelar') }}
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Crear Usuario') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role_id');
            
            // Ocultar todos los paneles de información
            function hideAllRoleInfos() {
                document.querySelectorAll('[id^="role-info-"]').forEach(function(el) {
                    el.style.display = 'none';
                });
            }
            
            // Mostrar el panel correspondiente al rol seleccionado
            function showSelectedRoleInfo() {
                hideAllRoleInfos();
                
                const selectedRoleId = roleSelect.value;
                if (selectedRoleId) {
                    const roleInfoElement = document.getElementById('role-info-' + selectedRoleId);
                    if (roleInfoElement) {
                        roleInfoElement.style.display = 'block';
                    }
                }
            }
            
            // Evento al cambiar el rol
            roleSelect.addEventListener('change', showSelectedRoleInfo);
            
            // Inicializar si hay un valor seleccionado
            if (roleSelect.value) {
                showSelectedRoleInfo();
            }
        });
    </script>
</x-app-layout>

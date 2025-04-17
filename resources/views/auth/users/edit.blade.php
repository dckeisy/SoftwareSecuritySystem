<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($errors->any())
                        <div class="bg-red-500 text-white p-4 rounded mb-4">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Nombre de usuario -->
                        <div>
                            <x-input-label for="username" :value="__('Nombre de Usuario')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="username" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="text" name="username" 
                                :value="old('username', $user->username)" 
                                required />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <!-- Contraseña -->
                        <div class="mt-4">
                            <x-input-label for="password" :value="__('Contraseña (dejar en blanco si no desea cambiarla)')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="password" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="password" name="password" 
                                autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="password_confirmation" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="password" name="password_confirmation" 
                                autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <!-- Rol -->
                        <div class="mt-4">
                            <x-input-label for="role_id" :value="__('Rol')" class="text-gray-800 dark:text-gray-100" />
                            <select id="role_id" name="role_id" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 rounded-md shadow-sm" 
                                required onchange="showRoleInfo(this.value)">
                                <option value="">Seleccione un rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
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
                                    <h3 class="text-md font-medium mb-2">Rol: {{ $role->name }} ({{ $role->slug }})</h3>
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

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline mr-2">
                                {{ __('Cancelar') }}
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Actualizar Usuario') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showRoleInfo(roleId) {
            // Ocultar todos los paneles de roles
            document.querySelectorAll('[id^="role-info-"]').forEach(function(el) {
                el.style.display = 'none';
            });
            
            // Mostrar el panel del rol seleccionado
            if (roleId) {
                const roleInfo = document.getElementById('role-info-' + roleId);
                if (roleInfo) {
                    roleInfo.style.display = 'block';
                }
            }
        }
        
        // Inicializar mostrando el rol actual
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role_id');
            if (roleSelect.value) {
                showRoleInfo(roleSelect.value);
            }
        });
    </script>
</x-app-layout>

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

                    <form method="POST" action="{{ route('roles.update', $role->id) }}">
                        @csrf
                        @method('PUT')

                        <!-- Nombre -->
                        <div>
                            <x-input-label for="name" :value="__('Nombre')" class="text-gray-800 dark:text-gray-100" />
                            <select id="name" name="name" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 rounded-md shadow-sm" 
                                required>
                                <option value="">Seleccione un rol</option>
                                <option value="SuperAdmin" {{ old('name', $role->name) == 'SuperAdmin' ? 'selected' : '' }}>SuperAdmin</option>
                                <option value="Auditor" {{ old('name', $role->name) == 'Auditor' ? 'selected' : '' }}>Auditor</option>
                                <option value="Registrador" {{ old('name', $role->name) == 'Registrador' ? 'selected' : '' }}>Registrador</option>
                            </select>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Slug (solo lectura) -->
                        <div class="mt-4">
                            <x-input-label for="slug" :value="__('Slug')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="slug" 
                                class="block mt-1 w-full bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 cursor-not-allowed" 
                                type="text" 
                                :value="$role->slug" 
                                readonly />
                        </div>

                        <!-- Permisos por defecto para cada rol -->
                        <div class="mt-6 bg-gray-100 dark:bg-gray-700 p-4 rounded-md">
                            <h3 class="text-md font-medium mb-2">Permisos por Defecto:</h3>
                            <div class="hidden" id="superadmin-permissions">
                                <p class="font-bold mb-1">SuperAdmin:</p>
                                <ul class="list-disc pl-6 mb-2 text-sm">
                                    <li>Usuarios: Crear, Editar, Borrar, Ver Reportes</li>
                                    <li>Roles: Crear, Editar, Borrar, Ver Reportes</li>
                                    <li>Productos: Ver Reportes</li>
                                </ul>
                            </div>
                            <div class="hidden" id="auditor-permissions">
                                <p class="font-bold mb-1">Auditor:</p>
                                <ul class="list-disc pl-6 mb-2 text-sm">
                                    <li>Usuarios: Ver Reportes</li>
                                    <li>Productos: Ver Reportes</li>
                                </ul>
                            </div>
                            <div class="hidden" id="registrador-permissions">
                                <p class="font-bold mb-1">Registrador:</p>
                                <ul class="list-disc pl-6 mb-2 text-sm">
                                    <li>Usuarios: Ver Reportes</li>
                                    <li>Productos: Crear, Editar, Borrar, Ver Reportes</li>
                                </ul>
                            </div>
                            <p class="text-xs italic mt-2">Nota: Estos permisos se asignarán automáticamente y no podrán ser eliminados.</p>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('roles.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline mr-2">
                                {{ __('Cancelar') }}
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Actualizar') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('name');
            const superadminPermissions = document.getElementById('superadmin-permissions');
            const auditorPermissions = document.getElementById('auditor-permissions');
            const registradorPermissions = document.getElementById('registrador-permissions');
            
            // Mostrar los permisos por defecto del rol seleccionado
            function showDefaultPermissions() {
                // Ocultar todos
                superadminPermissions.classList.add('hidden');
                auditorPermissions.classList.add('hidden');
                registradorPermissions.classList.add('hidden');
                
                // Mostrar según la selección
                if (roleSelect.value === 'SuperAdmin') {
                    superadminPermissions.classList.remove('hidden');
                } else if (roleSelect.value === 'Auditor') {
                    auditorPermissions.classList.remove('hidden');
                } else if (roleSelect.value === 'Registrador') {
                    registradorPermissions.classList.remove('hidden');
                }
            }
            
            // Inicializar la vista
            showDefaultPermissions();
            
            // Evento al cambiar el rol
            roleSelect.addEventListener('change', showDefaultPermissions);
        });
    </script>
</x-app-layout>
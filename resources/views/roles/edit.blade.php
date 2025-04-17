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
                    <form method="POST" action="{{ route('roles.update', $role->id) }}">
                        @csrf
                        @method('PUT')

                        <!-- Nombre -->
                        <div>
                            <x-select-input
                                name="name"
                                label="Nombre"
                                :options="['SuperAdmin' => 'SuperAdmin', 'Auditor' => 'Auditor', 'Registrador' => 'Registrador']"
                                :selected="old('name', $role->name)"
                                placeholder="Seleccione un rol"
                            />
                        </div>

                        <!-- Slug -->
                        <div class="mt-4">
                            <x-input-label for="slug" :value="__('Slug')" class="text-gray-800 dark:text-gray-100" />
                            <x-text-input id="slug" 
                                class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600" 
                                type="text" name="slug" 
                                :value="old('slug', $role->slug)" required />
                            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                        </div>

                        <!-- Permisos -->
                        <div class="mt-4">
                            <x-input-label :value="__('Permisos')" class="text-gray-800 dark:text-gray-100" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                                @foreach ($permissions as $permission)
                                    <label for="permission_{{ $permission->id }}" class="inline-flex items-center">
                                        <input type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $permission->id }}" 
                                            id="permission_{{ $permission->id }}"
                                            {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}
                                            class="rounded text-indigo-600 border-gray-300 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-600 dark:focus:ring-indigo-400">
                                        <span class="ml-2 text-sm text-gray-800 dark:text-gray-100">
                                            {{ $permission->name }} - {{ $permission->description }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('roles.index') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:underline">
                                {{ __('Cancelar') }}
                            </a>
                            <x-primary-button class="ms-4">
                                {{ __('Actualizar Rol') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
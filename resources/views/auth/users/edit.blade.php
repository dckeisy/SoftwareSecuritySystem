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

                    <form action="{{ route('users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Nombre de usuario -->
                        <label for="username" class="block text-gray-800 dark:text-gray-100 font-semibold">Nombre de usuario:</label>
                        <input type="text" name="username" class="w-full p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600" value="{{ old('username', $user->username) }}" required>

                        <!-- Contraseña -->
                        <label for="password" class="block mt-4 text-gray-800 dark:text-gray-100 font-semibold">Contraseña (dejar en blanco si no desea cambiarla):</label>
                        <input type="password" name="password" class="w-full p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600">

                        <!-- Confirmar contraseña -->
                        <label for="password_confirmation" class="block mt-4 text-gray-800 dark:text-gray-100 font-semibold">Confirmar contraseña:</label>
                        <input type="password" name="password_confirmation" class="w-full p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600">

                        <!-- Rol -->
                        <label for="role" class="block mt-4 text-gray-800 dark:text-gray-100 font-semibold">Rol:</label>
                        <input type="text" name="role" class="w-full p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600" value="{{ old('role', $user->role) }}" required>

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-6">
                            Actualizar Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

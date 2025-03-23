<x-guest-layout>
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 p-8 rounded-lg shadow-lg text-center">
        <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-200 mb-4">
            Bienvenido al Sistema de Gestión de Productos
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Accede a tu cuenta o regístrate para comenzar.
        </p>

        <div class="space-x-4">
            <a href="{{ route('login') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Iniciar Sesión
            </a>
            <a href="{{ route('register') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                Registrarse
            </a>
        </div>
    </div>
</x-guest-layout>
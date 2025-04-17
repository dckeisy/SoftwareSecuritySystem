<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Crear Producto') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf
                        <x-input-label for="code" :value="__('Código')" />
                        <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('Código')" required />

                        <x-input-label for="name" :value="__('Nombre')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('Nombre')" required />

                        <x-input-label for="description" :value="__('Descripción')" />
                        <x-text-area id="description" class="block mt-1 w-full" name="description" :value="old('Descripción')" required />

                        <x-input-label for="quantity" :value="__('Cantidad')" />
                        <x-text-input id="quantity" class="block mt-1 w-full" type="number" name="quantity" :value="old('Cantidad')" required />

                        <x-input-label for="price" :value="__('Precio')" />
                        <x-text-input id="price" class="block mt-1 w-full" type="text" name="price" :value="old('Precio')" required />

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-4">
                            Guardar Producto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Producto') }}
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

                    <form action="{{ route('products.update', $product) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <x-input-label for="code" :value="__('Código:')" />
                        <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code',$product->code)" required />

                        <x-input-label for="name" :value="__('Nombre:')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name',$product->name)" required />

                        <x-input-label for="description" :value="__('Descripción:')" />
                        <x-text-area id="description" class="block mt-1 w-full" name="description" required>
                            {{ old('description', $product->description) }}
                        </x-text-area>

                        <x-input-label for="quantity" :value="__('Cantidad:')" />
                        <x-text-input id="quantity" class="block mt-1 w-full" type="number" name="quantity" :value="old('quantity',$product->quantity)" required />

                        <x-input-label for="price" :value="__('Precio:')" />
                        <x-text-input id="price" class="block mt-1 w-full" type="text" name="price" :value="old('price',$product->price)" required />

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-4">
                            Actualizar Producto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

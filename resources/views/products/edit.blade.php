@include('profile/partials.navuser')
<x-user-layout>
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

                        <label for="code" class="block">Código:</label>
                        <input type="text" name="code" class="w-full p-2 rounded" value="{{ old('code', $product->code) }}" required>

                        <label for="name" class="block mt-2">Nombre:</label>
                        <input type="text" name="name" class="w-full p-2 rounded" value="{{ old('name', $product->name) }}" required>

                        <label for="description" class="block mt-2">Descripción:</label>
                        <textarea name="description" class="w-full p-2 rounded" required>{{ old('description', $product->description) }}</textarea>

                        <label for="quantity" class="block mt-2">Cantidad:</label>
                        <input type="number" name="quantity" class="w-full p-2 rounded" value="{{ old('quantity', $product->quantity) }}" required>

                        <label for="price" class="block mt-2">Precio:</label>
                        <input type="text" name="price" class="w-full p-2 rounded" value="{{ old('price', $product->price) }}" required>

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-4">
                            Actualizar Producto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>

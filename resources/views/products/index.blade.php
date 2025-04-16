<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Lista de Productos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="mb-4 text-center">
                        <a href="{{ route('products.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Crear Producto
                        </a>
                    </div>
                    <table class="min-w-full bg-white dark:bg-gray-700 mt-4">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-center">Código</th>
                                <th class="py-2 px-4 border-b text-center">Nombre</th>
                                <th class="py-2 px-4 border-b text-center">Cantidad</th>
                                <th class="py-2 px-4 border-b text-center">Precio</th>
                                <th class="py-2 px-4 border-b text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="py-2 px-4 border-b text-center">{{ $product->code }}</td>
                                    <td class="py-2 px-4 border-b text-center">{{ $product->name }}</td>
                                    <td class="py-2 px-4 border-b text-center">{{ $product->quantity }}</td>
                                    <td class="py-2 px-4 border-b text-center">${{ $product->price }}</td>
                                    <td class="py-2 px-4 border-b text-center">
                                        <a href="{{ route('products.edit', $product) }}" class="text-blue-500">Editar</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

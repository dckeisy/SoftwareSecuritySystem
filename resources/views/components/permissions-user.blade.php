@props(['for', 'value'])

<div class="mt-4">
    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ $value }}</h3>
    <div class="mt-2 space-y-2">
        <div class="flex items-center">
            <x-text-input id="create" class="rounded border-gray-300 shadow-sm" type="checkbox" name="permissions[]" :value="old('create')" />
            <x-input-label for="create" :value="__('Crear')" class="ml-2"/>
        </div>
        <!--Read-->
        <div class="flex items-center">
            <x-text-input id="read" class="rounded border-gray-300 shadow-sm" type="checkbox" name="permissions[]" :value="old('read')" />
            <x-input-label for="read" :value="__('Ver Reportes')" class="ml-2"/>
        </div>
        <!--Update-->
        <div class="flex items-center">
            <x-text-input id="update" class="rounded border-gray-300 shadow-sm" type="checkbox" name="permissions[]" :value="old('update')" />
            <x-input-label for="update" :value="__('Editar')" class="ml-2"/>
        </div>
        <!--Delete-->
        <div class="flex items-center">
            <x-text-input id="delete" class="rounded border-gray-300 shadow-sm" type="checkbox" name="permissions[]" :value="old('delete')" />
            <x-input-label for="delete" :value="__('Borrar')" class="ml-2"/>
        </div>
    </div>
</div>
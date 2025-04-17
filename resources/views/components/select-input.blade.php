@props([
    'name',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Seleccione una opción',
    'required' => false,
])

@php
    $id = $id ?? $name;
@endphp

@if ($label)
    <x-input-label :for="$id" :value="$label" class="text-gray-800 dark:text-gray-100" />
@endif

<select
    name="{{ $name }}"
    id="{{ $id }}"
    @if ($required) required @endif
    {{ $attributes->merge(['class' =>
        'block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 
        border-gray-300 dark:border-gray-600 rounded-md shadow-sm 
        focus:ring focus:ring-indigo-200 focus:border-indigo-500'
    ]) }}
>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $key => $value)
        <option value="{{ $key }}" {{ (old($name, $selected) == $key) ? 'selected' : '' }}>
            {{ $value }}
        </option>
    @endforeach
</select>

<x-input-error :messages="$errors->get($name)" class="mt-2" />
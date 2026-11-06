{{-- resources/views/components/forms/input.blade.php --}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'autocomplete' => null,
    'id' => null,
    'required' => false,      // Asterisco en la etiqueta (la validación es del servidor).
    'hint' => null,
    'modelModifier' => null,  // Ej. 'live.debounce.300ms' para wire:model.live.debounce.300ms.
])

@php
    $id = $id ?? str_replace('.', '-', $name);
    $model = $modelModifier ? "wire:model.{$modelModifier}" : 'wire:model';
@endphp

<x-forms.field :id="$id" :name="$name" :label="$label" :required="$required" :hint="$hint">
    <input id="{{ $id }}" type="{{ $type }}" {{ $model }}="{{ $name }}"
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @if ($required) aria-required="true" @endif
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
</x-forms.field>

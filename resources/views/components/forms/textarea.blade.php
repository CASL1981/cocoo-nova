{{-- Área de texto con etiqueta, ayuda y error (misma apariencia que <x-forms.input>). --}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'rows' => 3,
    'required' => false,
    'hint' => null,
])

@php $id = $id ?? str_replace('.', '-', $name); @endphp

<x-forms.field :id="$id" :name="$name" :label="$label" :required="$required" :hint="$hint">
    <textarea id="{{ $id }}" rows="{{ $rows }}" wire:model="{{ $name }}" @if ($required) aria-required="true" @endif
              {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}></textarea>
</x-forms.field>

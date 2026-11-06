{{--
    Select con opciones [valor => etiqueta].

    Con `name` se pinta como campo completo (etiqueta, obligatorio, ayuda, error y
    wire:model="{name}"); sin `name` se comporta como antes: solo el <select>, con
    wire:model y demás atributos pasados por quien lo usa.
--}}
@props([
    'options' => [],        // array o Collection: puede ser [value => label] YA MAPEADO,
                             // o una colección/array crudo de modelos/objetos (usar valueField/labelField)
    'valueField' => null,    // ej: 'id' -> úsalo cuando $options trae objetos/arrays sin mapear
    'labelField' => null,    // ej: 'name'
    'placeholder' => null,
    'name' => null,          // Clave de wire:model y del error de validación.
    'label' => null,
    'required' => false,
    'hint' => null,
    'id' => null,
    'modelModifier' => null,  // Ej. 'live' cuando el valor cambia otros campos del formulario.
])

@php
    $items = collect($options);

    // Si vienen filas crudas (p. ej. resultado directo de una consulta), se mapean aquí
    if ($valueField && $labelField) {
        $items = $items->mapWithKeys(fn ($item) => [
            data_get($item, $valueField) => data_get($item, $labelField),
        ]);
    }

    $id = $id ?? ($name ? str_replace('.', '-', $name) : null);
    $attributes = $attributes->class(['form-select', 'is-invalid' => $name && $errors->has($name)]);

    if ($name) {
        $attributes = $attributes->merge([($modelModifier ? "wire:model.{$modelModifier}" : 'wire:model') => $name]);
    }
@endphp

@if ($name)
    <x-forms.field :id="$id" :name="$name" :label="$label" :required="$required" :hint="$hint">
        <select id="{{ $id }}" @if ($required) aria-required="true" @endif {{ $attributes }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($items as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        </select>
    </x-forms.field>
@else
    <select @if ($id) id="{{ $id }}" @endif {{ $attributes }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($items as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
@endif

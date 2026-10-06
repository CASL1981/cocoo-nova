@props([
    'options' => [],        // array o Collection: puede ser [value => label] YA MAPEADO,
                             // o una colección/array crudo de modelos/objetos (usar valueField/labelField)
    'valueField' => null,    // ej: 'id' -> úsalo cuando $options trae objetos/arrays sin mapear
    'labelField' => null,    // ej: 'name'
    'placeholder' => null,
])

@php
    $items = collect($options);

    // Si vienen filas crudas (p. ej. resultado directo de una consulta), se mapean aquí
    if ($valueField && $labelField) {
        $items = $items->mapWithKeys(fn ($item) => [
            data_get($item, $valueField) => data_get($item, $labelField),
        ]);
    }
@endphp

<select {{ $attributes->class(['form-select ']) }}>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($items as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>

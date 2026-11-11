{{--
    Campo de valor en pesos colombianos: mientras se escribe muestra el formato $ 1.234.567,89
    (punto de miles, coma decimal, máximo 2 decimales) y envía a Livewire el número sin formato
    (1234567.89), así las reglas numeric/min del servidor no cambian. Lógica en moneyInput
    (resources/js/app.js), enlazada al wire:model con x-modelable.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,       // Asterisco en la etiqueta (la validación es del servidor).
    'hint' => null,
    'allowNegative' => false,  // Ej. el patrimonio puede ser negativo.
])

@php
    $id = $id ?? str_replace('.', '-', $name);
@endphp

<x-forms.field :id="$id" :name="$name" :label="$label" :required="$required" :hint="$hint">
    <div class="input-group money-input" x-data="moneyInput({ negative: @js((bool) $allowNegative) })" x-modelable="value" wire:model="{{ $name }}">
        <span class="input-group-text" aria-hidden="true">$</span>
        <input id="{{ $id }}" type="text" inputmode="decimal" autocomplete="off"
               x-model="display" x-on:input.stop="sync($event)"
               @if ($required) aria-required="true" @endif
               {{ $attributes->class(['form-control', 'text-end', 'is-invalid' => $errors->has($name)]) }}>
    </div>
</x-forms.field>

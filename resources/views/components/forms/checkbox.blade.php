{{--
    Casilla de verificación con etiqueta, ayuda y error (misma API que los demás campos).
    Se usa para respuestas Sí/No y declaraciones; la validación vive en el servidor.
--}}
@props([
    'name',
    'label',
    'id' => null,
    'hint' => null,
    'info' => null,           // Texto largo que se lee al pasar el mouse por un ícono (ej. declaraciones).
    'modelModifier' => null,  // Ej. 'live' cuando la casilla muestra u oculta otros campos.
])

@php
    $id = $id ?? str_replace('.', '-', $name);
    $model = $modelModifier ? "wire:model.{$modelModifier}" : 'wire:model';
@endphp

<div {{ $attributes->only('class')->class('form-check') }}>
    <input id="{{ $id }}" type="checkbox" {{ $model }}="{{ $name }}"
           {{ $attributes->except('class')->class(['form-check-input', 'is-invalid' => $errors->has($name)]) }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($info)
        {{-- Fuera de la etiqueta: pasar el mouse o hacer clic en el ícono no marca la casilla. --}}
        <x-tooltip :text="$info" wide class="ms-1">
            <i class="bi bi-info-circle text-primary form-check-info" tabindex="0" role="img" aria-label="Texto completo: {{ $info }}"></i>
        </x-tooltip>
    @endif
    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

{{--
    Envoltorio común de un campo: etiqueta, marca de obligatorio, ayuda y error.
    Lo usan input, select, textarea y search-select para que todos se vean igual.
--}}
@props([
    'id',
    'name' => null,       // Clave del error de validación (ej. form.name).
    'label' => null,
    'required' => false,  // Solo visual (asterisco); la validación vive en el servidor.
    'hint' => null,       // Texto de ayuda bajo el campo.
])

<div {{ $attributes->class('form-field') }}>
    @if ($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}@if ($required)<span class="form-required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif
    @if ($name)
        @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @endif
</div>

@props([
    'name',              // ej: "form.name" -> usado para wire:model, error() e id
    'label' => null,
    'type' => 'text',
    'autocomplete' => null,
])

@php
    $id = str_replace('.', '-', $name);
@endphp

<div>
    @if ($label)
        <label class="form-label fw-medium" for="{{ $id }}">{{ $label }}</label>
    @endif

    <input id="{{ $id }}" type="{{ $type }}" wire:model="{{ $name }}"
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           {{ $attributes->class(['form-control rounded-3', 'is-invalid' => $errors->has($name)]) }}>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

{{--
    Pie estándar de un modal de formulario: Cancelar y Guardar.

    Cancelar cierra el modal desde Alpine (o llama a `cancel` si se indica un método);
    Guardar llama a `submit`, se bloquea y muestra el indicador solo mientras esa
    acción (o `target`) está en curso.
--}}
@props([
    'submit' => 'save',            // Método Livewire de guardado.
    'submitLabel' => 'Guardar',
    'submitIcon' => 'bi-check2',
    'target' => null,              // wire:target del indicador; por defecto el método de guardado.
    'cancel' => null,              // Método Livewire para cancelar; null = cerrar con Alpine.
    'cancelLabel' => 'Cancelar',
])

@php $target = $target ?? $submit; @endphp

<button type="button" class="btn btn-light"
        @if ($cancel) wire:click="{{ $cancel }}" @else x-on:click="open = false" @endif>
    {{ $cancelLabel }}
</button>
{{ $slot }}
<button type="button" wire:click="{{ $submit }}" wire:loading.attr="disabled" wire:target="{{ $target }}" class="btn btn-primary app-modal-submit">
    <i class="bi {{ $submitIcon }}" wire:loading.remove wire:target="{{ $target }}" aria-hidden="true"></i>
    <span class="spinner-border spinner-border-sm" wire:loading wire:target="{{ $target }}" aria-hidden="true"></span>
    {{ $submitLabel }}
</button>

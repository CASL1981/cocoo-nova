{{-- Par etiqueta/valor de una sección del encabezado; sin valor muestra un guion. --}}
@props([
    'label',
    'value' => null, // Texto del valor; si se omite se usa el slot.
])

<div {{ $attributes->class('document-field') }}>
    <dt>{{ $label }}</dt>
    <dd>
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @elseif (filled($value))
            {{ $value }}
        @else
            <span class="text-body-secondary">-</span>
        @endif
    </dd>
</div>

{{--
    Tooltip Bootstrap sobre un botón o enlace (directiva Alpine x-tooltip, ver resources/js/app.js).
    Envuelve el elemento para que el tooltip también aparezca cuando está deshabilitado:
    un botón disabled no recibe eventos del ratón.
--}}
@props([
    'text',               // Texto que describe la operación del botón.
    'placement' => 'top', // top, bottom, left o right.
])

<span {{ $attributes->class('d-inline-block') }} x-data x-tooltip.{{ $placement }}="@js($text)">{{ $slot }}</span>

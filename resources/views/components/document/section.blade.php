{{-- Bloque de datos del encabezado de un documento; los campos van como document.field. --}}
@props([
    'title',
    'icon' => null, // Clase de Bootstrap Icons (ej. bi-person).
])

<section {{ $attributes->class('document-section') }}>
    <h6 class="document-section-title">
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $title }}
    </h6>
    <dl class="document-fields">
        {{ $slot }}
    </dl>
</section>

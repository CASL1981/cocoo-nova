{{--
    Sección con título dentro de un formulario largo. Los campos van en columnas
    (col-md-*) directamente en el slot: la sección ya aporta la fila con su espaciado.
--}}
@props([
    'title',
    'icon' => null,         // Clase de Bootstrap Icons (ej. bi-person).
    'description' => null,  // Texto breve junto al título.
])

<section {{ $attributes->class('form-section') }}>
    <div class="form-section-header">
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        <h6 class="form-section-title">{{ $title }}</h6>
        @if ($description)
            <span class="form-section-description">{{ $description }}</span>
        @endif
    </div>
    <div class="row g-3">
        {{ $slot }}
    </div>
</section>

{{--
    Encabezado de un documento con líneas (pendientes, solicitudes, órdenes): título,
    estado, botón Volver y secciones de datos. Solo presentación: los datos y las
    acciones los decide la pantalla que lo usa.

    Slots:
    - default: secciones de datos (document.section con document.field).
    - actions: (opcional) botones propios junto a Volver.
--}}
@props([
    'title',
    'icon' => 'bi-file-earmark-text', // Clase de Bootstrap Icons.
    'subtitle' => null,               // Texto breve bajo el título (ej. fecha de creación).
    'status' => null,                 // Etiqueta del estado.
    'statusClass' => 'bg-secondary',  // Clase del badge del estado.
    'back' => null,                   // URL del botón Volver.
])

<div {{ $attributes->class('card document-header') }}>
    <div class="card-body">
        <div class="document-header-top">
            <div class="document-heading">
                <span class="document-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                <div class="document-heading-text">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h4 class="document-title">{{ $title }}</h4>
                        @if ($status)
                            <span class="badge {{ $statusClass }}">{{ $status }}</span>
                        @endif
                    </div>
                    @if ($subtitle)
                        <p class="document-subtitle">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <div class="document-header-actions">
                {{ $actions ?? '' }}
                @if ($back)
                    <a href="{{ $back }}" wire:navigate class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver
                    </a>
                @endif
            </div>
        </div>

        @if ($slot->isNotEmpty())
            <div class="document-sections">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

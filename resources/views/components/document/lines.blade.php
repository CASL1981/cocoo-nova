{{--
    Tarjeta de las líneas de un documento: título, acciones, panel de captura y tabla.
    Solo presentación: la captura y la tabla las aporta la pantalla que lo usa.

    Slots:
    - actions: (opcional) botones del encabezado de la tarjeta (ej. importar).
    - entry:   (opcional) formulario de captura de líneas, dentro de un panel resaltado.
    - notice:  (opcional) aviso cuando no se pueden capturar líneas.
    - default: tabla de líneas (envuelta en .document-lines-table; fila vacía con document.empty-row).
--}}
@props([
    'title' => 'Ítems',
    'icon' => 'bi-list-ul',
    'entryTitle' => 'Agregar ítem',
    'entryIcon' => 'bi-plus-circle',
    'editing' => false, // Resalta el panel de captura mientras se edita una línea.
])

<div {{ $attributes->class('card document-lines') }}>
    <div class="card-header document-lines-header">
        <h5 class="document-lines-title">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
            {{ $title }}
        </h5>
        @isset($actions)
            <div class="d-flex flex-wrap align-items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    <div class="card-body">
        @isset($entry)
            <div @class(['document-entry', 'document-entry-editing' => $editing])>
                <div class="document-entry-title">
                    <i class="bi {{ $entryIcon }}" aria-hidden="true"></i>
                    {{ $entryTitle }}
                </div>
                {{ $entry }}
            </div>
        @endisset

        {{ $notice ?? '' }}

        {{ $slot }}
    </div>
</div>

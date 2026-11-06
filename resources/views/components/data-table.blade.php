{{-- resources/views/components/data-table.blade.php --}}
{{--
    Estructura reutilizable para cualquier listado paginado de la app:
    barra de herramientas a todo el ancho, tabla con scroll y pie con los
    controles del listado + resumen y paginación.

    Es deliberadamente "tonto": no sabe nada de filtros ni acciones — lo
    decide quien lo usa a través de los slots y props.

    Props:
    - paginator:       paginador del listado.
    - perPageOptions:  (opcional) tamaños de página; muestra "Mostrar N registros" en el pie.
    - columns:         (opcional) columns() del componente; con el trait HasColumnVisibility
                       muestra el botón de configuración de columnas en el pie.
    - hiddenColumns:   activeHiddenColumns() del componente.
    - leadingColumns:  columnas antes de las de columns() (1 = checkbox de selección).

    Slots:
    - toolbar-left:  información o controles propios del listado (ej. avisos).
    - toolbar-right: normalmente <x-table.actions>.
    - before-table:  contenido entre la barra y la tabla (ej. <x-table.selection-banner>).
    - head / filters / slot (default): encabezados, fila de filtros y filas del <tbody>.
--}}
@props([
    'paginator',
    'perPageOptions' => null,
    'columns' => null,
    'hiddenColumns' => [],
    'leadingColumns' => 1,
])

@php
    // Identificador estable por paginador para acotar el CSS de columnas a esta tabla.
    $tableId = 'data-table-'.$paginator->getPageName();
    $columnKeys = is_array($columns) ? array_keys($columns) : [];

    // Ancho fijo por columna ('width' en columns()): el texto que no cabe termina en "…"
    // y se lee completo en el tooltip global de celdas recortadas (resources/js/app.js).
    $widths = ['xs' => '5rem', 'sm' => '7rem', 'md' => '10rem', 'lg' => '14rem', 'xl' => '20rem'];
    $sizedColumns = collect($columns ?? [])
        ->map(fn (array $meta) => $widths[$meta['width'] ?? ''] ?? null)
        ->filter();
@endphp

@if (($hiddenColumns !== [] || $sizedColumns->isNotEmpty()) && $columnKeys !== [])
    {{-- Solo visual: el mismo índice aplica a encabezado, filtros y celdas (la fila "Sin resultados" usa colspan). --}}
    <style>
        @foreach ($columnKeys as $index => $key)
            @if (in_array($key, $hiddenColumns, true))
                #{{ $tableId }} tr > :nth-child({{ $leadingColumns + $index + 1 }}):not([colspan]) { display: none; }
            @elseif ($sizedColumns->has($key))
                #{{ $tableId }} tr > :nth-child({{ $leadingColumns + $index + 1 }}):not([colspan]) {
                    width: {{ $sizedColumns[$key] }}; min-width: {{ $sizedColumns[$key] }}; max-width: {{ $sizedColumns[$key] }};
                    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                }
            @endif
        @endforeach
    </style>
@endif

@if (isset($toolbarLeft) && $toolbarLeft->hasActualContent() || isset($toolbarRight) && $toolbarRight->hasActualContent())
    <div class="table-toolbar">
        @if (isset($toolbarLeft) && $toolbarLeft->hasActualContent())
            <div class="table-toolbar-start">{{ $toolbarLeft }}</div>
        @endif
        <div class="table-toolbar-end">{{ $toolbarRight ?? '' }}</div>
    </div>
@endif

{{ $beforeTable ?? '' }}

<div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50">
    <table class="table table-striped align-middle" id="{{ $tableId }}">
        <thead>
            <tr>{{ $head }}</tr>
            @isset($filters)
                <tr>{{ $filters }}</tr>
            @endisset
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>

<x-table.pagination :paginator="$paginator">
    @if ($perPageOptions || $columnKeys !== [])
        <x-slot:start>
            @if ($perPageOptions)
                <x-table.per-page :options="$perPageOptions" />
            @endif
            @if ($columnKeys !== [])
                <x-table.columns-config :columns="$columns" :hidden="$hiddenColumns" />
            @endif
        </x-slot:start>
    @endif
</x-table.pagination>

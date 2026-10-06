{{-- resources/views/components/data-table.blade.php --}}
{{--
    Estructura reutilizable para cualquier listado paginado de la app:
    barra de herramientas (izquierda/derecha), tabla con scroll horizontal,
    y pie con el resumen + enlaces de paginación.

    Es deliberadamente "tonto": no sabe nada de columnas, filtros, ni
    acciones — todo eso lo decide quien lo usa a través de los slots.
    Así sirve igual para User, Role, ServicePoint, Product o cualquier
    módulo futuro, sin importar cuántas ni cuáles columnas tengan.

    Slots:
    - toolbar-left:  normalmente el selector "Mostrar N registros".
    - toolbar-right: buscador/filtros generales, botón "Limpiar", y los
                     botones de acción (Editar/Eliminar/Auditar/Exportar).
    - head:          contenido de la fila de encabezados (<th>...</th> cada una).
    - filters:       (opcional) fila de inputs de filtro por columna.
    - slot (default): filas <tr> del <tbody>.
--}}
@props(['paginator'])

<div class="row mb-3 align-items-center g-2">
    <div class="col-md-6 d-flex align-items-center gap-2 flex-wrap">
        {{ $toolbarLeft ?? '' }}
    </div>
    <div class="col-md-6 text-md-end d-flex justify-content-md-end gap-2 flex-wrap">
        {{ $toolbarRight ?? '' }}
    </div>
</div>

<div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50">
    <table class="table table-striped align-middle">
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

<div class="d-flex justify-content-between align-items-center mt-3">
    <span>Mostrando {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }} registros</span>
    {{ $paginator->links() }}
</div>

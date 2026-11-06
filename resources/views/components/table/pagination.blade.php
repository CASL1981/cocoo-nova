{{--
    Pie de un listado paginado: a la izquierda los controles del listado (slot "start":
    registros por página, columnas) y a la derecha el resumen "Mostrando X–Y de Z" con el
    paginador compacto (resources/views/vendor/livewire/bootstrap.blade.php).
--}}
@props(['paginator'])

<div class="table-footer">
    @isset($start)
        <div class="table-footer-start">{{ $start }}</div>
    @endisset
    <div class="table-footer-end">
        <span class="table-footer-count" wire:loading.class="opacity-50">
            Mostrando {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} de {{ number_format($paginator->total()) }} registros
        </span>
        <span wire:loading.delay class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Cargando"></span>
        {{ $paginator->links() }}
    </div>
</div>

{{--
    Aviso de selección tipo Gmail (trait HasRowSelection): con la página completa marcada
    ofrece ampliar la selección a todos los registros que coinciden con los filtros.
--}}
@props([
    'pageIds' => [],       // IDs de la página visible.
    'selected' => [],      // $selected del componente.
    'allMatching' => false, // $selectAllMatching del componente.
    'matchingCount' => null, // $this->matchingSelectionCount($pageIds): null si no aplica.
])

@php
    $max = \App\Support\BulkSelection::MAX_MATCHING;
    $pageCount = count($pageIds);
@endphp

@if ($allMatching)
    <div class="alert alert-primary py-2 mb-2 small d-flex flex-wrap align-items-center gap-2">
        <span>
            Están seleccionados los <strong>{{ number_format(min((int) $matchingCount, $max)) }}</strong> registros que coinciden con los filtros.
        </span>
        <button type="button" wire:click="clearSelection" class="btn btn-link btn-sm p-0 align-baseline">Limpiar selección</button>
    </div>
@elseif ($matchingCount !== null && $matchingCount > $pageCount)
    <div class="alert alert-info py-2 mb-2 small d-flex flex-wrap align-items-center gap-2">
        <span>Se seleccionaron los <strong>{{ $pageCount }}</strong> registros de esta página.</span>
        @if ($matchingCount <= $max)
            <button type="button" wire:click="selectAllMatchingRows" class="btn btn-link btn-sm p-0 align-baseline fw-semibold">
                Seleccionar los {{ number_format($matchingCount) }} registros que coinciden con los filtros
            </button>
        @else
            <span class="text-muted">Los filtros traen {{ number_format($matchingCount) }} registros (máximo {{ number_format($max) }} por acción): afine los filtros para seleccionarlos todos.</span>
        @endif
    </div>
@endif

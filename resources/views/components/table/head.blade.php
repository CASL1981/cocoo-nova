{{-- Encabezados ordenables y checkbox de "seleccionar página"; null en pageIds omite la selección. --}}
@props(['columns', 'sortBy', 'sortDirection', 'pageIds' => null, 'selected' => []])

@if (! is_null($pageIds))
    @php $allPageSelected = count($pageIds) > 0 && empty(array_diff($pageIds, $selected)); @endphp
    <th class="table-col-check">
        <input type="checkbox" wire:key="select-all-{{ $allPageSelected ? 'checked' : 'unchecked' }}"
               class="form-check-input" @checked($allPageSelected) aria-label="Seleccionar los registros de esta página"
               wire:click="toggleSelectAll({{ json_encode($pageIds) }})">
    </th>
@endif
@foreach ($columns as $col => $meta)
    @php $sorted = $sortBy === $col; @endphp
    <th role="button" wire:click="sort('{{ $col }}')" @class(['text-nowrap table-sortable', 'is-sorted' => $sorted])
        aria-sort="{{ $sorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
        {{ $meta['label'] }}
        <i @class([
            'bi table-sort-icon',
            'bi-caret-up-fill' => $sorted && $sortDirection === 'asc',
            'bi-caret-down-fill' => $sorted && $sortDirection !== 'asc',
            'bi-arrow-down-up' => ! $sorted,
        ]) aria-hidden="true"></i>
    </th>
@endforeach

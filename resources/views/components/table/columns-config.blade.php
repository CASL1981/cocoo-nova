{{-- Botón y panel para mostrar/ocultar columnas; el componente Livewire usa el trait HasColumnVisibility. --}}
@props([
    'columns',          // columns() del componente.
    'hidden' => [],     // activeHiddenColumns() del componente.
])

{{-- Desplegable con Alpine: conserva su estado abierto entre actualizaciones de Livewire. --}}
<div class="dropdown dropup" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <x-tooltip text="Configurar columnas">
        <button type="button" class="btn btn-sm btn-outline-secondary btn-table-icon position-relative" x-on:click="open = ! open"
                :aria-expanded="open" aria-haspopup="true" aria-label="Configurar columnas">
            <i class="bi bi-layout-three-columns"></i>
            @if ($hidden !== [])
                <span class="table-columns-badge">{{ count($hidden) }}</span>
            @endif
        </button>
    </x-tooltip>

    <div class="dropdown-menu table-columns-menu" :class="{ show: open }">
        <div class="d-flex justify-content-between align-items-center px-3 pb-2 mb-1 border-bottom">
            <span class="fw-semibold">Columnas visibles</span>
            <button type="button" wire:click="showAllColumns" class="btn btn-link btn-sm p-0" @disabled($hidden === [])>Mostrar todas</button>
        </div>
        <div class="table-columns-list">
            @foreach ($columns as $key => $meta)
                @php $locked = ($meta['hideable'] ?? true) === false; @endphp
                <label class="dropdown-item d-flex align-items-center gap-2" wire:key="column-toggle-{{ $key }}-{{ in_array($key, $hidden, true) ? '0' : '1' }}">
                    <input type="checkbox" class="form-check-input m-0" @checked(! in_array($key, $hidden, true)) @disabled($locked)
                           wire:click="toggleColumn('{{ $key }}')">
                    <span>{{ $meta['label'] }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

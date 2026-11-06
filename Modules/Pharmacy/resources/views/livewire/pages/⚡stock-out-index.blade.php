<?php

use App\Enums\Status;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\DuplicatesRows;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\ImportsRows;
use App\Contracts\RowImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharmacy\App\Imports\StockOutImporter;
use Modules\Pharmacy\App\Models\StockOut;
use Illuminate\Database\Eloquent\Model;

/**
 * Página de agotados: catálogo mantenido por Compras con circulares de desabastecimiento.
 */
new #[Layout('layouts.app')] #[Title('Agotados')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions, DuplicatesRows,
        HasColumnFilters, HasColumnVisibility, ExportsRows, ImportsRows, Notifies;

    #[Session(key: 'stock-outs.index.filterGenericCode-{authId}')] public string $filterGenericCode = '';
    #[Session(key: 'stock-outs.index.filterGenericName-{authId}')] public string $filterGenericName = '';
    #[Session(key: 'stock-outs.index.filterProductName-{authId}')] public string $filterProductName = '';
    #[Session(key: 'stock-outs.index.filterBrand-{authId}')] public string $filterBrand = '';
    #[Session(key: 'stock-outs.index.filterClassification-{authId}')] public string $filterClassification = '';
    #[Session(key: 'stock-outs.index.filterCircularNumber-{authId}')] public string $filterCircularNumber = '';
    #[Session(key: 'stock-outs.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'stock-outs.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'stock-outs.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'stock-outs.index.sortBy-{authId}')] public string $sortBy = 'generic_code';
    #[Session(key: 'stock-outs.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    protected function rowModelClass(): string { return StockOut::class; }
    protected function computedRowsProperty(): string { return 'stockOuts'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', StockOut::class);
    }

    /**
     * Consulta base del listado y la exportación, incluidos los eliminados.
     * Los filtros de texto usan coincidencia parcial; los de selección, exacta.
     */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(StockOut::query()->withTrashed());
    }

    #[Computed]
    public function stockOuts(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('stock-out-saved')]
    public function refreshStockOuts(): void
    {
        unset($this->stockOuts);
    }

    protected function importer(): RowImporter
    {
        return new StockOutImporter;
    }

    protected function afterImport(): void
    {
        $this->clearSelection();
        unset($this->stockOuts);
    }

    protected function exportFileName(): string { return 'agotados'; }

    protected function exportHeadings(): array
    {
        return [...array_column($this->columns(), 'label'), 'Eliminado'];
    }

    /** @param StockOut $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->generic_code, $row->generic_name, $row->product_name, $row->brand, $row->classification,
            $row->circular_number, $row->circular_date->format('Y-m-d'), $row->status->label(),
            $row->created_at->format('Y-m-d'), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y exportación de cada columna. */
    protected function columns(): array
    {
        return [
            'generic_code' => ['label' => 'Cód. genérico', 'width' => 'sm', 'filter' => 'filterGenericCode'],
            'generic_name' => ['label' => 'Genérico', 'width' => 'lg', 'filter' => 'filterGenericName'],
            'product_name' => ['label' => 'Producto', 'width' => 'xl', 'filter' => 'filterProductName'],
            'brand' => ['label' => 'Marca', 'width' => 'md', 'filter' => 'filterBrand'],
            'classification' => ['label' => 'Clasificación', 'width' => 'md', 'filter' => 'filterClassification'],
            'circular_number' => ['label' => 'Circular', 'width' => 'sm', 'filter' => 'filterCircularNumber'],
            'circular_date' => ['label' => 'Fecha circular', 'filter' => null],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => Status::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Agotados</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->stockOuts" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                {{-- <x-slot:toolbar-left>
                    <label class="d-inline-flex align-items-center gap-2">Mostrar
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                            @foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                        </select>
                        registros
                    </label>
                </x-slot:toolbar-left> --}}

                {{-- <x-slot:toolbar-right>
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>

                    @can('stock-outs-update')
                        <button type="button" wire:click="$dispatch('open-stock-out-edit', { stockOutId: {{ $selected[0] ?? 'null' }} })"
                                @disabled(! $this->singleActiveSelected()) class="btn btn-sm btn-outline-primary">Editar</button>
                    @endcan
                    @can('stock-outs-delete')
                        <button type="button" x-on:click="confirmAction({ title: 'Eliminar agotados', text: '¿Eliminar {{ count($selected) }} agotado(s)?' }).then(() => $wire.deleteSelected()).catch(() => {})"
                                @disabled(count($selected) === 0) class="btn btn-sm btn-danger">Eliminar</button>
                    @endcan
                    @can('stock-outs-toggle')
                        <button type="button" wire:click="toggleSelected" @disabled(count($selected) === 0)
                                class="btn btn-sm btn-outline-warning"><i class="bi bi-toggle2-on"></i></button>
                    @endcan
                    @can('stock-outs-reverse')
                        <button type="button" wire:click="restoreSelected" @disabled(count($selected) === 0)
                                class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                    @endcan
                    @can('stock-outs-read')
                        <button type="button" wire:click="$dispatch('open-userstamps-audit', { modelClass: 'Modules\\Pharmacy\\App\\Models\\StockOut', modelId: {{ $selected[0] ?? 'null' }} })"
                                @disabled(count($selected) !== 1) class="btn btn-sm btn-outline-info"><i class="bi bi-clock-history"></i></button>
                    @endcan
                    @can('stock-outs-create')
                        <button type="button" wire:click="$dispatch('open-stock-out-create')" class="btn btn-primary btn-sm">Agregar</button>
                    @endcan
                    <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i></button>
                    <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv"></i></button>
                </x-slot:toolbar-right> --}}

                <x-slot:toolbar-right>
                    <x-table.actions module="stock-outs" :model-class="StockOut::class" :selected="$selected"
                                    :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                    create-event="open-stock-out-create" edit-event="open-stock-out-edit" edit-param="stockOutId" duplicable
                                    importable :import-errors="$importErrors" import-key-label="Código genérico" />
                </x-slot:toolbar-right>

                {{-- <x-slot:head>
                    <th style="width: 2rem;">
                        <input type="checkbox"
                            wire:key="select-all-{{ $allPageSelected ? 'checked' : 'unchecked' }}"
                            class="form-check-input" @checked($allPageSelected)
                            wire:click="toggleSelectAll({{ json_encode($pageIds) }})">
                    </th>
                    @foreach ($this->columns() as $col => $meta)
                        <th role="button" wire:click="sort('{{ $col }}')" class="text-nowrap">
                            {{ $meta['label'] }} @if ($sortBy === $col) {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                    @endforeach
                </x-slot:head> --}}

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->stockOuts->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->stockOuts->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                :page-ids="$this->stockOuts->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                {{-- <x-slot:filters>
                    <th></th>
                    @foreach ($this->columns() as $col => $meta)
                        <th>
                            @if (($meta['filter'] ?? null) === null)
                            @elseif (($meta['type'] ?? 'text') === 'select')
                                <x-forms.select wire:model.live="{{ $meta['filter'] }}" :options="$meta['options']" placeholder="Todos" />
                            @else
                                <input type="text" wire:model.live.debounce.300ms="{{ $meta['filter'] }}" class="form-control form-control-sm">
                            @endif
                        </th>
                    @endforeach
                </x-slot:filters> --}}

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                {{-- @forelse ($this->stockOuts as $stockOut)
                    <tr wire:key="stock-out-{{ $stockOut->id }}" class="{{ $this->rowStatusClass($stockOut) }}"> --}}
                @forelse ($this->stockOuts as $stockOut)
                    <tr wire:key="stock-out-{{ $stockOut->id }}" class="{{ $this->rowStatusClass($stockOut) }}">
                        <x-table.row-checkbox :id="$stockOut->id" :selected="$selected" />
                        <td>{{ $stockOut->generic_code }} @if ($stockOut->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>{{ $stockOut->generic_name }}</td>
                        <td>{{ $stockOut->product_name }}</td>
                        <td>{{ $stockOut->brand ?? '-' }}</td>
                        <td>{{ $stockOut->classification ?? '-' }}</td>
                        <td>{{ $stockOut->circular_number }}</td>
                        <td>{{ $stockOut->circular_date->format('Y/m/d') }}</td>
                        <td><span class="badge {{ $stockOut->status->badgeClass() }}">{{ $stockOut->status->label() }}</span></td>
                        <td>{{ $stockOut->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:pharmacy::stock-outs.stock-out-form-modal />
</div>

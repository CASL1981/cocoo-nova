<?php

use App\Exports\GenericExport;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasRowSelection;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pharmacy\App\Actions\AssignTransferDocument;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\PendingItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Asignación de TB (Farmacia → Gestión).
 *
 * Lista los ítems Cumplidos (por defecto los que aún no tienen TB). El funcionario
 * selecciona ítems de un mismo C.C., aunque sean de distintos pendientes, y les asigna
 * el número TB del ERP. Asignar: inventory-balances-process; cambiar o quitar un TB
 * ya asignado: inventory-balances-reverse. Reglas en AssignTransferDocument.
 */
new #[Layout('layouts.app')] #[Title('Asignación de TB')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasRowSelection, Notifies;

    /** Filtros que reinician la paginación y la selección al cambiar. */
    private const FILTERS = [
        'filterTb', 'filterDestination', 'filterPendingId', 'filterSearch', 'filterDocNumber',
        'filterSendFrom', 'filterSendTo', 'perPage',
    ];

    /** without = sin TB (por defecto), with = con TB, all = todos. */
    #[Session(key: 'inventory-balances.tb.filterTb-{authId}')] public string $filterTb = 'without';
    #[Session(key: 'inventory-balances.tb.filterDestination-{authId}')] public string $filterDestination = '';
    #[Session(key: 'inventory-balances.tb.filterPendingId-{authId}')] public string $filterPendingId = '';
    #[Session(key: 'inventory-balances.tb.filterSearch-{authId}')] public string $filterSearch = '';
    #[Session(key: 'inventory-balances.tb.filterDocNumber-{authId}')] public string $filterDocNumber = '';
    #[Session(key: 'inventory-balances.tb.filterSendFrom-{authId}')] public string $filterSendFrom = '';
    #[Session(key: 'inventory-balances.tb.filterSendTo-{authId}')] public string $filterSendTo = '';
    #[Session(key: 'inventory-balances.tb.perPage-{authId}')] public int $perPage = 25;
    #[Session(key: 'inventory-balances.tb.sortBy-{authId}')] public string $sortBy = 'destination_code';
    #[Session(key: 'inventory-balances.tb.sortDirection-{authId}')] public string $sortDirection = 'asc';

    /** Número TB a asignar a los ítems seleccionados. */
    public string $docNumber = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', InventoryBalance::class);
    }

    protected function sortableColumns(): array
    {
        return ['destination_code', 'pending_id', 'generic_name', 'quantity', 'send_quantity', 'date_send', 'number_day', 'doc_number'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->resetPage();
            $this->clearSelection();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
        $this->clearSelection();
    }

    /** Ítems Cumplidos con los filtros activos; la misma consulta alimenta la exportación. */
    protected function baseQuery(): Builder
    {
        $search = trim($this->filterSearch);

        return PendingItem::query()
            ->where('status', PendingItemStatus::Fulfilled)
            ->with(['pending:id,type', 'product:id,item'])
            ->when($this->filterTb === 'without', fn (Builder $q) => $q->whereNull('doc_number'))
            ->when($this->filterTb === 'with', fn (Builder $q) => $q->whereNotNull('doc_number'))
            ->when($this->filterDestination !== '', fn (Builder $q) => $q->where('destination_code', 'like', '%'.trim($this->filterDestination).'%'))
            ->when(ctype_digit(trim($this->filterPendingId)), fn (Builder $q) => $q->where('pending_id', (int) trim($this->filterPendingId)))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('generic_name', 'like', "%{$search}%")
                ->orWhere('generic_code', 'like', "%{$search}%")
                ->orWhere('product_name', 'like', "%{$search}%")))
            ->when($this->filterDocNumber !== '', fn (Builder $q) => $q->where('doc_number', 'like', '%'.trim($this->filterDocNumber).'%'))
            ->when($this->filterSendFrom !== '', fn (Builder $q) => $q->whereDate('date_send', '>=', $this->filterSendFrom))
            ->when($this->filterSendTo !== '', fn (Builder $q) => $q->whereDate('date_send', '<=', $this->filterSendTo));
    }

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        return $this->applySort($this->baseQuery())->orderBy('id')->paginate($perPage);
    }

    /**
     * C.C. de los ítems seleccionados (un TB debe tener uno solo).
     *
     * @return list<string>
     */
    #[Computed]
    public function selectedDestinations(): array
    {
        if ($this->selected === []) {
            return [];
        }

        return PendingItem::query()->whereKey($this->selectedIds())->distinct()->orderBy('destination_code')
            ->pluck('destination_code')->all();
    }

    public function updatedSelected(): void
    {
        unset($this->selectedDestinations);
        $this->resetValidation('docNumber');
    }

    /** Asigna el TB digitado a los ítems seleccionados. */
    public function assign(AssignTransferDocument $action): void
    {
        Gate::authorize('process', InventoryBalance::class);

        $this->validate(
            ['docNumber' => ['required', 'regex:/^\d{1,50}$/']],
            ['docNumber.regex' => 'El número TB debe contener solo números (máximo 50 dígitos).'],
            ['docNumber' => 'número TB'],
        );

        $count = $action->assign($this->selectedIds(), $this->docNumber, Gate::allows('reverse', InventoryBalance::class));

        $this->toastSuccess("TB {$this->docNumber} asignado a {$count} ítem(s).");
        $this->afterChange();
    }

    /** Quita el TB de los ítems seleccionados (corrección; requiere reversar). */
    public function remove(AssignTransferDocument $action): void
    {
        Gate::authorize('reverse', InventoryBalance::class);

        $count = $action->remove($this->selectedIds());

        $this->toastSuccess("TB quitado de {$count} ítem(s).");
        $this->afterChange();
    }

    public function export(string $format): BinaryFileResponse
    {
        Gate::authorize('viewAny', InventoryBalance::class);
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery()),
            headings: ['C.C.', 'N° pendiente', 'Tipo', 'Ítem', 'Genérico', 'Cód. genérico', 'Cantidad', 'Enviado', 'Fecha envío', 'Días', 'TB'],
            mapRow: fn (PendingItem $row) => [
                $row->destination_code, $row->pending_id, $row->pending?->type->value, $row->product?->item,
                $row->generic_name, $row->generic_code, $row->quantity, $row->send_quantity,
                $row->date_send?->format('Y-m-d'), $row->number_day, $row->doc_number,
            ],
        );

        return Excel::download($export, 'asignacion-tb-'.now()->format('Y-m-d_His').'.'.$format);
    }

    public function sortIndicator(string $column): string
    {
        return $this->sortBy === $column ? ($this->sortDirection === 'asc' ? ' ▲' : ' ▼') : '';
    }

    private function afterChange(): void
    {
        $this->reset('docNumber');
        $this->resetValidation();
        $this->clearSelection();
        unset($this->items, $this->selectedDestinations);
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Asignación de TB</h4></div>

        <div class="card-body">
            {{-- Filtros --}}
            <div class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1" for="tb-filter">TB</label>
                    <select id="tb-filter" wire:model.live="filterTb" class="form-select form-select-sm">
                        <option value="without">Sin TB</option>
                        <option value="with">Con TB</option>
                        <option value="all">Todos</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label class="form-label small mb-1" for="tb-destination">C.C.</label>
                    <input id="tb-destination" type="text" wire:model.live.debounce.300ms="filterDestination" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label class="form-label small mb-1" for="tb-pending">N° pend.</label>
                    <input id="tb-pending" type="text" wire:model.live.debounce.300ms="filterPendingId" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-3">
                    <label class="form-label small mb-1" for="tb-search">Genérico / producto</label>
                    <input id="tb-search" type="search" wire:model.live.debounce.300ms="filterSearch" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label class="form-label small mb-1" for="tb-doc">N° TB</label>
                    <input id="tb-doc" type="text" wire:model.live.debounce.300ms="filterDocNumber" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1" for="tb-from">Envío desde</label>
                    <input id="tb-from" type="date" wire:model.live="filterSendFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1" for="tb-to">Envío hasta</label>
                    <input id="tb-to" type="date" wire:model.live="filterSendTo" class="form-control form-control-sm">
                </div>
            </div>

            {{-- Barra de acciones --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <label class="d-inline-flex align-items-center gap-2 small">Mostrar
                    <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                        @foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                    </select>
                </label>

                <div class="d-flex align-items-start flex-wrap gap-2">
                    @can('process', \Modules\Pharmacy\App\Models\InventoryBalance::class)
                        <div>
                            <div class="input-group input-group-sm" style="max-width: 20rem;">
                                <input type="text" inputmode="numeric" wire:model="docNumber" wire:keydown.enter="assign"
                                       @class(['form-control', 'is-invalid' => $errors->has('docNumber')])
                                       placeholder="N° TB" aria-label="Número TB" @disabled(count($selected) === 0)>
                                <x-tooltip text="Asignar el TB a los ítems seleccionados (mismo C.C.)">
                                    <button type="button" wire:click="assign" @disabled(count($selected) === 0 || count($this->selectedDestinations) > 1)
                                            class="btn btn-primary btn-sm" aria-label="Asignar TB" wire:loading.attr="disabled" wire:target="assign">
                                        Asignar TB
                                    </button>
                                </x-tooltip>
                            </div>
                            @error('docNumber')<div class="invalid-feedback d-block small">{{ $message }}</div>@enderror
                            @if (count($selected) > 0)
                                <div @class(['small mt-1', 'text-danger' => count($this->selectedDestinations) > 1, 'text-muted' => count($this->selectedDestinations) <= 1])>
                                    {{ $this->selectedCount() }} seleccionado(s) · C.C. {{ implode(', ', $this->selectedDestinations) }}
                                    @if (count($this->selectedDestinations) > 1) — un TB debe ser de un solo C.C. @endif
                                </div>
                            @endif
                        </div>
                    @endcan
                    @can('reverse', \Modules\Pharmacy\App\Models\InventoryBalance::class)
                        <x-tooltip text="Quitar el TB de los ítems seleccionados">
                            <button type="button" @disabled(count($selected) === 0) class="btn btn-sm btn-outline-danger" aria-label="Quitar TB"
                                    x-on:click="confirmAction({ title: 'Quitar TB', text: '¿Quitar el TB de {{ $this->selectedCount() }} ítem(s)?' }).then(() => $wire.remove()).catch(() => {})">
                                <i class="bi bi-eraser"></i>
                            </button>
                        </x-tooltip>
                    @endcan
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>
                    <x-tooltip text="Exportar a Excel">
                        <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                    </x-tooltip>
                    <x-tooltip text="Exportar a CSV">
                        <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                    </x-tooltip>
                </div>
            </div>

            @php $pageIds = $this->items->pluck('id')->all(); $allPageSelected = count($pageIds) > 0 && empty(array_diff($pageIds, $selected)); @endphp
            <x-table.selection-banner :page-ids="$pageIds" :selected="$selected" :all-matching="$selectAllMatching"
                                      :matching-count="$this->matchingSelectionCount($pageIds)" />
            <div class="table-fit">
                <div class="table-scroll-10 border rounded" wire:loading.class="opacity-50">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="col-fit">
                                    <input type="checkbox" wire:key="select-all-{{ $allPageSelected ? 'checked' : 'unchecked' }}" class="form-check-input"
                                           @checked($allPageSelected) wire:click="toggleSelectAll({{ json_encode($pageIds) }})" aria-label="Seleccionar página">
                                </th>
                                <th role="button" class="col-fit" wire:click="sort('destination_code')">C.C.{{ $this->sortIndicator('destination_code') }}</th>
                                <th role="button" class="col-fit" wire:click="sort('pending_id')">N° pend.{{ $this->sortIndicator('pending_id') }}</th>
                                <th class="col-fit">Tipo</th>
                                <th class="col-fit">Ítem</th>
                                <th role="button" wire:click="sort('generic_name')">Genérico{{ $this->sortIndicator('generic_name') }}</th>
                                <th role="button" class="col-fit text-end" wire:click="sort('quantity')">Cant.{{ $this->sortIndicator('quantity') }}</th>
                                <th role="button" class="col-fit text-end" wire:click="sort('send_quantity')">Enviado{{ $this->sortIndicator('send_quantity') }}</th>
                                <th role="button" class="col-fit" wire:click="sort('date_send')">Fecha envío{{ $this->sortIndicator('date_send') }}</th>
                                <th role="button" class="col-fit text-end" wire:click="sort('number_day')">Días{{ $this->sortIndicator('number_day') }}</th>
                                <th role="button" class="col-fit" wire:click="sort('doc_number')">TB{{ $this->sortIndicator('doc_number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->items as $item)
                                <tr wire:key="tb-item-{{ $item->id }}">
                                    <x-table.row-checkbox :id="$item->id" :selected="$selected" />
                                    <td class="col-fit">{{ $item->destination_code }}</td>
                                    <td class="col-fit">{{ $item->pending_id }}</td>
                                    <td class="col-fit" title="{{ $item->pending?->type->label() }}">{{ $item->pending?->type->value }}</td>
                                    <td class="col-fit">{{ $item->product?->item }}</td>
                                    <td>{{ $item->generic_name }}</td>
                                    <td class="col-fit text-end">{{ number_format($item->quantity) }}</td>
                                    <td class="col-fit text-end">{{ number_format((int) $item->send_quantity) }}</td>
                                    <td class="col-fit">{{ $item->date_send?->format('Y/m/d') }}</td>
                                    <td class="col-fit text-end">{{ $item->number_day ?? '-' }}</td>
                                    <td class="col-fit fw-semibold">{{ $item->doc_number ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center text-muted py-4">No hay ítems Cumplidos con los filtros aplicados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-table.pagination :paginator="$this->items" />
        </div>
    </div>
</div>

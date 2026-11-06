<?php

use App\Exports\GenericExport;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasRowSelection;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pharmacy\App\Actions\AssignSentQuantity;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\PendingItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Asignación de stock de Oficina Principal (Farmacia → Gestión).
 *
 * Muestra los ítems Sin revisión, Pendiente o Parcial de pendientes Terminados con el
 * saldo de su código genérico en la carga de inventario vigente. Por cada fila se digita
 * la cantidad enviada (se suma a lo ya enviado); el estado lo calcula AssignSentQuantity.
 */
new #[Layout('layouts.app')] #[Title('Asignación de stock')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasRowSelection, Notifies;

    /** Filtros que reinician la paginación y la selección al cambiar. */
    private const FILTERS = ['search', 'filterDestination', 'filterStatus', 'filterDateFrom', 'filterDateTo', 'onlyWithStock', 'perPage'];

    #[Session(key: 'inventory-balances.assignment.search-{authId}')] public string $search = '';
    #[Session(key: 'inventory-balances.assignment.filterDestination-{authId}')] public string $filterDestination = '';
    #[Session(key: 'inventory-balances.assignment.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'inventory-balances.assignment.filterDateFrom-{authId}')] public string $filterDateFrom = '';
    #[Session(key: 'inventory-balances.assignment.filterDateTo-{authId}')] public string $filterDateTo = '';
    #[Session(key: 'inventory-balances.assignment.onlyWithStock-{authId}')] public bool $onlyWithStock = false;
    #[Session(key: 'inventory-balances.assignment.perPage-{authId}')] public int $perPage = 25;
    #[Session(key: 'inventory-balances.assignment.sortBy-{authId}')] public string $sortBy = 'created_at';
    #[Session(key: 'inventory-balances.assignment.sortDirection-{authId}')] public string $sortDirection = 'asc';

    /** Ítem en edición; solo lo asigna el servidor al pulsar el lápiz de la fila. */
    #[Locked]
    public ?int $editingId = null;

    /** Cantidad a enviar en esta asignación (se suma a lo ya enviado). */
    public string $sendQuantity = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', InventoryBalance::class);
    }

    protected function sortableColumns(): array
    {
        return ['created_at', 'status', 'pending_id', 'generic_name', 'brand', 'destination_code', 'quantity', 'send_quantity'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->resetPage();
            $this->clearSelection();
            $this->cancelEdit();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
        $this->clearSelection();
    }

    /** Ítems asignables con los filtros activos. */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return PendingItem::query()->assignable()
            ->with(['pending:id,type,status', 'product:id,item'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('generic_name', 'like', "%{$search}%")
                ->orWhere('product_name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('observations', 'like', "%{$search}%")
                ->when(ctype_digit($search), fn (Builder $q) => $q->orWhere('pending_id', (int) $search))))
            ->when($this->filterDestination !== '', fn (Builder $query) => $query->where('destination_code', 'like', "%{$this->filterDestination}%"))
            ->when($this->filterStatus !== '', fn (Builder $query) => $query->where('status', $this->filterStatus))
            ->when($this->filterDateFrom !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->filterDateTo))
            ->when($this->onlyWithStock, fn (Builder $query) => $query->whereIn('generic_code', InventoryBalance::genericCodesWithStock()));
    }

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        return $this->applySort($this->baseQuery())->orderBy('id')->paginate($perPage);
    }

    /**
     * Saldo por código genérico de los ítems de la página.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stocks(): array
    {
        return InventoryBalance::balancesByGeneric($this->items->pluck('generic_code')->all());
    }

    #[Computed]
    public function inventoryLoaded(): bool
    {
        return InventoryBalance::query()->exists();
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return collect(PendingItemStatus::assignable())->mapWithKeys(fn (PendingItemStatus $status) => [$status->value => $status->label()])->all();
    }

    public function editRow(int $itemId): void
    {
        Gate::authorize('process', InventoryBalance::class);

        $this->resetValidation();
        $this->editingId = PendingItem::query()->assignable()->findOrFail($itemId)->id;
        $this->sendQuantity = '';
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->sendQuantity = '';
        $this->resetValidation();
    }

    /**
     * Asignación automática (a voluntad del usuario, por fila): envía de una vez lo que
     * falta del ítem (solicitado − enviado) cuando el saldo del genérico es mayor que esa cantidad.
     * Sin campo ni confirmación; AssignSentQuantity vuelve a validar el saldo.
     */
    public function autoAssign(int $itemId, AssignSentQuantity $action): void
    {
        Gate::authorize('process', InventoryBalance::class);

        $item = PendingItem::query()->assignable()->find($itemId);

        if ($item === null) {
            $this->toastError('El ítem ya no está disponible para asignación.');

            return;
        }

        $missing = $item->quantity - ($item->send_quantity ?? 0);
        $stock = InventoryBalance::balancesByGeneric([$item->generic_code])[$item->generic_code] ?? 0;

        if ($missing < 1 || $stock <= $missing) {
            $this->toastError("Stock insuficiente para asignar automáticamente: faltan {$missing} y el saldo es {$stock}.");
            unset($this->items, $this->stocks);

            return;
        }

        try {
            $item = $action->assign($item, $missing);
        } catch (ValidationException $exception) {
            $this->toastError(collect($exception->errors())->flatten()->first() ?? 'No se pudo asignar el ítem.');
            unset($this->items, $this->stocks);

            return;
        }

        if ($this->editingId === $itemId) {
            $this->cancelEdit();
        }

        $this->toastSuccess("Asignado automáticamente: {$missing} unidad(es) del ítem {$item->generic_name} (Cumplido).");
        unset($this->items, $this->stocks);
    }

    /** Suma la cantidad digitada a lo enviado del ítem en edición. */
    public function saveRow(AssignSentQuantity $action): void
    {
        Gate::authorize('process', InventoryBalance::class);

        $this->validate(
            ['sendQuantity' => ['required', 'integer', 'min:1', 'max:9999999']],
            [],
            ['sendQuantity' => 'cantidad enviada'],
        );

        $item = PendingItem::query()->findOrFail($this->editingId);
        $item = $action->assign($item, (int) $this->sendQuantity);

        $this->toastSuccess("Ítem {$item->status->label()}: enviado {$item->send_quantity} de {$item->quantity}.");
        $this->cancelEdit();
        unset($this->items, $this->stocks);
    }

    /** Pe: marca como Pendiente los ítems seleccionados sin tocar lo ya enviado. */
    public function markPendingSelected(AssignSentQuantity $action): void
    {
        Gate::authorize('process', InventoryBalance::class);

        $count = $skipped = 0;
        foreach (PendingItem::query()->whereKey($this->selectedIds())->get() as $item) {
            try {
                $action->markPending($item);
                $count++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        $this->clearSelection();
        $this->cancelEdit();
        unset($this->items);

        $this->toastSuccess("{$count} ítem(s) marcado(s) como Pendiente.");
        if ($skipped > 0) {
            $this->toastError("{$skipped} ítem(s) omitido(s): ya no están disponibles para asignación.");
        }
    }

    /**
     * Exporta los ítems con los filtros y el orden de la pantalla, incluido el stock
     * (saldo del genérico) para revisar la asignación fuera del sistema.
     */
    public function export(string $format): BinaryFileResponse
    {
        Gate::authorize('viewAny', InventoryBalance::class);
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $stocks = InventoryBalance::balancesByGeneric($this->baseQuery()->distinct()->pluck('generic_code')->all());

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery())->orderBy('id'),
            headings: [
                'Estado', 'Fecha', 'N° pendiente', 'Tipo', 'Ítem', 'Descripción', 'Cód. genérico', 'Marca', 'C.C.',
                'Cantidad', 'Stock', 'Enviado', 'Falta', 'Orden', 'Observaciones',
            ],
            mapRow: fn (PendingItem $row) => [
                $row->status->label(), $row->created_at->format('Y-m-d'), $row->pending_id, $row->pending?->type->value,
                $row->product?->item, $row->generic_name, $row->generic_code, $row->brand, $row->destination_code,
                $row->quantity, $stocks[$row->generic_code] ?? 0, $row->send_quantity ?? 0,
                $row->quantity - ($row->send_quantity ?? 0), $row->doc_number, $row->observations,
            ],
        );

        return Excel::download($export, 'asignacion-stock-'.now()->format('Y-m-d_His').'.'.$format);
    }

    /** Encabezado ordenable de la tabla. */
    public function sortIndicator(string $column): string
    {
        return $this->sortBy === $column ? ($this->sortDirection === 'asc' ? ' ▲' : ' ▼') : '';
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Asignación de stock</h4></div>

        <div class="card-body">
            @unless ($this->inventoryLoaded)
                <div class="alert alert-warning py-2">No hay inventario cargado: el stock aparece en cero y no se puede asignar.</div>
            @endunless

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mb-3">
                <div class="col-lg-3">
                    <label class="form-label small mb-1" for="assignment-search">Buscar</label>
                    <input id="assignment-search" type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                           placeholder="Producto, marca, N° pendiente, observaciones">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small mb-1" for="assignment-destination">C.C.</label>
                    <input id="assignment-destination" type="text" wire:model.live.debounce.300ms="filterDestination" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1" for="assignment-status">Estado</label>
                    <select id="assignment-status" wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($this->statusOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1" for="assignment-from">Desde</label>
                    <input id="assignment-from" type="date" wire:model.live="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1" for="assignment-to">Hasta</label>
                    <input id="assignment-to" type="date" wire:model.live="filterDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 d-flex align-items-center gap-2">
                    <div class="form-check form-switch mb-1">
                        <input id="assignment-stock" type="checkbox" wire:model.live="onlyWithStock" class="form-check-input" role="switch">
                        <label class="form-check-label small" for="assignment-stock">Solo con stock</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <label class="d-inline-flex align-items-center gap-2 small">Mostrar
                    <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                        @foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                    </select>
                </label>
                <div class="d-flex gap-2">
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>
                    <x-tooltip text="Exportar a Excel (con los filtros aplicados)">
                        <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                    </x-tooltip>
                    <x-tooltip text="Exportar a CSV (con los filtros aplicados)">
                        <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                    </x-tooltip>
                    @can('process', \Modules\Pharmacy\App\Models\InventoryBalance::class)
                        <x-tooltip text="Marcar como Pendiente los ítems seleccionados">
                            <button type="button" @disabled(count($selected) === 0) class="btn btn-sm btn-outline-warning" aria-label="Marcar Pendiente"
                                    x-on:click="confirmAction({ title: 'Marcar Pendiente', text: '¿Marcar {{ $this->selectedCount() }} ítem(s) como Pendiente?' }).then(() => $wire.markPendingSelected()).catch(() => {})">
                                Pe
                            </button>
                        </x-tooltip>
                    @endcan
                </div>
            </div>

            @php $pageIds = $this->items->pluck('id')->all(); $allPageSelected = count($pageIds) > 0 && empty(array_diff($pageIds, $selected)); @endphp
            <x-table.selection-banner :page-ids="$pageIds" :selected="$selected" :all-matching="$selectAllMatching"
                                      :matching-count="$this->matchingSelectionCount($pageIds)" />
            <div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50" wire:target="saveRow,markPendingSelected,search,filterDestination,filterStatus,filterDateFrom,filterDateTo,onlyWithStock">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 2rem;">
                                <input type="checkbox" wire:key="select-all-{{ $allPageSelected ? 'checked' : 'unchecked' }}" class="form-check-input"
                                       @checked($allPageSelected) wire:click="toggleSelectAll({{ json_encode($pageIds) }})" aria-label="Seleccionar página">
                            </th>
                            <th>Acción</th>
                            <th role="button" class="text-nowrap" wire:click="sort('status')">Estado{{ $this->sortIndicator('status') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('created_at')">Fecha{{ $this->sortIndicator('created_at') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('pending_id')">N° pend.{{ $this->sortIndicator('pending_id') }}</th>
                            <th>Tipo</th>
                            <th>Ítem</th>
                            <th role="button" class="text-nowrap" wire:click="sort('generic_name')">Descripción{{ $this->sortIndicator('generic_name') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('brand')">Marca{{ $this->sortIndicator('brand') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('destination_code')">C.C.{{ $this->sortIndicator('destination_code') }}</th>
                            <th role="button" class="text-nowrap text-end" wire:click="sort('quantity')">Cant.{{ $this->sortIndicator('quantity') }}</th>
                            <th class="text-end">Stock</th>
                            <th role="button" class="text-nowrap text-end" wire:click="sort('send_quantity')">Enviado{{ $this->sortIndicator('send_quantity') }}</th>
                            <th>Orden</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->items as $item)
                            @php $stock = $this->stocks[$item->generic_code] ?? 0; $editing = $editingId === $item->id; @endphp
                            <tr wire:key="assignment-{{ $item->id }}" @class(['table-active' => $editing])>
                                <x-table.row-checkbox :id="$item->id" :selected="$selected" />
                                <td class="text-nowrap">
                                    @can('process', \Modules\Pharmacy\App\Models\InventoryBalance::class)
                                        @if ($editing)
                                            <x-tooltip text="Guardar enviado">
                                                <button type="button" wire:click="saveRow" class="btn btn-sm btn-outline-success" aria-label="Guardar enviado"
                                                        wire:loading.attr="disabled" wire:target="saveRow"><i class="bi bi-check-lg"></i></button>
                                            </x-tooltip>
                                            <x-tooltip text="Cancelar">
                                                <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-outline-danger" aria-label="Cancelar"><i class="bi bi-x-lg"></i></button>
                                            </x-tooltip>
                                        @else
                                            <x-tooltip text="Asignar cantidad enviada">
                                                <button type="button" wire:click="editRow({{ $item->id }})" class="btn btn-sm btn-outline-primary" aria-label="Asignar cantidad enviada">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </x-tooltip>
                                            {{-- Solo cuando el saldo del genérico es mayor que lo que falta enviar del ítem. --}}
                                            @php $missing = $item->quantity - ($item->send_quantity ?? 0); @endphp
                                            @if ($missing > 0 && $stock > $missing)
                                                <x-tooltip text="Asignar automáticamente lo que falta ({{ number_format($missing) }})">
                                                    <button type="button" wire:click="autoAssign({{ $item->id }})" class="btn btn-sm btn-outline-success"
                                                            aria-label="Asignar automáticamente {{ $missing }}" wire:loading.attr="disabled" wire:target="autoAssign">
                                                        <i class="bi bi-lightning-charge"></i>
                                                    </button>
                                                </x-tooltip>
                                            @endif
                                        @endif
                                    @endcan
                                </td>
                                <td><span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                                <td class="text-nowrap">{{ $item->created_at->format('Y/m/d') }}</td>
                                <td>{{ $item->pending_id }}</td>
                                <td title="{{ $item->pending?->type->label() }}">{{ $item->pending?->type->value }}</td>
                                <td>{{ $item->product?->item }}</td>
                                <td>{{ $item->generic_name }}</td>
                                <td>{{ $item->brand ?? '-' }}</td>
                                <td title="{{ $item->destination_code }}">{{ $item->destination_code }}</td>
                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                <td @class(['text-end fw-semibold', 'text-success' => $stock > 0, 'text-muted' => $stock <= 0])>{{ number_format($stock) }}</td>
                                <td class="text-end" style="min-width: 8rem;">
                                    @if ($editing)
                                        <input type="number" min="1" wire:model="sendQuantity" wire:keydown.enter="saveRow" wire:keydown.escape="cancelEdit"
                                               @class(['form-control form-control-sm text-end', 'is-invalid' => $errors->has('sendQuantity') || $errors->has('quantity')])
                                               placeholder="+ cant." aria-label="Cantidad a enviar" autofocus>
                                        @if ($item->send_quantity)<div class="small text-muted">Ya enviado: {{ $item->send_quantity }}</div>@endif
                                        @error('sendQuantity')<div class="invalid-feedback d-block text-start">{{ $message }}</div>@enderror
                                        @error('quantity')<div class="invalid-feedback d-block text-start">{{ $message }}</div>@enderror
                                    @else
                                        {{ $item->send_quantity ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $item->doc_number ?? '-' }}</td>
                                <td class="small">{{ $item->observations ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="text-center text-muted py-4">No hay ítems pendientes por asignar</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table.pagination :paginator="$this->items" />
        </div>
    </div>
</div>

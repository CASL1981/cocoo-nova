<?php

use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasRowSelection;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharmacy\App\Actions\AssignSentQuantity;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\PendingItem;

/**
 * Consulta de ítems de pendientes (Farmacia → Gestión).
 *
 * Todos los ítems, en cualquier estado y de todas las sedes, con filtros avanzados.
 * Desde aquí se corrige la cantidad enviada de un ítem (permiso inventory-balances-reverse),
 * porque permite encontrarlo sin conocer el número de pendiente.
 */
new #[Layout('layouts.app')] #[Title('Consulta de ítems')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasRowSelection, ExportsRows, Notifies;

    /** Filtros que reinician la paginación y la selección al cambiar. */
    private const FILTERS = [
        'filterRequestFrom', 'filterRequestTo', 'filterSendFrom', 'filterSendTo', 'filterStatuses',
        'filterPendingStatus', 'filterType', 'filterDestination', 'filterPendingId', 'filterItem',
        'filterGenericName', 'filterGenericCode', 'filterBrand', 'filterDocNumber', 'filterCircular', 'perPage',
    ];

    #[Session(key: 'pendings.items.filterRequestFrom-{authId}')] public string $filterRequestFrom = '';
    #[Session(key: 'pendings.items.filterRequestTo-{authId}')] public string $filterRequestTo = '';
    #[Session(key: 'pendings.items.filterSendFrom-{authId}')] public string $filterSendFrom = '';
    #[Session(key: 'pendings.items.filterSendTo-{authId}')] public string $filterSendTo = '';
    /** @var list<string> */
    #[Session(key: 'pendings.items.filterStatuses-{authId}')] public array $filterStatuses = [];
    #[Session(key: 'pendings.items.filterPendingStatus-{authId}')] public string $filterPendingStatus = '';
    #[Session(key: 'pendings.items.filterType-{authId}')] public string $filterType = '';
    #[Session(key: 'pendings.items.filterDestination-{authId}')] public string $filterDestination = '';
    #[Session(key: 'pendings.items.filterPendingId-{authId}')] public string $filterPendingId = '';
    #[Session(key: 'pendings.items.filterItem-{authId}')] public string $filterItem = '';
    #[Session(key: 'pendings.items.filterGenericName-{authId}')] public string $filterGenericName = '';
    #[Session(key: 'pendings.items.filterGenericCode-{authId}')] public string $filterGenericCode = '';
    #[Session(key: 'pendings.items.filterBrand-{authId}')] public string $filterBrand = '';
    #[Session(key: 'pendings.items.filterDocNumber-{authId}')] public string $filterDocNumber = '';
    #[Session(key: 'pendings.items.filterCircular-{authId}')] public string $filterCircular = '';
    #[Session(key: 'pendings.items.perPage-{authId}')] public int $perPage = 25;
    #[Session(key: 'pendings.items.sortBy-{authId}')] public string $sortBy = 'id';
    #[Session(key: 'pendings.items.sortDirection-{authId}')] public string $sortDirection = 'desc';

    /** Modal de corrección del enviado. */
    public bool $showCorrect = false;

    #[Locked]
    public ?int $correctingId = null;

    public string $correctTotal = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', PendingItem::class);
    }

    protected function rowModelClass(): string { return PendingItem::class; }

    protected function sortableColumns(): array
    {
        return [
            'id', 'pending_id', 'generic_name', 'generic_code', 'brand', 'destination_code', 'quantity',
            'send_quantity', 'date_send', 'number_day', 'doc_number', 'circular_number', 'status', 'created_at',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array(explode('.', $property)[0], self::FILTERS, true)) {
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

    /** Consulta filtrada; la misma alimenta la tabla y la exportación. */
    protected function baseQuery(): Builder
    {
        $like = fn (string $value): string => '%'.trim($value).'%';

        return PendingItem::query()
            ->with(['pending:id,type,status,created_at', 'product:id,item'])
            ->when($this->filterStatuses !== [], fn (Builder $q) => $q->whereIn('status', $this->filterStatuses))
            ->when($this->filterDestination !== '', fn (Builder $q) => $q->where('destination_code', 'like', $like($this->filterDestination)))
            ->when(ctype_digit(trim($this->filterPendingId)), fn (Builder $q) => $q->where('pending_id', (int) trim($this->filterPendingId)))
            ->when($this->filterGenericName !== '', fn (Builder $q) => $q->where('generic_name', 'like', $like($this->filterGenericName)))
            ->when($this->filterGenericCode !== '', fn (Builder $q) => $q->where('generic_code', 'like', $like($this->filterGenericCode)))
            ->when($this->filterBrand !== '', fn (Builder $q) => $q->where('brand', 'like', $like($this->filterBrand)))
            ->when($this->filterDocNumber !== '', fn (Builder $q) => $q->where('doc_number', 'like', $like($this->filterDocNumber)))
            ->when($this->filterCircular !== '', fn (Builder $q) => $q->where('circular_number', 'like', $like($this->filterCircular)))
            ->when($this->filterSendFrom !== '', fn (Builder $q) => $q->whereDate('date_send', '>=', $this->filterSendFrom))
            ->when($this->filterSendTo !== '', fn (Builder $q) => $q->whereDate('date_send', '<=', $this->filterSendTo))
            ->when($this->filterItem !== '', fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->where('item', 'like', $like($this->filterItem))))
            // Filtros del encabezado: fecha de generación de la solicitud, estado y tipo.
            ->when($this->filterRequestFrom !== '' || $this->filterRequestTo !== '' || $this->filterPendingStatus !== '' || $this->filterType !== '',
                fn (Builder $q) => $q->whereHas('pending', fn (Builder $p) => $p
                    ->when($this->filterRequestFrom !== '', fn (Builder $p) => $p->whereDate('created_at', '>=', $this->filterRequestFrom))
                    ->when($this->filterRequestTo !== '', fn (Builder $p) => $p->whereDate('created_at', '<=', $this->filterRequestTo))
                    ->when($this->filterPendingStatus !== '', fn (Builder $p) => $p->where('status', $this->filterPendingStatus))
                    ->when($this->filterType !== '', fn (Builder $p) => $p->where('type', $this->filterType))));
    }

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        return $this->applySort($this->baseQuery())->paginate($perPage);
    }

    /** Un único ítem seleccionado con cantidad enviada (corregible). */
    public function singleCorrectableSelected(): bool
    {
        if (count($this->selected) !== 1) {
            return false;
        }

        $item = PendingItem::query()->find($this->selectedIds()[0]);

        return $item !== null && ($item->send_quantity ?? 0) > 0 && ! $item->isOutOfStock();
    }

    public function openCorrect(): void
    {
        Gate::authorize('reverse', InventoryBalance::class);

        $this->resetValidation();
        $this->showCorrect = true;

        $item = PendingItem::query()->findOrFail($this->selectedIds()[0] ?? 0);
        $this->correctingId = $item->id;
        $this->correctTotal = (string) ($item->send_quantity ?? 0);
    }

    public function closeCorrect(): void
    {
        $this->showCorrect = false;
        $this->reset('correctingId', 'correctTotal');
        $this->resetValidation();
    }

    /** Ítem en corrección, para mostrar sus datos en el modal. */
    #[Computed]
    public function correctingItem(): ?PendingItem
    {
        return $this->correctingId ? PendingItem::query()->find($this->correctingId) : null;
    }

    /** Fija el total enviado correcto; el estado se recalcula en AssignSentQuantity. */
    public function saveCorrection(AssignSentQuantity $action): void
    {
        Gate::authorize('reverse', InventoryBalance::class);

        $this->validate(
            ['correctTotal' => ['required', 'integer', 'min:0', 'max:9999999']],
            [],
            ['correctTotal' => 'total enviado'],
        );

        $item = $action->correct(PendingItem::query()->findOrFail($this->correctingId), (int) $this->correctTotal);

        $this->closeCorrect();
        $this->clearSelection();
        unset($this->items);
        $this->toastSuccess("Enviado corregido: el ítem quedó {$item->status->label()}.");
    }

    /** @return array<string, string> */
    public function itemStatusOptions(): array
    {
        return PendingItemStatus::options();
    }

    public function sortIndicator(string $column): string
    {
        return $this->sortBy === $column ? ($this->sortDirection === 'asc' ? ' ▲' : ' ▼') : '';
    }

    protected function exportFileName(): string { return 'consulta-items-pendientes'; }

    protected function exportHeadings(): array
    {
        return [
            'N° pendiente', 'Tipo', 'Estado pendiente', 'Fecha solicitud', 'Ítem', 'Genérico', 'Cód. genérico',
            'Marca', 'C.C.', 'Cantidad', 'Enviado', 'Fecha envío', 'Días', 'Orden', 'Circular', 'Estado ítem', 'Observaciones',
        ];
    }

    /** @param PendingItem $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->pending_id, $row->pending?->type->label(), $row->pending?->status->label(),
            $row->pending?->created_at->format('Y-m-d'), $row->product?->item, $row->generic_name, $row->generic_code,
            $row->brand, $row->destination_code, $row->quantity, $row->send_quantity, $row->date_send?->format('Y-m-d'),
            $row->number_day, $row->doc_number, $row->circular_number, $row->status->label(), $row->observations,
        ];
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Consulta de ítems</h4></div>

        <div class="card-body">
            {{-- Filtros avanzados --}}
            <div class="border rounded p-3 mb-3">
                <div class="row g-2">
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1" for="query-request-from">Solicitud desde</label>
                        <input id="query-request-from" type="date" wire:model.live="filterRequestFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1" for="query-request-to">Solicitud hasta</label>
                        <input id="query-request-to" type="date" wire:model.live="filterRequestTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1" for="query-send-from">Envío desde</label>
                        <input id="query-send-from" type="date" wire:model.live="filterSendFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1" for="query-send-to">Envío hasta</label>
                        <input id="query-send-to" type="date" wire:model.live="filterSendTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1" for="query-pending-status">Estado pendiente</label>
                        <select id="query-pending-status" wire:model.live="filterPendingStatus" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach (PendingStatus::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label small mb-1" for="query-type">Tipo</label>
                        <select id="query-type" wire:model.live="filterType" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach (PendingType::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>

                    @foreach ([
                        'filterPendingId' => 'N° pendiente', 'filterItem' => 'Ítem', 'filterGenericName' => 'Genérico',
                        'filterGenericCode' => 'Cód. genérico', 'filterBrand' => 'Marca', 'filterDestination' => 'C.C.',
                        'filterDocNumber' => 'Orden (TB)', 'filterCircular' => 'Circular',
                    ] as $property => $label)
                        <div class="col-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1" for="query-{{ $property }}">{{ $label }}</label>
                            <input id="query-{{ $property }}" type="text" wire:model.live.debounce.300ms="{{ $property }}" class="form-control form-control-sm">
                        </div>
                    @endforeach

                    <div class="col-12">
                        <span class="form-label small me-2">Estado del ítem:</span>
                        @foreach ($this->itemStatusOptions() as $value => $label)
                            <div class="form-check form-check-inline">
                                <input id="query-status-{{ $value }}" type="checkbox" value="{{ $value }}" wire:model.live="filterStatuses" class="form-check-input">
                                <label class="form-check-label small" for="query-status-{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
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
                    @can('reverse', \Modules\Pharmacy\App\Models\InventoryBalance::class)
                        <x-tooltip text="Corregir la cantidad enviada del ítem seleccionado">
                            <button type="button" wire:click="openCorrect" @disabled(! $this->singleCorrectableSelected())
                                    class="btn btn-sm btn-outline-warning" aria-label="Corregir enviado">
                                <i class="bi bi-arrow-counterclockwise"></i> Corregir enviado
                            </button>
                        </x-tooltip>
                    @endcan
                    <x-tooltip text="Exportar a Excel">
                        <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                    </x-tooltip>
                    <x-tooltip text="Exportar a CSV">
                        <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                    </x-tooltip>
                </div>
            </div>

            <div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 2rem;"></th>
                            @foreach ([
                                'pending_id' => 'N° pend.', null => 'Tipo', 'pending_status' => 'Estado pend.', 'pending_date' => 'Fecha solicitud',
                                'item' => 'Ítem', 'generic_name' => 'Genérico', 'generic_code' => 'Cód. genérico', 'brand' => 'Marca',
                                'destination_code' => 'C.C.', 'quantity' => 'Cant.', 'send_quantity' => 'Enviado', 'date_send' => 'Fecha envío',
                                'number_day' => 'Días', 'doc_number' => 'Orden', 'circular_number' => 'Circular', 'status' => 'Estado ítem',
                                'observations' => 'Observaciones',
                            ] as $column => $label)
                                @if (in_array($column, $this->sortableColumns(), true))
                                    <th role="button" class="text-nowrap" wire:click="sort('{{ $column }}')">{{ $label }}{{ $this->sortIndicator($column) }}</th>
                                @else
                                    <th class="text-nowrap">{{ $label }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->items as $item)
                            <tr wire:key="query-item-{{ $item->id }}">
                                <x-table.row-checkbox :id="$item->id" :selected="$selected" />
                                <td>{{ $item->pending_id }}</td>
                                <td title="{{ $item->pending?->type->label() }}">{{ $item->pending?->type->value }}</td>
                                <td>@if ($item->pending)<span class="badge {{ $item->pending->status->badgeClass() }}">{{ $item->pending->status->label() }}</span>@endif</td>
                                <td class="text-nowrap">{{ $item->pending?->created_at->format('Y/m/d') }}</td>
                                <td>{{ $item->product?->item }}</td>
                                <td>{{ $item->generic_name }}</td>
                                <td>{{ $item->generic_code }}</td>
                                <td>{{ $item->brand ?? '-' }}</td>
                                <td>{{ $item->destination_code }}</td>
                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                <td class="text-end">{{ $item->send_quantity ?? '-' }}</td>
                                <td class="text-nowrap">{{ $item->date_send?->format('Y/m/d') ?? '-' }}</td>
                                <td class="text-end">{{ $item->number_day ?? '-' }}</td>
                                <td>{{ $item->doc_number ?? '-' }}</td>
                                <td>{{ $item->circular_number ?? '-' }}</td>
                                <td><span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                                <td class="small">{{ $item->observations ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="18" class="text-center text-muted py-4">Sin resultados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table.pagination :paginator="$this->items" />
        </div>
    </div>

    @can('reverse', \Modules\Pharmacy\App\Models\InventoryBalance::class)
        <x-modal wire-model="showCorrect" title="Corregir cantidad enviada" size="md">
            <div class="modal-body">
                @if ($this->correctingItem)
                    <p class="mb-2">
                        <strong>{{ $this->correctingItem->generic_name }}</strong><br>
                        Pendiente N° {{ $this->correctingItem->pending_id }} · C.C. {{ $this->correctingItem->destination_code }} ·
                        Solicitado: {{ $this->correctingItem->quantity }} · Enviado actual: {{ $this->correctingItem->send_quantity ?? 0 }}
                    </p>
                @endif
                <p class="small text-muted">
                    Digite el <strong>total enviado correcto</strong>. 0 deja el ítem Pendiente; menos de lo solicitado, Parcial;
                    lo solicitado o más, Cumplido. Un aumento se descuenta del saldo del inventario vigente.
                </p>
                <label class="form-label" for="correct-total">Total enviado *</label>
                <input id="correct-total" type="number" min="0" wire:model="correctTotal" wire:keydown.enter="saveCorrection"
                       @class(['form-control', 'is-invalid' => $errors->has('correctTotal') || $errors->has('quantity')])>
                @error('correctTotal')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <x-slot:footer>
                <button type="button" wire:click="closeCorrect" class="btn btn-secondary">Cancelar</button>
                <button type="button" wire:click="saveCorrection" wire:loading.attr="disabled" wire:target="saveCorrection" class="btn btn-primary">Guardar</button>
            </x-slot:footer>
        </x-modal>
    @endcan
</div>

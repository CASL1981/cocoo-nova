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
use Modules\Pharmacy\App\Actions\ManageNewProducts;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\NewProduct;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Gestión de productos nuevos (Farmacia → Productos nuevos → Gestión), solo Oficina Principal.
 *
 * Cuando Compras codifica el producto se asocia su ítem (obligatorio para asignar), se
 * asignan las unidades manualmente (no descuenta el saldo del ERP; el saldo se muestra
 * solo como referencia) y, al quedar Cumplidas, el número TB. También rechaza solicitudes.
 * Asociar, asignar, TB y rechazar: new-products-process; corregir enviado, desasociar,
 * cambiar o quitar TB y reabrir: new-products-reverse. Reglas en ManageNewProducts.
 */
new #[Layout('layouts.app')] #[Title('Gestión de productos nuevos')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasRowSelection, Notifies;

    /** Filtros que reinician la paginación y la selección al cambiar. */
    private const FILTERS = ['filterStatus', 'filterLink', 'filterDestination', 'search', 'filterDocNumber', 'perPage'];

    /** open = Sin asignar y Parcial (por defecto), all = todas, o un estado puntual. */
    #[Session(key: 'new-products.management.filterStatus-{authId}')] public string $filterStatus = 'open';
    /** linked = con ítem, unlinked = sin ítem, '' = todas. */
    #[Session(key: 'new-products.management.filterLink-{authId}')] public string $filterLink = '';
    #[Session(key: 'new-products.management.filterDestination-{authId}')] public string $filterDestination = '';
    #[Session(key: 'new-products.management.search-{authId}')] public string $search = '';
    #[Session(key: 'new-products.management.filterDocNumber-{authId}')] public string $filterDocNumber = '';
    #[Session(key: 'new-products.management.perPage-{authId}')] public int $perPage = 25;
    #[Session(key: 'new-products.management.sortBy-{authId}')] public string $sortBy = 'created_at';
    #[Session(key: 'new-products.management.sortDirection-{authId}')] public string $sortDirection = 'asc';

    /** Solicitud en edición en la fila; solo la asigna el servidor. */
    #[Locked]
    public ?int $editingId = null;

    /** assign = sumar unidades (process); correct = fijar el total enviado (reverse). */
    #[Locked]
    public string $editingMode = 'assign';

    public string $rowQuantity = '';

    /** Buscador del ítem del catálogo a asociar a las solicitudes seleccionadas. */
    public string $productSearch = '';

    public string $productId = '';

    /** Número TB a asignar a las solicitudes seleccionadas. */
    public string $docNumber = '';

    public function mount(): void
    {
        Gate::authorize('manage', NewProduct::class);
    }

    protected function sortableColumns(): array
    {
        return ['id', 'created_at', 'status', 'destination_code', 'product_name', 'brand', 'quantity', 'send_quantity', 'date_send', 'doc_number'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->resetPage();
            $this->clearSelection();
            $this->cancelEdit();
        }
    }

    public function updatedSelected(): void
    {
        unset($this->selectedDestinations);
        $this->resetValidation(['docNumber', 'productId']);
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
        $this->clearSelection();
    }

    /** Solicitudes con los filtros activos; la misma consulta alimenta la exportación. */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);
        $statuses = array_map(fn (NewProductStatus $status) => $status->value, NewProductStatus::cases());

        return NewProduct::query()
            ->with(['product:id,item,description,generic_code', 'user:id,name,lastname'])
            ->when($this->filterStatus === 'open', fn (Builder $q) => $q->open())
            ->when(in_array($this->filterStatus, $statuses, true), fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->when($this->filterLink === 'linked', fn (Builder $q) => $q->whereNotNull('product_id'))
            ->when($this->filterLink === 'unlinked', fn (Builder $q) => $q->whereNull('product_id'))
            ->when($this->filterDestination !== '', fn (Builder $q) => $q->where('destination_code', 'like', '%'.trim($this->filterDestination).'%'))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('product_name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('observations', 'like', "%{$search}%")
                ->when(ctype_digit($search), fn (Builder $w) => $w->orWhere('id', (int) $search))))
            ->when($this->filterDocNumber !== '', fn (Builder $q) => $q->where('doc_number', 'like', '%'.trim($this->filterDocNumber).'%'));
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        return $this->applySort($this->baseQuery())->orderBy('id')->paginate($perPage);
    }

    /**
     * Saldo del ERP por código genérico de los ítems asociados de la página (solo referencia).
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stocks(): array
    {
        return InventoryBalance::balancesByGeneric($this->rows->pluck('product.generic_code')->filter()->unique()->values()->all());
    }

    /**
     * C.C. de las solicitudes seleccionadas (un TB debe tener uno solo).
     *
     * @return list<string>
     */
    #[Computed]
    public function selectedDestinations(): array
    {
        if ($this->selected === []) {
            return [];
        }

        return NewProduct::query()->whereKey($this->selectedIds())->distinct()->orderBy('destination_code')
            ->pluck('destination_code')->all();
    }

    /**
     * Ítems activos del catálogo por código o descripción (desde 3 caracteres).
     *
     * @return array<int, string>
     */
    #[Computed]
    public function productResults(): array
    {
        $term = trim($this->productSearch);

        if (mb_strlen($term) < 3 || $this->productId !== '') {
            return [];
        }

        return Product::query()->where('status', ProductStatus::Active)
            ->where(fn (Builder $q) => $q->where('item', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"))
            ->orderBy('description')->limit(20)->get(['id', 'item', 'description'])
            ->mapWithKeys(fn (Product $product) => [$product->id => "{$product->item} - {$product->description}"])
            ->all();
    }

    /** Escribir de nuevo en el buscador descarta el ítem elegido. */
    public function updatedProductSearch(): void
    {
        $this->productId = '';
    }

    public function selectProduct(string $productId): void
    {
        $product = Product::query()->where('status', ProductStatus::Active)->findOrFail($productId);

        $this->productId = (string) $product->id;
        $this->productSearch = "{$product->item} - {$product->description}";
        $this->resetValidation('productId');
    }

    /** Asocia el ítem elegido a las solicitudes seleccionadas. */
    public function link(ManageNewProducts $action): void
    {
        Gate::authorize('process', NewProduct::class);

        $this->validate(['productId' => ['required', 'integer']], ['productId.required' => 'Seleccione el ítem del catálogo.']);

        $count = $action->link($this->selectedIds(), (int) $this->productId, Gate::allows('reverse', NewProduct::class));

        $this->toastSuccess("Ítem asociado a {$count} solicitud(es).");
        $this->reset('productSearch', 'productId');
        $this->afterChange();
    }

    /** Quita el ítem asociado de las solicitudes seleccionadas (corrección). */
    public function unlink(ManageNewProducts $action): void
    {
        Gate::authorize('reverse', NewProduct::class);

        $this->runBulk(fn () => $action->unlink($this->selectedIds()), 'Ítem desasociado de :count solicitud(es).');
    }

    public function reject(ManageNewProducts $action): void
    {
        Gate::authorize('process', NewProduct::class);

        $this->runBulk(fn () => $action->reject($this->selectedIds()), ':count solicitud(es) rechazada(s).');
    }

    public function reopen(ManageNewProducts $action): void
    {
        Gate::authorize('reverse', NewProduct::class);

        $this->runBulk(fn () => $action->reopen($this->selectedIds()), ':count solicitud(es) reabierta(s).');
    }

    public function assignTransferDocument(ManageNewProducts $action): void
    {
        Gate::authorize('process', NewProduct::class);

        $this->validate(
            ['docNumber' => ['required', 'regex:/^\d{1,50}$/']],
            ['docNumber.regex' => 'El número TB debe contener solo números (máximo 50 dígitos).'],
            ['docNumber' => 'número TB'],
        );

        $count = $action->assignTransferDocument($this->selectedIds(), $this->docNumber, Gate::allows('reverse', NewProduct::class));

        $this->toastSuccess("TB {$this->docNumber} asignado a {$count} solicitud(es).");
        $this->reset('docNumber');
        $this->afterChange();
    }

    public function removeTransferDocument(ManageNewProducts $action): void
    {
        Gate::authorize('reverse', NewProduct::class);

        $this->runBulk(fn () => $action->removeTransferDocument($this->selectedIds()), 'TB quitado de :count solicitud(es).');
    }

    /** Abre la fila para sumar unidades (assign) o fijar el total enviado (correct). */
    public function editRow(int $id, string $mode = 'assign'): void
    {
        $mode = $mode === 'correct' ? 'correct' : 'assign';
        Gate::authorize($mode === 'correct' ? 'reverse' : 'process', NewProduct::class);

        $row = NewProduct::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $row->id;
        $this->editingMode = $mode;
        $this->rowQuantity = $mode === 'correct' ? (string) $row->send_quantity : '';
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingMode = 'assign';
        $this->rowQuantity = '';
        $this->resetValidation();
    }

    public function saveRow(ManageNewProducts $action): void
    {
        $correcting = $this->editingMode === 'correct';
        Gate::authorize($correcting ? 'reverse' : 'process', NewProduct::class);

        $this->validate(
            ['rowQuantity' => ['required', 'integer', $correcting ? 'min:0' : 'min:1', 'max:9999999']],
            [],
            ['rowQuantity' => $correcting ? 'total enviado' : 'cantidad enviada'],
        );

        $row = NewProduct::query()->findOrFail($this->editingId);
        $row = $correcting ? $action->correct($row, (int) $this->rowQuantity) : $action->assign($row, (int) $this->rowQuantity);

        $this->toastSuccess("Solicitud {$row->status->label()}: enviado ".((int) $row->send_quantity)." de {$row->quantity}.");
        $this->cancelEdit();
        unset($this->rows, $this->stocks);
    }

    public function export(string $format): BinaryFileResponse
    {
        Gate::authorize('manage', NewProduct::class);
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery())->orderBy('id'),
            headings: [
                'N°', 'Estado', 'Fecha', 'C.C.', 'Producto', 'Marca', 'Solicitante', 'Ítem asociado', 'Descripción del ítem',
                'Cantidad', 'Enviado', 'Falta', 'Fecha envío', 'TB', 'Observaciones',
            ],
            mapRow: fn (NewProduct $row) => [
                $row->id, $row->status->label(), $row->created_at->format('Y-m-d'), $row->destination_code, $row->product_name,
                $row->brand, $row->user?->fullName(), $row->product?->item, $row->product?->description, $row->quantity,
                $row->send_quantity ?? 0, $row->quantity - ($row->send_quantity ?? 0), $row->date_send?->format('Y-m-d'),
                $row->doc_number, $row->observations,
            ],
        );

        return Excel::download($export, 'gestion-productos-nuevos-'.now()->format('Y-m-d_His').'.'.$format);
    }

    public function sortIndicator(string $column): string
    {
        return $this->sortBy === $column ? ($this->sortDirection === 'asc' ? ' ▲' : ' ▼') : '';
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return ['open' => 'Abiertas', ...NewProductStatus::options(), 'all' => 'Todas'];
    }

    /**
     * Ejecuta una acción masiva; los errores de negocio se informan con un toast.
     *
     * @param  \Closure(): int  $operation
     */
    private function runBulk(\Closure $operation, string $message): void
    {
        try {
            $count = $operation();
        } catch (ValidationException $exception) {
            $this->toastError(collect($exception->errors())->flatten()->first() ?? 'No se pudo completar la acción.');

            return;
        }

        $this->toastSuccess(str_replace(':count', (string) $count, $message));
        $this->afterChange();
    }

    private function afterChange(): void
    {
        $this->resetValidation();
        $this->clearSelection();
        $this->cancelEdit();
        unset($this->rows, $this->stocks, $this->selectedDestinations);
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Gestión de productos nuevos</h4></div>

        <div class="card-body">
            {{-- Filtros --}}
            <div class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1" for="np-status">Estado</label>
                    <select id="np-status" wire:model.live="filterStatus" class="form-select form-select-sm">
                        @foreach ($this->statusOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1" for="np-link">Ítem</label>
                    <select id="np-link" wire:model.live="filterLink" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="unlinked">Sin asociar</option>
                        <option value="linked">Asociados</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label class="form-label small mb-1" for="np-destination">C.C.</label>
                    <input id="np-destination" type="text" wire:model.live.debounce.300ms="filterDestination" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-4">
                    <label class="form-label small mb-1" for="np-search">Buscar</label>
                    <input id="np-search" type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                           placeholder="Producto, marca, N°, observaciones">
                </div>
                <div class="col-6 col-md-3 col-xl-1">
                    <label class="form-label small mb-1" for="np-doc">N° TB</label>
                    <input id="np-doc" type="text" wire:model.live.debounce.300ms="filterDocNumber" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2 d-flex gap-2">
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>
                    <x-tooltip text="Exportar a Excel (con los filtros aplicados)">
                        <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                    </x-tooltip>
                    <x-tooltip text="Exportar a CSV (con los filtros aplicados)">
                        <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                    </x-tooltip>
                </div>
            </div>

            {{-- Acciones sobre las solicitudes seleccionadas --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <label class="d-inline-flex align-items-center gap-2 small">Mostrar
                    <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                        @foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                    </select>
                </label>

                <div class="d-flex align-items-start flex-wrap gap-2">
                    @can('process', NewProduct::class)
                        {{-- Asociar el ítem codificado por Compras (obligatorio para asignar unidades). --}}
                        <div class="d-flex align-items-start gap-1" style="min-width: 22rem;">
                            <div class="flex-grow-1">
                                <x-forms.search-select name="productId" search="productSearch" select="selectProduct" id="np-product"
                                                       :results="$this->productResults" placeholder="Ítem del catálogo (código o descripción)"
                                                       class="form-control-sm" :disabled="count($selected) === 0" />
                            </div>
                            <x-tooltip text="Asociar el ítem a las solicitudes seleccionadas">
                                <button type="button" wire:click="link" @disabled(count($selected) === 0 || $productId === '')
                                        class="btn btn-sm btn-primary text-nowrap" wire:loading.attr="disabled" wire:target="link">Asociar ítem</button>
                            </x-tooltip>
                        </div>

                        <div>
                            <div class="input-group input-group-sm" style="max-width: 16rem;">
                                <input type="text" inputmode="numeric" wire:model="docNumber" wire:keydown.enter="assignTransferDocument"
                                       @class(['form-control', 'is-invalid' => $errors->has('docNumber')])
                                       placeholder="N° TB" aria-label="Número TB" @disabled(count($selected) === 0)>
                                <x-tooltip text="Asignar el TB a las solicitudes Cumplidas seleccionadas (mismo C.C.)">
                                    <button type="button" wire:click="assignTransferDocument" @disabled(count($selected) === 0 || count($this->selectedDestinations) > 1)
                                            class="btn btn-primary btn-sm" aria-label="Asignar TB" wire:loading.attr="disabled" wire:target="assignTransferDocument">
                                        Asignar TB
                                    </button>
                                </x-tooltip>
                            </div>
                            @error('docNumber')<div class="invalid-feedback d-block small">{{ $message }}</div>@enderror
                            @if (count($selected) > 0)
                                <div @class(['small mt-1', 'text-danger' => count($this->selectedDestinations) > 1, 'text-muted' => count($this->selectedDestinations) <= 1])>
                                    {{ $this->selectedCount() }} seleccionada(s) · C.C. {{ implode(', ', $this->selectedDestinations) }}
                                </div>
                            @endif
                        </div>

                        <x-tooltip text="Rechazar las solicitudes seleccionadas (Sin asignar)">
                            <button type="button" @disabled(count($selected) === 0) class="btn btn-sm btn-outline-danger" aria-label="Rechazar"
                                    x-on:click="confirmAction({ title: 'Rechazar solicitudes', text: '¿Rechazar {{ $this->selectedCount() }} solicitud(es)? El producto no se codificará.' }).then(() => $wire.reject()).catch(() => {})">
                                <i class="bi bi-x-octagon"></i>
                            </button>
                        </x-tooltip>
                    @endcan
                    @can('reverse', NewProduct::class)
                        <x-tooltip text="Reabrir las solicitudes rechazadas seleccionadas">
                            <button type="button" @disabled(count($selected) === 0) class="btn btn-sm btn-outline-warning" aria-label="Reabrir"
                                    x-on:click="confirmAction({ title: 'Reabrir solicitudes', text: '¿Reabrir {{ $this->selectedCount() }} solicitud(es) rechazada(s)?' }).then(() => $wire.reopen()).catch(() => {})">
                                <i class="bi bi-unlock"></i>
                            </button>
                        </x-tooltip>
                        <x-tooltip text="Desasociar el ítem de las solicitudes seleccionadas (sin unidades enviadas)">
                            <button type="button" @disabled(count($selected) === 0) class="btn btn-sm btn-outline-secondary" aria-label="Desasociar ítem"
                                    x-on:click="confirmAction({ title: 'Desasociar ítem', text: '¿Quitar el ítem asociado de {{ $this->selectedCount() }} solicitud(es)?' }).then(() => $wire.unlink()).catch(() => {})">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </x-tooltip>
                        <x-tooltip text="Quitar el TB de las solicitudes seleccionadas">
                            <button type="button" @disabled(count($selected) === 0) class="btn btn-sm btn-outline-danger" aria-label="Quitar TB"
                                    x-on:click="confirmAction({ title: 'Quitar TB', text: '¿Quitar el TB de {{ $this->selectedCount() }} solicitud(es)?' }).then(() => $wire.removeTransferDocument()).catch(() => {})">
                                <i class="bi bi-eraser"></i>
                            </button>
                        </x-tooltip>
                    @endcan
                </div>
            </div>

            @php $pageIds = $this->rows->pluck('id')->all(); $allPageSelected = count($pageIds) > 0 && empty(array_diff($pageIds, $selected)); @endphp
            <x-table.selection-banner :page-ids="$pageIds" :selected="$selected" :all-matching="$selectAllMatching"
                                      :matching-count="$this->matchingSelectionCount($pageIds)" />
            <div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50"
                 wire:target="saveRow,link,unlink,reject,reopen,assignTransferDocument,removeTransferDocument,search,filterStatus,filterLink,filterDestination,filterDocNumber">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 2rem;">
                                <input type="checkbox" wire:key="select-all-{{ $allPageSelected ? 'checked' : 'unchecked' }}" class="form-check-input"
                                       @checked($allPageSelected) wire:click="toggleSelectAll({{ json_encode($pageIds) }})" aria-label="Seleccionar página">
                            </th>
                            <th>Acción</th>
                            <th role="button" class="text-nowrap" wire:click="sort('id')">N°{{ $this->sortIndicator('id') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('status')">Estado{{ $this->sortIndicator('status') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('created_at')">Fecha{{ $this->sortIndicator('created_at') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('destination_code')">C.C.{{ $this->sortIndicator('destination_code') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('product_name')">Producto{{ $this->sortIndicator('product_name') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('brand')">Marca{{ $this->sortIndicator('brand') }}</th>
                            <th>Ítem asociado</th>
                            <th class="text-end" title="Saldo del genérico en la carga vigente del ERP (solo referencia)">Saldo ERP</th>
                            <th role="button" class="text-nowrap text-end" wire:click="sort('quantity')">Cant.{{ $this->sortIndicator('quantity') }}</th>
                            <th role="button" class="text-nowrap text-end" wire:click="sort('send_quantity')">Enviado{{ $this->sortIndicator('send_quantity') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('date_send')">Fecha envío{{ $this->sortIndicator('date_send') }}</th>
                            <th role="button" class="text-nowrap" wire:click="sort('doc_number')">TB{{ $this->sortIndicator('doc_number') }}</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->rows as $row)
                            @php
                                $editing = $editingId === $row->id;
                                $stock = $row->product?->generic_code ? ($this->stocks[$row->product->generic_code] ?? 0) : null;
                            @endphp
                            <tr wire:key="np-row-{{ $row->id }}" @class(['table-active' => $editing])>
                                <x-table.row-checkbox :id="$row->id" :selected="$selected" />
                                <td class="text-nowrap">
                                    @if ($editing)
                                        <x-tooltip text="Guardar">
                                            <button type="button" wire:click="saveRow" class="btn btn-sm btn-outline-success" aria-label="Guardar"
                                                    wire:loading.attr="disabled" wire:target="saveRow"><i class="bi bi-check-lg"></i></button>
                                        </x-tooltip>
                                        <x-tooltip text="Cancelar">
                                            <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-outline-danger" aria-label="Cancelar"><i class="bi bi-x-lg"></i></button>
                                        </x-tooltip>
                                    @else
                                        {{-- Asignar: solicitud abierta y con ítem asociado. --}}
                                        @if ($row->isOpen() && $row->product_id !== null)
                                            @can('process', NewProduct::class)
                                                <x-tooltip text="Asignar unidades enviadas">
                                                    <button type="button" wire:click="editRow({{ $row->id }}, 'assign')" class="btn btn-sm btn-outline-primary" aria-label="Asignar unidades">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </x-tooltip>
                                            @endcan
                                        @endif
                                        @if (($row->send_quantity ?? 0) > 0 && $row->doc_number === null)
                                            @can('reverse', NewProduct::class)
                                                <x-tooltip text="Corregir el total enviado">
                                                    <button type="button" wire:click="editRow({{ $row->id }}, 'correct')" class="btn btn-sm btn-outline-warning" aria-label="Corregir enviado">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </x-tooltip>
                                            @endcan
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $row->id }}</td>
                                <td><span class="badge {{ $row->status->badgeClass() }}">{{ $row->status->label() }}</span></td>
                                <td class="text-nowrap">{{ $row->created_at->format('Y/m/d') }}</td>
                                <td>{{ $row->destination_code }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ $row->brand ?? '-' }}</td>
                                <td>
                                    @if ($row->product)
                                        <span class="fw-semibold">{{ $row->product->item }}</span> <span class="small text-muted">{{ $row->product->description }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">Sin asociar</span>
                                    @endif
                                </td>
                                <td @class(['text-end', 'text-success fw-semibold' => ($stock ?? 0) > 0, 'text-muted' => ($stock ?? 0) <= 0])>
                                    {{ $stock === null ? '-' : number_format($stock) }}
                                </td>
                                <td class="text-end">{{ number_format($row->quantity) }}</td>
                                <td class="text-end" style="min-width: 8rem;">
                                    @if ($editing)
                                        <input type="number" min="{{ $editingMode === 'correct' ? 0 : 1 }}" wire:model="rowQuantity"
                                               wire:keydown.enter="saveRow" wire:keydown.escape="cancelEdit"
                                               @class(['form-control form-control-sm text-end', 'is-invalid' => $errors->has('rowQuantity') || $errors->has('quantity')])
                                               placeholder="{{ $editingMode === 'correct' ? 'Total' : '+ cant.' }}"
                                               aria-label="{{ $editingMode === 'correct' ? 'Total enviado' : 'Cantidad a enviar' }}" autofocus>
                                        @if ($editingMode === 'assign' && $row->send_quantity)<div class="small text-muted">Ya enviado: {{ $row->send_quantity }}</div>@endif
                                        @error('rowQuantity')<div class="invalid-feedback d-block text-start">{{ $message }}</div>@enderror
                                        @error('quantity')<div class="invalid-feedback d-block text-start">{{ $message }}</div>@enderror
                                    @else
                                        {{ $row->send_quantity ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $row->date_send?->format('Y/m/d') ?? '-' }}</td>
                                <td class="fw-semibold">{{ $row->doc_number ?? '-' }}</td>
                                <td class="small">{{ $row->observations ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="text-center text-muted py-4">No hay solicitudes con los filtros aplicados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table.pagination :paginator="$this->rows" />
        </div>
    </div>
</div>

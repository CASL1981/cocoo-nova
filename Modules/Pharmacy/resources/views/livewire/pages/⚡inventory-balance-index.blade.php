<?php

use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Pharmacy\App\Actions\ImportInventoryBalances;
use Modules\Pharmacy\App\Models\InventoryBalance;

/**
 * Inventario disponible de Oficina Principal (Farmacia → Gestión).
 *
 * Carga el archivo del ERP (reemplaza la carga anterior) y muestra los ítems
 * homologados con el catálogo, con su saldo, y aparte los no homologados.
 */
new #[Layout('layouts.app')] #[Title('Inventario disponible')] class extends Component
{
    use WithPagination, WithFileUploads, HasAuthScopedSession, HasSortableColumns,
        HasColumnFilters, HasColumnVisibility, ExportsRows, Notifies;

    #[Session(key: 'inventory-balances.index.filterItem-{authId}')] public string $filterItem = '';
    #[Session(key: 'inventory-balances.index.filterProductName-{authId}')] public string $filterProductName = '';
    #[Session(key: 'inventory-balances.index.filterGenericCode-{authId}')] public string $filterGenericCode = '';
    #[Session(key: 'inventory-balances.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'inventory-balances.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'inventory-balances.index.sortBy-{authId}')] public string $sortBy = 'product_name';
    #[Session(key: 'inventory-balances.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    /** Búsqueda en la tabla de no homologados (ítem o nombre del ERP). */
    public string $unmatchedSearch = '';

    /** Modal de carga del archivo del ERP. */
    public bool $showImport = false;

    public ?TemporaryUploadedFile $importFile = null;

    public string $cutOffDate = '';

    /** @var list<string> Errores por fila de la última carga fallida. */
    public array $importErrors = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', InventoryBalance::class);
    }

    protected function rowModelClass(): string { return InventoryBalance::class; }

    /** Ítems homologados con el catálogo; misma consulta para la tabla y la exportación. */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(InventoryBalance::query()->matched());
    }

    #[Computed]
    public function balances(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[Computed]
    public function unmatched(): LengthAwarePaginator
    {
        $term = trim($this->unmatchedSearch);

        return InventoryBalance::query()->unmatched()
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('item', 'like', "%{$term}%")->orWhere('product_name', 'like', "%{$term}%")))
            ->orderBy('product_name')
            ->paginate(10, ['*'], 'unmatchedPage');
    }

    /** Carga vigente: fecha de corte, fecha de carga, usuario y totales. */
    #[Computed]
    public function currentLoad(): ?InventoryBalance
    {
        return InventoryBalance::currentLoad();
    }

    #[Computed]
    public function unmatchedCount(): int
    {
        return InventoryBalance::query()->unmatched()->count();
    }

    public function updatedUnmatchedSearch(): void
    {
        $this->resetPage('unmatchedPage');
    }

    public function openImport(): void
    {
        Gate::authorize('import', InventoryBalance::class);

        $this->reset('importFile', 'importErrors');
        $this->resetValidation();
        $this->cutOffDate = now()->toDateString();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->showImport = false;
        $this->reset('importFile', 'importErrors');
        $this->resetValidation();
    }

    /** Valida el archivo y la fecha de corte y reemplaza la carga vigente. */
    public function import(ImportInventoryBalances $action): void
    {
        Gate::authorize('import', InventoryBalance::class);

        $this->importErrors = [];
        $this->validate(
            [
                'cutOffDate' => ['required', 'date', 'before_or_equal:today'],
                'importFile' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:20480'],
            ],
            [
                'cutOffDate.before_or_equal' => 'La fecha de corte no puede ser futura.',
                'importFile.required' => 'Seleccione un archivo.',
                'importFile.mimes' => 'El archivo debe ser Excel (.xlsx) o CSV.',
                'importFile.max' => 'El archivo no debe superar 20 MB.',
            ],
            ['cutOffDate' => 'fecha de corte'],
        );

        /** @var User $user */
        $user = Auth::user();
        /** @var TemporaryUploadedFile $file */
        $file = $this->importFile;

        $result = $action->execute($file, CarbonImmutable::parse($this->cutOffDate), $user);

        if ($result->failed()) {
            $this->importErrors = $result->errors;
            $this->toastError('No se cargó el inventario: corrija los errores del archivo.');

            return;
        }

        $this->closeImport();
        $this->resetPage();
        $this->resetPage('unmatchedPage');
        unset($this->balances, $this->unmatched, $this->currentLoad, $this->unmatchedCount);
        $this->toastSuccess("Inventario cargado: {$result->created} ítem(s), {$this->unmatchedCount} sin homologar.");
    }

    protected function exportFileName(): string { return 'inventario-disponible'; }

    protected function exportHeadings(): array
    {
        return ['Ítem', 'Producto (ERP)', 'Cód. genérico', 'Disponible', 'Asignado', 'Saldo', 'Fecha de corte'];
    }

    /** @param InventoryBalance $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->item, $row->product_name, $row->generic_code, $row->available_quantity,
            $row->assigned_quantity, $row->balance(), $row->cut_off_date->format('Y-m-d'),
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y orden de la tabla de homologados. */
    protected function columns(): array
    {
        return [
            'item' => ['label' => 'Ítem', 'filter' => 'filterItem'],
            'product_name' => ['label' => 'Producto', 'width' => 'xl', 'filter' => 'filterProductName'],
            'generic_code' => ['label' => 'Cód. gen.', 'filter' => 'filterGenericCode'],
            'available_quantity' => ['label' => 'Disp.', 'filter' => null],
            'assigned_quantity' => ['label' => 'Asig.', 'filter' => null],
        ];
    }
};
?>

<div x-data>
    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">Inventario disponible</h4>
                    @can('inventory-balances-import')
                        <x-tooltip text="Cargar el inventario del ERP (reemplaza la carga vigente)">
                            <button type="button" wire:click="openImport" class="btn btn-sm btn-primary" aria-label="Cargar inventario">
                                <i class="bi bi-upload"></i> Cargar inventario
                            </button>
                        </x-tooltip>
                    @endcan
                </div>

                <div class="card-body">
                    @if ($this->currentLoad)
                        <div class="alert alert-info py-2 d-flex flex-wrap gap-3">
                            <span><strong>Fecha de corte:</strong> {{ $this->currentLoad->cut_off_date->format('Y/m/d') }}</span>
                            <span><strong>Cargado:</strong> {{ $this->currentLoad->created_at->format('Y/m/d H:i') }}</span>
                            <span><strong>Por:</strong> {{ $this->currentLoad->creator?->fullName() ?? '-' }}</span>
                            <span><strong>Sin homologar:</strong> {{ $this->unmatchedCount }}</span>
                        </div>
                    @else
                        <div class="alert alert-warning py-2">Aún no se ha cargado el inventario del ERP.</div>
                    @endif

                    <h5 class="mb-2">Homologados con el catálogo</h5>
                    <div class="table-fit">
                    <x-data-table :paginator="$this->balances" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()" :leading-columns="0">
                        <x-slot:toolbar-right>
                            <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>
                            <x-tooltip text="Exportar a Excel">
                                <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                            </x-tooltip>
                            <x-tooltip text="Exportar a CSV">
                                <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                            </x-tooltip>
                        </x-slot:toolbar-right>

                        <x-slot:head>
                            <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                            <th class="col-fit text-end">Saldo</th>
                        </x-slot:head>

                        <x-slot:filters>
                            <x-table.filters :columns="$this->columns()" :with-selection="false" />
                            <th></th>
                        </x-slot:filters>

                        @forelse ($this->balances as $balance)
                            <tr wire:key="balance-{{ $balance->id }}">
                                <td class="col-fit">{{ $balance->item }}</td>
                                <td>{{ $balance->product_name ?? '-' }}</td>
                                <td class="col-fit">{{ $balance->generic_code ?? '-' }}</td>
                                <td class="col-fit text-end">{{ number_format($balance->available_quantity) }}</td>
                                <td class="col-fit text-end">{{ number_format($balance->assigned_quantity) }}</td>
                                <td class="col-fit text-end fw-semibold">{{ number_format($balance->balance()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados</td></tr>
                        @endforelse
                    </x-data-table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">No homologados <small class="text-muted fs-6">(el ítem del ERP no existe en el catálogo de productos)</small></h4>
                    <input type="search" wire:model.live.debounce.300ms="unmatchedSearch" class="form-control form-control-sm w-auto"
                           placeholder="Buscar ítem o producto..." aria-label="Buscar no homologados">
                </div>
                <div class="card-body">
                    <div class="table-fit table-responsive border rounded">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr><th class="col-fit">Ítem</th><th>Producto</th><th class="col-fit text-end">Disp.</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($this->unmatched as $balance)
                                    <tr wire:key="unmatched-{{ $balance->id }}">
                                        <td class="col-fit">{{ $balance->item }}</td>
                                        <td>{{ $balance->product_name ?? '-' }}</td>
                                        <td class="col-fit text-end">{{ number_format($balance->available_quantity) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Sin ítems no homologados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-table.pagination :paginator="$this->unmatched" />
                </div>
            </div>
        </div>

    </div>

    @can('inventory-balances-import')
        <x-modal wire-model="showImport" title="Cargar inventario del ERP" size="lg">
            <div class="modal-body">
                <p class="mb-3">
                    Cargue el archivo de existencias del ERP (ispl). Se toman las columnas <strong>ProductId</strong>
                    y <strong>Disponible</strong> y se suman por ítem. <strong>La carga reemplaza la vigente</strong>
                    y el saldo asignado vuelve a cero. Si alguna fila tiene errores no se carga nada.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <x-forms.input name="cutOffDate" label="Fecha de corte *" type="date" id="inventory-cut-off" />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="inventory-file">Archivo *</label>
                        <input id="inventory-file" type="file" wire:model="importFile" accept=".xlsx,.csv"
                               @class(['form-control', 'is-invalid' => $errors->has('importFile')])>
                        @error('importFile')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div wire:loading wire:target="importFile" class="small text-muted mt-1">Cargando archivo…</div>
                    </div>
                </div>
                <div wire:loading wire:target="import" class="small text-muted mt-2">Procesando el archivo, puede tardar unos segundos…</div>

                @if ($importErrors !== [])
                    <div class="alert alert-danger mt-3 mb-0" style="max-height: 16rem; overflow-y: auto;">
                        <ul class="mb-0 ps-3 small">
                            @foreach ($importErrors as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeImport" class="btn btn-secondary">Cancelar</button>
                <button type="button" wire:click="import" wire:loading.attr="disabled" wire:target="import,importFile"
                        class="btn btn-primary">Cargar</button>
            </x-slot:footer>
        </x-modal>
    @endcan
</div>

<?php
// Modules/Shopping/resources/views/pages/product-index.blade.php

use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\DuplicatesRows;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Contracts\RowImporter;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\ImportsRows;
use App\Livewire\Concerns\Notifies;
use App\Exports\GenericExport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Imports\ProductImporter;
use Modules\Shopping\App\Models\Product;

new #[Layout('layouts.app')] #[Title('Productos')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions, DuplicatesRows, HasColumnVisibility, ImportsRows, Notifies;

    private const PER_PAGE = [10, 25, 50, 100];

    #[Session(key: 'products.index.filterItem-{authId}')] public string $filterItem = '';
    #[Session(key: 'products.index.filterDescription-{authId}')] public string $filterDescription = '';
    #[Session(key: 'products.index.filterBrand-{authId}')] public string $filterBrand = '';
    #[Session(key: 'products.index.filterAtc-{authId}')] public string $filterAtc = '';
    #[Session(key: 'products.index.filterInvima-{authId}')] public string $filterInvima = '';
    #[Session(key: 'products.index.filterMeasureUnit-{authId}')] public string $filterMeasureUnit = '';
    #[Session(key: 'products.index.filterPresentation-{authId}')] public string $filterPresentation = '';
    #[Session(key: 'products.index.filterConcentration-{authId}')] public string $filterConcentration = '';
    #[Session(key: 'products.index.filterPharmaceuticalForm-{authId}')] public string $filterPharmaceuticalForm = '';
    #[Session(key: 'products.index.filterGenericName-{authId}')] public string $filterGenericName = '';
    #[Session(key: 'products.index.filterGenericCode-{authId}')] public string $filterGenericCode = '';
    #[Session(key: 'products.index.filterTaxPercentage-{authId}')] public string $filterTaxPercentage = '';
    #[Session(key: 'products.index.filterSupplierNit-{authId}')] public string $filterSupplierNit = '';
    #[Session(key: 'products.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'products.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'products.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'products.index.sortBy-{authId}')] public string $sortBy = 'item';
    #[Session(key: 'products.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    protected function rowModelClass(): string
    {
        return Product::class;
    }

    /** Devuelve el nombre de la propiedad que contiene los registros a mostrar en la tabla. */
    protected function computedRowsProperty(): string
    {
        return 'products';
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' || str_starts_with($property, 'filter')) {
            $this->resetPage();
            $this->clearSelection();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterItem', 'filterDescription', 'filterBrand', 'filterAtc', 'filterInvima',
            'filterMeasureUnit', 'filterPresentation', 'filterConcentration',
            'filterPharmaceuticalForm', 'filterGenericName', 'filterGenericCode',
            'filterTaxPercentage', 'filterSupplierNit', 'filterStatus',
        ]);
        $this->resetPage();
        $this->clearSelection();
    }

    protected function sortableColumns(): array
    {
        return array_keys($this->columns());
    }

    private function baseQuery()
    {
        return Product::query()
            ->withTrashed()
            ->when($this->filterItem, fn ($q, $v) => $q->where('item', 'like', "%{$v}%"))
            ->when($this->filterDescription, fn ($q, $v) => $q->where('description', 'like', "%{$v}%"))
            ->when($this->filterBrand, fn ($q, $v) => $q->where('brand', 'like', "%{$v}%"))
            ->when($this->filterAtc, fn ($q, $v) => $q->where('atc', 'like', "%{$v}%"))
            ->when($this->filterInvima, fn ($q, $v) => $q->where('invima', 'like', "%{$v}%"))
            ->when($this->filterMeasureUnit, fn ($q, $v) => $q->where('measure_unit', 'like', "%{$v}%"))
            ->when($this->filterPresentation, fn ($q, $v) => $q->where('presentation', 'like', "%{$v}%"))
            ->when($this->filterConcentration, fn ($q, $v) => $q->where('concentration', 'like', "%{$v}%"))
            ->when($this->filterPharmaceuticalForm, fn ($q, $v) => $q->where('pharmaceutical_form', 'like', "%{$v}%"))
            ->when($this->filterGenericName, fn ($q, $v) => $q->where('generic_name', 'like', "%{$v}%"))
            ->when($this->filterGenericCode, fn ($q, $v) => $q->where('generic_code', 'like', "%{$v}%"))
            ->when($this->filterTaxPercentage, fn ($q, $v) => $q->where('tax_percentage', 'like', "%{$v}%"))
            ->when($this->filterSupplierNit, fn ($q, $v) => $q->where('supplier_nit', 'like', "%{$v}%"))
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v));
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, self::PER_PAGE, true) ? $this->perPage : 10;

        return $this->applySort($this->baseQuery())->paginate($perPage);
    }

    #[On('product-saved')]
    public function refreshProducts(): void
    {
        unset($this->products);
    }

    protected function importer(): RowImporter
    {
        return new ProductImporter;
    }

    protected function afterImport(): void
    {
        $this->clearSelection();
        unset($this->products);
    }

    public function export(string $format)
    {
        Gate::authorize('viewAny', Product::class);

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery()),
            headings: array_column($this->columns(), 'label'),
            mapRow: fn (Product $p) => [
                $p->item, $p->description, $p->brand, $p->atc, $p->invima, $p->measure_unit,
                $p->presentation, $p->concentration, $p->pharmaceutical_form,
                $p->generic_name, $p->generic_code, $p->tax_percentage, $p->supplier_nit,
                $p->status->label(), $p->created_at->format('Y-m-d'),
            ],
        );

        return Excel::download($export, 'productos-'.now()->format('Y-m-d_His').'.'.$format);
    }

    /** Fuente única de verdad: encabezado + filtro de cada columna se renderizan desde aquí. */
    protected function columns(): array
    {
        return [
            'item' => ['label' => 'Ítem', 'width' => 'sm', 'filter' => 'filterItem'],
            'description' => ['label' => 'Descripción', 'width' => 'xl', 'filter' => 'filterDescription'],
            'brand' => ['label' => 'Marca', 'width' => 'md', 'filter' => 'filterBrand'],
            'atc' => ['label' => 'ATC', 'width' => 'sm', 'filter' => 'filterAtc'],
            'invima' => ['label' => 'INVIMA', 'width' => 'md', 'filter' => 'filterInvima'],
            'measure_unit' => ['label' => 'Unidad', 'width' => 'sm', 'filter' => 'filterMeasureUnit'],
            'presentation' => ['label' => 'Presentación', 'width' => 'md', 'filter' => 'filterPresentation'],
            'concentration' => ['label' => 'Concentración', 'width' => 'md', 'filter' => 'filterConcentration'],
            'pharmaceutical_form' => ['label' => 'Forma farm.', 'width' => 'md', 'filter' => 'filterPharmaceuticalForm'],
            'generic_name' => ['label' => 'Genérico', 'width' => 'lg', 'filter' => 'filterGenericName'],
            'generic_code' => ['label' => 'Cód. genérico', 'width' => 'sm', 'filter' => 'filterGenericCode'],
            'tax_percentage' => ['label' => '% Imp.', 'filter' => 'filterTaxPercentage'],
            'supplier_nit' => ['label' => 'NIT proveedor', 'filter' => 'filterSupplierNit'],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => ProductStatus::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>
    @php $pageIds = $this->products->pluck('id')->all(); @endphp

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Productos</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->products" :per-page-options="[10, 25, 50, 100]"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="products" :model-class="Product::class" :selected="$selected"
                                     :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                     create-event="open-product-create" edit-event="open-product-edit" edit-param="productId"
                                     duplicable importable :import-errors="$importErrors" import-key-label="Ítem" />
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$pageIds" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($pageIds)" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$pageIds" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="{{ $this->rowStatusClass($product) }}">
                        <x-table.row-checkbox :id="$product->id" :selected="$selected" />
                        <td>{{ $product->item }} @if ($product->trashed())<span class="badge bg-danger ms-1">Eliminado</span>@endif</td>
                        <td>{{ $product->description }}</td>
                        <td>{{ $product->brand ?? '-' }}</td>
                        <td>{{ $product->atc ?? '-' }}</td>
                        <td>{{ $product->invima ?? '-' }}</td>
                        <td>{{ $product->measure_unit }}</td>
                        <td>{{ $product->presentation ?? '-' }}</td>
                        <td>{{ $product->concentration ?? '-' }}</td>
                        <td>{{ $product->pharmaceutical_form ?? '-' }}</td>
                        <td>{{ $product->generic_name ?? '-' }}</td>
                        <td>{{ $product->generic_code ?? '-' }}</td>
                        <td>{{ $product->tax_percentage ?? '-' }}</td>
                        <td>{{ $product->supplier_nit ?? '-' }}</td>
                        <td><span class="badge {{ $product->status->badgeClass() }}">{{ $product->status->label() }}</span></td>
                        <td>{{ $product->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="16" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:shopping::products.product-form-modal />
</div>

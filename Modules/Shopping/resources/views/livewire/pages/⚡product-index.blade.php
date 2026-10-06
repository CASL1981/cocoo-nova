<?php

use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasRowSelection;
use App\Livewire\Concerns\HasSortableColumns;
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
use Modules\Shopping\App\Models\Product;

new #[Layout('layouts.app')] #[Title('Productos')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasRowSelection, HasBulkRowActions, Notifies;

    protected string $computedRowsProperty = 'products';
    private const PER_PAGE = [10, 25, 50, 100];

    /** Columnas filtrables por columna — un input por cada atributo de Product. */
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
    #[Session(key: 'products.index.filterStatus-{authId}')] public string $filterStatus = '';

    #[Session(key: 'products.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'products.index.sortBy-{authId}')] public string $sortBy = 'item';
    #[Session(key: 'products.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    protected function rowModelClass(): string
    {
        return Product::class;
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' || str_starts_with($property, 'filter')) {
            $this->resetPage();
            // Cambiar el filtro invalida la página actual: limpiar la
            // selección evita "editar/eliminar" un registro que ya no
            // está visible en la nueva vista filtrada.
            $this->clearSelection();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterItem', 'filterDescription', 'filterBrand', 'filterAtc', 'filterInvima',
            'filterMeasureUnit', 'filterPresentation', 'filterConcentration',
            'filterPharmaceuticalForm', 'filterGenericName', 'filterGenericCode',
            'filterTaxPercentage', 'filterStatus',
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
        // tax_percentage se filtra como texto (like), no como rango numérico:
        // es una búsqueda simple, no una necesidad de negocio de "entre X e Y".
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

    public function export(string $format)
    {
        Gate::authorize('viewAny', Product::class);

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery()),
            headings: ['Ítem', 'Descripción', 'Marca', 'ATC', 'INVIMA', 'Unidad', 'Presentación', 'Concentración', 'Forma farmacéutica', 'Genérico', 'Código genérico', '% Impuesto', 'Estado', 'Creado'],
            mapRow: fn (Product $p) => [
                $p->item, $p->description, $p->brand, $p->atc, $p->invima, $p->measure_unit,
                $p->presentation, $p->concentration, $p->pharmaceutical_form,
                $p->generic_name, $p->generic_code, $p->tax_percentage,
                $p->status->label(), $p->created_at->format('Y-m-d'),
            ],
        );

        return Excel::download($export, 'productos-'.now()->format('Y-m-d_His').'.'.$format);
    }

    /** Fuente única de verdad: encabezado + filtro de cada columna se renderizan desde aquí. */
    protected function columns(): array
    {
        return [
            'item' => ['label' => 'Ítem', 'filter' => 'filterItem'],
            'description' => ['label' => 'Descripción', 'filter' => 'filterDescription'],
            'brand' => ['label' => 'Marca', 'filter' => 'filterBrand'],
            'atc' => ['label' => 'ATC', 'filter' => 'filterAtc'],
            'invima' => ['label' => 'INVIMA', 'filter' => 'filterInvima'],
            'measure_unit' => ['label' => 'Unidad', 'filter' => 'filterMeasureUnit'],
            'presentation' => ['label' => 'Presentación', 'filter' => 'filterPresentation'],
            'concentration' => ['label' => 'Concentración', 'filter' => 'filterConcentration'],
            'pharmaceutical_form' => ['label' => 'Forma farm.', 'filter' => 'filterPharmaceuticalForm'],
            'generic_name' => ['label' => 'Genérico', 'filter' => 'filterGenericName'],
            'generic_code' => ['label' => 'Cód. genérico', 'filter' => 'filterGenericCode'],
            'tax_percentage' => ['label' => '% Imp.', 'filter' => 'filterTaxPercentage'],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => ProductStatus::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>
    @php
        $pageIds = $this->products->pluck('id')->all();
        $allPageSelected = count($pageIds) > 0 && empty(array_diff($pageIds, $selected));
    @endphp

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Productos</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->products">
                <x-slot:toolbar-left>
                    <label class="d-inline-flex align-items-center gap-2">Mostrar
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                            @foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                        </select>
                        registros
                    </label>
                </x-slot:toolbar-left>

                <x-slot:toolbar-right>
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>

                    @can('products-update')
                        <button type="button" wire:click="$dispatch('open-product-edit', { productId: {{ $selected[0] ?? 'null' }} })"
                                @disabled(! $this->singleActiveSelected()) class="btn btn-sm btn-outline-primary">Editar</button>
                    @endcan
                    @can('products-delete')
                        <button type="button" x-on:click="confirmAction({ title: 'Eliminar productos', text: '¿Eliminar {{ count($selected) }} producto(s)?' }).then(() => $wire.deleteSelected()).catch(() => {})"
                                @disabled(count($selected) === 0) class="btn btn-sm btn-danger">Eliminar</button>
                    @endcan
                    @can('products-toggle')
                        <button type="button" wire:click="toggleSelected" @disabled(count($selected) === 0)
                                class="btn btn-sm btn-outline-warning"><i class="bi bi-toggle2-on"></i></button>
                    @endcan
                    @can('products-reverse')
                        <button type="button" wire:click="restoreSelected" @disabled(count($selected) === 0)
                                class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                    @endcan
                    @can('products-read')
                        <button type="button" wire:click="$dispatch('open-userstamps-audit', { modelClass: 'Modules\\Shopping\\App\\Models\\Product', modelId: {{ $selected[0] ?? 'null' }} })"
                                @disabled(! $this->singleActiveSelected()) class="btn btn-sm btn-outline-info"><i class="bi bi-clock-history"></i></button>
                    @endcan
                    @can('products-create')
                        <button type="button" wire:click="$dispatch('open-product-create')" class="btn btn-primary btn-sm">Agregar</button>
                    @endcan
                    <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i></button>
                    <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv"></i></button>
                </x-slot:toolbar-right>

                <x-slot:head>
                    <th style="width: 2rem;">
                        <input type="checkbox" class="form-check-input" @checked($allPageSelected)
                               wire:click="toggleSelectAll({{ json_encode($pageIds) }})">
                    </th>
                    @foreach ($this->columns() as $col => $meta)
                        <th role="button" wire:click="sort('{{ $col }}')" class="text-nowrap">
                            {{ $meta['label'] }} @if ($sortBy === $col) {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                    @endforeach
                </x-slot:head>

                <x-slot:filters>
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
                </x-slot:filters>

                @forelse ($this->products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="{{ $this->rowStatusClass($product) }}">
                        <td><input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $product->id }}"></td>
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
                        <td><span class="badge {{ $product->status->badgeClass() }}">{{ $product->status->label() }}</span></td>
                        <td>{{ $product->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="15" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:shopping::products.product-form-modal />
</div>

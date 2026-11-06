<?php

use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\App\Models\NewProduct;

/**
 * Solicitudes de productos nuevos (Farmacia → Productos nuevos → Solicitudes).
 *
 * Las farmacias registran productos que nunca han existido en el inventario (nombre,
 * marca opcional, cantidad y C.C.) y los editan o eliminan mientras estén Sin asignar.
 * Se listan las solicitudes de todas las sedes para que cada farmacia vea si ya se pidió.
 */
new #[Layout('layouts.app')] #[Title('Productos nuevos')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions,
        HasColumnFilters, HasColumnVisibility, ExportsRows, Notifies;

    #[Session(key: 'new-products.index.filterId-{authId}')] public string $filterId = '';
    #[Session(key: 'new-products.index.filterName-{authId}')] public string $filterName = '';
    #[Session(key: 'new-products.index.filterBrand-{authId}')] public string $filterBrand = '';
    #[Session(key: 'new-products.index.filterDestination-{authId}')] public string $filterDestination = '';
    #[Session(key: 'new-products.index.filterDocNumber-{authId}')] public string $filterDocNumber = '';
    #[Session(key: 'new-products.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'new-products.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'new-products.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'new-products.index.sortBy-{authId}')] public string $sortBy = 'id';
    #[Session(key: 'new-products.index.sortDirection-{authId}')] public string $sortDirection = 'desc';

    protected function rowModelClass(): string { return NewProduct::class; }
    protected function computedRowsProperty(): string { return 'newProducts'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', NewProduct::class);
    }

    /** Consulta base del listado y la exportación, incluidos los eliminados. */
    protected function baseQuery(): Builder
    {
        $query = NewProduct::query()->withTrashed()
            ->with(['user:id,name,lastname', 'product:id,item,description']);

        return $this->applyColumnFilters($query);
    }

    #[Computed]
    public function newProducts(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('new-product-saved')]
    public function refreshNewProducts(): void
    {
        unset($this->newProducts);
    }

    /** Una única solicitud seleccionada que el usuario pueda editar (la Policy decide estado y sede). */
    public function singleEditableSelected(): bool
    {
        if (count($this->selected) !== 1) {
            return false;
        }

        $newProduct = NewProduct::find($this->selectedIds()[0]);

        return $newProduct !== null && Gate::allows('update', $newProduct);
    }

    public function deleteSelected(): void
    {
        $this->runBulkAction('delete', fn (NewProduct $row) => $row->delete(), 'eliminado(s)',
            'solo puedes eliminar solicitudes Sin asignar, sin ítem asociado y de tu sede (Oficina Principal puede eliminar las de cualquier sede).');
    }

    /** Las filas Rechazadas se distinguen como las inactivas del resto de listados. */
    public function rowStatusClass($row): string
    {
        if ($row->trashed()) {
            return 'table-danger';
        }

        return $row->status === NewProductStatus::Rejected ? 'table-warning' : '';
    }

    protected function exportFileName(): string { return 'productos-nuevos'; }

    protected function exportHeadings(): array
    {
        return [
            'N°', 'Producto', 'Marca', 'Cantidad', 'C.C.', 'Solicitante', 'Ítem asociado', 'Descripción del ítem',
            'Enviado', 'Fecha envío', 'TB', 'Observaciones', 'Estado', 'Creado', 'Eliminado',
        ];
    }

    /** @param NewProduct $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->id, $row->product_name, $row->brand, $row->quantity, $row->destination_code, $row->user?->fullName(),
            $row->product?->item, $row->product?->description, $row->send_quantity, $row->date_send?->format('Y-m-d'),
            $row->doc_number, $row->observations, $row->status->label(), $row->created_at->format('Y-m-d'),
            $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y orden de cada columna. */
    protected function columns(): array
    {
        return [
            'id' => ['label' => 'N°', 'filter' => 'filterId'],
            'product_name' => ['label' => 'Producto', 'width' => 'xl', 'filter' => 'filterName'],
            'brand' => ['label' => 'Marca', 'width' => 'md', 'filter' => 'filterBrand'],
            'quantity' => ['label' => 'Cant.', 'filter' => null],
            'destination_code' => ['label' => 'C.C.', 'filter' => 'filterDestination'],
            'user_id' => ['label' => 'Solicitante', 'width' => 'md', 'filter' => null],
            'product_id' => ['label' => 'Ítem asociado', 'width' => 'lg', 'filter' => null],
            'send_quantity' => ['label' => 'Enviado', 'filter' => null],
            'doc_number' => ['label' => 'TB', 'width' => 'sm', 'filter' => 'filterDocNumber'],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => NewProductStatus::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Productos nuevos</h4></div>

        <div class="card-body">
            <p class="small text-muted mb-2">
                Solicite aquí solo productos que nunca han existido en el inventario. Si el producto ya tiene ítem, solicítelo en Pendientes.
            </p>

            <x-data-table :paginator="$this->newProducts" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="new-products" :model-class="NewProduct::class" :selected="$selected"
                                     :single-active="$this->singleEditableSelected()" :toggle="false" :selected-count="$this->selectedCount()"
                                     create-event="open-new-product-create" edit-event="open-new-product-edit" edit-param="newProductId" />
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->newProducts->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->newProducts->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->newProducts->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->newProducts as $newProduct)
                    <tr wire:key="new-product-{{ $newProduct->id }}" class="{{ $this->rowStatusClass($newProduct) }}">
                        <x-table.row-checkbox :id="$newProduct->id" :selected="$selected" />
                        <td>{{ $newProduct->id }} @if ($newProduct->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>{{ $newProduct->product_name }}</td>
                        <td>{{ $newProduct->brand ?? '-' }}</td>
                        <td class="text-end">{{ number_format($newProduct->quantity) }}</td>
                        <td>{{ $newProduct->destination_code }}</td>
                        <td>{{ $newProduct->user?->fullName() }}</td>
                        <td title="{{ $newProduct->product?->description }}">{{ $newProduct->product?->item ?? '-' }}</td>
                        <td class="text-end">{{ $newProduct->send_quantity ?? '-' }}</td>
                        <td>{{ $newProduct->doc_number ?? '-' }}</td>
                        <td><span class="badge {{ $newProduct->status->badgeClass() }}">{{ $newProduct->status->label() }}</span></td>
                        <td>{{ $newProduct->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:pharmacy::new-products.new-product-form-modal />
</div>

<?php

use App\Contracts\RowImporter;
use App\Enums\ServicePointStatus;
use App\Livewire\Concerns\ImportsRows;
use App\Livewire\Concerns\Notifies;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Pharmacy\App\Actions\SavePendingItem;
use Modules\Pharmacy\App\Imports\PendingItemImporter;
use Modules\Pharmacy\App\Livewire\Forms\PendingItemForm;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/**
 * Detalle de un pendiente: datos del encabezado e ítems.
 *
 * Los ítems se agregan y editan en el formulario en línea mientras el pendiente
 * esté Activo, o se importan desde Excel/CSV (permiso pendings-import); la asignación
 * de cantidades enviadas y TB vive en otra pantalla.
 */
new #[Layout('layouts.app')] #[Title('Ítems del pendiente')] class extends Component
{
    use ImportsRows, Notifies;

    #[Locked]
    public Pending $pending;

    public PendingItemForm $form;

    // Textos de los buscadores; el valor real vive en el formulario.
    public string $productSearch = '';

    public string $destinationSearch = '';

    public function mount(Pending $pending): void
    {
        Gate::authorize('view', $pending);

        $this->pending = $pending->load(['destination:id,code,name', 'user.servicePoint:id,code,name', 'user.role:id,name', 'user.process:id,name']);
        $this->resetItemForm();
    }

    /** Ítems del pendiente en orden de registro. */
    #[Computed]
    public function items(): Collection
    {
        // El encabezado ya está cargado: se asigna a cada ítem para que la Policy no lo consulte por fila.
        return $this->pending->items()->with('destination:id,code,name')->orderBy('id')->get()
            ->each(fn (PendingItem $item) => $item->setRelation('pending', $this->pending));
    }

    /** El usuario puede agregar ítems: pendiente activo, permiso de editar y regla de sede. */
    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('create', [PendingItem::class, $this->pending]);
    }

    /** Solo Oficina Principal puede registrar un C.C. distinto al del encabezado. */
    #[Computed]
    public function choosesDestination(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->worksAtHeadquarters();
    }

    /**
     * Genéricos activos cuyo nombre contiene el texto (mínimo 3 caracteres), sin repetir.
     *
     * El catálogo tiene varias presentaciones por genérico: se agrupa por nombre + código
     * (el código es el que se cruza con los agotados) y el valor es el producto representativo
     * (menor id). Si un nombre tiene varios códigos se muestra el código para distinguirlos.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function productResults(): array
    {
        $term = trim($this->productSearch);

        if (mb_strlen($term) < 3 || $this->form->product_id !== '') {
            return [];
        }

        $generics = Product::query()
            ->where('status', ProductStatus::Active)
            ->where('generic_name', 'like', "%{$term}%")
            ->selectRaw('MIN(id) as id, generic_name, generic_code')
            ->groupBy('generic_name', 'generic_code')
            ->orderBy('generic_name')
            ->orderBy('generic_code')
            ->limit(20)
            ->get();

        $repeatedNames = $generics->countBy('generic_name');

        return $generics
            ->mapWithKeys(fn (Product $generic) => [$generic->id => $repeatedNames[$generic->generic_name] > 1
                ? "{$generic->generic_name} · Cód. {$generic->generic_code}"
                : $generic->generic_name])
            ->all();
    }

    /**
     * Sedes activas por código o nombre, para Oficina Principal.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function destinationResults(): array
    {
        $term = trim($this->destinationSearch);

        if ($term === '' || ! $this->choosesDestination) {
            return [];
        }

        return ServicePoint::query()->search($term)
            ->where('status', ServicePointStatus::Active)
            ->orderBy('code')
            ->limit(20)
            ->get(['code', 'name'])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->code => "{$point->code} - {$point->name}"])
            ->all();
    }

    /** Escribir de nuevo en el buscador descarta el producto elegido. */
    public function updatedProductSearch(): void
    {
        $this->form->product_id = '';
    }

    /** El C.C. también se puede escribir directamente: el texto es el código. */
    public function updatedDestinationSearch(string $value): void
    {
        $this->form->destination_code = trim($value);
    }

    public function selectProduct(string $productId): void
    {
        $product = Product::query()->where('status', ProductStatus::Active)->findOrFail($productId);

        $this->form->product_id = (string) $product->id;
        $this->productSearch = $product->generic_name;
    }

    public function selectDestination(string $code): void
    {
        $this->form->destination_code = $code;
        $this->destinationSearch = $code;
    }

    /** Agrega o actualiza el ítem del formulario. */
    public function save(SavePendingItem $action): void
    {
        $item = $this->form->item;

        $item
            ? Gate::authorize('update', $item)
            : Gate::authorize('create', [PendingItem::class, $this->pending]);

        $action->execute($this->form->payload(), $this->pending, $item);

        $this->toastSuccess($item ? 'Ítem actualizado' : 'Ítem agregado');
        $this->resetItemForm();
        unset($this->items);
    }

    public function editItem(int $itemId): void
    {
        $item = $this->pending->items()->findOrFail($itemId);
        Gate::authorize('update', $item);

        $this->form->setItem($item);
        $this->productSearch = $item->generic_name;
        $this->destinationSearch = $item->destination_code;
    }

    public function deleteItem(int $itemId): void
    {
        $item = $this->pending->items()->findOrFail($itemId);
        Gate::authorize('delete', $item);

        $item->delete();

        if ($this->form->item?->is($item)) {
            $this->resetItemForm();
        }

        $this->toastSuccess('Ítem eliminado');
        unset($this->items);
    }

    /** Los ítems importados quedan en el pendiente abierto, con el usuario como responsable de la carga. */
    protected function importer(): RowImporter
    {
        /** @var User $user */
        $user = Auth::user();

        return new PendingItemImporter($this->pending, $user);
    }

    /** Importar exige el permiso pendings-import y poder agregar ítems a este pendiente. */
    protected function authorizeImport(): void
    {
        Gate::authorize('import', Pending::class);
        Gate::authorize('create', [PendingItem::class, $this->pending]);
    }

    protected function afterImport(): void
    {
        $this->resetItemForm();
        unset($this->items);
    }

    public function cancelEdit(): void
    {
        $this->resetItemForm();
    }

    private function resetItemForm(): void
    {
        $this->form->setPending($this->pending);
        $this->productSearch = '';
        $this->destinationSearch = $this->form->destination_code;
    }
};
?>

<div x-data>

    {{-- Encabezado del pendiente --}}
    <x-document.header :title="'Pendiente N° '.$pending->id" icon="bi-box-seam"
                       :subtitle="'Creado el '.$pending->created_at->format('Y/m/d')"
                       :status="$pending->status->label()" :status-class="$pending->status->badgeClass()"
                       :back="route('pendings.index')">
        <x-document.section title="Solicitante" icon="bi-person">
            <x-document.field label="Nombre" :value="$pending->user?->fullName()" />
            <x-document.field label="Proceso" :value="$pending->user?->process?->name" />
            <x-document.field label="Sede" :value="$pending->user?->servicePoint ? $pending->user->servicePoint->code.' - '.$pending->user->servicePoint->name : null" />
            <x-document.field label="Email" :value="$pending->user?->email" class="text-break" />
        </x-document.section>

        <x-document.section title="Pendiente" icon="bi-clipboard-check">
            <x-document.field label="Tipo" :value="$pending->type->label()" />
            <x-document.field label="Categoría" :value="$pending->category" />
            <x-document.field label="Destino" :value="$pending->destination ? $pending->destination->code.' - '.$pending->destination->name : null" />
            <x-document.field label="EPS" :value="$pending->eps" />
            <x-document.field label="Motivo" :value="$pending->reason" />
        </x-document.section>

        <x-document.section title="Contrato" icon="bi-file-earmark-text">
            <x-document.field label="Modalidad" :value="$pending->contracting_modality" />
            <x-document.field label="Facturación" :value="$pending->invoicing_method" />
            <x-document.field label="Autoriza" :value="$pending->manager" />
        </x-document.section>
    </x-document.header>

    {{-- Ítems --}}
    <x-document.lines title="Ítems" icon="bi-list-check" :editing="(bool) $form->item"
                      :entry-title="$form->item ? 'Editar ítem' : 'Agregar ítem'"
                      :entry-icon="$form->item ? 'bi-pencil-square' : 'bi-plus-circle'">
        @if ($this->canManage)
            <x-slot:actions>
                <x-table.import module="pendings" :errors-list="$importErrors" />
            </x-slot:actions>

            <x-slot:entry>
                <form wire:submit="save" class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        @if ($form->item)
                            {{-- El producto no se edita: si es otro, se elimina el ítem y se agrega el correcto. --}}
                            <x-forms.field id="item-product" label="Producto" hint="Para cambiarlo, elimine el ítem y agregue el correcto.">
                                <input id="item-product" type="text" class="form-control" value="{{ $form->item->generic_name }}" readonly
                                       title="El producto no se puede cambiar; elimine el ítem y agregue el correcto">
                            </x-forms.field>
                        @else
                            <x-forms.search-select name="form.product_id" label="Producto" required search="productSearch"
                                                   select="selectProduct" :results="$this->productResults"
                                                   placeholder="Buscar por nombre genérico (mín. 3 letras)" />
                        @endif
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <x-forms.input name="form.brand" label="Marca" id="item-brand" />
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <x-forms.input name="form.quantity" label="Cantidad" required type="number" min="1" id="item-quantity" />
                    </div>
                    <div class="col-lg-2 col-md-4">
                        @if ($this->choosesDestination)
                            <x-forms.search-select name="form.destination_code" label="C.C." required search="destinationSearch"
                                                   select="selectDestination" :results="$this->destinationResults"
                                                   placeholder="Código o nombre" />
                        @else
                            <x-forms.field id="item-destination" name="form.destination_code" label="C.C.">
                                <input id="item-destination" type="text" class="form-control" value="{{ $form->destination_code }}" readonly>
                            </x-forms.field>
                        @endif
                    </div>
                    <div class="col-lg-2 document-entry-actions">
                        <button type="submit" class="btn btn-primary app-modal-submit" wire:loading.attr="disabled" wire:target="save">
                            <i @class(['bi', 'bi-check2' => $form->item, 'bi-plus-lg' => ! $form->item]) wire:loading.remove wire:target="save" aria-hidden="true"></i>
                            <span class="spinner-border spinner-border-sm" wire:loading wire:target="save" aria-hidden="true"></span>
                            {{ $form->item ? 'Guardar' : 'Agregar' }}
                        </button>
                        @if ($form->item)
                            <button type="button" wire:click="cancelEdit" class="btn btn-light">Cancelar</button>
                        @endif
                    </div>
                </form>
            </x-slot:entry>
        @elseif (! $pending->isActive())
            <x-slot:notice>
                <div class="alert alert-info d-flex align-items-center gap-2 py-2">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    El pendiente está terminado: sus ítems ya no se pueden agregar ni editar.
                </div>
            </x-slot:notice>
        @endif

        <div class="table-responsive table-scroll-10 border rounded document-lines-table" wire:loading.class="opacity-50" wire:target="save,deleteItem">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="col-fit">#</th>
                        <th>Producto</th>
                        <th class="col-fit">Cód. genérico</th>
                        <th>Marca</th>
                        <th class="col-fit text-end">Cantidad</th>
                        <th class="col-fit">C.C.</th>
                        <th class="col-fit">Estado</th>
                        <th class="col-fit">Fecha</th>
                        <th class="col-fit text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->items as $item)
                        <tr wire:key="pending-item-{{ $item->id }}" @class(['document-line-editing' => $form->item?->id === $item->id])>
                            <td class="text-body-secondary">{{ $loop->iteration }}</td>
                            <td class="document-line-product">{{ $item->generic_name }}</td>
                            <td>{{ $item->generic_code }}</td>
                            <td>{{ $item->brand ?? '-' }}</td>
                            <td class="text-end document-line-number">{{ number_format($item->quantity) }}</td>
                            <td title="{{ $item->destination?->name }}">{{ $item->destination_code }}</td>
                            <td class="text-nowrap">
                                <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                                @if ($item->circular_number)<small class="d-block text-body-secondary mt-1">Circular {{ $item->circular_number }}</small>@endif
                            </td>
                            <td class="text-nowrap">{{ $item->created_at->format('Y/m/d') }}</td>
                            <td class="text-end">
                                <div class="table-actions-group">
                                    @can('update', $item)
                                        <x-tooltip text="Editar ítem">
                                            <button type="button" wire:click="editItem({{ $item->id }})" class="btn btn-sm btn-outline-primary btn-table-icon" aria-label="Editar ítem">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </x-tooltip>
                                    @endcan
                                    @can('delete', $item)
                                        <x-tooltip text="Eliminar ítem">
                                            <button type="button" aria-label="Eliminar ítem" class="btn btn-sm btn-outline-danger btn-table-icon"
                                                    x-on:click="confirmAction({ title: 'Eliminar ítem', text: '¿Eliminar este ítem del pendiente?' }).then(() => $wire.deleteItem({{ $item->id }})).catch(() => {})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </x-tooltip>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-document.empty-row colspan="9" text="El pendiente aún no tiene ítems"
                                              :hint="$this->canManage ? 'Agréguelos con el formulario o impórtelos desde Excel.' : null" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-document.lines>
</div>

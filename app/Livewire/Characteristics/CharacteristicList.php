<?php

namespace App\Livewire\Characteristics;

use App\Exports\GenericExport;
use App\Livewire\Concerns\DuplicatesRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Characteristic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Panel izquierdo de /characteristics: lista de Characteristic.
 * Al hacer clic en una fila, avisa al panel derecho (CharacteristicOptionList)
 * cuál característica mostrar, vía evento 'characteristic-selected'.
 */
class CharacteristicList extends Component
{
    use DuplicatesRows, HasAuthScopedSession, HasBulkRowActions, HasSortableColumns, Notifies, WithPagination;

    private const PER_PAGE = [10, 25, 50];

    #[Session(key: 'characteristics.list.filterName-{authId}')]
    public string $filterName = '';

    #[Session(key: 'characteristics.list.perPage-{authId}')]
    public int $perPage = 10;

    #[Session(key: 'characteristics.list.sortBy-{authId}')]
    public string $sortBy = 'name';

    #[Session(key: 'characteristics.list.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    public ?int $activeCharacteristicId = null;

    protected function rowModelClass(): string
    {
        return Characteristic::class;
    }

    protected function computedRowsProperty(): string
    {
        return 'characteristics';
    }

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['name', 'code', 'status'];
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', Characteristic::class);
    }

    public function updatedFilterName(): void
    {
        $this->resetPage();
    }

    /** El usuario hace clic en una fila: avisa al panel de opciones y resalta la fila activa. */
    public function selectCharacteristic(int $id): void
    {
        $this->activeCharacteristicId = $id;
        $this->dispatch('characteristic-selected', characteristicId: $id);
    }

    /** @return LengthAwarePaginator<int, Characteristic> */
    #[Computed]
    public function characteristics(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, self::PER_PAGE, true) ? $this->perPage : 10;

        return $this->applySort($this->baseQuery())->paginate($perPage);
    }

    #[On('characteristic-saved')]
    public function refresh(): void
    {
        unset($this->characteristics);
    }

    /**
     * Consulta base compartida por el listado y la exportación.
     * Incluye eliminadas para mostrarlas resaltadas y permitir restaurarlas.
     */
    /** @return Builder<Characteristic> */
    private function baseQuery(): Builder
    {
        return Characteristic::withTrashed()->search($this->filterName);
    }

    /**
     * Al marcar exactamente una característica, la activa también en el panel de opciones,
     * para que el usuario no tenga que hacer clic adicional sobre la fila.
     */
    public function updatedSelected(): void
    {
        if (count($this->selected) === 1) {
            $this->selectCharacteristic((int) $this->selected[0]);
        }
    }

    /**
     * Exporta el listado tal como se ve en pantalla (filtros y orden incluidos).
     */
    public function export(string $format): BinaryFileResponse
    {
        Gate::authorize('viewAny', Characteristic::class);

        // El formato llega desde el cliente: solo se aceptan los soportados.
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery()),
            headings: ['Nombre', 'Código', 'Estado', 'Eliminado', 'Creado'],
            mapRow: fn (Characteristic $c) => [
                $c->name, $c->code, $c->status->label(),
                $c->trashed() ? 'Sí' : 'No', $c->created_at->format('Y-m-d'),
            ],
        );

        return Excel::download($export, 'caracteristicas-'.now()->format('Y-m-d_His').'.'.$format);
    }

    public function render(): View
    {
        return view('livewire.characteristics.characteristic-list');
    }
}

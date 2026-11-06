<?php

namespace App\Livewire\Characteristics;

use App\Exports\GenericExport;
use App\Livewire\Concerns\DuplicatesRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Panel derecho de /characteristics: opciones de la Characteristic activa.
 * $characteristicId llega por el evento 'characteristic-selected' que
 * dispara CharacteristicList — no tiene Policy ni ruta propia, vive
 * siempre dentro del contexto de una característica seleccionada.
 */
class CharacteristicOptionList extends Component
{
    use DuplicatesRows, HasAuthScopedSession, HasBulkRowActions, HasSortableColumns, Notifies, WithPagination;

    // Característica cuyas opciones se muestran; solo la asigna el servidor vía evento.
    #[Locked]
    public ?int $characteristicId = null;

    // Búsqueda por detalle de la opción, persistida por usuario; aplica al listado y a la exportación.
    #[Session(key: 'characteristics.options.filterName-{authId}')]
    public string $filterName = '';

    // Orden del listado de opciones, persistido por usuario (requerido por HasSortableColumns).
    #[Session(key: 'characteristics.options.sortBy-{authId}')]
    public string $sortBy = 'name';

    #[Session(key: 'characteristics.options.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    protected function rowModelClass(): string
    {
        return CharacteristicOption::class;
    }

    protected function computedRowsProperty(): string
    {
        return 'options';
    }

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['name', 'status'];
    }

    #[On('characteristic-selected')]
    public function setCharacteristic(int $characteristicId): void
    {
        $this->characteristicId = $characteristicId;
        // La búsqueda pertenece a la característica anterior: se limpia al cambiar.
        $this->filterName = '';
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedFilterName(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    /** @return LengthAwarePaginator<int, CharacteristicOption>|null */
    #[Computed]
    public function options(): ?LengthAwarePaginator
    {
        if (! $this->characteristicId) {
            return null;
        }

        return $this->applySort($this->baseQuery())->paginate(10);
    }

    #[On('characteristic-option-saved')]
    public function refresh(): void
    {
        unset($this->options);
    }

    /**
     * Opciones de la característica activa, incluidas las eliminadas, filtradas por la búsqueda.
     */
    /** @return Builder<CharacteristicOption> */
    private function baseQuery(): Builder
    {
        return CharacteristicOption::withTrashed()
            ->where('characteristic_id', $this->characteristicId)
            ->search($this->filterName);
    }

    /**
     * Exporta las opciones de la característica activa con el orden en pantalla.
     */
    public function export(string $format): BinaryFileResponse
    {
        Gate::authorize('viewAny', CharacteristicOption::class);
        abort_unless($this->characteristicId && in_array($format, ['xlsx', 'csv'], true), 404);

        // El código de la característica identifica el archivo descargado.
        $code = Characteristic::withTrashed()->whereKey($this->characteristicId)->value('code');

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery()),
            headings: ['Detalle', 'Estado', 'Eliminado', 'Creado'],
            mapRow: fn (CharacteristicOption $o) => [
                $o->name, $o->status->label(),
                $o->trashed() ? 'Sí' : 'No', $o->created_at->format('Y-m-d'),
            ],
        );

        return Excel::download($export, "opciones-{$code}-".now()->format('Y-m-d_His').'.'.$format);
    }

    /**
     * Indica si la característica activa está eliminada, para bloquear la edición de sus opciones.
     */
    #[Computed]
    public function parentTrashed(): bool
    {
        return $this->characteristicId !== null
            && Characteristic::onlyTrashed()->whereKey($this->characteristicId)->exists();
    }

    public function render(): View
    {
        return view('livewire.characteristics.characteristic-option-list');
    }
}

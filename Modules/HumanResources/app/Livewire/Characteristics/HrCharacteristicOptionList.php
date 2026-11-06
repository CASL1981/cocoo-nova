<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Livewire\Characteristics;

use App\Enums\Status;
use App\Livewire\Concerns\DuplicatesRows;
use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Process;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * Panel derecho de Talento Humano → Características: opciones de la característica elegida.
 * En Cargos muestra además el proceso y el jefe inmediato de cada cargo.
 */
final class HrCharacteristicOptionList extends Component
{
    use DuplicatesRows, ExportsRows, HasAuthScopedSession, HasBulkRowActions, HasColumnFilters, HasColumnVisibility,
        HasSortableColumns, Notifies, WithPagination;

    // Característica cuyas opciones se muestran; solo la asigna el servidor por evento.
    #[Locked]
    public ?int $characteristicId = null;

    #[Session(key: 'hr-characteristics.options.filterName-{authId}')]
    public string $filterName = '';

    #[Session(key: 'hr-characteristics.options.filterProcess-{authId}')]
    public string $filterProcess = '';

    #[Session(key: 'hr-characteristics.options.filterStatus-{authId}')]
    public string $filterStatus = '';

    #[Session(key: 'hr-characteristics.options.perPage-{authId}')]
    public int $perPage = 10;

    /** @var list<string> */
    #[Session(key: 'hr-characteristics.options.hiddenColumns-{authId}')]
    public array $hiddenColumns = [];

    #[Session(key: 'hr-characteristics.options.sortBy-{authId}')]
    public string $sortBy = 'name';

    #[Session(key: 'hr-characteristics.options.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        Gate::authorize('viewAny', HrCharacteristicOption::class);
    }

    protected function rowModelClass(): string
    {
        return HrCharacteristicOption::class;
    }

    protected function computedRowsProperty(): string
    {
        return 'options';
    }

    #[On('hr-characteristic-selected')]
    public function setCharacteristic(int $characteristicId): void
    {
        $this->characteristicId = $characteristicId;
        // Los filtros pertenecen a la característica anterior.
        $this->filterProcess = '';
        unset($this->characteristic);
        $this->resetFilters();
    }

    /** Característica activa (incluida si está eliminada, para avisarlo). */
    #[Computed]
    public function characteristic(): ?HrCharacteristic
    {
        return $this->characteristicId === null ? null : HrCharacteristic::withTrashed()->find($this->characteristicId);
    }

    /** La característica activa está eliminada: sus opciones no se gestionan hasta restaurarla. */
    #[Computed]
    public function parentTrashed(): bool
    {
        return (bool) $this->characteristic?->trashed();
    }

    /** @return Builder<HrCharacteristicOption> */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(HrCharacteristicOption::query()->withTrashed()
            ->with(['process', 'parent'])
            ->where('hr_characteristic_id', $this->characteristicId));
    }

    /** Proceso y jefe inmediato no se ordenan por su id. */
    protected function sortableColumns(): array
    {
        return ['name', 'status'];
    }

    #[Computed]
    public function options(): ?LengthAwarePaginator
    {
        if ($this->characteristicId === null) {
            return null;
        }

        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('hr-characteristic-option-saved')]
    public function refreshOptions(): void
    {
        unset($this->options);
    }

    protected function exportFileName(): string
    {
        return 'opciones-'.($this->characteristic->code ?? 'talento-humano');
    }

    protected function exportHeadings(): array
    {
        return array_values([...array_column($this->columns(), 'label'), 'Eliminado']);
    }

    /** @param HrCharacteristicOption $row */
    protected function exportRow(Model $row): array
    {
        $values = [$row->name];

        if ($this->isPositions()) {
            $values[] = $row->process?->name;
            $values[] = $row->parent?->name;
        }

        return [...$values, $row->status->label(), $row->trashed() ? 'Sí' : 'No'];
    }

    public function isPositions(): bool
    {
        return (bool) $this->characteristic?->isPositions();
    }

    protected function columns(): array
    {
        $columns = ['name' => ['label' => 'Nombre', 'width' => 'lg', 'filter' => 'filterName', 'hideable' => false]];

        if ($this->isPositions()) {
            $columns['process_id'] = ['label' => 'Proceso', 'filter' => 'filterProcess', 'type' => 'select',
                'options' => Process::query()->orderBy('name')->pluck('name', 'id')->all()];
            $columns['parent_id'] = ['label' => 'Jefe inmediato', 'width' => 'md', 'filter' => null];
        }

        return [...$columns, 'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => Status::options()]];
    }

    public function render(): View
    {
        return view('humanresources::livewire.characteristics.hr-characteristic-option-list');
    }
}

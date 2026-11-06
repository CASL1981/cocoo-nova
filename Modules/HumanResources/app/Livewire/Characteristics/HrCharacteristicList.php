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
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * Panel izquierdo de Talento Humano → Características. Al elegir una fila avisa al panel de
 * opciones (HrCharacteristicOptionList) con el evento 'hr-characteristic-selected'.
 */
final class HrCharacteristicList extends Component
{
    use DuplicatesRows, ExportsRows, HasAuthScopedSession, HasBulkRowActions, HasColumnFilters, HasColumnVisibility,
        HasSortableColumns, Notifies, WithPagination;

    #[Session(key: 'hr-characteristics.list.filterName-{authId}')]
    public string $filterName = '';

    #[Session(key: 'hr-characteristics.list.filterCode-{authId}')]
    public string $filterCode = '';

    #[Session(key: 'hr-characteristics.list.filterStatus-{authId}')]
    public string $filterStatus = '';

    #[Session(key: 'hr-characteristics.list.perPage-{authId}')]
    public int $perPage = 10;

    /** @var list<string> */
    #[Session(key: 'hr-characteristics.list.hiddenColumns-{authId}')]
    public array $hiddenColumns = [];

    #[Session(key: 'hr-characteristics.list.sortBy-{authId}')]
    public string $sortBy = 'name';

    #[Session(key: 'hr-characteristics.list.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    // Característica cuyas opciones se ven a la derecha (resaltada en la tabla).
    public ?int $activeCharacteristicId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', HrCharacteristic::class);
    }

    protected function rowModelClass(): string
    {
        return HrCharacteristic::class;
    }

    protected function computedRowsProperty(): string
    {
        return 'characteristics';
    }

    /** El usuario elige una fila: avisa al panel de opciones y la resalta. */
    public function selectCharacteristic(int $id): void
    {
        $this->activeCharacteristicId = $id;
        $this->dispatch('hr-characteristic-selected', characteristicId: $id);
    }

    /** Al marcar exactamente una característica también se activa en el panel de opciones. */
    public function updatedSelected(): void
    {
        if (count($this->selected) === 1) {
            $this->selectCharacteristic((int) $this->selected[0]);
        }
    }

    /** @return Builder<HrCharacteristic> */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(HrCharacteristic::query()->withTrashed());
    }

    #[Computed]
    public function characteristics(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('hr-characteristic-saved')]
    public function refreshCharacteristics(): void
    {
        unset($this->characteristics);
    }

    protected function exportFileName(): string
    {
        return 'caracteristicas-talento-humano';
    }

    protected function exportHeadings(): array
    {
        return ['Nombre', 'Código', 'Estado', 'Eliminado'];
    }

    /** @param HrCharacteristic $row */
    protected function exportRow(Model $row): array
    {
        return [$row->name, $row->code, $row->status->label(), $row->trashed() ? 'Sí' : 'No'];
    }

    protected function columns(): array
    {
        return [
            'name' => ['label' => 'Nombre', 'width' => 'md', 'filter' => 'filterName', 'hideable' => false],
            'code' => ['label' => 'Código', 'filter' => 'filterCode'],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => Status::options()],
        ];
    }

    public function render(): View
    {
        return view('humanresources::livewire.characteristics.hr-characteristic-list');
    }
}

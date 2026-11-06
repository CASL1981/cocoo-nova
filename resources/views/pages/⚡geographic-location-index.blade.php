<?php

use App\Contracts\RowImporter;
use App\Enums\GeographicLevel;
use App\Enums\Status;
use App\Imports\GeographicLocationImporter;
use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\ImportsRows;
use App\Livewire\Concerns\Notifies;
use App\Models\GeographicLocation;
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

/**
 * Configuración → Ubicación geográfica: países, departamentos y ciudades en una sola tabla.
 */
new #[Layout('layouts.app')] #[Title('Ubicación geográfica')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions, HasColumnVisibility,
        ExportsRows, ImportsRows, Notifies;
    use HasColumnFilters {
        applyColumnFilters as applyStandardColumnFilters;
    }

    #[Session(key: 'geographic-locations.index.filterLevel-{authId}')] public string $filterLevel = '';
    #[Session(key: 'geographic-locations.index.filterCode-{authId}')] public string $filterCode = '';
    #[Session(key: 'geographic-locations.index.filterName-{authId}')] public string $filterName = '';
    #[Session(key: 'geographic-locations.index.filterParent-{authId}')] public string $filterParent = '';
    #[Session(key: 'geographic-locations.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'geographic-locations.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'geographic-locations.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'geographic-locations.index.sortBy-{authId}')] public string $sortBy = 'name';
    #[Session(key: 'geographic-locations.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    protected function rowModelClass(): string { return GeographicLocation::class; }
    protected function computedRowsProperty(): string { return 'locations'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', GeographicLocation::class);
    }

    /**
     * Consulta base del listado y la exportación, incluidas las eliminadas.
     * "Pertenece a" busca en el nombre del padre y del abuelo (ej. "Colombia" trae sus ciudades).
     */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(GeographicLocation::query()->withTrashed()->with('parent.parent'));
    }

    /**
     * @param  Builder<GeographicLocation>  $query
     * @return Builder<GeographicLocation>
     */
    protected function applyColumnFilters(Builder $query): Builder
    {
        $parent = $this->filterParent;
        $this->filterParent = '';
        $query = $this->applyStandardColumnFilters($query);
        $this->filterParent = $parent;

        if ($parent !== '') {
            $query->whereHas('parent', fn (Builder $inner) => $inner->withTrashed()
                ->where(fn (Builder $names) => $names->where('name', 'like', "%{$parent}%")
                    ->orWhereHas('parent', fn (Builder $grand) => $grand->withTrashed()->where('name', 'like', "%{$parent}%"))));
        }

        return $query;
    }

    /** "Pertenece a" se ordena por el nombre del padre. */
    protected function applySort(Builder $query): Builder
    {
        if ($this->sortBy !== 'parent') {
            $columns = $this->sortableColumns();
            $column = in_array($this->sortBy, $columns, true) ? $this->sortBy : 'name';

            return $query->orderBy($column, $this->sortDirection === 'desc' ? 'desc' : 'asc')->orderBy('id');
        }

        return $query->orderBy(
            GeographicLocation::withTrashed()->select('name')->from('geographic_locations as parents')
                ->whereColumn('parents.id', 'geographic_locations.parent_id'),
            $this->sortDirection === 'desc' ? 'desc' : 'asc'
        )->orderBy('name');
    }

    #[Computed]
    public function locations(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('geographic-location-saved')]
    public function refreshLocations(): void
    {
        unset($this->locations);
    }

    protected function importer(): RowImporter
    {
        return new GeographicLocationImporter;
    }

    protected function afterImport(): void
    {
        $this->clearSelection();
        unset($this->locations);
    }

    protected function exportFileName(): string { return 'ubicacion-geografica'; }

    protected function exportHeadings(): array
    {
        return ['Nivel', 'Código', 'Nombre', 'Código del padre', 'Pertenece a', 'Población', 'Estado', 'Eliminado'];
    }

    /** @param GeographicLocation $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->level->label(), $row->code, $row->name, $row->parent?->code, $row->parentPath(),
            $row->population, $row->status->label(), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y orden de cada columna. */
    protected function columns(): array
    {
        return [
            'level' => ['label' => 'Nivel', 'filter' => 'filterLevel', 'type' => 'select', 'options' => GeographicLevel::options()],
            'code' => ['label' => 'Código', 'filter' => 'filterCode'],
            'name' => ['label' => 'Nombre', 'width' => 'lg', 'filter' => 'filterName', 'hideable' => false],
            'parent' => ['label' => 'Pertenece a', 'width' => 'lg', 'filter' => 'filterParent'],
            'population' => ['label' => 'Población', 'filter' => null],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => Status::options()],
        ];
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Ubicación geográfica</h4>
            <p class="text-muted small mb-0">Países, departamentos y ciudades que usan los formularios de la aplicación.</p>
        </div>

        <div class="card-body">
            <x-data-table :paginator="$this->locations" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="geographic-locations" :model-class="GeographicLocation::class" :selected="$selected"
                                     :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                     create-event="open-geographic-location-create" edit-event="open-geographic-location-edit" edit-param="locationId"
                                     importable :import-errors="$importErrors" import-key-label="Código" />
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->locations->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->locations->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->locations->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->locations as $location)
                    <tr wire:key="geographic-location-{{ $location->id }}" class="{{ $this->rowStatusClass($location) }}">
                        <x-table.row-checkbox :id="$location->id" :selected="$selected" />
                        <td><span class="badge {{ $location->level->badgeClass() }}">{{ $location->level->label() }}</span></td>
                        <td>{{ $location->code }} @if ($location->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>{{ $location->name }}</td>
                        <td>{{ $location->parentPath() ?: '-' }}</td>
                        <td class="text-end">{{ $location->population !== null ? number_format($location->population, 0, ',', '.') : '-' }}</td>
                        <td><span class="badge {{ $location->status->badgeClass() }}">{{ $location->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:geographic-locations.geographic-location-form-modal />
</div>

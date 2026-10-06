<?php

use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\ServicePoint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Exports\GenericExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Listado de sedes/servicios. Mismo esqueleto que role-index/user-index:
 * paginación, orden por columna y filtros persistidos en sesión por usuario.
 */
new #[Layout('layouts.app')] #[Title('Sedes y Servicios')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, Notifies;

    private const PER_PAGE = [10, 25, 50, 100];

    #[Session(key: 'service-points.index.perPage-{authId}')]
    public int $perPage = 10;

    #[Session(key: 'service-points.index.filterName-{authId}')]
    public string $filterName = '';

    #[Session(key: 'service-points.index.sortBy-{authId}')]
    public string $sortBy = 'code';

    #[Session(key: 'service-points.index.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        Gate::authorize('viewAny', ServicePoint::class);
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' || str_starts_with($property, 'filter')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterName']);
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return ['id', 'code', 'name', 'type', 'status', 'created_at'];
    }

    #[Computed]
    public function servicePoints(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, self::PER_PAGE, true) ? $this->perPage : 10;

        $query = ServicePoint::query()->search($this->filterName);

        return $this->applySort($query)->paginate($perPage);
    }

    #[On('service-point-saved')]
    public function refreshServicePoints(): void
    {
        unset($this->servicePoints);
    }

    public function delete(int $id): void
    {
        $servicePoint = ServicePoint::findOrFail($id);
        Gate::authorize('delete', $servicePoint);

        if (\App\Models\User::where('service_point_id', $servicePoint->id)->exists()) {
            $this->toastError('No puedes eliminar un centro de costos con usuarios asignados.');
            return;
        }

        $servicePoint->delete();
        $this->toastSuccess('Centro de costos eliminado correctamente');
    }

    /**
     * Exporta el listado respetando el filtro y el orden actuales de la tabla.
     * $format: 'xlsx' o 'csv' — Maatwebsite\Excel infiere el escritor por la extensión.
     */
    public function export(string $format)
    {
        Gate::authorize('viewAny', ServicePoint::class); // mismo permiso que ver el listado

        $query = $this->applySort(ServicePoint::query()->search($this->filterName));

        $export = new GenericExport(
            query: $query,
            headings: ['Código', 'Nombre', 'Tipo', 'Estado', 'Creado'],
            mapRow: fn (ServicePoint $sp) => [
                $sp->code,
                $sp->name,
                $sp->type->label(),
                $sp->status->label(),
                $sp->created_at->format('Y-m-d'),
            ],
        );

        return Excel::download($export, 'sedes-servicios-'.now()->format('Y-m-d_His').'.'.$format);
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Sedes y Servicios</h4>
            @can('create', ServicePoint::class)
                <button type="button" wire:click="$dispatch('open-service-point-create')" class="btn btn-primary btn-sm">
                    Agregar
                </button>
            @endcan
        </div>

        <div class="card-body">
            <div class="row mb-3 align-items-center g-2">
                <div class="col-md-3">
                    <label class="d-inline-flex align-items-center gap-2">Mostrar
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                        registros
                    </label>
                </div>
                <div class="col-md-6">
                    <input type="search" wire:model.live.debounce.300ms="filterName"
                           class="form-control form-control-sm" placeholder="Buscar por código o nombre...">
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            @foreach (['code' => 'Código', 'name' => 'Nombre', 'type' => 'Tipo', 'status' => 'Estado', 'created_at' => 'Creado'] as $col => $label)
                                <th role="button" wire:click="sort('{{ $col }}')">
                                    {{ $label }}
                                    @if ($sortBy === $col) {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif
                                </th>
                            @endforeach
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->servicePoints as $sp)
                            <tr wire:key="sp-{{ $sp->id }}">
                                <td>{{ $sp->code }}</td>
                                <td>{{ $sp->name }}</td>
                                <td>{{ $sp->type->label() }}</td>
                                <td><span class="badge {{ $sp->status->badgeClass() }}">{{ $sp->status->label() }}</span></td>
                                <td>{{ $sp->created_at->format('Y/m/d') }}</td>
                                <td class="text-center">
                                    @can('update', $sp)
                                        <button type="button" wire:click="$dispatch('open-service-point-edit', { servicePointId: {{ $sp->id }} })"
                                                class="btn btn-sm btn-outline-primary">Editar</button>
                                    @endcan
                                    @can('delete', $sp)
                                        <button type="button"
                                                x-on:click="confirmAction({ title: 'Eliminar sede/servicio', text: '¿Eliminar {{ addslashes($sp->name) }}?' }).then(() => $wire.delete({{ $sp->id }})).catch(() => {})"
                                                class="btn btn-sm btn-danger">Eliminar</button>
                                    @endcan
                                    <button type="button"
                                            wire:click="$dispatch('open-userstamps-audit', { modelClass: 'App\\Models\\ServicePoint', modelId: {{ $sp->id }} })"
                                            class="btn btn-sm btn-outline-info" title="Auditoría">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span>Mostrando {{ $this->servicePoints->firstItem() ?? 0 }} a {{ $this->servicePoints->lastItem() ?? 0 }} de {{ $this->servicePoints->total() }} registros</span>
                {{ $this->servicePoints->links() }}
            </div>
        </div>
    </div>

    <livewire:service-points.service-point-form-modal />
</div>

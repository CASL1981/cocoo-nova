<?php

use App\Models\User;
use App\Models\Role;
use App\Enums\UserStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Session;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasSortableColumns;
use App\Exports\GenericExport;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.app')] #[Title('Lista de usuarios')] class extends Component
{
    use WithPagination;
    use Notifies;
    use HasAuthScopedSession;

    private const SORTABLE = ['id', 'identification', 'name', 'lastname', 'area', 'email', 'status', 'role_id', 'destination', 'created_at'];
    private const PER_PAGE = [10, 25, 50, 100];

    #[Session(key: 'users.index.sortBy-{authId}')]
    public string $sortBy = 'id';

    #[Session(key: 'users.index.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    #[Session(key: 'users.index.perPage-{authId}')]
    public int $perPage = 10;

    #[Session(key: 'users.index.filterIdentification-{authId}')]
    public string $filterIdentification = '';

    #[Session(key: 'users.index.filterName-{authId}')]
    public string $filterName = '';

    #[Session(key: 'users.index.filterLastname-{authId}')]
    public string $filterLastname = '';

    #[Session(key: 'users.index.filterArea-{authId}')]
    public string $filterArea = '';

    #[Session(key: 'users.index.filterEmail-{authId}')]
    public string $filterEmail = '';

    #[Session(key: 'users.index.filterRoleId-{authId}')]
    public string $filterRoleId = '';

    #[Session(key: 'users.index.filterStatus-{authId}')]
    public string $filterStatus = '';

    #[Session(key: 'users.index.filterServicePointId-{authId}')]
    public string $filterServicePointId = '';

    public int $authId = 0;

    public function boot(): void
    {
        $this->authId = (int) auth()->id();
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    #[On('user-saved')]
    public function refreshUsers(): void
    {
        unset($this->users); // limpia el cache del computed para reflejar el cambio
    }

    protected function sortableColumns(): array
    {
        return ['id', 'identification', 'name', 'lastname', 'area', 'email', 'status', 'role_id', 'destination', 'created_at'];
    }

    // Hook genérico: cualquier propiedad que cambie y empiece por "filter", o el tamaño de página, resetea la paginación
    public function updated(string $property): void
    {
        if ($property === 'perPage' || str_starts_with($property, 'filter')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterIdentification', 'filterName', 'filterLastname', 'filterArea',
            'filterEmail', 'filterRoleId', 'filterStatus', 'filterServicePointId',
        ]);
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        $this->sortDirection = ($this->sortBy === $column && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    #[Computed]
    public function roleOptions()
    {
        return Role::query()->orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $column = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'id';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $perPage = in_array($this->perPage, self::PER_PAGE, true) ? $this->perPage : 10;

        return User::query()
            ->with('role') // evita N+1 al mostrar $user->role->name en la tabla
            ->with('servicePoint')
            ->when($this->filterIdentification, fn ($q, $v) => $q->where('identification', 'like', "%{$v}%"))
            ->when($this->filterName, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($this->filterLastname, fn ($q, $v) => $q->where('lastname', 'like', "%{$v}%"))
            ->when($this->filterArea, fn ($q, $v) => $q->where('area', 'like', "%{$v}%"))
            ->when($this->filterEmail, fn ($q, $v) => $q->where('email', 'like', "%{$v}%"))
            ->when($this->filterRoleId, fn ($q, $v) => $q->where('role_id', $v))
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filterServicePointId, fn ($q, $v) => $q->where('service_point_id', $v))
            ->orderBy($column, $direction)
            ->paginate($perPage);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('delete', $user);

        $user->delete();
        // session()->flash('success', 'Usuario eliminado correctamente');
        $this->toastSuccess('Usuario eliminado correctamente');
    }

    /**
     * Exporta el listado de usuarios respetando los filtros y el orden activos
     * en pantalla, igual que la exportación de ServicePoint. $format: 'xlsx' | 'csv'.
     */
    public function export(string $format)
    {
        Gate::authorize('viewAny', User::class);

        $column = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'id';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $query = User::query()
            ->with(['role', 'servicePoint'])
            ->when($this->filterIdentification, fn ($q, $v) => $q->where('identification', 'like', "%{$v}%"))
            ->when($this->filterName, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($this->filterLastname, fn ($q, $v) => $q->where('lastname', 'like', "%{$v}%"))
            ->when($this->filterArea, fn ($q, $v) => $q->where('area', 'like', "%{$v}%"))
            ->when($this->filterEmail, fn ($q, $v) => $q->where('email', 'like', "%{$v}%"))
            ->when($this->filterRoleId, fn ($q, $v) => $q->where('role_id', $v))
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v))
            ->orderBy($column, $direction);

        $export = new GenericExport(
            query: $query,
            headings: ['Identificación', 'Nombre', 'Apellido', 'Área', 'Correo', 'Rol', 'Sede/Servicio', 'Estado', 'Creado'],
            mapRow: fn (User $u) => [
                $u->identification,
                $u->name,
                $u->lastname,
                $u->area,
                $u->email,
                $u->role?->name,
                $u->servicePoint?->name,
                $u->status->label(),
                $u->created_at->format('Y-m-d'),
            ],
        );

        return Excel::download($export, 'usuarios-'.now()->format('Y-m-d_His').'.'.$format);
    }
};
?>

<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Lista de usuarios</h4>
            @can('create', User::class)
                <button type="button" wire:click="$dispatch('open-user-create')" class="btn btn-primary btn-sm">
                    Agregar usuario
                </button>
            @endcan
        </div>

        <div class="card-body">
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <label class="d-inline-flex align-items-center gap-2">Mostrar
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                        registros
                    </label>
                </div>
                <div class="col-md-6 text-md-end">
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

            <div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50" x-data>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            @foreach (['id' => 'ID', 'identification' => 'Identificación', 'name' => 'Nombre', 'lastname' => 'Apellido', 'area' => 'Área',
                                       'email' => 'Correo', 'role_id' => 'Rol', 'status' => 'Estado', 'destination' => 'Centro Costo', 'created_at' => 'Fecha de alta'] as $col => $label)
                                <th role="button" wire:click="sort('{{ $col }}')">
                                    {{ $label }}
                                    @if ($sortBy === $col) {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif
                                </th>
                            @endforeach
                            <th class="text-center">Acciones</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th><input type="text" wire:model.live.debounce.300ms="filterIdentification" class="form-control form-control-sm"></th>
                            <th><input type="text" wire:model.live.debounce.300ms="filterName" class="form-control form-control-sm"></th>
                            <th><input type="text" wire:model.live.debounce.300ms="filterLastname" class="form-control form-control-sm"></th>
                            <th><input type="text" wire:model.live.debounce.300ms="filterArea" class="form-control form-control-sm"></th>
                            <th><input type="text" wire:model.live.debounce.300ms="filterEmail" class="form-control form-control-sm"></th>
                            <th>
                                <x-forms.select wire:model.live="filterRoleId" :options="$this->roleOptions" placeholder="Todos" />
                            </th>
                            <th>
                                <x-forms.select wire:model.live="filterStatus" :options="UserStatus::options()" placeholder="Todos" />
                            </th>
                            <th><input type="text" wire:model.live.debounce.300ms="filterServicePointId" class="form-control form-control-sm"></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->identification }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->lastname }}</td>
                                <td>{{ $user->area ?? '-' }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->name ?? '-' }}</td>
                                <td>
                                    @if ($user->status)
                                        <span class="badge {{ $user->status->badgeClass() }}">{{ $user->status->label() }}</span>
                                    @endif
                                </td>
                                <td>{{ $user->servicePoint?->name ?? '-' }}</td>
                                <td>{{ $user->created_at->format('Y/m/d') }}</td>
                                <td class="text-center">
                                    @can('update', $user)
                                        <button type="button" wire:click="$dispatch('open-user-edit', { userId: {{ $user->id }} })"
                                                class="btn btn-sm btn-outline-primary">Editar</button>
                                    @endcan
                                    @can('delete', $user)
                                        <button type="button"
                                                x-on:click="confirmAction({
                                                    title: 'Eliminar usuario',
                                                    text: '¿Eliminar a {{ addslashes($user->name.' '.$user->lastname) }}? Esta acción no se puede deshacer.'
                                                }).then(() => $wire.delete({{ $user->id }})).catch(() => {})"
                                                class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    @endcan
                                    <button type="button"
                                            wire:click="$dispatch('open-userstamps-audit', { modelClass: 'App\\Models\\User', modelId: {{ $user->id }} })"
                                            class="btn btn-sm btn-outline-info" title="Auditoría">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-4">Sin resultados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span>Mostrando {{ $this->users->firstItem() ?? 0 }} a {{ $this->users->lastItem() ?? 0 }} de {{ $this->users->total() }} registros</span>
                {{ $this->users->links() }}
            </div>
        </div>
    </div>
    <livewire:users.user-form-modal />
</div>

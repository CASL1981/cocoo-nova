<?php

use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Role;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Roles')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, Notifies;

    private const PER_PAGE = [10, 25, 50, 100];

    #[Session(key: 'roles.index.perPage-{authId}')]
    public int $perPage = 10;

    #[Session(key: 'roles.index.filterName-{authId}')]
    public string $filterName = '';

    #[Session(key: 'roles.index.sortBy-{authId}')]
    public string $sortBy = 'id';

    #[Session(key: 'roles.index.sortDirection-{authId}')]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
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
        return ['id', 'name', 'created_at'];
    }

    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, self::PER_PAGE, true) ? $this->perPage : 10;

        $query = Role::query()
            ->withCount('assignedUsers')
            ->when($this->filterName, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"));

        return $this->applySort($query)->paginate($perPage);
    }

    #[On('role-saved')]
    public function refreshRoles(): void
    {
        unset($this->roles);
    }

    #[Computed]
    public function topRoles()
    {
        return Role::query()
            ->withCount('assignedUsers')
            ->orderByDesc('assigned_users_count')
            ->take(5)
            ->with(['assignedUsers' => fn ($q) => $q->limit(3)])
            ->get();
    }

    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);
        Gate::authorize('delete', $role);

        if ($role->assignedUsers()->exists()) {
            $this->toastError('No puedes eliminar un rol con usuarios asignados.');
            return;
        }

        $role->delete();
        $this->toastSuccess('Rol eliminado correctamente');
    }
};
?>

<div x-data>
    <div class="row g-3 mb-4 roles-grid">
        @foreach ($this->topRoles as $role)
            <div class="col-md-4">
                <div class="card role-card">
                    <div class="card-body role-card-body">

                        <div class="role-card-top">
                            <span class="role-card-count">
                                Total {{ $role->assigned_users_count }} usuarios
                            </span>

                            <div class="role-card-avatars">
                                @foreach ($role->assignedUsers as $user)
                                    <img
                                        src="{{ $user->profile_photo_path
                                            ? Storage::disk('public')->url($user->profile_photo_path)
                                            : 'https://ui-avatars.com/api/?name='.urlencode($user->name.' '.$user->lastname).'&background=random' }}"
                                        class="role-avatar"
                                        title="{{ $user->name }} {{ $user->lastname }}"
                                        alt="{{ $user->name }}"
                                    >
                                @endforeach

                                @if ($role->assigned_users_count > $role->assignedUsers->count())
                                    <span class="role-avatar role-avatar-more">
                                        +{{ $role->assigned_users_count - $role->assignedUsers->count() }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <h5 class="role-card-title">
                            {{ $role?->name ?? 'Sin rol asignado' }}
                        </h5>
                        @can('update', $role)
                        <button type="button" wire:click="$dispatch('open-role-edit', { roleId: {{ $role->id }} })" class="btn btn-link p-0 role-card-link">
                            <i class="bi bi-pencil-square me-1"></i>
                            Editar rol
                        </button>
                        @endcan
                        <br>
                        @can('managePermissions', $role)
                        <button type="button" wire:click="$dispatch('open-role-permissions', { roleId: {{ $role->id }} })" class="btn btn-link p-0 role-card-link">
                            <i class="bi bi-key me-1"></i>
                            Permisos
                        </button>
                        @endcan
                        <br>
                        @can('view', $role)
                        <button type="button" wire:click="$dispatch('view-role-permissions', { roleId: {{ $role->id }} })" class="btn btn-link p-0 role-card-link">
                            <i class="bi bi-eye me-1"></i>
                                Ver permisos
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach

        @can('create', Role::class)
            <div class="col-md-4">
                <div class="card role-card role-add-card">
                    <div class="card-body role-card-body">

                        <button
                            type="button"
                            wire:click="$dispatch('open-role-create')"
                            class="btn btn-primary btn-sm"
                        >
                            Agregar rol
                        </button>

                        <span class="role-add-text">
                            Crea un nuevo rol si aún no existe.
                        </span>

                    </div>
                </div>
            </div>
        @endcan
    </div>


    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Roles</h4>
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
                        class="form-control form-control-sm" placeholder="Buscar rol por nombre...">
                </div>
                <div class="col-md-3 text-md-end">
                    <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <div class="table-responsive table-scroll-10 border rounded" wire:loading.class="opacity-50">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            @foreach (['id' => 'ID', 'name' => 'Nombre', 'created_at' => 'Fecha de alta'] as $col => $label)
                                <th role="button" wire:click="sort('{{ $col }}')">
                                    {{ $label }}
                                    @if ($sortBy === $col) {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif
                                </th>
                            @endforeach
                            <th>Usuarios</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->roles as $role)
                            <tr wire:key="role-{{ $role->id }}">
                                <td>{{ $role->id }}</td>
                                <td>{{ $role?->name ?? 'Sin rol asignado' }}</td>
                                <td>{{ $role->created_at->format('Y/m/d') }}</td>
                                <td>{{ $role->assigned_users_count }}</td>
                                <td class="text-center">
                                    @can('update', $role)
                                        <button type="button" wire:click="$dispatch('open-role-edit', { roleId: {{ $role->id }} })"
                                                class="btn btn-sm btn-outline-primary">Editar</button>
                                    @endcan
                                    @can('view', $role)
                                        <button type="button" wire:click="$dispatch('view-role-permissions', { roleId: {{ $role->id }} })"
                                                class="btn btn-sm btn-outline-info">Ver permisos</button>
                                    @endcan
                                    @can('managePermissions', $role)
                                        <button type="button" wire:click="$dispatch('open-role-permissions', { roleId: {{ $role->id }} })"
                                                class="btn btn-sm btn-outline-secondary">Permisos</button>
                                    @endcan
                                    @can('delete', $role)
                                        <button type="button"
                                                x-on:click="confirmAction({
                                                    title: 'Eliminar rol',
                                                    text: '¿Eliminar el rol {{ addslashes($role?->name ?? 'Sin rol asignado') }}?'
                                                }).then(() => $wire.delete({{ $role->id }})).catch(() => {})"
                                                class="btn btn-sm btn-danger">Eliminar</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span>Mostrando {{ $this->roles->firstItem() ?? 0 }} a {{ $this->roles->lastItem() ?? 0 }} de {{ $this->roles->total() }} registros</span>
                {{ $this->roles->links() }}
            </div>
        </div>
    </div>

    <livewire:roles.role-form-modal />
    <livewire:roles.role-permissions-modal />
</div>

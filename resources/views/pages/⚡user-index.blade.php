<?php

use App\Enums\UserStatus;
use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Process;
use App\Models\Role;
use App\Models\ServicePoint;
use App\Models\User;
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
 * Listado de usuarios con la interfaz estándar: filtros por columna, selección,
 * acciones masivas (eliminar, restaurar, habilitar solicitudes de suministros),
 * auditoría y exportación.
 */
new #[Layout('layouts.app')] #[Title('Lista de usuarios')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions,
        HasColumnFilters, HasColumnVisibility, ExportsRows, Notifies;

    #[Session(key: 'users.index.filterIdentification-{authId}')] public string $filterIdentification = '';
    #[Session(key: 'users.index.filterName-{authId}')] public string $filterName = '';
    #[Session(key: 'users.index.filterLastname-{authId}')] public string $filterLastname = '';
    #[Session(key: 'users.index.filterEmail-{authId}')] public string $filterEmail = '';
    #[Session(key: 'users.index.filterProcessId-{authId}')] public string $filterProcessId = '';
    #[Session(key: 'users.index.filterRoleId-{authId}')] public string $filterRoleId = '';
    #[Session(key: 'users.index.filterServicePointId-{authId}')] public string $filterServicePointId = '';
    #[Session(key: 'users.index.filterCanRequestSupplies-{authId}')] public string $filterCanRequestSupplies = '';
    #[Session(key: 'users.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'users.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'users.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'users.index.sortBy-{authId}')] public string $sortBy = 'name';
    #[Session(key: 'users.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    protected function rowModelClass(): string { return User::class; }
    protected function computedRowsProperty(): string { return 'users'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /** Consulta base del listado y la exportación, incluidos los eliminados. */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(
            User::query()->withTrashed()->with(['role:id,name', 'servicePoint:id,name', 'process:id,name'])
        );
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('user-saved')]
    public function refreshUsers(): void
    {
        unset($this->users);
    }

    /**
     * Habilita o deshabilita las requisiciones de suministros de los usuarios seleccionados.
     * Para habilitar, el usuario debe tener proceso (define quién aprueba) y sede (su C.U.).
     */
    public function setSupplyRequests(bool $enabled): void
    {
        $count = $skipped = 0;

        foreach (User::whereKey($this->selectedIds())->get() as $user) {
            if (Gate::denies('update', $user) || ($enabled && ($user->process_id === null || $user->service_point_id === null))) {
                $skipped++;

                continue;
            }

            $user->update(['can_request_supplies' => $enabled]);
            $count++;
        }

        $this->finishBulkAction($count, $enabled ? 'habilitado(s) para solicitar suministros' : 'deshabilitado(s) para solicitar suministros',
            $skipped, 'no tienen proceso o sede asignados, o no tienes permiso.');
    }

    protected function exportFileName(): string { return 'usuarios'; }

    protected function exportHeadings(): array
    {
        return [...array_column($this->columns(), 'label'), 'Eliminado'];
    }

    /** @param User $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->identification, $row->name, $row->lastname, $row->email, $row->process?->name, $row->role?->name,
            $row->servicePoint?->name, $row->can_request_supplies ? 'Sí' : 'No', $row->status?->label(),
            $row->created_at->format('Y-m-d'), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro, orden y exportación de cada columna. */
    protected function columns(): array
    {
        return [
            'identification' => ['label' => 'Identificación', 'filter' => 'filterIdentification', 'hideable' => false],
            'name' => ['label' => 'Nombre', 'width' => 'md', 'filter' => 'filterName'],
            'lastname' => ['label' => 'Apellido', 'width' => 'md', 'filter' => 'filterLastname'],
            'email' => ['label' => 'Correo', 'width' => 'lg', 'filter' => 'filterEmail'],
            'process_id' => ['label' => 'Proceso', 'width' => 'md', 'filter' => 'filterProcessId', 'type' => 'select',
                             'options' => Process::query()->orderBy('name')->pluck('name', 'id')->all()],
            'role_id' => ['label' => 'Rol', 'width' => 'sm', 'filter' => 'filterRoleId', 'type' => 'select',
                          'options' => Role::query()->orderBy('name')->pluck('name', 'id')->all()],
            'service_point_id' => ['label' => 'Sede', 'width' => 'md', 'filter' => 'filterServicePointId', 'type' => 'select',
                                   'options' => ServicePoint::query()->orderBy('name')->pluck('name', 'id')->all()],
            'can_request_supplies' => ['label' => 'Solicita suministros', 'filter' => 'filterCanRequestSupplies', 'type' => 'select',
                                       'options' => ['1' => 'Sí', '0' => 'No']],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => UserStatus::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Lista de usuarios</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->users" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="users" :model-class="User::class" :selected="$selected"
                                     :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                     create-event="open-user-create" edit-event="open-user-edit" edit-param="userId" :toggle="false">
                        @can('users-update')
                            <x-tooltip text="Habilitar solicitud de suministros">
                                <button type="button" wire:click="setSupplyRequests(true)" wire:loading.attr="disabled" wire:target="setSupplyRequests"
                                        @disabled(count($selected) === 0) class="btn btn-sm btn-outline-success btn-table-icon"
                                        aria-label="Habilitar solicitud de suministros"><i class="bi bi-cart-check"></i></button>
                            </x-tooltip>
                            <x-tooltip text="Deshabilitar solicitud de suministros">
                                <button type="button" wire:click="setSupplyRequests(false)" wire:loading.attr="disabled" wire:target="setSupplyRequests"
                                        @disabled(count($selected) === 0) class="btn btn-sm btn-outline-secondary btn-table-icon"
                                        aria-label="Deshabilitar solicitud de suministros"><i class="bi bi-cart-x"></i></button>
                            </x-tooltip>
                        @endcan
                    </x-table.actions>
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->users->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->users->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->users->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="{{ $this->rowStatusClass($user) }}">
                        <x-table.row-checkbox :id="$user->id" :selected="$selected" />
                        <td>{{ $user->identification }} @if ($user->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->lastname }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->process?->name ?? '-' }}</td>
                        <td>{{ $user->role?->name ?? '-' }}</td>
                        <td>{{ $user->servicePoint?->name ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $user->can_request_supplies ? 'bg-success' : 'bg-secondary' }}">{{ $user->can_request_supplies ? 'Sí' : 'No' }}</span>
                        </td>
                        <td>
                            @if ($user->status)
                                <span class="badge {{ $user->status->badgeClass() }}">{{ $user->status->label() }}</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:users.user-form-modal />
</div>

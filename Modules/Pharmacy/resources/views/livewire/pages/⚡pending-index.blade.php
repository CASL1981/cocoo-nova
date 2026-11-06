<?php

use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\DuplicatesRows;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Contracts\RowImporter;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\ImportsRows;
use App\Livewire\Concerns\Notifies;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Imports\PendingImporter;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Página de pendientes de farmacia (encabezados).
 * El listado respeta la preferencia de sede configurada en el perfil del usuario.
 */
new #[Layout('layouts.app')] #[Title('Pendientes')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions, DuplicatesRows,
        HasColumnFilters, HasColumnVisibility, ExportsRows, ImportsRows, Notifies;

    #[Session(key: 'pendings.index.filterId-{authId}')] public string $filterId = '';
    #[Session(key: 'pendings.index.filterType-{authId}')] public string $filterType = '';
    #[Session(key: 'pendings.index.filterCategory-{authId}')] public string $filterCategory = '';
    #[Session(key: 'pendings.index.filterDestination-{authId}')] public string $filterDestination = '';
    #[Session(key: 'pendings.index.filterReason-{authId}')] public string $filterReason = '';
    #[Session(key: 'pendings.index.filterEps-{authId}')] public string $filterEps = '';
    #[Session(key: 'pendings.index.filterManager-{authId}')] public string $filterManager = '';
    #[Session(key: 'pendings.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'pendings.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'pendings.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'pendings.index.sortBy-{authId}')] public string $sortBy = 'id';
    #[Session(key: 'pendings.index.sortDirection-{authId}')] public string $sortDirection = 'desc';

    protected function rowModelClass(): string { return Pending::class; }
    protected function computedRowsProperty(): string { return 'pendings'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', Pending::class);
    }

    /**
     * Consulta base del listado y la exportación, incluidos los eliminados
     * y limitada a la sede preferida del usuario (si la tiene).
     */
    protected function baseQuery(): Builder
    {
        $query = Pending::query()->withTrashed()
            ->with(['destination:id,code,name', 'user:id,name,lastname'])
            ->visibleTo(Auth::user());

        return $this->applyColumnFilters($query);
    }

    #[Computed]
    public function pendings(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('pending-saved')]
    public function refreshPendings(): void
    {
        unset($this->pendings);
    }

    /** Un único pendiente seleccionado, no eliminado y Activo (los terminados no se editan). */
    public function singleEditableSelected(): bool
    {
        if (count($this->selected) !== 1) {
            return false;
        }

        // La Policy decide (estado, permiso y sede) para que el botón Editar refleje las mismas reglas.
        $pending = Pending::find($this->selectedIds()[0]);

        return $pending !== null && Gate::allows('update', $pending);
    }

    /** Elimina los pendientes seleccionados que el usuario pueda eliminar (Policy delete). */
    public function deleteSelected(): void
    {
        $this->runBulkAction('delete', fn (Pending $row) => $row->delete(), 'eliminado(s)',
            'solo puedes eliminar pendientes activos con destino en tu sede (Oficina Principal puede eliminar los de cualquier sede).');
    }

    /** Termina los pendientes seleccionados que el usuario pueda terminar (Policy finish). */
    public function finishSelected(): void
    {
        $this->runBulkAction('finish', fn (Pending $row) => $row->finish(), 'terminado(s)',
            'solo puedes terminar pendientes activos con ítems y destino en tu sede, creados por ti o con permiso de procesar (Oficina Principal puede terminar los de cualquier sede).');
    }

    /** Reabre los pendientes terminados seleccionados (requiere permiso de reversar). */
    public function reopenSelected(): void
    {
        $this->runBulkAction('reopen', fn (Pending $row) => $row->reopen(), 'reabierto(s)',
            'solo se pueden reabrir pendientes terminados.');
    }

    /** Opciones del filtro de destino: sedes como "código - nombre". */
    #[Computed]
    public function destinationOptions(): array
    {
        return ServicePoint::withTrashed()->orderBy('code')->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->id => "{$point->code}"])
            ->all();
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return CharacteristicOption::forCharacteristicCode(Pending::CATEGORY_CHARACTERISTIC_CODE)->all();
    }

    #[Computed]
    public function epsOptions(): array
    {
        return CharacteristicOption::forCharacteristicCode(Pending::EPS_CHARACTERISTIC_CODE)->all();
    }

    /** Importación de solo creación: el usuario autenticado queda como solicitante. */
    protected function importer(): RowImporter
    {
        /** @var User $user */
        $user = Auth::user();

        return new PendingImporter($user);
    }

    protected function afterImport(): void
    {
        $this->clearSelection();
        unset($this->pendings);
    }

    protected function exportFileName(): string { return 'pendientes'; }

    protected function exportHeadings(): array
    {
        return [
            'N°', 'Tipo', 'Categoría', 'Destino', 'Solicitante', 'Motivo', 'Duración', 'EPS',
            'Modalidad de contratación', 'Forma de facturación', 'Autoriza', 'Observaciones',
            'Estado', 'Creado', 'Eliminado',
        ];
    }

    /** @param Pending $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->id, $row->type->label(), $row->category,
            $row->destination ? "{$row->destination->code} - {$row->destination->name}" : '',
            $row->user?->fullName(), $row->reason, $row->duration, $row->eps,
            $row->contracting_modality, $row->invoicing_method, $row->manager, $row->observations,
            $row->status->label(), $row->created_at->format('Y-m-d'), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y orden de cada columna. */
    protected function columns(): array
    {
        return [
            'id' => ['label' => 'N°', 'filter' => 'filterId'],
            'type' => ['label' => 'Tipo', 'filter' => 'filterType', 'type' => 'select', 'options' => PendingType::options()],
            'category' => ['label' => 'Categoría', 'width' => 'md', 'filter' => 'filterCategory', 'type' => 'select', 'options' => $this->categoryOptions],
            'destination_id' => ['label' => 'Destino', 'filter' => 'filterDestination', 'type' => 'select', 'options' => $this->destinationOptions],
            'user_id' => ['label' => 'Solicitante', 'width' => 'md', 'filter' => null],
            'reason' => ['label' => 'Motivo', 'width' => 'lg', 'filter' => 'filterReason'],
            'eps' => ['label' => 'EPS', 'width' => 'md', 'filter' => 'filterEps', 'type' => 'select', 'options' => $this->epsOptions],
            'manager' => ['label' => 'Autoriza', 'width' => 'md', 'filter' => 'filterManager'],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => PendingStatus::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Pendientes</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->pendings" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-left>
                    @if (Auth::user()->pending_service_point_id)
                        <span class="badge bg-info ms-2" title="Configurable en tu perfil">Vista limitada a tu sede preferida</span>
                    @endif
                </x-slot:toolbar-left>

                <x-slot:toolbar-right>
                    <x-table.actions module="pendings" :model-class="Pending::class" :selected="$selected"
                                     :single-active="$this->singleEditableSelected()" :toggle="false" :selected-count="$this->selectedCount()"
                                     create-event="open-pending-create" edit-event="open-pending-edit" edit-param="pendingId"
                                     duplicable :single-duplicable="$this->singleActiveSelected()"
                                     importable :import-errors="$importErrors">
                        {{-- Importación de ítems a varios pendientes activos (solo Oficina Principal). --}}
                        <livewire:pharmacy::pendings.pending-items-import />
                        {{-- Ítems: abre el detalle del pendiente seleccionado (no eliminado). --}}
                        <x-tooltip text="Ver y gestionar los ítems del pendiente seleccionado">
                            <a href="{{ $this->singleActiveSelected() ? route('pendings.show', $selected[0]) : '#' }}" wire:navigate
                               @class(['btn btn-sm btn-outline-primary btn-table-icon', 'disabled' => ! $this->singleActiveSelected()])
                               aria-label="Ver y gestionar los ítems del pendiente seleccionado">
                                <i class="bi bi-list-check"></i>
                            </a>
                        </x-tooltip>
                        {{-- Terminar: el solicitante o quien tenga permiso de procesar; la Policy filtra por fila. --}}
                        <x-tooltip text="Terminar pendientes seleccionados">
                            <button type="button"
                                    x-on:click="confirmAction({ title: 'Terminar pendientes', text: '¿Terminar {{ $this->selectedCount() }} pendiente(s)? Farmacia ya no podrá editarlos.' }).then(() => $wire.finishSelected()).catch(() => {})"
                                    @disabled(count($selected) === 0) class="btn btn-sm btn-outline-success btn-table-icon" aria-label="Terminar pendientes seleccionados">
                                <i class="bi bi-check2-circle"></i>
                            </button>
                        </x-tooltip>
                        @can('pendings-reverse')
                            <x-tooltip text="Reabrir pendientes terminados seleccionados">
                                <button type="button"
                                        x-on:click="confirmAction({ title: 'Reabrir pendientes', text: '¿Reabrir {{ $this->selectedCount() }} pendiente(s) terminado(s)?' }).then(() => $wire.reopenSelected()).catch(() => {})"
                                        @disabled(count($selected) === 0) class="btn btn-sm btn-outline-warning btn-table-icon" aria-label="Reabrir pendientes terminados seleccionados">
                                    <i class="bi bi-unlock"></i>
                                </button>
                            </x-tooltip>
                        @endcan
                    </x-table.actions>
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->pendings->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->pendings->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->pendings->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->pendings as $pending)
                    <tr wire:key="pending-{{ $pending->id }}" class="{{ $this->rowStatusClass($pending) }}">
                        <x-table.row-checkbox :id="$pending->id" :selected="$selected" />
                        <td>{{ $pending->id }} @if ($pending->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td title="{{ $pending->type->label() }}">{{ $pending->type->value }}</td>
                        <td>{{ $pending->category }}</td>
                        <td>{{ $pending->destination?->code }}</td>
                        <td>{{ $pending->user?->fullName() }}</td>
                        <td>{{ $pending->reason ?? '-' }}</td>
                        <td>{{ $pending->eps ?? '-' }}</td>
                        <td>{{ $pending->manager ?? '-' }}</td>
                        <td><span class="badge {{ $pending->status->badgeClass() }}">{{ $pending->status->label() }}</span></td>
                        <td>{{ $pending->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:pharmacy::pendings.pending-form-modal />
</div>

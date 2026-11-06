<?php

use App\Enums\Status;
use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Process;
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
 * Procesos (áreas) de la organización y su director, quien aprueba las
 * requisiciones del módulo Administrativo.
 */
new #[Layout('layouts.app')] #[Title('Procesos')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions,
        HasColumnFilters, HasColumnVisibility, ExportsRows, Notifies;

    #[Session(key: 'processes.index.filterName-{authId}')] public string $filterName = '';
    #[Session(key: 'processes.index.filterStatus-{authId}')] public string $filterStatus = '';
    #[Session(key: 'processes.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'processes.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'processes.index.sortBy-{authId}')] public string $sortBy = 'name';
    #[Session(key: 'processes.index.sortDirection-{authId}')] public string $sortDirection = 'asc';

    protected function rowModelClass(): string { return Process::class; }
    protected function computedRowsProperty(): string { return 'processes'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', Process::class);
    }

    /** Consulta base del listado y la exportación, incluidos los eliminados. */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(
            Process::query()->withTrashed()->with('director:id,name,lastname')->withCount('users')
        );
    }

    #[Computed]
    public function processes(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('process-saved')]
    public function refreshProcesses(): void
    {
        unset($this->processes);
    }

    /** Sobrescribe la eliminación masiva para explicar por qué se omiten procesos. */
    public function deleteSelected(): void
    {
        $this->runBulkAction('delete', fn (Process $row) => $row->delete(), 'eliminado(s)',
            'tienen usuarios asignados (reasígnelos primero) o no tienes permiso.');
    }

    protected function exportFileName(): string { return 'procesos'; }

    protected function exportHeadings(): array
    {
        return [...array_column($this->columns(), 'label'), 'Eliminado'];
    }

    /** @param Process $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->name, $row->director?->fullName(), $row->users_count, $row->status->label(),
            $row->created_at->format('Y-m-d'), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y exportación de cada columna. */
    protected function columns(): array
    {
        return [
            'name' => ['label' => 'Proceso', 'width' => 'lg', 'filter' => 'filterName', 'hideable' => false],
            'director_id' => ['label' => 'Director', 'width' => 'lg', 'filter' => null],
            'users_count' => ['label' => 'Usuarios', 'filter' => null],
            'status' => ['label' => 'Estado', 'filter' => 'filterStatus', 'type' => 'select', 'options' => Status::options()],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Procesos</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->processes" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="processes" :model-class="Process::class" :selected="$selected"
                                     :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                     create-event="open-process-create" edit-event="open-process-edit" edit-param="processId" />
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->processes->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->processes->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->processes->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->processes as $process)
                    <tr wire:key="process-{{ $process->id }}" class="{{ $this->rowStatusClass($process) }}">
                        <x-table.row-checkbox :id="$process->id" :selected="$selected" />
                        <td>{{ $process->name }} @if ($process->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>{{ $process->director?->fullName() ?? '-' }}</td>
                        <td>{{ $process->users_count }}</td>
                        <td><span class="badge {{ $process->status->badgeClass() }}">{{ $process->status->label() }}</span></td>
                        <td>{{ $process->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:processes.process-form-modal />
</div>

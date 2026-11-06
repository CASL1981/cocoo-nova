<?php

use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\Process;
use App\Models\ProcessSubstitution;
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
 * Reemplazos de directores de proceso: quién aprueba requisiciones en lugar del
 * director (vacaciones, licencias) o de forma permanente (sin fecha de fin).
 */
new #[Layout('layouts.app')] #[Title('Reemplazos de directores')] class extends Component
{
    use WithPagination, HasAuthScopedSession, HasSortableColumns, HasBulkRowActions,
        HasColumnFilters, HasColumnVisibility, ExportsRows, Notifies;

    #[Session(key: 'process-substitutions.index.filterProcessId-{authId}')] public string $filterProcessId = '';
    #[Session(key: 'process-substitutions.index.filterObservations-{authId}')] public string $filterObservations = '';
    #[Session(key: 'process-substitutions.index.perPage-{authId}')] public int $perPage = 10;
    #[Session(key: 'process-substitutions.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];
    #[Session(key: 'process-substitutions.index.sortBy-{authId}')] public string $sortBy = 'starts_on';
    #[Session(key: 'process-substitutions.index.sortDirection-{authId}')] public string $sortDirection = 'desc';

    protected function rowModelClass(): string { return ProcessSubstitution::class; }
    protected function computedRowsProperty(): string { return 'substitutions'; }

    public function mount(): void
    {
        Gate::authorize('viewAny', ProcessSubstitution::class);
    }

    /** Consulta base del listado y la exportación, incluidos los eliminados. */
    protected function baseQuery(): Builder
    {
        return $this->applyColumnFilters(
            ProcessSubstitution::query()->withTrashed()
                ->with(['process' => fn ($q) => $q->withTrashed()->select('id', 'name', 'director_id'),
                        'process.director:id,name,lastname', 'substitute:id,name,lastname'])
        );
    }

    #[Computed]
    public function substitutions(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('process-substitution-saved')]
    public function refreshSubstitutions(): void
    {
        unset($this->substitutions);
    }

    protected function exportFileName(): string { return 'reemplazos-directores'; }

    protected function exportHeadings(): array
    {
        return ['Proceso', 'Director', 'Reemplazo', 'Desde', 'Hasta', 'Observaciones', 'Creado', 'Eliminado'];
    }

    /** @param ProcessSubstitution $row */
    protected function exportRow(Model $row): array
    {
        return [
            $row->process?->name, $row->process?->director?->fullName(), $row->substitute?->fullName(),
            $row->starts_on->format('Y-m-d'), $row->ends_on?->format('Y-m-d') ?? 'Permanente', $row->observations,
            $row->created_at->format('Y-m-d'), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** Fuente única de verdad: encabezado, filtro y orden de cada columna. */
    protected function columns(): array
    {
        return [
            'process_id' => ['label' => 'Proceso', 'width' => 'md', 'filter' => 'filterProcessId', 'type' => 'select',
                             'options' => Process::query()->orderBy('name')->pluck('name', 'id')->all(), 'hideable' => false],
            'substitute_id' => ['label' => 'Reemplazo', 'width' => 'lg', 'filter' => null],
            'starts_on' => ['label' => 'Desde', 'filter' => null],
            'ends_on' => ['label' => 'Hasta', 'filter' => null],
            'observations' => ['label' => 'Observaciones', 'width' => 'xl', 'filter' => 'filterObservations'],
            'created_at' => ['label' => 'Creado', 'filter' => null],
        ];
    }
};
?>

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Reemplazos de directores</h4></div>

        <div class="card-body">
            <x-data-table :paginator="$this->substitutions" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="process-substitutions" :model-class="ProcessSubstitution::class" :selected="$selected"
                                     :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                     create-event="open-process-substitution-create" edit-event="open-process-substitution-edit"
                                     edit-param="substitutionId" :toggle="false" />
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->substitutions->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->substitutions->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->substitutions->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->substitutions as $substitution)
                    <tr wire:key="substitution-{{ $substitution->id }}" class="{{ $this->rowStatusClass($substitution) }}">
                        <x-table.row-checkbox :id="$substitution->id" :selected="$selected" />
                        <td>{{ $substitution->process?->name ?? '-' }} @if ($substitution->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>{{ $substitution->substitute?->fullName() ?? '-' }}</td>
                        <td>{{ $substitution->starts_on->format('Y/m/d') }}</td>
                        <td>
                            @if ($substitution->isPermanent())
                                <span class="badge bg-info">Permanente</span>
                            @else
                                {{ $substitution->ends_on->format('Y/m/d') }}
                            @endif
                        </td>
                        <td>{{ $substitution->observations ?? '-' }}</td>
                        <td>{{ $substitution->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:processes.process-substitution-form-modal />
</div>

<div>
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Opciones{{ $this->characteristic ? ': '.$this->characteristic->name : '' }}</h5>
            @if ($this->isPositions())
                <p class="text-muted small mb-0">Cada cargo pertenece a un proceso y puede tener un jefe inmediato.</p>
            @endif
        </div>
        <div class="card-body">
            @if ($characteristicId === null)
                <p class="text-muted text-center py-5 mb-0">Elige una característica a la izquierda para ver sus opciones.</p>
            @else
                @if ($this->parentTrashed)
                    <div class="alert alert-warning py-2 small" role="alert">
                        La característica está eliminada: restáurala para gestionar sus opciones.
                    </div>
                @endif

                <x-data-table :paginator="$this->options" :per-page-options="$this->perPageOptions()"
                              :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                    <x-slot:toolbar-right>
                        <x-table.actions module="hr-characteristics" :model-class="\Modules\HumanResources\App\Models\HrCharacteristicOption::class" :selected="$selected"
                                         :single-active="! $this->parentTrashed && $this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                         create-event="open-hr-characteristic-option-create" edit-event="open-hr-characteristic-option-edit" edit-param="optionId" duplicable />
                    </x-slot:toolbar-right>

                    <x-slot:before-table>
                        <x-table.selection-banner :page-ids="$this->options->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                                  :matching-count="$this->matchingSelectionCount($this->options->pluck('id')->all())" />
                    </x-slot:before-table>

                    <x-slot:head>
                        <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                      :page-ids="$this->options->pluck('id')->all()" :selected="$selected" />
                    </x-slot:head>

                    <x-slot:filters>
                        <x-table.filters :columns="$this->columns()" />
                    </x-slot:filters>

                    @forelse ($this->options as $option)
                        <tr wire:key="hr-option-{{ $option->id }}" class="{{ $this->rowStatusClass($option) }}">
                            <x-table.row-checkbox :id="$option->id" :selected="$selected" />
                            <td>{{ $option->name }} @if ($option->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                            @if ($this->isPositions())
                                <td>{{ $option->process?->name ?? '-' }}</td>
                                <td>{{ $option->parent?->name ?? '-' }}</td>
                            @endif
                            <td><span class="badge {{ $option->status->badgeClass() }}">{{ $option->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $this->isPositions() ? 5 : 3 }}" class="text-center text-muted py-4">Sin opciones</td></tr>
                    @endforelse
                </x-data-table>
            @endif
        </div>
    </div>

    <livewire:humanresources::characteristics.hr-characteristic-option-form-modal />
</div>

<div>
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Características</h5>
            <p class="text-muted small mb-0">Elige una para ver sus opciones.</p>
        </div>
        <div class="card-body">
            <x-data-table :paginator="$this->characteristics" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions module="hr-characteristics" :model-class="\Modules\HumanResources\App\Models\HrCharacteristic::class" :selected="$selected"
                                     :single-active="$this->singleActiveSelected()" :selected-count="$this->selectedCount()"
                                     create-event="open-hr-characteristic-create" edit-event="open-hr-characteristic-edit" edit-param="characteristicId" duplicable />
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$this->characteristics->pluck('id')->all()" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($this->characteristics->pluck('id')->all())" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$this->characteristics->pluck('id')->all()" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->characteristics as $characteristic)
                    <tr wire:key="hr-characteristic-{{ $characteristic->id }}" role="button" wire:click="selectCharacteristic({{ $characteristic->id }})"
                        class="{{ $this->rowStatusClass($characteristic) }} {{ $activeCharacteristicId === $characteristic->id ? 'table-active' : '' }}">
                        <x-table.row-checkbox :id="$characteristic->id" :selected="$selected" />
                        <td>{{ $characteristic->name }} @if ($characteristic->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td><code>{{ $characteristic->code }}</code></td>
                        <td><span class="badge {{ $characteristic->status->badgeClass() }}">{{ $characteristic->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:humanresources::characteristics.hr-characteristic-form-modal />
</div>
